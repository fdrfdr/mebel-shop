<?php
require '../includes/db.php';
require 'inc/auth.php';

if (!function_exists('mb_ucfirst')) {
    function mb_ucfirst($str) {
        return mb_strtoupper(mb_substr($str, 0, 1)) . mb_substr($str, 1);
    }
}

$allowed_tables = ['attr_categories', 'attr_colors', 'attr_materials', 'attr_styles', 'attr_rooms'];

// Обработка удаления
if (isset($_GET['delete']) && in_array($_GET['table'], $allowed_tables)) {
    try {
        $stmt = $pdo->prepare("DELETE FROM {$_GET['table']} WHERE id = ?");
        $stmt->execute([$_GET['id']]);
    } catch (Exception $e) {
        die("Ошибка: Нельзя удалить атрибут, который используется в товарах!");
    }
    header("Location: attributes.php"); exit;
}

// Обработка добавления
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['table'], $allowed_tables)) {
    $table = $_POST['table'];
    $name = trim($_POST['name']);
    
    $check = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE name = ?");
    $check->execute([$name]);
    if (!$check->fetchColumn()) {
        if ($table === 'attr_colors') {
            $hex = $_POST['hex_code'] ?? '#cccccc';
            $pdo->prepare("INSERT INTO attr_colors (name, hex_code) VALUES (?, ?)")->execute([$name, $hex]);
        } else {
            $pdo->prepare("INSERT INTO $table (name) VALUES (?)")->execute([$name]);
        }
    }
    header("Location: attributes.php"); exit;
}

$tables = [
    'attr_categories' => 'Категории',
    'attr_materials'  => 'Материалы',
    'attr_styles'     => 'Стили',
    'attr_rooms'      => 'Комнаты'
];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Атрибуты | Fedosik Admin</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="admin-navigation.css">
    <link rel="icon" type="image/png" href="../images/icons/906343.png">

    <style>
        :root { --yandex-yellow: #ffcc00; --soft-bg: #f8f9fa; --dark-slate: #0f172a; }
        body { background: var(--soft-bg); font-family: 'Inter', system-ui, sans-serif; color: #334155; }
        .attr-card { background: white; border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.05); transition: 0.3s; }
        .attr-card:hover { transform: translateY(-5px); }
        .color-badge { width: 24px; height: 24px; display: inline-block; border-radius: 6px; border: 2px solid #fff; box-shadow: 0 0 5px rgba(0,0,0,0.1); vertical-align: middle; }
        .scroll-list { max-height: 250px; overflow-y: auto; padding-right: 5px; }
        .scroll-list::-webkit-scrollbar { width: 4px; }
        .scroll-list::-webkit-scrollbar-thumb { background: #eee; border-radius: 10px; }
        .btn-add { background: #000; color: #fff; border: none; }
        .btn-add:hover { background: #333; color: #fff; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <?php include 'inc/navbar.php'; ?>

        <main class="col-md-10 ms-sm-auto p-4 px-md-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold m-0">Управление атрибутами</h2>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="attr-card p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-warning rounded-3 p-2 me-3 text-dark"><i class="bi bi-palette"></i></div>
                            <h5 class="fw-bold m-0">Цвета</h5>
                        </div>
                        <form method="POST" class="d-flex gap-2 mb-4">
                            <input type="hidden" name="table" value="attr_colors">
                            <input type="text" name="name" class="form-control form-control-sm" placeholder="Название" required>
                            <input type="color" name="hex_code" class="form-control form-control-color form-control-sm border-0" value="#ffcc00" title="Выбрать цвет">
                            <button type="submit" class="btn btn-add btn-sm px-3"><i class="bi bi-plus-lg"></i></button>
                        </form>
                        <div class="scroll-list">
                            <?php 
                            $colors = $pdo->query("SELECT * FROM attr_colors ORDER BY id DESC")->fetchAll();
                            foreach($colors as $c): ?>
                                <div class="d-flex justify-content-between align-items-center p-2 mb-2 bg-light rounded-3">
                                    <span class="small fw-medium">
                                        <span class="color-badge me-2" style="background: <?= $c['hex_code'] ?>"></span> 
                                        <?= mb_ucfirst(htmlspecialchars($c['name'])) ?>
                                    </span>
                                    <a href="?delete=1&table=attr_colors&id=<?= $c['id'] ?>" class="text-danger opacity-50" onclick="return confirm('Удалить?')">
                                        <i class="bi bi-trash3"></i>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <?php foreach($tables as $tbl => $label): ?>
                <div class="col-md-4">
                    <div class="attr-card p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-2 me-3"><i class="bi bi-tag"></i></div>
                            <h5 class="fw-bold m-0"><?= $label ?></h5>
                        </div>
                        <form method="POST" class="d-flex gap-2 mb-4">
                            <input type="hidden" name="table" value="<?= $tbl ?>">
                            <input type="text" name="name" class="form-control form-control-sm" placeholder="Новое значение..." required>
                            <button type="submit" class="btn btn-add btn-sm px-3"><i class="bi bi-plus-lg"></i></button>
                        </form>
                        <div class="scroll-list">
                            <?php 
                            $items = $pdo->query("SELECT * FROM $tbl ORDER BY id DESC")->fetchAll();
                            foreach($items as $it): ?>
                                <div class="d-flex justify-content-between align-items-center p-2 mb-2 bg-light rounded-3">
                                    <span class="small fw-medium"><?= mb_ucfirst(htmlspecialchars($it['name'])) ?></span>
                                    <a href="?delete=1&table=<?= $tbl ?>&id=<?= $it['id'] ?>" class="text-danger opacity-50" onclick="return confirm('Удалить?')">
                                        <i class="bi bi-trash3"></i>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
</div>

</body>
</html>