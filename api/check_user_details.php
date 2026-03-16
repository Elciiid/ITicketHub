<?php
include '../db/db.php';
require_once '../services/UserService.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['username'])) {
        throw new Exception("Username is required");
    }

    $username = $_GET['username'];
    $userService = new UserService($conn);
    $details = $userService->getUserDetails($username);

    if ($details) {
        echo json_encode(['success' => true, 'data' => $details]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found in master list']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>