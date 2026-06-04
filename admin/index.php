<?php
require '../includes/db.php';
require 'inc/auth.php';


$adminName = $_SESSION['user_name'] ?? 'Администратор';

// --- РАСЧЕТ СОСТОЯНИЯ СЕРВЕРА ---
$load = function_exists('sys_getloadavg') ? sys_getloadavg() : [0.1, 0.1, 0.1];
$mem_usage = round(memory_get_usage() / 1024 / 1024, 2);

// скорость отклика БД
$start_db_bench = microtime(true);
$pdo->query("SELECT 1");
$db_time = round((microtime(true) - $start_db_bench) * 1000, 2);

// cтатистика (общая)
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn() ?: 0;
$revenue = $pdo->query("SELECT SUM(total_price) FROM orders WHERE status IN ('paid', 'delivered')")->fetchColumn() ?: 0;

// посещаемость и заказы за сегодня
$visits_today = 0;
try {
    $visits_today = $pdo->query("SELECT COUNT(DISTINCT ip_address) FROM visit_logs WHERE visit_date = CURDATE()")->fetchColumn() ?: 0;
} catch (Exception $e) { $visits_today = 0; }
$today_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn() ?: 0;

// ТОП проданных
$top_sold = $pdo->query("
    SELECT p.name, SUM(oi.quantity) as total_qty 
    FROM order_items oi 
    JOIN orders o ON oi.order_id = o.id 
    JOIN products p ON oi.product_id = p.id 
    WHERE o.status IN ('paid', 'delivered')
    GROUP BY oi.product_id 
    ORDER BY total_qty DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// популярные товары
$popular_viewed = $pdo->query("
    SELECT pid, COUNT(*) as views, p.name 
    FROM (
        SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(page_url, 'id=', -1), '&', 1) as pid 
        FROM visit_logs 
        WHERE page_url LIKE '%product.php?id=%'
    ) as logs
    INNER JOIN products p ON logs.pid = p.id 
    GROUP BY pid 
    ORDER BY views DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// график выручки
$chart_query = $pdo->query("
    SELECT MONTH(created_at) as m, SUM(total_price) as sum 
    FROM orders 
    WHERE status IN ('paid', 'delivered') AND YEAR(created_at) = YEAR(CURDATE())
    GROUP BY m
")->fetchAll(PDO::FETCH_KEY_PAIR);

$chart_data = [];
for($i=1; $i<=12; $i++) { 
    $chart_data[] = isset($chart_query[$i]) ? (float)$chart_query[$i] : 0; 
}

// график посещаемости
$visit_chart_query = $pdo->query("
    SELECT visit_date, COUNT(DISTINCT ip_address) as count 
    FROM visit_logs 
    WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY visit_date 
    ORDER BY visit_date ASC
")->fetchAll(PDO::FETCH_KEY_PAIR);

$visit_labels = [];
$visit_data = [];
for($i=6; $i>=0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $visit_labels[] = date('d.m', strtotime($date));
    $visit_data[] = $visit_chart_query[$date] ?? 0;
}

// --- ЖИВАЯ ЛЕНТА ---
$recent_events = $pdo->query("
    (SELECT 'order' as type, id as target_id, created_at as dt, total_price as meta FROM orders)
    UNION
    (SELECT 'user' as type, id as target_id, created_at as dt, name as meta FROM users)
    ORDER BY dt DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// вывод времени 
function timeAgo($timestamp) {
    $diff = time() - strtotime($timestamp);
    if ($diff < 60) return 'Только что';
    if ($diff < 3600) return floor($diff/60) . ' мин. назад';
    if ($diff < 86400) return floor($diff/3600) . ' час. назад';
    return date('d.m', strtotime($timestamp));
}

$new_orders_count = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'new'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель управления | Furniture Shop</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/bootstrap-icons.min.css">
    <script src="../assets/js/chart.umd.min.js"></script>
    <link rel="icon" type="image/png" href="../images/icons/906343.png">
    <style>
 :root { --yandex-yellow: #ffcc00; --soft-bg: #f8f9fa; --dark-slate: #0f172a; }
        body { background: var(--soft-bg); font-family: 'Inter', system-ui, sans-serif; color: #334155; }

        .stat-card { border: none; border-radius: 28px; transition: transform 0.3s ease; }
        .stat-card:hover { transform: translateY(-5px); }
        .card-title-small { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; color: #9ca3af; font-weight: 800; }
        
        .pulse-animation { animation: pulse-green 2s infinite; }
        @keyframes pulse-green {
            0% { box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.7); }
            70% { box-shadow: 0 0 0 6px rgba(25, 135, 84, 0); }
            100% { box-shadow: 0 0 0 0 rgba(25, 135, 84, 0); }
        }

        .server-card {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
            border: 1px solid rgba(255, 255, 255, 0.08);
            position: relative;
            overflow: hidden;
            min-height: 240px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .server-card::after {
            content: "";
            position: absolute;
            top: -50px;
            right: -50px;
            width: 180px;
            height: 180px;
            background: radial-gradient(circle, rgba(0, 255, 157, 0.15) 0%, rgba(0,0,0,0) 70%);
            filter: blur(20px);
        }
        .bg-grid {
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(255,255,255,0.05) 1px, transparent 1px);
            background-size: 20px 20px;
            opacity: 0.3;
        }
        .system-value {
            font-family: 'Monaco', 'Consolas', monospace;
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.2);
        }
        .status-dot-active {
            width: 8px;
            height: 8px;
            background: #00ff9d;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 12px #00ff9d;
            margin-right: 8px;
        }

        .quick-link-card {
            background: #fff;
            transition: all 0.2s ease;
            border: 1px solid #f1f3f5;
        }
        .quick-link-card:hover {
            border-color: var(--yandex-yellow);
            background: #fffdf5;
        }
    </style>
    <link rel="stylesheet" href="admin-navigation.css">
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <?php include 'inc/navbar.php'; ?>

        <main class="col-md-10 ms-sm-auto p-4 p-lg-5">
            
            <div class="row align-items-center mb-5">
                <div class="col-md-8">
                    <?php
                        $hour = date('H');
                        $greeting = ($hour < 12) ? "Доброе утро" : (($hour < 18) ? "Добрый день" : "Добрый вечер");
                    ?>
                    <h2 class="fw-bold mb-1"><?= $greeting ?>, <?= htmlspecialchars($adminName) ?>!</h2>
                    <p class="text-muted mb-0">Магазин работает стабильно, вот ключевые показатели.</p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <div class="badge bg-white border text-dark p-3 rounded-4 shadow-sm">
                        <i class="bi bi-clock-history me-2 text-warning"></i><?= date('d.m.Y H:i') ?>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-md-3">
                    <div class="card stat-card p-4 bg-white shadow-sm h-100 border-0">
                        <div class="card-title-small mb-2 text-uppercase">Всего заказов</div>
                        <div class="d-flex align-items-end justify-content-between">
                            <div class="h2 fw-bold mb-0"><?= $total_orders ?></div>
                            <div class="text-success small fw-bold"><i class="bi bi-arrow-up-short"></i>Live</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card p-4 bg-white shadow-sm h-100 border-0">
                        <div class="card-title-small mb-2 text-uppercase">Заказы сегодня</div>
                        <div class="d-flex align-items-end justify-content-between">
                            <div class="h2 fw-bold mb-0"><?= $today_orders ?></div>
                            <div class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-2">Сегодня</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card p-4 bg-white shadow-sm h-100 border-0 border-start border-warning border-5">
                        <div class="card-title-small mb-2 text-warning text-uppercase">Прибыль</div>
                        <div class="h2 fw-bold mb-0"><?= number_format($revenue, 0, '.', ' ') ?> ₽</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card p-4 bg-dark text-white shadow-sm h-100 border-0">
                        <div class="card-title-small mb-2 text-white-50 text-uppercase">Визиты сегодня</div>
                        <div class="d-flex align-items-center">
                            <div class="h2 fw-bold mb-0 me-3"><?= $visits_today ?></div>
                            <i class="bi bi-people text-white-50"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
                        <h5 class="fw-bold mb-4">Выручка (₽)</h5>
                        <div style="height: 300px;">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white border-top border-primary border-5">
                        <h5 class="fw-bold mb-4">Трафик</h5>
                        <div style="height: 300px;">
                            <canvas id="visitsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>



            <div class="row g-4 mb-4">

                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
                        <h5 class="fw-bold mb-3 small text-uppercase" style="letter-spacing: 1px;">Популярные товары</h5>
                        <div class="table-responsive">
                            <table class="table table-borderless align-middle mb-0">
                                <tbody>
                                    <?php foreach($popular_viewed as $pv): ?>
                                    <tr>
                                        <td class="ps-0"><span class=" small"><?= htmlspecialchars($pv['name']) ?></span></td>
                                        <td class="text-end"><span class="badge bg-light text-muted fw-normal"><?= $pv['views'] ?> <i class="bi bi-eye ms-1"></i></span></td>
                                        <td class="text-end" style="width: 40px;">
                                            <a href="../product.php?id=<?= $pv['pid'] ?>" class="text-dark"><i class="bi bi-arrow-up-right-circle"></i></a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm rounded-4 p-4 text-white h-100 server-card">
                        <div class="bg-grid"></div>
                        
                        <div style="position: relative; z-index: 2;">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h6 class="fw-bold m-0 small text-uppercase opacity-50" style="letter-spacing: 1.5px;">Состояние сервера</h6>
                                <div class="small d-flex align-items-center bg-white bg-opacity-10 px-2 py-1 rounded-pill">
                                    <span class="status-dot-active pulse-animation"></span>
                                    <span style="font-size: 0.65rem; font-weight: 700;">ONLINE</span>
                                </div>
                            </div>

                            <div class="row g-0 mb-4">
                                <div class="col-6 border-end border-white border-opacity-10">
                                    <div class="tiny text-white-50 mb-1 text-uppercase">Задержка</div>
                                    <div class="h2 fw-bold system-value text-success mb-0"><?= $db_time ?><span class="h6 opacity-50 ms-1">ms</span></div>
                                </div>
                                <div class="col-6 ps-4">
                                    <div class="tiny text-white-50 mb-1 text-uppercase">Загрузка CPU</div>
                                    <div class="h2 fw-bold system-value mb-0 text-info"><?= $load[0] ?><span class="h6 opacity-50 ms-1">%</span></div>
                                </div>
                            </div>

                            <div class="p-3 rounded-4" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05);">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="tiny text-white-50">Использование памяти: <?= $mem_usage ?> MB</span>
                                    <span class="tiny text-white-50">PHP <?= PHP_VERSION ?></span>
                                </div>
                                <div class="progress bg-dark bg-opacity-50" style="height: 4px;">
                                    <div class="progress-bar bg-info" role="progressbar" style="width: 45%; box-shadow: 0 0 10px rgba(0, 204, 255, 0.5);"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
                        <h5 class="fw-bold mb-4 small text-uppercase">Последние события</h5>
                        <div class="row g-3">
                            <?php foreach(array_slice($recent_events, 0, 4) as $ev): 
                                $isOrder = ($ev['type'] == 'order');
                            ?>
                            <div class="col-md-6">
                                <div class="p-3 rounded-4 border bg-light bg-opacity-50 h-100">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bi <?= $isOrder ? 'bi-cart-fill text-primary' : 'bi-person-fill text-success' ?> me-2"></i>
                                        <span class="fw-bold small"><?= $isOrder ? "Заказ #".$ev['target_id'] : "Новый клиент" ?></span>
                                    </div>
                                    <div class="small text-muted mb-1"><?= $isOrder ? number_format($ev['meta'], 0, '.', ' ') . ' ₽' : $ev['meta'] ?></div>
                                    <div class="tiny text-warning fw-bold"><?= timeAgo($ev['dt']) ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white border-top border-warning border-5">
                        <h5 class="fw-bold mb-4 small text-uppercase">Быстрое управление</h5>
                        <div class="row g-2">
                            <div class="col-6">
                                <a href="products.php?action=add" class="text-decoration-none">
                                    <div class="quick-link-card p-3 rounded-4 text-center">
                                        <i class="bi bi-plus-circle-dotted fs-4 text-warning"></i>
                                        <div class="small text-dark fw-bold mt-1">Товар</div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="orders.php?status=new" class="text-decoration-none">
                                    <div class="quick-link-card p-3 rounded-4 text-center position-relative">
                                        <div class="position-relative d-inline-block">
                                            <i class="bi bi-bell fs-4 text-primary"></i>
                                            
                                            <?php if($new_orders_count > 0): ?>
                                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light pulse-animation" 
                                                    style="font-size: 0.65rem; padding: 0.35em 0.5em;">
                                                    +<?= $new_orders_count ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="small text-dark fw-bold mt-1">Новые заказы</div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="settings.php" class="text-decoration-none">
                                    <div class="quick-link-card p-3 rounded-4 text-center">
                                        <i class="bi bi-sliders fs-4 text-secondary"></i>
                                        <div class="small text-dark fw-bold mt-1">Настройки</div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="/" target="_blank" class="text-decoration-none">
                                    <div class="quick-link-card p-3 rounded-4 text-center">
                                        <i class="bi bi-eye fs-4 text-success"></i>
                                        <div class="small text-dark fw-bold mt-1">На сайт</div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


        </main>
    </div>
</div>

<script>
const commonOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { grid: { display: false }, ticks: { font: { size: 10 } } }, x: { grid: { display: false }, ticks: { font: { size: 10 } } } } };
new Chart(document.getElementById('salesChart'), { type: 'line', data: { labels: ['Янв','Фев','Мар','Апр','Май','Июн','Июл','Авг','Сен','Окт','Ноя','Дек'], datasets: [{ data: <?= json_encode($chart_data) ?>, borderColor: '#ffcc00', backgroundColor: 'rgba(255, 204, 0, 0.05)', borderWidth: 4, fill: true, tension: 0.4, pointRadius: 0 }] }, options: commonOptions });
new Chart(document.getElementById('visitsChart'), { type: 'line', data: { labels: <?= json_encode($visit_labels) ?>, datasets: [{ data: <?= json_encode($visit_data) ?>, borderColor: '#0d6efd', borderWidth: 3, fill: false, tension: 0.4, pointRadius: 4 }] }, options: commonOptions });
</script>

</body>
</html>