<?php
$cart_ids = [];
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_ids[] = (int)(is_array($item) ? ($item['id'] ?? 0) : $item);
    }
}
if (isset($id)) { $cart_ids[] = (int)$id; }
if (isset($_COOKIE['last_viewed'])) { $cart_ids[] = (int)$_COOKIE['last_viewed']; }

$cart_ids = array_filter(array_unique($cart_ids), function($v) { return $v > 0; });
$exclude_list = !empty($cart_ids) ? implode(',', $cart_ids) : '0';

$history_cats = [0];
$session_user_id = $_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? null;

if ($session_user_id) {
    $hist_stmt = $pdo->prepare("
        SELECT DISTINCT p.category_id 
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        JOIN products p ON oi.product_id = p.id
        WHERE o.user_id = ? AND o.status IN ('paid', 'delivered', 'completed')
    ");
    $hist_stmt->execute([$session_user_id]);
    $res_hist = $hist_stmt->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($res_hist)) { $history_cats = array_merge($history_cats, $res_hist); }
}
$history_cats_str = implode(',', array_unique($history_cats));

$current_styles = [0]; $current_rooms = [0]; $current_cats = [0];
$avg_cart_price = 0;

if (!empty($cart_ids)) {
    $placeholders = implode(',', array_fill(0, count($cart_ids), '?'));
    $stmt = $pdo->prepare("SELECT style_id, room_id, category_id, price FROM products WHERE id IN ($placeholders)");
    $stmt->execute(array_values($cart_ids));
    $props = $stmt->fetchAll();
    
    if (count($props) > 0) {
        $total_price = 0;
        foreach ($props as $p) {
            $current_styles[] = (int)$p['style_id'];
            $current_rooms[]  = (int)$p['room_id'];
            $current_cats[]   = (int)$p['category_id'];
            $total_price += (float)$p['price'];
        }
        $avg_cart_price = $total_price / count($props);
    }
}

$final_price_limit = ($avg_cart_price > 0) ? ($avg_cart_price * 1.8) : 15000;
$styles_str = implode(',', array_unique($current_styles));
$rooms_str  = implode(',', array_unique($current_rooms));
$cats_str   = implode(',', array_unique($current_cats));

$sql = "
SELECT p.*, cl.name as color_name, cl.hex_code as color_hex,
    (
        -- 1. Ручные связи (самый высокий приоритет)
        (SELECT COUNT(*) * 150 FROM product_related pr WHERE pr.product_id IN ($exclude_list) AND pr.related_id = p.id) +
        
        -- 2. Совпадение по комнате
        (CASE WHEN p.room_id IN ($rooms_str) THEN 60 ELSE 0 END) +
        
        -- 3. ЗЕРКАЛЬНЫЕ СВЯЗИ (текущие интересы + история покупок)
        (SELECT COUNT(*) * 40 FROM attr_category_links cl_link 
         WHERE (cl_link.parent_cat_id IN ($cats_str, $history_cats_str) AND cl_link.linked_cat_id = p.category_id)
            OR (cl_link.linked_cat_id IN ($cats_str, $history_cats_str) AND cl_link.parent_cat_id = p.category_id)
        ) +
        
        -- 4. ПЕРСОНАЛЬНЫЙ БОНУС (лояльность к ранее купленным категориям)
        (CASE WHEN p.category_id IN ($history_cats_str) THEN 30 ELSE 0 END) +

        -- 5. Единство стиля
        (CASE WHEN p.style_id IN ($styles_str) THEN 50 ELSE 0 END)
    ) as relevance_score
FROM products p
LEFT JOIN attr_colors cl ON p.color_id = cl.id
WHERE p.id NOT IN ($exclude_list) 
  AND p.price <= $final_price_limit
ORDER BY relevance_score DESC, RAND()
LIMIT 4
";

$recommendations = $pdo->query($sql)->fetchAll();

if (!isset($user_wishlist)) { $user_wishlist = []; }
?>

