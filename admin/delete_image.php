<?php
require '../includes/db.php';
require 'inc/auth.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    $stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE id = ?");
    $stmt->execute([$id]);
    $img = $stmt->fetch();

    if ($img) {
        $file_path = '../images/' . $img['image_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        $pdo->prepare("DELETE FROM product_images WHERE id = ?")->execute([$id]);
    }
}

header("Location: " . $_SERVER['HTTP_REFERER']);
exit;