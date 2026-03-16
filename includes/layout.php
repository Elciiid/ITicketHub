<?php
// This file contains reusable layout components for the modern sidebar design
// Include this file and use the functions to render header, sidebar, and footer

// Include photo helper for user avatars
require_once __DIR__ . '/../api/photo_helper.php';

/**
 * Render the top navigation bar with logo and status pills
 * @param string $activePage - Current active page for highlighting (e.g., 'tickets', 'dashboard', 'history', 'users')
 * @param string $userRole - User's role for permission-based navigation
 */
function renderTopNav($activePage = 'tickets', $userRole = 'user')
{
    ?>
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

        <!-- Category Pills (Navigation) -->
        <nav class="nav-pills">
            <a href="index.php" class="pill <?php echo $activePage === 'tickets' ? 'active' : ''; ?>">
                <i class="fas fa-ticket-alt"></i> Tickets
            </a>
            <?php if (UserService::hasRole($userRole, 'it_admin') || UserService::hasRole($userRole, 'it_pic')): ?>
                <a href="dashboard.php" class="pill <?php echo $activePage === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie"></i> Dashboard
                </a>
                <a href="history_logs.php" class="pill <?php echo $activePage === 'history' ? 'active' : ''; ?>">
                    <i class="fas fa-history"></i> History
                </a>
            <?php endif; ?>
            <?php if (UserService::hasRole($userRole, 'it_admin')): ?>
                <a href="manage_users.php" class="pill <?php echo $activePage === 'users' ? 'active' : ''; ?>">
                    <i class="fas fa-users-cog"></i> Users
                </a>
            <?php endif; ?>
        </nav>

        <!-- Right Side: Actions -->
        <div class="nav-actions">
            <?php if (UserService::hasRole($userRole, 'it_admin')): ?>
                <a href="manage_survey_questions.php" class="icon-btn" title="Manage Survey Questions"><i
                        class="fas fa-poll"></i></a>
                <a href="manage_categories.php" class="icon-btn" title="Manage Categories"><i
                        class="fas fa-layer-group"></i></a>
                <a href="manage_users.php" class="icon-btn" title="Manage Users"><i class="fas fa-users-cog"></i></a>
            <?php endif; ?>
            <button class="icon-btn sidebar-toggle-btn" id="navPanelToggle" onclick="toggleRightPanel()"
                title="Toggle sidebar">
                <i class="fas fa-columns"></i>
            </button>
        </div>
    </header>
    <?php
}

/**
 * Render the right sidebar panel with user info and quick links
 * @param string $userRole - User's role for permission-based actions
 * @param string $activePage - Current active page
 */
function renderRightPanel($userRole = 'user', $activePage = 'tickets')
{
    ?>
    <!-- RIGHT: User Panel -->
    <aside class="right-panel" id="rightPanel">
        <!-- Hide Panel Button -->
        <!-- <button class="right-panel-hide-btn" id="rightPanelToggle" onclick="toggleRightPanel()" title="Hide sidebar">
            <i class="fas fa-chevron-right" id="toggleIcon"></i>
            <span>Hide</span>
        </button> -->
        <div class="panel-section user-card">
            <div class="user-avatar">
                <?php
                // Lookup the employee ID for photo display
                $photoUsername = $_SESSION['username'] ?? '';

                // Try to get BiometricsID or EmployeeID from lrn_master_list
                if (!empty($photoUsername) && isset($GLOBALS['conn'])) {
                    try {
                        $photoQuery = "SELECT TOP 1 BiometricsID, EmployeeID FROM lrn_master_list WHERE BiometricsID = ? OR EmployeeID = ?";
                        $photoStmt = $GLOBALS['conn']->prepare($photoQuery);
                        $photoStmt->execute([$photoUsername, $photoUsername]);
                        $photoRow = $photoStmt->fetch(PDO::FETCH_ASSOC);

                        if ($photoRow) {
                            // Prefer EmployeeID for photos
                            $photoUsername = !empty($photoRow['EmployeeID']) ? $photoRow['EmployeeID'] : $photoRow['BiometricsID'];
                        }
                    } catch (Exception $e) {
                        // If query fails, fall back to session username
                    }
                }


                echo getEmployeePhotoImg($photoUsername, 'avatar-img');
                // Debug: output what ID we're trying to load
                echo "<!-- Photo lookup using ID: " . htmlspecialchars($photoUsername) . " -->";
                ?>
            </div>
            <h3>
                <?php echo htmlspecialchars(($_SESSION['firstname'] ?? '') . ' ' . ($_SESSION['lastname'] ?? '')); ?>
            </h3>
            <span class="user-role-badge">
                <?php echo htmlspecialchars($_SESSION['department'] ?? ''); ?>
            </span>
        </div>

        <div class="panel-section quick-links">
            <h4>Quick Actions</h4>
            <?php if (UserService::hasRole($userRole, 'it_admin') || UserService::hasRole($userRole, 'it_pic')): ?>
                <a href="dashboard.php" class="quick-link <?php echo $activePage === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie"></i> Analytics Dashboard
                </a>
                <a href="history_logs.php" class="quick-link <?php echo $activePage === 'history' ? 'active' : ''; ?>">
                    <i class="fas fa-history"></i> History Logs
                </a>
            <?php endif; ?>
            <a href="index.php" class="quick-link <?php echo $activePage === 'tickets' ? 'active' : ''; ?>">
                <i class="fas fa-ticket-alt"></i> View Tickets
            </a>
        </div>

        <div class="panel-section">
            <?php if (UserService::hasRole($userRole, 'it_admin')): ?>
                <a href="manage_users.php?action=add" class="logout-btn"
                    style="background: linear-gradient(135deg, #3b82f6, #2563eb); margin-bottom: 10px;">
                    <i class="fas fa-user-plus"></i> Add User
                </a>
            <?php endif; ?>
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
            const panel = document.getElementById('rightPanel');
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

            // Persist state in localStorage
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
    <?php
}

/**
 * Render the footer
 */
function renderFooter()
{
    // Footer removed - copyright text moved under pagination, IT logo moved to right panel
}

/**
 * Render the start of the page layout (head, body opening, app container)
 * @param string $pageTitle - Title of the page
 */
function renderPageStart($pageTitle = 'ITicketHub')
{
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>
            <?php echo htmlspecialchars($pageTitle); ?>
        </title>
        <link rel="stylesheet" href="css/ticket_summary.css">
        <link rel="stylesheet" href="css/modern_sidebar.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    </head>

    <body>
        <!-- APP CONTAINER (The "Island") -->
        <div class="app-container">
            <?php
}

/**
 * Render the end of the page layout
 */
function renderPageEnd()
{
    ?>
        </div> <!-- End app-container -->
        <?php if (function_exists('outputPhotoHelperScript'))
            outputPhotoHelperScript(); ?>
    </body>

    </html>
    <?php
}
?>