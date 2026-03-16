<?php
/**
 * Helper function to get employee profile photo URL
 * @param string $employee_number The employee ID/number
 * @return string The photo URL or empty string if not found
 */
function getEmployeePhotoUrl($employee_number)
{
    if (empty($employee_number)) {
        return '';
    }

    // Base URL for employee photos
    $base_url = '/lrnph/emp_photos/';
    return $base_url . htmlspecialchars($employee_number) . '.jpg';
}

/**
 * Generate an img tag with fallback for employee photo
 * Uses tryPhotoExtensions JavaScript function for fallback
 * @param string $employee_number The employee ID/number
 * @param string $classes CSS classes for the img tag
 * @param string $alt Alt text (defaults to employee number)
 * @return string HTML img tag with fallback icon
 */
function getEmployeePhotoImg($employee_number, $classes = '', $alt = '')
{
    if (empty($employee_number)) {
        return '<div class="default-avatar ' . htmlspecialchars($classes) . '" style="display:flex;"><i class="fas fa-user"></i></div>';
    }

    $employee_id = htmlspecialchars($employee_number);
    $alt_text = $alt ?: 'Employee ' . $employee_id;
    $classes_attr = $classes ? htmlspecialchars($classes) : '';

    // Base URL for employee photos
    $base_url = '/lrnph/emp_photos/';

    // Create img tag with JavaScript fallback that tries multiple extensions
    $js_function = "tryPhotoExtensions('$employee_id', this)";

    return '<div class="relative w-full h-full flex items-center justify-center">' .
        '<img src="' . $base_url . $employee_id . '.jpg" alt="' . htmlspecialchars($alt_text) . '" class="' . $classes_attr . ' relative z-10" onerror="' . $js_function . '" />' .
        '<div class="default-avatar ' . $classes_attr . ' absolute z-0" style="display:none;"><i class="fas fa-user"></i></div>' .
        '</div>';
}

/**
 * Output the JavaScript function for trying multiple photo extensions
 * Call this once in your page, preferably before </body>
 */
function outputPhotoHelperScript()
{
    echo <<<'SCRIPT'
<script>
// Function to try multiple photo extensions
function tryPhotoExtensions(employeeId, imgElement) {
    var extensions = ['jpeg', 'png', 'gif', 'webp', 'JPG', 'JPEG', 'PNG', 'GIF', 'WEBP'];
    var baseUrl = '/lrnph/emp_photos/';

    // Initialize state
    if (typeof imgElement.dataset.tryIndex === 'undefined') {
        imgElement.dataset.tryIndex = 0;
        imgElement.dataset.tryingAltId = 'false';
        console.log('Photo fallback: Starting attempts for employee ID:', employeeId);
    }

    var currentIndex = parseInt(imgElement.dataset.tryIndex);
    var tryingAltId = imgElement.dataset.tryingAltId === 'true';

    // If employee ID has a year prefix (e.g., "2025-40696"), extract just the number part
    var altEmployeeId = employeeId;
    if (employeeId.includes('-')) {
        var parts = employeeId.split('-');
        if (parts.length === 2 && !isNaN(parts[0]) && parts[0].length === 4) {
            altEmployeeId = parts[1]; // Extract "40696" from "2025-40696"
        }
    }

    if (currentIndex < extensions.length) {
        // Try next extension
        imgElement.dataset.tryIndex = currentIndex + 1;
        var idToUse = tryingAltId ? altEmployeeId : employeeId;
        var newUrl = baseUrl + idToUse + '.' + extensions[currentIndex];
        console.log('Photo fallback: Trying', newUrl);
        imgElement.src = newUrl;
    } else if (!tryingAltId && altEmployeeId !== employeeId) {
        // Switch to trying the alternate ID (without year prefix)
        console.log('Photo fallback: Switching to alternate ID (without year prefix):', altEmployeeId);
        imgElement.dataset.tryingAltId = 'true';
        imgElement.dataset.tryIndex = 0;
        imgElement.src = baseUrl + altEmployeeId + '.jpg';
    } else {
        // All extensions failed - show fallback
        console.log('Photo fallback: All attempts failed, showing default icon');
        imgElement.onerror = null;
        imgElement.style.display = 'none';
        var fallback = imgElement.nextElementSibling;
        if (fallback) {
            fallback.style.display = 'flex';
        }
    }
}
</script>
SCRIPT;
}
?>