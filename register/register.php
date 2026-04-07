<?php
session_start();
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($db === null) {
        $error = "Ошибка подключения к базе данных. Попробуйте позже.";
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $phone = trim($_POST['phone'] ?? '');

        try {
            // Проверка email
            $query = "SELECT id FROM users WHERE email = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $error = "Пользователь с таким email уже существует!";
            } else {
                // Хеширование пароля
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Вставка пользователя
                $query = "INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                
                if ($stmt->execute([$name, $email, $hashed_password, $phone])) {
                    $success = "Регистрация успешна! Теперь вы можете войти.";
                } else {
                    $error = "Ошибка регистрации. Попробуйте еще раз.";
                }
            }
        } catch(PDOException $e) {
            $error = "Ошибка регистрации: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация - Фитнес-клуб "Энергия"</title>
    <link rel="stylesheet" href="register.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-form">
            <h2>Регистрация</h2>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <input type="text" name="name" placeholder="Имя" value="<?php echo isset($_POST['name']) ? $_POST['name'] : ''; ?>" required>
                </div>
                <div class="form-group">
                    <input type="email" name="email" placeholder="Email" value="<?php echo isset($_POST['email']) ? $_POST['email'] : ''; ?>" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Пароль" required>
                </div>
                <div class="form-group">
                    <input type="tel" name="phone" placeholder="Телефон" value="<?php echo isset($_POST['phone']) ? $_POST['phone'] : ''; ?>">
                </div>
                <button type="submit">Зарегистрироваться</button>
            </form>
            <p>Уже есть аккаунт? <a href="../login/login.php">Войдите</a></p>
        </div>
    </div>
</body>
</html>