<?php
require 'db.php';
session_start();

$items = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT p.id, p.name, p.price, p.image FROM products p 
                           JOIN wishlist w ON p.id = w.product_id 
                           WHERE w.user_id = ? ORDER BY w.id DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
header('Content-Type: application/json');
echo json_encode($items);