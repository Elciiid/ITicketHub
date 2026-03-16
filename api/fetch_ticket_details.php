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
                itr.resolution,
                itr.assigned_to,
                itr.reject_remarks,
                itr.urgency_level,
                COALESCE(
                    CASE 
                        WHEN u.LastName IS NOT NULL OR u.FirstName IS NOT NULL 
                        THEN LTRIM(RTRIM(ISNULL(u.LastName, '') + ', ' + ISNULL(u.FirstName, '') + ' ' + ISNULL(u.MiddleName, '')))
                        ELSE NULL 
                    END,
                    ojt_r.full_name,
                    itr.requestor
                ) AS requestor,
                (
                    SELECT STRING_AGG(
                        COALESCE(
                            CASE 
                                WHEN e.LastName IS NOT NULL OR e.FirstName IS NOT NULL 
                                THEN LTRIM(RTRIM(ISNULL(e.LastName, '') + ', ' + ISNULL(e.FirstName, '') + ' ' + ISNULL(e.MiddleName, '')))
                                ELSE NULL 
                            END,
                            ojt.full_name,
                            LTRIM(RTRIM(empcodes.value))
                        ), 
                        ', '
                    )
                    FROM STRING_SPLIT(ISNULL(itr.assigned_to, ''), ',') AS empcodes
                    LEFT JOIN LRNPH_E.dbo.lrn_master_list e ON (LTRIM(RTRIM(empcodes.value)) = e.BiometricsID OR LTRIM(RTRIM(empcodes.value)) = e.EmployeeID)
                    LEFT JOIN LRNPH_E.app.app_ojt_employees ojt ON LTRIM(RTRIM(empcodes.value)) = ojt.employee_id
                    WHERE LTRIM(RTRIM(empcodes.value)) != ''
                ) AS assigned
            FROM it_ticket_request itr
            LEFT JOIN LRNPH_E.dbo.lrn_master_list u ON (itr.requestor = u.BiometricsID OR itr.requestor = u.EmployeeID)
            LEFT JOIN LRNPH_E.app.app_ojt_employees ojt_r ON itr.requestor = ojt_r.employee_id
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