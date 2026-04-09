<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/controllers/it_tickets_controller.php';
require_once __DIR__ . '/api/fetch_categ.php';
require_once __DIR__ . '/api/photo_helper.php';

// Fetch assignee data from the database
$sql = "SELECT 
    ir.empcode as empcode, 
    ml.biometricsid,
    COALESCE(ml.firstname, ojt.full_name, ir.empcode) as firstname,
    COALESCE(
        NULLIF(TRIM(COALESCE(ml.lastname, '') || ', ' || COALESCE(ml.firstname, '') || ' ' || COALESCE(ml.middlename, '')), ',  '),
        ojt.full_name,
        ir.empcode
    ) as fullname,
    COALESCE(ml.department, 'OJT') as department
FROM it_ticket_roles ir 
LEFT JOIN lrn_master_list ml ON (ml.biometricsid = ir.empcode OR ml.employeeid = ir.empcode)
LEFT JOIN app_ojt_employees ojt ON ir.empcode = ojt.employee_id
WHERE ir.ticket_role LIKE '%it_pic%' AND ir.isactive = 1";
$stmt = $conn->prepare($sql);
$stmt->execute();

$assignees = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Fetch department data strictly from the master list
$sqlDept = "SELECT DISTINCT \"department\" as \"department\" FROM \"lrn_master_list\" WHERE \"department\" IS NOT NULL AND \"department\" != '' ORDER BY \"department\" ASC";
$stmtDept = $conn->prepare($sqlDept);
$stmtDept->execute();

$departments = $stmtDept->fetchAll(PDO::FETCH_ASSOC);
// <span class="role">(<?php echo htmlspecialchars($_SESSION['role']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ITicketHub</title>
    <link rel="stylesheet" href="css\ticket_summary.css">
    <link rel="stylesheet" href="css\modern_sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .table-container th {
            text-align: center !important;
        }
    </style>
</head>

