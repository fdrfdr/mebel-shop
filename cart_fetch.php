<?php
session_start();
require 'includes/db.php';

if (!empty($_SESSION['cart'])) {
    $preview_ids = array_keys($_SESSION['cart']);
    $preview_placeholders = str_repeat('?,', count($preview_ids) - 1) . '?';
    
    $stmt_mini = $pdo->prepare("SELECT id, name, price, image FROM products WHERE id IN ($preview_placeholders)");
    $stmt_mini->execute($preview_ids);
    $products_mini = $stmt_mini->fetchAll();

    echo '<div class="cart-items-wrapper">';

    foreach ($products_mini as $p_mini): 
        $rawPath = str_replace('images/', '', $p_mini['image']);
        $img_mini = (strpos($p_mini['image'], 'http') === 0) ? $p_mini['image'] : 'images/'.$rawPath;
        $current_qty = $_SESSION['cart'][$p_mini['id']];
    ?>
        <div class="d-flex align-items-center mb-3 position-relative">
            <img src="<?= $img_mini ?>" 
                 class="rounded border" 
                 style="width: 45px; height: 45px; object-fit: cover;"
                 onerror="this.src='images/no-image.png'">
            <div class="ms-2 flex-grow-1">
                <div class="small fw-bold text-truncate" style="max-width: 140px;"><?= htmlspecialchars($p_mini['name']) ?></div>
                <div class="small text-muted"><?= $current_qty ?> шт. × <?= number_format($p_mini['price'], 0, '.', ' ') ?> ₽</div>
            </div>
            <button onclick="event.stopPropagation(); removeFromCart(<?= $p_mini['id'] ?>, 1)" 
                    class="btn btn-link text-danger p-0 ms-2" title="Уменьшить">
                <i class="bi bi-dash-circle"></i>
            </button>
        </div>
    <?php endforeach;

    echo '</div>'; 
    ?>
    <div class="border-top mt-2 pt-2">
        <button onclick="event.stopPropagation(); clearCart()" 
                class="btn btn-sm btn-outline-danger w-100 border-0 py-2" 
                style="font-size: 0.85rem; background-color: #fff5f5;">
            <i class="bi bi-trash3 me-1"></i> Очистить корзину полностью
        </button>
    </div>
<?php
} else {
    echo '<div class="text-center py-4">';
    echo '  <i class="bi bi-cart-x text-muted" style="font-size: 2rem;"></i>';
    echo '  <p class="small text-muted mt-2 m-0">Корзина пуста</p>';
    echo '</div>';
}
?>