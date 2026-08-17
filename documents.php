<?php
require_once __DIR__ . '/config/db.php';
requireLogin();

$user = currentUser();

// Handle Delete Action if Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hasRole('admin')) {
        setFlash('danger', 'Only administrators can delete log records.');
        header("Location: documents.php");
        exit();
    }

    $formAction = $_POST['form_action'] ?? '';
    if ($formAction === 'delete') {
        $docId = intval($_POST['document_id'] ?? 0);
        if ($docId > 0) {
            // Delete physical attachment files
            $stmtAtt = $pdo->prepare("SELECT id, file_path FROM document_attachments WHERE document_id = :id");
            $stmtAtt->execute([':id' => $docId]);
            $files = $stmtAtt->fetchAll();
            foreach ($files as $f) {
                deleteAttachmentFile($pdo, $f['file_path'], $f['id']);
            }
            // Delete document record
            $stmtDel = $pdo->prepare("DELETE FROM documents WHERE id = :id");
            $stmtDel->execute([':id' => $docId]);
            setFlash('success', 'Document record deleted successfully.');
        }
        header("Location: documents.php");
        exit();
    }

    if ($formAction === 'bulk_delete') {
        $selectedIds = $_POST['selected_ids'] ?? [];
        if (is_array($selectedIds) && !empty($selectedIds)) {
            $ids = array_map('intval', array_filter($selectedIds, 'is_numeric'));
            if (!empty($ids)) {
                $inQuery = implode(',', array_fill(0, count($ids), '?'));
                // Retrieve attachment file paths to delete physical files
                $stmtAtt = $pdo->prepare("SELECT id, file_path FROM document_attachments WHERE document_id IN ($inQuery)");
                $stmtAtt->execute($ids);
                $files = $stmtAtt->fetchAll();
                foreach ($files as $f) {
                    deleteAttachmentFile($pdo, $f['file_path'], $f['id']);
                }
                // Delete document records
                $stmtDel = $pdo->prepare("DELETE FROM documents WHERE id IN ($inQuery)");
                $stmtDel->execute($ids);
                setFlash('success', count($ids) . ' document record(s) deleted successfully.');
            }
        }
        header("Location: documents.php");
        exit();
    }
}

// Search & Filter Parameters
$search = trim($_GET['search'] ?? '');
$filterMonth = trim($_GET['month'] ?? ''); // YYYY-MM
$filterCategory = intval($_GET['category_id'] ?? 0);
$filterType = intval($_GET['type_id'] ?? 0);
$originSource = trim($_GET['origin_source'] ?? '');
$direction = trim($_GET['direction'] ?? 'all');

// Build SQL Query
$whereClause = ["1=1"];
$params = [];

if (!empty($search)) {
    $whereClause[] = "(d.reference_number LIKE :search1 OR d.document_title LIKE :search2 OR d.origin_source LIKE :search3 OR d.recipient_office LIKE :search4)";
    $params[':search1'] = "%$search%";
    $params[':search2'] = "%$search%";
    $params[':search3'] = "%$search%";
    $params[':search4'] = "%$search%";
}

if (!empty($filterMonth)) {
    // filterMonth is YYYY-MM
    $whereClause[] = "DATE_FORMAT(d.document_date, '%Y-%m') = :month";
    $params[':month'] = $filterMonth;
}

if ($filterCategory > 0) {
    $whereClause[] = "d.category_id = :cat_id";
    $params[':cat_id'] = $filterCategory;
}

if ($filterType > 0) {
    $whereClause[] = "d.document_type_id = :type_id";
    $params[':type_id'] = $filterType;
}

if (!empty($originSource)) {
    $whereClause[] = "(d.origin_source LIKE :origin1 OR d.recipient_office LIKE :origin2)";
    $params[':origin1'] = "%$originSource%";
    $params[':origin2'] = "%$originSource%";
}

