<?php
require_once __DIR__ . '/config/db.php';
requireLogin();

$user = currentUser();

// Metrics Queries
$totalIncoming = $pdo->query("SELECT COUNT(*) FROM documents WHERE direction = 'Incoming'")->fetchColumn();
$totalOutgoing = $pdo->query("SELECT COUNT(*) FROM documents WHERE direction = 'Outgoing'")->fetchColumn();
$totalDocs = $pdo->query("SELECT COUNT(*) FROM documents")->fetchColumn();
$totalCategories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();

// Category Summary Stats
$catStmt = $pdo->query("
    SELECT c.category_name, COUNT(d.id) AS doc_count 
    FROM categories c 
    LEFT JOIN documents d ON c.id = d.category_id 
    GROUP BY c.id 
    ORDER BY doc_count DESC, c.category_name ASC
");
$categoryStats = $catStmt->fetchAll();

// Recent Entries (Latest 10)
$recentStmt = $pdo->query("
    SELECT d.*, c.category_name, dt.type_name, u.full_name AS encoder_name
    FROM documents d
    JOIN categories c ON d.category_id = c.id
    JOIN document_types dt ON d.document_type_id = dt.id
    LEFT JOIN users u ON d.encoded_by = u.id
    ORDER BY d.id DESC
    LIMIT 10
");
$recentEntries = $recentStmt->fetchAll();

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="container-fluid px-4 py-2">
    <!-- Page Title & Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-2 border-bottom">
        <div>
            <h3 class="fw-bold mb-1" style="color: var(--primary-color);">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard Overview
            </h3>
            <p class="text-muted mb-0 small">QPTEO Electronic Logbook System &bull; System Summary & Recent Records</p>
        </div>
        <?php if (!hasRole('viewer')): ?>
        <div class="mt-3 mt-md-0 d-flex gap-2">
            <a href="incoming.php?action=add" class="btn btn-primary-custom shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Log Incoming
            </a>
            <a href="outgoing.php?action=add" class="btn btn-accent-custom shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Log Outgoing
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Stat Cards Row -->
    <div class="row g-3 mb-4">
        <!-- Incoming Stat -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card stat-incoming">
                <div class="text-uppercase small fw-bold text-white-50">Incoming Documents</div>
                <div class="stat-number my-1"><?= number_format($totalIncoming) ?></div>
                <div class="small text-white-50"><i class="bi bi-arrow-down-left-circle me-1"></i> Total files received</div>
                <i class="bi bi-box-arrow-in-down stat-icon"></i>
            </div>
        </div>

        <!-- Outgoing Stat -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card stat-outgoing">
                <div class="text-uppercase small fw-bold text-dark-50">Outgoing Documents</div>
                <div class="stat-number my-1"><?= number_format($totalOutgoing) ?></div>
                <div class="small text-dark-50"><i class="bi bi-arrow-up-right-circle me-1"></i> Total files dispatched</div>
                <i class="bi bi-box-arrow-up stat-icon"></i>
            </div>
        </div>

        <!-- Total Records -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card stat-total">
                <div class="text-uppercase small fw-bold text-muted">Total Repository Log</div>
                <div class="stat-number my-1" style="color: var(--primary-color);"><?= number_format($totalDocs) ?></div>
                <div class="small text-muted"><i class="bi bi-files me-1"></i> Combined archive count</div>
                <i class="bi bi-folder2-open stat-icon text-primary"></i>
            </div>
        </div>

        <!-- Categories Stat -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card stat-total" style="border-left-color: var(--accent-color);">
                <div class="text-uppercase small fw-bold text-muted">Office Categories</div>
                <div class="stat-number my-1" style="color: #b45309;"><?= number_format($totalCategories) ?></div>
                <div class="small text-muted"><i class="bi bi-tags me-1"></i> Configured category types</div>
                <i class="bi bi-bookmark-star stat-icon text-warning"></i>
            </div>
        </div>
    </div>

    <!-- Main Section: Recent Entries & Category Breakdown -->
    <div class="row g-4 mb-4">
        <!-- Recent Entries List -->
        <div class="col-12 col-lg-8">
            <div class="card card-custom h-100">
                <div class="card-header card-header-custom d-flex justify-content-between align-items-center">
                    <h5 class="card-title fw-bold mb-0 text-dark">
                        <i class="bi bi-clock-history me-2 text-primary"></i> Recent Log Entries
                    </h5>
                    <span class="badge bg-light text-muted border">Latest 10</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-custom align-middle">
                            <thead>
                                <tr>
                                    <th>Ref #</th>
                                    <th>Direction</th>
                                    <th>Title</th>
                                    <th>Category / Type</th>
                                    <th>Date</th>
                                    <th>Time Log</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentEntries)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-2 d-block mb-1 opacity-50"></i>
                                        No log entries found. Start by adding an Incoming or Outgoing document log.
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($recentEntries as $entry): ?>
                                    <tr>
                                        <td class="fw-bold text-dark" style="font-size:0.85rem;"><?= sanitize($entry['reference_number']) ?></td>
                                        <td>
                                            <?php if ($entry['direction'] === 'Incoming'): ?>
                                                <span class="badge badge-incoming"><i class="bi bi-arrow-down-left me-1"></i> Incoming</span>
                                            <?php else: ?>
                                                <span class="badge badge-outgoing"><i class="bi bi-arrow-up-right me-1"></i> Outgoing</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="max-width:200px;" class="text-truncate" title="<?= sanitize($entry['document_title']) ?>">
                                            <?= sanitize($entry['document_title']) ?>
                                        </td>
                                        <td>
                                            <span class="d-block fw-semibold text-dark small"><?= sanitize($entry['category_name']) ?></span>
                                            <span class="d-block text-muted small" style="font-size:0.75rem;"><?= sanitize($entry['type_name']) ?></span>
                                        </td>
                                        <td class="text-nowrap small text-muted"><?= date('M d, Y', strtotime($entry['document_date'])) ?></td>
                                        <td class="text-nowrap small text-muted"><i class="bi bi-clock me-1 opacity-75"></i><?= !empty($entry['time_log']) ? date('h:i A', strtotime($entry['time_log'])) : (!empty($entry['created_at']) ? date('h:i A', strtotime($entry['created_at'])) : '—') ?></td>
                                        <td class="text-end">
                                            <a href="<?= ($entry['direction'] === 'Incoming') ? 'incoming.php' : 'outgoing.php' ?>?view=<?= $entry['id'] ?>" class="btn btn-sm btn-outline-primary-custom py-1 px-2">
                                                <i class="bi bi-eye me-1"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Category Summary Sidebar -->
        <div class="col-12 col-lg-4">
            <div class="card card-custom h-100">
                <div class="card-header card-header-custom">
                    <h5 class="card-title fw-bold mb-0 text-dark">
                        <i class="bi bi-pie-chart me-2 text-warning"></i> Documents by Category
                    </h5>
                </div>
                <div class="card-body p-3">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($categoryStats as $cat): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-1">
                            <span class="fw-semibold text-dark small"><?= sanitize($cat['category_name']) ?></span>
                            <span class="badge rounded-pill bg-light text-dark border font-monospace fw-bold px-3 py-1">
                                <?= number_format($cat['doc_count']) ?>
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
