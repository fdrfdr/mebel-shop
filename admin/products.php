<?php
require '../includes/db.php';
require 'inc/auth.php';

// 1. Получаем товары с JOIN-ами ко всем справочникам
$query = $pdo->query("
    SELECT p.*, 
           c.name as cat_name,
           cl.name as color_name,
           m.name as material_name
    FROM products p 
    LEFT JOIN attr_categories c ON p.category_id = c.id 
    LEFT JOIN attr_colors cl ON p.color_id = cl.id
    LEFT JOIN attr_materials m ON p.material_id = m.id
    ORDER BY c.name ASC, p.id DESC
");
$products_all = $query->fetchAll();

// 2. Группируем по категориям
$grouped_products = [];
foreach ($products_all as $row) {
    // Используем cat_name из JOIN, если его нет — "Без категории"
    $catName = !empty($row['cat_name']) ? $row['cat_name'] : 'Без категории';
    $grouped_products[$catName][] = $row;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление товарами</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="admin-navigation.css">
    <link rel="icon" type="image/png" href="../images/icons/906343.png">
    <style>
        :root { --yandex-yellow: #ffcc00; --soft-bg: #f8f9fa; --dark-slate: #0f172a; }
        body { background: var(--soft-bg); font-family: 'Inter', system-ui, sans-serif; color: #334155; }

        .user-row { cursor: pointer; transition: 0.2s; border-left: 4px solid transparent; }
        .user-row:hover { background: #fdfdfd; }
        .user-row.active { background: #fff9e6 !important; border-left-color: var(--yandex-yellow); }

        .chevron-icon { transition: transform 0.4s; display: inline-block; }
        .user-row.active .chevron-icon { transform: rotate(180deg); }
        
        .inner-collapse { 
            height: 0; 
            overflow: hidden; 
            transition: height 0.4s ease-in-out; 
        }

        .product-img { width: 45px; height: 45px; object-fit: cover; border-radius: 8px; background: #fff; }
        .badge-cat { 
            background: #eee; 
            color: #555; 
            font-weight: 600; 
            text-transform: uppercase; 
            font-size: 0.7rem; 
            padding: 4px 8px; 
            border-radius: 6px; 
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <?php include 'inc/navbar.php'; ?>

        <main class="col-md-10 ms-sm-auto p-4 px-md-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold m-0">Управление товарами</h2>
                <a href="product_form.php" class="btn btn-warning fw-bold px-4 rounded-3 shadow-sm">
                    <i class="bi bi-plus-lg me-2"></i>Добавить товар
                </a>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <table class="table align-middle mb-0">
                    <thead class="bg-light small text-muted">
                        <tr>
                            <th class="ps-4">Категория / Название</th>
                            <th>Цена</th>
                            <th>Цвет</th>
                            <th>Материал</th>
                            <th class="text-end pe-4">Действие</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($grouped_products as $catName => $products): 
                            $hash = md5($catName); 
                        ?>
                        <tr class="user-row bg-white" id="row-<?= $hash ?>" onclick="toggleGroup('<?= $hash ?>')">
                            <td class="ps-4 py-3">
                                <div class="d-flex align-items-center">
                                    <span class="badge badge-cat me-3"><?= htmlspecialchars($catName) ?></span>
                                    <span class="fw-bold fs-6 text-dark"><?= count($products) ?> товаров</span>
                                </div>
                            </td>
                            <td colspan="3" class="text-muted small">Нажмите, чтобы развернуть список</td>
                            <td class="text-end pe-4">
                                <i class="bi bi-chevron-down chevron-icon"></i>
                            </td>
                        </tr>

                        <tr id="collapse-<?= $hash ?>" style="display: none;">
                            <td colspan="5" class="p-0 border-0">
                                <div class="inner-collapse">
                                    <div class="px-4 pb-3 bg-light-subtle">
                                        <div class="bg-white rounded-3 shadow-sm border overflow-hidden mt-3">
                                            <table class="table table-hover align-middle m-0">
                                                <tbody class="small">
                                                    <?php foreach ($products as $p): ?>
                                                    <tr>
                                                        <td class="ps-3 py-2" style="width: 60px;">
                                                            <?php 
                                                                $img_name = trim($p['image']);
                                                                $src = '../images/no-image.png'; 
                                                                if (!empty($img_name)) {
                                                                    $img_name = str_replace('images/', '', $img_name);
                                                                    if (strpos($img_name, 'http') === 0) {
                                                                        $src = $img_name;
                                                                    } elseif (file_exists('../images/' . $img_name)) {
                                                                        $src = '../images/' . $img_name;
                                                                    }
                                                                }
                                                            ?>
                                                            <img src="<?= $src ?>" class="product-img border shadow-sm" onerror="this.src='../images/no-image.png'">
                                                        </td>
                                                        <td>
                                                            <div class="fw-bold"><?= htmlspecialchars($p['name']) ?></div>
                                                            <div class="text-muted" style="font-size: 0.65rem;">ID: #<?= $p['id'] ?></div>
                                                        </td>
                                                        <td class="fw-bold text-nowrap">
                                                            <?= number_format($p['price'], 0, '', ' ') ?> ₽
                                                        </td>
                                                        <td>
                                                            <span class="badge border text-dark fw-normal">
                                                                <?= !empty($p['color_name']) ? htmlspecialchars($p['color_name']) : '—' ?>
                                                            </span>
                                                        </td>
                                                        <td class="text-muted">
                                                            <?= !empty($p['material_name']) ? htmlspecialchars($p['material_name']) : '—' ?>
                                                        </td>
                                                        <td class="text-end pe-3">
                                                            <div class="btn-group btn-group-sm shadow-sm">
                                                                <a href="product_form.php?id=<?= $p['id'] ?>" class="btn btn-white border">
                                                                    <i class="bi bi-pencil text-primary"></i>
                                                                </a>
                                                                <a href="product_delete.php?id=<?= $p['id'] ?>" class="btn btn-white border" onclick="confirmDelete(event, this)">
                                                                    <i class="bi bi-trash text-danger"></i>
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>

function confirmDelete(event, element) {
    event.preventDefault(); 
    const url = element.getAttribute('href');

    Swal.fire({
        title: 'Вы уверены?',
        text: "Это действие нельзя будет отменить!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33', 
        cancelButtonColor: '#000',
        confirmButtonText: 'Да, удалить',
        cancelButtonText: 'Отмена',
        reverseButtons: true,
        focusCancel: true 
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });
}


function toggleGroup(hash) {
    const row = document.getElementById('collapse-' + hash);
    const parentRow = document.getElementById('row-' + hash);
    const inner = row.querySelector('.inner-collapse');

    if (row.style.display === 'none' || row.style.display === '') {
        row.style.display = 'table-row';
        parentRow.classList.add('active');
        
        inner.style.height = inner.scrollHeight + "px";
        localStorage.setItem('p_group_' + hash, 'true');
    } else {
        inner.style.height = '0px';
        parentRow.classList.remove('active');
        
        setTimeout(() => { 
            row.style.display = 'none'; 
        }, 400);
        localStorage.removeItem('p_group_' + hash);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[id^="collapse-"]').forEach(row => {
        const hash = row.id.replace('collapse-', '');
        if (localStorage.getItem('p_group_' + hash) === 'true') {
            row.style.display = 'table-row';
            const inner = row.querySelector('.inner-collapse');
            if (inner) {
                inner.style.transition = 'none'; 
                inner.style.height = 'auto';
                
                setTimeout(() => { inner.style.transition = ''; }, 10);
            }
            const pRow = document.getElementById('row-' + hash);
            if(pRow) pRow.classList.add('active');
        }
    });
});
</script>
</body>
</html>