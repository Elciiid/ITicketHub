document.addEventListener("DOMContentLoaded", function () {
    const newTicketForm = document.getElementById('newTicketForm');
    if (newTicketForm) {
        newTicketForm.onsubmit = async function (event) {
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
                const attachments = document.getElementById('attachments');
                if (attachments && attachments.files.length > 0) {
                    for (let i = 0; i < attachments.files.length; i++) {
                        if (attachments.files[i].size > maxFileSize) {
                            notification.textContent = 'The file attached is too big. Maximum file size is 50MB.';
                            notification.style.backgroundColor = '#f44336';
                            notification.style.display = 'block';
                            setTimeout(() => { notification.style.display = 'none'; }, 5000);
                            return;
                        }
                    }
                }

                const formData = new FormData(this);

                const response = await fetch('api/submit_ticket.php', {
                    method: 'POST',
                    body: formData,
                });

                const data = await response.json();

                if (data.success) {
                    // Show success notification
                    notification.textContent = data.message;
                    notification.style.backgroundColor = '#4CAF50'; // Green for success
                    notification.style.display = 'block';

                    // Hide notification after 3 seconds
                    setTimeout(() => {
                        notification.style.display = 'none';
                    }, 3000);

                    // Reload the page to reflect the updated data
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000); // Reload after 1 second (adjust as needed)
                } else {
                    // Show error notification
                    notification.textContent = data.message;
                    notification.style.backgroundColor = '#f44336'; // Red for error
                    notification.style.display = 'block';

                    // Hide notification after 3 seconds
                    setTimeout(() => {
                        notification.style.display = 'none';
                    }, 3000);
                }
            } catch (error) {
                console.error('Error submitting ticket:', error);
                console.log(error);
                // Show error notification
                notification.textContent = 'Error submitting ticket. Please try again.';
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