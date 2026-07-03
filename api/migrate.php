<?php
require_once __DIR__ . '/config/db.php';
try {
    $db = new Database();
    $conn = $db->getConnection();
    // Use IF NOT EXISTS equivalent logic or just run it and catch error if it exists
    $conn->exec("ALTER TABLE books ADD COLUMN image_url VARCHAR(500) DEFAULT NULL");
    echo "Migration successful.";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column already exists.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>
