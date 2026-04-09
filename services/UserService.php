<?php
// services/UserService.php

class UserService
{
    private $conn;

    public function __construct($dbConnection)
    {
        $this->conn = $dbConnection;
    }

    /**
     * Authenticate a user locally
     */
    public function authenticate($username, $password)
    {
        $sql = "SELECT empcode, ticket_role, password, isactive FROM it_ticket_roles WHERE empcode = :username LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $user['isactive'] == 1 && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['empcode'];
            $_SESSION['username'] = $user['empcode'];
            $_SESSION['role'] = $user['ticket_role'];
            
            // Log successful login
            $this->logHistory(0, $user['empcode'], "Successfully logged in via local authentication", "Login Success");
            
            return true;
        }
        return false;
    }

    /**
     * Fetch all users with their roles for the management page
     */
    public function getManagedUsers($page = 1, $limit = 6)
    {
        $offset = ($page - 1) * $limit;
        $sql = "
            SELECT 
                r.empcode as username,
                COALESCE(
                    CASE 
                        WHEN ml.lastname IS NOT NULL OR ml.firstname IS NOT NULL 
                        THEN TRIM(COALESCE(ml.lastname, '') || ', ' || COALESCE(ml.firstname, '') || ' ' || COALESCE(ml.middlename, ''))
                        ELSE NULL 
                    END,
                    ojt.full_name
                ) as fullname,
                COALESCE(ml.department, 'OJT') as department,
                r.ticket_role,
                CASE WHEN r.isactive = 1 THEN 'active' ELSE 'inactive' END as status
            FROM it_ticket_roles r
            LEFT JOIN lrn_master_list ml 
                ON r.empcode = ml.employeeid OR r.empcode = ml.biometricsid
            LEFT JOIN app_ojt_employees ojt
                ON r.empcode = ojt.employee_id
            ORDER BY 
                CASE WHEN ml.lastname IS NOT NULL THEN ml.lastname ELSE ojt.full_name END
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count total managed users for pagination
     */
    public function countManagedUsers()
    {
        $sql = "SELECT COUNT(*) FROM it_ticket_roles";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    /**
     * Get a specific user's role
     */
    public function getUserRole($empcode)
    {
        $sql = "SELECT ticket_role FROM it_ticket_roles WHERE empcode = :empcode LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':empcode', $empcode);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['ticket_role'] : 'user';
    }

    /**
     * Check if user has a specific role
     */
    public static function hasRole($userRole, $targetRole)
    {
        if (!$userRole)
            return false;
        // Normalize both input and target: lowercase, trim, and treat spaces/underscores the same
        $roles = array_map(function ($r) {
            return str_replace('_', ' ', trim(strtolower($r)));
        }, explode(',', $userRole));

        $normalizedTarget = str_replace('_', ' ', strtolower($targetRole));
        return in_array($normalizedTarget, $roles);
    }

    /**
     * Get user details (name, department) from master list
     */
    public function getUserDetails($empcode)
    {
        // Try master list first
        $sql = "
            SELECT
                TRIM(COALESCE(lastname, '')) || ', ' || COALESCE(firstname, '') || ' ' || COALESCE(middlename, '') as fullname,
                department
            FROM lrn_master_list
            WHERE employeeid = :empcode OR biometricsid = :empcode2
            LIMIT 1
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':empcode', $empcode);
        $stmt->bindValue(':empcode2', $empcode);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            return $result;
        }

        // Fallback to OJT table
        $sqlOjt = "
            SELECT
                \"full_name\" as \"fullname\",
                'OJT' as \"department\"
            FROM \"app_ojt_employees\"
            WHERE \"employee_id\" = :empcode
            LIMIT 1
        ";
        $stmtOjt = $this->conn->prepare($sqlOjt);
        $stmtOjt->bindValue(':empcode', $empcode);
        $stmtOjt->execute();
        return $stmtOjt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Update a user's role and status
     */
    public function updateUser($empcode, $role, $status, $actor = null)
    {
        // 1. $empcode is passed directly as $username from the frontend

        try {
            $this->conn->beginTransaction();

            // 2. Check existence in it_ticket_roles
            $checkUser = $this->conn->prepare("SELECT COUNT(*) FROM it_ticket_roles WHERE empcode = :empcode");
            $checkUser->execute([':empcode' => $empcode]);
            $exists = $checkUser->fetchColumn();

            // Map status to 1/0 if provided
            $isActive = ($status === 'active') ? 1 : (($status === 'inactive') ? 0 : null);

            // Handle multi-role if passed as array or comma string
            if (is_array($role)) {
                $roleString = implode(',', $role);
            } else {
                $roleString = $role;
            }

            $actionDescription = "";
            $actionStatus = "";

            if ($exists) {
                // Prepare dynamic Update 
                $fields = [];
                $params = [':empcode' => $empcode];

                if ($roleString !== null) {
                    $fields[] = "ticket_role = :role";
                    $params[':role'] = $roleString;
                }
                if ($isActive !== null) {
                    $fields[] = "isactive = :isActive";
                    $params[':isActive'] = $isActive;
                }

                if (!empty($fields)) {
                    $sql = "UPDATE it_ticket_roles SET " . implode(', ', $fields) . " WHERE empcode = :empcode";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->execute($params);
                }

                $actionDescription = "Updated user account for $empcode (Role: $roleString, Status: $status)";
                $actionStatus = "User Updated";
            } else {
                // Insert new record
                // Default role 'user', default isActive 1
                $newRole = $roleString ?? 'user';
                $newActive = $isActive ?? 1;

                $insert = $this->conn->prepare("INSERT INTO it_ticket_roles (empcode, ticket_role, isactive) VALUES (:empcode, :role, :isActive)");
                $insert->execute([
                    ':empcode' => $empcode,
                    ':role' => $newRole,
                    ':isActive' => $newActive
                ]);

                $actionDescription = "Created user account for $empcode (Role: $newRole)";
                $actionStatus = "User Created";
            }

            // Log History
            if ($actor) {
                $this->logHistory(0, $actor, $actionDescription, $actionStatus);
            }

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Log history
     */
    private function logHistory($ticketId, $user, $action, $status)
    {
        // Simpler approach: Check master list, if null check OJT
        $fullname = $user;

        $stmt = $this->conn->prepare("SELECT TRIM(COALESCE(\"lastname\", '') || ', ' || COALESCE(\"firstname\", '') || ' ' || COALESCE(\"middlename\", '')) as \"fullname\" FROM \"lrn_master_list\" WHERE \"biometricsid\" = ? OR \"employeeid\" = ? LIMIT 1");
        $stmt->execute([$user, $user]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && !empty($row['fullname'])) {
            $fullname = $row['fullname'];
        } else {
            // Check OJT
            $stmt = $this->conn->prepare("SELECT \"full_name\" FROM \"app_ojt_employees\" WHERE \"employee_id\" = ? LIMIT 1");
            $stmt->execute([$user]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $fullname = $row['full_name'];
            }
        }

        // Insert history log
        // Note: using ticket_id = 0 for system/user logs
        $sql = "INSERT INTO \"it_ticket_history_logs\" (\"ticket_id\", \"ticket_user\", \"user_fullname\", \"action\", \"status\", \"date_time\") 
                VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$ticketId, $user, $fullname, $action, $status]);
    }
}
?>