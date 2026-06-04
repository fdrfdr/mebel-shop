<?php
require 'includes/db.php';
require 'includes/header.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $surname = trim($_POST['surname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if (empty($name) || empty($email) || empty($password)) {
        $errors[] = 'Заполните все обязательные поля';
    }
    if ($password !== $confirm) {
        $errors[] = 'Пароли не совпадают';
    }
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors[] = 'Такой Email уже зарегистрирован';
    }

    if (empty($errors)) {
            $pass_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO users (name, surname, email, password) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            
            if ($stmt->execute([$name, $surname, $email, $pass_hash])) {
                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['user_name'] = $name;
                echo "<script>location.href='profile.php';</script>";
                exit;
            } else {
                $errors[] = 'Ошибка базы данных';
            }
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card border-0 shadow-lg rounded-5 overflow-hidden">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold">Создать аккаунт</h2>
                        <p class="text-muted">Станьте частью клуба FedosikShop</p>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger rounded-4">
                            <ul class="mb-0 small ps-3">
                                <?php foreach ($errors as $error) echo "<li>$error</li>"; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Имя</label>
                                <input type="text" name="name" class="form-control bg-light border-0 rounded-3 py-2" required placeholder="Иван">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Фамилия</label>
                                <input type="text" name="surname" class="form-control bg-light border-0 rounded-3 py-2" placeholder="Иванов">
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label small fw-bold">Email</label>
                                <input type="email" name="email" class="form-control bg-light border-0 rounded-3 py-2" required placeholder="example@mail.ru">
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label small fw-bold">Пароль</label>
                                <input type="password" name="password" class="form-control bg-light border-0 rounded-3 py-2" required>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label small fw-bold">Повторите пароль</label>
                                <input type="password" name="confirm_password" class="form-control bg-light border-0 rounded-3 py-2" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-warning w-100 rounded-pill py-3 fw-bold mt-4 shadow-sm">
                            Зарегистрироваться
                        </button>
                    </form>
                    
                    <div class="text-center mt-4">
                        <span class="text-muted small">Уже есть аккаунт?</span>
                        <a href="login.php" class="text-decoration-none fw-bold text-dark ms-1">Войти</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>