<?php
require '../includes/db.php';
require 'inc/auth.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    $img_stmt = $pdo->prepare("
        SELECT image_path FROM product_images WHERE product_id = ?
        UNION
        SELECT image FROM products WHERE id = ?
    ");
    $img_stmt->execute([$id, $id]);
    $images = $img_stmt->fetchAll(PDO::FETCH_COLUMN);

    $upload_dir = '../images/';
    foreach ($images as $file_name) {
        if (!$file_name || $file_name == 'no-image.png') continue; // моя любимая заглушка 😊

        $full_path = $upload_dir . $file_name;

        if (file_exists($full_path)) {
            unlink($full_path);
        }
    }

   
    $pdo->prepare("DELETE FROM product_images WHERE product_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);

    header("Location: products.php?deleted=1");
    exit;
} else {
    header("Location: products.php");
    exit;
}