<?php
include 'controllers/it_tickets_dashboard_controller.php';
include 'api/photo_helper.php';
include 'includes/layout.php';

// Pagination settings
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$recordsPerPage = 10; // Number of logs per page
$offset = ($page - 1) * $recordsPerPage;

// Filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$actionType = isset($_GET['action_type']) ? $_GET['action_type'] : '';
$days = isset($_GET['days']) ? $_GET['days'] : '7'; // Default to 7 days

// Build SQL query with filters
$sql = "
    SELECT 
        h.id, 
        h.ticket_id, 
        COALESCE(h.user_fullname, h.ticket_user) as ticket_user, 
        h.action, 
        h.date_time, 
        t.subject 
    FROM it_ticket_history_logs h
    LEFT JOIN it_ticket_request t ON h.ticket_id = t.id
    WHERE 1=1
";

$params = [];

// Add search filter
if (!empty($search)) {
    $sql .= " AND (t.subject LIKE ? OR h.action LIKE ? OR h.user_fullname LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

// Add action type filter
if (!empty($actionType)) {
    switch ($actionType) {
        case 'status_change':
            $sql .= " AND h.action LIKE '%status%'";
            break;
        case 'comment':
            $sql .= " AND (h.action LIKE '%comment%' OR h.action LIKE '%reply%')";
            break;
        case 'assigned':
            $sql .= " AND h.action LIKE '%assign%'";
            break;
        case 'priority':
            $sql .= " AND (h.action LIKE '%priority%' OR h.action LIKE '%urgent%')";
            break;
        case 'survey':
            $sql .= " AND h.action LIKE '%survey%'";
            break;
        case 'category':
            $sql .= " AND h.action LIKE '%category%'";
            break;
    }
}

// Add date filter
if ($days != 'all') {
    $daysInt = intval($days);
    $sql .= " AND h.date_time >= DATEADD(day, -$daysInt, GETDATE())";
}

// Count total records for pagination
$countSql = "SELECT COUNT(*) as total FROM (" . $sql . ") as filtered_results";
$stmt = $conn->prepare($countSql);

// Bind parameters for count query
for ($i = 0; $i < count($params); $i++) {
    $stmt->bindValue($i + 1, $params[$i]);
}

$stmt->execute();
$totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalRecords / $recordsPerPage);

// Add ORDER BY and OFFSET FETCH for pagination
$sql .= " ORDER BY h.date_time DESC
          OFFSET ? ROWS
          FETCH NEXT ? ROWS ONLY";

// Execute main query
$stmt = $conn->prepare($sql);

// Bind parameters for main query (text parameters)
for ($i = 0; $i < count($params); $i++) {
    $stmt->bindValue($i + 1, $params[$i]);
}

// Explicitly bind the OFFSET and FETCH parameters as integers
$stmt->bindValue(count($params) + 1, $offset, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, $recordsPerPage, PDO::PARAM_INT);

$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get action color for visual indication
function getActionColor($action)
{
    $action = strtolower($action);
    if (strpos($action, 'status') !== false) {
        return "#ec4899"; // Pink for status changes
    } elseif (strpos($action, 'comment') !== false || strpos($action, 'reply') !== false) {
        return "#10b981"; // Green for comments
    } elseif (strpos($action, 'assign') !== false) {
        return "#f59e0b"; // Orange for assignments
    } elseif (strpos($action, 'priority') !== false || strpos($action, 'urgent') !== false) {
        return "#ef4444"; // Red for priority changes
    } elseif (strpos($action, 'created') !== false) {
        return "#3b82f6"; // Blue for created
    } elseif (strpos($action, 'survey') !== false) {
        return "#8b5cf6"; // Purple for survey changes
    } elseif (strpos($action, 'category') !== false) {
        return "#06b6d4"; // Cyan for category changes
    } else {
        return "#64748b"; // Gray for other actions
    }
}

// Function to generate pagination URL
function getPaginationUrl($page)
{
    $params = $_GET;
    $params['page'] = $page;
    return '?' . http_build_query($params);
}

renderPageStart('ITicketHub - History Logs');
renderTopNav('history', $userRole);
?>

