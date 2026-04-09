<?php
session_start();
include '../db/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticketId = $_POST['ticketId'];
    $status = $_POST['status'];
    $remarks = $_POST['remarks'];
    $username = $_SESSION['username']; // current user

    try {
        $conn->beginTransaction();

        // Update SQL
        if ($status === 'Completed') {
            // Only set completed fields if status is Completed
            $updateSql = "
                UPDATE it_ticket_request
                SET 
                    status = :status,
                    completed_by = :username,
                    completed_by_dt = CURRENT_TIMESTAMP,
                    date_updated = CURRENT_TIMESTAMP,
                    resolution = :remarks
                WHERE id = :ticketId
            ";
        } else {
            // For other statuses, do not update completed fields
            $updateSql = "
                UPDATE it_ticket_request
                SET 
                    status = :status,
                    date_updated = CURRENT_TIMESTAMP,
                    resolution = :remarks
                WHERE id = :ticketId
            ";
        }

        $stmt = $conn->prepare($updateSql);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':ticketId', $ticketId);
        $stmt->bindParam(':remarks', $remarks);
        if ($status === 'Completed') {
            $stmt->bindParam(':username', $username);
        }
        $stmt->execute();

        // Insert history log
        // Try to get fullname from session, or default to username
        $fullname = $_SESSION['fullname'] ?? $username;
        $historyAction = "Ticket updated to $status";
        $historySql = "
            INSERT INTO it_ticket_history_logs (ticket_id, ticket_user, user_fullname, action, status, remarks, date_time)
            VALUES (:ticketId, :username, :fullname, :action, :status, :remarks, CURRENT_TIMESTAMP)
        ";
        $historyStmt = $conn->prepare($historySql);
        $historyStmt->bindParam(':ticketId', $ticketId);
        $historyStmt->bindParam(':username', $username);
        $historyStmt->bindParam(':fullname', $fullname);
        $historyStmt->bindParam(':action', $historyAction);
        $historyStmt->bindParam(':status', $status);
        $historyStmt->bindParam(':remarks', $remarks);
        $historyStmt->execute();

        // Handle attachments
        if (!empty($_FILES['resoAttachments']['name'][0])) {
            $uploadDir = __DIR__ . "/../uploads_pic/$ticketId/";
            if (!is_dir($uploadDir))
                mkdir($uploadDir, 0755, true);

            foreach ($_FILES['resoAttachments']['tmp_name'] as $key => $tmpName) {
                $fileNameOriginal = $_FILES['resoAttachments']['name'][$key];
                $sanitizedName = preg_replace("/[^a-zA-Z0-9._-]/", "_", $fileNameOriginal);
                $newFileName = $sanitizedName;
                $destPath = $uploadDir . $newFileName;

                if (move_uploaded_file($tmpName, $destPath)) {
                    $filePath = "uploads_pic/$ticketId/$newFileName";
                    $attachSql = "
                        INSERT INTO it_ticket_pic_attachments (ticketid, filepath, created_at, uploaded_by)
                        VALUES (?, ?, CURRENT_TIMESTAMP, ?)
                    ";
                    $attachStmt = $conn->prepare($attachSql);
                    $attachStmt->execute([$ticketId, $filePath, $fullname]);
                }
            }
        }

        $conn->commit();
        echo json_encode(['message' => 'Ticket updated successfully.']);
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['error_message'] = 'Error updating ticket: ' . $e->getMessage();
        echo json_encode(['message' => 'Error updating ticket: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['message' => 'Invalid request method.']);
}
