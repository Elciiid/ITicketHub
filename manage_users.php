<?php
include_once __DIR__ . '/controllers/it_tickets_dashboard_controller.php';
include_once __DIR__ . '/api/photo_helper.php';
include_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/services/UserService.php';

// Pagination
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 10;
$userService = new UserService($conn);
$totalUsers = $userService->countManagedUsers();
$totalPages = ceil($totalUsers / $limit);
$users = $userService->getManagedUsers($page, $limit);

renderPageStart('ITicketHub - Manage Users');
renderTopNav('users', $userRole);
?>

<!-- MAIN LAYOUT: Left Content + Right Panel -->
<div class="main-layout">

    <!-- LEFT: Main Content Area -->
    <main class="content-area" style="overflow-x: hidden; width: 100%;">
        <div class="page-header">
            <h1><i class="fas fa-users-cog"></i> Manage Users</h1>
        </div>

        <!-- Summary Cards Row -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon open"><i class="fas fa-folder-open"></i></div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo $openCount; ?></span>
                    <span class="stat-label">Open</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon assigned"><i class="fas fa-user-check"></i></div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo $assignedCount; ?></span>
                    <span class="stat-label">Assigned</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon urgent"><i class="fas fa-fire"></i></div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo $urgentCount; ?></span>
                    <span class="stat-label">Urgent</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon closed"><i class="fas fa-lock"></i></div>
                <div class="stat-info">
                    <span class="stat-value"><?php echo $closedCount; ?></span>
                    <span class="stat-label">Closed</span>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="users-table-container">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Full Name</th>
                        <th>Department</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <div class="avatar-wrapper">
                                        <?php echo getEmployeePhotoImg($user['username'], 'user-list-avatar'); ?>
                                    </div>
                                    <span class="user-id "><?php echo htmlspecialchars($user['username']); ?></span>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($user['fullname']); ?></td>
                            <td><?php echo htmlspecialchars($user['department']); ?></td>
                            <td>
                                <div style="display: flex; gap: 4px; flex-wrap: wrap; justify-content: center;">
                                    <?php
                                    $userRoles = explode(',', $user['ticket_role'] ?? '');
                                    foreach ($userRoles as $r):
                                        $r = trim($r);
                                        if (!$r)
                                            continue;
                                        ?>
                                        <span class="role-badge role-<?php echo htmlspecialchars($r); ?>">
                                            <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $r))); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo htmlspecialchars($user['status']); ?>">
                                    <?php echo htmlspecialchars(ucfirst($user['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <button class="edit-btn"
                                    onclick="openEditModal(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination-container" style="margin-top: 20px; display: flex; justify-content: flex-end;">
                <div class="pagination" style="display: flex; gap: 8px;">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="page-link"
                            style="padding: 8px 16px; border-radius: 8px; background: white; color: #64748b; text-decoration: none; border: 1px solid #e2e8f0; font-size: 14px; font-weight: 500; transition: all 0.2s;"><i
                                class="fas fa-chevron-left"></i> Prev</a>
                    <?php endif; ?>

                    <?php
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);

                    if ($start > 1) {
                        echo '<span style="padding: 8px; color: #94a3b8;">...</span>';
                    }

                    for ($i = $start; $i <= $end; $i++):
                        ?>
                        <a href="?page=<?php echo $i; ?>" class="page-link" style="padding: 8px 16px; border-radius: 8px; 
                              <?php echo ($i == $page) ? 'background: #ec4899; color: white; border-color: #ec4899; box-shadow: 0 4px 6px rgba(236, 72, 153, 0.2);' : 'background: white; color: #64748b; border: 1px solid #e2e8f0;'; ?> 
                              text-decoration: none; font-size: 14px; font-weight: 500; transition: all 0.2s;">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor;

                    if ($end < $totalPages) {
                        echo '<span style="padding: 8px; color: #94a3b8;">...</span>';
                    }
                    ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="page-link"
                            style="padding: 8px 16px; border-radius: 8px; background: white; color: #64748b; text-decoration: none; border: 1px solid #e2e8f0; font-size: 14px; font-weight: 500; transition: all 0.2s;">Next
                            <i class="fas fa-chevron-right"></i></a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <style>
            .users-table-container {
                background: white;
                border-radius: 16px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
                overflow-x: auto;
                margin-top: 20px;
                border: 1px solid #e2e8f0;
            }

            .modern-table {
                width: 100%;
                min-width: 800px;
                /* Ensure table has minimum width to trigger scroll on small screens/zoom */
                border-collapse: collapse;
            }

            .modern-table thead {
                background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            }

            .modern-table th {
                padding: 16px 20px;
                text-align: center !important;
                font-weight: 600;
                color: #64748b;
                font-size: 12px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                border-bottom: 2px solid #e5e7eb;
                white-space: nowrap;
            }

            .modern-table td {
                padding: 16px 20px;
                border-bottom: 1px solid #f1f5f9;
                color: #1e293b;
                font-size: 14px;
            }

            .modern-table tbody tr:hover {
                background: #fdf2f8;
            }

            .modern-table tbody tr:last-child td {
                border-bottom: none;
            }

            .user-cell {
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .avatar-wrapper {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                overflow: hidden;
                flex-shrink: 0;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
                border: 2px solid #fff;
            }

            .user-list-avatar {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .user-id {
                font-weight: 600;
                color: #1e293b;
            }

            .role-badge {
                display: inline-block;
                padding: 5px 12px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 600;
                text-transform: uppercase;
            }

            .role-badge.role-it_admin {
                background: none;
                color: #db2777;
                padding: 0;
            }

            .role-badge.role-it_pic {
                background: none;
                color: #7c3aed;
                padding: 0;
            }

            .role-badge.role-user {
                background: none;
                color: #475569;
                padding: 0;
            }

            .status-badge {
                display: inline-block;
                padding: 5px 12px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 600;
            }

            .status-badge.status-active {
                background: #d1fae5;
                color: #065f46;
            }

            .status-badge.status-inactive {
                background: #fee2e2;
                color: #991b1b;
            }

            .edit-btn {
                width: 36px;
                height: 36px;
                border: none;
                border-radius: 10px;
                background: linear-gradient(135deg, #ec4899, #db2777);
                color: white;
                cursor: pointer;
                transition: all 0.3s ease;
            }

            .edit-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(236, 72, 153, 0.3);
            }

            /* Fix Modal for Zoomed Container */
            .modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 10000;
                display: none;
                justify-content: center;
                align-items: center;
                backdrop-filter: blur(5px);
            }

            .modal-content {
                background: white;
                padding: 30px;
                border-radius: 16px;
                width: 90%;
                max-width: 500px;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
                animation: modalFadeIn 0.3s ease;
            }

            @keyframes modalFadeIn {
                from {
                    opacity: 0;
                    transform: translateY(-20px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            /* Custom Checkbox Design */
            .roles-checkbox-group {
                display: flex;
                gap: 20px;
                margin-top: 8px;
            }

            .custom-checkbox {
                position: relative;
                padding-left: 30px;
                cursor: pointer;
                font-size: 15px;
                font-weight: 500;
                color: #334155;
                user-select: none;
                display: flex;
                align-items: center;
                height: 24px;
            }

            .custom-checkbox input {
                position: absolute;
                opacity: 0;
                cursor: pointer;
                height: 0;
                width: 0;
            }

            .checkmark {
                position: absolute;
                top: 0;
                left: 0;
                height: 22px;
                width: 22px;
                background-color: #fff;
                border: 2px solid #cbd5e1;
                border-radius: 6px;
                transition: all 0.2s ease;
            }

            .custom-checkbox:hover .checkmark {
                border-color: #ec4899;
                background-color: #fdf2f8;
            }

            .custom-checkbox input:checked~.checkmark {
                background-color: #ec4899;
                border-color: #ec4899;
            }

            /* Checkmark Icon */
            .checkmark::after {
                content: "";
                position: absolute;
                display: none;
                left: 7px;
                top: 3px;
                width: 6px;
                height: 12px;
                border: solid white;
                border-width: 0 2px 2px 0;
                transform: rotate(45deg);
            }

            .custom-checkbox input:checked~.checkmark::after {
                display: block;
            }
        </style>
    </main> <!-- End content-area -->

    <?php renderRightPanel($userRole, 'users'); ?>

</div> <!-- End main-layout -->

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close-button" onclick="closeEditModal()">&times;</span>
        <h2>Edit User</h2>
        <form id="editForm">
            <div class="form-group">
                <label for="editUsername">Biometrics ID</label>
                <input type="text" id="editUsername" name="editUsername" readonly>
            </div>

            <div class="form-group">
                <label for="editFullname">Full Name</label>
                <input type="text" id="editFullname" name="editFullname" readonly>
            </div>

            <div class="form-group">
                <label for="editDepartment">Department</label>
                <input type="text" id="editDepartment" name="editDepartment" readonly>
            </div>

            <div class="form-group">
                <label>Roles</label>
                <div class="roles-checkbox-group">
                    <label class="custom-checkbox">
                        <input type="checkbox" name="editRoles" value="it_admin" id="role_it_admin">
                        <span class="checkmark"></span>
                        IT Admin
                    </label>
                    <label class="custom-checkbox">
                        <input type="checkbox" name="editRoles" value="it_pic" id="role_it_pic">
                        <span class="checkmark"></span>
                        IT PIC
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label for="editStatus">Status</label>
                <select id="editStatus" name="editStatus">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div class="form-group button-group">
                <button type="button" class="submit-button" onclick="saveChanges()">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Add User Modal -->
<div id="addUserModal" class="modal">
    <div class="modal-content">
        <span class="close-button" onclick="closeAddUserModal()">&times;</span>
        <h2>Add User</h2>
        <form id="addUserForm">
            <div class="form-group">
                <label for="addUsername">Employee ID</label>
                <input type="text" id="addUsername" name="addUsername" placeholder="Enter Employee ID" required>
            </div>

            <div class="form-group">
                <label for="addFullname">Full Name</label>
                <input type="text" id="addFullname" name="addFullname" placeholder="Auto-fetched name" readonly
                    style="background-color: #f1f5f9; cursor: not-allowed;">
            </div>

            <p style="font-size: 12px; color: #64748b; margin-bottom: 15px;">
                <i class="fas fa-info-circle"></i> Full Name and Department will be automatically fetched from the
                master list.
            </p>

            <div class="form-group">
                <label>Roles</label>
                <div class="roles-checkbox-group">
                    <label class="custom-checkbox">
                        <input type="checkbox" name="addRoles" value="it_admin">
                        <span class="checkmark"></span>
                        IT Admin
                    </label>
                    <label class="custom-checkbox">
                        <input type="checkbox" name="addRoles" value="it_pic">
                        <span class="checkmark"></span>
                        IT PIC
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label for="addStatus">Status</label>
                <select id="addStatus" name="addStatus">
                    <option value="active" selected>Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div class="form-group button-group">
                <button type="button" class="submit-button" onclick="saveNewUser()">Add User</button>
            </div>
        </form>
    </div>
</div>

<?php renderFooter(); ?>

<script>
    var userRole = '<?php echo $userRole; ?>';
    var user = '<?php echo $username; ?>';
</script>
<script>
</script>
<script>
    // Existing Edit Modal Functions ... (Assume they are here)
    function openEditModal(user) {
        document.getElementById('editUsername').value = user.username;
        document.getElementById('editFullname').value = user.fullname;
        document.getElementById('editDepartment').value = user.department;

        // Reset and set checkboxes
        const roles = (user.ticket_role || "").split(',').map(r => r.trim());
        document.getElementById('role_it_admin').checked = roles.includes('it_admin');
        document.getElementById('role_it_pic').checked = roles.includes('it_pic');

        document.getElementById('editStatus').value = user.status;
        document.getElementById('editModal').style.display = 'block';
    }

    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    function saveChanges() {
        const username = document.getElementById('editUsername').value;
        const status = document.getElementById('editStatus').value;

        // Collect checked roles
        const roleCheckboxes = document.querySelectorAll('input[name="editRoles"]:checked');
        const roles = Array.from(roleCheckboxes).map(cb => cb.value);

        if (roles.length === 0) {
            alert("Please select at least one role.");
            return;
        }

        updateUserRequest(username, roles, status);
    }

    // Add User Functions
    function openAddUserModal() {
        document.getElementById('addUserModal').style.display = 'block';
    }

    function closeAddUserModal() {
        document.getElementById('addUserModal').style.display = 'none';
        // Clean URL if it has ?action=add
        const url = new URL(window.location);
        if (url.searchParams.get('action') === 'add') {
            url.searchParams.delete('action');
            window.history.replaceState({}, '', url);
        }
    }

    function saveNewUser() {
        const username = document.getElementById('addUsername').value;
        const status = document.getElementById('addStatus').value;

        // Collect checked roles
        const roleCheckboxes = document.querySelectorAll('input[name="addRoles"]:checked');
        const roles = Array.from(roleCheckboxes).map(cb => cb.value);

        if (!username) {
            alert("Biometrics ID is required.");
            return;
        }

        if (roles.length === 0) {
            alert("Please select at least one role.");
            return;
        }

        updateUserRequest(username, roles, status);
    }

    function updateUserRequest(username, role, status) {
        fetch('api/update_user.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                username: username,
                role: role,
                status: status
            }),
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('User saved successfully!');
                    location.href = 'manage_users.php'; // Reload to clear params and refresh list
                } else {
                    alert('Failed: ' + (data.message || 'Unknown error'));
                }
            })
            .catch((error) => {
                console.error('Error:', error);
                alert('An error occurred.');
            });
    }

    // Close modal when clicking outside
    window.onclick = function (event) {
        var editModal = document.getElementById('editModal');
        var addModal = document.getElementById('addUserModal');
        if (event.target == editModal) {
            editModal.style.display = 'none';
        }
        if (event.target == addModal) {
            closeAddUserModal();
        }
    }

    // Auto-open modal if query param action=add exists
    document.addEventListener('DOMContentLoaded', function () {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('action') === 'add') {
            openAddUserModal();
        }

        // Auto-fetch user details on input (as you type)
        const usernameInput = document.getElementById('addUsername');
        let timeout = null;

        if (usernameInput) {
            usernameInput.addEventListener('input', function () {
                const username = this.value;
                // Clear previous timeout
                clearTimeout(timeout);

                // Wait 500ms after typing stops
                timeout = setTimeout(() => {
                    if (username.length >= 3) { // Only fetch if length is meaningful
                        fetchUserDetails(username);
                    } else {
                        document.getElementById('addFullname').value = "";
                    }
                }, 500);
            });
        }
    });

    function fetchUserDetails(username) {
        const fullnameInput = document.getElementById('addFullname');

        // Show loading state (optional, or just clear)
        fullnameInput.value = "Fetching...";

        fetch(`api/check_user_details.php?username=${encodeURIComponent(username)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    fullnameInput.value = data.data.fullname;
                } else {
                    fullnameInput.value = ""; // Clear if not found
                    // optional: alert or show error nearby
                }
            })
            .catch(error => {
                console.error('Error fetching details:', error);
                fullnameInput.value = "";
            });
    }
</script>

<?php
outputPhotoHelperScript();
renderPageEnd();
$conn = null;
?>