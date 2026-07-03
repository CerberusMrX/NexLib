<?php
require_once __DIR__ . '/api/config/db.php';
$db = new Database();
$conn = $db->getConnection();
$stmt = $conn->prepare("SELECT id, title, image_url, quantity FROM books ORDER BY id DESC LIMIT 5");
$stmt->execute();
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($books, JSON_PRETTY_PRINT);
?>