<body>

    <!-- APP CONTAINER (The "Island") -->
    <div class="app-container">
        
        <!-- TOP NAVIGATION BAR -->
        <header class="top-nav">
            <!-- Cherry Blossom Petals -->
            <div class="meteor-container">
                <span class="meteor"></span>
                <span class="meteor"></span>
                <span class="meteor"></span>
                <span class="meteor"></span>
                <span class="meteor"></span>
                <span class="meteor"></span>
                <span class="meteor"></span>
            </div>
            <div class="nav-brand">
                <img src="img/tickethublogo.png?v=2" alt="Logo">
            </div>
            
            <!-- Category Pills (Status Filters) -->
            <nav class="nav-pills">
                <a href="?status=" class="pill <?php echo empty($_GET['status']) && empty($_GET['urgency_level']) ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i> All Tickets
                </a>
                <a href="?status=Open" class="pill <?php echo ($_GET['status'] ?? '') === 'Open' ? 'active' : ''; ?>">
                    <i class="fas fa-folder-open"></i> Open
                </a>
                <a href="?status=Assigned" class="pill <?php echo ($_GET['status'] ?? '') === 'Assigned' ? 'active' : ''; ?>">
                    <i class="fas fa-user-check"></i> Assigned
                </a>
                <a href="?urgency_level=High" class="pill <?php echo ($_GET['urgency_level'] ?? '') === 'High' ? 'active' : ''; ?>">
                    <i class="fas fa-exclamation-circle"></i> Urgent
                </a>
                <a href="?status=Completed" class="pill <?php echo ($_GET['status'] ?? '') === 'Completed' ? 'active' : ''; ?>">
                    <i class="fas fa-check-circle"></i> Completed
                </a>
                <a href="?status=Closed" class="pill <?php echo ($_GET['status'] ?? '') === 'Closed' ? 'active' : ''; ?>">
                    <i class="fas fa-lock"></i> Closed
                </a>
            </nav>

            <div class="nav-actions">
                <?php if (UserService::hasRole($userRole, 'it_admin')): ?>
                    <a href="manage_survey_questions.php" class="icon-btn" title="Manage Survey Questions"><i class="fas fa-poll"></i></a>
                    <a href="manage_categories.php" class="icon-btn" title="Manage Categories"><i class="fas fa-layer-group"></i></a>
                    <a href="manage_users.php" class="icon-btn" title="Manage Users"><i class="fas fa-users-cog"></i></a>
                <?php endif; ?>
                <button class="icon-btn sidebar-toggle-btn" id="navPanelToggle" onclick="toggleRightPanel()" title="Toggle sidebar">
                    <i class="fas fa-columns"></i>
                </button>
            </div>
        </header>

        <!-- MAIN LAYOUT: Left Content + Right Panel -->
        <div class="main-layout">
            
            <!-- LEFT: Main Content Area -->
            <main class="content-area">
                <div class="page-header">
                    <h1>Ticket Overview 
                        <span class="info-video-wrapper">
                            <i class="fas fa-info-circle page-header-info-icon"></i>
                            <div class="info-video-tooltip">
                                <div class="info-video-tooltip-arrow"></div>
                                <div class="info-video-tooltip-header">
                                    <i class="fas fa-play-circle"></i> How to Submit a Ticket
                                </div>
                                <video class="info-video-player" muted loop playsinline>
                                    <source src="How to submit a ticket.mp4" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            </div>
                        </span>
                    </h1>
                </div>

                <!-- Summary Cards Row -->
                <div class="stats-row">
                    <div class="stat-card" onclick="location.href='?status=Open'" style="cursor: pointer;">
                        <div class="stat-icon open"><i class="fas fa-folder-open"></i></div>
                        <div class="stat-info">
                            <span class="stat-value"><?php echo $openCount; ?></span>
                            <span class="stat-label">Open</span>
                        </div>
                    </div>
                    <div class="stat-card" onclick="location.href='?status=Assigned'" style="cursor: pointer;">
                        <div class="stat-icon assigned"><i class="fas fa-user-check"></i></div>
                        <div class="stat-info">
                            <span class="stat-value"><?php echo $assignedCount; ?></span>
                            <span class="stat-label">Assigned</span>
                        </div>
                    </div>
                    <div class="stat-card" onclick="location.href='?urgency_level=High'" style="cursor: pointer;">
                        <div class="stat-icon urgent"><i class="fas fa-fire"></i></div>
                        <div class="stat-info">
                            <span class="stat-value"><?php echo $urgentCount; ?></span>
                            <span class="stat-label">Urgent</span>
                        </div>
                    </div>
                    <div class="stat-card" onclick="location.href='?status=Completed'" style="cursor: pointer;">
                        <div class="stat-icon completed"><i class="fas fa-check-double"></i></div>
                        <div class="stat-info">
                            <span class="stat-value"><?php echo $completedCount; ?></span>
                            <span class="stat-label">Completed</span>
                        </div>
                    </div>
                    <div class="stat-card" onclick="location.href='?status=Closed'" style="cursor: pointer;">
                        <div class="stat-icon closed"><i class="fas fa-lock"></i></div>
                        <div class="stat-info">
                            <span class="stat-value"><?php echo $closedCount; ?></span>
                            <span class="stat-label">Closed</span>
                        </div>
                    </div>
                </div>


    <!-- JavaScript Alert for Success or Error Messages -->
    <?php
    if (isset($_SESSION['success_message'])) {
        echo "<script>alert('" . addslashes($_SESSION['success_message']) . "');</script>";
        unset($_SESSION['success_message']); // Remove message after showing
    }

    if (isset($_SESSION['error_message'])) {
        echo "<script>alert('" . addslashes($_SESSION['error_message']) . "');</script>";
        unset($_SESSION['error_message']); // Remove message after showing
    }
    ?>
    

    <div class="table-controls">
        <!-- New Ticket Button -->
        <button class="new-ticket-btn" onclick="document.getElementById('newTicketModal').style.display='block'">
            <i class="fas fa-plus"></i> New Ticket
        </button>

        <!-- Filters -->
        <form method="GET" action="">
            <div class="filters">
                <!-- Search -->
                <div class="filter-group">
                    <input type="text" name="search" id="search" placeholder="Search ticket..."
                        value="<?php echo htmlspecialchars($search); ?>">
                </div>

                <!-- Date Created (From) -->
                <div class="filter-group">
                    <div class="date-input-container">
                        <input type="date" name="date_created_from" id="date-created-from"
                            value="<?php echo htmlspecialchars($dateFrom); ?>">
                        <span class="date-label">Created From</span>
                    </div>
                </div>

                <!-- Date Created (To) -->
                <div class="filter-group">
                    <div class="date-input-container">
                        <input type="date" name="date_created_to" id="date-created-to"
                            value="<?php echo htmlspecialchars($dateTo); ?>">
                        <span class="date-label">Created To</span>
                    </div>
                </div>

                <!-- Department -->
                <div class="filter-group">
                    <select name="department" id="department">
                        <option value="">Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept['department']); ?>"
                                <?php echo ($deptFilter == $dept['department']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['department']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Assignee -->
                <div class="filter-group">
                    <select name="assignee" id="assignee">
                        <option value="">Assignee</option>
                        <?php foreach ($assignees as $assignee): 
                            $assigneeVal = !empty($assignee['biometricsid']) ? $assignee['biometricsid'] : $assignee['empcode'];
                            // Trim to ensure clean comparison
                            $assigneeVal = trim($assigneeVal);
                        ?>
                            <option value="<?php echo htmlspecialchars($assigneeVal); ?>"
                                <?php echo ($assigneeFilter == $assigneeVal) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($assignee['fullname']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Status -->
                <div class="filter-group">
                    <select name="status" id="status">
                        <option value="">Status</option>
                        <option value="Open" <?php echo ($statusFilter == 'Open') ? 'selected' : ''; ?>>Open</option>
                        <option value="Pending" <?php echo ($statusFilter == 'Pending') ? 'selected' : ''; ?>>Pending
                        </option>
                        <option value="Closed" <?php echo ($statusFilter == 'Closed') ? 'selected' : ''; ?>>Closed
                        </option>
                        <option value="Assigned" <?php echo ($statusFilter == 'Assigned') ? 'selected' : ''; ?>>Assigned
                        </option>
                        <option value="In Progress" <?php echo ($statusFilter == 'In Progress') ? 'selected' : ''; ?>>In
                            Progress</option>
                        <option value="Completed" <?php echo ($statusFilter == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>

                <!-- Apply Filter Button -->
                <!-- <button type="submit" class="apply-filter-btn">
                    <i class="fas fa-filter"></i> Apply Filter
                </button> -->

                <button type="button" class="reset-filter-btn" onclick="window.location.href='index.php'">
                    <i class="fas fa-sync"></i> Reset Filter
                </button> 
            </div>
        </form>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Ticket</th>
                    <th>Status</th>
                    <th>Assigned To</th>
                    <th>Category</th>
                    <th>Subject</th>
                    <th>Requestor</th>
                    <th>Department</th>
                    <th>Priority</th>
                    <th>Created Date</th>
                    <th>Last Updated</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($tickets)) {
                    foreach ($tickets as $ticket) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($ticket['id']) . "</td>";
                        echo "<td><span class='status-badge status-" . strtolower(str_replace(' ', '-', htmlspecialchars($ticket['status']))) . "'>" . htmlspecialchars($ticket['status']) . "</span></td>";
                        echo "<td>" . (!empty($ticket['assigned_to']) ? htmlspecialchars($ticket['assigned_to']) : "Unassigned") . "</td>";
                        echo "<td>" . htmlspecialchars($ticket['category_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($ticket['subject']) . "</td>";  
                        echo "<td>".  htmlspecialchars($ticket['requestor']). "</td>";
                      echo "<td class='hidden-requestor'>" . htmlspecialchars($ticket['requestor']) . "</td>";
                        echo "<td>" . htmlspecialchars($ticket['department']) . "</td>";
                        echo "<td><span class='priority-badge priority-" . strtolower(!empty($ticket['urgency_level']) ? htmlspecialchars($ticket['urgency_level']) : "Unassigned") . "'>" . (!empty($ticket['urgency_level']) ? htmlspecialchars($ticket['urgency_level']) : "Unassigned") . "</span></td>";
                        echo "<td data-date-created='" . htmlspecialchars($ticket['date_created']) . "'>" . htmlspecialchars($ticket['date_created']) . "</td>";
                        echo "<td data-date-updated='" . htmlspecialchars($ticket['date_updated']) . "'>" . htmlspecialchars($ticket['date_updated']) . "</td>";
                        echo '<td>
                            <div class="action-dropdown">
                                <div class="action-toggle" onclick="toggleActionDropdown(event, this)">
                                    <i class="fas fa-ellipsis-h"></i>
                                </div>
                                <div class="dropdown-menu">
                                    <button class="dropdown-item" onclick="fetchWorkOrderDetails(' . htmlspecialchars($ticket['id']) . ')">
                                        <i class="fas fa-eye"></i> View Details
                                    </button>';

$currentUserEmpcode = trim($_SESSION['username']);
$ticketStatus = trim(strtolower($ticket['status']));
$ticketRequestor = trim($ticket['requestor']);

                        if ($ticketStatus === 'completed' && $currentUserEmpcode === $ticket['requestor_emp']) {
                            $surveySubmitted = hasSubmittedSurvey($conn, $ticket['id'], $currentUserEmpcode);
                            if (!$surveySubmitted) {
                                echo '<button class="dropdown-item" onclick="openSurveyForm(' . (int)$ticket['id'] . ')" style="color: #be185d;">
                                        <i class="fas fa-poll" style="color: #ec4899;"></i> Take Survey
                                      </button>';
                            } else {
                                echo '<button class="dropdown-item" onclick="viewSurveyResults(' . (int)$ticket['id'] . ')">
                                        <i class="fas fa-check-double"></i> Survey Results
                                      </button>';
                            }
                        }

                        if ($ticketStatus === 'closed') {
                            echo '<button class="dropdown-item" onclick="viewSurveyResults(' . (int)$ticket['id'] . ')">
                                    <i class="fas fa-poll-h"></i> Survey Results
                                  </button>';
                        }
                        echo '      </div>
                            </div>
                        </td>';
echo '</tr>';
                    }
                } else {
                    $emptyMessage = "No tickets found.";
                    if (!empty($deptFilter)) {
                        $emptyMessage = "No Requests from " . htmlspecialchars($deptFilter) . " department";
                    }
                    echo "<tr><td colspan='11' style='text-align: center; padding: 40px; color: #64748b;'>
                            <i class='fas fa-inbox' style='font-size: 32px; margin-bottom: 10px; display: block;'></i>
                            $emptyMessage
                          </td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    <div class="pagination">
        <?php
        // Create an array to hold the filter parameters
        $filterParams = [
            'search' => $search,
            'date_created_from' => $dateFrom,
            'date_created_to' => $dateTo,
            'department' => $deptFilter,
            'status' => $statusFilter,
            'assignee' => $assigneeFilter,
            'completion_date' => $completionDate,
            'urgency_level' => $urgencyFilter
        ];

        // First Page link
        $firstQueryString = http_build_query(array_merge(['page' => 1], $filterParams));
        echo '<a href="?' . $firstQueryString . '" class="pagination-nav ' . ($page <= 1 ? 'disabled' : '') . '">First Page</a>';

        // Previous page button
        if ($page > 1) {
            $prevQueryString = http_build_query(array_merge(['page' => ($page - 1)], $filterParams));
            echo '<a href="?' . $prevQueryString . '" class="pagination-nav">&laquo; Previous</a>';
        } else {
            echo '<span class="pagination-nav disabled">&laquo; Previous</span>';
        }

        // Calculate pagination range
        $paginationRange = 1; // Show current page and 1 on each side => 3 pages total (if available)
        $startPage = max(1, $page - $paginationRange);
        $endPage = min($totalPages, $page + $paginationRange);

        // Adjust start/end to always show 3 pages if possible
        if ($endPage - $startPage < 2) {
            if ($startPage == 1) {
                $endPage = min($totalPages, 3);
            } else if ($endPage == $totalPages) {
                $startPage = max(1, $totalPages - 2);
            }
        }

        // Page numbers
        for ($i = $startPage; $i <= $endPage; $i++) {
            $queryString = http_build_query(array_merge(['page' => $i], $filterParams));
            echo '<a href="?' . $queryString . '" class="' . (($i == $page) ? 'active' : '') . '">' . $i . '</a>';
        }

        // Next page button
        if ($page < $totalPages) {
            $nextQueryString = http_build_query(array_merge(['page' => ($page + 1)], $filterParams));
            echo '<a href="?' . $nextQueryString . '" class="pagination-nav">Next &raquo;</a>';
        } else {
            echo '<span class="pagination-nav disabled">Next &raquo;</span>';
        }

        // Last Page link
        $lastQueryString = http_build_query(array_merge(['page' => $totalPages], $filterParams));
        echo '<a href="?' . $lastQueryString . '" class="pagination-nav ' . ($page >= $totalPages ? 'disabled' : '') . '">Last Page</a>';
        ?>
    </div>
    <p class="content-copyright">&copy; 2025 ITicketHub. All rights reserved. For urgent concerns contact us at local 8022.</p>
    <!-- Modals moved to bottom of body -->

    <script src="js/filter_format.js"></script>
    <script src="js/modal.js"></script>
    <script src="js/assign_pic.js"></script>
    <script src="js/update_resolution.js"></script>
    <script src="js/submit_ticket.js"></script>
    <script src="js/decline_ticket.js"></script>
    <script>
        var userRole = '<?php echo $userRole; ?>'; // Define userRole in JavaScript
        var user = '<?php echo $username; ?>'; // Define userRole in JavaScript
    </script>
    <script src="js/fetch_ticket_details.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
    // Info video hover play/pause
    const infoWrapper = document.querySelector('.info-video-wrapper');
    const infoVideo = document.querySelector('.info-video-player');
    if (infoWrapper && infoVideo) {
        infoWrapper.addEventListener('mouseenter', function() {
            infoVideo.currentTime = 0;
            infoVideo.play().catch(() => {});
        });
        infoWrapper.addEventListener('mouseleave', function() {
            infoVideo.pause();
            infoVideo.currentTime = 0;
        });
    }

    const dashboardCounts = document.querySelectorAll('.dashboard a');
    dashboardCounts.forEach(count => {
        count.addEventListener('click', function(event) {
            event.preventDefault();
            const url = this.getAttribute('href');
            window.location.href = url;
        });
    });

    // Close modal handlers
    // Attach listener
    document.getElementById('surveyForm').addEventListener('submit', submitSurvey);
});

// Survey Modal Functions
function openSurveyForm(ticketId) {
    document.getElementById('surveyTicketId').value = ticketId;
    document.getElementById('surveyModal').style.display = 'block';
    document.getElementById('surveyForm').reset();
}

function closeSurveyModal() {
    document.getElementById('surveyModal').style.display = 'none';
}

function viewSurveyResults(ticketId) {
    if (!ticketId || ticketId <= 0) {
        alert("Invalid ticket ID");
        return;
    }

    const modal = document.getElementById('surveyResultsModal');
    const content = document.getElementById('surveyResultsContent');
    content.innerHTML = '<div style="text-align: center; padding: 40px;"><i class="fas fa-circle-notch fa-spin" style="font-size: 30px; color: #ec4899;"></i><p style="margin-top: 15px; color: #64748b;">Fetching survey data...</p></div>';
    modal.style.display = 'block';

    fetch('api/get_survey.php?ticketId=' + ticketId)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (data.error) {
                content.innerHTML = `<div class="error-state" style="text-align: center; padding: 40px;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 40px; color: #ef4444; margin-bottom: 15px;"></i>
                    <p style="color: #1e293b; font-weight: 600;">${data.error}</p>
                </div>`;
                return;
            }
            
            let html = `
                <div class="survey-results-modern">
                    <div class="survey-header">
                        <div class="ticket-id-tag">TICKET #${ticketId}</div>
                        <h3>Customer Feedback Results</h3>
                        <p class="survey-date">Submitted on ${data.submitted_at ? new Date(data.submitted_at).toLocaleDateString() : 'N/A'}</p>
                    </div>

                    <div class="survey-questions-list">
            `;
            
            if (data.questions) {
                data.questions.forEach((q, index) => {
                    const ratingClass = q.answer.toLowerCase();
                    const icon = ratingClass === 'excellent' ? 'smile-beam' : (ratingClass === 'satisfied' ? 'meh' : 'frown');
                    
                    html += `
                        <div class="modern-question-card">
                            <div class="q-number">${index + 1}</div>
                            <div class="q-body">
                                <p class="q-text">${q.question}</p>
                                <div class="q-answer-row">
                                    <div class="q-rating-badge ${ratingClass}">
                                        <i class="fas fa-${icon}"></i> ${q.answer}
                                    </div>
                                    <div class="q-score-dots">
                                        ${Array(3).fill(0).map((_, i) => `<span class="dot ${i < q.score ? 'filled' : ''}"></span>`).join('')}
                                        <span class="score-num">${q.score}/3</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
            }
            
            html += `
                    </div>

                    <div class="survey-footer-sections">
                        <div class="modern-comments-box">
                            <h4><i class="fas fa-comment-dots"></i> Additional Comments</h4>
                            <div class="comments-text">
                                ${data.comments && data.comments.trim() !== '' ? `"${data.comments}"` : '<span class="no-comments">No feedback provided.</span>'}
                            </div>
                        </div>

                        <div class="modern-summary-card">
                            <div class="total-score-box">
                                <div class="score-circle">
                                    <span class="big-num">${data.totalScore || 0}</span>
                                    <span class="small-total">/12</span>
                                </div>
                                <p>Total Rating</p>
                            </div>
                            <div class="avg-box">
                                <div class="stars-row">
                                    ${Array(3).fill(0).map((_, i) => `<i class="fas fa-star ${i < (data.totalScore/4) ? 'active' : ''}"></i>`).join('')}
                                </div>
                                <p>Overall Experience</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            content.innerHTML = html;
        })
        .catch(error => {
            console.error('Error:', error);
            content.innerHTML = '<div style="text-align: center; padding: 40px; color: #ef4444;"><i class="fas fa-wifi-slash"></i><p>Connection Error. Try again.</p></div>';
        });
}

function closeSurveyResultsModal() {
    document.getElementById('surveyResultsModal').style.display = 'none';
}

function submitSurvey(e) {
    if (e && e.preventDefault) e.preventDefault();

    const form = document.getElementById('surveyForm');
    const formData = new FormData(form);

    // Create a custom notification element
    const notification = document.createElement('div');
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.padding = '15px 20px';
    notification.style.borderRadius = '5px';
    notification.style.color = '#fff';
    notification.style.zIndex = '10001';
    notification.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
    notification.style.display = 'none'; // Initially hidden
    document.body.appendChild(notification);

    fetch('api/submit_survey.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            notification.textContent = 'Thank you for your feedback!';
            notification.style.backgroundColor = '#4CAF50';
            notification.style.display = 'block';

            // ✅ Update ticket status after saving survey
            const ticketId = formData.get('ticket_id');
            fetch('api/update_ticket_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `ticket_id=${ticketId}&status=Closed`
            }).then(() => {
                setTimeout(() => {
                    notification.style.display = 'none';
                    closeSurveyModal();
                    window.location.reload();
                }, 1000);
            });
        } else {
            notification.textContent = 'Error: ' + data.message;
            notification.style.backgroundColor = '#f44336';
            notification.style.display = 'block';
            setTimeout(() => notification.style.display = 'none', 3000);
        }
    })
    .catch(err => {
        console.error('Error:', err);
        notification.textContent = 'Error submitting survey. Please try again.';
        notification.style.backgroundColor = '#f44336';
        notification.style.display = 'block';
        setTimeout(() => notification.style.display = 'none', 3000);
    });
}

