document.addEventListener("DOMContentLoaded", function () {
    const assignPICForm = document.getElementById('assignPICForm');
    if (assignPICForm) {
        assignPICForm.onsubmit = async function (event) {
            event.preventDefault(); // Prevent default form submission

            // Validation: Priority Level is required when assigning a PIC
            const assignees = document.querySelectorAll('input[name="assignees[]"]:checked');
            const prioritySelect = document.getElementById('priorityLevel');

            if (assignees.length > 0 && prioritySelect.value === "") {
                prioritySelect.setCustomValidity("Please select a Priority Level.");
                prioritySelect.reportValidity();

                // Clear validity message as soon as the user changes the selection
                prioritySelect.addEventListener('change', function () {
                    this.setCustomValidity('');
                }, { once: true });

                return; // Stop submission
            }

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

            try {
                const formData = new FormData(this);
                const ticketId = document.getElementById('ticketId').textContent; // Get ticket ID from the modal
                formData.append('ticketId', ticketId);

                const response = await fetch('api/assign_pic.php', {
                    method: 'POST',
                    body: formData,
                });

                const data = await response.json();

                // Show success notification
                notification.textContent = data.message;
                notification.style.backgroundColor = '#4CAF50'; // Green for success
                notification.style.display = 'block';

                // Hide notification after 3 seconds
                setTimeout(() => {
                    notification.style.display = 'none';
                }, 3000);

                // Optionally, refresh the page or update the UI
                window.location.reload();
            } catch (error) {
                console.error('Error assigning PIC:', error);

                // Show error notification
                notification.textContent = 'Error processing assignment. Please try again.';
                notification.style.backgroundColor = '#f44336'; // Red for error
                notification.style.display = 'block';

                // Hide notification after 3 seconds
                setTimeout(() => {
                    notification.style.display = 'none';
                }, 3000);
            }
        };
    }
});