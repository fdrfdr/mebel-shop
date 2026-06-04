<?php
require '../includes/db.php';
require 'inc/auth.php';

$active_tab = $_GET['tab'] ?? 'orders';

if (isset($_POST['update_stock'])) {
    $p_id = (int)$_POST['product_id'];
    $qty = (int)$_POST['quantity'];
    
    $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?")->execute([$qty, $p_id]);
    header("Location: warehouse.php?tab=stock&success=1"); 
    exit;
}

if (isset($_POST['complete_packing'])) {
    $order_id = (int)$_POST['order_id'];
    
    $stmt = $pdo->prepare("UPDATE orders SET status = 'processing' WHERE id = ?");
    $stmt->execute([$order_id]);
    
    $items = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
    $items->execute([$order_id]);
    foreach ($items->fetchAll() as $item) {
        $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?")
            ->execute([$item['quantity'], $item['product_id'], $item['quantity']]);
    }
    header("Location: warehouse.php?tab=orders&done=1"); 
    exit;
}

$query = $pdo->query("SELECT o.*, u.name, u.surname, u.email, u.phone FROM orders o 
                      JOIN users u ON o.user_id = u.id 
                      WHERE o.status = 'paid' 
                      ORDER BY o.created_at ASC");
$all_orders = $query->fetchAll();

$grouped_orders = [];
foreach ($all_orders as $order) {
    $grouped_orders[$order['email']][] = $order;
}

$products = $pdo->query("SELECT * FROM products ORDER BY stock ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Терминал Склада | FurnitureShop</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --yandex-yellow: #ffcc00; --dark-bg: #1e1e2d; }
        body { background: #f0f2f5; font-family: 'Inter', sans-serif; }
        
        .warehouse-header {
            background: var(--dark-bg);
            color: white;
            padding: 1rem 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .nav-pills .nav-link { color: #6c757d; border-radius: 12px; font-weight: 600; padding: 10px 25px; }
        .nav-pills .nav-link.active { background: var(--yandex-yellow); color: #000; }

        .user-row { cursor: pointer; transition: background 0.2s; border-radius: 12px; border: 1px solid transparent; }
        .user-row:hover { background: #fff !important; border-color: var(--yandex-yellow); }
        .chevron-icon { transition: transform 0.3s; }
        .rotated { transform: rotate(180deg); }
        .inner-collapse { display: none; }
        
        .btn-collect { 
            background: var(--yandex-yellow); 
            color: #000; 
            border: none; 
            font-weight: 600; 
            border-radius: 10px;
        }
        
        .order-card { background: #fff; border-radius: 15px; border: none; margin-bottom: 1rem; }

        .stock-table-container { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

<header class="warehouse-header d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center">
        <i class="bi bi-box-seam fs-3 me-3 text-warning"></i>
        <div>
            <h5 class="m-0 fw-bold">FurnitureShop LOGISTICS</h5>
            <small class="text-white-50">Рабочее место: <?= htmlspecialchars($_SESSION['user_name'] ?? 'Кладовщик') ?></small>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="../index.php" class="btn btn-outline-light btn-sm rounded-pill px-3">На сайт</a>
        <a href="../logout.php" class="btn btn-danger btn-sm rounded-pill px-3">Выход</a>
    </div>
</header>

<div class="container">
    <ul class="nav nav-pills mb-4 justify-content-center bg-white p-2 rounded-4 shadow-sm mx-auto" style="max-width: fit-content;">
        <li class="nav-item">
            <a class="nav-link <?= $active_tab == 'orders' ? 'active' : '' ?>" href="?tab=orders">
                <i class="bi bi-cart-check me-2"></i>Сборка заказов
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $active_tab == 'stock' ? 'active' : '' ?>" href="?tab=stock">
                <i class="bi bi-boxes me-2"></i>Учет остатков
            </a>
        </li>
    </ul>

    <div class="row justify-content-center">
        <div class="col-lg-10">

            <?php if ($active_tab == 'orders'): ?>
                <div class="mb-4 d-flex justify-content-between align-items-end">
                    <h3 class="fw-bold m-0">Очередь на сборку</h3>
                    <span class="badge bg-dark rounded-pill">Всего клиентов: <?= count($grouped_orders) ?></span>
                </div>

                <?php if(empty($grouped_orders)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-emoji-smile fs-1 text-muted"></i>
                        <p class="mt-3 text-muted">Все заказы собраны!</p>
                    </div>
                <?php endif; ?>

                <?php foreach ($grouped_orders as $email => $items): $hash = md5($email); ?>
                    <div class="order-card shadow-sm overflow-hidden">
                        <div class="p-3 px-4 d-flex align-items-center user-row" onclick="toggleGroup('<?= $hash ?>')">
                            <div class="flex-grow-1">
                                <div class="fw-bold fs-5"><?= htmlspecialchars($items[0]['surname'] . ' ' . $items[0]['name']) ?></div>
                                <div class="text-muted small"><i class="bi bi-telephone me-1"></i><?= $items[0]['phone'] ?></div>
                            </div>
                            <div class="text-end me-4">
                                <div class="small text-muted mb-1">Заказов:</div>
                                <span class="badge bg-primary rounded-pill"><?= count($items) ?> поз.</span>
                            </div>
                            <i class="bi bi-chevron-down fs-5 chevron-icon" id="icon-<?= $hash ?>"></i>
                        </div>

                        <div class="inner-collapse border-top" id="collapse-<?= $hash ?>" style="background: #fafafa;">
                            <div class="p-3">
                                <?php foreach ($items as $o): ?>
                                    <div class="bg-white border rounded-3 p-3 mb-2 d-flex align-items-center justify-content-between">
                                        <div>
                                            <span class="badge bg-light text-dark border me-2">#<?= $o['id'] ?></span>
                                            <span class="small text-muted"><?= date('H:i', strtotime($o['created_at'])) ?></span>
                                        </div>
                                        <div class="fw-bold"><?= number_format($o['total_price'], 0, '', ' ') ?> ₽</div>
                                        <form method="POST" class="m-0">
                                            <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                            <button name="complete_packing" class="btn btn-collect btn-sm px-4">Укомплектовать</button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

            <?php else: ?>
                <div class="mb-4">
                    <h3 class="fw-bold m-0">Наличие на складе</h3>
                    <p class="text-muted small">Пополняйте запасы по мере необходимости</p>
                </div>

                <div class="stock-table-container">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Товар</th>
                                    <th>Текущий остаток</th>
                                    <th class="text-end">Добавить приход</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($products as $p): ?>
                                    <tr class="<?= $p['stock'] <= 3 ? 'table-danger' : '' ?>">
                                        <td class="fw-bold"><?= htmlspecialchars($p['name']) ?></td>
                                        <td>
                                            <span class="badge rounded-pill bg-<?= $p['stock'] > 3 ? 'secondary' : 'danger' ?> fs-6">
                                                <?= $p['stock'] ?> шт.
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" class="d-flex justify-content-end gap-2">
                                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                                <input type="number" name="quantity" class="form-control form-control-sm" placeholder="+ шт." style="width: 80px;" min="1" required>
                                                <button name="update_stock" class="btn btn-success btn-sm">
                                                    <i class="bi bi-plus-lg"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
function toggleGroup(hash) {
    const content = document.getElementById('collapse-' + hash);
    const icon = document.getElementById('icon-' + hash);
    if (content.style.display === "block") {
        content.style.display = "none";
        icon.classList.remove('rotated');
    } else {
        content.style.display = "block";
        icon.classList.add('rotated');
    }
}
</script>

</body>
</html>