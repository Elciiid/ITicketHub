<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once '../services/CategoryHelper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';
$helper = new CategoryHelper($conn);

try {
    if ($action === 'add') {
        $name = trim($_POST['category_name'] ?? '');
        if (empty($name)) {
            throw new Exception("Category name is required");
        }

        if ($helper->addCategory($name)) {
            $helper->logHistory($_SESSION['username'], "Added new category: '$name'", "Category Management");
            echo json_encode(['success' => true]);
        } else {
            throw new Exception("Failed to add category");
        }

    } elseif ($action === 'update') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['category_name'] ?? '');

        if ($id <= 0 || empty($name)) {
            throw new Exception("Invalid ID or Category Name");
        }

        $oldCategory = $helper->getCategory($id);
        if ($helper->updateCategory($id, $name)) {
            $oldName = $oldCategory['category_name'] ?? 'Unknown';
            $logAction = "Updated category: '$name'";
            if ($oldName !== $name) {
                $logAction = "Updated category from '$oldName' to '$name'";
            }
            $helper->logHistory($_SESSION['username'], $logAction, "Category Management");
            echo json_encode(['success' => true]);
        } else {
            throw new Exception("Failed to update category");
        }

    } else {
        throw new Exception("Invalid action");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>