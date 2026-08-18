<?php
require_once __DIR__ . '/../config/db.php';
$user = currentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top mb-4">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="branding/TEC Seal no bg.png" alt="TEC Logo">
            <div>
                <span class="d-block fw-bold fs-6 leading-none">QPTEO</span>
                <span class="d-block text-warning small fw-normal" style="font-size:0.72rem; margin-top:-2px;">Electronic Logbook System</span>
            </div>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
                <li class="nav-item">
                    <a class="nav-link <?= ($currentPage === 'index.php') ? 'active' : '' ?>" href="index.php">
                        <i class="bi bi-speedometer2 me-1"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($currentPage === 'document_log.php') ? 'active' : '' ?>" href="document_log.php">
                        <i class="bi bi-box-arrow-in-down me-1"></i> Document Log
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($currentPage === 'export.php') ? 'active' : '' ?>" href="export.php">
                        <i class="bi bi-file-earmark-excel me-1"></i> Export Data
                    </a>
                </li>
                <?php if (hasRole('admin')): ?>
                <li class="nav-item">
                    <a class="nav-link <?= ($currentPage === 'users.php') ? 'active' : '' ?>" href="users.php">
                        <i class="bi bi-people me-1"></i> User Management
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <?php if ($user): ?>
            <div class="d-flex align-items-center gap-3 text-white">
                <div class="text-end">
                    <div class="fw-semibold text-white small me-1"><?= sanitize($user['full_name']) ?></div>
                    <span class="badge badge-role badge-role-<?= $user['role'] ?>"><?= strtoupper($user['role']) ?></span>
                </div>
                <a href="logout.php" class="btn btn-sm btn-outline-light ms-2" title="Logout">
                    <i class="bi bi-box-arrow-right me-1"></i> Logout
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container-fluid px-4">
    <?php
    $flash = getFlash();
    if ($flash):
    ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show shadow-sm" role="alert">
        <i class="bi bi-info-circle me-2"></i><?= sanitize($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
</div>
