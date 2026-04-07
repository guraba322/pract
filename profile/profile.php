<?php
session_start();
require_once '../config/database.php';

// Проверка авторизации
if (!isset($_SESSION['user'])) {
    header('Location: ../login/login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    die("Ошибка подключения к базе данных");
}

// Получение данных пользователя
$user_id = $_SESSION['user']['id'];
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Получение билетов пользователя
try {
    $tickets_query = "SELECT * FROM tickets WHERE user_id = ? ORDER BY purchase_date DESC";
    $tickets_stmt = $db->prepare($tickets_query);
    $tickets_stmt->execute([$user_id]);
    $tickets = $tickets_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $tickets = [];
}

// Получение записей на тренировки
try {
    $bookings_query = "SELECT * FROM class_bookings WHERE user_id = ? ORDER BY class_date ASC, class_time ASC";
    $bookings_stmt = $db->prepare($bookings_query);
    $bookings_stmt->execute([$user_id]);
    $bookings = $bookings_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $bookings = [];
}

// Получение абонементов пользователя
try {
    $subscriptions_query = "SELECT * FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC";
    $subscriptions_stmt = $db->prepare($subscriptions_query);
    $subscriptions_stmt->execute([$user_id]);
    $subscriptions = $subscriptions_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Обновление статуса абонементов
    foreach ($subscriptions as $sub) {
        $end_date = strtotime($sub['end_date']);
        $today = strtotime(date('Y-m-d'));
        
        if ($end_date < $today && $sub['status'] == 'active') {
            $update_query = "UPDATE subscriptions SET status = 'expired' WHERE id = ?";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->execute([$sub['id']]);
        }
    }
    
    // Перезагружаем абонементы после обновления
    $subscriptions_stmt->execute([$user_id]);
    $subscriptions = $subscriptions_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $subscriptions = [];
}

// Обработка обновления профиля
$update_success = '';
$update_error = '';

// Сообщения о записях
if (isset($_SESSION['booking_message'])) {
    if ($_SESSION['booking_message_type'] == 'success') {
        $update_success = $_SESSION['booking_message'];
    } else {
        $update_error = $_SESSION['booking_message'];
    }
    unset($_SESSION['booking_message']);
    unset($_SESSION['booking_message_type']);
}

// Обработка покупки абонемента из профиля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_subscription'])) {
    $type = $_POST['type'] ?? '';
    $duration = intval($_POST['duration'] ?? 1);
    $price = floatval($_POST['price'] ?? 0);
    
    try {
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime("+{$duration} months"));
        
        $query = "INSERT INTO subscriptions (user_id, type, start_date, end_date, price, status, created_at) 
                  VALUES (?, ?, ?, ?, ?, 'active', NOW())";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$user_id, $type, $start_date, $end_date, $price])) {
            $update_success = "Абонемент успешно приобретен!";
            // Перезагружаем абонементы
            $subscriptions_stmt->execute([$user_id]);
            $subscriptions = $subscriptions_stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $update_error = "Ошибка при покупке абонемента.";
        }
    } catch(PDOException $e) {
        $update_error = "Ошибка: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    try {
        // Проверка уникальности email (кроме текущего пользователя)
        $check_email_query = "SELECT id FROM users WHERE email = ? AND id != ?";
        $check_stmt = $db->prepare($check_email_query);
        $check_stmt->execute([$email, $user_id]);
        
        if ($check_stmt->rowCount() > 0) {
            $update_error = "Пользователь с таким email уже существует!";
        } else {
            // Обновление данных
            $update_query = "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?";
            $update_stmt = $db->prepare($update_query);
            
            if ($update_stmt->execute([$name, $email, $phone, $user_id])) {
                $update_success = "Профиль успешно обновлен!";
                // Обновляем данные в сессии
                $_SESSION['user']['name'] = $name;
                $_SESSION['user']['email'] = $email;
                // Перезагружаем данные пользователя
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $update_error = "Ошибка при обновлении профиля!";
            }
        }
    } catch(PDOException $e) {
        $update_error = "Ошибка при обновлении профиля: " . $e->getMessage();
    }
}

