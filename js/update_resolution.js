document.addEventListener("DOMContentLoaded", function () {
    const assignPICForm = document.getElementById('resoPICForm');
    if (assignPICForm) {
        assignPICForm.onsubmit = async function (event) {
            event.preventDefault(); // Prevent default form submission

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
                // Validate file size (50MB max)
                const maxFileSize = 50 * 1024 * 1024; // 50MB in bytes
                const resoFiles = document.getElementById('resoAttachments');
                if (resoFiles && resoFiles.files.length > 0) {
                    for (let i = 0; i < resoFiles.files.length; i++) {
                        if (resoFiles.files[i].size > maxFileSize) {
                            notification.textContent = 'The file attached is too big. Maximum file size is 50MB.';
                            notification.style.backgroundColor = '#f44336';
                            notification.style.display = 'block';
                            setTimeout(() => { notification.style.display = 'none'; }, 5000);
                            return;
                        }
                    }
                }

                const formData = new FormData(this);
                const ticketId = document.getElementById('ticketId').textContent; // Get ticket ID from the modal
                formData.append('ticketId', ticketId);

                const response = await fetch('api/update_resolution.php', {
                    method: 'POST',
                    body: formData,
                });

                const data = await response.json();

                // Show success notification
                notification.textContent = data.message;
                notification.style.backgroundColor = '#4CAF50'; // Green for success
                notification.style.display = 'block';

                // Close the ticket details modal
                const ticketModal = document.getElementById('ticketDetailsModal');
                if (ticketModal) ticketModal.style.display = 'none';

                // Reset the form fields
                const remarksField = document.getElementById('remarks');
                if (remarksField) remarksField.value = '';
                const resoStatus = document.getElementById('resoStatus');
                if (resoStatus) resoStatus.value = '';
                const resoAttachments = document.getElementById('resoAttachments');
                if (resoAttachments) resoAttachments.value = '';

                // Reload the page after a short delay so user sees the notification
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } catch (error) {
                console.error('Error updating ticket:', error);

                // Show error notification
                notification.textContent = 'Error updating ticket.';
                notification.style.backgroundColor = '#f44336'; // Red for error
                notification.style.display = 'block';

                // Hide notification after 3 seconds
                setTimeout(() => {
                    notification.style.display = 'none';
                }, 5000);
            }
        };
    }
});