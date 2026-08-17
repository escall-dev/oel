<?php
require_once __DIR__ . '/config/db.php';
requireLogin();

$user = currentUser();

// Handle Form Submissions (Add / Edit / Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Only Admin & Encoder can create/edit/delete
    if (!hasRole(['admin', 'encoder'])) {
        setFlash('danger', 'Viewer role cannot modify document entries.');
        header("Location: incoming.php");
        exit();
    }

    $formAction = $_POST['form_action'] ?? '';

    // DELETE DOCUMENT
    if ($formAction === 'delete') {
        if (!hasRole('admin')) {
            setFlash('danger', 'Only administrators can delete log records.');
            header("Location: incoming.php");
            exit();
        }
        $docId = intval($_POST['document_id'] ?? 0);
        if ($docId > 0) {
            // Retrieve attachment file paths to delete physical files
            $stmtAtt = $pdo->prepare("SELECT id, file_path FROM document_attachments WHERE document_id = :id");
            $stmtAtt->execute([':id' => $docId]);
            $files = $stmtAtt->fetchAll();
            foreach ($files as $f) {
                deleteAttachmentFile($pdo, $f['file_path'], $f['id']);
            }
            // Delete document
            $stmtDel = $pdo->prepare("DELETE FROM documents WHERE id = :id AND direction = 'Incoming'");
            $stmtDel->execute([':id' => $docId]);
            setFlash('success', 'Incoming document record deleted successfully.');
        }
        header("Location: incoming.php");
        exit();
    }

    if ($formAction === 'bulk_delete') {
        if (!hasRole('admin')) {
            setFlash('danger', 'Only administrators can delete log records.');
            header("Location: incoming.php");
            exit();
        }
        $selectedIds = $_POST['selected_ids'] ?? [];
        if (is_array($selectedIds) && !empty($selectedIds)) {
            $ids = array_map('intval', array_filter($selectedIds, 'is_numeric'));
            if (!empty($ids)) {
                $inQuery = implode(',', array_fill(0, count($ids), '?'));
                $stmtAtt = $pdo->prepare("SELECT id, file_path FROM document_attachments WHERE document_id IN ($inQuery)");
                $stmtAtt->execute($ids);
                $files = $stmtAtt->fetchAll();
                foreach ($files as $f) {
                    deleteAttachmentFile($pdo, $f['file_path'], $f['id']);
                }
                $stmtDel = $pdo->prepare("DELETE FROM documents WHERE id IN ($inQuery) AND direction = 'Incoming'");
                $stmtDel->execute($ids);
                setFlash('success', count($ids) . ' incoming document record(s) deleted successfully.');
            }
        }
        header("Location: incoming.php");
        exit();
    }


    // ADD OR EDIT DOCUMENT
    if ($formAction === 'save') {
        $docId = intval($_POST['document_id'] ?? 0);
        $referenceNumber = trim($_POST['reference_number'] ?? '');
        $documentTitle = trim($_POST['document_title'] ?? '');
        $categoryId = intval($_POST['category_id'] ?? 0);
        $documentTypeId = intval($_POST['document_type_id'] ?? 0);
        $originSource = trim($_POST['origin_source'] ?? '');
        $documentDate = trim($_POST['document_date'] ?? '');
        $timeLog = trim($_POST['time_log'] ?? '');
        $remarks = trim($_POST['remarks'] ?? '');

        // Store empty reference as NULL
        $referenceNumber = $referenceNumber === '' ? null : $referenceNumber;
        $timeLog = $timeLog === '' ? null : $timeLog;

        // Basic Server Validation
        if (empty($documentTitle) || $categoryId <= 0 || $documentTypeId <= 0 || empty($documentDate)) {
            setFlash('danger', 'Please fill in all required fields (Title, Category, Type, Date).');
            header("Location: incoming.php");
            exit();
        }

        // Check reference number uniqueness (only if provided)
        if (!empty($referenceNumber)) {
            if ($docId > 0) {
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM documents WHERE reference_number = :ref AND id != :id");
                $stmtCheck->execute([':ref' => $referenceNumber, ':id' => $docId]);
            } else {
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM documents WHERE reference_number = :ref");
                $stmtCheck->execute([':ref' => $referenceNumber]);
            }

            if ($stmtCheck->fetchColumn() > 0) {
                setFlash('danger', "Error: Reference Number '$referenceNumber' already exists in the system.");
                header("Location: incoming.php");
                exit();
            }
        }

        try {
            $pdo->beginTransaction();

            if ($docId > 0) {
                // Update Existing Document
                $stmtUpdate = $pdo->prepare("
                    UPDATE documents SET 
                        reference_number = :ref,
                        document_title = :title,
                        category_id = :cat,
                        document_type_id = :type,
                        origin_source = :origin,
                        document_date = :doc_date,
                        time_log = :time_log,
                        remarks = :remarks
                    WHERE id = :id AND direction = 'Incoming'
                ");
                $stmtUpdate->execute([
                    ':ref' => $referenceNumber,
                    ':title' => $documentTitle,
                    ':cat' => $categoryId,
                    ':type' => $documentTypeId,
                    ':origin' => $originSource,
                    ':doc_date' => $documentDate,
                    ':time_log' => $timeLog,
                    ':remarks' => $remarks,
                    ':id' => $docId
                ]);
                $savedDocId = $docId;
                setFlash('success', 'Incoming document updated successfully.');
            } else {
                // Insert New Document
                $stmtInsert = $pdo->prepare("
                    INSERT INTO documents 
                        (reference_number, document_title, category_id, document_type_id, direction, origin_source, document_date, time_log, remarks, encoded_by)
                    VALUES 
                        (:ref, :title, :cat, :type, 'Incoming', :origin, :doc_date, :time_log, :remarks, :encoded_by)
                ");
                $stmtInsert->execute([
                    ':ref' => $referenceNumber,
                    ':title' => $documentTitle,
                    ':cat' => $categoryId,
                    ':type' => $documentTypeId,
                    ':origin' => $originSource,
                    ':doc_date' => $documentDate,
                    ':time_log' => $timeLog,
                    ':remarks' => $remarks,
                    ':encoded_by' => $user['id']
                ]);
                $savedDocId = $pdo->lastInsertId();
                setFlash('success', 'Incoming document entry logged successfully.');
            }

            // Process Standard Checklist Attachments
            $attachmentItems = $_POST['attachment_items'] ?? [];
            if (is_array($attachmentItems)) {
                foreach ($attachmentItems as $itemId) {
                    $itemId = intval($itemId);
                    $fileKey = "attachment_files_$itemId";
                    if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
                        $filePath = saveUploadedFile($_FILES[$fileKey]);
                        if ($filePath) {
                            $stmtAtt = $pdo->prepare("
                                INSERT INTO document_attachments (document_id, attachment_item_id, file_path) 
                                VALUES (:doc_id, :item_id, :file_path)
                            ");
                            $stmtAtt->execute([
                                ':doc_id' => $savedDocId,
                                ':item_id' => $itemId,
                                ':file_path' => $filePath
                            ]);
                        }
                    }
                }
            }

            // Process Custom Attachments
            $customNames = $_POST['custom_names'] ?? [];
            if (is_array($customNames)) {
                foreach ($customNames as $idx => $cName) {
                    $cName = trim($cName);
                    if (!empty($cName) && isset($_FILES['custom_files']['name'][$idx]) && $_FILES['custom_files']['error'][$idx] === UPLOAD_ERR_OK) {
                        $singleFile = [
                            'name' => $_FILES['custom_files']['name'][$idx],
                            'type' => $_FILES['custom_files']['type'][$idx],
                            'tmp_name' => $_FILES['custom_files']['tmp_name'][$idx],
                            'error' => $_FILES['custom_files']['error'][$idx],
                            'size' => $_FILES['custom_files']['size'][$idx]
                        ];
                        $filePath = saveUploadedFile($singleFile);
                        if ($filePath) {
                            $stmtAtt = $pdo->prepare("
                                INSERT INTO document_attachments (document_id, custom_item_name, file_path) 
                                VALUES (:doc_id, :cname, :file_path)
                            ");
                            $stmtAtt->execute([
                                ':doc_id' => $savedDocId,
                                ':cname' => $cName,
                                ':file_path' => $filePath
                            ]);
                        }
                    }
                }
            }

            $pdo->commit();

        } catch (Exception $e) {
            $pdo->rollBack();
            setFlash('danger', 'Database Error: ' . $e->getMessage());
        }

        header("Location: incoming.php");
        exit();
    }
}

// Search & Filter Parameters
$search = trim($_GET['search'] ?? '');
$filterCategory = intval($_GET['category_id'] ?? 0);
$filterType = intval($_GET['type_id'] ?? 0);
$fromDate = trim($_GET['from_date'] ?? '');
$toDate = trim($_GET['to_date'] ?? '');

// Build Query
$whereClause = ["d.direction = 'Incoming'"];
$params = [];

if (!empty($search)) {
    $whereClause[] = "(d.reference_number LIKE :search1 OR d.document_title LIKE :search2 OR d.origin_source LIKE :search3)";
    $params[':search1'] = "%$search%";
    $params[':search2'] = "%$search%";
    $params[':search3'] = "%$search%";
}

if ($filterCategory > 0) {
    $whereClause[] = "d.category_id = :cat_id";
    $params[':cat_id'] = $filterCategory;
}

if ($filterType > 0) {
    $whereClause[] = "d.document_type_id = :type_id";
    $params[':type_id'] = $filterType;
}

if (!empty($fromDate)) {
    $whereClause[] = "d.document_date >= :from_date";
    $params[':from_date'] = $fromDate;
}

if (!empty($toDate)) {
    $whereClause[] = "d.document_date <= :to_date";
    $params[':to_date'] = $toDate;
}

$whereSql = implode(" AND ", $whereClause);

$stmtDocs = $pdo->prepare("
    SELECT d.*, c.category_name, dt.type_name, u.full_name AS encoder_name
    FROM documents d
    JOIN categories c ON d.category_id = c.id
    JOIN document_types dt ON d.document_type_id = dt.id
    LEFT JOIN users u ON d.encoded_by = u.id
    WHERE $whereSql
    ORDER BY d.id DESC
");
$stmtDocs->execute($params);
$documents = $stmtDocs->fetchAll();

// Fetch Categories & Types for Filter Dropdowns
$allCategories = $pdo->query("SELECT * FROM categories ORDER BY category_name ASC")->fetchAll();
$allTypes = [];
if ($filterCategory > 0) {
    $stmtT = $pdo->prepare("SELECT * FROM document_types WHERE category_id = :c ORDER BY type_name ASC");
    $stmtT->execute([':c' => $filterCategory]);
    $allTypes = $stmtT->fetchAll();
}

// Check if viewing a specific document modal via ?view=ID
$viewDoc = null;
$viewAttachments = [];
$viewDocId = intval($_GET['view'] ?? 0);
if ($viewDocId > 0) {
    $stmtV = $pdo->prepare("
        SELECT d.*, c.category_name, dt.type_name, u.full_name AS encoder_name
        FROM documents d
        JOIN categories c ON d.category_id = c.id
        JOIN document_types dt ON d.document_type_id = dt.id
        LEFT JOIN users u ON d.encoded_by = u.id
        WHERE d.id = :id AND d.direction = 'Incoming'
    ");
    $stmtV->execute([':id' => $viewDocId]);
    $viewDoc = $stmtV->fetch();

    if ($viewDoc) {
        $stmtAtt = $pdo->prepare("
            SELECT da.*, ai.item_name 
            FROM document_attachments da
            LEFT JOIN attachment_items ai ON da.attachment_item_id = ai.id
            WHERE da.document_id = :doc_id
        ");
        $stmtAtt->execute([':doc_id' => $viewDocId]);
        $viewAttachments = $stmtAtt->fetchAll();
    }
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="container-fluid px-4 py-2">
    <!-- Header Title -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-2 border-bottom">
        <div>
            <h3 class="fw-bold mb-1" style="color: var(--primary-color);">
                <i class="bi bi-box-arrow-in-down me-2 text-primary"></i> Incoming Document Logbook
            </h3>
            <p class="text-muted mb-0 small">Repository for all incoming files, letters, and documents received by the office.</p>
        </div>
        <?php if (!hasRole('viewer')): ?>
        <div class="mt-3 mt-md-0">
            <button type="button" class="btn btn-primary-custom shadow-sm" data-bs-toggle="modal" data-bs-target="#addDocumentModal">
                <i class="bi bi-plus-lg me-1"></i> Add Incoming Entry
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- Search & Filter Card -->
    <div class="card card-custom mb-4">
        <div class="card-body p-3">
            <form method="GET" action="incoming.php" class="row g-2 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Search Keyword</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Ref #, Title, or Origin..." value="<?= sanitize($search) ?>">
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Category</label>
                    <select name="category_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <?php foreach ($allCategories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($filterCategory === (int)$cat['id']) ? 'selected' : '' ?>>
                            <?= sanitize($cat['category_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Document Type</label>
                    <select name="type_id" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        <?php foreach ($allTypes as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= ($filterType === (int)$t['id']) ? 'selected' : '' ?>>
                            <?= sanitize($t['type_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">From Date</label>
                    <input type="date" name="from_date" class="form-control form-control-sm" value="<?= sanitize($fromDate) ?>">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">To Date</label>
                    <input type="date" name="to_date" class="form-control form-control-sm" value="<?= sanitize($toDate) ?>">
                </div>
                <div class="col-12 col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary-custom w-100" title="Apply Filter">
                        <i class="bi bi-search"></i>
                    </button>
                    <a href="incoming.php" class="btn btn-sm btn-outline-secondary" title="Reset Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Incoming Documents Table -->
    <form method="POST" action="incoming.php" id="bulkDeleteForm">
        <input type="hidden" name="form_action" value="bulk_delete">
        <div class="card card-custom">
            <div class="card-header card-header-custom d-flex justify-content-between align-items-center">
                <h5 class="card-title fw-bold mb-0 text-dark">
                    <i class="bi bi-list-ul me-2 text-primary"></i> Incoming Entries Log
                </h5>
                <div class="d-flex align-items-center">
                    <?php if (hasRole('admin')): ?>
                    <button type="button" id="btnDeleteSelected" class="btn btn-sm btn-danger shadow-sm d-none me-2">
                        <i class="bi bi-trash3-fill me-1"></i> Delete Selected (<span id="selectedCount">0</span>)
                    </button>
                    <?php endif; ?>
                    <span class="badge bg-light text-muted border"><?= count($documents) ?> entries found</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom align-middle">
                        <thead>
                            <tr>
                                <?php if (hasRole('admin')): ?>
                                <th style="width: 38px;" class="text-center">
                                    <input type="checkbox" id="selectAllDocs" class="form-check-input" title="Select All">
                                </th>
                                <?php endif; ?>
                                <th>Ref #</th>
                                <th>Document Title</th>
                                <th>Category</th>
                                <th>Type</th>
                                <th>Origin / Source</th>
                                <th>Document Date</th>
                                <th>Time Log</th>
                                <th>Encoded By</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($documents)): ?>
                            <tr>
                                <td colspan="<?= hasRole('admin') ? '10' : '9' ?>" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                                    No incoming document logs matched your criteria.
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($documents as $doc): ?>
                                <tr>
                                    <?php if (hasRole('admin')): ?>
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input doc-checkbox" name="selected_ids[]" value="<?= $doc['id'] ?>" data-ref="<?= sanitize($doc['reference_number']) ?>">
                                    </td>
                                    <?php endif; ?>
                                    <td class="fw-bold text-primary font-monospace" style="font-size:0.85rem;"><?= sanitize($doc['reference_number'] ?: '—') ?></td>
                                    <td class="fw-semibold text-dark" style="max-width:240px;"><?= sanitize($doc['document_title']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= sanitize($doc['category_name']) ?></span></td>
                                    <td><span class="small text-secondary"><?= sanitize($doc['type_name']) ?></span></td>
                                    <td><?= sanitize($doc['origin_source'] ?: '—') ?></td>
                                    <td class="small text-nowrap"><?= date('M d, Y', strtotime($doc['document_date'])) ?></td>
                                    <td class="small text-nowrap text-muted"><i class="bi bi-clock me-1 text-primary opacity-75"></i><?= !empty($doc['time_log']) ? date('h:i A', strtotime($doc['time_log'])) : (!empty($doc['created_at']) ? date('h:i A', strtotime($doc['created_at'])) : '—') ?></td>
                                    <td class="small text-muted"><?= sanitize($doc['encoder_name'] ?: 'System') ?></td>
                                    <td class="text-end text-nowrap">
                                        <a href="incoming.php?view=<?= $doc['id'] ?>" class="btn btn-sm btn-outline-primary-custom py-1 px-2" title="View Details">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                        <?php if (hasRole(['admin', 'encoder'])): ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 btn-edit-doc" 
                                                data-doc='<?= json_encode($doc, JSON_HEX_APOS | JSON_HEX_QUOT) ?>' title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if (hasRole('admin')): ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2 btn-delete-doc" title="Delete"
                                                data-doc-id="<?= $doc['id'] ?>"
                                                data-doc-ref="<?= sanitize($doc['reference_number']) ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Modal: Add / Edit Incoming Document -->
<div class="modal fade" id="addDocumentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form method="POST" action="incoming.php" enctype="multipart/form-data" class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #040484 0%, #020257 100%); border-bottom: 3px solid var(--accent-color);">
                <h5 class="modal-title fw-bold" id="modalTitle">
                    <i class="bi bi-box-arrow-in-down me-2"></i> Log New Incoming Document
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="form_action" value="save">
                <input type="hidden" name="document_id" id="document_id" value="0">

                <div class="row g-3">
                    <!-- Reference Number -->
                    <div class="col-md-6">
                        <label for="reference_number" class="form-label">Reference Number</label>
                        <input type="text" class="form-control" id="reference_number" name="reference_number" placeholder="e.g. REF-2026-001 (optional)">
                        <div id="reference_feedback" class="mt-1"></div>
                    </div>

                    <!-- Document Date -->
                    <div class="col-md-3">
                        <label for="document_date" class="form-label">Document Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="document_date" name="document_date" required value="<?= date('Y-m-d') ?>">
                    </div>

                    <!-- Time Log -->
                    <div class="col-md-3">
                        <label for="time_log" class="form-label">Time Log</label>
                        <input type="time" class="form-control" id="time_log" name="time_log" value="<?= date('H:i') ?>">
                    </div>

                    <!-- Document Title -->
                    <div class="col-12">
                        <label for="document_title" class="form-label">Document Title / Subject <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="document_title" name="document_title" required placeholder="Enter descriptive document subject or title">
                    </div>

                    <!-- Category Dropdown -->
                    <div class="col-md-6">
                        <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <option value="">-- Select Category --</option>
                            <?php foreach ($allCategories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= sanitize($cat['category_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Type Dropdown (Filtered by Category via JS) -->
                    <div class="col-md-6">
                        <label for="document_type_id" class="form-label">Document Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="document_type_id" name="document_type_id" required>
                            <option value="">-- Select Category First --</option>
                        </select>
                    </div>

                    <!-- Origin / Source -->
                    <div class="col-12">
                        <label for="origin_source" class="form-label">Origin / Source Office</label>
                        <input type="text" class="form-control" id="origin_source" name="origin_source" placeholder="e.g. Regional Office / Division of Finance">
                    </div>

                    <!-- Attachment Checklist Container (Populated by JS) -->
                    <div class="col-12">
                        <label class="form-label d-block">Attachment Items & Files</label>
                        <div class="attachment-box" id="attachment_checklist_container">
                            <div class="text-muted small">Select a category and document type above to render the attachment checklist.</div>
                        </div>
                    </div>

                    <!-- Remarks -->
                    <div class="col-12">
                        <label for="remarks" class="form-label">Remarks / Additional Notes</label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="2" placeholder="Optional comments or notes..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary-custom px-4"><i class="bi bi-save me-1"></i> Save Incoming Entry</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: View Document Details -->
<?php if ($viewDoc): ?>
<div class="modal fade show d-block" id="viewDocumentModal" tabindex="-1" style="background: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #040484 0%, #020257 100%);">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-file-earmark-text me-2 text-warning"></i> Incoming Entry Details
                </h5>
                <a href="incoming.php" class="btn-close btn-close-white"></a>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="text-muted small">Reference Number</div>
                        <div class="fs-5 fw-bold text-primary"><?= sanitize($viewDoc['reference_number'] ?: 'N/A') ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Document Date</div>
                        <div class="fw-semibold text-dark"><?= date('F d, Y', strtotime($viewDoc['document_date'])) ?></div>
                    </div>
                    <div class="col-md-3 text-md-end">
                        <div class="text-muted small">Time Log</div>
                        <div class="fw-semibold text-dark"><?= !empty($viewDoc['time_log']) ? date('h:i A', strtotime($viewDoc['time_log'])) : 'N/A' ?></div>
                    </div>
                    <div class="col-12">
                        <div class="text-muted small">Document Title / Subject</div>
                        <div class="fs-6 fw-bold text-dark"><?= sanitize($viewDoc['document_title']) ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Category</div>
                        <span class="badge bg-light text-dark border px-2 py-1"><?= sanitize($viewDoc['category_name']) ?></span>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Document Type</div>
                        <div class="fw-semibold text-dark"><?= sanitize($viewDoc['type_name']) ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Origin / Source</div>
                        <div class="fw-semibold text-dark"><?= sanitize($viewDoc['origin_source'] ?: 'N/A') ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Encoded By</div>
                        <div class="fw-semibold text-dark"><?= sanitize($viewDoc['encoder_name'] ?: 'System') ?> (<?= date('M d, Y h:i A', strtotime($viewDoc['created_at'])) ?>)</div>
                    </div>
                    <?php if (!empty($viewDoc['remarks'])): ?>
                    <div class="col-12">
                        <div class="text-muted small">Remarks</div>
                        <div class="p-2 bg-light rounded border text-dark small"><?= nl2br(sanitize($viewDoc['remarks'])) ?></div>
                    </div>
                    <?php endif; ?>
                </div>

                <hr>

                <h6 class="fw-bold text-primary mb-3"><i class="bi bi-paperclip me-1"></i> File Attachments (<?= count($viewAttachments) ?>)</h6>
                <?php if (empty($viewAttachments)): ?>
                    <div class="alert alert-light text-muted small border">No files attached to this document log.</div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($viewAttachments as $att): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-2 attachment-row-<?= $att['id'] ?>">
                            <div>
                                <i class="bi bi-file-earmark-arrow-down fs-5 text-primary me-2"></i>
                                <span class="fw-semibold text-dark small">
                                    <?= sanitize($att['item_name'] ?: $att['custom_item_name'] ?: 'Attached File') ?>
                                </span>
                            </div>
                            <div class="d-flex gap-1 align-items-center">
                                <a href="<?= sanitize($att['file_path']) ?>" target="_blank" download class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-download me-1"></i> Download
                                </a>
                                <?php if (hasRole('admin')): ?>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-attachment" data-id="<?= $att['id'] ?>" data-name="<?= sanitize($att['item_name'] ?: $att['custom_item_name'] ?: 'Attached File') ?>">
                                    <i class="bi bi-trash me-1"></i> Delete
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer bg-light">
                <a href="incoming.php" class="btn btn-secondary">Close</a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Edit Document Button Handler
    const editBtns = document.querySelectorAll('.btn-edit-doc');
    editBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const data = JSON.parse(this.getAttribute('data-doc'));
            document.getElementById('modalTitle').innerHTML = '<i class="bi bi-pencil me-2"></i> Edit Incoming Entry';
            document.getElementById('document_id').value = data.id;
            document.getElementById('reference_number').value = data.reference_number || '';
            document.getElementById('document_date').value = data.document_date;
            document.getElementById('time_log').value = data.time_log || '';
            document.getElementById('document_title').value = data.document_title;
            document.getElementById('origin_source').value = data.origin_source || '';
            document.getElementById('remarks').value = data.remarks || '';

            // Trigger Category Change
            const catSelect = document.getElementById('category_id');
            catSelect.value = data.category_id;
            
            // Fetch types and select current type
            fetch(`api.php?action=get_types&category_id=${data.category_id}`)
                .then(res => res.json())
                .then(types => {
                    const typeSelect = document.getElementById('document_type_id');
                    typeSelect.innerHTML = '<option value="">-- Select Document Type --</option>';
                    types.forEach(t => {
                        const opt = document.createElement('option');
                        opt.value = t.id;
                        opt.textContent = t.type_name;
                        if (t.id == data.document_type_id) opt.selected = true;
                        typeSelect.appendChild(opt);
                    });
                });

            const modal = new bootstrap.Modal(document.getElementById('addDocumentModal'));
            modal.show();
        });
    });

    <?php if (isset($_GET['action']) && $_GET['action'] === 'add'): ?>
    const addModal = new bootstrap.Modal(document.getElementById('addDocumentModal'));
    addModal.show();
    <?php endif; ?>
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
