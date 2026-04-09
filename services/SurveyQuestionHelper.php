<?php
// services/SurveyQuestionHelper.php

class SurveyQuestionHelper
{
    private $conn;

    public function __construct($dbConnection)
    {
        $this->conn = $dbConnection;
    }

    /**
     * Fetch all survey questions with pagination
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getAllQuestions($page = 1, $limit = 10)
    {
        try {
            $offset = ($page - 1) * $limit;
            $sql = "SELECT id, question_text, sort_order, is_active 
                    FROM survey_questions 
                    ORDER BY sort_order ASC, id ASC 
                    LIMIT :limit OFFSET :offset";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function countQuestions()
    {
        try {
            $sql = "SELECT COUNT(*) FROM survey_questions";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Get the next available sort order
     * @return int
     */
    public function getNextSortOrder()
    {
        try {
            $sql = "SELECT COALESCE(MAX(sort_order), 0) + 1 FROM survey_questions";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 1;
        }
    }

    /**
     * Add a new survey question
     * @param string $questionText
     * @param int $sortOrder
     * @return bool
     */
    public function addQuestion($questionText, $sortOrder = 0)
    {
        try {
            if ($sortOrder <= 0) {
                $sortOrder = $this->getNextSortOrder();
            }
            $sql = "INSERT INTO survey_questions (question_text, sort_order, is_active) VALUES (?, ?, 1)";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$questionText, $sortOrder]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Update an existing survey question
     * @param int $id
     * @param string $questionText
     * @param int $sortOrder
     * @param int $isActive
     * @return bool
     */
    public function updateQuestion($id, $questionText, $sortOrder, $isActive)
    {
        try {
            $sql = "UPDATE survey_questions SET question_text = ?, sort_order = ?, is_active = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$questionText, $sortOrder, $isActive, $id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Delete a survey question
     * @param int $id
     * @return bool
     */
    public function deleteQuestion($id)
    {
        try {
            $sql = "DELETE FROM survey_questions WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Get a single survey question
     * @param int $id
     * @return array|null
     */
    public function getQuestion($id)
    {
        try {
            $sql = "SELECT id, question_text, sort_order, is_active FROM survey_questions WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Log survey management actions
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