<?php
/**
 * PdoSessionHandler
 * Stateless session handler for Supabase (PostgreSQL)
 */

class PdoSessionHandler implements SessionHandlerInterface {
    private $pdo;
    private $table = '"it_app_sessions"';

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function open($savePath, $sessionName): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read($id): string {
        try {
            $stmt = $this->pdo->prepare("SELECT \"data\" FROM $this->table WHERE \"id\" = :id");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? (string)$row['data'] : '';
        } catch (PDOException $e) {
            return '';
        }
    }

    public function write($id, $data): bool {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO $this->table (\"id\", \"data\", \"last_accessed\")
                VALUES (:id, :data, CURRENT_TIMESTAMP)
                ON CONFLICT (\"id\") DO UPDATE SET
                    \"data\" = EXCLUDED.data,
                    \"last_accessed\" = EXCLUDED.last_accessed
            ");
            return $stmt->execute([':id' => $id, ':data' => $data]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function destroy($id): bool {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM $this->table WHERE \"id\" = :id");
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function gc($maxlifetime): int|false {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM $this->table WHERE \"last_accessed\" < CURRENT_TIMESTAMP - (INTERVAL '1 second' * :maxlifetime)");
            $stmt->bindValue(':maxlifetime', $maxlifetime, PDO::PARAM_INT);
            $stmt->execute();
            return (int)$stmt->rowCount();
        } catch (PDOException $e) {
            return false;
        }
    }
}
