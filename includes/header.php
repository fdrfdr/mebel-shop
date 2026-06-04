<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start(); 
}

$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0; 

$user_wishlist = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_wishlist = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

if (isset($pdo)) {
    $stmt = $pdo->prepare("INSERT INTO visit_logs (page_url, ip_address) VALUES (?, ?)");
    $stmt->execute([$_SERVER['REQUEST_URI'], $_SERVER['REMOTE_ADDR']]);
}

$settings_query = $pdo->query("SELECT * FROM settings")->fetchAll(PDO::FETCH_UNIQUE);
$site_name = $settings_query['site_name']['value'] ?? 'FurnitureShop';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Furniture Store</title>
    <link rel="stylesheet" href="assets/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/png" href="https://cdn-icons-png.flaticon.com/512/2603/2603741.png">
    <style>

        .offcanvas { z-index: 1060 !important; }
        .offcanvas-backdrop { z-index: 1055 !important; }
        
        @media (min-width: 992px) {
            .mobile-only { display: none !important; }
        }
        @media (max-width: 991px) {
            .desktop-only { display: none !important; }
            .offcanvas { width: 85% !important; }
        }

        .main-img {
            transition: opacity 0.2s ease-in-out;
        }
    </style>

</head>
<body>


<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top mb-4 py-2">
  <div class="container">
    <a class="navbar-brand fw-bold fs-4" href="index.php">
        <?= htmlspecialchars($site_name) ?>
    </a>
    
    <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0 fw-medium">
        <li class="nav-item"><a class="nav-link" href="shop.php">Каталог</a></li>
        <li class="nav-item"><a class="nav-link" href="about.php">О бренде</a></li>
      </ul>
      
      <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 mt-3 mt-lg-0">
        
        <div class="search-box-wrapper me-lg-2">
            <button class="btn btn-light rounded-pill border-0 w-100 text-start d-lg-none py-2 px-3" 
                    data-bs-toggle="offcanvas" data-bs-target="#offcanvasSearch">
                <i class="bi bi-search me-2"></i> Поиск товаров
            </button>
            
            <div class="search-box position-relative d-none d-lg-block" style="width: 300px;">
                <div class="input-group shadow-sm rounded-pill overflow-hidden bg-light border-0">
                    <span class="input-group-text bg-transparent border-0 ps-3"><i class="bi bi-search text-muted small"></i></span>
                    <input type="text" id="main-search" class="form-control bg-transparent border-0 py-2 shadow-none" placeholder="Поиск..." autocomplete="off">
                </div>
                <div id="search-results" class="search-dropdown shadow-lg border-0 rounded-4 w-100 mt-2 py-2" style="display: none; position: absolute; background: white; z-index:1000;"></div>
            </div>
        </div>

        <div class="dropdown">
            <button class="btn btn-light rounded-pill border-0 shadow-sm d-none d-lg-block px-3 py-2 position-relative" 
                    data-bs-toggle="dropdown" type="button">
                <i class="bi bi-heart text-danger"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger wish-badge" style="font-size: 0.6rem;">
                    <?= isset($user_wishlist) ? count($user_wishlist) : 0 ?>
                </span>
            </button>
            
            <button class="btn btn-light rounded-pill border-0 shadow-sm d-lg-none w-100 text-start px-3 py-2 position-relative" 
                    data-bs-toggle="offcanvas" data-bs-target="#offcanvasWishlist" type="button">
                <i class="bi bi-heart text-danger me-2"></i>
                <span>Избранное</span>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger wish-badge" style="font-size: 0.6rem;">
                    <?= isset($user_wishlist) ? count($user_wishlist) : 0 ?>
                </span>
            </button>

            <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 p-3" style="width: 320px;">
                <h6 class="fw-bold mb-3 small">Избранное</h6>
                <div id="wishlist-mini-list"><?php @include 'includes/wishlist_mini.php'; ?></div>
            </div>
        </div>

        <div class="dropdown ms-lg-2">
            <button class="btn btn-outline-dark border-0 shadow-sm rounded-pill d-none d-lg-block px-3 py-2 position-relative" 
                    data-bs-toggle="dropdown" type="button">
                <i class="bi bi-cart3"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger cart-badge" style="font-size: 0.6rem;">
                    <?= $cart_count ?? 0 ?>
                </span>
            </button>

            <button class="btn btn-outline-dark border-0 shadow-sm rounded-pill d-lg-none w-100 text-start px-3 py-2 position-relative" 
                    data-bs-toggle="offcanvas" data-bs-target="#offcanvasCart" type="button">
                <i class="bi bi-cart3 me-2"></i>
                <span>Корзина</span>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger cart-badge" style="font-size: 0.6rem;">
                    <?= $cart_count ?? 0 ?>
                </span>
            </button>

            <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-3" style="width: 320px; border-radius: 20px;">
                <h6 class="fw-bold mb-3 small">Корзина</h6>
                <div id="cart-mini-list"><?php @include 'cart_fetch.php'; ?></div>
                <hr><a href="cart.php" class="btn btn-warning btn-sm w-100 py-2 fw-bold rounded-pill">В КОРЗИНУ</a>
            </div>
        </div>

        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="dropdown">
                <button class="btn btn-dark rounded-pill w-100 text-start text-lg-center px-3 py-2 dropdown-toggle no-caret" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle me-2 me-lg-0"></i> <span class="d-lg-none">Профиль</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4">
                    <li><a class="dropdown-item py-2" href="profile.php">Мой профиль</a></li>
                    
                    <?php 
                    // Получаем роль из сессии (убедись, что при логине ты записываешь $_SESSION['role'] = $user['role'])
                    $userRole = $_SESSION['role'] ?? 'user'; 
                    ?>

                    <?php if ($userRole === 'admin'): ?>
                        <li><a class="dropdown-item py-2 fw-bold text-primary" href="admin/index.php">
                            <i class="bi bi-shield-lock me-2"></i>Админ-панель
                        </a></li>
                    <?php endif; ?>

                    <?php if ($userRole === 'warehouse' || $userRole === 'admin'): ?>
                        <li><a class="dropdown-item py-2 fw-bold text-success" href="admin/warehouse.php">
                            <i class="bi bi-box-seam me-2"></i>Складской учет
                        </a></li>
                    <?php endif; ?>

                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger py-2" href="logout.php">Выход</a></li>
                </ul>
            </div>
        <?php else: ?>
            <a href="login.php" class="btn btn-primary rounded-pill px-4 w-100 mt-2 mt-lg-0 fw-bold">Войти</a>
        <?php endif; ?>

      </div>
    </div>
  </div>
