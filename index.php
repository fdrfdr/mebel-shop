<?php
require 'includes/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'includes/header.php';

if (!function_exists('getSwatchColor')) {
    function getSwatchColor($colorName) {
        $colors = ['черный' => '#212121', 'белый' => '#ffffff', 'синий' => '#0d6efd', 'серый' => '#6c757d', 'бежевый' => '#F5F5DC'];
        return $colors[mb_strtolower($colorName)] ?? '#ccc';
    }
}
?>

<div class="container mt-4">

<div id="heroCarousel" class="carousel slide mb-5 shadow-lg rounded-5" data-bs-ride="carousel">
    <div class="carousel-indicators" style="z-index: 10;">
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active"></button>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2"></button>
    </div>

    <div class="carousel-inner rounded-5">
        <div class="carousel-item active" style="height: 500px;">
            <div class="position-absolute w-100 h-100" style="background: linear-gradient(90deg, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.2) 100%), url('https://images.unsplash.com/photo-1583847268964-b28dc8f51f92?auto=format&fit=crop&w=1600&q=80') center/cover;"></div>
            <div class="container h-100 hero-content">
                <div class="row h-100 align-items-center">
                    <div class="col-lg-6 text-white ps-5">
                        <span class="badge bg-warning text-dark mb-3 px-3 py-2 rounded-pill fw-bold">Новое поступление</span>
                        <h1 class="display-4 fw-bold mb-3">Уют вашей мечты</h1>
                        <p class="lead mb-4 opacity-75">Минимализм и функциональность в каждой детали.</p>
                        <a href="shop.php" class="btn btn-warning btn-lg px-5 rounded-pill fw-bold shadow-sm">В каталог</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="carousel-item" style="height: 500px;">
            <div class="position-absolute w-100 h-100" style="background: linear-gradient(90deg, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0) 100%), url('https://images.unsplash.com/photo-1538688525198-9b88f6f53126?auto=format&fit=crop&w=1600&q=80') center/cover;"></div>
            <div class="container h-100 hero-content">
                <div class="row h-100 align-items-center text-white ps-5">
                    <div class="col-lg-6">
                        <h1 class="display-4 fw-bold mb-3 text-warning">Промышленный Лофт</h1>
                        <p class="lead mb-4">Грубое дерево и безупречный комфорт.</p>
                        <a href="shop.php?search=лофт" class="btn btn-outline-light btn-lg px-5 rounded-pill fw-bold">Смотреть</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="carousel-item" style="height: 500px;">
            <div class="position-absolute w-100 h-100" style="background: linear-gradient(90deg, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0) 100%), url('https://images.unsplash.com/photo-1598300042247-d088f8ab3a91?auto=format&fit=crop&w=1600&q=80') center/cover;"></div>
            <div class="container h-100 hero-content">
                <div class="row h-100 align-items-center text-white ps-5">
                    <div class="col-lg-6">
                        <span class="badge bg-info text-white mb-3 px-3 py-2 rounded-pill fw-bold">Хит продаж</span>
                        <h1 class="display-4 fw-bold mb-3">Скандинавский стиль</h1>
                        <p class="lead mb-4">Светлые тона и природные материалы для вашего дома.</p>
                        <a href="shop.php?style_ids%5B%5D=1" class="btn btn-info text-white btn-lg px-5 rounded-pill fw-bold border-0 shadow-sm" style="background-color: #5bc0de;">Выбрать мебель</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
    </button>
</div>

