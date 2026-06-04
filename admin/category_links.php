<?php
require '../includes/db.php';

//КАТЕГОРИЯ -> КАТЕГОРИЯ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_link'])) {
    $parent_id = (int)$_POST['parent_cat_id'];
    $linked_id = (int)$_POST['linked_cat_id'];
    if ($parent_id !== $linked_id) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO attr_category_links (parent_cat_id, linked_cat_id) VALUES (?, ?)");
        $stmt->execute([$parent_id, $linked_id]);
    }
}

//КОМНАТА -> КАТЕГОРИЯ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_room_link'])) {
    $room_id = (int)$_POST['room_id'];
    $linked_id = (int)$_POST['linked_cat_id'];
    $stmt = $pdo->prepare("INSERT IGNORE INTO attr_room_links (room_id, linked_cat_id) VALUES (?, ?)");
    $stmt->execute([$room_id, $linked_id]);
}

// удалениe связей категорий
if (isset($_GET['delete_cat'])) {
    $stmt = $pdo->prepare("DELETE FROM attr_category_links WHERE id = ?");
    $stmt->execute([(int)$_GET['delete_cat']]);
    header("Location: category_links.php"); exit;
}

// удалениe связей комнат
if (isset($_GET['delete_room'])) {
    $stmt = $pdo->prepare("DELETE FROM attr_room_links WHERE id = ?");
    $stmt->execute([(int)$_GET['delete_room']]);
    header("Location: category_links.php"); exit;
}


$categories = $pdo->query("SELECT * FROM attr_categories ORDER BY name")->fetchAll();
$rooms = $pdo->query("SELECT * FROM attr_rooms ORDER BY name")->fetchAll();