<section class="mb-5 pt-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h3 class="fw-bold mb-0">Предложенные товары</h3>
        <a href="shop.php" class="btn btn-outline-dark rounded-pill px-4 fw-bold shadow-sm small">
            Смотреть все <i class="bi bi-arrow-right ms-2"></i>
        </a>
    </div>
    
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
        <?php foreach ($recommendations as $p_rec): 
            $img_stmt = $pdo->prepare("
                SELECT pi.image_path, pi.color_name, ac.hex_code 
                FROM product_images pi 
                LEFT JOIN attr_colors ac ON pi.color_name = ac.name
                WHERE pi.product_id = ?
            ");
            $img_stmt->execute([$p_rec['id']]);
            $raw_imgs = $img_stmt->fetchAll(PDO::FETCH_ASSOC);

            $all_images = [];
            $hex_map = [];
            if (!empty($raw_imgs)) {
                foreach ($raw_imgs as $row) {
                    $all_images[$row['color_name']][] = $row;
                    $hex_map[$row['color_name']] = $row['hex_code'];
                }
            } else {
                $c_name = $p_rec['color_name'] ?? 'Стандарт';
                $all_images[$c_name][] = ['image_path' => $p_rec['image']];
                $hex_map[$c_name] = $p_rec['color_hex'] ?? '#ddd';
            }
            
            $first_c = array_key_first($all_images);
            $p_count = count($all_images[$first_c]);
        ?>
        <div class="col">
            <div class="ym-card h-100 shadow-sm border-0 position-relative" id="product-rec-<?= $p_rec['id'] ?>" data-current-color="<?= htmlspecialchars($first_c) ?>" data-current-index="0">
                
                <?php $is_wish = in_array($p_rec['id'], $user_wishlist); ?>
                <button class="wishlist-btn position-absolute" 
                        onclick="event.preventDefault(); toggleWishlist(<?= $p_rec['id'] ?>, this)"
                        style="top: 10px; right: 10px; z-index: 10; border: none; background: white; border-radius: 50%; width: 32px; height: 32px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); display: flex; align-items: center; justify-content: center; padding: 0;">
                    <i class="bi <?= $is_wish ? 'bi-heart-fill text-danger' : 'bi-heart' ?>" style="font-size: 0.9rem;"></i>
                </button>

                <div class="ym-card-img-wrapper position-relative" style="height: 200px; overflow: hidden; background: #f8f9fa; border-radius: 8px 8px 0 0;">
                    <a href="product.php?id=<?= $p_rec['id'] ?>" class="d-block h-100">
                        <?php 
                            $path = $all_images[$first_c][0]['image_path'];
                            $src = (strpos($path, 'http') === 0) ? $path : 'images/' . str_replace('images/', '', $path);
                        ?>
                        <img src="<?= $src ?>" 
                        class="main-img w-100 h-100" 
                        id="main-img-rec-<?= $p_rec['id'] ?>" 
                        style="object-fit: cover;"
                        onerror="this.onerror=null; this.src='images/no-image.png';">
                    </a>

                    <div class="slider-dots" id="dots-rec-<?= $p_rec['id'] ?>">
                        <?php for($i=0; $i < $p_count; $i++): ?>
                            <div class="dot <?= ($i === 0) ? 'active' : '' ?>"></div>
                        <?php endfor; ?>
                    </div>
                    
                    <?php if($p_count > 1): ?>
                    <button class="carousel-control-prev border-0 bg-transparent" onclick="event.preventDefault(); moveSliderRec(<?= $p_rec['id'] ?>, -1)" style="z-index: 5;">
                        <i class="bi bi-chevron-left text-dark"></i>
                    </button>
                    <button class="carousel-control-next border-0 bg-transparent" onclick="event.preventDefault(); moveSliderRec(<?= $p_rec['id'] ?>, 1)" style="z-index: 5;">
                        <i class="bi bi-chevron-right text-dark"></i>
                    </button>
                    <?php endif; ?>
                </div>

                <div class="ym-card-body p-3 d-flex flex-column">
                    <div class="ym-price fw-bold mb-1"><?= number_format($p_rec['price'], 0, '.', ' ') ?> ₽</div>
                    <a href="product.php?id=<?= $p_rec['id'] ?>" class="text-decoration-none text-dark small mb-2 d-block text-truncate fw-medium">
                        <?= htmlspecialchars($p_rec['name']) ?>
                    </a>
                    
                    <div class="d-flex align-items-center mb-3" style="min-height: 20px;">
                        <?php foreach($all_images as $c_name => $photos): ?>
                            <span class="color-swatch border me-1" 
                                style="background: <?= $hex_map[$c_name] ?? '#ddd' ?>; width: 14px; height: 14px; border-radius: 50%; cursor: pointer;" 
                                onclick="setProductColorRec(<?= $p_rec['id'] ?>, '<?= htmlspecialchars($c_name) ?>')"
                                title="<?= htmlspecialchars($c_name) ?>">
                            </span>
                        <?php endforeach; ?>
                    </div>

                    <button class="btn btn-warning w-100 rounded-pill btn-sm fw-bold add-to-cart-btn" 
                            data-id="<?= $p_rec['id'] ?>">
                        В корзину
                    </button>
                </div>

                <script type="application/json" id="data-rec-<?= $p_rec['id'] ?>">
                    <?= json_encode($all_images) ?>
                </script>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<script>
function setProductColorRec(id, color) {
    const card = document.getElementById('product-rec-' + id);
    const dataJSON = document.getElementById('data-rec-' + id).textContent;
    const data = JSON.parse(dataJSON);
    
    card.setAttribute('data-current-color', color);
    card.setAttribute('data-current-index', 0);
    
    const imgPath = data[color][0].image_path;
    const imgElement = document.getElementById('main-img-rec-' + id);
    imgElement.src = imgPath.startsWith('http') ? imgPath : 'images/' + imgPath.replace('images/', '');
    
    const dotsContainer = document.getElementById('dots-rec-' + id);
    dotsContainer.innerHTML = '';
    data[color].forEach((_, idx) => {
        const dot = document.createElement('div');
        dot.className = 'dot' + (idx === 0 ? ' active' : '');
        dotsContainer.appendChild(dot);
    });
}

function moveSliderRec(id, dir) {
    const card = document.getElementById('product-rec-' + id);
    const data = JSON.parse(document.getElementById('data-rec-' + id).textContent);
    const color = card.getAttribute('data-current-color');
    let idx = parseInt(card.getAttribute('data-current-index')) + dir;
    
    if (idx >= data[color].length) idx = 0;
    if (idx < 0) idx = data[color].length - 1;
    
    card.setAttribute('data-current-index', idx);
    const imgPath = data[color][idx].image_path;
    document.getElementById('main-img-rec-' + id).src = imgPath.startsWith('http') ? imgPath : 'images/' + imgPath.replace('images/', '');
    
    const dots = card.querySelectorAll('.dot');
    dots.forEach((d, i) => d.classList.toggle('active', i === idx));
}
</script>