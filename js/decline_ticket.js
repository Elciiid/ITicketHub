// Decline Ticket Function
function openDeclineModal(ticketId) {

    if (!ticketId) {
        const ticketIdElement = document.getElementById('ticketId');
        if (ticketIdElement && ticketIdElement.textContent) {
            ticketId = ticketIdElement.textContent.trim();
        }
    }

    // Ensure we have a valid ticket ID
    if (!ticketId) {
        alert('Error: Unable to determine ticket ID.');
        return;
    }

    // Set the ticket ID in the decline form
    document.getElementById('declineTicketId').value = ticketId;

    // Show the decline modal
    const declineModal = document.getElementById('declineTicketModal');
    if (declineModal) {
        declineModal.style.display = 'block';
    } else {
        alert('Error: Decline modal not found.');
    }
}

// Handle decline ticket form submission
document.addEventListener('DOMContentLoaded', function () {
    const declineForm = document.getElementById('declineTicketForm');

    console.log('Decline form found:', declineForm);

    if (declineForm) {
        declineForm.addEventListener('submit', function (e) {
            console.log('Form submission triggered');
            e.preventDefault();

            const formData = new FormData(declineForm);
            const ticketId = formData.get('ticket_id');
            const rejectRemarks = formData.get('reject_remarks');

            // Debug logging
            console.log('Ticket ID:', ticketId);
            console.log('Reject Remarks:', rejectRemarks);
            console.log('FormData entries:');
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
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

            if (!rejectRemarks || rejectRemarks.trim() === '') {
                notification.textContent = 'Please enter remarks for declining this ticket.';
                notification.style.backgroundColor = '#f44336';
                notification.style.display = 'block';
                setTimeout(() => notification.style.display = 'none', 3000);
                return;
            }

            // Send the decline request
            fetch('api/decline_ticket.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    ticket_id: ticketId,
                    reject_remarks: rejectRemarks.trim()
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        notification.textContent = 'Ticket declined successfully.';
                        notification.style.backgroundColor = '#4CAF50';
                        notification.style.display = 'block';

                        setTimeout(() => {
                            notification.style.display = 'none';
                            // Close the decline modal
                            closeDeclineModal();
                            // Close the ticket details modal
                            closeTicketDetailsModal();
                            // Reload the page to refresh the ticket list
                            location.reload();
                        }, 1000);
                    } else {
                        notification.textContent = 'Error: ' + data.message;
                        notification.style.backgroundColor = '#f44336';
                        notification.style.display = 'block';
                        setTimeout(() => notification.style.display = 'none', 3000);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    notification.textContent = 'An error occurred while declining the ticket.';
                    notification.style.backgroundColor = '#f44336';
                    notification.style.display = 'block';
                    setTimeout(() => notification.style.display = 'none', 3000);
                });
        });
    }
});

// Close decline modal
function closeDeclineModal() {
    const declineModal = document.getElementById('declineTicketModal');
    if (declineModal) {
        declineModal.style.display = 'none';
        // Clear the form when closing
        const declineForm = document.getElementById('declineTicketForm');
        if (declineForm) {
            declineForm.reset();
            // Clear the hidden ticket ID as well
            document.getElementById('declineTicketId').value = '';
        }
    }
}

// Close ticket details modal (assuming it exists)
function closeTicketDetailsModal() {
    const ticketModal = document.getElementById('ticketDetailsModal');
    if (ticketModal) {
        ticketModal.style.display = 'none';
    }
}
