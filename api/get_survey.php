<?php
include '../db/db.php'; // your database connection

header('Content-Type: application/json');

$ticketId = isset($_GET['ticketId']) ? intval($_GET['ticketId']) : 0;

if ($ticketId <= 0) {
    echo json_encode(['error' => 'Invalid ticket ID.']);
    exit;
}

try {
    // Fetch the survey row
    $sql = "SELECT id, ticket_id, requestor_empcode, q1, q2, q3, q4, q5, q6, q7, q8, q9, q10, comments, submitted_at 
            FROM dbo.ticket_surveys 
            WHERE ticket_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$ticketId]);
    $survey = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$survey) {
        echo json_encode(['error' => 'No survey found for this ticket.']);
        exit;
    }

    // Fetch survey questions from DB
    $qSql = "SELECT question_text FROM survey_questions WHERE is_active = 1 ORDER BY sort_order ASC";
    $qStmt = $conn->prepare($qSql);
    $qStmt->execute();
    $questionsList = $qStmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($questionsList)) {
        // Fallback or handle error
        $questionsList = [
            "Question 1",
            "Question 2",
            "Question 3",
            "Question 4"
        ];
    }

    $questions = [];
    $totalScore = 0;

    // Map q1–qX from the database into questions (up to number of columns available)
    $maxCols = 10; // Updated to support more questions
    $count = min(count($questionsList), $maxCols);

    for ($i = 1; $i <= $count; $i++) {
        $col = "q$i";   // matches DB column name exactly
        $answer = $survey[$col] ?? '';

        // Convert numeric score to readable text
        $score = match ($answer) {
            "Excellent" => 3,
            "Satisfied" => 2,
            "Poor" => 1,
            default => 0
        };

        $questions[] = [
            'question' => $questionsList[$i - 1],
            'score' => $score,
            'answer' => $answer
        ];

        $totalScore += $score;
    }

    // Return JSON in the format your JS expects
    echo json_encode([
        'questions' => $questions,
        'comments' => $survey['comments'] ?? '',
        'totalScore' => $totalScore,
        'submitted_at' => $survey['submitted_at']
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
