<?php
require 'includes/db.php';
session_start();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// echo '<pre>';
// print_r($_SESSION['cart']);
// echo '</pre>';
// die();

$cart_items = [];
$total_price = 0;

if (!empty($_SESSION['cart'])) {
    
    $ids = !empty($_SESSION['cart']) ? array_keys($_SESSION['cart']) : [];

    if (!empty($ids)) {
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $products = $stmt->fetchAll();

    foreach ($products as $product) {
        $qty = $_SESSION['cart'][$product['id']];
        $subtotal = $product['price'] * $qty;
        $total_price += $subtotal;
        
        $cart_items[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'image' => $product['image'],
            'qty' => $qty,
            'subtotal' => $subtotal
        ];
    }
    } else {
    $cart_items = []; 
    }
}

require 'includes/header.php';
?>

<div class="container py-5" >
    <div class="d-flex align-items-center mb-4">
        <h1 class="fw-bold h2 mb-0">Корзина</h1>
        <span class="badge bg-white text-dark border ms-3 rounded-pill px-3 shadow-sm">
            <?= isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0 ?> товаров
        </span>
    </div>

    <?php if (empty($cart_items)): ?>
        <div class="text-center py-5 bg-white rounded-5 shadow-sm mt-4">
            <div class="mb-4">
                <i class="bi bi-cart-x text-light" style="font-size: 100px;"></i>
            </div>
            <h3 class="fw-bold">В корзине пока пусто</h3>
            <p class="text-muted mb-4">Загляните в каталог, чтобы найти что-то интересное</p>
            <a href="shop.php" class="btn btn-yandex px-5 py-3 fw-bold rounded-pill">ПЕРЕЙТИ В КАТАЛОГ</a>
        </div>
       
    <?php else: ?>
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-body p-0">
                        <?php foreach ($cart_items as $item): 
                          
                            $image_path = !empty($item['image']) ? $item['image'] : 'no-image.png';
                            $img = (strpos($image_path, 'http') === 0) ? $image_path : 'images/'.$image_path;
                        ?>
                        <div class="p-4 border-bottom cart-item-row" id="item-<?= $item['id'] ?>">
                            <div class="row align-items-center">
                                <div class="col-3 col-md-2">
                                    <div class="rounded-3 overflow-hidden bg-light border d-flex align-items-center justify-content-center" style="height: 80px;">
                                        <img src="<?= $img ?>" 
                                            class="img-fluid" 
                                            style="mix-blend-mode: multiply; max-height: 100%; object-fit: contain;"
                                            onerror="this.onerror=null; this.src='images/no-image.png';">
                                    </div>
                                </div>
                                <div class="col-9 col-md-5">
                                    <h6 class="fw-bold mb-1 fs-5"><?= htmlspecialchars($item['name']) ?></h6>
                                    <div class="d-flex gap-2 mb-2">
                                        <span class="badge bg-light text-dark fw-normal border">В наличии</span>
                                    </div>
                                    <button class="btn btn-link btn-sm text-danger text-decoration-none p-0 fw-bold" onclick="removeFromCart(<?= $item['id'] ?>)">
                                        <i class="bi bi-trash3 me-1"></i> Удалить
                                    </button>
                                </div>
                                <div class="col-6 col-md-2 mt-3 mt-md-0">
                                    <div class="d-flex align-items-center justify-content-center border rounded-pill p-1 bg-light">
                                        <button class="btn btn-sm btn-white rounded-circle shadow-sm border-0" onclick="changeQty(<?= $item['id'] ?>, -1)" style="width:30px; height:30px;">-</button>
                                        <input type="text" class="form-control form-control-sm border-0 bg-transparent text-center fw-bold" 
                                               value="<?= $item['qty'] ?>" readonly style="width: 40px;" id="qty-<?= $item['id'] ?>">
                                        <button class="btn btn-sm btn-white rounded-circle shadow-sm border-0" onclick="changeQty(<?= $item['id'] ?>, 1)" style="width:30px; height:30px;">+</button>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3 mt-3 mt-md-0 text-end">
                                    <div class="fw-bold fs-5"><?= number_format($item['subtotal'], 0, '.', ' ') ?> ₽</div>
                                    <?php if($item['qty'] > 1): ?>
                                        <div class="small text-muted"><?= number_format($item['price'], 0, '.', ' ') ?> ₽ / шт.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 sticky-top" style="top: 100px;">
                    <h5 class="fw-bold mb-4">Детали заказа</h5>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Товары, <?= array_sum($_SESSION['cart']) ?> шт.</span>
                        <span class="fw-medium"><?= number_format($total_price, 0, '.', ' ') ?> ₽</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Скидка</span>
                        <span class="text-danger fw-medium">− 0 ₽</span>
                    </div>
                    <div class="d-flex justify-content-between mb-4">
                        <span class="text-muted">Доставка</span>
                        <span class="text-success fw-bold">Бесплатно</span>
                    </div>
                    <div class="bg-light p-3 rounded-3 mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold h5 mb-0">Итого</span>
                            <span class="fw-bold h4 mb-0 text-primary"><?= number_format($total_price, 0, '.', ' ') ?> ₽</span>
                        </div>
                    </div>
                    <button class="btn btn-yandex w-100 py-3 fw-bold rounded-pill shadow-sm" onclick="checkout()">
                        ПЕРЕЙТИ К ОФОРМЛЕНИЮ
                    </button>
                    <p class="text-center text-muted small mt-3">Способ оплаты можно выбрать при оформлении</p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="mt-3">
        <?php include 'includes/recommendations.php'; ?>
    </div>
