<?php
include 'includes/db.php';

function checkTable($conn, $table) {
    echo "COLUMNS FOR $table:\n";
    try {
        $stmt = $conn->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = '$table' ORDER BY ordinal_position");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo " - {$row['column_name']} ({$row['data_type']})\n";
        }
    } catch (Exception $e) {
        echo "ERROR CHECKING $table: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

checkTable($conn, 'it_ticket_roles');
checkTable($conn, 'it_ticket_request');
?>
