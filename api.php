<?php
require_once __DIR__ . '/config/db.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_types':
        $categoryId = intval($_GET['category_id'] ?? 0);
        if ($categoryId <= 0) {
            echo json_encode([]);
            exit;
        }
        $stmt = $pdo->prepare("SELECT id, type_name FROM document_types WHERE category_id = :cat_id ORDER BY type_name ASC");
        $stmt->execute([':cat_id' => $categoryId]);
        echo json_encode($stmt->fetchAll());
        exit;

    case 'get_attachments':
        $typeId = intval($_GET['type_id'] ?? 0);
        if ($typeId <= 0) {
            echo json_encode([]);
            exit;
        }
        $stmt = $pdo->prepare("SELECT id, item_name FROM attachment_items WHERE document_type_id = :type_id ORDER BY id ASC");
        $stmt->execute([':type_id' => $typeId]);
        echo json_encode($stmt->fetchAll());
        exit;

    case 'check_reference':
        $ref = trim($_GET['ref'] ?? '');
        $excludeId = intval($_GET['exclude_id'] ?? 0);
        
        if (empty($ref)) {
            echo json_encode(['exists' => false]);
            exit;
        }

        if ($excludeId > 0) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM documents WHERE reference_number = :ref AND id != :exclude_id");
            $stmt->execute([':ref' => $ref, ':exclude_id' => $excludeId]);
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM documents WHERE reference_number = :ref");
            $stmt->execute([':ref' => $ref]);
        }

        $count = $stmt->fetchColumn();
        echo json_encode(['exists' => ($count > 0)]);
        exit;

    case 'delete_attachment':
        if (!hasRole(['admin', 'encoder'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        $attId = intval($_POST['id'] ?? $_GET['id'] ?? 0);
        if ($attId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid attachment ID']);
            exit;
        }

        $stmtAtt = $pdo->prepare("SELECT file_path FROM document_attachments WHERE id = :id");
        $stmtAtt->execute([':id' => $attId]);
        $attachment = $stmtAtt->fetch();

        if ($attachment) {
            deleteAttachmentFile($pdo, $attachment['file_path'], $attId);
            $stmtDel = $pdo->prepare("DELETE FROM document_attachments WHERE id = :id");
            $stmtDel->execute([':id' => $attId]);
            echo json_encode(['success' => true, 'message' => 'File attachment deleted successfully.']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Attachment file not found']);
        }
        exit;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        exit;
}
