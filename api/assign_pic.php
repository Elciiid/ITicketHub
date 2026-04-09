<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../services/TicketService.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticketId = $_POST['ticketId'];
    $assignees = $_POST['assignees'] ?? [];
    $priorityLevel = $_POST['priorityLevel'];
    $assignedBy = $_SESSION['username'];

    try {
        $ticketService = new TicketService($conn);
        $ticketService->assignTicket($ticketId, $assignedBy, $assignees, $priorityLevel);

        echo json_encode(['message' => 'Ticket assigned successfully.']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Error assigning ticket: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Invalid request method.']);
}
?>