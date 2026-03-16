<?php
// process_survey_question.php
session_start();
include '../db/db.php';
require_once '../services/SurveyQuestionHelper.php';
require_once '../services/UserService.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Basic security check - only IT Admin can edit questions
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';
$helper = new SurveyQuestionHelper($conn);

try {
    if ($action === 'add') {
        $text = trim($_POST['question_text'] ?? '');
        $sort = intval($_POST['sort_order'] ?? 0);

        if (empty($text)) {
            throw new Exception("Question text is required");
        }

        if ($helper->addQuestion($text, $sort)) {
            $helper->logHistory($_SESSION['username'], "Added new survey question: '$text'", "Survey Management");
            echo json_encode(['success' => true]);
        } else {
            throw new Exception("Failed to add question");
        }

    } elseif ($action === 'update') {
        $id = intval($_POST['id'] ?? 0);
        $text = trim($_POST['question_text'] ?? '');
        $sort = intval($_POST['sort_order'] ?? 0);
        $active = isset($_POST['is_active']) ? 1 : 0;

        if ($id <= 0 || empty($text)) {
            throw new Exception("Invalid ID or Question Text");
        }

        $oldQuestion = $helper->getQuestion($id);
        if ($helper->updateQuestion($id, $text, $sort, $active)) {
            $oldText = $oldQuestion['question_text'] ?? 'Unknown';
            $logAction = "Updated survey question: '$text'";
            if ($oldText !== $text) {
                $logAction = "Updated survey question from '$oldText' to '$text'";
            }
            if ($oldQuestion['is_active'] != $active) {
                $statusStr = $active ? 'Enabled' : 'Disabled';
                $logAction .= " (Status changed to $statusStr)";
            }
            $helper->logHistory($_SESSION['username'], $logAction, "Survey Management");
            echo json_encode(['success' => true]);
        } else {
            throw new Exception("Failed to update question");
        }

    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            throw new Exception("Invalid ID");
        }

        $oldQuestion = $helper->getQuestion($id);
        if ($helper->deleteQuestion($id)) {
            $oldText = $oldQuestion['question_text'] ?? 'Unknown';
            $helper->logHistory($_SESSION['username'], "Deleted survey question: '$oldText'", "Survey Management");
            echo json_encode(['success' => true]);
        } else {
            throw new Exception("Failed to delete question");
        }

    } else {
        throw new Exception("Invalid action");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>