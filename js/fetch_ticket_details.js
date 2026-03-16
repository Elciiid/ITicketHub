function fetchWorkOrderDetails(ticketId) {
    console.log("Fetching details for ticket:", ticketId);

    // Normalize global role safely
    window.userRole = (window.userRole ?? '').toString();

    // LOCAL STATE (important)
    let isAssignedToUser = false;

    console.log('--- Fetching Ticket ---');
    console.log('Ticket ID:', ticketId);
    console.log('Global Role:', window.userRole);

    // Modal + loading state
    const modal = document.getElementById('ticketDetailsModal');
    if (modal) {
        modal.style.display = 'flex';
        document.getElementById('ticketId').textContent = 'Loading...';
    }

    // 🔁 RESET UI SECTIONS (CRITICAL)
    const assignPICSection = document.getElementById('assignPICSection');
    const resoPICSection = document.getElementById('resoPICSection');
    const closedPICSection = document.getElementById('closedPICSection');

    assignPICSection.style.display = 'none';
    resoPICSection.style.display = 'none';
    closedPICSection.style.display = 'none';
    const activityLogsSection = document.getElementById('activityLogsSection');
    if (activityLogsSection) activityLogsSection.style.display = 'none';
    const activityLogsList = document.getElementById('activityLogsList');
    if (activityLogsList) activityLogsList.style.display = 'none';
    const toggleLogsIcon = document.getElementById('toggleLogsIcon');
    if (toggleLogsIcon) toggleLogsIcon.style.transform = 'rotate(0deg)';
    const toggleLogsText = document.getElementById('toggleLogsText');
    if (toggleLogsText) toggleLogsText.textContent = 'View';

    fetch(`api/fetch_ticket_details.php?ticketId=${ticketId}`)
        .then(res => res.ok ? res.text() : res.text().then(t => Promise.reject(t)))
        .then(text => {
            const data = JSON.parse(text);
            const ticket = data.ticket;

            /* ------------------ BASIC DETAILS ------------------ */
            document.getElementById('ticketId').textContent = ticket.id;
            document.getElementById('ticketStatus').textContent = ticket.status;
            document.getElementById('ticketCategory').textContent = ticket.category_name;
            document.getElementById('ticketRequestor').textContent = ticket.requestor;
            document.getElementById('ticketDepartment').textContent = ticket.department;
            document.getElementById('ticketSubject').textContent = ticket.subject;
            document.getElementById('ticketDescription').textContent = ticket.description;
            document.getElementById('ticketRemarks').textContent = ticket.remarks || ticket.resolution || '';
            document.getElementById('assignedTo').textContent = ticket.assigned || '';

            /* ------------------ DATE FORMAT ------------------ */
            const formattedDate = new Date(ticket.date_created).toLocaleString('en-US', {
                month: 'short',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            }).replace(',', ' at');
            document.getElementById('ticketDateCreated').textContent = formattedDate;

            /* ------------------ REJECT REMARKS ------------------ */
            const rejectSection = document.getElementById('rejectRemarksSection');
            const rejectText = document.getElementById('ticketRejectRemarks');

            if (ticket.reject_remarks?.trim()) {
                rejectSection.style.display = 'block';
                rejectText.textContent = ticket.reject_remarks;
            } else {
                rejectSection.style.display = 'none';
            }

            /* ------------------ NORMALIZED VALUES ------------------ */
            const ticketStatus = ticket.status.trim().toLowerCase();
            const userRoleLower = (window.userRole || "").toLowerCase();
            const userRoles = userRoleLower.split(',').map(r => r.trim());
            const isAdmin = ['it_admin', 'administrator', 'admin'].some(role => userRoles.includes(role));
            const isPIC = userRoles.includes('it_pic');
            // Admins can assign Open, Assigned, In Progress, or Pending tickets
            const isActiveTicket = ['open', 'assigned', 'in progress', 'pending'].includes(ticketStatus);

            /* ------------------ ASSIGNMENT CHECK ------------------ */
            if (ticket.assigned_to && window.user) {
                const assignedList = ticket.assigned_to
                    .split(',')
                    .map(v => v.trim().toLowerCase());

                const currentUser = window.user.toString().trim().toLowerCase();

                isAssignedToUser = assignedList.some(code =>
                    code === currentUser ||
                    code.endsWith(currentUser) ||
                    currentUser.endsWith(code)
                );
            }

            console.log({ isAdmin, isActiveTicket, isAssignedToUser, ticketStatus });

            /* ------------------ ASSIGN PIC (ADMIN ONLY) ------------------ */
            if (isAdmin && isActiveTicket) {
                assignPICSection.style.display = 'block';

                const checkboxes = document.querySelectorAll('input[name="assignees[]"]');
                checkboxes.forEach(cb => cb.checked = false);

                if (ticket.assigned_to) {
                    const assignedCodes = ticket.assigned_to.split(',').map(c => c.trim());
                    checkboxes.forEach(cb => {
                        if (assignedCodes.includes(cb.value)) cb.checked = true;
                    });
                }


                // Set Priority Level
                const prioritySelect = document.getElementById('priorityLevel');
                if (prioritySelect) {
                    console.log('Setting priority for ticket. urgency_level:', ticket.urgency_level);

                    // Reset first
                    prioritySelect.value = "";

                    if (ticket.urgency_level) {
                        const dbVal = ticket.urgency_level.trim();
                        console.log('Trimmed DB Value:', dbVal);

                        // Create a map of lowercase option values/text to actual values
                        // We want to match against the option value or text
                        let matchFound = false;
                        for (let i = 0; i < prioritySelect.options.length; i++) {
                            const opt = prioritySelect.options[i];
                            const optVal = opt.value;

                            // Check exact match, case-insensitive match, or special mapping
                            if (optVal && (
                                optVal.toLowerCase() === dbVal.toLowerCase() ||
                                (dbVal.toLowerCase() === 'medium' && optVal === 'Mid')
                            )) {
                                prioritySelect.value = optVal;
                                matchFound = true;
                                console.log('Match found, setting to:', optVal);
                                break;
                            }
                        }

                        if (!matchFound) {
                            console.warn('No matching option found for urgency:', dbVal);
                        }
                    }
                }
            }

            /* ------------------ DECLINE BUTTON ------------------ */
            const declineButton = document.querySelector('button[onclick="openDeclineModal()"]');
            if (declineButton) {
                declineButton.style.display =
                    isAdmin && isActiveTicket ? 'inline-block' : 'none';
            }

            /* ------------------ RESOLUTION SECTION (PIC) ------------------ */
            if (
                isAssignedToUser &&
                isPIC &&
                ['assigned', 'in progress', 'pending'].includes(ticketStatus)
            ) {
                resoPICSection.style.display = 'block';

                // Keep remarks empty for new updates
                document.getElementById('remarks').value = '';

                document.getElementById('resoStatus').value = ticket.status;
            }

            /* ------------------ CLOSED / HISTORY ------------------ */
            if (
                !resoPICSection.style.display || resoPICSection.style.display === 'none'
            ) {
                if (
                    ['in progress', 'closed', 'pending', 'completed'].includes(ticketStatus) ||
                    ticket.remarks?.trim() ||
                    ticket.reject_remarks?.trim() ||
                    (data.picAttachments && data.picAttachments.length > 0)
                ) {
                    closedPICSection.style.display = 'block';
                }
            }

            /* ------------------ ACTIVITY LOGS ------------------ */
            renderActivityLogs(data.historyLogs, data.picAttachments);

            /* ------------------ FILES ------------------ */
            renderFiles('ticketImages', data.images);
        })
        .catch(err => {
            console.error('Fetch error:', err);
            alert('Error fetching ticket details.');
        });
}

