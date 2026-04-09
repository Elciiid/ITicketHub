<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);

include '../db/db.php';

header('Content-Type: application/json');

$ticketId = isset($_GET['ticketId']) ? intval($_GET['ticketId']) : null;

if (!$ticketId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid Ticket ID']);
    exit;
}

try {
    $sql = "SELECT 
                itr.id, 
                itr.status, 
                itr.date_created, 
                itr.categ_name AS category_name,
                itr.department, 
                itr.subject, 
                itr.description,
                itr.assigned_to,
                itr.reject_remarks,
                itr.urgency_level,
                COALESCE(
                    CASE 
                        WHEN u.lastname IS NOT NULL OR u.firstname IS NOT NULL 
                        THEN TRIM(COALESCE(u.lastname, '') || ', ' || COALESCE(u.firstname, '') || ' ' || COALESCE(u.middlename, ''))
                        ELSE NULL 
                    END,
                    ojt_r.full_name,
                    itr.requestor
                ) AS requestor,
                (
                    SELECT string_agg(
                        COALESCE(
                            CASE 
                                WHEN e.lastname IS NOT NULL OR e.firstname IS NOT NULL 
                                THEN TRIM(COALESCE(e.lastname, '') || ', ' || COALESCE(e.firstname, '') || ' ' || COALESCE(e.middlename, ''))
                                ELSE NULL 
                            END,
                            ojt.full_name,
                            TRIM(empcode)
                        ), 
                        ', '
                    )
                    FROM unnest(string_to_array(COALESCE(itr.assigned_to, ''), ',')) AS empcode
                    LEFT JOIN lrn_master_list e ON (TRIM(empcode) = e.biometricsid OR TRIM(empcode) = e.employeeid)
                    LEFT JOIN app_ojt_employees ojt ON TRIM(empcode) = ojt.employee_id
                    WHERE TRIM(empcode) != ''
                ) AS assigned
            FROM it_ticket_request itr
            LEFT JOIN lrn_master_list u ON (itr.requestor = u.biometricsid OR itr.requestor = u.employeeid)
            LEFT JOIN app_ojt_employees ojt_r ON itr.requestor = ojt_r.employee_id
            WHERE itr.id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Ticket not found']);
        exit;
    }

    // Images
    $stmtImages = $conn->prepare("SELECT filepath FROM it_ticket_attachments WHERE ticketid = ?");
    $stmtImages->execute([$ticketId]);
    $images = $stmtImages->fetchAll(PDO::FETCH_ASSOC);

    // PIC Attachments
    $picAttachments = [];
    try {
        $stmtPic = $conn->prepare("SELECT filepath, created_at, uploaded_by FROM it_ticket_pic_attachments WHERE ticketid = ? ORDER BY created_at DESC");
        $stmtPic->execute([$ticketId]);
        $picAttachments = $stmtPic->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e2) {
        // Fallback if uploaded_by column doesn't exist yet
        $stmtPic = $conn->prepare("SELECT filepath, created_at FROM it_ticket_pic_attachments WHERE ticketid = ?");
        $stmtPic->execute([$ticketId]);
        $picAttachments = $stmtPic->fetchAll(PDO::FETCH_ASSOC);
    }

    // History Logs
    $historyLogs = [];
    try {
        $stmtLogs = $conn->prepare("SELECT ticket_user, user_fullname, action, status, remarks, date_time FROM it_ticket_history_logs WHERE ticket_id = ? ORDER BY date_time DESC");
        $stmtLogs->execute([$ticketId]);
        $historyLogs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e3) {
        // Fallback if some columns don't exist yet
        try {
            $stmtLogs = $conn->prepare("SELECT ticket_user, action, status, date_time FROM it_ticket_history_logs WHERE ticket_id = ? ORDER BY date_time DESC");
            $stmtLogs->execute([$ticketId]);
            $historyLogs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e4) {
            $historyLogs = [];
        }
    }

    echo json_encode([
        'success' => true,
        'ticket' => $ticket,
        'images' => $images,
        'picAttachments' => $picAttachments,
        'historyLogs' => $historyLogs
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}