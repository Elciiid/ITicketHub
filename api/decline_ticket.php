<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../services/TicketService.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['ticket_id']) || !isset($data['reject_remarks'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    $ticketId = (int) $data['ticket_id'];
    $rejectRemarks = $data['reject_remarks'];
    $rejectBy = $_SESSION['username'] ?? 'Unknown';

    $ticketService = new TicketService($conn);
    $ticketService->declineTicket($ticketId, $rejectBy, $rejectRemarks);

    echo json_encode(['success' => true, 'message' => 'Ticket declined successfully']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>