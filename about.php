<?php
require 'includes/db.php';
session_start();

$settings_query = $pdo->query("SELECT * FROM settings")->fetchAll(PDO::FETCH_UNIQUE);
$site_name = $settings_query['site_name']['value'] ?? 'FedosikShop';
$phone = $settings_query['contact_phone']['value'] ?? '8 800 000-00-00';
$email = $settings_query['contact_email']['value'] ?? 'info@shop.ru';
$address = $settings_query['shop_address']['value'] ?? 'Москва, ул. Примерная, 10';

include 'includes/header.php'; 
?>

<div class="container my-5">
    <div class="row align-items-center mb-5 pb-lg-4">
        <div class="col-lg-6">
            <h1 class="fw-bold display-4 mb-4">Создаем уют в <span class="text-warning">вашем доме</span></h1>
            <p class="lead text-muted">Мы в <?= htmlspecialchars($site_name) ?> верим, что мебель — это не просто предметы интерьера, а часть вашей истории. С 2024 года мы подбираем решения, которые сочетают в себе эстетику, комфорт и долговечность.</p>
            <div class="d-flex gap-3 mt-4">
                <div class="text-center">
                    <h3 class="fw-bold mb-0">500+</h3>
                    <small class="text-muted">Товаров</small>
                </div>
                <div class="vr"></div>
                <div class="text-center">
                    <h3 class="fw-bold mb-0">10k</h3>
                    <small class="text-muted">Клиентов</small>
                </div>
                <div class="vr"></div>
                <div class="text-center">
                    <h3 class="fw-bold mb-0">2 года</h3>
                    <small class="text-muted">Гарантии</small>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mt-4 mt-lg-0">
            <img src="https://images.unsplash.com/photo-1524758631624-e2822e304c36?auto=format&fit=crop&w=800&q=80" class="img-fluid rounded-5 shadow-lg" alt="Интерьер">
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm p-4 rounded-4">
                <i class="bi bi-truck fs-1 text-warning mb-3"></i>
                <h5 class="fw-bold">Бережная доставка</h5>
                <p class="text-muted small">Мы сами упаковываем и доставляем мебель, чтобы вы получили её в идеальном состоянии.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm p-4 rounded-4">
                <i class="bi bi-gem fs-1 text-warning mb-3"></i>
                <h5 class="fw-bold">Качество материалов</h5>
                <p class="text-muted small">Используем только экологичные ткани, натуральное дерево и надежную фурнитуру.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm p-4 rounded-4">
                <i class="bi bi-headset fs-1 text-warning mb-3"></i>
                <h5 class="fw-bold">Поддержка 24/7</h5>
                <p class="text-muted small">Наши менеджеры всегда на связи, чтобы помочь вам с выбором или уточнить статус заказа.</p>
            </div>
        </div>
    </div>

    <div class="row g-0 rounded-5 overflow-hidden shadow-lg mt-5">
        <div class="col-lg-4 bg-dark text-white p-5">
            <h3 class="fw-bold mb-4">Наши контакты</h3>
            
            <div class="mb-4">
                <label class="text-warning small text-uppercase fw-bold">Адрес шоурума</label>
                <p class="mb-0"><?= htmlspecialchars($address) ?></p>
            </div>

            <div class="mb-4">
                <label class="text-warning small text-uppercase fw-bold">Телефон</label>
                <p class="mb-0"><?= htmlspecialchars($phone) ?></p>
            </div>

            <div class="mb-4">
                <label class="text-warning small text-uppercase fw-bold">Email</label>
                <p class="mb-0"><?= htmlspecialchars($email) ?></p>
            </div>

            <div class="social-links d-flex gap-3 mt-5">
                <a href="#" class="btn btn-outline-light btn-sm rounded-circle"><i class="bi bi-telegram"></i></a>
                <a href="#" class="btn btn-outline-light btn-sm rounded-circle"><i class="bi bi-whatsapp"></i></a>
                <a href="#" class="btn btn-outline-light btn-sm rounded-circle"><i class="bi bi-vk"></i></a>
            </div>
        </div>
        <div class="col-lg-8" style="min-height: 400px;">
            <div style="position:relative;overflow:hidden; height: 100%;">
                <iframe src="https://yandex.ru/map-widget/v1/?text=<?= urlencode($address) ?>&z=14" 
                        width="100%" height="100%" frameborder="0" style="position:relative; filter: grayscale(0.5);"></iframe>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>