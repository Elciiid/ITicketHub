function resetFilters() {
    // Clear the input fields
    document.getElementById('search').value = '';
    document.getElementById('date-created-from').value = '';
    document.getElementById('date-created-to').value = '';
    document.getElementById('department').selectedIndex = 0; // Reset to first option
    document.getElementById('assignee').selectedIndex = 0; // Reset to first option
    document.getElementById('status').selectedIndex = 0; // Reset to first option
    document.getElementById('completion-date').value = '';

    // Optionally, reload the page to show all tickets
    window.location.href = window.location.pathname; // This will reload the page without any query parameters
}

document.addEventListener('DOMContentLoaded', function () {
    const dateCells = document.querySelectorAll('td[data-date-created], td[data-date-updated]');

    dateCells.forEach(cell => {
        const dateValue = cell.getAttribute('data-date-created') || cell.getAttribute('data-date-updated');

        // Check if the date value exists and is valid
        if (dateValue && !isNaN(new Date(dateValue).getTime())) {
            const date = new Date(dateValue);
            if (cell.hasAttribute('data-date-created')) {
                cell.textContent = formatDate(date);
            } else if (cell.hasAttribute('data-date-updated')) {
                cell.textContent = formatTimeAgo(date);
            }
        } else {
            // Handle null or invalid date
            cell.textContent = 'Date unavailable';
        }
    });

    function formatDate(date) {
        const months = ["January", "February", "March", "April", "May", "June", "July",
            "August", "September", "October", "November", "December"];
        const month = months[date.getMonth()];
        const day = date.getDate();
        const year = date.getFullYear();
        const hours = date.getHours() % 12 || 12;
        const minutes = String(date.getMinutes()).padStart(2, '0');
        const ampm = date.getHours() >= 12 ? 'PM' : 'AM';

        // More formal format: "January 1, 2025 at 12:00 PM"
        return `${month} ${day}, ${year} at ${hours}:${minutes} ${ampm}`;
    }

    function formatTimeAgo(date) {
        // Ensure the date is valid
        if (!date || isNaN(date.getTime())) {
            return 'Date unavailable';
        }

        const now = new Date();
        const diff = now - date;

        // Calculate time differences in various units
        const seconds = Math.floor(diff / 1000);
        const minutes = Math.floor(seconds / 60);
        const hours = Math.floor(minutes / 60);
        const days = Math.floor(hours / 24);

        // More formal time representations
        if (days > 0) {
            return days === 1 ? '1 day ago' : `${days} days ago`;
        } else if (hours > 0) {
            return hours === 1 ? '1 hour ago' : `${hours} hours ago`;
        } else if (minutes > 0) {
            return minutes === 1 ? '1 minute ago' : `${minutes} minutes ago`;
        } else {
            return 'Less than a minute ago';
        }
    }
});


// Auto-submit search and filters
document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.querySelector('.table-controls form');
    const searchInput = document.getElementById('search');
    const filterSelects = filterForm ? filterForm.querySelectorAll('select') : [];
    const dateInputs = filterForm ? filterForm.querySelectorAll('input[type="date"]') : [];

    if (!filterForm) return;

    function performSubmit() {
        const inputs = filterForm.querySelectorAll('input, select');
        inputs.forEach(input => {
            if (!input.value) {
                input.disabled = true;
            }
        });
        filterForm.submit();
    }

    // Debounce function to prevent too many requests while typing
    let debounceTimer;
    function debounceSubmit() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(performSubmit, 600); // 600ms delay after typing stops
    }

    // Auto-submit for search text
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            // Only search if at least 2 characters or empty (to reset)
            if (this.value.length >= 2 || this.value.length === 0) {
                debounceSubmit();
            }
        });

        // Set cursor to end of text if it was from a search
        if (searchInput.value) {
            searchInput.focus();
            const val = searchInput.value;
            searchInput.value = '';
            searchInput.value = val;
        }
    }

    // Immediate submit for dropdowns and dates
    filterSelects.forEach(select => {
        select.addEventListener('change', performSubmit);
    });

    dateInputs.forEach(date => {
        date.addEventListener('change', performSubmit);
    });
});