// Обработка смены пароля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    try {
        // Проверка текущего пароля
        if (!password_verify($current_password, $user['password'])) {
            $update_error = "Текущий пароль указан неверно!";
        } elseif ($new_password !== $confirm_password) {
            $update_error = "Новые пароли не совпадают!";
        } elseif (strlen($new_password) < 6) {
            $update_error = "Новый пароль должен содержать минимум 6 символов!";
        } else {
            // Обновление пароля
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_password_query = "UPDATE users SET password = ? WHERE id = ?";
            $update_password_stmt = $db->prepare($update_password_query);
            
            if ($update_password_stmt->execute([$hashed_password, $user_id])) {
                $update_success = "Пароль успешно изменен!";
            } else {
                $update_error = "Ошибка при изменении пароля!";
            }
        }
    } catch(PDOException $e) {
        $update_error = "Ошибка при изменении пароля: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мой профиль - Фитнес-клуб "Энергия"</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="profile.css">
</head>
<body>
    <div class="profile-container">
        <!-- Боковая панель -->
        <aside class="sidebar">
            <div class="profile-header">
                <div class="avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <h2><?php echo htmlspecialchars($user['name']); ?></h2>
                <p>Участник фитнес-клуба</p>
            </div>
            
            <nav class="profile-nav">
                <a href="../index.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Главная</span>
                </a>
                <a href="#personal-info" class="nav-link active">
                    <i class="fas fa-user"></i>
                    <span>Личная информация</span>
                </a>
                <a href="#subscriptions" class="nav-link">
                    <i class="fas fa-id-card"></i>
                    <span>Мои абонементы</span>
                </a>
                <a href="#tickets" class="nav-link">
                    <i class="fas fa-ticket-alt"></i>
                    <span>Мои билеты</span>
                </a>
                <a href="#bookings" class="nav-link">
                    <i class="fas fa-calendar-check"></i>
                    <span>Мои записи</span>
                </a>
                <a href="../logout.php" class="nav-link logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Выйти</span>
                </a>
            </nav>
        </aside>

        <!-- Основной контент -->
        <main class="main-content">
            <div class="content-header">
                <h1>Мой профиль</h1>
                <p>Управление вашей учетной записью и настройками</p>
            </div>

            <!-- Сообщения -->
            <?php if ($update_success): ?>
                <div class="message success-message">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $update_success; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($update_error): ?>
                <div class="message error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $update_error; ?>
                </div>
            <?php endif; ?>

            <!-- Личная информация -->
            <section id="personal-info" class="profile-section">
                <div class="section-header">
                    <h2>Личная информация</h2>
                    <p>Основные данные вашего профиля</p>
                </div>
                
                <form method="POST" class="profile-form">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name">Имя и фамилия</label>
                            <input type="text" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($user['name']); ?>" 
                                   class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email адрес</label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" 
                                   class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Телефон</label>
                            <input type="tel" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                                   class="form-input">
                        </div>
                        
                        <div class="form-group">
                            <label for="member_since">Участник с</label>
                            <input type="text" id="member_since" 
                                   value="<?php echo date('d.m.Y', strtotime($user['created_at'])); ?>" 
                                   class="form-input" disabled>
                        </div>
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-save"></i>
                        Сохранить изменения
                    </button>
                </form>
            </section>

            <!-- Смена пароля -->
            <section class="profile-section">
                <div class="section-header">
                    <h2>Смена пароля</h2>
                    <p>Обновите ваш пароль для безопасности</p>
                </div>
                
                <form method="POST" class="profile-form">
                    <input type="hidden" name="change_password" value="1">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="current_password">Текущий пароль</label>
                            <input type="password" id="current_password" name="current_password" 
                                   class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">Новый пароль</label>
                            <input type="password" id="new_password" name="new_password" 
                                   class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Подтвердите новый пароль</label>
                            <input type="password" id="confirm_password" name="confirm_password" 
                                   class="form-input" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="submit-btn change-password-btn">
                        <i class="fas fa-key"></i>
                        Сменить пароль
                    </button>
                </form>
            </section>

            <!-- Мои абонементы -->
            <section class="profile-section" id="subscriptions">
                <div class="section-header">
                    <h2>Мои абонементы</h2>
                    <p>Ваши активные и истекшие абонементы</p>
                </div>
                
                <?php if (empty($subscriptions)): ?>
                    <div style="text-align: center; padding: 2rem; background: #f8f9fa; border-radius: 10px; margin-bottom: 2rem;">
                        <i class="fas fa-id-card" style="font-size: 3rem; color: #bdc3c7; margin-bottom: 1rem;"></i>
                        <p style="color: #7f8c8d;">У вас пока нет абонементов</p>
                        <a href="../my_subscriptions.php" style="display: inline-block; margin-top: 1rem; padding: 0.75rem 1.5rem; background: #3498db; color: white; text-decoration: none; border-radius: 5px;">
                            <i class="fas fa-shopping-cart"></i> Купить абонемент
                        </a>
                    </div>
                <?php else: ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                        <?php foreach ($subscriptions as $subscription): 
                            $status_class = $subscription['status'] == 'active' ? 'active' : 'expired';
                            $status_text = $subscription['status'] == 'active' ? 'Активен' : 'Истек';
                            $days_left = 0;
                            if ($subscription['status'] == 'active') {
                                $end_date = strtotime($subscription['end_date']);
                                $today = strtotime(date('Y-m-d'));
                                $days_left = max(0, floor(($end_date - $today) / 86400));
                            }
                            $border_color = $subscription['status'] == 'active' ? '#27ae60' : '#e74c3c';
                            $status_bg = $subscription['status'] == 'active' ? '#d4edda' : '#f8d7da';
                            $status_color = $subscription['status'] == 'active' ? '#155724' : '#721c24';
                        ?>
                            <div style="background: white; border-radius: 10px; padding: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-left: 4px solid <?php echo $border_color; ?>;">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                    <div>
                                        <h3 style="color: #2c3e50; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($subscription['type']); ?></h3>
                                    </div>
                                    <span style="padding: 0.25rem 0.75rem; border-radius: 15px; font-size: 0.8rem; font-weight: 600; background: <?php echo $status_bg; ?>; color: <?php echo $status_color; ?>;">
                                        <?php echo $status_text; ?>
                                    </span>
                                </div>
                                <div style="border-top: 1px solid #ecf0f1; padding-top: 1rem;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                        <span style="color: #7f8c8d;">Начало:</span>
                                        <span style="color: #2c3e50; font-weight: 600;"><?php echo date('d.m.Y', strtotime($subscription['start_date'])); ?></span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                        <span style="color: #7f8c8d;">Окончание:</span>
                                        <span style="color: #2c3e50; font-weight: 600;"><?php echo date('d.m.Y', strtotime($subscription['end_date'])); ?></span>
                                    </div>
                                    <?php if ($subscription['status'] == 'active' && $days_left > 0): ?>
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                            <span style="color: #7f8c8d;">Осталось дней:</span>
                                            <span style="color: #27ae60; font-weight: 600;"><?php echo $days_left; ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                        <span style="color: #7f8c8d;">Стоимость:</span>
                                        <span style="color: #2c3e50; font-weight: 600;"><?php echo number_format($subscription['price'], 0, ',', ' '); ?> ₽</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; color: #95a5a6;">
                                        <span>Куплен:</span>
                                        <span><?php echo date('d.m.Y', strtotime($subscription['created_at'])); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="text-align: center;">
                        <a href="../my_subscriptions.php" style="display: inline-block; padding: 0.75rem 1.5rem; background: #3498db; color: white; text-decoration: none; border-radius: 5px; margin-right: 1rem;">
                            <i class="fas fa-plus"></i> Купить еще абонемент
                        </a>
                    </div>
                <?php endif; ?>
                
                <!-- Быстрая покупка абонемента -->
                <div style="margin-top: 2rem; padding: 2rem; background: #f8f9fa; border-radius: 10px;">
                    <h3 style="color: #2c3e50; margin-bottom: 1rem;">Быстрая покупка абонемента</h3>
                    <form method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; color: #2c3e50; font-weight: 500;">Тип абонемента:</label>
                            <select name="type" required style="width: 100%; padding: 0.75rem; border: 2px solid #ecf0f1; border-radius: 5px;">
                                <option value="">Выберите тип</option>
                                <option value="Базовый">Базовый (1 месяц - 2000 ₽)</option>
                                <option value="Стандарт">Стандарт (3 месяца - 5000 ₽)</option>
                                <option value="Премиум">Премиум (6 месяцев - 9000 ₽)</option>
                                <option value="VIP">VIP (12 месяцев - 15000 ₽)</option>
                            </select>
                        </div>
                        <input type="hidden" name="duration" id="duration" value="1">
                        <input type="hidden" name="price" id="price" value="2000">
                        <button type="submit" name="buy_subscription" style="padding: 0.75rem 1.5rem; background: #27ae60; color: white; border: none; border-radius: 5px; font-weight: 600; cursor: pointer; transition: background 0.3s;">
                            <i class="fas fa-shopping-cart"></i> Купить
                        </button>
                    </form>
                </div>
            </section>

            <!-- Мои билеты -->
            <section class="profile-section" id="tickets">
                <div class="section-header">
                    <h2>Мои билеты</h2>
                    <p>Купленные билеты на занятия</p>
                </div>
                
                <?php if (empty($tickets)): ?>
                    <div style="text-align: center; padding: 2rem; background: #f8f9fa; border-radius: 10px;">
                        <i class="fas fa-ticket-alt" style="font-size: 3rem; color: #bdc3c7; margin-bottom: 1rem;"></i>
                        <p style="color: #7f8c8d;">У вас пока нет билетов</p>
                        <a href="../buy_tickets.php" style="display: inline-block; margin-top: 1rem; padding: 0.75rem 1.5rem; background: #3498db; color: white; text-decoration: none; border-radius: 5px;">
                            <i class="fas fa-shopping-cart"></i> Купить билеты
                        </a>
                    </div>
                <?php else: ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
                        <?php foreach ($tickets as $ticket): 
                            $status_class = '';
                            $status_text = '';
                            switch($ticket['status']) {
                                case 'active':
                                    $status_class = 'background: #d4edda; color: #155724;';
                                    $status_text = 'Активен';
                                    break;
                                case 'used':
                                    $status_class = 'background: #e2e3e5; color: #383d41;';
                                    $status_text = 'Использован';
                                    break;
                                case 'expired':
                                    $status_class = 'background: #f8d7da; color: #721c24;';
                                    $status_text = 'Истек';
                                    break;
                                default:
                                    $status_class = 'background: #fff3cd; color: #856404;';
                                    $status_text = 'Отменен';
                            }
                        ?>
                            <div style="background: white; border-radius: 10px; padding: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-left: 4px solid #3498db;">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                    <div>
                                        <h3 style="color: #2c3e50; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($ticket['ticket_type']); ?></h3>
                                        <?php if ($ticket['event_name']): ?>
                                            <p style="color: #7f8c8d; font-size: 0.9rem;"><?php echo htmlspecialchars($ticket['event_name']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <span style="padding: 0.25rem 0.75rem; border-radius: 15px; font-size: 0.8rem; font-weight: 600; <?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </div>
                                <div style="border-top: 1px solid #ecf0f1; padding-top: 1rem;">
                                    <?php if ($ticket['event_date']): ?>
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                            <span style="color: #7f8c8d;">Дата:</span>
                                            <span style="color: #2c3e50; font-weight: 600;"><?php echo date('d.m.Y', strtotime($ticket['event_date'])); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($ticket['event_time']): ?>
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                            <span style="color: #7f8c8d;">Время:</span>
                                            <span style="color: #2c3e50; font-weight: 600;"><?php echo date('H:i', strtotime($ticket['event_time'])); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                        <span style="color: #7f8c8d;">Стоимость:</span>
                                        <span style="color: #2c3e50; font-weight: 600;"><?php echo number_format($ticket['price'], 0, ',', ' '); ?> ₽</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; color: #95a5a6;">
                                        <span>Куплен:</span>
                                        <span><?php echo date('d.m.Y', strtotime($ticket['purchase_date'])); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="text-align: center; margin-top: 2rem;">
                        <a href="../buy_tickets.php" style="display: inline-block; padding: 0.75rem 1.5rem; background: #3498db; color: white; text-decoration: none; border-radius: 5px;">
                            <i class="fas fa-plus"></i> Купить еще билеты
                        </a>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Мои записи на тренировки -->
            <section class="profile-section" id="bookings">
                <div class="section-header">
                    <h2>Мои записи на тренировки</h2>
                    <p>Ваши предстоящие и прошедшие занятия</p>
                </div>
                
                <?php if (empty($bookings)): ?>
                    <div style="text-align: center; padding: 2rem; background: #f8f9fa; border-radius: 10px; margin-bottom: 2rem;">
                        <i class="fas fa-calendar-check" style="font-size: 3rem; color: #bdc3c7; margin-bottom: 1rem;"></i>
                        <p style="color: #7f8c8d;">У вас пока нет записей на тренировки</p>
                        <a href="../schedule.php" style="display: inline-block; margin-top: 1rem; padding: 0.75rem 1.5rem; background: #3498db; color: white; text-decoration: none; border-radius: 5px;">
                            <i class="fas fa-calendar-plus"></i> Посмотреть расписание
                        </a>
                    </div>
                <?php else: 
                    $upcoming_bookings = array_filter($bookings, function($b) {
                        $booking_date = strtotime($b['class_date'] . ' ' . $b['class_time']);
                        $now = time();
                        return $booking_date >= $now && $b['status'] == 'booked';
                    });
                    $past_bookings = array_filter($bookings, function($b) {
                        $booking_date = strtotime($b['class_date'] . ' ' . $b['class_time']);
                        $now = time();
                        return $booking_date < $now || $b['status'] != 'booked';
                    });
                ?>
                    <?php if (!empty($upcoming_bookings)): ?>
                        <h3 style="color: #2c3e50; margin-bottom: 1rem; margin-top: 1rem;">Предстоящие занятия</h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                            <?php foreach ($upcoming_bookings as $booking): ?>
                                <div style="background: white; border-radius: 10px; padding: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-left: 4px solid #27ae60;">
                                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                        <div>
                                            <h3 style="color: #2c3e50; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($booking['class_name']); ?></h3>
                                            <p style="color: #7f8c8d; font-size: 0.9rem;"><?php echo htmlspecialchars($booking['trainer']); ?></p>
                                        </div>
                                        <span style="padding: 0.25rem 0.75rem; border-radius: 15px; font-size: 0.8rem; font-weight: 600; background: #d4edda; color: #155724;">
                                            Записан
                                        </span>
                                    </div>
                                    <div style="border-top: 1px solid #ecf0f1; padding-top: 1rem;">
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                            <span style="color: #7f8c8d;">Дата:</span>
                                            <span style="color: #2c3e50; font-weight: 600;"><?php echo date('d.m.Y', strtotime($booking['class_date'])); ?></span>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                            <span style="color: #7f8c8d;">Время:</span>
                                            <span style="color: #2c3e50; font-weight: 600;"><?php echo date('H:i', strtotime($booking['class_time'])); ?></span>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                            <span style="color: #7f8c8d;">Зал:</span>
                                            <span style="color: #2c3e50; font-weight: 600;"><?php echo htmlspecialchars($booking['room']); ?></span>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                            <span style="color: #7f8c8d;">Длительность:</span>
                                            <span style="color: #2c3e50; font-weight: 600;"><?php echo htmlspecialchars($booking['duration']); ?></span>
                                        </div>
                                        <form method="POST" action="../register_class.php" style="margin-top: 1rem;">
                                            <input type="hidden" name="cancel_booking" value="1">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <input type="hidden" name="redirect" value="profile.php">
                                            <button type="submit" style="width: 100%; padding: 0.5rem; background: #e74c3c; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 500;">
                                                <i class="fas fa-times"></i> Отменить запись
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($past_bookings)): ?>
                        <h3 style="color: #2c3e50; margin-bottom: 1rem; margin-top: 1rem;">Прошедшие занятия</h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
                            <?php foreach ($past_bookings as $booking): 
                                $status_class = $booking['status'] == 'cancelled' ? 'background: #f8d7da; color: #721c24;' : 'background: #e2e3e5; color: #383d41;';
                                $status_text = $booking['status'] == 'cancelled' ? 'Отменен' : 'Завершен';
                            ?>
                                <div style="background: white; border-radius: 10px; padding: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-left: 4px solid #95a5a6; opacity: 0.7;">
                                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                        <div>
                                            <h3 style="color: #2c3e50; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($booking['class_name']); ?></h3>
                                            <p style="color: #7f8c8d; font-size: 0.9rem;"><?php echo htmlspecialchars($booking['trainer']); ?></p>
                                        </div>
                                        <span style="padding: 0.25rem 0.75rem; border-radius: 15px; font-size: 0.8rem; font-weight: 600; <?php echo $status_class; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </div>
                                    <div style="border-top: 1px solid #ecf0f1; padding-top: 1rem;">
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                            <span style="color: #7f8c8d;">Дата:</span>
                                            <span style="color: #2c3e50; font-weight: 600;"><?php echo date('d.m.Y', strtotime($booking['class_date'])); ?></span>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                            <span style="color: #7f8c8d;">Время:</span>
                                            <span style="color: #2c3e50; font-weight: 600;"><?php echo date('H:i', strtotime($booking['class_time'])); ?></span>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; font-size: 0.85rem; color: #95a5a6;">
                                            <span>Запись создана:</span>
                                            <span><?php echo date('d.m.Y', strtotime($booking['created_at'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div style="text-align: center; margin-top: 2rem;">
                        <a href="../schedule.php" style="display: inline-block; padding: 0.75rem 1.5rem; background: #3498db; color: white; text-decoration: none; border-radius: 5px;">
                            <i class="fas fa-calendar-plus"></i> Записаться на тренировку
                        </a>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Статистика -->
            <section class="profile-section">
                <div class="section-header">
                    <h2>Моя активность</h2>
                    <p>Ваша статистика посещений</p>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-id-card"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo count(array_filter($subscriptions, function($s) { return $s['status'] == 'active'; })); ?></h3>
                            <p>Активных абонементов</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo count(array_filter($tickets, function($t) { return $t['status'] == 'active'; })); ?></h3>
                            <p>Активных билетов</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo count(array_filter($bookings, function($b) { return $b['status'] == 'booked'; })); ?></h3>
                            <p>Активных записей</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo count($tickets) + count($subscriptions); ?></h3>
                            <p>Всего покупок</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-ruble-sign"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php 
                                $total_tickets = array_sum(array_column($tickets, 'price'));
                                $total_subs = array_sum(array_column($subscriptions, 'price'));
                                echo number_format($total_tickets + $total_subs, 0, ',', ' '); 
                            ?> ₽</h3>
                            <p>Всего потрачено</p>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script src="profile.js"></script>
</body>
</html>