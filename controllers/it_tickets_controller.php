<?php
include 'includes/db.php';
require_once __DIR__ . '/../services/TicketService.php';
require_once __DIR__ . '/../services/UserService.php';

date_default_timezone_set('Asia/Manila');

if (empty($_SESSION['username'])) {
    $_SESSION['redirect_after_login'] = 'index.php';
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

// Fetch Department and Name
$stmt = $conn->prepare("SELECT department, firstname, lastname, middlename FROM lrn_master_list WHERE biometricsid = ? OR employeeid = ? LIMIT 1");
$stmt->execute([$username, $username]);
$userRow = $stmt->fetch(PDO::FETCH_ASSOC);
$department = $userRow['department'] ?? null;

// Populate session firstname/lastname/department if not already set (fixes blank name/dept on dashboard)
if (empty($_SESSION['firstname']) && empty($_SESSION['lastname'])) {
    if ($userRow && !empty($userRow['firstname'])) {
        $_SESSION['firstname'] = $userRow['firstname'];
        $_SESSION['lastname'] = $userRow['lastname'] ?? '';
        $_SESSION['department'] = $userRow['department'] ?? 'Unknown';
        $_SESSION['fullname'] = trim(($userRow['lastname'] ?? '') . ', ' . ($userRow['firstname'] ?? '') . ' ' . ($userRow['middlename'] ?? ''));
    } else {
        // OJT fallback
        try {
            $ojtStmt = $conn->prepare("SELECT full_name FROM app_ojt_employees WHERE employee_id = ? OR biometricsid = ? LIMIT 1");
            $ojtStmt->execute([$username, $username]);
            $ojtRow = $ojtStmt->fetch(PDO::FETCH_ASSOC);
            if ($ojtRow && !empty($ojtRow['full_name'])) {
                $_SESSION['firstname'] = $ojtRow['full_name'];
                $_SESSION['lastname'] = '';
                $_SESSION['department'] = 'OJT'; // Set department to OJT
                $_SESSION['fullname'] = $ojtRow['full_name'];
            }
        } catch (Exception $e) { /* OJT table may not be accessible */
        }
    }
} elseif (empty($_SESSION['department']) || $_SESSION['department'] === 'Unknown') {
    // If name is set but department isn't, sync it
    if ($department) {
        $_SESSION['department'] = $department;
    } else {
        // Check if user is actually an OJT
        try {
            $ojtCheck = $conn->prepare("SELECT COUNT(*) FROM app_ojt_employees WHERE employee_id = ? OR biometricsid = ?");
            $ojtCheck->execute([$username, $username]);
            if ($ojtCheck->fetchColumn() > 0) {
                $_SESSION['department'] = 'OJT';
            } else {
                $_SESSION['department'] = 'Unknown';
            }
        } catch (Exception $e) {
            $_SESSION['department'] = 'Unknown';
        }
    }
}

// Get User Role
$userService = new UserService($conn);
$userRole = $userService->getUserRole($username);

// Filters
$limit = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$dateFrom = $_GET['date_created_from'] ?? '';
$dateTo = $_GET['date_created_to'] ?? '';
$deptFilter = $_GET['department'] ?? '';
$assigneeFilter = isset($_GET['assignee']) ? trim($_GET['assignee']) : '';
$urgencyFilter = $_GET['urgency_level'] ?? '';
$completionDate = $_GET['completion_date'] ?? '';

$filters = [
    'search' => $search,
    'status' => $statusFilter,
    'date_from' => $dateFrom,
    'date_to' => $dateTo,
    'dept' => $deptFilter,
    'assignee' => $assigneeFilter,
    'urgency' => $urgencyFilter
];

// Fetch Tickets
$ticketService = new TicketService($conn);
$result = $ticketService->getTickets($filters, $page, $limit, $userRole, $username);

$tickets = $result['tickets'];
$totalTickets = $result['total'];
$totalPages = $result['pages'];

// Stats Logic (Still somewhat procedural but simplified)
// ... keeping specific count queries for dashboard widgets ...
// You can move these to TicketService later as getTicketStats()

// Role specific check
$isITStaff = UserService::hasRole($userRole, 'it_admin') || UserService::hasRole($userRole, 'it_pic');

// Consolidated status counts — single query instead of 5 separate queries
$statsQuery = "SELECT 
    SUM(CASE WHEN status = 'Open' THEN 1 ELSE 0 END) AS open_count,
    SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) AS closed_count,
    SUM(CASE WHEN urgency_level = 'High' AND status NOT IN ('Closed', 'Completed') THEN 1 ELSE 0 END) AS urgent_count,
    SUM(CASE WHEN status = 'Assigned' THEN 1 ELSE 0 END) AS assigned_count,
    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed_count
FROM it_ticket_request WHERE 1=1";

if (!$isITStaff) {
    $statsQuery .= " AND requestor = :username";
}

$statsStmt = $conn->prepare($statsQuery);
if (!$isITStaff) {
    $statsStmt->bindValue(':username', $username);
}
$statsStmt->execute();
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

$openCount = (int) $stats['open_count'];
$closedCount = (int) $stats['closed_count'];
$urgentCount = (int) $stats['urgent_count'];
$assignedCount = (int) $stats['assigned_count'];
$completedCount = (int) $stats['completed_count'];

// Pagination setup
$paginationRange = 3;
$startPage = max(1, $page - $paginationRange);
$endPage = min($totalPages, $page + $paginationRange);
$showFirstPage = ($startPage > 1);
$showLastPage = ($endPage < $totalPages);
$showStartDots = ($startPage > 2);
$showEndDots = ($endPage < $totalPages - 1);

// Survey helpers
function hasSubmittedSurvey($conn, $ticketId, $user)
{
    if (!$conn)
        return false;
    $sql = "SELECT COUNT(*) FROM ticket_surveys WHERE ticket_id = ? AND requestor_empcode = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$ticketId, $user]);
    return $stmt->fetchColumn() > 0;
}

function getSurveyResults($conn, $ticketId, $user)
{
    // Note: Updated to select q1-q10 to match get_survey.php
    $sql = "SELECT q1, q2, q3, q4, q5, q6, q7, q8, q9, q10, comments, submitted_at FROM ticket_surveys WHERE ticket_id = ? AND requestor_empcode = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$ticketId, $user]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getSurveyQuestions($conn)
{
    $sql = "SELECT id, question_text FROM survey_questions WHERE is_active = 1 ORDER BY sort_order ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch questions for use in the view
$surveyQuestions = getSurveyQuestions($conn);
?>