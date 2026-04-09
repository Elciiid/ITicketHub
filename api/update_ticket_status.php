<?php
include '../includes/db.php'; // your DB connection

if (isset($_POST['ticket_id'], $_POST['status'])) {
    $ticketId = intval($_POST['ticket_id']);
    $status = $_POST['status'];

    $sql = "UPDATE it_ticket_request SET status = ?, date_updated = CURRENT_TIMESTAMP WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt->execute([$status, $ticketId])) {
        echo "SUCCESS: Ticket $ticketId updated to $status";
    } else {
        $errorInfo = $stmt->errorInfo();
        echo "ERROR: " . $errorInfo[2];
    }
} else {
    echo "ERROR: ticket_id or status missing";
}
?>