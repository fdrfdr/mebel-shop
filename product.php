<?php
require 'includes/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
setcookie('last_viewed', $id, time() + (86400 * 30), "/"); // 30 дней

$stmt = $pdo->prepare("
    SELECT p.*, 
           cat.name as category_name, 
           st.name as style_name, 
           rm.name as room_name, 
           mat.name as material_name,
           cl.name as color_name,
           cl.hex_code as color_hex
    FROM products p
    LEFT JOIN attr_categories cat ON p.category_id = cat.id
    LEFT JOIN attr_styles st      ON p.style_id = st.id
    LEFT JOIN attr_rooms rm       ON p.room_id = rm.id
    LEFT JOIN attr_materials mat  ON p.material_id = mat.id
    LEFT JOIN attr_colors cl      ON p.color_id = cl.id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    die("Товар не найден. <a href='shop.php'>Вернуться в магазин</a>");
}

$img_stmt = $pdo->prepare("
    SELECT pi.image_path, pi.color_name, cl.hex_code
    FROM product_images pi
    LEFT JOIN attr_colors cl ON pi.color_name = cl.name
    WHERE pi.product_id = ?
");
$img_stmt->execute([$id]);
$raw_images = $img_stmt->fetchAll(PDO::FETCH_ASSOC);

$images_by_color = [];
$color_hex_map = []; // Для хранения ИмяЦвета => HEX

if (!empty($raw_images)) {
    foreach ($raw_images as $row) {
        $c_name = $row['color_name'];
        $images_by_color[$c_name][] = ['image_path' => $row['image_path']];
        $color_hex_map[$c_name] = $row['hex_code'] ?? '#ddd';
    }
} else {
    $c_name = $product['color_name'] ?: 'Стандарт';
    $images_by_color[$c_name][] = ['image_path' => $product['image']];
    $color_hex_map[$c_name] = $product['color_hex'] ?? '#ddd';
}

$rec_stmt = $pdo->prepare("SELECT * FROM products WHERE (style_id = :style OR room_id = :room) AND id != :id LIMIT 4");
$rec_stmt->execute([
    'style' => $product['style_id'],
    'room' => $product['room_id'],
    'id' => $id
]);
$related = $rec_stmt->fetchAll();

$is_wishlisted = false;
if (isset($_SESSION['user_id'])) {
    $wish_stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $wish_stmt->execute([$_SESSION['user_id'], $id]);
    $is_wishlisted = (bool)$wish_stmt->fetch();
}

require 'includes/header.php';
?>

<div class="container py-5">
    <nav class="mb-5">
        <a href="shop.php" class="text-muted text-decoration-none small fw-bold">← КАТАЛОГ</a>
        <span class="mx-2 text-muted">/</span>
        <span class="small text-uppercase fw-bold"><?= htmlspecialchars($product['room_name']) ?></span>
    </nav>

    <div class="row g-5">
        <div class="col-md-7">
            <div class="main-img-box rounded-4 overflow-hidden border mb-3 bg-light" style="height: 550px;">
                <img id="main-view" src="images/no-image.png" class="w-100 h-100" style="object-fit: cover;">
            </div>
            <div id="thumb-bar" class="d-flex gap-2 flex-wrap"></div>
        </div>

        <div class="col-md-5">
            <h1 class="fw-bold h2 mb-3"><?= htmlspecialchars($product['name']) ?></h1>
            
            <div class="mb-4">
                <span class="h3 fw-bold text-danger"><?= number_format($product['price'], 0, '.', ' ') ?> ₽</span>
            </div>

            <div class="mb-4 border-top border-bottom py-3">
                <p class="small fw-bold text-uppercase mb-2">Выберите цвет: <span id="color-label" class="text-muted"></span></p>
                <div class="d-flex gap-2">
                    <?php foreach($images_by_color as $c_name => $photos): ?>
                        <div class="color-option d-flex flex-column align-items-center" 
                             onclick="selectColor('<?= htmlspecialchars($c_name) ?>')" 
                             style="cursor:pointer;">
                            <div class="color-dot" 
                                 style="width:35px; height:35px; border-radius:50%; 
                                        background:<?= $color_hex_map[$c_name] ?>; 
                                        border:2px solid #eee;">
                            </div>
                            <span class="small text-muted mt-1" style="font-size: 10px;"><?= htmlspecialchars($c_name) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="mb-4">
                <h5 class="fw-bold small text-uppercase mb-3">Характеристики</h5>
                
                <div class="row mb-1">
                    <div class="col-6 text-muted small">Стиль</div>
                    <div class="col-6 small fw-bold">
                        <?= mb_convert_case(htmlspecialchars($product['style_name']), MB_CASE_TITLE, "UTF-8") ?>
                    </div>
                </div>
                
                <div class="row mb-1">
                    <div class="col-6 text-muted small">Материал</div>
                    <div class="col-6 small fw-bold">
                        <?= mb_convert_case(htmlspecialchars($product['material_name']), MB_CASE_TITLE, "UTF-8") ?>
                    </div>
                </div>
                
                <div class="row mb-1">
                    <div class="col-6 text-muted small">Комната</div>
                    <div class="col-6 small fw-bold">
                        <?= mb_convert_case(htmlspecialchars($product['room_name']), MB_CASE_TITLE, "UTF-8") ?>
                    </div>
                </div>
            </div>

            <div class="mb-5">
                <h5 class="fw-bold small text-uppercase mb-2">Описание</h5>
                <p class="text-muted small lh-lg"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
            </div>

            <div class="d-flex gap-2 align-items-center">
                <button class="btn btn-warning w-100 py-3 fw-bold shadow-sm rounded-3" 
                        onclick="addToCart(<?= $product['id'] ?>)">
                    Добавить в корзину
                </button>
                
                <button class="btn btn-outline-danger py-3 px-4 shadow-sm rounded-3 wishlist-btn-main" 
                        onclick="toggleWishlist(<?= $product['id'] ?>, this)">
                    <i class="bi <?= $is_wishlisted ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
                </button>
            </div>
        </div>
    </div>

    <?php include 'includes/recommendations.php'; ?>
</div>

<script id="img-data" type="application/json"><?= json_encode($images_by_color) ?></script>

<script>

document.addEventListener('error', function (event) {
    if (event.target.tagName.toLowerCase() === 'img') {
        const noImage = 'images/no-image.png';
        if (!event.target.src.includes(noImage)) {
            event.target.src = noImage;
        }
    }
}, true);

try {
    const data = JSON.parse(document.getElementById('img-data').textContent);
    const mainView = document.getElementById('main-view');
    const thumbBar = document.getElementById('thumb-bar');
    const colorLabel = document.getElementById('color-label');

    function selectColor(color) {
        colorLabel.textContent = color;
        const photos = data[color];
        
        thumbBar.innerHTML = '';
        photos.forEach((p, idx) => {
            let rawPath = p.image_path.replace('images/', '');
            const url = p.image_path.startsWith('http') ? p.image_path : 'images/' + rawPath;
            
            const thumb = document.createElement('img');
            thumb.src = url;
            thumb.className = "img-thumbnail";
            thumb.style = "width:70px; height:70px; object-fit:cover; cursor:pointer; border-radius:8px; border:2px solid #eee;";
            
            thumb.onclick = () => {
                mainView.src = url;
                document.querySelectorAll('#thumb-bar img').forEach(i => i.style.borderColor = '#eee');
                thumb.style.borderColor = '#ffcc00';
            };
            
            thumbBar.appendChild(thumb);
            
            if(idx === 0) {
                mainView.src = url;
                thumb.style.borderColor = '#ffcc00';
            }
        });
    }

    const firstColor = Object.keys(data)[0];
    if(firstColor) selectColor(firstColor);

} catch(e) { 
    console.error("Ошибка инициализации галереи:", e); 
}
</script>

<?php require 'includes/footer.php'; ?>