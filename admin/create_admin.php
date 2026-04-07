<?php
/**
 * Скрипт для создания администратора
 * Запустите этот файл один раз для создания администратора
 */

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    die("Ошибка подключения к базе данных");
}

// Данные администратора
$admin_name = 'Администратор';
$admin_email = 'admin@energia-fitness.ru';
$admin_password = 'admin123'; // Рекомендуется изменить после первого входа
$admin_phone = '+7 (999) 123-45-67';

$message = '';
$message_type = '';

// Проверка, существует ли уже администратор
try {
    $check_query = "SELECT id FROM users WHERE email = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([$admin_email]);
    
    if ($check_stmt->rowCount() > 0) {
        $message = "Администратор с email {$admin_email} уже существует!";
        $message_type = 'warning';
    } else {
        // Создание администратора
        $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
        
        $insert_query = "INSERT INTO users (name, email, password, phone, created_at) VALUES (?, ?, ?, ?, NOW())";
        $insert_stmt = $db->prepare($insert_query);
        
        if ($insert_stmt->execute([$admin_name, $admin_email, $hashed_password, $admin_phone])) {
            $admin_id = $db->lastInsertId();
            $message = "Администратор успешно создан! ID: {$admin_id}";
            $message_type = 'success';
        } else {
            $message = "Ошибка при создании администратора.";
            $message_type = 'error';
        }
    }
} catch(PDOException $e) {
    $message = "Ошибка: " . $e->getMessage();
    $message_type = 'error';
}

// Если нужно добавить поле is_admin в таблицу users
try {
    // Проверяем, существует ли поле is_admin
    $check_column_query = "SHOW COLUMNS FROM users LIKE 'is_admin'";
    $check_column_stmt = $db->query($check_column_query);
    
    if ($check_column_stmt->rowCount() == 0) {
        // Добавляем поле is_admin
        $alter_query = "ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0";
        $db->exec($alter_query);
        
        // Устанавливаем is_admin = 1 для администратора
        if (isset($admin_id)) {
            $update_query = "UPDATE users SET is_admin = 1 WHERE id = ?";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->execute([$admin_id]);
        } else {
            // Если администратор уже существует, обновляем его
            $update_query = "UPDATE users SET is_admin = 1 WHERE email = ?";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->execute([$admin_email]);
        }
    } else {
        // Поле уже существует, просто обновляем администратора
        if (isset($admin_id)) {
            $update_query = "UPDATE users SET is_admin = 1 WHERE id = ?";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->execute([$admin_id]);
        } else {
            $update_query = "UPDATE users SET is_admin = 1 WHERE email = ?";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->execute([$admin_email]);
        }
    }
} catch(PDOException $e) {
    // Игнорируем ошибку, если поле уже существует
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создание администратора</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 10px;
            padding: 3rem;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        
        h1 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .message {
            padding: 1rem 1.5rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 1.5rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
        }
        
        .info-box h3 {
            color: #1976D2;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        
        .info-box ul {
            list-style: none;
            padding-left: 0;
        }
        
        .info-box li {
            padding: 0.5rem 0;
            color: #424242;
        }
        
        .info-box li strong {
            color: #1976D2;
            display: inline-block;
            min-width: 120px;
        }
        
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            text-align: center;
            transition: background 0.3s;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            width: 100%;
            margin-top: 1rem;
        }
        
        .btn:hover {
            background: #2980b9;
        }
        
        .btn-secondary {
            background: #95a5a6;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .credentials {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin-top: 1rem;
            border: 2px dashed #dee2e6;
        }
        
        .credentials p {
            margin: 0.5rem 0;
            font-family: 'Courier New', monospace;
        }
        
        .credentials strong {
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-user-shield"></i> Создание администратора</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : ($message_type == 'warning' ? 'exclamation-triangle' : 'times-circle'); ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="info-box">
            <h3>Данные администратора:</h3>
            <ul>
                <li><strong>Имя:</strong> <?php echo htmlspecialchars($admin_name); ?></li>
                <li><strong>Email:</strong> <?php echo htmlspecialchars($admin_email); ?></li>
                <li><strong>Пароль:</strong> <?php echo htmlspecialchars($admin_password); ?></li>
                <li><strong>Телефон:</strong> <?php echo htmlspecialchars($admin_phone); ?></li>
            </ul>
            
            <?php if ($message_type == 'success'): ?>
                <div class="credentials">
                    <p><strong>⚠️ ВАЖНО!</strong> Сохраните эти данные для входа в систему.</p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($admin_email); ?></p>
                    <p><strong>Пароль:</strong> <?php echo htmlspecialchars($admin_password); ?></p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="info-box">
            <h3>Что было сделано:</h3>
            <ul>
                <li>✓ Проверено наличие администратора в базе данных</li>
                <li>✓ Создан пользователь с правами администратора</li>
                <li>✓ Добавлено поле is_admin в таблицу users (если его не было)</li>
                <li>✓ Установлен флаг is_admin = 1 для администратора</li>
            </ul>
        </div>
        
        <div class="info-box">
            <h3>Доступ к админ-панели:</h3>
            <ul>
                <li><strong>Админ-панель:</strong> <a href="admin.php">admin/admin.php</a></li>
                <li><strong>Вход в систему:</strong> <a href="../login/login.php">login/login.php</a></li>
            </ul>
        </div>
        
        <a href="../login/login.php" class="btn">
            <i class="fas fa-sign-in-alt"></i> Перейти к входу
        </a>
        
        <a href="admin.php" class="btn btn-secondary">
            <i class="fas fa-shield-alt"></i> Открыть админ-панель
        </a>
        
        <p style="text-align: center; margin-top: 2rem; color: #7f8c8d; font-size: 0.9rem;">
            <i class="fas fa-info-circle"></i> Этот скрипт можно запустить только один раз. 
            После создания администратора рекомендуется удалить или защитить этот файл.
        </p>
    </div>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</body>
</html>

