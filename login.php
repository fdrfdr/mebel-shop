<?php
require 'includes/db.php';
require 'includes/header.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        // ВОТ ЭТА СТРОЧКА СПАСЕТ МИР:
        $_SESSION['role'] = $user['role']; 
        
        echo "<script>location.href='profile.php';</script>";
        exit;
    } else {
            $error = 'Неверный email или пароль';
        }
    } else {
        $error = 'Пожалуйста, заполните все поля';
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center align-items-center" style="min-height: 60vh;">
        <div class="col-md-5 col-lg-4">
            <div class="card border-0 shadow-lg rounded-5 overflow-hidden">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center bg-warning bg-opacity-10 rounded-circle mb-3" 
                            style="width: 80px; height: 80px;">
                            <i class="bi bi-person-lock fs-1 text-warning"></i>
                        </div>
                        <h2 class="fw-bold">Вход</h2>
                        <p class="text-muted small">Добро пожаловать в FedosikShop</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger rounded-4 small py-2">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $error ?>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Email</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0 rounded-start-3"><i class="bi bi-envelope text-muted"></i></span>
                                <input type="email" name="email" class="form-control bg-light border-0 rounded-end-3 py-2" required placeholder="example@mail.ru">
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label small fw-bold">Пароль</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0 rounded-start-3"><i class="bi bi-key text-muted"></i></span>
                                <input type="password" name="password" class="form-control bg-light border-0 rounded-end-3 py-2" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-warning w-100 rounded-pill py-3 fw-bold shadow-sm transition-up">
                            Войти в систему
                        </button>
                    </form>

                    <div class="text-center mt-4 pt-2 border-top">
                        <span class="text-muted small">Ещё нет аккаунта?</span>
                        <a href="register.php" class="text-decoration-none fw-bold text-dark ms-1 small">Создать профиль</a>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <a href="index.php" class="text-muted small text-decoration-none"><i class="bi bi-arrow-left me-1"></i> На главную</a>
            </div>
        </div>
    </div>
</div>

<style>
.transition-up {
    transition: transform 0.2s, box-shadow 0.2s;
}
.transition-up:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1) !important;
}
.input-group-text {
    border-right: none !important;
}
.form-control:focus {
    box-shadow: none;
    background-color: #f8f9fa !important;
}
</style>

<?php require 'includes/footer.php'; ?>