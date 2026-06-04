<?php 
require 'includes/db.php'; 

function mb_ucfirst($str) {
    return mb_strtoupper(mb_substr($str, 0, 1)) . mb_substr($str, 1);
}

// --- ПОДГОТОВКА ДАННЫХ ---
$available_categories = $pdo->query("SELECT id, name FROM attr_categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$available_styles     = $pdo->query("SELECT id, name FROM attr_styles ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$available_rooms      = $pdo->query("SELECT id, name FROM attr_rooms ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$available_materials  = $pdo->query("SELECT id, name FROM attr_materials ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$available_colors_data = $pdo->query("SELECT id, name, hex_code FROM attr_colors ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// --- ЛОГИКА ФИЛЬТРАЦИИ ---
$params = [];
$where = [];
$search = trim($_GET['search'] ?? '');

if ($search !== '') {
    $where[] = "p.name LIKE ?";
    $params[] = "%$search%";
}

if (!empty($_GET['category_id'])) { 
    $where[] = "p.category_id = ?"; 
    $params[] = $_GET['category_id']; 
}

$selected_styles = $_GET['style_ids'] ?? [];
if (!empty($selected_styles)) {
    $placeholders = implode(',', array_fill(0, count($selected_styles), '?'));
    $where[] = "p.style_id IN ($placeholders)";
    foreach ($selected_styles as $val) $params[] = $val;
}

if (!empty($_GET['room_id'])) { 
    $where[] = "p.room_id = ?"; 
    $params[] = $_GET['room_id']; 
}

$selected_colors = $_GET['color_ids'] ?? [];
if (!empty($selected_colors)) {
    $placeholders = implode(',', array_fill(0, count($selected_colors), '?'));
    $where[] = "p.color_id IN ($placeholders)";
    foreach ($selected_colors as $val) $params[] = $val;
}

$selected_materials = $_GET['material_ids'] ?? [];
if (!empty($selected_materials)) {
    $placeholders = implode(',', array_fill(0, count($selected_materials), '?'));
    $where[] = "p.material_id IN ($placeholders)";
    foreach ($selected_materials as $val) $params[] = $val;
}

$min_price = !empty($_GET['min_price']) ? (int)$_GET['min_price'] : 0;
$max_price = !empty($_GET['max_price']) ? (int)$_GET['max_price'] : 1000000;
$where[] = "p.price BETWEEN ? AND ?";
$params[] = $min_price; $params[] = $max_price;

// --- ПАГИНАЦИЯ ---
$count_sql = "SELECT COUNT(*) FROM products p" . (count($where) ? " WHERE " . implode(" AND ", $where) : "");
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_items = $count_stmt->fetchColumn();

$limit = 6;
$total_pages = ceil($total_items / $limit);
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($current_page - 1) * $limit;

// --- СБОРКА ЗАПРОСА ---
$sql = "SELECT p.*, c.name as color_name FROM products p LEFT JOIN attr_colors c ON p.color_id = c.id";
if (count($where)) { $sql .= " WHERE " . implode(" AND ", $where); }

$sort = $_GET['sort'] ?? 'new';
switch ($sort) {
    case 'price_asc':  $sql .= " ORDER BY p.price ASC"; break;
    case 'price_desc': $sql .= " ORDER BY p.price DESC"; break;
    default:           $sql .= " ORDER BY p.id DESC"; break;
}
$sql .= " LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

require 'includes/header.php';
?>

<div class="row">
    <aside class="col-lg-3 mb-4">
        <div class="card border-0 shadow-sm p-3 sticky-top" style="top: 90px;">
            <h5 class="mb-3 fw-bold">Фильтры</h5>
            <form action="shop.php" method="GET">
                
                <div class="mb-4">
                    <label class="form-label small fw-bold">Категория</label>
                    <select name="category_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Все категории</option>
                        <?php foreach($available_categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= (($_GET['category_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>>
                                <?= mb_ucfirst(htmlspecialchars($cat['name'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">Цена, ₽</label>
                    <div class="d-flex gap-2">
                        <input type="number" name="min_price" class="form-control form-control-sm" placeholder="От" value="<?= $min_price ?>">
                        <input type="number" name="max_price" class="form-control form-control-sm" placeholder="До" value="<?= $max_price ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Стили</label>
                    <div class="filter-scroll" style="max-height: 150px; overflow-y: auto;">
                        <?php foreach($available_styles as $s): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="style_ids[]" value="<?= $s['id'] ?>" id="s_<?= $s['id'] ?>" <?= in_array($s['id'], $selected_styles) ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="s_<?= $s['id'] ?>">
                                    <?= mb_ucfirst(htmlspecialchars($s['name'])) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Комната</label>
                    <select name="room_id" class="form-select form-select-sm">
                        <option value="">Все комнаты</option>
                        <?php foreach($available_rooms as $rm): ?>
                            <option value="<?= $rm['id'] ?>" <?= (($_GET['room_id'] ?? '') == $rm['id']) ? 'selected' : '' ?>>
                                <?= mb_ucfirst(htmlspecialchars($rm['name'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Цвет</label>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach($available_colors_data as $clr): ?>
                            <input type="checkbox" name="color_ids[]" value="<?= $clr['id'] ?>" id="clr_<?= $clr['id'] ?>" class="btn-check" <?= in_array($clr['id'], $selected_colors) ? 'checked' : '' ?>>
                            <label class="btn btn-outline-dark p-0 rounded-circle" for="clr_<?= $clr['id'] ?>" style="width: 25px; height: 25px; background: <?= $clr['hex_code'] ?>; border: 1px solid #ddd;" title="<?= mb_ucfirst(htmlspecialchars($clr['name'])) ?>"></label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Материал</label>
                    <div class="filter-scroll" style="max-height: 150px; overflow-y: auto;">
                        <?php foreach($available_materials as $mat): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="material_ids[]" value="<?= $mat['id'] ?>" id="mat_<?= $mat['id'] ?>" <?= in_array($mat['id'], $selected_materials) ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="mat_<?= $mat['id'] ?>">
                                    <?= mb_ucfirst(htmlspecialchars($mat['name'])) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button type="submit" class="btn btn-dark btn-sm w-100 mt-2">Применить</button>
                <a href="shop.php" class="btn btn-link btn-sm w-100 text-muted text-decoration-none">Сбросить всё</a>
            </form>
        </div>
    </aside>

    <section class="col-lg-9 d-flex flex-column">
        <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded-3 shadow-sm">
            <span class="text-muted small">Найдено: <strong><?= $total_items ?></strong></span>
            <div class="sort-group d-flex align-items-center">
                <span class="small text-muted me-2">Сортировать:</span>
                <div class="btn-group btn-group-sm">
                    <?php 
                    $sorts = [
                        'new' => ['label' => 'Новинки', 'icon' => 'bi-stars'],
                        'price_asc' => ['label' => 'Дешевле', 'icon' => 'bi-sort-numeric-down'],
                        'price_desc' => ['label' => 'Дороже', 'icon' => 'bi-sort-numeric-up-alt']
                    ];
                    foreach ($sorts as $key => $opt): 
                        $active = ($sort == $key);
                    ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['sort' => $key, 'page' => 1])) ?>" class="btn btn-outline-secondary border-0 px-3 <?= $active ? 'active' : '' ?>" style="<?= $active ? 'background-color: #f0f0f0; color: #000; font-weight: 600;' : 'color: #777;' ?>">
                            <i class="bi <?= $opt['icon'] ?> me-1"></i> <?= $opt['label'] ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php if ($products): ?>
                <?php foreach ($products as $product): 
                    $img_stmt = $pdo->prepare("SELECT image_path, color_name FROM product_images WHERE product_id = ?");
                    $img_stmt->execute([$product['id']]);
                    $raw_images = $img_stmt->fetchAll(PDO::FETCH_ASSOC);

                    $all_images = [];
                    if (!empty($raw_images)) {
                        foreach ($raw_images as $row) { $all_images[$row['color_name']][] = $row; }
                    } else {
                        $def_c = $product['color_name'] ?? 'Стандарт';
                        $all_images[$def_c][] = ['image_path' => $product['image']];
                    }
                    $first_color = array_key_first($all_images);
                    $photos_count = count($all_images[$first_color]);
                ?>
                <div class="col">
                    <div class="ym-card" id="product-<?= $product['id'] ?>" data-current-color="<?= htmlspecialchars($first_color) ?>">
                        <div class="ym-card-img-wrapper position-relative" style="overflow: hidden; border-radius: 8px;">
                            <?php 
                                // Проверяем, есть ли товар уже в избранном 
                                $is_wish = in_array($product['id'], $user_wishlist); 
                            ?>
                            <button class="wishlist-btn position-absolute <?= $is_wish ? 'active' : '' ?>" 
                                    onclick="event.preventDefault(); toggleWishlist(<?= $product['id'] ?>, this)"
                                    style="top: 10px; right: 10px; z-index: 10; border: none; background: white; border-radius: 50%; width: 35px; height: 35px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); 
                                        display: flex; align-items: center; justify-content: center; padding: 0;">
                                <i class="bi <?= $is_wish ? 'bi-heart-fill text-danger' : 'bi-heart' ?>" 
                                style="font-size: 1.1rem; line-height: 1;"></i>
                            </button>

                            <a href="product.php?id=<?= $product['id'] ?>">
                                <?php 
                                    $raw_path = $all_images[$first_color][0]['image_path'] ?? '';
                                    $src = (strpos($raw_path, 'http') === 0) ? $raw_path : 'images/' . str_replace('images/', '', $raw_path);
                                    if (strpos($src, 'http') !== 0 && !file_exists($src)) { $src = 'images/no-image.png'; }
                                ?>
                                <img src="<?= $src ?>" class="main-img" id="main-img-<?= $product['id'] ?>" style="height: 240px; width: 100%; object-fit: cover; transition: 0.3s;">
                            </a>
                            
                            <div class="slider-dots" id="dots-<?= $product['id'] ?>" style="position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%); display: flex; gap: 5px;">
                                <?php for($i = 0; $i < $photos_count; $i++): ?>
                                    <span class="dot <?= $i === 0 ? 'active' : '' ?>" 
                                        style="width: 30px; height: 3px; background: rgba(255,255,255,0.5); border-radius: 2px; cursor: pointer;" 
                                        onclick="jumpToSlide(<?= $product['id'] ?>, <?= $i ?>)"></span>
                                <?php endfor; ?>
                            </div>

                            <button class="carousel-control-prev border-0 bg-transparent <?= ($photos_count <= 1) ? 'd-none' : '' ?>" id="prev-<?= $product['id'] ?>" style="position: absolute; top: 50%; left: 5px; transform: translateY(-50%); z-index: 2;" onclick="event.preventDefault(); moveSlider(<?= $product['id'] ?>, -1)">
                                <i class="bi bi-chevron-left fs-4 text-white"></i>
                            </button>
                            <button class="carousel-control-next border-0 bg-transparent <?= ($photos_count <= 1) ? 'd-none' : '' ?>" id="next-<?= $product['id'] ?>" style="position: absolute; top: 50%; right: 5px; transform: translateY(-50%); z-index: 2;" onclick="event.preventDefault(); moveSlider(<?= $product['id'] ?>, 1)">
                                <i class="bi bi-chevron-right fs-4 text-white"></i>
                            </button>
                        </div>

                        <div class="ym-card-body p-3">
                            <div class="ym-price fw-bold fs-5"><?= number_format($product['price'], 0, '.', ' ') ?> ₽</div>
                            <a href="product.php?id=<?= $product['id'] ?>" class="text-decoration-none text-dark d-block mb-2 small fw-medium"><?= htmlspecialchars($product['name']) ?></a>
                            
                            <div class="d-flex gap-1 mb-2">
                                <?php foreach($all_images as $color_name => $photos): 
                                    $sw_hex = '#ccc';
                                    foreach($available_colors_data as $ac) { if($ac['name'] == $color_name) $sw_hex = $ac['hex_code']; }
                                ?>
                                    <span class="color-swatch" style="background: <?= $sw_hex ?>; width:15px; height:15px; border-radius:50%; border:1px solid #ddd; cursor:pointer;" onclick="setProductColor(<?= $product['id'] ?>, '<?= htmlspecialchars($color_name) ?>')" title="<?= htmlspecialchars($color_name) ?>"></span>
                                <?php endforeach; ?>
                            </div>
                            <p class="card-text mb-1">
                                <?php if ($product['stock'] > 0): ?>
                                    <small class="text-success fw-bold">
                                        <i class="bi bi-box-seam me-1"></i> В наличии: <?= $product['stock'] ?> шт.
                                    </small>
                                <?php else: ?>
                                    <small class="text-danger fw-bold">Закончился</small>
                                <?php endif; ?>
                            </p>
                            <button class="btn btn-warning w-100 rounded-pill btn-sm fw-bold add-to-cart-btn" 
                                    data-id="<?= $product['id'] ?>">
                                В корзину
                            </button>
                        </div>
                        <script type="application/json" id="data-<?= $product['id'] ?>"><?= json_encode($all_images) ?></script>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5"><h3>Ничего не нашли :(</h3></div>
            <?php endif; ?>
        </div>

        <?php if ($total_pages > 1): ?>
        <nav class="mt-5"><ul class="pagination justify-content-center">
            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= ($current_page == $i)?'active':'' ?>"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a></li>
            <?php endfor; ?>
        </ul></nav>
        <?php endif; ?>
    </section>
</div>

<script>
document.addEventListener('error', function (event) {
    if (event.target.tagName.toLowerCase() === 'img') {
        const noImage = 'images/no-image.png';
        if (!event.target.src.includes(noImage)) { event.target.src = noImage; }
    }
}, true);

let sliderIndexes = {};

function setProductColor(productId, color) {
    const data = JSON.parse(document.getElementById('data-' + productId).textContent);
    const card = document.getElementById('product-' + productId);
    sliderIndexes[productId] = 0;
    card.setAttribute('data-current-color', color);
    renderDots(productId, data[color].length);
    updateCardImage(productId);
}

function renderDots(productId, count) {
    const dotsContainer = document.getElementById('dots-' + productId);
    dotsContainer.innerHTML = '';
    if (count <= 1) return;
    for (let i = 0; i < count; i++) {
        const dot = document.createElement('span');
        dot.style.cssText = "width: 30px; height: 3px; background: rgba(255,255,255,0.5); border-radius: 2px; cursor: pointer; margin: 0 2px; transition: 0.3s;";
        dot.className = 'dot' + (i === 0 ? ' active' : '');
        dot.onclick = () => jumpToSlide(productId, i);
        dotsContainer.appendChild(dot);
    }
}

function jumpToSlide(productId, index) {
    sliderIndexes[productId] = index;
    updateCardImage(productId);
}

function moveSlider(productId, direction) {
    const card = document.getElementById('product-' + productId);
    const color = card.getAttribute('data-current-color');
    const data = JSON.parse(document.getElementById('data-' + productId).textContent);
    const photos = data[color];
    if (!sliderIndexes[productId]) sliderIndexes[productId] = 0;
    sliderIndexes[productId] += direction;
    if (sliderIndexes[productId] >= photos.length) sliderIndexes[productId] = 0;
    if (sliderIndexes[productId] < 0) sliderIndexes[productId] = photos.length - 1;
    updateCardImage(productId);
}

function updateCardImage(productId) {
    const card = document.getElementById('product-' + productId);
    const color = card.getAttribute('data-current-color');
    const data = JSON.parse(document.getElementById('data-' + productId).textContent);
    const index = sliderIndexes[productId] || 0;
    const imgElement = document.getElementById('main-img-' + productId);
    const path = data[color][index].image_path;
    imgElement.src = (path.indexOf('http') === 0) ? path : 'images/' + path.replace('images/', '');
    const dots = document.querySelectorAll(`#dots-${productId} .dot`);
    dots.forEach((dot, i) => { dot.classList.toggle('active', i === index); });
    
    const prevBtn = document.getElementById('prev-' + productId);
    const nextBtn = document.getElementById('next-' + productId);
    if (data[color].length <= 1) {
        prevBtn?.classList.add('d-none'); nextBtn?.classList.add('d-none');
    } else {
        prevBtn?.classList.remove('d-none'); nextBtn?.classList.remove('d-none');
    }
}
</script>

<?php require 'includes/footer.php'; ?>