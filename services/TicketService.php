<?php
// services/TicketService.php
require_once __DIR__ . '/UserService.php';

class TicketService
{
    private $conn;

    public function __construct($dbConnection)
    {
        $this->conn = $dbConnection;
    }

    /**
     * Create a new ticket
     */
    public function createTicket($data, $files = [])
    {
        try {
            $this->conn->beginTransaction();

            $sql = "INSERT INTO it_ticket_request
([status], [date_created], [requestor], [department], [subject], [description], [date_updated], [categ_name])
VALUES ('Open', GETDATE(), :requestor, :department, :subject, :description, GETDATE(), :category)";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':requestor' => $data['username'],
                ':department' => $data['department'],
                ':subject' => $data['subject'],
                ':description' => $data['description'],
                ':category' => $data['category']
            ]);

            // Get Inserted ID (MSSQL specific)
            $ticketId = $this->conn->lastInsertId();
            if (!$ticketId) {
                // Fallback for some MSSQL drivers
                $stmt = $this->conn->query("SELECT SCOPE_IDENTITY() as id");
                $ticketId = $stmt->fetchColumn();
            }

            // Log Creation
            $this->logHistory($ticketId, $data['username'], 'Ticket Created', 'Open');

            // Handle Attachments
            if (!empty($files)) {
                $this->processAttachments($ticketId, $data['username'], $files);
            }

