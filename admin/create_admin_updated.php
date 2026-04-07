<?php
/**
 * Скрипт для создания администратора в отдельной таблице admins
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
$admin_email = 'admin@ru';
$admin_password = 'admin123'; // Рекомендуется изменить после первого входа
$admin_phone = '+89776720546';
$admin_role = 'super_admin'; // admin, super_admin, manager

$message = '';
$message_type = '';
$steps = [];

// Шаг 1: Создание таблицы admins
try {
    $create_table_query = "CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        phone VARCHAR(50),
        role VARCHAR(50) DEFAULT 'admin' COMMENT 'Роль: admin, super_admin, manager',
        is_active TINYINT(1) DEFAULT 1 COMMENT 'Активен ли администратор',
        last_login DATETIME NULL COMMENT 'Последний вход',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_email (email),
        INDEX idx_user_id (user_id),
        INDEX idx_is_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($create_table_query);
    $steps[] = ['status' => 'success', 'message' => 'Таблица admins создана успешно'];
} catch(PDOException $e) {
    $steps[] = ['status' => 'error', 'message' => 'Ошибка создания таблицы: ' . $e->getMessage()];
}

// Шаг 2: Создание пользователя (если его нет)
try {
    $check_user_query = "SELECT id FROM users WHERE email = ?";
    $check_user_stmt = $db->prepare($check_user_query);
    $check_user_stmt->execute([$admin_email]);
    
    if ($check_user_stmt->rowCount() > 0) {
        $user = $check_user_stmt->fetch(PDO::FETCH_ASSOC);
        $user_id = $user['id'];
        $steps[] = ['status' => 'info', 'message' => 'Пользователь уже существует (ID: ' . $user_id . ')'];
    } else {
        $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
        $insert_user_query = "INSERT INTO users (name, email, password, phone, created_at) VALUES (?, ?, ?, ?, NOW())";
        $insert_user_stmt = $db->prepare($insert_user_query);
        
        if ($insert_user_stmt->execute([$admin_name, $admin_email, $hashed_password, $admin_phone])) {
            $user_id = $db->lastInsertId();
            $steps[] = ['status' => 'success', 'message' => 'Пользователь создан (ID: ' . $user_id . ')'];
        } else {
            throw new Exception("Ошибка при создании пользователя");
        }
    }
} catch(PDOException $e) {
    $steps[] = ['status' => 'error', 'message' => 'Ошибка работы с пользователем: ' . $e->getMessage()];
    $user_id = null;
}

// Шаг 3: Создание администратора
if ($user_id) {
    try {
        $check_admin_query = "SELECT id FROM admins WHERE email = ?";
        $check_admin_stmt = $db->prepare($check_admin_query);
        $check_admin_stmt->execute([$admin_email]);
        
        if ($check_admin_stmt->rowCount() > 0) {
            // Обновляем существующего администратора
            $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
            $update_admin_query = "UPDATE admins SET user_id = ?, name = ?, password = ?, phone = ?, role = ?, is_active = 1, updated_at = NOW() WHERE email = ?";
            $update_admin_stmt = $db->prepare($update_admin_query);
            $update_admin_stmt->execute([$user_id, $admin_name, $hashed_password, $admin_phone, $admin_role, $admin_email]);
            
            $admin = $check_admin_stmt->fetch(PDO::FETCH_ASSOC);
            $admin_id = $admin['id'];
            $message = "Администратор обновлен! ID: {$admin_id}";
            $message_type = 'warning';
            $steps[] = ['status' => 'warning', 'message' => 'Администратор обновлен (ID: ' . $admin_id . ')'];
        } else {
            // Создаем нового администратора
            $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
            $insert_admin_query = "INSERT INTO admins (user_id, name, email, password, phone, role, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)";
            $insert_admin_stmt = $db->prepare($insert_admin_query);
            
            if ($insert_admin_stmt->execute([$user_id, $admin_name, $admin_email, $hashed_password, $admin_phone, $admin_role])) {
                $admin_id = $db->lastInsertId();
                $message = "Администратор успешно создан! ID: {$admin_id}";
                $message_type = 'success';
                $steps[] = ['status' => 'success', 'message' => 'Администратор создан (ID: ' . $admin_id . ')'];
            } else {
                throw new Exception("Ошибка при создании администратора");
            }
        }
    } catch(PDOException $e) {
        $message = "Ошибка: " . $e->getMessage();
        $message_type = 'error';
        $steps[] = ['status' => 'error', 'message' => 'Ошибка создания администратора: ' . $e->getMessage()];
    }
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
            max-width: 700px;
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
        
        .steps {
            margin-bottom: 1.5rem;
        }
        
        .step {
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .step.success {
            background: #d4edda;
            color: #155724;
        }
        
        .step.error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .step.info {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .step.warning {
            background: #fff3cd;
            color: #856404;
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
            margin-top: 0.5rem;
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
        
        <?php if (!empty($steps)): ?>
            <div class="steps">
                <h3 style="margin-bottom: 1rem; color: #2c3e50;">Шаги выполнения:</h3>
                <?php foreach ($steps as $step): ?>
                    <div class="step <?php echo $step['status']; ?>">
                        <i class="fas fa-<?php 
                            echo $step['status'] == 'success' ? 'check-circle' : 
                                ($step['status'] == 'error' ? 'times-circle' : 
                                ($step['status'] == 'warning' ? 'exclamation-triangle' : 'info-circle')); 
                        ?>"></i>
                        <?php echo htmlspecialchars($step['message']); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="info-box">
            <h3>Данные администратора:</h3>
            <ul>
                <li><strong>Имя:</strong> <?php echo htmlspecialchars($admin_name); ?></li>
                <li><strong>Email:</strong> <?php echo htmlspecialchars($admin_email); ?></li>
                <li><strong>Пароль:</strong> <?php echo htmlspecialchars($admin_password); ?></li>
                <li><strong>Телефон:</strong> <?php echo htmlspecialchars($admin_phone); ?></li>
                <li><strong>Роль:</strong> <?php echo htmlspecialchars($admin_role); ?></li>
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
                <li>✓ Создана таблица <strong>admins</strong> для администраторов</li>
                <li>✓ Создан пользователь в таблице <strong>users</strong></li>
                <li>✓ Создана запись администратора в таблице <strong>admins</strong></li>
                <li>✓ Установлена связь между пользователем и администратором</li>
            </ul>
        </div>
        
        <div class="info-box">
            <h3>Структура таблицы admins:</h3>
            <ul>
                <li><strong>id</strong> - Уникальный идентификатор</li>
                <li><strong>user_id</strong> - Связь с таблицей users</li>
                <li><strong>name</strong> - Имя администратора</li>
                <li><strong>email</strong> - Email (уникальный)</li>
                <li><strong>password</strong> - Хешированный пароль</li>
                <li><strong>phone</strong> - Телефон</li>
                <li><strong>role</strong> - Роль (admin, super_admin, manager)</li>
                <li><strong>is_active</strong> - Активен ли администратор</li>
                <li><strong>last_login</strong> - Последний вход</li>
                <li><strong>created_at</strong> - Дата создания</li>
                <li><strong>updated_at</strong> - Дата обновления</li>
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
        
        <div class="info-box" style="margin-top: 2rem;">
            <h3>📚 Инструкции по использованию:</h3>
            <ul>
                <li><a href="QUICK_START.md" target="_blank" style="color: #1976D2; text-decoration: underline;">🚀 Быстрый старт</a> - краткая инструкция</li>
                <li><a href="STEP_BY_STEP.md" target="_blank" style="color: #1976D2; text-decoration: underline;">📖 Пошаговая инструкция</a> - подробное руководство</li>
                <li><a href="INSTRUCTION.md" target="_blank" style="color: #1976D2; text-decoration: underline;">📋 Полная инструкция</a> - со всеми деталями</li>
                <li><a href="LOGIN_GUIDE.txt" target="_blank" style="color: #1976D2; text-decoration: underline;">📄 Краткая памятка</a> - для печати</li>
            </ul>
        </div>
        
        <p style="text-align: center; margin-top: 2rem; color: #7f8c8d; font-size: 0.9rem;">
            <i class="fas fa-info-circle"></i> Этот скрипт можно запустить несколько раз. 
            Если администратор уже существует, он будет обновлен.
        </p>
    </div>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</body>
</html>

