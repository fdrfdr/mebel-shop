<?php
$current_page = basename($_SERVER['PHP_SELF']);

// Подсчет новых заказов 
try {
    $stmt_count = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'new'");
    $new_orders_count = $stmt_count->fetchColumn();
} catch (Exception $e) {
    $new_orders_count = 0;
}
?>
<link rel="stylesheet" href="admin-navigation.css">

<nav class="col-md-2 d-none d-md-block sidebar position-fixed">
    <div class="d-flex align-items-center mb-4 px-2">
        <div class="bg-dark text-white rounded-3 p-2 me-2" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
            <i class="bi bi-lightning-charge-fill"></i>
        </div>
        <h4 class="fw-bold m-0" style="font-size: 1.1rem; letter-spacing: -0.5px;">FurnitureShop.Admin</h4>
    </div>

    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>" href="index.php">
                <i class="bi bi-grid-1x2"></i> Дашборд
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $current_page == 'orders.php' ? 'active' : '' ?> d-flex justify-content-between align-items-center" href="orders.php">
                <span><i class="bi bi-cart3"></i> Заказы</span>
                <?php if ($new_orders_count > 0): ?>
                    <span class="badge rounded-pill bg-danger" style="font-size: 0.7rem; padding: 4px 8px;">
                        +<?= $new_orders_count ?>
                    </span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $current_page == 'products.php' ? 'active' : '' ?>" href="products.php">
                <i class="bi bi-box-seam"></i> Товары
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $current_page == 'users.php' ? 'active' : '' ?>" href="users.php">
                <i class="bi bi-people"></i> Клиенты
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?= $current_page == 'attributes.php' ? 'active' : '' ?>" href="attributes.php">
                <i class="bi bi-tags"></i> Атрибуты
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?= $current_page == 'settings.php' ? 'active' : '' ?>" href="settings.php">
                <i class="bi bi-gear"></i> Настройки
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?= $current_page == 'category_links.php' ? 'active' : '' ?>" href="category_links.php">
                <i class="bi bi-diagram-3"></i> Рекомендации
            </a>
        </li>
        <hr>
        
        <li class="nav-item">
            <a class="nav-link text-danger" href="../logout.php">
                <i class="bi bi-box-arrow-right"></i> Выход
            </a>
        </li>
    </ul>
</nav>