            $this->conn->commit();
            return ['success' => true, 'ticket_id' => $ticketId];

        } catch (Exception $e) {
            if ($this->conn->inTransaction())
                $this->conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Assign a ticket to PICs
     */
    public function assignTicket($ticketId, $assignedBy, $assignees, $priority)
    {
        try {
            // Fetch current state for intelligent logging
            $stmt = $this->conn->prepare("SELECT [assigned_to], [status] FROM it_ticket_request WHERE id = ?");
            $stmt->execute([$ticketId]);
            $currentTicket = $stmt->fetch(PDO::FETCH_ASSOC);
            $oldAssignedTo = $currentTicket['assigned_to'] ?? '';

            $assignedTo = implode(', ', $assignees);
            // If no assignees, revert to 'Open' and clear priority
            if (empty($assignees)) {
                $newStatus = 'Open';
                $newPriority = null;
            } else {
                $newStatus = 'Assigned';
                $newPriority = $priority;
            }

            $sql = "UPDATE it_ticket_request
                SET status = :status,
                assignedby = :assignedBy,
                assignedby_dt = GETDATE(),
                date_updated = GETDATE(),
                assigned_to = :assignedTo,
                urgency_level = :priority
                WHERE id = :ticketId";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':status' => $newStatus,
                ':assignedBy' => $assignedBy,
                ':ticketId' => $ticketId,
                ':assignedTo' => $assignedTo,
                ':priority' => $newPriority
            ]);

            // Determine log message
            if (empty($assignees)) {
                // Get names of those who WERE assigned
                $oldAssigneeCodes = array_filter(array_map('trim', explode(',', $oldAssignedTo)));
                $oldNames = [];
                foreach ($oldAssigneeCodes as $code) {
                    $oldNames[] = $this->getUserFullName($code);
                }
                $oldNamesStr = implode(', ', $oldNames);
                $actionText = "Removed assignment to: " . ($oldNamesStr ?: "N/A");
            } else {
                // Get names of those NOW assigned
                $assigneeNames = [];
                foreach ($assignees as $assignee) {
                    $assigneeNames[] = $this->getUserFullName($assignee);
                }
                $assigneeNamesStr = implode(', ', $assigneeNames);
                $actionText = "Assigned ticket to: $assigneeNamesStr";
            }

            $this->logHistory($ticketId, $assignedBy, $actionText, $newStatus);

            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Helper to get user full name
     */
    private function getUserFullName($user)
    {
        if (empty($user))
            return '';

        $nameSql = "SELECT TOP 1
            COALESCE(
                CASE
                    WHEN ml.LastName IS NOT NULL OR ml.FirstName IS NOT NULL
                    THEN LTRIM(RTRIM(ISNULL(ml.LastName, '') + ', ' + ISNULL(ml.FirstName, '') + ' ' + ISNULL(ml.MiddleName, '')))
                    ELSE NULL
                END,
                ojt.full_name,
                ?
            ) as fullname
            FROM LRNPH_E.dbo.lrn_master_list ml
            LEFT JOIN LRNPH_E.app.app_ojt_employees ojt ON (? = ojt.employee_id)
            WHERE ml.BiometricsID = ? OR ml.EmployeeID = ? OR CAST(ml.BiometricsID AS VARCHAR(50)) = ? OR CAST(ml.EmployeeID AS VARCHAR(50)) = ?";

        $nameStmt = $this->conn->prepare($nameSql);
        $nameStmt->execute([$user, $user, $user, $user, $user, $user]);
        $nameResult = $nameStmt->fetch(PDO::FETCH_ASSOC);

        return $nameResult ? ($nameResult['fullname'] ?? $user) : $user;
    }

    /**
     * Decline/Reject a ticket
     */
    public function declineTicket($ticketId, $rejectBy, $remarks)
    {
        try {
            $sql = "UPDATE it_ticket_request
SET status = 'Closed',
reject_by = :rejectBy,
reject_dt = GETDATE(),
reject_remarks = :remarks
WHERE id = :ticketId";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':rejectBy' => $rejectBy,
                ':remarks' => $remarks,
                ':ticketId' => $ticketId
            ]);

            $this->logHistory($ticketId, $rejectBy, "Ticket declined with remarks: $remarks", 'Closed');
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Log history
     */
    private function logHistory($ticketId, $user, $action, $status, $remarks = null)
    {
        // Get actor's full name using the helper
        $fullname = $this->getUserFullName($user);

        // Insert history log with actor's fullname
        $sql = "INSERT INTO it_ticket_history_logs (ticket_id, ticket_user, user_fullname, action, status, remarks, date_time)
            VALUES (?, ?, ?, ?, ?, ?, GETDATE())";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$ticketId, $user, $fullname, $action, $status, $remarks]);
    }

    /**
     * Process File Uploads that are passed from $_FILES
     */
    private function processAttachments($ticketId, $username, $files)
    {
        if (empty($files['name'][0]))
            return;

        $uploadFileDir = __DIR__ . "/../uploads/$ticketId/";
        if (!is_dir($uploadFileDir)) {
            mkdir($uploadFileDir, 0755, true);
        }

        foreach ($files['tmp_name'] as $key => $tmpName) {
            $fileNameOriginal = basename($files['name'][$key]);
            // Sanitize filename: replace non-alphanumeric (except . _ -) with underscore
            $sanitizedName = preg_replace("/[^a-zA-Z0-9._-]/", "_", $fileNameOriginal);

            // Ensure uniqueness within the ticket folder by prefixing with index if needed
            // but for better UX, let's keep it clean and just add index if it's the 2nd+ file
            $newFileName = $sanitizedName;
            $destPath = $uploadFileDir . $newFileName;

            if (move_uploaded_file($tmpName, $destPath)) {
                $dbPath = "uploads/$ticketId/$newFileName";

                $sql = "INSERT INTO it_ticket_attachments (ticketid, filepath, created_at) VALUES (?, ?, GETDATE())";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$ticketId, $dbPath]);

                $this->logHistory($ticketId, $username, "File attached: $fileNameOriginal", 'Open');
            }
        }
    }
    /**
     * Get Tickets with Pagination and Filtering
     */
    public function getTickets($filters, $page = 1, $limit = 10, $userRole = 'user', $empcode = null)
    {
        $offset = ($page - 1) * $limit;

        // Main query — removed expensive STRING_AGG/STRING_SPLIT correlated subquery
        $sql = "SELECT
itr.id,
itr.status,
itr.date_created,
COALESCE(
CASE
WHEN ml.LastName IS NOT NULL OR ml.FirstName IS NOT NULL
THEN LTRIM(RTRIM(ISNULL(ml.LastName, '') + ', ' + ISNULL(ml.FirstName, '') + ' ' + ISNULL(ml.MiddleName, '')))
ELSE NULL
END,
ojt_r.full_name,
itr.requestor
) as requestor,
itr.department,
itr.subject,
itr.urgency_level,
itr.requestor as requestor_emp,
itr.assigned_to,
itr.date_updated,
itr.categ_name as category_name
FROM it_ticket_request itr
LEFT JOIN LRNPH_E.dbo.lrn_master_list ml ON itr.requestor = ml.BiometricsID
LEFT JOIN LRNPH_E.app.app_ojt_employees ojt_r ON itr.requestor = ojt_r.employee_id
WHERE 1=1";

        // Count query — simplified, no JOINs needed unless searching by name
        $needsJoinForSearch = !empty($filters['search']);

        if ($needsJoinForSearch) {
            $sqlCount = "SELECT COUNT(*) FROM it_ticket_request itr
LEFT JOIN LRNPH_E.dbo.lrn_master_list ml ON itr.requestor = ml.BiometricsID
LEFT JOIN LRNPH_E.app.app_ojt_employees ojt_r ON itr.requestor = ojt_r.employee_id
WHERE 1=1";
        } else {
            $sqlCount = "SELECT COUNT(*) FROM it_ticket_request itr WHERE 1=1";
        }

        $params = [];

        // Role restriction
        $isITStaff = UserService::hasRole($userRole, 'it_admin') || UserService::hasRole($userRole, 'it_pic');
        if (!$isITStaff) {
            $userFilter = " AND itr.requestor = :username";
            $sql .= $userFilter;
            $sqlCount .= $userFilter;
            $params[':username'] = $empcode;
        }

        // Apply filters
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            // Simplified search - removed slow EXISTS subquery with STRING_SPLIT
            // For technician search, we just search the assigned_to field directly
            $searchCond = " AND (
                CAST(itr.id AS NVARCHAR) LIKE :s1
                OR itr.subject LIKE :s2
                OR itr.description LIKE :s3
                OR itr.requestor LIKE :s4
                OR ml.FirstName LIKE :s5
                OR ml.LastName LIKE :s6
                OR ml.BiometricsID LIKE :s7
                OR ml.EmployeeID LIKE :s8
                OR itr.categ_name LIKE :s9
                OR itr.assigned_to LIKE :s10
                OR ojt_r.full_name LIKE :s11
            )";
            $sql .= $searchCond;
            $sqlCount .= $searchCond;

            for ($i = 1; $i <= 11; $i++) {
                $params[":s$i"] = $search;
            }
        }

        if (!empty($filters['status'])) {
            $sql .= " AND itr.status = :status";
            $sqlCount .= " AND itr.status = :status";
            $params[':status'] = $filters['status'];
        } elseif (!empty($filters['urgency']) && $filters['urgency'] === 'High') {
            // If filtering by High urgency, we only show active ones by default
            $defaultStatus = " AND itr.status NOT IN ('Closed', 'Completed')";
            $sql .= $defaultStatus;
            $sqlCount .= $defaultStatus;
        }

        if (!empty($filters['urgency'])) {
            $sql .= " AND itr.urgency_level = :urgency";
            $sqlCount .= " AND itr.urgency_level = :urgency";
            $params[':urgency'] = $filters['urgency'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND itr.date_created >= :date_from";
            $sqlCount .= " AND itr.date_created >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND itr.date_created <= DATEADD(day, 1, CAST(:date_to AS DATE))";
            $sqlCount .= " AND itr.date_created <= DATEADD(day, 1, CAST(:date_to AS DATE))";
            $params[':date_to'] = $filters['date_to'];
        }

        if (!empty($filters['dept'])) {
            $sql .= " AND itr.department = :dept";
            $sqlCount .= " AND itr.department = :dept";
            $params[':dept'] = $filters['dept'];
        }

        if (!empty($filters['assignee'])) {
            $sql .= " AND itr.assigned_to LIKE :assignee";
            $sqlCount .= " AND itr.assigned_to LIKE :assignee";
            $params[':assignee'] = '%' . $filters['assignee'] . '%';
        }

        // Add sorting and pagination
        $sql .= " ORDER BY itr.date_created DESC OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY";

        // Execute Count first (simpler query)
        $stmtCount = $this->conn->prepare($sqlCount);
        foreach ($params as $k => $v) {
            $stmtCount->bindValue($k, $v);
        }
        $stmtCount->execute();
        $total = $stmtCount->fetchColumn();

        // Execute Data query
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->execute();

        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Resolve assigned_to names ONLY for the paginated results (max ~10 rows)
        foreach ($tickets as &$ticket) {
            if (!empty($ticket['assigned_to'])) {
                $empcodes = array_filter(array_map('trim', explode(',', $ticket['assigned_to'])));
                if (!empty($empcodes)) {
                    $placeholders = implode(',', array_fill(0, count($empcodes), '?'));
                    $nameStmt = $this->conn->prepare("
                        SELECT COALESCE(
                            CASE 
                                WHEN ml.LastName IS NOT NULL OR ml.FirstName IS NOT NULL 
                                THEN LTRIM(RTRIM(ISNULL(ml.LastName, '') + ', ' + ISNULL(ml.FirstName, '') + ' ' + ISNULL(ml.MiddleName, '')))
                                ELSE NULL 
                            END,
                            ojt.full_name,
                            ml.BiometricsID
                        ) as fullname
                        FROM LRNPH_E.dbo.lrn_master_list ml
                        LEFT JOIN LRNPH_E.app.app_ojt_employees ojt ON ml.BiometricsID = ojt.employee_id
                        WHERE ml.BiometricsID IN ($placeholders)
                    ");
                    $nameStmt->execute($empcodes);
                    $names = $nameStmt->fetchAll(PDO::FETCH_COLUMN);
                    if (!empty($names)) {
                        $ticket['assigned_to'] = implode(', ', $names);
                    }
                }
            }
        }
        unset($ticket);

        return [
            'tickets' => $tickets,
            'total' => (int) $total,
            'pages' => ceil($total / $limit)
        ];
    }
}
?>