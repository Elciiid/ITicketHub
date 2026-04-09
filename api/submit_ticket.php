<?php
require_once __DIR__ . '/../includes/db.php';
require_once '../services/TicketService.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_SESSION['username'])) {
        echo json_encode(['success' => false, 'message' => 'You must be logged in to submit a ticket.']);
        exit();
    }

    $data = [
        'username' => $_SESSION['username'],
        'department' => $_POST['department'] ?? '',
        'category' => $_POST['category'] ?? '',
        'subject' => $_POST['subject'] ?? '',
        'description' => $_POST['description'] ?? ''
    ];

    if (empty($data['category']) || empty($data['subject']) || empty($data['description'])) {
        echo json_encode(['success' => false, 'message' => 'All fields are required!']);
        exit();
    }

    try {
        $ticketService = new TicketService($conn);
        $result = $ticketService->createTicket($data, $_FILES['attachments'] ?? []);

        if ($result['success']) {
            echo json_encode(['success' => true, 'message' => 'Ticket #' . $result['ticket_id'] . ' submitted successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error submitting ticket: ' . $result['message']]);
        }
    } catch (Exception $e) {
        // Log error
        file_put_contents('debug_error.log', date('Y-m-d H:i:s') . ' - Error: ' . $e->getMessage() . "\n", FILE_APPEND);
        echo json_encode(['success' => false, 'message' => 'Error submitting ticket: ' . $e->getMessage()]);
    }
} else {
    $_SESSION['error_message'] = 'Invalid request method.';
    header('Location: ../index.php');
    exit();
}
?>