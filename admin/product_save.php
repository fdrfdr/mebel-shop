<?php
require '../includes/db.php';
require 'inc/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['product_id'] ?? '';
    $name = $_POST['name'];
    $price = $_POST['price'];
    $description = $_POST['description'];

    $category_id = $_POST['category_id'] ?: null;
    $style_id    = $_POST['style_id'] ?: null;
    $material_id = $_POST['material_id'] ?: null;
    $room_id     = $_POST['room_id'] ?: null;
    
    $colors = $_POST['colors']; 

    if ($id) {
        // существующий товар
        $stmt = $pdo->prepare("UPDATE products SET name=?, price=?, category_id=?, description=?, style_id=?, material_id=?, room_id=? WHERE id=?");
        $stmt->execute([$name, $price, $category_id, $description, $style_id, $material_id, $room_id, $id]);
    } else {
        // новый товар
        $stmt = $pdo->prepare("INSERT INTO products (name, price, category_id, description, style_id, material_id, room_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $price, $category_id, $description, $style_id, $material_id, $room_id]);
        $id = $pdo->lastInsertId();
    }

    $upload_dir = '../images/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    if (isset($_POST['color_indexes'])) {
        foreach ($_POST['color_indexes'] as $key => $index) {
            $color_name = $colors[$key];

            if ($key === 0) {
                $c_stmt = $pdo->prepare("SELECT id FROM attr_colors WHERE name = ?");
                $c_stmt->execute([$color_name]);
                $color_id = $c_stmt->fetchColumn();
                
                if ($color_id) {
                    $pdo->prepare("UPDATE products SET color_id = ? WHERE id = ?")->execute([$color_id, $id]);
                }
            }
            
            if (!empty($_FILES['images']['name'][$index][0])) {
                foreach ($_FILES['images']['name'][$index] as $file_key => $file_name) {
                    if ($_FILES['images']['error'][$index][$file_key] !== UPLOAD_ERR_OK) continue;

                    $tmp_name = $_FILES['images']['tmp_name'][$index][$file_key];
                    $extension = pathinfo($file_name, PATHINFO_EXTENSION);
                    $new_name = time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
                    
                    if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
                        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM product_images WHERE product_id = ? AND color_name = ? AND is_main = 1");
                        $check_stmt->execute([$id, $color_name]);
                        $has_main = $check_stmt->fetchColumn();

                        $is_main = ($has_main == 0 && $file_key === 0) ? 1 : 0;

                        $img_stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, color_name, is_main) VALUES (?, ?, ?, ?)");
                        $img_stmt->execute([$id, $new_name, $color_name, $is_main]);

                        $prod_stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
                        $prod_stmt->execute([$id]);
                        $current_main_img = $prod_stmt->fetchColumn();

                        if (!$current_main_img || $current_main_img == 'no-image.png') {
                            $pdo->prepare("UPDATE products SET image = ? WHERE id = ?")->execute([$new_name, $id]);
                        }
                    }
                }
            }
        }
    }

    if (isset($_POST['related_products'])) {

        $pdo->prepare("DELETE FROM product_related WHERE product_id = ?")->execute([$id]);
        
        $stmt_rel = $pdo->prepare("INSERT INTO product_related (product_id, related_id) VALUES (?, ?)");
        foreach ($_POST['related_products'] as $rel_id) {
            $rel_id = (int)$rel_id;
            if ($rel_id > 0) {

                $stmt_rel->execute([$id, $rel_id]);
            }
        }
    }

    header("Location: products.php?success=1");
    exit;
}