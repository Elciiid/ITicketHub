document.addEventListener('DOMContentLoaded', function () {
    const newTicketModal = document.getElementById("newTicketModal");
    const openNewTicketBtn = document.querySelector(".new-ticket-btn");
    const closeBtns = document.querySelectorAll(".close-button, .ticket-close-button");

    // Open the new ticket modal
    if (openNewTicketBtn && newTicketModal) {
        openNewTicketBtn.onclick = function () {
            newTicketModal.style.display = "flex";
        }
    }

    // Generic close button handler for all modals
    closeBtns.forEach(btn => {
        btn.onclick = function () {
            const modal = this.closest('.modal') || this.closest('.full-image-modal') || this.closest('.ticket-modal');
            if (modal) {
                modal.style.display = "none";
            }
        }
    });

    // Consolidated window click handler for closing ALL modals when clicking outside
    window.addEventListener('click', function (event) {
        const modals = [
            document.getElementById("newTicketModal"),
            document.getElementById("ticketDetailsModal"),
            document.getElementById("surveyModal"),
            document.getElementById("surveyResultsModal"),
            document.getElementById("fullImageModal"),
            document.getElementById("declineTicketModal")
        ];

        modals.forEach(modal => {
            if (modal && event.target === modal) {
                modal.style.display = "none";
            }
        });
    });
});
