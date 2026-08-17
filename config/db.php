<?php
// Configuration and Database Connection File for QPTEO Logbook System

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'qpteo_logbook_db');

function getDBConnection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            // Attempt auto initialization if database does not exist
            if ($e->getCode() == 1049 || str_contains($e->getMessage(), 'Unknown database')) {
                require_once __DIR__ . '/../setup_db.php';
                return getDBConnection();
            } else {
                die("Database Connection Error: " . $e->getMessage());
            }
        }
    }
    return $pdo;
}

// Global DB instance
$pdo = getDBConnection();

// Authentication Helpers
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function currentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? '',
        'role' => $_SESSION['role'] ?? 'viewer'
    ];
}

function hasRole($roles) {
    if (!isLoggedIn()) return false;
    $currentRole = $_SESSION['role'] ?? '';
    if (is_array($roles)) {
        return in_array($currentRole, $roles);
    }
    return $currentRole === $roles;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function requireRole($roles) {
    requireLogin();
    if (!hasRole($roles)) {
        setFlash('danger', 'Unauthorized access: You do not have permission to perform this action.');
        header("Location: index.php");
        exit();
    }
}

function sanitize($data) {
    return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
}

function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // success, danger, warning, info
        'message' => $message
    ];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Upload Helper - Stores files in uploads/YYYY/MM/
function saveUploadedFile($fileArray) {
    if (!$fileArray || $fileArray['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $year = date('Y');
    $month = date('m');
    $uploadDir = __DIR__ . "/../uploads/$year/$month/";
    
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileExt = strtolower(pathinfo($fileArray['name'], PATHINFO_EXTENSION));
    $originalName = pathinfo($fileArray['name'], PATHINFO_FILENAME);
    $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName);
    $uniqueFileName = $cleanName . '_' . time() . '_' . rand(1000, 9999) . ($fileExt ? '.' . $fileExt : '');
    
    $targetPath = $uploadDir . $uniqueFileName;
    $relativePath = "uploads/$year/$month/$uniqueFileName";

    if (move_uploaded_file($fileArray['tmp_name'], $targetPath)) {
        return $relativePath;
    }
    return null;
}

// Attachment File Deletion Helper - Ensures physical files are unlinked ONLY if no other document attachment references the path
function deleteAttachmentFile($pdo, $filePath, $excludeAttId = 0) {
    if (empty($filePath)) return;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM document_attachments WHERE file_path = :fp AND id != :exclude_id");
    $stmt->execute([':fp' => $filePath, ':exclude_id' => $excludeAttId]);
    if ($stmt->fetchColumn() == 0) {
        $absPath = __DIR__ . '/../' . $filePath;
        if (file_exists($absPath)) {
            @unlink($absPath);
        }
    }
}

