<?php
include_once __DIR__ . '/controllers/it_tickets_dashboard_controller.php';
include_once __DIR__ . '/api/photo_helper.php';
include_once __DIR__ . '/includes/layout.php';

if (!UserService::hasRole($userRole ?? '', 'it_admin')) {
    header("Location: index.php");
    exit();
}
require_once __DIR__ . '/services/CategoryHelper.php';

// Initialize Category Helper
$categoryHelper = new CategoryHelper($conn);
// Pagination
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 10;
$totalCategories = $categoryHelper->countCategories();
$totalPages = ceil($totalCategories / $limit);

// Fetch Categories
$categories = $categoryHelper->getAllCategories($page, $limit);

renderPageStart('ITicketHub - Manage Categories');
renderTopNav('categories', $userRole);
?>

<!-- MAIN LAYOUT: Left Content + Right Panel -->
<div class="main-layout">

    <!-- LEFT: Main Content Area -->
    <main class="content-area" style="overflow-x: hidden; width: 100%;">
        <div class="page-header">
            <h1><i class="fas fa-layer-group"></i> Manage Categories</h1>
            <div class="header-actions">
                <button class="new-ticket-btn" onclick="openAddCategoryModal()">
                    <i class="fas fa-plus"></i> Add Category
                </button>
            </div>
        </div>

        <!-- Categories Table -->
        <div class="users-table-container">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th style="text-align: center;">ID</th>
                        <th style="text-align: center;">Category Name</th>
                        <th style="text-align: center; width: 150px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td style="text-align: center;">
                                    <?php echo htmlspecialchars($cat['id']); ?>
                                </td>
                                <td style="text-align: center; font-weight: 500; color: #334155;">
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </td>
                                <td style="text-align: center;">
                                    <button class="edit-btn"
                                        onclick="openEditCategoryModal(<?php echo htmlspecialchars(json_encode($cat)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" style="text-align: center; padding: 20px;">No categories found.</td>
                        </tr>
                    <?php endif; ?>
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

        <p class="content-copyright">&copy; 2025 ITicketHub. All rights reserved. For urgent concerns contact us at
            local 8022.</p>

        <style>
            /* Reuse styles from manage_users.php via CSS or inline for now to ensure consistency */
            .users-table-container {
                background: white;
                border-radius: 16px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
                overflow-x: auto;
                /* Enable horizontal scrolling */
                margin-top: 20px;
                border: 1px solid #e2e8f0;
            }

            .modern-table {
                width: 100%;
                min-width: 600px;
                /* Ensure table has minimum width to trigger scroll on small screens/zoom */
                border-collapse: collapse;
            }

            .modern-table thead {
                background: linear-gradient(135deg, #f8fafc, #f1f5f9);
                position: sticky;
                /* Keep header visible on vertical scroll if needed */
                top: 0;
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
                /* Prevent header wrap */
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

            .page-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
            }

            .page-header h1 {
                margin: 0;
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

            .modal[style*="display: block"] {
                display: flex !important;
            }

            .modal-content {
                background: white;
                padding: 30px;
                border-radius: 16px;
                width: 90%;
                max-width: 500px;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
                animation: modalFadeIn 0.3s ease;
                position: relative;
            }

            .modal-content h2 {
                color: #be185d;
                font-size: 24px;
                font-weight: 700;
                margin-top: 0;
                margin-bottom: 24px;
                padding-bottom: 12px;
                border-bottom: 2px solid #fce7f3;
            }

            .close-button {
                position: absolute;
                top: 15px;
                right: 20px;
                font-size: 24px;
                cursor: pointer;
                color: #64748b;
            }

            .form-group label {
                font-weight: 600;
                color: #475569;
                font-size: 14px;
                margin-bottom: 6px;
                display: block;
            }

            .form-group input,
            .form-group textarea {
                width: 100%;
                padding: 12px;
                border: 1px solid #cbd5e1;
                border-radius: 10px;
                font-family: inherit;
                font-size: 14px;
                color: #334155;
                box-sizing: border-box;
                transition: all 0.2s ease;
                margin-top: 0;
            }

            .form-group input:focus,
            .form-group textarea:focus {
                border-color: #ec4899;
                outline: none;
                box-shadow: 0 0 0 4px rgba(236, 72, 153, 0.1);
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
        </style>

    </main> <!-- End content-area -->

    <?php renderRightPanel($userRole, 'categories'); ?>

</div> <!-- End main-layout -->

<!-- Add Category Modal -->
<div id="addCategoryModal" class="modal">
    <div class="modal-content">
        <span class="close-button" onclick="closeAddCategoryModal()">&times;</span>
        <h2>Add new Category</h2>
        <form id="addCategoryForm">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label for="addCategoryName">Category Name</label>
                <input type="text" id="addCategoryName" name="category_name" required>
            </div>
            <div class="form-group button-group">
                <button type="submit" class="submit-button">Add Category</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Category Modal -->
<div id="editCategoryModal" class="modal">
    <div class="modal-content">
        <span class="close-button" onclick="closeEditCategoryModal()">&times;</span>
        <h2>Edit Category</h2>
        <form id="editCategoryForm">
            <input type="hidden" name="action" value="update">
            <input type="hidden" id="editCategoryId" name="id">
            <div class="form-group">
                <label for="editCategoryName">Category Name</label>
                <input type="text" id="editCategoryName" name="category_name" required>
            </div>
            <div class="form-group button-group">
                <button type="submit" class="submit-button">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Modal functions
    function openAddCategoryModal() {
        document.getElementById('addCategoryModal').style.display = 'block';
        document.getElementById('addCategoryForm').reset();
    }

    function closeAddCategoryModal() {
        document.getElementById('addCategoryModal').style.display = 'none';
    }

    function openEditCategoryModal(category) {
        document.getElementById('editCategoryId').value = category.id;
        document.getElementById('editCategoryName').value = category.category_name;
        document.getElementById('editCategoryModal').style.display = 'block';
    }

    function closeEditCategoryModal() {
        document.getElementById('editCategoryModal').style.display = 'none';
    }

    // Close on click outside
    window.onclick = function (event) {
        if (event.target == document.getElementById('addCategoryModal')) {
            closeAddCategoryModal();
        }
        if (event.target == document.getElementById('editCategoryModal')) {
            closeEditCategoryModal();
        }
    }

    // Submit Handlers
    document.getElementById('addCategoryForm').addEventListener('submit', function (e) {
        e.preventDefault();
        saveCategory(new FormData(this));
    });

    document.getElementById('editCategoryForm').addEventListener('submit', function (e) {
        e.preventDefault();
        saveCategory(new FormData(this));
    });

    function saveCategory(formData) {
        fetch('api/process_category.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Category saved successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred.');
            });
    }
</script>

<?php
renderFooter();
renderPageEnd();
?>