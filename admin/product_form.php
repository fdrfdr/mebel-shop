<?php
require '../includes/db.php';
require 'inc/auth.php';

function ucFirstRussian($str) {
    return mb_strtoupper(mb_substr($str, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($str, 1, null, 'UTF-8');
}

$product = [
    'id' => '', 
    'name' => '', 
    'price' => '', 
    'category_id' => '', 
    'description' => '', 
    'style_id' => '', 
    'material_id' => '', 
    'room_id' => ''
];
$colors_data = [];

$categories = $pdo->query("SELECT id, name FROM attr_categories ORDER BY name ASC")->fetchAll();
$styles     = $pdo->query("SELECT id, name FROM attr_styles ORDER BY name ASC")->fetchAll();
$materials  = $pdo->query("SELECT id, name FROM attr_materials ORDER BY name ASC")->fetchAll();
$rooms      = $pdo->query("SELECT id, name FROM attr_rooms ORDER BY name ASC")->fetchAll();
$all_colors = $pdo->query("SELECT id, name FROM attr_colors ORDER BY name ASC")->fetchAll();

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $res = $stmt->fetch();
    if ($res) $product = $res;

    $img_stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY id ASC");
    $img_stmt->execute([$_GET['id']]);
    $images = $img_stmt->fetchAll();
    foreach ($images as $img) {
        $colors_data[$img['color_name']][] = $img;
    }
}

function getImgPath($path) {
    if (!$path) return '../images/no-image.png';
    if (strpos($path, 'http') === 0) return $path;
    return '../images/' . str_replace('images/', '', $path);
}

$related_ids = [];
if ($product['id']) {
    $current_related = $pdo->prepare("SELECT related_id FROM product_related WHERE product_id = ?");
    $current_related->execute([$product['id']]);
    $related_ids = $current_related->fetchAll(PDO::FETCH_COLUMN);
}

$all_products_list = $pdo->query("SELECT id, name FROM products ORDER BY name ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Товар | <?= $product['id'] ? 'Редактирование' : 'Новый' ?></title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/bootstrap-icons.min.css">
    <link rel="icon" type="image/png" href="../images/icons/906343.png">
    <style>
        :root { --yandex-yellow: #ffcc00; }
        .color-group { background: #fff; border: 1px solid #dee2e6; border-radius: 15px; padding: 20px; margin-bottom: 20px; transition: 0.3s; }
        .color-group:hover { border-color: var(--yandex-yellow); box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .preview-img { width: 65px; height: 65px; object-fit: cover; border-radius: 10px; border: 1px solid #eee; }
        .card { border-radius: 20px; border: none; }
        .form-label { font-size: 0.75rem; letter-spacing: 0.5px; }
        .btn-primary { background: var(--yandex-yellow); border: none; color: #000; font-weight: bold; }
        .btn-primary:hover { background: #e6b800; color: #000; }
    </style>
</head>
<body class="bg-light">

<div class="container py-5">
    <form action="product_save.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="products.php" class="btn btn-outline-dark rounded-pill px-4"><i class="bi bi-arrow-left"></i> Назад</a>
            <h2 class="fw-bold m-0"><?= $product['id'] ? 'Редактирование ID: #'.$product['id'] : 'Новый товар' ?></h2>
            <button type="submit" class="btn btn-primary px-5 rounded-pill shadow-sm fw-bold">Сохранить</button>
        </div>

        <div class="row">
            <div class="col-md-7">
                <div class="card shadow-sm p-4 mb-4">
                    <h5 class="mb-4 fw-bold text-uppercase border-bottom pb-2">Основная информация</h5>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted">НАЗВАНИЕ ТОВАРА</label>
                        <input type="text" name="name" class="form-control form-control-lg" value="<?= htmlspecialchars($product['name']) ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-muted">ЦЕНА (₽)</label>
                            <input type="number" name="price" class="form-control" value="<?= $product['price'] ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-muted">КАТЕГОРИЯ</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Выберите категорию</option>
                                <?php foreach($categories as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= $product['category_id'] == $c['id'] ? 'selected' : '' ?>>
                                        <?= ucFirstRussian(htmlspecialchars($c['name'])) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-bold text-muted">ПОДРОБНОЕ ОПИСАНИЕ</label>
                        <textarea name="description" class="form-control" rows="6"><?= htmlspecialchars($product['description']) ?></textarea>
                    </div>
                </div>

                <div class="card shadow-sm p-4">
                    <h5 class="mb-4 fw-bold text-uppercase border-bottom pb-2">Характеристики</h5>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold text-muted">СТИЛЬ</label>
                            <select name="style_id" class="form-select">
                                <option value="">Не выбран</option>
                                <?php foreach($styles as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= $product['style_id'] == $s['id'] ? 'selected' : '' ?>>
                                        <?= ucFirstRussian(htmlspecialchars($s['name'])) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold text-muted">МАТЕРИАЛ</label>
                            <select name="material_id" class="form-select">
                                <option value="">Не выбран</option>
                                <?php foreach($materials as $m): ?>
                                    <option value="<?= $m['id'] ?>" <?= $product['material_id'] == $m['id'] ? 'selected' : '' ?>>
                                        <?= ucFirstRussian(htmlspecialchars($m['name'])) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold text-muted">КОМНАТА</label>
                            <select name="room_id" class="form-select">
                                <option value="">Не выбран</option>
                                <?php foreach($rooms as $r): ?>
                                    <option value="<?= $r['id'] ?>" <?= $product['room_id'] == $r['id'] ? 'selected' : '' ?>>
                                        <?= ucFirstRussian(htmlspecialchars($r['name'])) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>


                <div class="card shadow-sm p-4 mt-4" style="border: 1px solid #ffcc00;">
                    <h5 class="mb-3 fw-bold text-uppercase border-bottom pb-2">
                        <i class="bi bi-star-fill text-warning"></i> Интеллектуальные связи
                    </h5>
                    <p class="text-muted small">Выберите конкретные товары, которые будут рекомендоваться к этому товару в первую очередь.</p>
                    
                    <?php for ($i = 0; $i < 3; $i++): ?>
                        <div class="mb-2">
                            <select name="related_products[]" class="form-select form-select-sm">
                                <option value="0">-- Не выбрано (автоподбор) --</option>
                                <?php foreach ($all_products_list as $item): 
                                    if ($item['id'] == $product['id']) continue; // Не рекомендовать самого себя
                                ?>
                                    <option value="<?= $item['id'] ?>" <?= (isset($related_ids[$i]) && $related_ids[$i] == $item['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($item['name']) ?> (ID: <?= $item['id'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endfor; ?>
                </div>


            </div>

            <div class="col-md-5">
                <h5 class="mb-3 fw-bold text-uppercase">Цветовые решения</h5>
                <div id="colors-container">
                    <?php if (empty($colors_data)): ?>
                        <div class="color-group shadow-sm">
                            <label class="form-label fw-bold text-muted">ЦВЕТ</label>
                            <select name="colors[]" class="form-select mb-3" required>
                                <?php foreach($all_colors as $cl): ?>
                                    <option value="<?= htmlspecialchars($cl['name']) ?>">
                                        <?= ucFirstRussian(htmlspecialchars($cl['name'])) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label class="form-label fw-bold text-muted">ЗАГРУЗИТЬ ФОТО</label>
                            <input type="file" name="images[0][]" class="form-control" multiple>
                            <input type="hidden" name="color_indexes[]" value="0">
                        </div>
                    <?php else: ?>
                        <?php $idx = 0; foreach ($colors_data as $color_name => $imgs): ?>
                            <div class="color-group shadow-sm">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <select name="colors[]" class="form-select form-select-sm fw-bold border-0 bg-light">
                                        <?php foreach($all_colors as $cl): ?>
                                            <option value="<?= htmlspecialchars($cl['name']) ?>" <?= $color_name == $cl['name'] ? 'selected' : '' ?>>
                                                <?= ucFirstRussian(htmlspecialchars($cl['name'])) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-link text-danger p-0 ms-2" onclick="this.closest('.color-group').remove()">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </div>
                                
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <?php foreach($imgs as $img): ?>
                                        <div class="position-relative">
                                            <img src="<?= getImgPath($img['image_path']) ?>" class="preview-img shadow-sm">
                                            <a href="delete_image.php?id=<?= $img['id'] ?>&product_id=<?= $product['id'] ?>" 
                                               class="btn btn-danger btn-sm position-absolute top-0 end-0 rounded-circle p-0" 
                                               style="width:20px; height:20px; transform: translate(30%, -30%);"
                                               onclick="confirmDeleteImage(event, this)">
                                                <i class="bi bi-x" style="font-size: 14px; vertical-align: top;"></i>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <input type="file" name="images[<?= $idx ?>][]" class="form-control form-control-sm" multiple>
                                <input type="hidden" name="color_indexes[]" value="<?= $idx ?>">
                            </div>
                        <?php $idx++; endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <button type="button" class="btn btn-outline-dark w-100 rounded-pill py-3 border-2 fw-bold mb-4" onclick="addColorBlock()">
                    <i class="bi bi-plus-circle-fill me-2"></i> Добавить вариант цвета
                </button>
            </div>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let colorIndex = <?= !empty($colors_data) ? count($colors_data) : 1 ?>;

const colorsOptions = `<?php foreach($all_colors as $cl): ?><option value="<?= htmlspecialchars($cl['name']) ?>"><?= ucFirstRussian(htmlspecialchars($cl['name'])) ?></option><?php endforeach; ?>`;

function addColorBlock() {
    const html = `
        <div class="color-group shadow-sm border-warning animate__animated animate__fadeIn">
            <div class="d-flex justify-content-between mb-3">
                <select name="colors[]" class="form-select form-select-sm fw-bold border-0 bg-light">${colorsOptions}</select>
                <button type="button" class="btn btn-link text-danger p-0 ms-2" onclick="this.closest('.color-group').remove()">
                    <i class="bi bi-trash-fill"></i>
                </button>
            </div>
            <label class="form-label fw-bold text-muted small">ВЫБЕРИТЕ ФОТО</label>
            <input type="file" name="images[${colorIndex}][]" class="form-control form-control-sm" multiple>
            <input type="hidden" name="color_indexes[]" value="${colorIndex}">
        </div>`;
    document.getElementById('colors-container').insertAdjacentHTML('beforeend', html);
    colorIndex++;
}

function confirmDeleteImage(event, element) {
    event.preventDefault();
    const url = element.getAttribute('href');
    Swal.fire({
        title: 'Удалить фото?',
        text: "Это действие нельзя отменить",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#000',
        confirmButtonText: 'Удалить',
        cancelButtonText: 'Отмена',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });
}
</script>
</body>
</html>