// Текущие связи категорий
$links = $pdo->query("
    SELECT cl.id, c1.name as parent_name, c2.name as linked_name 
    FROM attr_category_links cl
    JOIN attr_categories c1 ON cl.parent_cat_id = c1.id
    JOIN attr_categories c2 ON cl.linked_cat_id = c2.id
    ORDER BY c1.name
")->fetchAll();

// Текущие связи комнат
$room_links = $pdo->query("
    SELECT rl.id, r.name as room_name, c.name as linked_name 
    FROM attr_room_links rl
    JOIN attr_rooms r ON rl.room_id = r.id
    JOIN attr_categories c ON rl.linked_cat_id = c.id
    ORDER BY r.name
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Умные связи | Furniture Admin</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/bootstrap-icons.min.css">
    <link rel="icon" type="image/png" href="../images/icons/906343.png">

    <style>
        :root { --yandex-yellow: #ffcc00; --soft-bg: #f8f9fa; --dark-slate: #0f172a; }
        body { background: var(--soft-bg); font-family: 'Inter', system-ui, sans-serif; color: #334155; }
        
        .admin-card { border: none; border-radius: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.02); background: #fff; transition: all 0.3s ease; }
        .card-header { background: transparent !important; border-bottom: 1px solid #f1f5f9 !important; padding: 25px !important; }
        
        .form-label { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; margin-bottom: 8px; }
        .form-select { border: 1px solid #e2e8f0; border-radius: 14px; padding: 12px; font-size: 0.95rem; }
        .form-select:focus { border-color: var(--yandex-yellow); box-shadow: 0 0 0 3px rgba(255, 204, 0, 0.15); }

        .btn-add { border-radius: 14px; padding: 12px; font-weight: 700; border: none; transition: all 0.2s; }
        .btn-primary-dark { background: var(--dark-slate); color: white; }
        .btn-primary-dark:hover { background: #1e293b; transform: translateY(-2px); }
        
        .link-item { 
            background: #ffffff; 
            border: 1px solid #f1f5f9; 
            border-radius: 18px; 
            padding: 14px 20px; 
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.2s;
        }
        .link-item:hover { border-color: var(--yandex-yellow); background: #fffdf5; }
        
        .badge-custom { padding: 6px 12px; border-radius: 10px; font-weight: 600; font-size: 0.85rem; display: inline-flex; align-items: center; }
        .bg-cat { background: #eff6ff; color: #2563eb; }
        .bg-room { background: #fff7ed; color: #ea580c; }
        
        .connection-icon { font-size: 1.2rem; color: #cbd5e1; margin: 0 15px; }
        .mirror-info { font-size: 0.75rem; color: #10b981; font-weight: 600; display: block; margin-top: 4px; }

        .btn-delete { color: #f1f5f9; transition: color 0.2s; }
        .link-item:hover .btn-delete { color: #ef4444; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <?php if(file_exists('inc/navbar.php')) include 'inc/navbar.php'; ?>

        <main class="col-md-10 ms-sm-auto p-4 p-lg-5">
            <div class="d-flex align-items-center justify-content-between mb-5">
                <div>
                    <h2 class="fw-bold mb-1">Матрица рекомендаций</h2>
                    <p class="text-muted mb-0">Управление нейронными связями вашего магазина</p>
                </div>
                <div class="badge bg-white text-dark border rounded-pill px-3 py-2 shadow-sm">
                    <span class="status-dot-active"></span> Система активна
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="admin-card h-100">
                        <div class="card-header">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-intersect me-2 text-primary"></i> Зеркальные связи</h5>
                            <small class="text-muted">Работают в обе стороны автоматически</small>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST" class="row g-3 mb-5">
                                <div class="col-md-5">
                                    <label class="form-label">Категория А</label>
                                    <select name="parent_cat_id" class="form-select">
                                        <?php foreach($categories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2 d-flex align-items-end justify-content-center p-3">
                                    <i class="bi bi-arrow-left-right text-muted" style="font-size: 1.5rem;"></i>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Категория Б</label>
                                    <select name="linked_cat_id" class="form-select">
                                        <?php foreach($categories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12 mt-4">
                                    <button type="submit" name="add_link" class="btn btn-add btn-primary-dark w-100">
                                        Создать умную связь
                                    </button>
                                </div>
                            </form>

                            <div class="links-list">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="small fw-bold text-uppercase text-muted mb-0">Активные пары</h6>
                                    <span class="badge bg-success-subtle text-success rounded-pill px-3" style="font-size: 0.7rem;">Двусторонние</span>
                                </div>
                                
                                <?php foreach($links as $link): ?>
                                    <div class="link-item">
                                        <div class="d-flex align-items-center">
                                            <span class="badge-custom bg-cat"><?= htmlspecialchars($link['parent_name']) ?></span>
                                            <i class="bi bi-arrow-left-right connection-icon"></i>
                                            <span class="badge-custom bg-cat"><?= htmlspecialchars($link['linked_name']) ?></span>
                                        </div>
                                        <a href="?delete_cat=<?= $link['id'] ?>" class="btn-delete px-2" title="Удалить связь">
                                            <i class="bi bi-trash3-fill"></i>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="admin-card h-100">
                        <div class="card-header">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-geo-fill me-2 text-warning"></i> Контекстные связи</h5>
                            <small class="text-muted">От комнаты к набору товаров</small>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST" class="row g-3 mb-5">
                                <div class="col-md-5">
                                    <label class="form-label">Если комната:</label>
                                    <select name="room_id" class="form-select">
                                        <?php foreach($rooms as $room): ?>
                                            <option value="<?= $room['id'] ?>"><?= htmlspecialchars($room['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2 d-flex align-items-end justify-content-center p-3">
                                    <i class="bi bi-chevron-double-right text-muted" style="font-size: 1.5rem;"></i>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Предлагать категорию:</label>
                                    <select name="linked_cat_id" class="form-select">
                                        <?php foreach($categories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12 mt-4">
                                    <button type="submit" name="add_room_link" class="btn btn-add btn-warning w-100 shadow-sm">
                                        Привязать товары к локации
                                    </button>
                                </div>
                            </form>

                            <div class="links-list">
                                <h6 class="small fw-bold text-uppercase text-muted mb-3">Настроенные правила</h6>
                                <?php foreach($room_links as $rl): ?>
                                    <div class="link-item">
                                        <div class="d-flex align-items-center">
                                            <span class="badge-custom bg-room"><?= htmlspecialchars($rl['room_name']) ?></span>
                                            <i class="bi bi-arrow-right connection-icon text-warning"></i>
                                            <span class="badge-custom bg-cat"><?= htmlspecialchars($rl['linked_name']) ?></span>
                                        </div>
                                        <a href="?delete_room=<?= $rl['id'] ?>" class="btn-delete px-2">
                                            <i class="bi bi-trash3-fill"></i>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

</body>
</html>