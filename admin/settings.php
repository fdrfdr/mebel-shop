<?php
require '../includes/db.php';
require 'inc/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['settings'] as $key => $value) {
        $stmt = $pdo->prepare("UPDATE settings SET value = ? WHERE `key` = ?");
        $stmt->execute([trim($value), $key]);
    }
    $message = "Настройки успешно сохранены!";
}

$settings = $pdo->query("SELECT * FROM settings")->fetchAll(PDO::FETCH_UNIQUE);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Настройки | Fedosik Admin</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="admin-navigation.css">
    <link rel="icon" type="image/png" href="../images/icons/906343.png">
    <style>
        :root { --yandex-yellow: #ffcc00; --soft-bg: #f8f9fa; --dark-slate: #0f172a; }
        body { background: var(--soft-bg); font-family: 'Inter', system-ui, sans-serif; color: #334155; }
        .settings-card { background: white; border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .form-label { font-weight: 600; color: #4b5563; font-size: 0.9rem; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <?php include 'inc/navbar.php'; ?>

        <main class="col-md-10 ms-sm-auto p-4 px-md-5">
            <h2 class="fw-bold mb-4">Общие настройки сайта</h2>

            <?php if (isset($message)): ?>
                <div class="alert alert-success border-0 shadow-sm mb-4 rounded-3 d-flex align-items-center">
                    <i class="bi bi-check-circle-fill me-2"></i> <?= $message ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-7">
                    <div class="settings-card p-4 p-md-5">
                        <form method="POST">
                            <div class="mb-4">
                                <label class="form-label">Название магазина</label>
                                <input type="text" name="settings[site_name]" class="form-control form-control-lg" 
                                       value="<?= htmlspecialchars($settings['site_name']['value']) ?>">
                                <div class="form-text">Отображается в заголовке вкладки и в админке.</div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Контактный телефон</label>
                                    <input type="text" name="settings[contact_phone]" class="form-control" 
                                           value="<?= htmlspecialchars($settings['contact_phone']['value']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email для связи</label>
                                    <input type="email" name="settings[contact_email]" class="form-control" 
                                           value="<?= htmlspecialchars($settings['contact_email']['value']) ?>">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Адрес шоурума / самовывоза</label>
                                <textarea name="settings[shop_address]" class="form-control" rows="3"><?= htmlspecialchars($settings['shop_address']['value']) ?></textarea>
                            </div>

                            <hr class="my-4 opacity-50">

                            <button type="submit" class="btn btn-warning btn-lg px-5 fw-bold rounded-3 shadow-sm">
                                Сохранить изменения
                            </button>
                        </form>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card border-0 rounded-4 bg-dark text-white p-4 h-100 shadow">
                        <h5 class="fw-bold mb-3 text-warning">Как это использовать?</h5>
                        <p class="small opacity-75">Вы можете вывести эти данные в любой части клиентского сайта (в футере, контактах или шапке) одной простой заменой.</p>
                        
                        <div class="mt-auto">
                            <i class="bi bi-lightbulb fs-1 opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

</body>
</html>