</nav>

<div class="offcanvas offcanvas-top h-100" tabindex="-1" id="offcanvasSearch" style="z-index: 1070;">
    <div class="offcanvas-header border-bottom py-3">
        <div class="input-group rounded-pill bg-light px-2 w-100">
            <span class="input-group-text bg-transparent border-0"><i class="bi bi-search"></i></span>
            <input type="text" id="mobile-search-input" class="form-control bg-transparent border-0 shadow-none py-2" 
                   placeholder="Что вы ищете?" autofocus>
            <button type="button" class="btn-close ms-2 mt-2" data-bs-dismiss="offcanvas"></button>
        </div>
    </div>
    <div class="offcanvas-body p-0">
        <div id="mobile-search-results" class="p-3">
            <div class="text-center py-5 text-muted">
                <i class="bi bi-search mb-2 d-block" style="font-size: 2rem; opacity: 0.3;"></i>
                <p>Начните вводить название товара</p>
            </div>
        </div>
    </div>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasWishlist">
    <div class="offcanvas-header border-bottom">
        <h5 class="fw-bold mb-0">Избранное</h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body" id="wishlist-mini-list-mobile">
        <?php include 'includes/wishlist_mini.php'; ?>
    </div>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasCart">
    <div class="offcanvas-header border-bottom">
        <h5 class="fw-bold mb-0">Корзина</h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column">
        <div class="flex-grow-1 overflow-auto" id="cart-mini-list-mobile">
            <?php include 'cart_fetch.php'; ?>
        </div>
        <div class="mt-auto pt-3 border-top">
            <a href="cart.php" class="btn btn-warning w-100 py-3 fw-bold rounded-pill">ПЕРЕЙТИ К ОФОРМЛЕНИЮ</a>
        </div>
    </div>
</div>

<main class="container py-3">