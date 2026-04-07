<?php
session_start();
require_once 'config/database.php';

// Проверка авторизации
if (!isset($_SESSION['user'])) {
    header('Location: login/login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    die("Ошибка подключения к базе данных");
}

$user_id = $_SESSION['user']['id'];
$message = '';
$message_type = '';

// Обработка покупки нового абонемента
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
            $message = "Абонемент успешно приобретен!";
            $message_type = 'success';
        } else {
            $message = "Ошибка при покупке абонемента.";
            $message_type = 'error';
        }
    } catch(PDOException $e) {
        $message = "Ошибка: " . $e->getMessage();
        $message_type = 'error';
    }
}

// Получение абонементов пользователя
try {
    $query = "SELECT * FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
    $stmt->execute([$user_id]);
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $subscriptions = [];
}

// Определение текущей страницы для breadcrumbs
$current_page = basename($_SERVER['PHP_SELF']);
$breadcrumbs = [
    ['title' => 'Главная', 'url' => 'index.php', 'active' => false],
    ['title' => 'Мои абонементы', 'url' => 'my_subscriptions.php', 'active' => true]
];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои абонементы - Фитнес-клуб "Энергия"</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="animations.css">
    <script src="animations.js" defer></script>
    <style>
        .subscriptions-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .page-header h1 {
            color: #2c3e50;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        .subscriptions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .subscription-card {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            border-left: 4px solid #3498db;
        }
        
        .subscription-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .subscription-card.active {
            border-left-color: #27ae60;
        }
        
        .subscription-card.expired {
            border-left-color: #e74c3c;
            opacity: 0.8;
        }
        
        .subscription-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .subscription-type {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .subscription-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-expired {
            background: #f8d7da;
            color: #721c24;
        }
        
        .subscription-info {
            margin-bottom: 1rem;
        }
        
        .subscription-info-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .subscription-info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: #7f8c8d;
            font-weight: 500;
        }
        
        .info-value {
            color: #2c3e50;
            font-weight: 600;
        }
        
        .buy-subscription-section {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-top: 3rem;
        }
        
        .buy-subscription-section h2 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
        }
        
        .subscription-plans {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .plan-card {
            border: 2px solid #ecf0f1;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s;
        }
        
        .plan-card:hover {
            border-color: #3498db;
            transform: translateY(-3px);
        }
        
        .plan-name {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        
        .plan-price {
            font-size: 2rem;
            font-weight: 700;
            color: #3498db;
            margin-bottom: 0.5rem;
        }
        
        .plan-duration {
            color: #7f8c8d;
            margin-bottom: 1.5rem;
        }
        
        .buy-btn {
            width: 100%;
            padding: 0.75rem;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .buy-btn:hover {
            background: #2980b9;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #bdc3c7;
            margin-bottom: 1rem;
        }
        
        .empty-state h3 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .empty-state p {
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo">Фитнес-клуб "Энергия"</div>
        <nav class="nav">
            <a href="index.php">Главная</a>
            <a href="index.php#services">Услуги</a>
            <a href="index.php#schedule">Расписание</a>
            <a href="index.php#contact">Контакты</a>
        </nav>
        <div class="profile-icon" onclick="toggleProfileMenu()">
            <i class="fas fa-user-circle fa-2x"></i>
            <div class="profile-menu" id="profileMenu">
                <span>Привет, <?php echo htmlspecialchars($_SESSION['user']['name']); ?>!</span>
                <a href="profile/profile.php">Мой профиль</a>
                <a href="my_subscriptions.php">Мои абонементы</a>
                <a href="logout.php">Выйти</a>
            </div>
        </div>
    </header>

    <!-- Breadcrumbs -->
    <nav class="breadcrumbs">
        <div class="container">
            <a href="index.php" class="breadcrumb-link">
                <i class="fas fa-home"></i> Главная
            </a>
            <?php foreach ($breadcrumbs as $index => $crumb): ?>
                <span class="breadcrumb-separator">/</span>
                <?php if ($crumb['active']): ?>
                    <span class="breadcrumb-current"><?php echo htmlspecialchars($crumb['title']); ?></span>
                <?php else: ?>
                    <a href="<?php echo htmlspecialchars($crumb['url']); ?>" class="breadcrumb-link">
                        <?php echo htmlspecialchars($crumb['title']); ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </nav>

    <div class="subscriptions-container">
        <div class="page-header">
            <h1><i class="fas fa-id-card"></i> Мои абонементы</h1>
            <p>Управление вашими абонементами</p>
        </div>

        <?php if ($message): ?>
            <div class="<?php echo $message_type == 'success' ? 'success-message' : 'error-message'; ?>" style="margin-bottom: 2rem;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($subscriptions)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>У вас пока нет абонементов</h3>
                <p>Приобретите абонемент, чтобы начать тренироваться</p>
            </div>
        <?php else: ?>
            <div class="subscriptions-grid">
                <?php foreach ($subscriptions as $subscription): 
                    $status_class = $subscription['status'] == 'active' ? 'active' : 'expired';
                    $status_text = $subscription['status'] == 'active' ? 'Активен' : 'Истек';
                    $days_left = 0;
                    if ($subscription['status'] == 'active') {
                        $end_date = strtotime($subscription['end_date']);
                        $today = strtotime(date('Y-m-d'));
                        $days_left = max(0, floor(($end_date - $today) / 86400));
                    }
                ?>
                    <div class="subscription-card <?php echo $status_class; ?>">
                        <div class="subscription-header">
                            <div class="subscription-type"><?php echo htmlspecialchars($subscription['type']); ?></div>
                            <span class="subscription-status status-<?php echo $subscription['status']; ?>">
                                <?php echo $status_text; ?>
                            </span>
                        </div>
                        <div class="subscription-info">
                            <div class="subscription-info-item">
                                <span class="info-label">Начало действия:</span>
                                <span class="info-value"><?php echo date('d.m.Y', strtotime($subscription['start_date'])); ?></span>
                            </div>
                            <div class="subscription-info-item">
                                <span class="info-label">Окончание:</span>
                                <span class="info-value"><?php echo date('d.m.Y', strtotime($subscription['end_date'])); ?></span>
                            </div>
                            <?php if ($subscription['status'] == 'active' && $days_left > 0): ?>
                                <div class="subscription-info-item">
                                    <span class="info-label">Осталось дней:</span>
                                    <span class="info-value" style="color: #27ae60;"><?php echo $days_left; ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="subscription-info-item">
                                <span class="info-label">Стоимость:</span>
                                <span class="info-value"><?php echo number_format($subscription['price'], 0, ',', ' '); ?> ₽</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="buy-subscription-section">
            <h2><i class="fas fa-shopping-cart"></i> Приобрести новый абонемент</h2>
            <form method="POST">
                <div class="subscription-plans">
                    <div class="plan-card">
                        <div class="plan-name">Базовый</div>
                        <div class="plan-price">2000 ₽</div>
                        <div class="plan-duration">1 месяц</div>
                        <button type="submit" name="buy_subscription" class="buy-btn" 
                                onclick="this.form.type.value='Базовый'; this.form.duration.value='1'; this.form.price.value='2000';">
                            Купить
                        </button>
                    </div>
                    <div class="plan-card">
                        <div class="plan-name">Стандарт</div>
                        <div class="plan-price">5000 ₽</div>
                        <div class="plan-duration">3 месяца</div>
                        <button type="submit" name="buy_subscription" class="buy-btn"
                                onclick="this.form.type.value='Стандарт'; this.form.duration.value='3'; this.form.price.value='5000';">
                            Купить
                        </button>
                    </div>
                    <div class="plan-card">
                        <div class="plan-name">Премиум</div>
                        <div class="plan-price">9000 ₽</div>
                        <div class="plan-duration">6 месяцев</div>
                        <button type="submit" name="buy_subscription" class="buy-btn"
                                onclick="this.form.type.value='Премиум'; this.form.duration.value='6'; this.form.price.value='9000';">
                            Купить
                        </button>
                    </div>
                    <div class="plan-card">
                        <div class="plan-name">VIP</div>
                        <div class="plan-price">15000 ₽</div>
                        <div class="plan-duration">12 месяцев</div>
                        <button type="submit" name="buy_subscription" class="buy-btn"
                                onclick="this.form.type.value='VIP'; this.form.duration.value='12'; this.form.price.value='15000';">
                            Купить
                        </button>
                    </div>
                </div>
                <input type="hidden" name="type" value="">
                <input type="hidden" name="duration" value="">
                <input type="hidden" name="price" value="">
            </form>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-container">
            <div class="footer-section">
                <h3>Фитнес-клуб "Энергия"</h3>
                <p>Ваш путь к здоровому образу жизни начинается здесь.</p>
            </div>
            <div class="footer-section">
                <h3>Контакты</h3>
                <ul class="footer-contacts">
                    <li><i class="fas fa-phone"></i> <a href="tel:+79991234567">+7 (999) 123-45-67</a></li>
                    <li><i class="fas fa-envelope"></i> <a href="mailto:info@energia-fitness.ru">info@energia-fitness.ru</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© 2025 Фитнес-клуб "Энергия". Все права защищены.</p>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>

