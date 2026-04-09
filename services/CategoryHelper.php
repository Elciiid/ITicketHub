<?php
// helpers/CategoryHelper.php

class CategoryHelper
{
    private $conn;

    public function __construct($dbConnection)
    {
        $this->conn = $dbConnection;
    }

    /**
     * Fetch all categories
     * @return array
     */
    /**
     * Fetch all categories with pagination
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getAllCategories($page = 1, $limit = 1000)
    {
        try {
            $offset = ($page - 1) * $limit;
            $sql = "SELECT id, category_name FROM it_ticket_categ ORDER BY category_name ASC LIMIT :limit OFFSET :offset";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Log error or handle gracefully
            return [];
        }
    }

    /**
     * Count total categories
     * @return int
     */
    public function countCategories()
    {
        $sql = "SELECT COUNT(*) FROM it_ticket_categ";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    /**
     * Get ID by Name (if needed for migration compatibility)
     */
    public function getIdByName($name)
    {
        $sql = "SELECT id FROM it_ticket_categ WHERE category_name = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$name]);
        return $stmt->fetchColumn();
    }
    /**
     * Add a new category
     * @param string $name
     * @return bool
     */
    public function addCategory($name)
    {
        try {
            $sql = "INSERT INTO it_ticket_categ (category_name) VALUES (?)";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$name]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Update an existing category
     * @param int $id
     * @param string $name
     * @return bool
     */
    public function updateCategory($id, $name)
    {
        try {
            $sql = "UPDATE it_ticket_categ SET category_name = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$name, $id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Delete a category
     * @param int $id
     * @return bool
     */
    public function deleteCategory($id)
    {
        try {
            $sql = "DELETE FROM it_ticket_categ WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Get a single category
     * @param int $id
     * @return array|null
     */
    public function getCategory($id)
    {
        try {
            $sql = "SELECT id, category_name FROM it_ticket_categ WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Log category management actions
     */
    public function logHistory($user, $action, $status)
    {
        $fullname = $user;

        // Try to get full name from master list
        $stmt = $this->conn->prepare("SELECT TRIM(COALESCE(lastname, '') || ', ' || COALESCE(firstname, '') || ' ' || COALESCE(middlename, '')) as fullname FROM lrn_master_list WHERE biometricsid = ? OR employeeid = ? LIMIT 1");
        $stmt->execute([$user, $user]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && $row['fullname']) {
            $fullname = $row['fullname'];
        } else {
            // Check OJT
            $stmt = $this->conn->prepare("SELECT full_name FROM app_ojt_employees WHERE employee_id = ? LIMIT 1");
            $stmt->execute([$user]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $fullname = $row['full_name'];
            }
        }

        // Insert history log (ticket_id = 0 for system/management logs)
        $sql = "INSERT INTO it_ticket_history_logs (ticket_id, ticket_user, user_fullname, action, status, date_time) 
                VALUES (0, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$user, $fullname, $action, $status]);
    }
}
?>