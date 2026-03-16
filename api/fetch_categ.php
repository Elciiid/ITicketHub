<?php
require_once __DIR__ . '/../services/CategoryHelper.php';

// Initialize Helper with database connection from db.php (which is included by the parent file usually)
if (isset($conn)) {
    $categoryHelper = new CategoryHelper($conn);
    $categories = $categoryHelper->getAllCategories();
} else {
    $categories = []; // Fallback if no connection
}
?>