<?php
require 'includes/db.php';
session_start();

// Если корзина пуста или юзер не залогинен — выкидываем
if (empty($_SESSION['cart']) || !isset($_SESSION['user_id'])) {
    header('Location: shop.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$name = $_POST['name'] ?? '';
$surname = $_POST['surname'] ?? '';
$phone = $_POST['phone'] ?? '';
$address = $_POST['address'] ?? '';
$payment = $_POST['payment'] ?? 'cash';

$total_price = 0;
foreach ($_SESSION['cart'] as $item) {
    $total_price += $item['price'] * $item['quantity'];
}

try {
    $pdo->beginTransaction(); 

    $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_price, name, surname, phone, address, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $total_price, $name, $surname, $phone, $address, $payment]);
    
    $order_id = $pdo->lastInsertId();

    $stmt_item = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    
    foreach ($_SESSION['cart'] as $product_id => $item) {
        $stmt_item->execute([
            $order_id, 
            $product_id, 
            $item['quantity'], 
            $item['price']
        ]);
    }

    $pdo->commit(); 

    unset($_SESSION['cart']);

    header('Location: profile.php?order_success=1');

} catch (Exception $e) {
    $pdo->rollBack();
    die("Ошибка при оформлении заказа: " . $e->getMessage());
}