// Toggle update logs visibility
function toggleUpdateLogs() {
    const logsList = document.getElementById('activityLogsList');
    const icon = document.getElementById('toggleLogsIcon');
    const text = document.getElementById('toggleLogsText');
    if (!logsList) return;

    if (logsList.style.display === 'none') {
        logsList.style.display = 'block';
        if (icon) icon.style.transform = 'rotate(180deg)';
        if (text) text.textContent = 'Hide';
    } else {
        logsList.style.display = 'none';
        if (icon) icon.style.transform = 'rotate(0deg)';
        if (text) text.textContent = 'View';
    }
}

// Function to render activity logs
function renderActivityLogs(logs, picAttachments) {
    const container = document.getElementById('activityLogsList');
    const section = document.getElementById('activityLogsSection');
    const logsCount = document.getElementById('logsCount');
    if (!container || !section) return;

    container.innerHTML = '';

    // Filter for update logs only
    const updateLogs = (logs || []).filter(log =>
        log.action && log.action.toLowerCase().includes('updated to')
    );

    if (updateLogs.length === 0) {
        section.style.display = 'none';
        return;
    }

    section.style.display = 'block';
    if (logsCount) logsCount.textContent = `(${updateLogs.length})`;

    // Map files to logs first (find closest log for each file)
    const fileAssignments = new Map(); // logIndex -> array of files

    if (picAttachments && picAttachments.length > 0) {
        picAttachments.forEach(file => {
            if (!file.created_at || !file.uploaded_by) return;

            const fileDate = new Date(file.created_at);
            let bestLogIndex = -1;
            let minDiff = Infinity;

            updateLogs.forEach((log, index) => {
                const logDate = new Date(log.date_time);
                // Check user match first
                if (file.uploaded_by === (log.user_fullname || log.ticket_user)) {
                    const diff = Math.abs(fileDate - logDate) / 1000;
                    // Must be within 60 seconds and be the closest match found so far
                    if (diff < 60 && diff < minDiff) {
                        minDiff = diff;
                        bestLogIndex = index;
                    }
                }
            });

            if (bestLogIndex !== -1) {
                if (!fileAssignments.has(bestLogIndex)) {
                    fileAssignments.set(bestLogIndex, []);
                }
                fileAssignments.get(bestLogIndex).push(file);
            }
        });
    }

    updateLogs.forEach((log, index) => {
        const logItem = document.createElement('div');
        logItem.style.cssText = 'padding: 10px 12px; background: #f8fafc; border-radius: 6px; margin-bottom: 8px; border-left: 3px solid #e91e8a;';

        const rawDate = new Date(log.date_time);
        const formattedDate = rawDate.toLocaleString('en-US', {
            month: 'short', day: '2-digit', year: 'numeric',
            hour: '2-digit', minute: '2-digit', hour12: true
        });

        const matchedFiles = fileAssignments.get(index) || [];

        let filesHtml = '';
        if (matchedFiles.length > 0) {
            filesHtml = '<div style="display: flex; gap: 6px; margin-top: 8px; flex-wrap: wrap; align-items: center;">';
            filesHtml += '<span style="font-size: 11px; color: #94a3b8;"><i class="fas fa-paperclip"></i></span>';
            matchedFiles.forEach(file => {
                const fileName = file.filepath.split('/').pop();
                const isImg = /\.(jpg|jpeg|png|gif)$/i.test(fileName);
                if (isImg) {
                    filesHtml += `<div style="width: 40px; height: 40px; border: 1px solid #e2e8f0; border-radius: 4px; overflow: hidden; cursor: pointer;" onclick="showFullImage('${file.filepath}')"><img src="${file.filepath}" style="width: 100%; height: 100%; object-fit: cover;"></div>`;
                } else {
                    filesHtml += `<a href="${file.filepath}" target="_blank" style="font-size: 11px; padding: 3px 8px; background: #e2e8f0; border-radius: 4px; text-decoration: none; color: #475569;"><i class="fas fa-file"></i> ${fileName}</a>`;
                }
            });
            filesHtml += '</div>';
        }

        // Extract just the status from "Ticket updated to XXX"
        const statusMatch = log.action.match(/updated to (.+)/i);
        const statusText = statusMatch ? statusMatch[1] : log.action;

        logItem.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px;">
                <div style="flex: 1; min-width: 0;">
                    <div style="font-size: 13px; color: #1e293b; font-weight: 600;">${log.user_fullname || log.ticket_user}</div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Updated status to <span style="color: #e91e8a; font-weight: 500;">${statusText}</span></div>
                </div>
                <div style="font-size: 11px; color: #94a3b8; white-space: nowrap;">${formattedDate}</div>
            </div>
            ${log.remarks ? `<div style="margin-top: 6px; padding: 6px 10px; background: #fff; border-radius: 4px; font-size: 12px; color: #475569; border: 1px solid #e2e8f0; word-break: break-word; overflow-wrap: break-word;">${log.remarks}</div>` : ''}
            ${filesHtml}
        `;
        container.appendChild(logItem);
    });
}

// Function to render files
function renderFiles(containerId, files) {
    const container = document.getElementById(containerId);
    if (!container) return;
    container.innerHTML = ''; // Clear previous

    if (!files || !Array.isArray(files)) return;

    files.forEach(file => {
        const filePath = file.filepath;
        const fileName = filePath.split('/').pop();
        const fileType = fileName.split('.').pop().toLowerCase();
        const uploader = file.uploaded_by || '';
        let fileElement;

        // Wrap everything in a container for consistent styling
        const fileWrapper = document.createElement('div');
        fileWrapper.className = 'file-attachment-wrapper';
        fileWrapper.title = fileName + (uploader ? ` (By: ${uploader})` : '');

        switch (fileType) {
            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'gif':
                fileElement = document.createElement('img');
                fileElement.src = filePath;
                fileElement.alt = fileName;
                fileElement.onclick = () => showFullImage(filePath);
                fileWrapper.appendChild(fileElement);

                const nameLabel = document.createElement('div');
                nameLabel.className = 'file-name-label';
                nameLabel.textContent = fileName;
                fileWrapper.appendChild(nameLabel);

                if (uploader) {
                    const uLabel = document.createElement('div');
                    uLabel.style.fontSize = '10px';
                    uLabel.style.color = '#888';
                    uLabel.textContent = `By: ${uploader}`;
                    fileWrapper.appendChild(uLabel);
                }
                break;
            case 'pdf':
                fileElement = createFileLink(filePath, 'img/pdf-icon.png', 'PDF Document', fileName, uploader);
                fileWrapper.appendChild(fileElement);
                break;
            case 'doc':
            case 'docx':
                fileElement = createFileLink(filePath, 'img/word-icon.png', 'Word Document', fileName, uploader);
                fileWrapper.appendChild(fileElement);
                break;
            case 'xls':
            case 'xlsx':
                fileElement = createFileLink(filePath, 'img/excel-icon.png', 'Excel Document', fileName, uploader);
                fileWrapper.appendChild(fileElement);
                break;
            case 'ppt':
            case 'pptx':
                fileElement = createFileLink(filePath, 'img/powerpoint-icon.png', 'PowerPoint Presentation', fileName, uploader);
                fileWrapper.appendChild(fileElement);
                break;
            default:
                fileElement = document.createElement('a');
                fileElement.href = filePath;
                fileElement.target = '_blank';
                fileElement.className = 'file-attachment-link';
                fileElement.innerHTML = `
                    <i class="fas fa-file-alt" style="font-size: 24px; color: #64748b;"></i>
                    <div style="display: flex; flex-direction: column; margin-left: 5px;">
                        <span class="file-name-label">${fileName}</span>
                        ${uploader ? `<small style="color:#888;">By: ${uploader}</small>` : ''}
                    </div>
                `;
                fileWrapper.appendChild(fileElement);
                break;
        }
        container.appendChild(fileWrapper);
    });
}

// Function to create a file link with an icon and name
function createFileLink(filepath, iconSrc, altText, fileName, uploader = '') {
    const link = document.createElement('a');
    link.href = filepath;
    link.target = '_blank';
    link.className = 'file-attachment-link';

    const icon = document.createElement('img');
    icon.src = iconSrc;
    icon.alt = altText;
    icon.style.width = '24px';
    icon.style.height = '24px';

    const textContainer = document.createElement('div');
    textContainer.style.display = 'flex';
    textContainer.style.flexDirection = 'column';
    textContainer.style.marginLeft = '5px';

    const nameLabel = document.createElement('span');
    nameLabel.className = 'file-name-label';
    nameLabel.textContent = fileName;
    textContainer.appendChild(nameLabel);

    if (uploader) {
        const uLabel = document.createElement('small');
        uLabel.style.color = '#888';
        uLabel.textContent = `By: ${uploader}`;
        textContainer.appendChild(uLabel);
    }

    link.appendChild(icon);
    link.appendChild(textContainer);
    return link;
}

// Function to show full image
function showFullImage(imageSrc) {
    const fullImageModal = document.getElementById('fullImageModal');
    const fullImage = document.getElementById('fullImage');
    const ticketModal = document.getElementById('ticketDetailsModal');

    if (fullImage && fullImageModal) {
        fullImage.src = imageSrc;
        fullImageModal.style.display = 'block'; // CSS !important will make it flex

        // Hide ticket modal temporarily
        if (ticketModal) {
            ticketModal.style.display = 'none';
        }
    }
}

// Event delegation for view buttons and closing modals
document.addEventListener('click', function (event) {
    // Open Ticket Details
    const viewBtn = event.target.closest('.view-btn');
    if (viewBtn) {
        const ticketId = viewBtn.getAttribute('data-ticket-id');
        if (ticketId) {
            fetchWorkOrderDetails(ticketId);
        }
    }

    // Close Ticket Details Modal
    if (event.target.classList.contains('ticket-close-button') || event.target === document.getElementById('ticketDetailsModal')) {
        const modal = document.getElementById('ticketDetailsModal');
        if (modal) modal.style.display = 'none';
        event.preventDefault();
    }

    // Close Full Image Modal
    if (event.target.classList.contains('full-image-close-button') || event.target === document.getElementById('fullImageModal')) {
        const modal = document.getElementById('fullImageModal');
        if (modal) modal.style.display = 'none';

        // Restore ticket modal
        const ticketModal = document.getElementById('ticketDetailsModal');
        if (ticketModal) {
            ticketModal.style.display = 'flex'; // Restore visibility
        }

        event.preventDefault();
    }
});