function toggleActionDropdown(event, button) {
    event.stopPropagation();
    const dropdown = button.closest('.action-dropdown');
    
    // Close all other dropdowns first
    document.querySelectorAll('.action-dropdown.active').forEach(active => {
        if (active !== dropdown) active.classList.remove('active');
    });

    dropdown.classList.toggle('active');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.action-dropdown')) {
        document.querySelectorAll('.action-dropdown.active').forEach(dropdown => {
            dropdown.classList.remove('active');
        });
    }
});
</script>
            </main> <!-- End content-area -->

            <!-- RIGHT: User Panel (Like "My Order" in Foodly) -->
            <aside class="right-panel" id="rightPanel">
                <!-- Hide Panel Button -->
                <!-- <button class="right-panel-hide-btn" id="rightPanelToggle" onclick="toggleRightPanel()" title="Hide sidebar">
                    <i class="fas fa-chevron-right" id="toggleIcon"></i>
                    <span>Hide</span>
                </button> -->
                <div class="panel-section user-card">
                    <div class="user-avatar">
                        <?php 
                        $avatarUsername = $_SESSION['username'] ?? '';
                        echo getEmployeePhotoImg($avatarUsername, 'avatar-img'); 
                        ?>
                    </div>
                    <h3><?php echo htmlspecialchars(($_SESSION['firstname'] ?? '') . ' ' . ($_SESSION['lastname'] ?? '')); ?></h3>
                    <span class="user-role-badge"><?php echo htmlspecialchars($_SESSION['department'] ?? ''); ?></span>
                </div>

                <div class="panel-section quick-links">
                    <h4>Quick Actions</h4>
                    <?php if (UserService::hasRole($userRole, 'it_admin') || UserService::hasRole($userRole, 'it_pic')): ?>
                    <a href="dashboard.php" class="quick-link"><i class="fas fa-chart-pie"></i> Analytics Dashboard</a>
                    <a href="history_logs.php" class="quick-link"><i class="fas fa-history"></i> History Logs</a>
                    <?php endif; ?>
                    <a href="#" class="quick-link" onclick="document.getElementById('newTicketModal').style.display='block'; return false;">
                        <i class="fas fa-plus-circle"></i> Submit New Ticket
                    </a>
                </div>

                <div class="panel-section">
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>

                <div class="panel-footer-logo">
                    <img src="img/ITBlack.png" alt="IT Logo">
                </div>
            </aside>

            <script>
                function toggleRightPanel() {
                    const toggleBtn = document.getElementById('rightPanelToggle');
                    const toggleIcon = document.getElementById('toggleIcon');
                    const navToggleBtn = document.getElementById('navPanelToggle');
                    const mainLayout = document.querySelector('.main-layout');

                    const isHidden = mainLayout.classList.toggle('right-panel-hidden');

                    if (isHidden) {
                        toggleIcon.classList.remove('fa-chevron-right');
                        toggleIcon.classList.add('fa-chevron-left');
                        toggleBtn.title = 'Show sidebar';
                        if (navToggleBtn) navToggleBtn.classList.add('panel-hidden');
                    } else {
                        toggleIcon.classList.remove('fa-chevron-left');
                        toggleIcon.classList.add('fa-chevron-right');
                        toggleBtn.title = 'Hide sidebar';
                        if (navToggleBtn) navToggleBtn.classList.remove('panel-hidden');
                    }

                    localStorage.setItem('rightPanelHidden', isHidden ? '1' : '0');
                }

                // Restore panel state on page load
                document.addEventListener('DOMContentLoaded', function () {
                    const savedState = localStorage.getItem('rightPanelHidden');
                    if (savedState === '1') {
                        const mainLayout = document.querySelector('.main-layout');
                        const toggleIcon = document.getElementById('toggleIcon');
                        const toggleBtn = document.getElementById('rightPanelToggle');
                        const navToggleBtn = document.getElementById('navPanelToggle');

                        mainLayout.classList.add('right-panel-hidden');
                        toggleIcon.classList.remove('fa-chevron-right');
                        toggleIcon.classList.add('fa-chevron-left');
                        toggleBtn.title = 'Show sidebar';
                        if (navToggleBtn) navToggleBtn.classList.add('panel-hidden');
                    }
                });
            </script>

        </div> <!-- End main-layout -->
        

        
    </div> <!-- End app-container -->
    <?php outputPhotoHelperScript(); ?>

    <!-- Modals moved to bottom of body -->
    <div id="newTicketModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Create New Ticket</h2>
            <form id="newTicketForm" action="api/submit_ticket.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="department">Department</label>
                    <input type="text" id="department" name="department"
                        value="<?php echo htmlspecialchars($department); ?>" required readonly>
                </div>
                <div class="form-group">
                    <label for="categories">Categories</label>
                    <select id="categories" name="category" required>
                        <option value="">Select Category</option>
                        <?php
                        foreach ($categories as $category) {
                            echo '<option value="' . htmlspecialchars($category['category_name']) . '">' . htmlspecialchars($category['category_name']) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="subject">Subject</label>
                    <textarea id="subject" name="subject" rows="2" required></textarea>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label for="attachments">Attach Files</label>
                    <input type="file" id="attachments" name="attachments[]" multiple>
                </div>
                <div class="form-group button-group">
                    <button type="submit" class="submit-button">Submit</button>
                </div>
            </form>
        </div>
    </div>

    <div id="ticketDetailsModal" class="ticket-modal">
        <div class="ticket-modal-content">
            <span class="ticket-close-button">&times;</span>
            <h2 class="ticket-details-header">Ticket Details <span class="ticket-id-badge" id="ticketIdBadge"></span>
            </h2>
            <div class="ticket-details-container">
                <div class="ticket-info-section">
                    <div class="ticket-meta">
                        <div class="ticket-meta-item">
                            <div class="meta-label">Status:</div>
                            <div class="meta-value status-badgetwo" id="ticketStatus"></div>
                        </div>
                        <div class="ticket-meta-item">
                            <div class="meta-label">Date Created:</div>
                            <div class="meta-value" id="ticketDateCreated"></div>
                        </div>
                        <div class="ticket-meta-item">
                            <div class="meta-label">Ticket #</div>
                            <div class="meta-value" id="ticketId"></div>
                        </div>
                    </div>

                    <div class="ticket-content">
                        <h3 class="section-title">Ticket Information</h3>
                        <div class="info-grid">
                            <div class="info-row">
                                <div class="info-label">Category:</div>
                                <div class="info-value" id="ticketCategory"></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Requestor:</div>
                                <div class="info-value" id="ticketRequestor"></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Department:</div>
                                <div class="info-value" id="ticketDepartment"></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Assigned to:</div>
                                <div class="info-value" id="assignedTo"></div>
                            </div>
                        </div>

                        <div class="ticket-subject-section">
                            <h3 class="section-title">Subject:</h3>
                            <div class="subject-content" id="ticketSubject"></div>
                        </div>

                        <div class="ticket-description-section">
                            <h3 class="section-title">Additional Information:</h3>
                            <div class="description-content" id="ticketDescription"></div>
                        </div>
                    </div>
                </div>

                <div class="ticket-attachments-section">
                    <h3 class="section-title">Attached Files:</h3>
                    <div id="ticketImages" class="image-preview-container"></div>
                </div>
            </div>
            <div id="assignPICSection" style="display: none;">
                <h3>Assign PIC:</h3>
                <form id="assignPICForm">
                    <div class="assignees-container">
                        <?php
                        try {
                            // Fetch assignees from the database
                            // Try multiple join conditions to match empcode
                            $sql = "SELECT 
                                    TRIM(ir.empcode) as empcode,
                                    ml.biometricsid,
                                    COALESCE(
                                        CASE 
                                            WHEN ml.lastname IS NOT NULL OR ml.firstname IS NOT NULL 
                                            THEN TRIM(COALESCE(ml.lastname, '') || ', ' || COALESCE(ml.firstname, '') || ' ' || COALESCE(ml.middlename, ''))
                                            ELSE NULL 
                                        END,
                                        ojt.full_name
                                    ) as fullname,
                                    ml.department
                                FROM it_ticket_roles ir 
                                LEFT JOIN lrn_master_list ml ON (ml.biometricsid = ir.empcode OR ml.employeeid = ir.empcode)
                                LEFT JOIN app_ojt_employees ojt ON ir.empcode = ojt.employee_id
                                WHERE ir.ticket_role LIKE '%it_pic%'
                                AND ir.isactive = 1
                                ORDER BY 
                                    CASE 
                                        WHEN ml.lastname IS NOT NULL THEN ml.lastname 
                                        ELSE ojt.full_name 
                                    END";
                            $stmt = $conn->prepare($sql);
                            $stmt->execute();

                            // Fetch all results as an associative array
                            $assignees = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            if ($assignees && count($assignees) > 0) {
                                foreach ($assignees as $assignee) {
                                    $empcode = htmlspecialchars($assignee['empcode']);
                                    
                                    // Use BiometricsID if available (preferred for storage/filtering consistency)
                                    $valueToStore = !empty($assignee['biometricsid']) ? trim($assignee['biometricsid']) : $empcode;
                                    
                                    // Use the fullname from the query (already constructed with COALESCE)
                                    $fullname = trim($assignee['fullname'] ?? '');
                                    
                                    // Fallback to empcode if fullname is still empty
                                    if (empty($fullname)) {
                                        $fullname = $empcode;
                                    }
                                    
                                    $fullname = htmlspecialchars($fullname);
                                    $valueToStore = htmlspecialchars($valueToStore);
                                    
                                    echo "<label class='assignee-label'><input type='checkbox' name='assignees[]' value='$valueToStore'> <span>$fullname</span></label>";
                                }
                            } else {
                                echo "<p style='color: #9d174d;'>No IT PIC assignees found.</p>";
                            }
                        } catch (PDOException $e) {
                            echo "<p style='color: #dc2626;'>Error fetching assignees: " . htmlspecialchars($e->getMessage()) . "</p>";
                        }
                        ?>
                    </div>
                    <div class="form-group inline-group">
                        <label for="priorityLevel">Priority Level:</label>
                        <select id="priorityLevel" name="priorityLevel">
                            <option value="">Select Priority</option>
                            <option value="Low">Low</option>
                            <option value="Mid">Medium</option>
                            <option value="High">High</option>
                        </select>
                        <button type="submit" class="submit-button assign-btn">Assign</button>
                        <button type="button" class="submit-button decline-btn" onclick="openDeclineModal()">
                            Decline
                        </button>
                    </div>
                </form>
            </div>

            <!-- Activity Progress Logs Section (always between Assign PIC and Update Resolution) -->
            <div id="activityLogsSection" class="compact-info-section" style="display: none; margin-top: 15px;">
                <div style="display: flex; justify-content: space-between; align-items: center; cursor: pointer; padding: 8px 0; border-bottom: 1px solid #e2e8f0;" onclick="toggleUpdateLogs()">
                    <h3 class="section-title" style="margin: 0; font-size: 14px; color: #334155;"><i class="fas fa-history" style="color: #64748b; margin-right: 5px;"></i>Update Logs <span id="logsCount" style="font-weight: 400; color: #94a3b8; font-size: 12px;"></span></h3>
                    <span id="toggleLogsBtn" style="color: #e91e8a; font-size: 12px; cursor: pointer; user-select: none;">
                        <i class="fas fa-chevron-down" id="toggleLogsIcon" style="transition: transform 0.2s;"></i> <span id="toggleLogsText">View</span>
                    </span>
                </div>
                <div id="activityLogsList" class="activity-logs-container" style="display: none; padding-top: 10px;">
                </div>
            </div>

            <div id="closedPICSection" class="compact-info-section" style="display: none;">
                <div class="ticket-info">
                    <h3 class="section-title">IT PIC Remarks:</h3>
                    <div class="remarks-box">
                        <span id="ticketRemarks" class="remarks-display"></span>
                    </div>
                </div>
                
                <div class="ticket-info" id="rejectRemarksSection" style="margin-top: 10px;">
                    <h3 class="section-title">Decline Remarks:</h3>
                    <div class="remarks-box reject-box">
                        <span id="ticketRejectRemarks" class="reject-remarks-display"></span>
                    </div>
                </div>
            </div>

            <div id="resoPICSection" class="compact-info-section" style="display: none;">
                <h3 class="section-title">Update Resolution:</h3>
                <form id="resoPICForm">
                    <div class="form-group">
                        <textarea id="remarks" name="remarks" rows="2" placeholder="Enter remarks here..." class="remarks-textarea" required></textarea>
                    </div>
                    <div class="form-group inline-group">
                        <label for="resoStatus">Status:</label>
                        <select id="resoStatus" name="status" required>
                            <option value="">Select Status</option>
                            <option value="Pending">Pending</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Completed">Completed</option>
                        </select>

                        <label for="attachments">Attach Files:</label>
                        <input type="file" id="resoAttachments" name="resoAttachments[]" multiple>

                        <button type="submit" class="submit-button">Update</button>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <!-- Full Image Modal -->
    <div id="fullImageModal" class="full-image-modal">
        <span class="full-image-close-button">&times;</span>
        <img id="fullImage" src="" alt="Full Image">
    </div>

    <!-- Decline Ticket Modal -->
    <div id="declineTicketModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeDeclineModal()">&times;</span>
            <h2>Decline Ticket</h2>
            <form id="declineTicketForm">
                <input type="hidden" id="declineTicketId" name="ticket_id">
                <div class="form-group">
                    <label for="declineRemarks">Remarks:</label>
                    <textarea id="declineRemarks" name="reject_remarks" rows="5" maxlength="255" placeholder="Please provide reasons for declining this ticket..." 
                        style="width: 100%; padding: 5px; border-radius: 5px; border: 1px solid #dc3545; background-color: white; color: #2b2d42; margin-left: -6.5px;" 
                        required></textarea>
                </div>
                <div class="button-group">
                    <button type="submit" class="submit-button" style="background-color: #dc3545;">Confirm</button>
                    <button type="button" class="submit-button" onclick="closeDeclineModal()" style="background-color: #6c757d;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Survey Modal -->
<div id="surveyModal" class="modal">
    <div class="modal-content">
        <span class="close-button" onclick="closeSurveyModal()">&times;</span>
        <h2>Survey</h2>
        <form id="surveyForm">
            <input type="hidden" name="ticket_id" id="surveyTicketId">

            <?php 
            if (!empty($surveyQuestions)) {
                foreach ($surveyQuestions as $index => $q) {
                    $qNum = $index + 1;
                    $qFieldName = "q" . $qNum;
                    ?>
                    <!-- Question <?php echo $qNum; ?> -->
                    <div class="question-group">
                        <p><?php echo $qNum; ?>. <?php echo htmlspecialchars($q['question_text']); ?></p>
                        <div class="options-grid">
                            <label><input type="radio" name="<?php echo $qFieldName; ?>" value="Excellent" data-score="3" required> <span>Excellent</span></label>
                            <label><input type="radio" name="<?php echo $qFieldName; ?>" value="Satisfied" data-score="2"> <span>Satisfied</span></label>
                            <label><input type="radio" name="<?php echo $qFieldName; ?>" value="Fail" data-score="1"> <span>Poor</span></label>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo "<p style='color: #ef4444; padding: 20px; text-align: center;'>Survey questions not found in database.</p>";
            }
            ?>

            <!-- Optional comments -->
            <div class="comments-section">
                <p>Comments:</p>
                <textarea name="comments" rows="3" placeholder="Optional feedback..."></textarea>
            </div>

            <button type="submit">Submit Feedback</button>
        </form>
    </div>
</div>


<!-- Survey Results Modal -->
<div id="surveyResultsModal" class="modal">
    <div class="modal-content">
        <span class="close-button" onclick="closeSurveyResultsModal()">&times;</span>
        <h2>Survey Results</h2>
        <div id="surveyResultsContent">
            <!-- Results will be loaded here -->
        </div>
    </div>
</div>

<script src="js/modal.js"></script>
</body>

</html>

<?php
$conn = null;
?>