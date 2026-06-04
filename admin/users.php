<?php
require '../includes/db.php';
require 'inc/auth.php';

function getAdminAvatar($user) {
    $avatarPath = '../uploads/avatars/' . $user['avatar'];
    if (!empty($user['avatar']) && file_exists($avatarPath)) {
        return '<img src="' . $avatarPath . '" class="rounded-3" style="width: 40px; height: 40px; object-fit: cover;">';
    }
    $colors = ['#5865F2', '#EB459E', '#FEE75C', '#57F287', '#ED4245', '#9b59b6', '#3498db', '#1abc9c'];
    $colorIndex = $user['id'] % count($colors);
    $bgColor = $colors[$colorIndex];
    $u_name = !empty($user['name']) ? $user['name'] : ($user['login'] ?? 'U');
    $initial = mb_substr($u_name, 0, 1, "UTF-8");
    return '<div class="rounded-3 d-flex align-items-center justify-content-center text-white fw-bold" 
                 style="width: 40px; height: 40px; background-color: '.$bgColor.'; font-size: 1.1rem;">'
                 .strtoupper($initial).'</div>';
}

if (isset($_GET['delete']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($id !== $_SESSION['user_id']) {
        $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $u = $stmt->fetch();
        if ($u && !empty($u['avatar'])) {
            $filePath = '../uploads/avatars/' . $u['avatar'];
            if (file_exists($filePath)) unlink($filePath);
        }
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
    }
    header("Location: users.php"); exit;
}

if (isset($_GET['set_role']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $new_role = $_GET['set_role'];
    
    // роль допустимая?
    if (in_array($new_role, ['admin', 'user', 'warehouse']) && $id !== $_SESSION['user_id']) {
        $pdo->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$new_role, $id]);
    }
    header("Location: users.php"); exit;
}

$search = $_GET['search'] ?? '';
$query = "SELECT * FROM users";
$params = [];
if ($search) {
    $query .= " WHERE name LIKE ? OR email LIKE ?";
    $params = ["%$search%", "%$search%"];
}
$query .= " ORDER BY id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

$total_users = count($users);
$admins_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$warehouse_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'warehouse'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Клиенты | Fedosik Admin</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="admin-navigation.css">
    <link rel="icon" type="image/png" href="../images/icons/906343.png">
    <style>
        :root { --yandex-yellow: #ffcc00; --soft-bg: #f8f9fa; --dark-slate: #0f172a; }
        body { background: var(--soft-bg); font-family: 'Inter', system-ui, sans-serif; color: #334155; }
        .stats-card { background: white; border-radius: 15px; border: none; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .user-table-card { background: white; border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.05); overflow: hidden; }
        .role-badge { font-size: 0.75rem; padding: 4px 10px; border-radius: 6px; font-weight: 600; }
        .role-admin { background: #fff4e5; color: #ff8800; }
        .role-user { background: #eef2ff; color: #4338ca; }
        .role-warehouse { background: #f3e8ff; color: #7e22ce; } /* Фиолетовый для склада */
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <?php include 'inc/navbar.php'; ?>

        <main class="col-md-10 ms-sm-auto p-4 px-md-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold m-0">Клиенты</h2>
                <form class="d-flex gap-2" method="GET">
                    <input type="text" name="search" class="form-control" placeholder="Поиск..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-dark px-4">Найти</button>
                </form>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="stats-card d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-3 me-3"><i class="bi bi-people fs-4"></i></div>
                        <div>
                            <div class="text-muted small">Всего пользователей</div>
                            <div class="fw-bold fs-5"><?= $total_users ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card d-flex align-items-center">
                        <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-3 me-3"><i class="bi bi-shield-lock fs-4"></i></div>
                        <div>
                            <div class="text-muted small">Администраторов</div>
                            <div class="fw-bold fs-5"><?= $admins_count ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card d-flex align-items-center">
                        <div class="bg-info bg-opacity-10 text-info p-3 rounded-3 me-3"><i class="bi bi-box-seam fs-4"></i></div>
                        <div>
                            <div class="text-muted small">Кладовщиков</div>
                            <div class="fw-bold fs-5"><?= $warehouse_count ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="user-table-card">
                <table class="table table-hover align-middle m-0">
                    <thead class="bg-light text-uppercase small text-muted">
                        <tr>
                            <th class="px-4 py-3 border-0">Пользователь</th>
                            <th class="py-3 border-0">Email</th>
                            <th class="py-3 border-0">Роль</th>
                            <th class="py-3 border-0">Регистрация</th>
                            <th class="py-3 border-0 text-end px-4">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $user): ?>
                        <tr>
                            <td class="px-4">
                                <div class="d-flex align-items-center">
                                    <div class="me-3"><?= getAdminAvatar($user) ?></div>
                                    <div class="fw-medium"><?= htmlspecialchars($user['name']) ?></div>
                                </div>
                            </td>
                            <td class="text-muted small"><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                <?php 
                                    $roleClass = 'role-user';
                                    $roleName = 'Клиент';
                                    if($user['role'] === 'admin') { $roleClass = 'role-admin'; $roleName = 'Админ'; }
                                    if($user['role'] === 'warehouse') { $roleClass = 'role-warehouse'; $roleName = 'Склад'; }
                                ?>
                                <span class="role-badge <?= $roleClass ?>">
                                    <?= $roleName ?>
                                </span>
                            </td>
                            <td class="text-muted small"><?= date('d.m.Y', strtotime($user['created_at'])) ?></td>
                            <td class="text-end px-4">
                                <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                    
                                    <a href="?set_role=<?= $user['role'] === 'admin' ? 'user' : 'admin' ?>&id=<?= $user['id'] ?>" 
                                       class="btn btn-sm <?= $user['role'] === 'admin' ? 'btn-secondary' : 'btn-outline-warning' ?> rounded-3" 
                                       title="Админ-права">
                                         <i class="bi <?= $user['role'] === 'admin' ? 'bi-shield-minus' : 'bi-shield-plus' ?>"></i>
                                    </a>

                                    <a href="?set_role=<?= $user['role'] === 'warehouse' ? 'user' : 'warehouse' ?>&id=<?= $user['id'] ?>" 
                                       class="btn btn-sm <?= $user['role'] === 'warehouse' ? 'btn-info text-white' : 'btn-outline-info' ?> rounded-3 ms-1" 
                                       title="Права склада">
                                         <i class="bi bi-box-seam"></i>
                                    </a>

                                    <a href="?delete=1&id=<?= $user['id'] ?>" 
                                       class="btn btn-sm btn-outline-danger border-0 ms-1" 
                                       onclick="confirmDelete(event, this)">
                                         <i class="bi bi-trash3"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark small">Это вы</span>
                                <?php endif; ?>
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
        title: 'Удалить клиента?',
        text: "Все данные пользователя и его аватар будут удалены безвозвратно!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#000',
        confirmButtonText: 'Да, удалить',
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