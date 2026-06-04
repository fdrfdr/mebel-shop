<?php
require '../includes/db.php';
require 'inc/auth.php';

if (isset($_POST['save_all_changes'])) {
    if (!empty($_POST['status_update'])) {
        foreach ($_POST['status_update'] as $order_id => $new_status) {
            

            $check_stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
            $check_stmt->execute([$order_id]);
            $current_status = $check_stmt->fetchColumn();

            if ($new_status === 'processing' && $current_status !== 'processing') {
                
                $items_stmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
                $items_stmt->execute([$order_id]);
                $items = $items_stmt->fetchAll();

                foreach ($items as $item) {
                    $update_stock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
                    $update_stock->execute([$item['quantity'], $item['product_id'], $item['quantity']]);
                }
            }

            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $order_id]);
        }
    }
    header("Location: orders.php?saved=1");
    exit;
}

$query = $pdo->query("SELECT o.*, u.name, u.surname, u.phone, u.email 
                      FROM orders o 
                      JOIN users u ON o.user_id = u.id 
                      ORDER BY o.created_at DESC");
$grouped_orders = [];
foreach ($query->fetchAll() as $row) {
    $grouped_orders[$row['email']][] = $row;
}

// сначала те, где есть статус 'new'
uksort($grouped_orders, function($a, $b) use ($grouped_orders) {
    $aHasNew = in_array('new', array_column($grouped_orders[$a], 'status'));
    $bHasNew = in_array('new', array_column($grouped_orders[$b], 'status'));
    if ($aHasNew && !$bHasNew) return -1;
    if (!$aHasNew && $bHasNew) return 1;
    return 0;
});
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Заказы | Управление</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/bootstrap-icons.min.css">
    <link rel="icon" type="image/png" href="../images/icons/906343.png">
    <style>
        :root { --yandex-yellow: #ffcc00; --soft-bg: #f8f9fa; --dark-slate: #0f172a; }
        body { background: var(--soft-bg); font-family: 'Inter', system-ui, sans-serif; color: #334155; }

        .chevron-icon { transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1); display: inline-block; }
        .user-row { cursor: pointer; transition: background 0.3s ease; }
        .user-row.active { background: rgba(255, 204, 0, 0.08) !important; }
        .user-row.active .chevron-icon { transform: rotate(180deg); }

        .inner-collapse {
            height: 0;
            overflow: hidden;
            transition: height 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .order-scroll-area {
            max-height: 350px;
            overflow-y: auto;
            padding-right: 10px;
        }
        .order-scroll-area::-webkit-scrollbar { width: 4px; }
        .order-scroll-area::-webkit-scrollbar-thumb { background: #ddd; border-radius: 10px; }

        .bottom-actions {
            position: fixed;
            bottom: 0; right: 0; left: 16.66667%;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            padding: 20px 50px;
            border-top: 1px solid #dee2e6;
            z-index: 1000;
        }

        .border-bottom-subtle {
            border-bottom: 1px solid #f1f1f1 !important;
        }
        .border-bottom-subtle:last-child {
            border-bottom: none !important;
        }
    </style>
    <link rel="stylesheet" href="admin-navigation.css">
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <?php include 'inc/navbar.php'; ?>

        <main class="col-md-10 ms-sm-auto p-4 px-md-5">
            <h2 class="fw-bold mb-4">Заказы клиентов</h2>

            <form method="POST">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
                    <table class="table align-middle mb-0">
                        <thead class="bg-light small text-muted">
                            <tr>
                                <th class="ps-4">Клиент</th>
                                <th>Заказов</th>
                                <th>Общая сумма</th>
                                <th class="text-end pe-4">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($grouped_orders as $email => $items): 
                                $hash = md5($email); 
                                // количество новых заказов в этой группе
                                $newCount = count(array_filter($items, function($i) { return $i['status'] == 'new'; }));
                            ?>
                            <tr class="user-row" id="row-<?= $hash ?>" onclick="toggleGroup('<?= $hash ?>')">
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="fw-bold fs-5 me-2">
                                            <?= htmlspecialchars(($items[0]['surname'] ?? '') . ' ' . $items[0]['name']) ?>
                                        </div>
                                        
                                        <?php if ($newCount > 0): ?>
                                            <span class="badge rounded-pill bg-danger" style="font-size: 0.7rem;">
                                                +<?= $newCount ?> Новых
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="small text-muted">
                                        <i class="bi bi-envelope me-1"></i><?= $email ?> 
                                        <span class="mx-1">•</span> 
                                        <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($items[0]['phone']) ?>
                                    </div>
                                </td>
                                <td><span class="badge bg-dark rounded-pill"><?= count($items) ?></span></td>
                                <td><span class="fw-bold fs-5"><?= number_format(array_sum(array_column($items, 'total_price')), 0, '', ' ') ?> ₽</span></td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-outline-dark rounded-pill px-3" type="button">
                                        Подробнее <i class="bi bi-chevron-down ms-1 chevron-icon"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr id="collapse-<?= $hash ?>" style="display: none;">
                                <td colspan="4" class="p-0 border-0">
                                    <?php 
                                        // заказы внутри группы: сначала статус 'new', затем по дате DESC
                                        usort($items, function($a, $b) {
                                            if ($a['status'] === 'new' && $b['status'] !== 'new') return -1;
                                            if ($a['status'] !== 'new' && $b['status'] === 'new') return 1;
                                            return strtotime($b['created_at']) - strtotime($a['created_at']);
                                        });
                                    ?>
                                    <div class="inner-collapse">
                                        <div class="py-3 px-4 bg-light-subtle border-bottom">
                                            <div class="bg-white rounded-4 shadow-sm p-3 border">
                                                <div class="order-scroll-area">
                                                    <table class="table table-sm table-borderless align-middle m-0">
                                                        <thead>
                                                            <tr class="text-muted small border-bottom">
                                                                <th class="pb-2">ID</th>
                                                                <th class="pb-2">Дата</th>
                                                                <th class="pb-2">Сумма</th>
                                                                <th class="pb-2 text-end">Статус</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($items as $o): ?>
                                                            <tr class="border-bottom-subtle">
                                                                <td class="py-3">
                                                                    <span class="fw-bold">#<?= $o['id'] ?></span>
                                                                    <?php if ($o['status'] === 'new'): ?>
                                                                        <span class="ms-1 badge bg-danger" style="font-size: 0.6rem;">Новый</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td class="small text-muted"><?= date('d.m.Y H:i', strtotime($o['created_at'])) ?></td>
                                                                <td class="fw-bold"><?= number_format($o['total_price'], 0, '', ' ') ?> ₽</td>
                                                                <td class="text-end">
                                                                    <select name="status_update[<?= $o['id'] ?>]" class="form-select form-select-sm d-inline-block shadow-sm" 
                                                                            style="width: 140px; border-radius: 8px; border-left: 5px solid <?= ($o['status']=='processing'?'#0d6efd':($o['status']=='new'?'#dc3545':'#198754')) ?>;" 
                                                                            onclick="event.stopPropagation()">
                                                                        <option value="new" <?= $o['status']=='new'?'selected':'' ?>>Новый</option>
                                                                        <option value="paid" <?= $o['status']=='paid'?'selected':'' ?>>Оплачен</option>
                                                                        <option value="processing" <?= $o['status']=='processing'?'selected':'' ?>>На сборке (Склад)</option>
                                                                        <option value="delivered" <?= $o['status']=='delivered'?'selected':'' ?>>Доставлен</option>
                                                                        <option value="cancelled" <?= $o['status']=='cancelled'?'selected':'' ?>>Отмена</option>
                                                                    </select>
                                                                </td>
                                                            </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="bottom-actions d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        <i class="bi bi-info-circle me-1"></i> Состояние вкладок сохраняется автоматически.
                    </div>
                    <button type="submit" name="save_all_changes" class="btn btn-dark px-5 py-2 rounded-3 shadow fw-bold">
                        Сохранить все изменения
                    </button>
                </div>
            </form>
        </main>
    </div>
</div>



<script>
function toggleGroup(hash) {
    const row = document.getElementById('collapse-' + hash);
    const parentRow = document.getElementById('row-' + hash);
    const inner = row.querySelector('.inner-collapse');

    if (row.style.display === 'none') {
        row.style.display = 'table-row';
        parentRow.classList.add('active');
        
        const fullHeight = inner.scrollHeight + "px";
        setTimeout(() => {
            inner.style.height = fullHeight;
        }, 10);
        
        localStorage.setItem('group-' + hash, 'true');
    } else {
        inner.style.height = '0px';
        parentRow.classList.remove('active');
        
        setTimeout(() => {
            row.style.display = 'none';
        }, 400); 
        
        localStorage.removeItem('group-' + hash);
    }
}


document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[id^="collapse-"]').forEach(row => {
        const hash = row.id.replace('collapse-', '');
        if (localStorage.getItem('group-' + hash) === 'true') {
            row.style.display = 'table-row';
            const inner = row.querySelector('.inner-collapse');
            inner.style.height = 'auto';
            inner.style.transition = 'none'; 
            document.getElementById('row-' + hash).classList.add('active');
            
            setTimeout(() => { inner.style.transition = ''; }, 100);
        }
    });
});
</script>
</body>
</html>