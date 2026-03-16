<?php
session_start();
include '../db/db.php';
require_once '../services/UserService.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['username'])) {
        throw new Exception("Unauthorized action");
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['username'])) {
        throw new Exception("Username is required");
    }

    $username = $data['username'];
    $role = $data['role'] ?? null;
    $status = $data['status'] ?? null;
    $actor = $_SESSION['username'];

    $userService = new UserService($conn);
    $userService->updateUser($username, $role, $status, $actor);

    echo json_encode(['success' => true, 'message' => 'User updated successfully']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>