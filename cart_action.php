<?php
session_start();
require 'includes/db.php';

header('Content-Type: application/json'); 

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

if (isset($_POST['clear_all'])) {
    $_SESSION['cart'] = [];
    echo json_encode(['status' => 'success', 'total_count' => 0]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : (isset($_POST['update_id']) ? (int)$_POST['update_id'] : null);
    
    if ($id) {

        $check = $pdo->prepare("SELECT id FROM products WHERE id = ?");
        $check->execute([$id]);
        if (!$check->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Товар не найден']);
            exit;
        }

        if (isset($_POST['quantity']) || isset($_POST['qty'])) {
            $qty = isset($_POST['quantity']) ? (int)$_POST['quantity'] : (int)$_POST['qty'];
            
            if ($qty <= 0) {
                unset($_SESSION['cart'][$id]); 
            } else {
                $_SESSION['cart'][$id] = $qty; 
            }
        } else {
            $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + 1;
        }

        echo json_encode([
            'status' => 'success', 
            'total_count' => array_sum($_SESSION['cart'])
        ]);
        exit;
    }
}