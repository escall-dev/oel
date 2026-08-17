<?php
require_once __DIR__ . '/config/db.php';
requireLogin();

// Export Action Handler (CSV Download)
if (isset($_GET['download']) && $_GET['download'] == '1') {
    $direction = trim($_GET['direction'] ?? 'all');
    $filterCategory = intval($_GET['category_id'] ?? 0);
    $filterType = intval($_GET['type_id'] ?? 0);
    $fromDate = trim($_GET['from_date'] ?? '');
    $toDate = trim($_GET['to_date'] ?? '');

    $whereClause = ["1=1"];
    $params = [];

    if ($direction === 'Incoming' || $direction === 'Outgoing') {
        $whereClause[] = "d.direction = :dir";
        $params[':dir'] = $direction;
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

    $stmtExp = $pdo->prepare("
        SELECT 
            d.reference_number,
            d.direction,
            d.document_title,
            c.category_name,
            dt.type_name,
            d.origin_source,
            d.recipient_office,
            d.document_date,
            d.time_log,
            d.remarks,
            u.full_name AS encoder_name,
            d.created_at
        FROM documents d
        JOIN categories c ON d.category_id = c.id
        LEFT JOIN document_types dt ON d.document_type_id = dt.id
        LEFT JOIN users u ON d.encoded_by = u.id
        WHERE $whereSql
        ORDER BY d.id DESC
    ");
    $stmtExp->execute($params);
    $exportData = $stmtExp->fetchAll();

    // Generate CSV Output
    $filename = "qpteo_logbook_export_" . date('Ymd_His') . ".csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // Add UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Headers
    fputcsv($output, [
        'Reference Number',
        'Direction',
        'Document Title',
        'Category',
        'Document Type',
        'Origin / Source',
        'Recipient Office',
        'Document Date',
        'Time Log',
        'Remarks',
        'Encoded By',
        'Created Timestamp'
    ]);

    foreach ($exportData as $row) {
        fputcsv($output, [
            $row['reference_number'],
            $row['direction'],
            $row['document_title'],
            $row['category_name'],
            $row['type_name'],
            $row['origin_source'] ?: '',
            $row['recipient_office'] ?: '',
            $row['document_date'],
            !empty($row['time_log']) ? date('h:i A', strtotime($row['time_log'])) : '',
            $row['remarks'] ?: '',
            $row['encoder_name'] ?: 'System',
            $row['created_at']
        ]);
    }

    fclose($output);
    exit();
}

// Page Render Filter Preview
$direction = trim($_GET['direction'] ?? 'all');
$filterCategory = intval($_GET['category_id'] ?? 0);
$filterType = intval($_GET['type_id'] ?? 0);
$fromDate = trim($_GET['from_date'] ?? '');
$toDate = trim($_GET['to_date'] ?? '');

$whereClause = ["1=1"];
$params = [];

if ($direction === 'Incoming' || $direction === 'Outgoing') {
    $whereClause[] = "d.direction = :dir";
    $params[':dir'] = $direction;
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
    LEFT JOIN document_types dt ON d.document_type_id = dt.id
    LEFT JOIN users u ON d.encoded_by = u.id
    WHERE $whereSql
    ORDER BY d.id DESC
    LIMIT 100
");
$stmtDocs->execute($params);
$previewDocuments = $stmtDocs->fetchAll();

$allCategories = $pdo->query("SELECT * FROM categories ORDER BY category_name ASC")->fetchAll();
$allTypes = [];
if ($filterCategory > 0) {
    $stmtT = $pdo->prepare("SELECT * FROM document_types WHERE category_id = :c ORDER BY type_name ASC");
    $stmtT->execute([':c' => $filterCategory]);
    $allTypes = $stmtT->fetchAll();
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="container-fluid px-4 py-2">
    <!-- Header Title -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-2 border-bottom">
        <div>
            <h3 class="fw-bold mb-1" style="color: var(--primary-color);">
                <i class="bi bi-file-earmark-excel me-2 text-success"></i> Export Document Logbook Data
            </h3>
            <p class="text-muted mb-0 small">Filter and generate Microsoft Excel / CSV spreadsheet reports for office archiving.</p>
        </div>
    </div>

    <!-- Export Filter Card -->
    <div class="card card-custom mb-4">
        <div class="card-header card-header-custom">
            <h5 class="card-title fw-bold mb-0 text-dark">
                <i class="bi bi-funnel me-2 text-primary"></i> Report Filter Options
            </h5>
        </div>
        <div class="card-body p-4">
            <form method="GET" action="export.php" id="exportForm" class="row g-3 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold">Direction / Type</label>
                    <select name="direction" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?= ($direction === 'all') ? 'selected' : '' ?>>All Documents (Incoming & Outgoing)</option>
                        <option value="Incoming" <?= ($direction === 'Incoming') ? 'selected' : '' ?>>Incoming Only</option>
                        <option value="Outgoing" <?= ($direction === 'Outgoing') ? 'selected' : '' ?>>Outgoing Only</option>
                    </select>
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold">Category</label>
                    <select name="category_id" class="form-select" onchange="this.form.submit()">
                        <option value="0">All Categories</option>
                        <?php foreach ($allCategories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($filterCategory === (int)$cat['id']) ? 'selected' : '' ?>>
                            <?= sanitize($cat['category_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label fw-semibold">Document Type</label>
                    <select name="type_id" class="form-select">
                        <option value="0">All Document Types</option>
                        <?php foreach ($allTypes as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= ($filterType === (int)$t['id']) ? 'selected' : '' ?>>
                            <?= sanitize($t['type_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold">From Date</label>
                    <input type="date" name="from_date" class="form-control" value="<?= sanitize($fromDate) ?>">
                </div>

                <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold">To Date</label>
                    <input type="date" name="to_date" class="form-control" value="<?= sanitize($toDate) ?>">
                </div>

                <div class="col-12 col-md-9 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary-custom px-3">
                        <i class="bi bi-eye me-1"></i> Preview Records
                    </button>

                    <?php
                    $queryString = http_build_query([
                        'download' => '1',
                        'direction' => $direction,
                        'category_id' => $filterCategory,
                        'type_id' => $filterType,
                        'from_date' => $fromDate,
                        'to_date' => $toDate
                    ]);
                    ?>
                    <a href="export.php?<?= $queryString ?>" class="btn btn-accent-custom px-4 fw-bold shadow-sm">
                        <i class="bi bi-file-earmark-arrow-down me-1"></i> Download CSV / Excel Report
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Records Preview Table -->
    <div class="card card-custom">
        <div class="card-header card-header-custom d-flex justify-content-between align-items-center">
            <h5 class="card-title fw-bold mb-0 text-dark">
                <i class="bi bi-table me-2 text-primary"></i> Matching Log Entries Preview
            </h5>
            <span class="badge bg-light text-muted border"><?= count($previewDocuments) ?> records shown</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr>
                            <th>Ref #</th>
                            <th>Direction</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th>Origin / Recipient</th>
                            <th>Document Date</th>
                            <th>Time Log</th>
                            <th>Encoded By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($previewDocuments)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                                No documents match the selected export filters.
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($previewDocuments as $doc): ?>
                            <tr>
                                <td class="fw-bold text-primary font-monospace" style="font-size:0.85rem;"><?= sanitize($doc['reference_number']) ?></td>
                                <td>
                                    <?php if ($doc['direction'] === 'Incoming'): ?>
                                        <span class="badge badge-incoming"><i class="bi bi-arrow-down-left me-1"></i> Incoming</span>
                                    <?php else: ?>
                                        <span class="badge badge-outgoing"><i class="bi bi-arrow-up-right me-1"></i> Outgoing</span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-semibold text-dark"><?= sanitize($doc['document_title']) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= sanitize($doc['category_name']) ?></span></td>
                                <td class="small text-secondary"><?= sanitize($doc['type_name']) ?></td>
                                <td class="small"><?= sanitize(($doc['direction'] === 'Incoming') ? $doc['origin_source'] : $doc['recipient_office']) ?></td>
                                <td class="small text-nowrap"><?= date('M d, Y', strtotime($doc['document_date'])) ?></td>
                                <td class="small text-nowrap text-muted"><i class="bi bi-clock me-1 opacity-75"></i><?= !empty($doc['time_log']) ? date('h:i A', strtotime($doc['time_log'])) : (!empty($doc['created_at']) ? date('h:i A', strtotime($doc['created_at'])) : '—') ?></td>
                                <td class="small text-muted"><?= sanitize($doc['encoder_name'] ?: 'System') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
