<?php
// Setup script to initialize MySQL database for QPTEO Logbook System
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'qpteo_logbook_db';

try {
    // Connect to MySQL server without database selected
    $pdo = new PDO("mysql:host=$host", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");

    // Read and run schema.sql
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    $pdo->exec($sql);

    // Ensure superadmin password hash is strictly set to 'escall'
    $adminPasswordHash = password_hash('escall', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = :pass WHERE username = '061920'");
    $stmt->execute([':pass' => $adminPasswordHash]);

    // If update affected 0 rows (user doesn't exist yet for some reason), insert
    $check = $pdo->query("SELECT id FROM users WHERE username = '061920'")->fetch();
    if (!$check) {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES ('061920', :pass, 'Super Admin', 'admin')");
        $stmt->execute([':pass' => $adminPasswordHash]);
    }

    echo "SUCCESS: Database '$dbname' initialized successfully!\n";
    echo "Superadmin username: 061920\n";
    echo "Superadmin password: escall\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
