<?php
session_start();
include '../db/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $ticket_id = $_POST['ticket_id'] ?? null;
    $requestor_empcode = $_SESSION['username'];
    $comments = $_POST['comments'] ?? null;

    // Fetch active questions from DB to know how many to expect
    $qSql = "SELECT id FROM survey_questions WHERE is_active = 1 ORDER BY sort_order ASC";
    $qStmt = $conn->prepare($qSql);
    $qStmt->execute();
    $questions = $qStmt->fetchAll(PDO::FETCH_ASSOC);
    $qCount = count($questions);
    $maxCols = 10; // Updated from 4 to 10 to support more questions

    $answers = [];
    $valid = true;
    for ($i = 1; $i <= min($qCount, $maxCols); $i++) {
        $val = $_POST['q' . $i] ?? null;
        if (!$val) {
            $valid = false;
            break;
        }
        $answers['q' . $i] = $val;
    }

    if (!$ticket_id || !$valid) {
        echo json_encode(['success' => false, 'message' => "Please answer all questions."]);
        exit;
    }

    try {
        $checkSql = "SELECT COUNT(*) FROM ticket_surveys WHERE ticket_id = ? AND requestor_empcode = ?";
        $stmt = $conn->prepare($checkSql);
        $stmt->execute([$ticket_id, $requestor_empcode]);
        $exists = $stmt->fetchColumn();

        if ($exists) {
            echo json_encode(['success' => false, 'message' => "You have already submitted the survey for this ticket."]);
            exit;
        }

        // Build dynamic insert
        $cols = "ticket_id, requestor_empcode, comments, submitted_at";
        $valsArr = [$ticket_id, $requestor_empcode, $comments];
        $placeholders = "?, ?, ?, GETDATE()";

        foreach ($answers as $col => $ans) {
            $cols .= ", $col";
            $placeholders .= ", ?";
            $valsArr[] = $ans;
        }

        $insertSql = "INSERT INTO ticket_surveys ($cols) VALUES ($placeholders)";
        $stmt = $conn->prepare($insertSql);
        $stmt->execute($valsArr);

        echo json_encode(['success' => true, 'message' => "Survey submitted successfully!"]);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => "Error submitting survey: " . $e->getMessage()]);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => "Invalid request method."]);
    exit;
}
?>