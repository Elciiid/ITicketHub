<?php
include 'controllers/it_tickets_dashboard_controller.php';
include 'api/photo_helper.php';
include 'includes/layout.php';

if (!UserService::hasRole($userRole ?? '', 'it_admin')) {
    header("Location: index.php");
    exit();
}
require_once 'services/SurveyQuestionHelper.php';

// Initialize Helper
$questionHelper = new SurveyQuestionHelper($conn);

// Pagination
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 10;
$totalQuestions = $questionHelper->countQuestions();
$totalPages = ceil($totalQuestions / $limit);

// Fetch Questions
$questions = $questionHelper->getAllQuestions($page, $limit);
$nextOrder = $questionHelper->getNextSortOrder();

renderPageStart('ITicketHub - Manage Survey Questions');
renderTopNav('survey_questions', $userRole);
?>

<!-- MAIN LAYOUT: Left Content + Right Panel -->
<div class="main-layout">

    <!-- LEFT: Main Content Area -->
    <main class="content-area" style="overflow-x: hidden; width: 100%;">
        <div class="page-header">
            <h1><i class="fas fa-poll"></i> Manage Survey Questions</h1>
            <div class="header-actions">
                <button class="new-ticket-btn" onclick="openAddQuestionModal()">
                    <i class="fas fa-plus"></i> Add Question
                </button>
            </div>
        </div>

        <!-- Questions Table -->
        <div class="users-table-container">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th style="text-align: center; width: 60px;">ID</th>
                        <th style="text-align: center;">Question Text</th>
                        <th style="text-align: center; width: 80px;">Order</th>
                        <th style="text-align: center; width: 100px;">Status</th>
                        <th style="text-align: center; width: 150px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($questions)): ?>
                        <?php foreach ($questions as $q): ?>
                            <tr>
                                <td style="text-align: center;">
                                    <?php echo htmlspecialchars($q['id']); ?>
                                </td>
                                <td style="text-align: center; font-weight: 500; color: #334155;">
                                    <?php echo htmlspecialchars($q['question_text']); ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php echo htmlspecialchars($q['sort_order']); ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($q['is_active']): ?>
                                        <span class="status-badge"
                                            style="background: #dcfce7; color: #166534; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">Active</span>
                                    <?php else: ?>
                                        <span class="status-badge"
                                            style="background: #fee2e2; color: #991b1b; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <button class="edit-btn"
                                        onclick="openEditQuestionModal(<?php echo htmlspecialchars(json_encode($q)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="edit-btn"
                                        style="background: linear-gradient(135deg, #ef4444, #dc2626); margin-left: 5px;"
                                        onclick="deleteQuestion(<?php echo $q['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 20px;">No questions found.</td>
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
                min-width: 600px;
                border-collapse: collapse;
            }

            .modern-table thead {
                background: linear-gradient(135deg, #f8fafc, #f1f5f9);
                position: sticky;
                top: 0;
            }

            .modern-table th {
                padding: 16px 20px;
                text-align: center;
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

            /* Custom Switch Toggle */
            .switch {
                position: relative;
                display: inline-block;
                width: 48px;
                height: 26px;
                flex-shrink: 0;
            }

            .switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }

            .slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #cbd5e1;
                transition: .3s;
                border-radius: 34px;
            }

            .slider:before {
                position: absolute;
                content: "";
                height: 20px;
                width: 20px;
                left: 3px;
                bottom: 3px;
                background-color: white;
                transition: .3s;
                border-radius: 50%;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }

            input:checked+.slider {
                background-color: #ec4899;
            }

            input:checked+.slider:before {
                transform: translateX(22px);
            }
        </style>

    </main> <!-- End content-area -->

    <?php renderRightPanel($userRole, 'survey_questions'); ?>

</div> <!-- End main-layout -->

<!-- Add Question Modal -->
<div id="addQuestionModal" class="modal">
    <div class="modal-content">
        <span class="close-button" onclick="closeAddQuestionModal()">&times;</span>
        <h2>Add new Survey Question</h2>
        <form id="addQuestionForm">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label for="addQuestionText">Question Text</label>
                <textarea id="addQuestionText" name="question_text" rows="3" required></textarea>
            </div>
            <div class="form-group">
                <label for="addSortOrder">Display Order (Automatic)</label>
                <input type="number" id="addSortOrder" name="sort_order" value="<?php echo $nextOrder; ?>" readonly
                    style="background-color: #f1f5f9; cursor: not-allowed;"
                    title="Display order is assigned automatically">
            </div>
            <div class="form-group button-group">
                <button type="submit" class="submit-button">Add Question</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Question Modal -->
<div id="editQuestionModal" class="modal">
    <div class="modal-content">
        <span class="close-button" onclick="closeEditQuestionModal()">&times;</span>
        <h2>Edit Survey Question</h2>
        <form id="editQuestionForm">
            <input type="hidden" name="action" value="update">
            <input type="hidden" id="editQuestionId" name="id">
            <div class="form-group">
                <label for="editQuestionText">Question Text</label>
                <textarea id="editQuestionText" name="question_text" rows="3" required></textarea>
            </div>
            <div class="form-group">
                <label for="editSortOrder">Display Order</label>
                <input type="number" id="editSortOrder" name="sort_order">
            </div>
            <div class="form-group" style="display: flex; align-items: center; gap: 10px; margin-top: 15px;">
                <label class="switch">
                    <input type="checkbox" id="editIsActive" name="is_active">
                    <span class="slider"></span>
                </label>
                <span>Question is Active</span>
            </div>
            <div class="form-group button-group">
                <button type="submit" class="submit-button">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Modal functions
    function openAddQuestionModal() {
        document.getElementById('addQuestionModal').style.display = 'block';
        document.getElementById('addQuestionForm').reset();
        // Restore the automatic sort order value after reset
        document.getElementById('addSortOrder').value = '<?php echo $nextOrder; ?>';
    }

    function closeAddQuestionModal() {
        document.getElementById('addQuestionModal').style.display = 'none';
    }

    function openEditQuestionModal(q) {
        document.getElementById('editQuestionId').value = q.id;
        document.getElementById('editQuestionText').value = q.question_text;
        document.getElementById('editSortOrder').value = q.sort_order;
        document.getElementById('editIsActive').checked = q.is_active == 1;
        document.getElementById('editQuestionModal').style.display = 'block';
    }

    function closeEditQuestionModal() {
        document.getElementById('editQuestionModal').style.display = 'none';
    }

    function deleteQuestion(id) {
        if (confirm('Are you sure you want to delete this question? This will not affect previous survey responses.')) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);
            saveQuestion(formData);
        }
    }

    // Close on click outside
    window.onclick = function (event) {
        if (event.target == document.getElementById('addQuestionModal')) {
            closeAddQuestionModal();
        }
        if (event.target == document.getElementById('editQuestionModal')) {
            closeEditQuestionModal();
        }
    }

    // Submit Handlers
    document.getElementById('addQuestionForm').addEventListener('submit', function (e) {
        e.preventDefault();
        saveQuestion(new FormData(this));
    });

    document.getElementById('editQuestionForm').addEventListener('submit', function (e) {
        e.preventDefault();
        saveQuestion(new FormData(this));
    });

    function saveQuestion(formData) {
        fetch('api/process_survey_question.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Question saved successfully!');
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