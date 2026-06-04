<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    echo "<script>location.href='login.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $surname = trim($_POST['surname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    $clean_phone = preg_replace('/[^0-9]/', '', $phone);
    if (!empty($phone) && !preg_match('/^(7|8)\d{10}$/', $clean_phone)) {
        $_SESSION['profile_error'] = "Укажите корректный номер телефона (11 цифр).";
        header("Location: profile.php?edit=phone"); 
        exit;
    }

    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $new_name = "user_" . $user_id . "_" . time() . "." . $ext;
            $upload_dir = 'uploads/avatars/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_dir . $new_name)) {
                $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?")->execute([$new_name, $user_id]);
            }
        }
    }

    $stmt = $pdo->prepare("UPDATE users SET name = ?, surname = ?, email = ?, phone = ? WHERE id = ?");
    if ($stmt->execute([$name, $surname, $email, $phone, $user_id])) {
        $_SESSION['user_name'] = $name; 
        $_SESSION['profile_success'] = "Профиль успешно обновлен!"; 

        header("Location: profile.php");
        exit;
    }
}

require 'includes/header.php';


$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// --- ГЕНЕРАЦИИ АВАТАРКИ (Discord Style) ---
function getAvatar($user) {
    if (!empty($user['avatar']) && file_exists('uploads/avatars/' . $user['avatar'])) {
    return '<img src="uploads/avatars/' . htmlspecialchars($user['avatar']) . '" 
                 class="w-100 h-100" 
                 style="object-fit: cover;" 
                 onerror="this.style.display=\'none\'; this.parentElement.innerHTML=\'<div class=\\\'d-flex align-items-center justify-content-center w-100 h-100 text-white fw-bold display-4\\\' style=\\\'background-color: #5865F2;\\\'>?</div>\'">';
    }
    
    // Если фото нет — генерируем цветную заглушку
    $colors = ['#5865F2', '#EB459E', '#FEE75C', '#57F287', '#ED4245', '#9b59b6', '#3498db', '#1abc9c'];
    
    $colorIndex = $user['id'] % count($colors);
    $bgColor = $colors[$colorIndex];
    
    // Первая буква имени 
    $u_name = !empty($user['name']) ? $user['name'] : ($user['login'] ?? 'U'); 
    $initial = mb_substr($u_name, 0, 1, "UTF-8");
    
    return '<div class="d-flex align-items-center justify-content-center w-100 h-100 text-white fw-bold display-4" style="background-color: '.$bgColor.';">'.strtoupper($initial).'</div>';
}
?>
<style>
.nav-pills .nav-link { color: #555; transition: 0.3s; }
.nav-pills .nav-link.active { background-color: #ffcc00 !important; color: #000 !important; }
.nav-pills .nav-link:hover:not(.active) { background-color: #eee; }
</style>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <?php 
            
            $profile_error = $_SESSION['profile_error'] ?? '';
            unset($_SESSION['profile_error']); 
            ?>

            <?php if (isset($_GET['order_success'])): ?>
                <div class="alert alert-success rounded-4 border-0 shadow-sm mb-4 animate__animated animate__fadeInUp">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-bag-check-fill fs-3 me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-0">Заказ успешно оформлен!</h6>
                            <small>Менеджер свяжется с вами в ближайшее время.</small>
                        </div>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            <?php endif; ?>

            <?php if($profile_error): ?>
                <div class="alert alert-danger rounded-4 mb-4 border-0 shadow-sm">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($profile_error) ?>
                </div>
            <?php endif; ?>

            <?php if($message): ?>
                <div class="alert alert-success rounded-4 mb-4">
                    <i class="bi bi-check-circle-fill me-2"></i> <?= $message ?>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm rounded-5 overflow-hidden">
                <div class="bg-light p-4 text-center border-bottom">
                    <div class="position-relative d-inline-block mb-3">
                        <div class="rounded-circle overflow-hidden shadow-sm border border-4 border-white position-relative" 
                             style="width: 120px; height: 120px;">
                            <?= getAvatar($user) ?>
                        </div>
                        
                        <label for="avatarUpload" class="position-absolute bottom-0 end-0 bg-warning text-dark rounded-circle p-2 shadow-sm" 
                               style="cursor: pointer; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-camera-fill small"></i>
                        </label>
                    </div>
                    <h3 class="fw-bold mb-1">
                        <?= htmlspecialchars($user['name'] ?? 'Пользователь') ?> <?= htmlspecialchars($user['surname'] ?? '') ?>
                    </h3>
                    <p class="text-muted"><?= htmlspecialchars($user['email']) ?></p>
                </div>

                <div class="card-body p-4 p-md-5">
                    <h5 class="fw-bold mb-4">Личные данные</h5>
                    
                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="file" name="avatar" id="avatarUpload" class="d-none" onchange="previewImage(this)">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">Имя</label>
                                <input type="text" name="name" class="form-control rounded-3 py-2 bg-light border-0" 
                                value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">Фамилия</label>
                                <input type="text" name="surname" class="form-control rounded-3 py-2 bg-light border-0" 
                                value="<?= htmlspecialchars($user['surname'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">Email</label>
                                <input type="email" name="email" class="form-control rounded-3 py-2 bg-light border-0" 
                                       value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">Телефон</label>
                                <input type="tel" id="phoneInput" name="phone" class="form-control rounded-3 py-2 bg-light border-0" 
                                       value="<?= htmlspecialchars($user['phone']) ?>" placeholder="+7">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-dark rounded-pill px-4 py-2 fw-bold">
                                Сохранить изменения
                            </button>
                        </div>
                    </form>
                </div>
            </div>
           <ul class="nav nav-pills mt-5 mb-4 bg-light p-2 rounded-pill d-inline-flex border shadow-sm" id="profileTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active rounded-pill fw-bold px-4" data-bs-toggle="pill" data-bs-target="#tab-orders">
                        <i class="bi bi-bag-check me-2"></i>Заказы
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link rounded-pill fw-bold px-4" data-bs-toggle="pill" data-bs-target="#tab-wishlist">
                        <i class="bi bi-heart-fill me-2 text-danger"></i>Избранное
                    </button>
                </li>
            </ul>

<div class="tab-content">
<div class="tab-pane fade show active" id="tab-orders">
    <?php
    try {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        $orders = $stmt->fetchAll();
    } catch (PDOException $e) { $orders = []; }
    ?>

    <?php if (empty($orders)): ?>
        <div class="text-center py-5 bg-white border shadow-sm rounded-4 text-muted">
            <i class="bi bi-bag-x display-4 d-block mb-3 opacity-25"></i>
            <p>У вас пока нет заказов</p>
            <a href="shop.php" class="btn btn-warning rounded-pill px-4 fw-bold">В магазин</a>
        </div>
    <?php else: ?>
        <div class="accordion accordion-flush" id="ordersAccordion">
            <?php foreach($orders as $order): 
                $stmt_items = $pdo->prepare("
                    SELECT oi.*, p.name, p.image 
                    FROM order_items oi 
                    JOIN products p ON oi.product_id = p.id 
                    WHERE oi.order_id = ?
                ");
                $stmt_items->execute([$order['id']]);
                $items = $stmt_items->fetchAll();
                
                // Определение статуса для оформления
                $status = $order['status'] ?? 'new';
                $status_map = [
                    'new'        => ['text' => 'Ожидает оплаты', 'color' => 'text-muted',   'icon' => 'bi-clock', 'width' => '10%'],
                    'paid'       => ['text' => 'Оплачен',        'color' => 'text-info',    'icon' => 'bi-check-circle', 'width' => '35%'],
                    'processing' => ['text' => 'Собирается на складе', 'color' => 'text-primary', 'icon' => 'bi-box-seam-fill', 'width' => '65%'],
                    'delivered'  => ['text' => 'Доставлен',      'color' => 'text-success', 'icon' => 'bi-house-heart', 'width' => '100%'],
                    'cancelled'  => ['text' => 'Отменен',        'color' => 'text-danger',  'icon' => 'bi-x-circle', 'width' => '0%']
                ];
                $current = $status_map[$status] ?? $status_map['new'];
            ?>
                <div class="accordion-item border-0 mb-3 shadow-sm rounded-4 overflow-hidden">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed p-4" type="button" data-bs-toggle="collapse" data-bs-target="#order-<?= $order['id'] ?>">
                            <div class="d-flex justify-content-between w-100 align-items-center me-3">
                                <div>
                                    <span class="fw-bold text-dark">Заказ #<?= $order['id'] ?></span>
                                    <div class="small text-muted"><?= date('d.m.Y', strtotime($order['created_at'])) ?></div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold text-dark"><?= number_format((float)$order['total_price'], 0, '.', ' ') ?> ₽</div>
                                    <div class="small fw-bold <?= $current['color'] ?>">
                                        <?= $current['text'] ?>
                                    </div>
                                </div>
                            </div>
                        </button>
                    </h2>
                    <div id="order-<?= $order['id'] ?>" class="accordion-collapse collapse" data-bs-parent="#ordersAccordion">
                        <div class="accordion-body bg-light bg-opacity-50 p-4">
                            
                            <div class="mb-4">
                                <div class="d-flex justify-content-between mb-1 small fw-bold">
                                    <span class="<?= $current['color'] ?>">
                                        <?php if($status == 'processing'): ?>
                                            <span class="spinner-grow spinner-grow-sm me-1" role="status"></span>
                                        <?php else: ?>
                                            <i class="bi <?= $current['icon'] ?> me-1"></i>
                                        <?php endif; ?>
                                        <?= $current['text'] ?>
                                    </span>
                                    <span class="text-muted"><?= $current['width'] ?></span>
                                </div>
                                <div class="progress" style="height: 6px; border-radius: 10px;">
                                    <div class="progress-bar bg-warning progress-bar-striped progress-bar-animated" 
                                         style="width: <?= $current['width'] ?>"></div>
                                </div>
                            </div>

                            <h6 class="fw-bold mb-3 small text-uppercase text-muted">Состав заказа:</h6>
                            <div class="list-group list-group-flush rounded-3 overflow-hidden shadow-sm mb-4">
                            <?php foreach($items as $item): 
                                $order_img = !empty($item['image']) ? $item['image'] : 'no-image.png';
                                $order_img_path = (strpos($order_img, 'http') === 0) ? $order_img : 'images/'.$order_img;
                            ?>
                                <div class="list-group-item d-flex align-items-center border-0 py-2 bg-white">
                                    <img src="<?= $order_img_path ?>" 
                                        class="rounded-2 me-3 border" 
                                        style="width: 45px; height: 45px; object-fit: cover;"
                                        onerror="this.onerror=null; this.src='images/no-image.png';">
                                    <div class="flex-grow-1">
                                        <div class="small fw-bold text-truncate" style="max-width: 250px;"><?= htmlspecialchars($item['name']) ?></div>
                                        <div class="small text-muted" style="font-size: 0.8rem;">
                                            <?= (int)$item['quantity'] ?> шт. × <?= number_format((float)($item['price_at_time'] ?? 0), 0, '.', ' ') ?> ₽
                                        </div>
                                    </div>
                                    <div class="text-end fw-bold small">
                                        <?= number_format((float)($item['price_at_time'] ?? 0) * (int)$item['quantity'], 0, '.', ' ') ?> ₽
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            </div>
                            
                            <div class="row g-3 border-top pt-3">
                                <div class="col-sm-12">
                                    <div class="small text-muted mb-1">Адрес доставки:</div>
                                    <div class="small fw-bold"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($order['address'] ?? 'Самовывоз') ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="tab-pane fade" id="tab-wishlist">
        <?php
        $stmt_wish = $pdo->prepare("SELECT p.* FROM products p JOIN wishlist w ON p.id = w.product_id WHERE w.user_id = ?");
        $stmt_wish->execute([$user_id]);
        $wish_products = $stmt_wish->fetchAll();
        ?>

        <?php if (empty($wish_products)): ?>
            <div class="text-center py-5 bg-white border shadow-sm rounded-4 text-muted">
                <i class="bi bi-heart display-4 d-block mb-3 opacity-25"></i>
                <p>Список избранного пуст</p>
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-3 g-4">
                    <?php foreach ($wish_products as $product): 
                        $wish_img = !empty($product['image']) ? $product['image'] : 'no-image.png';
                        $wish_img_path = (strpos($wish_img, 'http') === 0) ? $wish_img : 'images/'.$wish_img;
                    ?>
                        <div class="col wish-card-<?= $product['id'] ?>">
                        <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden position-relative">
                            <button class="btn btn-light rounded-circle shadow-sm position-absolute top-0 end-0 m-2 z-3" 
                                    onclick="toggleWishlist(<?= $product['id'] ?>, this, true)" 
                                    style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-trash text-danger"></i>
                            </button>
                            <a href="product.php?id=<?= $product['id'] ?>">
                                <img src="<?= $wish_img_path ?>" 
                                    class="card-img-top" 
                                    style="height: 180px; object-fit: contain; padding: 15px;"
                                    onerror="this.onerror=null; this.src='images/no-image.png';">
                            </a>
                            <div class="card-body p-3">
                                <h6 class="fw-bold mb-1"><?= number_format($product['price'], 0, '.', ' ') ?> ₽</h6>
                                <p class="small text-muted text-truncate mb-0"><?= htmlspecialchars($product['name']) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
</div> 
</div> 
</div> 

<script src="https://cdn.jsdelivr.net/npm/inputmask/dist/inputmask.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Inputmask({"mask": "+7 (999) 999-99-99"}).mask(document.getElementById('phoneInput'));
    
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('edit') === 'phone') {
        const input = document.getElementById('phoneInput');
        input.focus();
        input.classList.add('is-invalid');
    }
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {

    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab');

    if (tab === 'wishlist') {
        const wishlistBtn = document.querySelector('button[data-bs-target="#tab-wishlist"]');
        const ordersBtn = document.querySelector('button[data-bs-target="#tab-orders"]');
        
        const wishlistPane = document.querySelector('#tab-wishlist');
        const ordersPane = document.querySelector('#tab-orders');

        if (wishlistBtn && ordersBtn) {
            ordersBtn.classList.remove('active');
            ordersBtn.setAttribute('aria-selected', 'false');
            ordersPane.classList.remove('show', 'active');

            wishlistBtn.classList.add('active');
            wishlistBtn.setAttribute('aria-selected', 'true');
            wishlistPane.classList.add('show', 'active');
            
            wishlistBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
});

document.addEventListener('DOMContentLoaded', function() {
    if (window.location.hash === '#tab-wishlist') {
        const wishlistTab = document.querySelector('[data-bs-target="#tab-wishlist"]');
        if (wishlistTab) {
            const tab = new bootstrap.Tab(wishlistTab);
            tab.show();
        }
    }
});

function toggleWishlist(productId, btn, isWishlistPage = false) {
    const formData = new FormData();
    formData.append('product_id', productId);

    fetch('wishlist_action.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'removed' && isWishlistPage) {
            const card = document.querySelector('.wish-card-' + productId);
            card.style.transition = '0.3s';
            card.style.opacity = '0';
            card.style.transform = 'scale(0.9)';
            setTimeout(() => card.remove(), 300);
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('edit') === 'phone') {
        const phoneInput = document.querySelector('input[name="phone"]');
        if (phoneInput) {
            phoneInput.focus();
            phoneInput.classList.add('is-invalid'); 
            phoneInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
});
</script>

<?php require 'includes/footer.php'; ?>