<section class="mb-5">
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <h2 class="fw-bold mb-0">Топ продаж</h2>
            <p class="text-muted small mb-0">Популярные модели месяца</p>
        </div>
        <a href="shop.php" class="btn btn-outline-dark btn-sm rounded-pill px-4">Все товары</a>
    </div>
    <div class="row g-4">
        <?php
        $stmt = $pdo->query("SELECT * FROM products LIMIT 4");
        while($product = $stmt->fetch()):
            // Проверка: если в базе пусто или файл не указан, сразу ставим заглушку
            $image_path = !empty($product['image']) ? $product['image'] : 'no-image.png';
            $img = (strpos($image_path, 'http') === 0) ? $image_path : 'images/'.$image_path;
        ?>
        <div class="col-6 col-md-3">
            <div class="card h-100 border-0 bg-transparent product-hover">
                <div class="position-relative overflow-hidden rounded-4 mb-3">
                    <img src="<?= $img ?>" 
                         class="card-img p-0" 
                         style="height: 300px; object-fit: cover;"
                         onerror="this.onerror=null; this.src='images/no-image.png';">
                    
                    <div class="product-overlay d-flex align-items-center justify-content-center">
                        <a href="product.php?id=<?= $product['id'] ?>" class="btn btn-light rounded-pill btn-sm fw-bold shadow-sm px-3">Подробнее</a>
                    </div>
                </div>
                <div class="card-body p-0 text-center text-md-start">
                    <h6 class="fw-bold mb-1 text-truncate"><?= htmlspecialchars($product['name']) ?></h6>
                    <p class="text-warning fw-bold mb-0"><?= number_format($product['price'], 0, '.', ' ') ?> ₽</p>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</section>

    <section class="row g-4 mb-5 text-center bg-white py-4 rounded-5 shadow-sm border mx-0">
        <div class="col-md-3 col-6">
            <i class="bi bi-truck fs-1 text-warning mb-2 d-block"></i>
            <h6 class="fw-bold">Доставка</h6>
            <p class="small text-muted mb-0">По всей Москве</p>
        </div>
        <div class="col-md-3 col-6">
            <i class="bi bi-shield-check fs-1 text-warning mb-2 d-block"></i>
            <h6 class="fw-bold">Качество</h6>
            <p class="small text-muted mb-0">Гарантия 2 года</p>
        </div>
        <div class="col-md-3 col-6">
            <i class="bi bi-hand-thumbs-up fs-1 text-warning mb-2 d-block"></i>
            <h6 class="fw-bold">Примерка</h6>
            <p class="small text-muted mb-0">Возврат 30 дней</p>
        </div>
        <div class="col-md-3 col-6">
            <i class="bi bi-headset fs-1 text-warning mb-2 d-block"></i>
            <h6 class="fw-bold">24/7</h6>
            <p class="small text-muted mb-0">Всегда на связи</p>
        </div>
    </section>

    <div class="row g-4 mb-5">
        <div class="col-md-6">
            <div class="p-5 rounded-5 text-white h-100 shadow-sm" style="background: #ff5c5c;">
                <h2 class="fw-bold">Скидка -15%</h2>
                <p>Промокод: <strong>WELCOME</strong></p>
                <a href="shop.php" class="btn btn-light rounded-pill px-4 fw-bold">В каталог</a>
            </div>
        </div>
        <div class="col-md-6">
            <div class="p-5 rounded-5 h-100 border bg-light shadow-sm text-dark">
                <h2 class="fw-bold">Доставка 0 ₽</h2>
                <p class="text-muted">От 50 000 ₽</p>
                <a href="shop.php" class="btn btn-dark rounded-pill px-4 fw-bold">Подробнее</a>
            </div>
        </div>
    </div>

    <div class="row g-3 justify-content-center mb-5 text-center">
        <?php
        $categories = [
            ['name' => 'Диваны', 'icon' => 'bi-house-fill', 'f' => 'диван'],
            ['name' => 'Стулья', 'icon' => 'bi-layout-three-columns', 'f' => 'стул'],
            ['name' => 'Лампы', 'icon' => 'bi-lightbulb', 'f' => 'лампа'],
            ['name' => 'Декор', 'icon' => 'bi-flower1', 'f' => 'декор'], 
            ['name' => 'Столы', 'icon' => 'bi-grid-1x2', 'f' => 'стол'],
        ];
        foreach ($categories as $cat): ?>
            <div class="col-4 col-md-2">
                <a href="shop.php?search=<?= urlencode($cat['f']) ?>" class="text-decoration-none shadow-sm p-3 bg-white rounded-4 d-flex flex-column align-items-center transition-up h-100 border text-dark">
                    <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px;">
                        <i class="bi <?= $cat['icon'] ?> fs-4 text-warning"></i>
                    </div>
                    <span class="small fw-bold"><?= $cat['name'] ?></span>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <section class="mb-5 bg-white p-4 p-md-5 rounded-5 border shadow-sm">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h2 class="fw-bold mb-4">FurnitureShop — эксперт в интерьере</h2>
                <p class="text-muted lh-lg">Мы верим, что дом — это отражение вашего характера. В FurnitureShop мы отбираем лучшее: от дубовых столов до изящных светильников.</p>
                <p class="text-muted lh-lg">Наша миссия — сделать качественный дизайн доступным. Работаем напрямую с фабриками, обеспечивая честные цены.</p>
            </div>
            <div class="col-lg-6">
                <div class="row g-2">
                    <div class="col-6"><img src="https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=400&q=80" class="img-fluid rounded-4 shadow-sm" alt="1"></div>
                    <div class="col-6"><img src="https://images.unsplash.com/photo-1524758631624-e2822e304c36?auto=format&fit=crop&w=400&q=80" class="img-fluid rounded-4 shadow-sm" alt="2"></div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/recommendations.php'; ?>

<?php require 'includes/footer.php'; ?>