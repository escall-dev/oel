<?php
require_once __DIR__ . '/config/db.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Please enter both Employee ID and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];

            setFlash('success', 'Welcome back, ' . $user['full_name'] . '!');
            header("Location: index.php");
            exit();
        } else {
            $error = 'Invalid Employee ID or password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - QPTEO Electronic Logbook System</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">

    <div class="login-card">
        <div class="login-header">
            <img src="branding/TEC Seal no bg.png" alt="TEC Seal Logo" class="login-logo">
            <h1 class="login-title">QPTEO LOGBOOK</h1>
            <div class="login-subtitle">Electronic Incoming & Outgoing Repository</div>
        </div>

        <div class="login-body-content">
            <?php if ($error): ?>
                <div class="alert alert-danger py-2 mb-3 text-center small" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= sanitize($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" autocomplete="off">
                <div class="mb-3">
                    <label for="username" class="form-label">Employee ID</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted"><i class="bi bi-person-badge"></i></span>
                        <input type="text" class="form-control" id="username" name="username" placeholder="e.g. 1234567" required autofocus value="<?= sanitize($_POST['username'] ?? '') ?>">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary-custom w-100 py-2.5 fw-bold text-uppercase" style="letter-spacing: 0.5px;">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                </button>
            </form>
        </div>
        <div class="bg-light text-center py-3 border-top border-light text-muted small">
            <span>QPTEO Office File Repository System</span>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