if ($direction === 'Incoming' || $direction === 'Outgoing') {
    $whereClause[] = "d.direction = :direction";
    $params[':direction'] = $direction;
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

// Fetch categories & types for filter options
$allCategories = $pdo->query("SELECT * FROM categories ORDER BY category_name ASC")->fetchAll();
$allTypes = [];
if ($filterCategory > 0) {
    $stmtT = $pdo->prepare("SELECT * FROM document_types WHERE category_id = :c ORDER BY type_name ASC");
    $stmtT->execute([':c' => $filterCategory]);
    $allTypes = $stmtT->fetchAll();
} else {
    $allTypes = $pdo->query("SELECT * FROM document_types ORDER BY type_name ASC")->fetchAll();
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
        WHERE d.id = :id
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
                <i class="bi bi-folder2-open me-2 text-primary"></i> All Documents Repository
            </h3>
            <p class="text-muted mb-0 small">Unified master list of all incoming and outgoing office files, letters, and documents.</p>
        </div>
        <?php if (!hasRole('viewer')): ?>
        <div class="mt-3 mt-md-0 d-flex gap-2">
            <a href="incoming.php?action=add" class="btn btn-primary-custom shadow-sm btn-sm">
                <i class="bi bi-plus-circle me-1"></i> Add Incoming
            </a>
            <a href="outgoing.php?action=add" class="btn btn-accent-custom shadow-sm btn-sm">
                <i class="bi bi-plus-circle me-1"></i> Add Outgoing
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Filter Bar Card -->
    <div class="card card-custom mb-4">
        <div class="card-header card-header-custom py-2 px-3">
            <h6 class="card-title fw-bold mb-0 text-dark" style="font-size:0.9rem;">
                <i class="bi bi-funnel me-1 text-primary"></i> Filter & Search Documents
            </h6>
        </div>
        <div class="card-body p-3">
            <form method="GET" action="documents.php" class="row g-2 align-items-end">
                <!-- Search Keyword -->
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold mb-1">Search Keyword</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Ref #, Title, Source..." value="<?= sanitize($search) ?>">
                </div>

                <!-- Month Picker -->
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Filter Month</label>
                    <input type="month" name="month" class="form-control form-control-sm" value="<?= sanitize($filterMonth) ?>">
                </div>

                <!-- Category Filter -->
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Document Category</label>
                    <select name="category_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="0">All Categories</option>
                        <?php foreach ($allCategories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($filterCategory === (int)$cat['id']) ? 'selected' : '' ?>>
                            <?= sanitize($cat['category_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Document Type Filter -->
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Document Type</label>
                    <select name="type_id" class="form-select form-select-sm">
                        <option value="0">All Types</option>
                        <?php foreach ($allTypes as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= ($filterType === (int)$t['id']) ? 'selected' : '' ?>>
                            <?= sanitize($t['type_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Origin Source / Recipient -->
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Origin / Recipient</label>
                    <input type="text" name="origin_source" class="form-control form-control-sm" placeholder="Office name..." value="<?= sanitize($originSource) ?>">
                </div>

                <!-- Direction Filter -->
                <div class="col-6 col-md-1">
                    <label class="form-label small fw-semibold mb-1">Direction</label>
                    <select name="direction" class="form-select form-select-sm">
                        <option value="all" <?= ($direction === 'all') ? 'selected' : '' ?>>All</option>
                        <option value="Incoming" <?= ($direction === 'Incoming') ? 'selected' : '' ?>>Incoming</option>
                        <option value="Outgoing" <?= ($direction === 'Outgoing') ? 'selected' : '' ?>>Outgoing</option>
                    </select>
                </div>

                <!-- Filter Actions -->
                <div class="col-12 text-end mt-2">
                    <button type="submit" class="btn btn-sm btn-primary-custom px-3 me-1">
                        <i class="bi bi-search me-1"></i> Apply Filters
                    </button>
                    <a href="documents.php" class="btn btn-sm btn-outline-secondary px-3">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Master Documents Table -->
    <form method="POST" action="documents.php" id="bulkDeleteForm">
        <input type="hidden" name="form_action" value="bulk_delete">
        <div class="card card-custom">
            <div class="card-header card-header-custom d-flex justify-content-between align-items-center">
                <h5 class="card-title fw-bold mb-0 text-dark">
                    <i class="bi bi-collection me-2 text-primary"></i> Master Documents Log
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
                                <th>Direction</th>
                                <th>Document Title</th>
                                <th>Category</th>
                                <th>Type</th>
                                <th>Origin / Recipient Office</th>
                                <th>Document Date</th>
                                <th>Time Log</th>
                                <th>Encoded By</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($documents)): ?>
                            <tr>
                                <td colspan="<?= hasRole('admin') ? '11' : '10' ?>" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                                    No documents matched your selected filter criteria.
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
                                    <td>
                                        <?php if ($doc['direction'] === 'Incoming'): ?>
                                            <span class="badge badge-incoming"><i class="bi bi-arrow-down-left me-1"></i> Incoming</span>
                                        <?php else: ?>
                                            <span class="badge badge-outgoing"><i class="bi bi-arrow-up-right me-1"></i> Outgoing</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-semibold text-dark" style="max-width:220px;"><?= sanitize($doc['document_title']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= sanitize($doc['category_name']) ?></span></td>
                                    <td><span class="small text-secondary"><?= sanitize($doc['type_name']) ?></span></td>
                                    <td class="small"><?= sanitize(($doc['direction'] === 'Incoming') ? $doc['origin_source'] : $doc['recipient_office']) ?: '—' ?></td>
                                    <td class="small text-nowrap"><?= date('M d, Y', strtotime($doc['document_date'])) ?></td>
                                    <td class="small text-nowrap text-muted"><i class="bi bi-clock me-1 text-secondary opacity-75"></i><?= !empty($doc['time_log']) ? date('h:i A', strtotime($doc['time_log'])) : (!empty($doc['created_at']) ? date('h:i A', strtotime($doc['created_at'])) : '—') ?></td>
                                    <td class="small text-muted"><?= sanitize($doc['encoder_name'] ?: 'System') ?></td>
                                    <td class="text-end text-nowrap">
                                        <a href="documents.php?view=<?= $doc['id'] ?>" class="btn btn-sm btn-outline-primary-custom py-1 px-2" title="View Details">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                        <?php if (hasRole(['admin', 'encoder'])): ?>
                                        <a href="<?= ($doc['direction'] === 'Incoming') ? 'incoming.php' : 'outgoing.php' ?>?view=<?= $doc['id'] ?>" class="btn btn-sm btn-outline-secondary py-1 px-2" title="Edit via Log Page">
                                            <i class="bi bi-pencil"></i>
                                        </a>
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

<!-- Modal: View Document Details -->
<?php if ($viewDoc): ?>
<div class="modal fade show d-block" id="viewDocumentModal" tabindex="-1" style="background: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #040484 0%, #020257 100%); border-bottom: 3px solid var(--accent-color);">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-file-earmark-text me-2 text-warning"></i> Document Details
                </h5>
                <a href="documents.php" class="btn-close btn-close-white"></a>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="text-muted small">Reference Number</div>
                        <div class="fs-5 fw-bold text-primary"><?= sanitize($viewDoc['reference_number'] ?: 'N/A') ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">Direction</div>
                        <?php if ($viewDoc['direction'] === 'Incoming'): ?>
                            <span class="badge badge-incoming fs-6"><i class="bi bi-arrow-down-left me-1"></i> Incoming</span>
                        <?php else: ?>
                            <span class="badge badge-outgoing fs-6"><i class="bi bi-arrow-up-right me-1"></i> Outgoing</span>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-2">
                        <div class="text-muted small">Document Date</div>
                        <div class="fw-semibold text-dark"><?= date('M d, Y', strtotime($viewDoc['document_date'])) ?></div>
                    </div>
                    <div class="col-md-2 text-md-end">
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
                        <div class="text-muted small"><?= ($viewDoc['direction'] === 'Incoming') ? 'Origin / Source' : 'Recipient Office' ?></div>
                        <div class="fw-semibold text-dark"><?= sanitize(($viewDoc['direction'] === 'Incoming') ? $viewDoc['origin_source'] : $viewDoc['recipient_office']) ?: 'N/A' ?></div>
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
                <a href="documents.php" class="btn btn-secondary">Close</a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
