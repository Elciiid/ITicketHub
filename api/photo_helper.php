<?php
/**
 * Helper function to get employee profile photo URL
 * @param string $employee_number The employee ID/number
 * @return string Always returns empty as we are using default avatars
 */
function getEmployeePhotoUrl($employee_number)
{
    return '';
}

/**
 * Generate an img tag with fallback for employee photo
 * NOW SIMPLIFIED: Returns the original styled default avatar div
 * @param string $employee_number The employee ID/number (ignored)
 * @param string $classes CSS classes for the container
 * @param string $alt Alt text (ignored)
 * @return string HTML for the premium default avatar icon
 */
function getEmployeePhotoImg($employee_number, $classes = '', $alt = '')
{
    $classes_attr = $classes ? htmlspecialchars($classes) : '';
    // This structure relies on the existing CSS in modern_sidebar.css and ticket_summary.css
    // which provides the pink gradient background and icon centering.
    return '<div class="default-avatar ' . $classes_attr . '" style="display:flex;"><i class="fas fa-user"></i></div>';
}

/**
 * Output the JavaScript function for trying multiple photo extensions
 * NOW DEPRECATED: No longer needed with default avatars
 */
function outputPhotoHelperScript()
{
    // No-op
}
?>