<!-- MAIN LAYOUT: Left Content + Right Panel -->
<div class="main-layout">

    <!-- LEFT: Main Content Area -->
    <main class="content-area">
        <div class="page-header">
            <h1><i class="fas fa-history"></i> History Logs</h1>
        </div>

        <!-- Filter Bar -->
        <form method="GET" action="" class="logs-filter-bar">
            <div class="filter-search">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Search logs..."
                    value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            </div>

            <select name="action_type" class="filter-select">
                <option value="">All Actions</option>
                <option value="status_change" <?php echo (isset($_GET['action_type']) && $_GET['action_type'] == 'status_change') ? 'selected' : ''; ?>>
                    Status Change</option>
                <option value="comment" <?php echo (isset($_GET['action_type']) && $_GET['action_type'] == 'comment') ? 'selected' : ''; ?>>
                    Comment Added</option>
                <option value="assigned" <?php echo (isset($_GET['action_type']) && $_GET['action_type'] == 'assigned') ? 'selected' : ''; ?>>
                    Ticket Assigned</option>
                <option value="priority" <?php echo (isset($_GET['action_type']) && $_GET['action_type'] == 'priority') ? 'selected' : ''; ?>>
                    Priority Change</option>
                <option value="survey" <?php echo (isset($_GET['action_type']) && $_GET['action_type'] == 'survey') ? 'selected' : ''; ?>>
                    Survey Management</option>
                <option value="category" <?php echo (isset($_GET['action_type']) && $_GET['action_type'] == 'category') ? 'selected' : ''; ?>>
                    Category Management</option>
            </select>

            <select name="days" class="filter-select">
                <option value="1" <?php echo (isset($_GET['days']) && $_GET['days'] == '1') ? 'selected' : ''; ?>>Last 24
                    hours</option>
                <option value="7" <?php echo (!isset($_GET['days']) || $_GET['days'] == '7') ? 'selected' : ''; ?>>Last 7
                    days</option>
                <option value="30" <?php echo (isset($_GET['days']) && $_GET['days'] == '30') ? 'selected' : ''; ?>>Last
                    30 days</option>
                <option value="90" <?php echo (isset($_GET['days']) && $_GET['days'] == '90') ? 'selected' : ''; ?>>Last
                    90 days</option>
                <option value="all" <?php echo (isset($_GET['days']) && $_GET['days'] == 'all') ? 'selected' : ''; ?>>All
                    time</option>
            </select>

            <button type="submit" class="filter-btn">
                <i class="fas fa-filter"></i> Filter
            </button>
            <button type="button" class="filter-btn" style="background-color: #ef4444; margin-left: 8px;"
                onclick="window.location.href='history_logs.php'">
                <i class="fas fa-sync"></i> Reset
            </button>
        </form>

        <!-- Log Entries -->
        <div class="log-entries">
            <?php if (count($logs) > 0): ?>
                <?php foreach ($logs as $log): ?>
                    <?php
                    $isSystemLog = ($log['ticket_id'] == 0);
                    $subject = $isSystemLog ? 'System Log' : ($log['subject'] ?: 'No Subject');
                    $badgeText = $isSystemLog ? 'SYS' : '#' . htmlspecialchars($log['ticket_id']);
                    $clickAction = $isSystemLog ? "window.location.href='manage_users.php'" : "window.location.href='index.php?ticket_id=" . $log['ticket_id'] . "'";
                    // Or prevent click for system logs if preferred, but linking to manage_users makes sense.
                    ?>
                    <div class="log-entry" onclick="<?php echo $clickAction; ?>">
                        <div class="log-entry-indicator"
                            style="background-color: <?php echo getActionColor($log['action']); ?>"></div>
                        <div class="log-content">
                            <div class="log-title">
                                <span class="ticket-badge"
                                    style="<?php echo $isSystemLog ? 'background: #64748b;' : ''; ?>"><?php echo $badgeText; ?></span>
                                <?php echo htmlspecialchars($subject); ?>
                            </div>
                            <div class="log-action">
                                <?php echo htmlspecialchars($log['action']); ?>
                            </div>
                            <div class="log-meta">
                                <span><i class="fas fa-user"></i>
                                    <?php echo htmlspecialchars($log['ticket_user'] ?: 'Unknown'); ?></span>
                                <span><i class="fas fa-clock"></i>
                                    <?php echo date('M d, Y h:i A', strtotime($log['date_time'])); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No log entries found</h3>
                    <p>Try adjusting your search or filter criteria</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination info -->
        <?php if ($totalRecords > 0): ?>
            <div class="pagination-info">
                Showing <?php echo min(($page - 1) * $recordsPerPage + 1, $totalRecords); ?> to
                <?php echo min($page * $recordsPerPage, $totalRecords); ?> of <?php echo $totalRecords; ?> entries
            </div>
        <?php endif; ?>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="<?php echo getPaginationUrl($page - 1); ?>" class="page-link">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php else: ?>
                    <span class="page-link disabled"><i class="fas fa-chevron-left"></i></span>
                <?php endif; ?>

                <?php
                // Determine pagination range
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $startPage + 4);

                if ($endPage - $startPage < 4) {
                    $startPage = max(1, $endPage - 4);
                }

                for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                    <a href="<?php echo getPaginationUrl($i); ?>"
                        class="page-link <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="<?php echo getPaginationUrl($page + 1); ?>" class="page-link">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php else: ?>
                    <span class="page-link disabled"><i class="fas fa-chevron-right"></i></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <style>
            .logs-filter-bar {
                display: flex;
                gap: 12px;
                align-items: center;
                flex-wrap: wrap;
                background: white;
                padding: 16px 20px;
                border-radius: 16px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
                margin-bottom: 20px;
            }

            .filter-search {
                flex: 1;
                min-width: 200px;
                position: relative;
            }

            .filter-search i {
                position: absolute;
                left: 14px;
                top: 50%;
                transform: translateY(-50%);
                color: #94a3b8;
            }

            .filter-search input {
                width: 100%;
                padding: 12px 12px 12px 42px;
                border: 2px solid #e5e7eb;
                border-radius: 12px;
                font-size: 14px;
                transition: all 0.2s ease;
            }

            .filter-search input:focus {
                outline: none;
                border-color: #ec4899;
                box-shadow: 0 0 0 3px rgba(236, 72, 153, 0.1);
            }

            .filter-select {
                padding: 12px 16px;
                border: 2px solid #e5e7eb;
                border-radius: 12px;
                font-size: 14px;
                background: white;
                cursor: pointer;
                transition: all 0.2s ease;
            }

            .filter-select:focus {
                outline: none;
                border-color: #ec4899;
            }

            .filter-btn {
                padding: 12px 24px;
                background: linear-gradient(135deg, #ec4899, #db2777);
                color: white;
                border: none;
                border-radius: 12px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
            }

            .filter-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(236, 72, 153, 0.3);
            }

            .log-entries {
                display: flex;
                flex-direction: column;
                gap: 12px;
            }

            .log-entry {
                background: white;
                border-radius: 16px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
                padding: 16px 20px;
                position: relative;
                overflow: hidden;
                cursor: pointer;
                transition: all 0.3s ease;
                display: flex;
            }

            .log-entry:hover {
                transform: translateY(-3px);
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
            }

            .log-entry-indicator {
                width: 4px;
                border-radius: 2px;
                margin-right: 16px;
                flex-shrink: 0;
            }

            .log-content {
                flex: 1;
            }

            .log-title {
                font-weight: 600;
                color: #1e293b;
                font-size: 15px;
                margin-bottom: 6px;
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .ticket-badge {
                background: linear-gradient(135deg, #ec4899, #db2777);
                color: white;
                padding: 3px 10px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 600;
            }

            .log-action {
                color: #64748b;
                font-size: 14px;
                margin-bottom: 10px;
            }

            .log-meta {
                display: flex;
                gap: 20px;
                color: #94a3b8;
                font-size: 12px;
            }

            .log-meta i {
                margin-right: 5px;
            }

            .empty-state {
                text-align: center;
                padding: 60px 20px;
                background: white;
                border-radius: 16px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            }

            .empty-state i {
                font-size: 48px;
                color: #e5e7eb;
                margin-bottom: 16px;
            }

            .empty-state h3 {
                color: #1e293b;
                margin-bottom: 8px;
            }

            .empty-state p {
                color: #64748b;
            }

            .pagination {
                display: flex;
                justify-content: center;
                gap: 8px;
                margin-top: 24px;
            }

            .page-link {
                padding: 10px 16px;
                background: white;
                border: 2px solid #e5e7eb;
                border-radius: 10px;
                color: #64748b;
                text-decoration: none;
                font-weight: 500;
                transition: all 0.2s ease;
            }

            .page-link:hover:not(.disabled) {
                border-color: #ec4899;
                color: #ec4899;
            }

            .page-link.active {
                background: linear-gradient(135deg, #ec4899, #db2777);
                border-color: #ec4899;
                color: white;
            }

            .page-link.disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }

            .pagination-info {
                text-align: center;
                margin-top: 20px;
                color: #64748b;
                font-size: 14px;
            }

            @media (max-width: 768px) {
                .logs-filter-bar {
                    flex-direction: column;
                }

                .filter-search {
                    width: 100%;
                }

                .filter-select,
                .filter-btn {
                    width: 100%;
                }
            }
        </style>
    </main> <!-- End content-area -->

    <?php renderRightPanel($userRole, 'history'); ?>

</div> <!-- End main-layout -->

<?php renderFooter(); ?>

<?php
renderPageEnd();
$conn = null;
?>