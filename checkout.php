<?php
require 'includes/db.php';
require 'includes/header.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (empty($_SESSION['cart'])) {
    header("Location: shop.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT phone, name, surname FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (empty($user['phone'])) {
    $_SESSION['profile_error'] = "Для оформления заказа необходимо указать номер телефона.";
    echo "<script>location.href='profile.php?edit=phone';</script>";
    exit;
}

$ids = array_keys($_SESSION['cart']);
$placeholders = str_repeat('?,', count($ids) - 1) . '?';
$stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
$stmt->execute($ids);
$products = $stmt->fetchAll();

$total_sum = 0;
foreach ($products as $p) {
    $total_sum += $p['price'] * $_SESSION['cart'][$p['id']];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    try {
        $pdo->beginTransaction();
        
        $is_pickup = isset($_POST['pickup_only']);
        $address = $is_pickup ? 'Самовывоз' : trim($_POST['address']);

        if (!$is_pickup && empty($address)) {
            throw new Exception("Пожалуйста, укажите адрес доставки или выберите самовывоз.");
        }

        // --- НОВАЯ ПРОВЕРКА СКЛАДА ПЕРЕД ЗАКАЗОМ ---
        foreach ($products as $p) {
            $requested_qty = $_SESSION['cart'][$p['id']];
            
            // Проверяем, сколько реально осталось в базе (поле stock)
            $check_stock = $pdo->prepare("SELECT stock, name FROM products WHERE id = ?");
            $check_stock->execute([$p['id']]);
            $current_product = $check_stock->fetch();

            if ($current_product['stock'] < $requested_qty) {
                throw new Exception("К сожалению, товара '{$current_product['name']}' недостаточно на складе. В наличии: {$current_product['stock']} шт.");
            }
        }
        // --- КОНЕЦ ПРОВЕРКИ ---

        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_price, address, status) VALUES (?, ?, ?, 'new')");
        $stmt->execute([$user_id, $total_sum, $address]);
        $order_id = $pdo->lastInsertId();

        $item_stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price_at_time) VALUES (?, ?, ?, ?)");
        foreach ($products as $p) {
            $item_stmt->execute([$order_id, $p['id'], $_SESSION['cart'][$p['id']], $p['price']]);
        }
        
        $pdo->commit();
        unset($_SESSION['cart']);
        echo "<script>location.href='profile.php?order_success=1';</script>";
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = $e->getMessage();
    }
}
?>

<div class="container py-5">
    <div class="row g-5">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h4 class="fw-bold mb-4">Оформление заказа</h4>
                
                <form method="POST" id="checkoutForm">
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase">Получатель</label>
                        <div class="p-3 bg-light rounded-3">
                            <i class="bi bi-person me-2"></i> <?= htmlspecialchars($user['name'] . ' ' . $user['surname']) ?><br>
                            <i class="bi bi-telephone me-2"></i> <?= htmlspecialchars($user['phone']) ?>
                        </div>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="pickup_only" id="pickupCheck" onchange="toggleAddress(this)">
                        <label class="form-check-label fw-bold" for="pickupCheck">Самовывоз из магазина</label>
                    </div>

                    <div class="mb-4" id="addressBlock">
                        <label class="form-label small fw-bold text-muted text-uppercase">Адрес доставки</label>
                        <textarea name="address" id="addressInput" class="form-control rounded-3 border-0 bg-light" 
                                rows="3" placeholder="Введите город, улицу, дом..." required></textarea>
                    </div>

                    <?php if(isset($error_message)): ?>
                        <div class="alert alert-danger py-2 small"><?= $error_message ?></div>
                    <?php endif; ?>

                    <button type="submit" name="place_order" class="btn btn-warning w-100 rounded-pill py-3 fw-bold shadow-sm">
                        ПОДТВЕРДИТЬ ЗАКАЗ
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h5 class="fw-bold mb-3">Ваш заказ</h5>
                <hr class="opacity-10">
                
                <?php foreach($products as $p): ?>
                <div class="d-flex align-items-center mb-3">
                    <?php 
                        $raw_img = !empty($p['image']) ? $p['image'] : 'no-image.png';
                        $img_path = (strpos($raw_img, 'http') === 0) ? $raw_img : 'images/' . $raw_img;
                    ?>
                    <img src="<?= $img_path ?>" 
                        class="rounded-3 me-3 border" 
                        style="width: 50px; height: 50px; object-fit: cover;"
                        onerror="this.onerror=null; this.src='images/no-image.png';">
                    
                    <div class="flex-grow-1">
                        <div class="small fw-bold text-truncate" style="max-width: 200px;"><?= htmlspecialchars($p['name']) ?></div>
                        <div class="small text-muted"><?= $_SESSION['cart'][$p['id']] ?> шт.</div>
                    </div>
                    <div class="fw-bold small"><?= number_format($p['price'] * $_SESSION['cart'][$p['id']], 0, '.', ' ') ?> ₽</div>
                </div>
                <?php endforeach; ?>

                <hr class="opacity-10">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted">Товары (<?= count($_SESSION['cart']) ?>)</span>
                    <span class="fw-bold"><?= number_format($total_sum, 0, '.', ' ') ?> ₽</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <span class="text-muted">Доставка</span>
                    <span class="text-success fw-bold">Бесплатно</span>
                </div>
                <div class="d-flex justify-content-between align-items-center border-top pt-3">
                    <h5 class="fw-bold">Итого</h5>
                    <h4 class="fw-bold text-warning"><?= number_format($total_sum, 0, '.', ' ') ?> ₽</h4>
                </div>
            </div>
        </div>
    </div>
</div>



<script>
function toggleAddress(checkbox) {
    const addressInput = document.getElementById('addressInput');
    const addressBlock = document.getElementById('addressBlock');

    if (checkbox.checked) {
        addressInput.value = ""; 
        addressInput.disabled = true;
        addressInput.required = false;
        addressBlock.style.opacity = "0.5"; 
    } else {
        addressInput.disabled = false;
        addressInput.required = true;
        addressBlock.style.opacity = "1";
    }
}
</script>
<?php require 'includes/footer.php'; ?>