</div>



<style>

.btn-white { background: #fff; }
.btn-white:hover { background: #f8f9fa; }
.cart-item-row { transition: background 0.2s; }
.cart-item-row:hover { background-color: #fafafa; }
.bg-yandex { background-color: #ffcc00; color: #000; }
.btn-yandex { background-color: #ffcc00; border: none; color: #000; }
.btn-yandex:hover { background-color: #f5c200; }
</style>

<script>
function changeQty(id, delta) {
    const qtyInput = document.getElementById('qty-' + id);
    let currentQty = parseInt(qtyInput.value);
    let newQty = currentQty + delta;

    if (newQty < 1) return;

    updateCart(id, newQty);
}

function removeFromCart(id) {
    Swal.fire({
        title: 'Удалить товар?',
        text: "Товар будет убран из вашей корзины",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#F5D700', 
        cancelButtonColor: '#f4f4f4',
        confirmButtonText: '<span style="color: #000">Да, удалить</span>',
        cancelButtonText: '<span style="color: #000">Отмена</span>',
        customClass: {
            popup: 'rounded-4 border-0'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            
            updateCart(id, 0); 
        }
    })
}

function updateCart(id, qty) {
    const formData = new FormData();
    formData.append('update_id', id);
    formData.append('qty', qty);

    fetch('cart_action.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            if (qty === 0) {
               
                const row = document.getElementById('item-' + id);
                row.style.opacity = '0';
                row.style.transform = 'translateX(20px)';
                setTimeout(() => { location.reload(); }, 300); 
            } else {
                location.reload(); 
            }
        }
    });
}

function checkout() {
    <?php if(!isset($_SESSION['user_id'])): ?>

        Swal.fire({
            title: 'Нужна авторизация',
            text: "Чтобы оформить заказ, пожалуйста, войдите в свой аккаунт",
            icon: 'info',
            confirmButtonColor: '#ffcc00',
            confirmButtonText: '<span style="color: #000">Войти</span>'
        }).then(() => {
            window.location.href = 'login.php';
        });
    <?php else: ?>
        
        Swal.fire({
            title: 'Оформить заказ?',
            text: 'Вы перейдете на страницу подтверждения данных',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ffcc00',
            cancelButtonColor: '#f4f4f4',
            confirmButtonText: '<span style="color: #000">Да, оформить</span>',
            cancelButtonText: '<span style="color: #000">Проверить ещё раз</span>',
            customClass: {
                popup: 'rounded-4'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'checkout.php';
            }
        });
    <?php endif; ?>
}
</script>

<?php require 'includes/footer.php'; ?>