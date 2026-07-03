<?php
require_once __DIR__ . '/config/db.php';
$db = new Database();
$conn = $db->getConnection();
$stmt = $conn->prepare("SELECT id, title, image_url FROM books ORDER BY id DESC LIMIT 10");
$stmt->execute();
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($books, JSON_PRETTY_PRINT);
?>
