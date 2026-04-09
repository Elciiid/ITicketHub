<?php
include 'includes/db.php';

date_default_timezone_set('Asia/Manila'); // Set PHP timezone to PH

if (empty($_SESSION['username'])) {
    $_SESSION['redirect_after_login'] = 'dashboard.php';
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../services/UserService.php';

$username = $_SESSION['username']; // This should be the empcode or username

// Fetch department
$sql = "SELECT \"department\" FROM \"lrn_master_list\" WHERE \"biometricsid\" = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->execute([$username]);
$department = $stmt->fetchColumn();

// Save to session if not already set or if it's different
if (empty($_SESSION['department']) || $_SESSION['department'] !== $department) {
    if ($department) {
        $_SESSION['department'] = $department;
    } else {
        // Fallback for OJTs or users not in master list
        $_SESSION['department'] = 'OJT';
    }
}

// Fetch the user role using UserService for consistent logic
$userService = new UserService($conn);
$userRole = $userService->getUserRole($username);

$limit = 5; // Number of tickets per page
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Prepare filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$dateFrom = isset($_GET['date_created_from']) ? $_GET['date_created_from'] : '';
$dateTo = isset($_GET['date_created_to']) ? $_GET['date_created_to'] : '';
$deptFilter = isset($_GET['department']) ? $_GET['department'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$assigneeFilter = isset($_GET['assignee']) ? $_GET['assignee'] : '';
$completionDate = isset($_GET['completion_date']) ? $_GET['completion_date'] : '';
$urgencyFilter = isset($_GET['urgency_level']) ? $_GET['urgency_level'] : '';

// Role specific check
$isITStaff = UserService::hasRole($userRole, 'it_admin') || UserService::hasRole($userRole, 'it_pic');

// Build the query with filters — removed expensive STRING_SPLIT/STRING_AGG correlated subquery
$query = "SELECT 
    itr.\"id\", 
    itr.\"status\", 
    itr.\"date_created\", 
    COALESCE(ml.\"lastname\" || ', ' || ml.\"firstname\" || ' ' || ml.\"middlename\", itr.\"requestor\") AS \"requestor\", 
    itr.\"department\", 
    itr.\"subject\", 
    itr.\"urgency_level\",
    itr.\"assigned_to\",
    itr.\"date_updated\",
    COALESCE(c.\"category_name\", 'Uncategorized') AS \"category_name\"
FROM \"it_ticket_request\" itr
LEFT JOIN \"lrn_master_list\" ml ON itr.\"requestor\" = ml.\"biometricsid\"
LEFT JOIN \"it_ticket_categ\" c ON itr.\"categ_name\" = c.\"category_name\"
WHERE 1=1";

// Apply role-based filtering
if (!$isITStaff) {
    // For regular users, show only their tickets
    $query .= " AND itr.requestor = :username";
}

// Exclude "Closed" tickets by default unless the status filter is set to "Closed"
if ($statusFilter !== 'Closed') {
    $query .= " AND itr.status != 'Closed'";
}

// Apply additional filters
if ($search) {
    $query .= " AND itr.id LIKE :search";
}
if ($dateFrom) {
    $query .= " AND itr.date_created >= :dateFrom";
}
if ($dateTo) {
    $query .= " AND itr.date_created <= :dateTo";
}
if ($deptFilter) {
    $query .= " AND itr.department = :department";
}
if ($statusFilter) {
    $query .= " AND itr.status = :status";
}
if ($assigneeFilter) {
    $query .= " AND itr.assigned_to = :assignee";
}
if ($completionDate) {
    $query .= " AND itr.date_updated <= :completionDate";
}
if ($urgencyFilter) {
    $query .= " AND itr.urgency_level = :urgency_level";
}

$query .= " ORDER BY itr.date_created DESC LIMIT :limit OFFSET :offset";

$stmt = $conn->prepare($query);

// Bind parameters based on role
if (!$isITStaff) {
    $stmt->bindValue(':username', $username);
}

// Bind other filter parameters
if ($search) {
    $stmt->bindValue(':search', '%' . $search . '%');
}
if ($dateFrom) {
    $stmt->bindValue(':dateFrom', $dateFrom);
}
if ($dateTo) {
    $stmt->bindValue(':dateTo', $dateTo);
}
if ($deptFilter) {
    $stmt->bindValue(':department', $deptFilter);
}
if ($statusFilter) {
    $stmt->bindValue(':status', $statusFilter);
}
if ($assigneeFilter) {
    $stmt->bindValue(':assignee', $assigneeFilter);
}
if ($completionDate) {
    $stmt->bindValue(':completionDate', $completionDate);
}
if ($urgencyFilter) {
    $stmt->bindValue(':urgency_level', $urgencyFilter);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Resolve assigned_to names ONLY for the paginated results (max 5 rows)
foreach ($tickets as &$ticket) {
    if (!empty($ticket['assigned_to'])) {
        $empcodes = array_filter(array_map('trim', explode(',', $ticket['assigned_to'])));
        if (!empty($empcodes)) {
            $placeholders = implode(',', array_fill(0, count($empcodes), '?'));
            $nameStmt = $conn->prepare("SELECT firstname FROM lrn_master_list WHERE biometricsid IN ($placeholders)");
            $nameStmt->execute($empcodes);
            $names = $nameStmt->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($names)) {
                $ticket['assigned_to'] = implode(', ', $names);
            }
        }
    }
}
unset($ticket);

// Fetch total tickets for pagination
$totalQuery = "SELECT COUNT(*) FROM it_ticket_request WHERE 1=1";

// Apply the same role-based filtering to the count query
if (!$isITStaff) {
    $totalQuery .= " AND requestor = :username";
}

// Exclude "Closed" tickets by default unless the status filter is set to "Closed"
if ($statusFilter !== 'Closed') {
    $totalQuery .= " AND status != 'Closed'";
}

// Apply other filters to count query
if ($search) {
    $totalQuery .= " AND subject LIKE :search";
}
if ($dateFrom) {
    $totalQuery .= " AND date_created >= :dateFrom";
}
if ($dateTo) {
    $totalQuery .= " AND date_created <= :dateTo";
}
if ($deptFilter) {
    $totalQuery .= " AND department = :department";
}
if ($statusFilter) {
    $totalQuery .= " AND status = :status";
}
if ($assigneeFilter) {
    $totalQuery .= " AND assigned_to = :assignee";
}
if ($completionDate) {
    $totalQuery .= " AND date_updated <= :completionDate";
}
if ($urgencyFilter) {
    $totalQuery .= " AND urgency_level = :urgency_level";
}

$totalStmt = $conn->prepare($totalQuery);

// Bind parameters to count query based on role
if (!$isITStaff) {
    $totalStmt->bindValue(':username', $username);
}

// Bind other filter parameters to count query
if ($search) {
    $totalStmt->bindValue(':search', '%' . $search . '%');
}
if ($dateFrom) {
    $totalStmt->bindValue(':dateFrom', $dateFrom);
}
if ($dateTo) {
    $totalStmt->bindValue(':dateTo', $dateTo);
}
if ($deptFilter) {
    $totalStmt->bindValue(':department', $deptFilter);
}
if ($statusFilter) {
    $totalStmt->bindValue(':status', $statusFilter);
}
if ($assigneeFilter) {
    $totalStmt->bindValue(':assignee', $assigneeFilter);
}
if ($completionDate) {
    $totalStmt->bindValue(':completionDate', $completionDate);
}
if ($urgencyFilter) {
    $totalStmt->bindValue(':urgency_level', $urgencyFilter);
}

$totalStmt->execute();
$totalTickets = $totalStmt->fetchColumn();
$totalPages = ceil($totalTickets / $limit);

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

// Calculate pagination range
$paginationRange = 3; // Number of page links to show on each side of current page
$startPage = max(1, $page - $paginationRange);
$endPage = min($totalPages, $page + $paginationRange);

// Always show first and last page
$showFirstPage = ($startPage > 1);
$showLastPage = ($endPage < $totalPages);

// Determine if "..." indicators should be shown
$showStartDots = ($startPage > 2);
$showEndDots = ($endPage < $totalPages - 1);

// Fetch total tickets per month this year
$monthlyTicketsData = [];
$departmentRequestsData = [];

// Use SARGable date range instead of YEAR(date_created) = YEAR(GETDATE())
$yearStart = date('Y-01-01');
$yearEnd = date('Y-12-31 23:59:59');

// Query for total tickets per month — SARGable date filter
$monthlyTicketsQuery = "
SELECT 
    EXTRACT(MONTH FROM date_created) AS month,
    COUNT(*) AS created_count,
    SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) AS completed_count,
    SUM(CASE WHEN status = 'Open' THEN 1 ELSE 0 END) AS open_count
FROM 
    it_ticket_request
WHERE 
    date_created >= :year_start AND date_created <= :year_end
";

// Apply role filtering to monthly stats
if (!$isITStaff) {
    $monthlyTicketsQuery .= " AND requestor = :username";
}

$monthlyTicketsQuery .= " GROUP BY month ORDER BY month";

$stmt = $conn->prepare($monthlyTicketsQuery);
$stmt->bindValue(':year_start', $yearStart);
$stmt->bindValue(':year_end', $yearEnd);

// Bind username parameter for non-admin users
if (!$isITStaff) {
    $stmt->bindValue(':username', $username);
}

$stmt->execute();
$monthlyTicketsResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for the chart
$createdCounts = array_fill(0, 12, 0);
$completedCounts = array_fill(0, 12, 0);
$openCounts = array_fill(0, 12, 0);

foreach ($monthlyTicketsResults as $row) {
    $month = (int) $row['month'] - 1; // Adjust for zero-based index
    $createdCounts[$month] = (int) $row['created_count'];
    $completedCounts[$month] = (int) $row['completed_count'];
    $openCounts[$month] = (int) $row['open_count'];
}

// Fetch total requests per department this month — SARGable date filter
$monthStart = date('Y-m-01');
$monthEnd = date('Y-m-t 23:59:59');

$departmentRequestsQuery = "
    SELECT 
        department,
        COUNT(*) AS request_count
    FROM 
        it_ticket_request
    WHERE 
        date_created >= :month_start AND date_created <= :month_end
";

// Apply role filtering to department stats
if (!$isITStaff) {
    $departmentRequestsQuery .= " AND requestor = :username";
}

$departmentRequestsQuery .= " GROUP BY department";

$stmt = $conn->prepare($departmentRequestsQuery);
$stmt->bindValue(':month_start', $monthStart);
$stmt->bindValue(':month_end', $monthEnd);

// Bind username parameter for non-admin users
if (!$isITStaff) {
    $stmt->bindValue(':username', $username);
}

$stmt->execute();
$departmentRequestsResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for the department requests chart
$departments = [];
$requestCounts = [];

foreach ($departmentRequestsResults as $row) {
    $departments[] = $row['department'];
    $requestCounts[] = (int) $row['request_count'];
}

// Tickets per day query — SARGable date filter (no CAST)
$ticketsPerDayQuery = "
SELECT 
    EXTRACT(DOW FROM completed_by_dt) AS day_of_week,
    TO_CHAR(completed_by_dt, 'Day') AS day_name,
    COUNT(*) AS closed_tickets_count
FROM 
    it_ticket_request
WHERE 
    status = 'Closed'
    AND completed_by_dt >= :date_from AND completed_by_dt < (CAST(:date_to AS DATE) + INTERVAL '1 day')
";

// Apply role filtering to tickets per day stats
if (!$isITStaff) {
    $ticketsPerDayQuery .= " AND requestor = :username";
}

$ticketsPerDayQuery .= " GROUP BY day_of_week, day_name
                         ORDER BY day_of_week";

// Default date range if not provided
$default_from = date('Y-01-01'); // Start of current year
$default_to = date('Y-m-d'); // Current date

$date_from_val = isset($_GET['date_from']) ? $_GET['date_from'] : $default_from;
$date_to_val = isset($_GET['date_to']) ? $_GET['date_to'] : $default_to;

$stmtPerDay = $conn->prepare($ticketsPerDayQuery);
$stmtPerDay->bindValue(':date_from', $date_from_val);
$stmtPerDay->bindValue(':date_to', $date_to_val);

if (!$isITStaff) {
    $stmtPerDay->bindValue(':username', $username);
}

$stmtPerDay->execute();
$ticketsPerDayResults = $stmtPerDay->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for the chart
$daysOrder = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$ticketsPerDayCounts = array_fill_keys($daysOrder, 0);

foreach ($ticketsPerDayResults as $row) {
    $ticketsPerDayCounts[$row['day_name']] = (int) $row['closed_tickets_count'];
}

$ticketsPerDayCounts = array_values($ticketsPerDayCounts);
?>

<script>
    const createdCounts = <?php echo json_encode($createdCounts); ?>;
    const completedCounts = <?php echo json_encode($completedCounts); ?>;
    const openCounts = <?php echo json_encode($openCounts); ?>;

    const departments = <?php echo json_encode($departments); ?>;
    const requestCounts = <?php echo json_encode($requestCounts); ?>;
    const ticketsPerDayDateFrom = '<?php echo htmlspecialchars($date_from_val); ?>';
    const ticketsPerDayDateTo = '<?php echo htmlspecialchars($date_to_val); ?>';
    const ticketsPerDayCounts = <?php echo json_encode($ticketsPerDayCounts); ?>;
</script>