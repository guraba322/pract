<?php
session_start();
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($db === null) {
        $error = "Ошибка подключения к базе данных. Попробуйте позже.";
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        try {
            $query = "SELECT id, name, email, password FROM users WHERE email = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() == 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user'] = [
                        'id' => $user['id'],
                        'name' => $user['name'],
                        'email' => $user['email']
                    ];
                    header('Location: ../index.php');
                    exit;
                } else {
                    $error = "Неверный пароль!";
                }
            } else {
                $error = "Пользователь с таким email не найден!";
            }
        } catch(PDOException $e) {
            $error = "Ошибка при входе: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход - Фитнес-клуб "Энергия"</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-form">
            <h2>Вход в систему</h2>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <input type="email" name="email" placeholder="Email" value="<?php echo isset($_POST['email']) ? $_POST['email'] : ''; ?>" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Пароль" required>
                </div>
                <button type="submit">Войти</button>
            </form>
            <p>Нет аккаунта? <a href="../register/register.php">Зарегистрируйтесь</a></p>
        </div>
    </div>
</body>
</html>