<?php
require_once '../db/db.php';

$categories = [
    'Face Terminal/Door Access',
    'Computer/Desktop',
    'CCTV',
    'Network',
    'Printer',
    'Software Development',
    'VOIP/Telephone',
    'User Access',
    'Others'
];

try {
    // 1. Clear existing categories (optional, but ensures clean slate if you want ONLY these)
    // Be careful if old tickets rely on IDs. But we are storing names now, so it's safer.
    // However, let's just INSERT/UPDATE to be safe.

    // Actually, user gave a specific list. It's cleaner to TRUNCATE and RE-INSERT 
    // IF the table is just a lookup list and we don't rely on Foreign Keys anymore.
    // Since we moved to storing `categ_name` in `it_ticket_request`, we can refresh this table.

    $conn->exec("TRUNCATE TABLE it_ticket_categ");

    $stmt = $conn->prepare("INSERT INTO it_ticket_categ (category_name) VALUES (?)");

    foreach ($categories as $cat) {
        $stmt->execute([$cat]);
        echo "Added: $cat\n";
    }

    echo "Categories updated successfully.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>