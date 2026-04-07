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

// Обработка покупки билета
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_ticket'])) {
    $ticket_type = $_POST['ticket_type'] ?? '';
    $event_name = $_POST['event_name'] ?? '';
    $event_date = $_POST['event_date'] ?? null;
    $event_time = $_POST['event_time'] ?? null;
    $price = floatval($_POST['price'] ?? 0);
    
    try {
        $query = "INSERT INTO tickets (user_id, ticket_type, event_name, event_date, event_time, price, status, purchase_date) 
                  VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$user_id, $ticket_type, $event_name, $event_date, $event_time, $price])) {
            $message = "Билет успешно приобретен!";
            $message_type = 'success';
        } else {
            $message = "Ошибка при покупке билета.";
            $message_type = 'error';
        }
    } catch(PDOException $e) {
        $message = "Ошибка: " . $e->getMessage();
        $message_type = 'error';
    }
}

// Определение текущей страницы для breadcrumbs
$current_page = basename($_SERVER['PHP_SELF']);
$breadcrumbs = [
    ['title' => 'Главная', 'url' => 'index.php', 'active' => false],
    ['title' => 'Купить билеты', 'url' => 'buy_tickets.php', 'active' => true]
];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Купить билеты - Фитнес-клуб "Энергия"</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="animations.css">
    <script src="animations.js" defer></script>
    <style>
        .tickets-container {
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
        
        .tickets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .ticket-card {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            border-top: 4px solid #3498db;
        }
        
        .ticket-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .ticket-card.personal {
            border-top-color: #e74c3c;
        }
        
        .ticket-card.group {
            border-top-color: #27ae60;
        }
        
        .ticket-card.single {
            border-top-color: #3498db;
        }
        
        .ticket-card.massage {
            border-top-color: #9b59b6;
        }
        
        .ticket-card.sauna {
            border-top-color: #f39c12;
        }
        
        .ticket-card.solarium {
            border-top-color: #e67e22;
        }
        
        .ticket-card.crossfit {
            border-top-color: #c0392b;
        }
        
        .ticket-card.dance {
            border-top-color: #16a085;
        }
        
        .ticket-header {
            margin-bottom: 1.5rem;
        }
        
        .ticket-type {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .ticket-description {
            color: #7f8c8d;
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
        }
        
        .ticket-price {
            font-size: 2rem;
            font-weight: 700;
            color: #3498db;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .ticket-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            margin-bottom: 0.5rem;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select {
            padding: 0.75rem;
            border: 2px solid #ecf0f1;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .buy-ticket-btn {
            width: 100%;
            padding: 1rem;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            margin-top: 0.5rem;
        }
        
        .buy-ticket-btn:hover {
            background: #2980b9;
        }
        
        .buy-ticket-btn.personal {
            background: #e74c3c;
        }
        
        .buy-ticket-btn.personal:hover {
            background: #c0392b;
        }
        
        .buy-ticket-btn.group {
            background: #27ae60;
        }
        
        .buy-ticket-btn.group:hover {
            background: #219a52;
        }
        
        .buy-ticket-btn.massage {
            background: #9b59b6;
        }
        
        .buy-ticket-btn.massage:hover {
            background: #8e44ad;
        }
        
        .buy-ticket-btn.sauna {
            background: #f39c12;
        }
        
        .buy-ticket-btn.sauna:hover {
            background: #e67e22;
        }
        
        .buy-ticket-btn.solarium {
            background: #e67e22;
        }
        
        .buy-ticket-btn.solarium:hover {
            background: #d35400;
        }
        
        .buy-ticket-btn.crossfit {
            background: #c0392b;
        }
        
        .buy-ticket-btn.crossfit:hover {
            background: #a93226;
        }
        
        .buy-ticket-btn.dance {
            background: #16a085;
        }
        
        .buy-ticket-btn.dance:hover {
            background: #138d75;
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
                <a href="buy_tickets.php">Купить билеты</a>
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

    <div class="tickets-container">
        <div class="page-header">
            <h1><i class="fas fa-ticket-alt"></i> Купить билеты</h1>
            <p>Выберите тип билета для посещения занятий</p>
        </div>

        <?php if ($message): ?>
            <div class="<?php echo $message_type == 'success' ? 'success-message' : 'error-message'; ?>" style="margin-bottom: 2rem;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="tickets-grid">
            <!-- Разовое посещение -->
            <div class="ticket-card single">
                <div class="ticket-header">
                    <div class="ticket-type">Разовое посещение</div>
                    <div class="ticket-description">
                        Доступ в тренажерный зал на один день. Включает все зоны клуба.
                    </div>
                    <div class="ticket-price">500 ₽</div>
                </div>
                <form method="POST" class="ticket-form">
                    <input type="hidden" name="ticket_type" value="Разовое посещение">
                    <input type="hidden" name="price" value="500">
                    <div class="form-group">
                        <label>Дата посещения:</label>
                        <input type="date" name="event_date" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <button type="submit" name="buy_ticket" class="buy-ticket-btn">
                        <i class="fas fa-shopping-cart"></i> Купить билет
                    </button>
                </form>
            </div>

            <!-- Групповое занятие -->
            <div class="ticket-card group">
                <div class="ticket-header">
                    <div class="ticket-type">Групповое занятие</div>
                    <div class="ticket-description">
                        Билет на одно групповое занятие (йога, пилатес, функциональный тренинг).
                    </div>
                    <div class="ticket-price">800 ₽</div>
                </div>
                <form method="POST" class="ticket-form">
                    <input type="hidden" name="ticket_type" value="Групповое занятие">
                    <input type="hidden" name="price" value="800">
                    <div class="form-group">
                        <label>Название занятия:</label>
                        <select name="event_name" required>
                            <option value="">Выберите занятие</option>
                            <option value="Йога">Йога</option>
                            <option value="Пилатес">Пилатес</option>
                            <option value="Функциональный тренинг">Функциональный тренинг</option>
                            <option value="Стретчинг">Стретчинг</option>
                            <option value="Аэробика">Аэробика</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Дата занятия:</label>
                        <input type="date" name="event_date" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Время занятия:</label>
                        <input type="time" name="event_time" required>
                    </div>
                    <button type="submit" name="buy_ticket" class="buy-ticket-btn group">
                        <i class="fas fa-shopping-cart"></i> Купить билет
                    </button>
                </form>
            </div>

            <!-- Персональная тренировка -->
            <div class="ticket-card personal">
                <div class="ticket-header">
                    <div class="ticket-type">Персональная тренировка</div>
                    <div class="ticket-description">
                        Индивидуальная тренировка с персональным тренером. Длительность 60 минут.
                    </div>
                    <div class="ticket-price">2000 ₽</div>
                </div>
                <form method="POST" class="ticket-form">
                    <input type="hidden" name="ticket_type" value="Персональная тренировка">
                    <input type="hidden" name="price" value="2000">
                    <div class="form-group">
                        <label>Название тренировки:</label>
                        <input type="text" name="event_name" placeholder="Например: Силовая тренировка" required>
                    </div>
                    <div class="form-group">
                        <label>Дата тренировки:</label>
                        <input type="date" name="event_date" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Время тренировки:</label>
                        <input type="time" name="event_time" required>
                    </div>
                    <button type="submit" name="buy_ticket" class="buy-ticket-btn personal">
                        <i class="fas fa-shopping-cart"></i> Купить билет
                    </button>
                </form>
            </div>

            <!-- Массаж -->
            <div class="ticket-card massage">
                <div class="ticket-header">
                    <div class="ticket-type">Массаж</div>
                    <div class="ticket-description">
                        Расслабляющий или лечебный массаж. Длительность 60 минут.
                    </div>
                    <div class="ticket-price">1500 ₽</div>
                </div>
                <form method="POST" class="ticket-form">
                    <input type="hidden" name="ticket_type" value="Массаж">
                    <input type="hidden" name="price" value="1500">
                    <div class="form-group">
                        <label>Тип массажа:</label>
                        <select name="event_name" required>
                            <option value="">Выберите тип</option>
                            <option value="Расслабляющий массаж">Расслабляющий массаж</option>
                            <option value="Лечебный массаж">Лечебный массаж</option>
                            <option value="Спортивный массаж">Спортивный массаж</option>
                            <option value="Антицеллюлитный массаж">Антицеллюлитный массаж</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Дата сеанса:</label>
                        <input type="date" name="event_date" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Время сеанса:</label>
                        <input type="time" name="event_time" required>
                    </div>
                    <button type="submit" name="buy_ticket" class="buy-ticket-btn massage">
                        <i class="fas fa-shopping-cart"></i> Купить билет
                    </button>
                </form>
            </div>

            <!-- Сауна и Хаммам -->
            <div class="ticket-card sauna">
                <div class="ticket-header">
                    <div class="ticket-type">Сауна и Хаммам</div>
                    <div class="ticket-description">
                        Посещение сауны или хаммама. Длительность 2 часа.
                    </div>
                    <div class="ticket-price">1200 ₽</div>
                </div>
                <form method="POST" class="ticket-form">
                    <input type="hidden" name="ticket_type" value="Сауна и Хаммам">
                    <input type="hidden" name="price" value="1200">
                    <div class="form-group">
                        <label>Тип посещения:</label>
                        <select name="event_name" required>
                            <option value="">Выберите тип</option>
                            <option value="Сауна">Сауна</option>
                            <option value="Хаммам">Хаммам</option>
                            <option value="Сауна + Хаммам">Сауна + Хаммам</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Дата посещения:</label>
                        <input type="date" name="event_date" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Время посещения:</label>
                        <input type="time" name="event_time" required>
                    </div>
                    <button type="submit" name="buy_ticket" class="buy-ticket-btn sauna">
                        <i class="fas fa-shopping-cart"></i> Купить билет
                    </button>
                </form>
            </div>

            <!-- Солярий -->
            <div class="ticket-card solarium">
                <div class="ticket-header">
                    <div class="ticket-type">Солярий</div>
                    <div class="ticket-description">
                        Посещение вертикального солярия. Одна сессия.
                    </div>
                    <div class="ticket-price">300 ₽</div>
                </div>
                <form method="POST" class="ticket-form">
                    <input type="hidden" name="ticket_type" value="Солярий">
                    <input type="hidden" name="price" value="300">
                    <div class="form-group">
                        <label>Дата посещения:</label>
                        <input type="date" name="event_date" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Время посещения:</label>
                        <input type="time" name="event_time" required>
                    </div>
                    <button type="submit" name="buy_ticket" class="buy-ticket-btn solarium">
                        <i class="fas fa-shopping-cart"></i> Купить билет
                    </button>
                </form>
            </div>

            <!-- Кроссфит -->
            <div class="ticket-card crossfit">
                <div class="ticket-header">
                    <div class="ticket-type">Кроссфит</div>
                    <div class="ticket-description">
                        Высокоинтенсивная функциональная тренировка. Длительность 60 минут.
                    </div>
                    <div class="ticket-price">1000 ₽</div>
                </div>
                <form method="POST" class="ticket-form">
                    <input type="hidden" name="ticket_type" value="Кроссфит">
                    <input type="hidden" name="price" value="1000">
                    <div class="form-group">
                        <label>Дата тренировки:</label>
                        <input type="date" name="event_date" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Время тренировки:</label>
                        <input type="time" name="event_time" required>
                    </div>
                    <button type="submit" name="buy_ticket" class="buy-ticket-btn crossfit">
                        <i class="fas fa-shopping-cart"></i> Купить билет
                    </button>
                </form>
            </div>

            <!-- Танцевальные классы -->
            <div class="ticket-card dance">
                <div class="ticket-header">
                    <div class="ticket-type">Танцевальные классы</div>
                    <div class="ticket-description">
                        Групповые танцевальные занятия. Длительность 60 минут.
                    </div>
                    <div class="ticket-price">900 ₽</div>
                </div>
                <form method="POST" class="ticket-form">
                    <input type="hidden" name="ticket_type" value="Танцевальные классы">
                    <input type="hidden" name="price" value="900">
                    <div class="form-group">
                        <label>Тип танца:</label>
                        <select name="event_name" required>
                            <option value="">Выберите тип</option>
                            <option value="Zumba">Zumba</option>
                            <option value="Латина">Латина</option>
                            <option value="Современные танцы">Современные танцы</option>
                            <option value="Хип-хоп">Хип-хоп</option>
                            <option value="Бальные танцы">Бальные танцы</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Дата занятия:</label>
                        <input type="date" name="event_date" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Время занятия:</label>
                        <input type="time" name="event_time" required>
                    </div>
                    <button type="submit" name="buy_ticket" class="buy-ticket-btn dance">
                        <i class="fas fa-shopping-cart"></i> Купить билет
                    </button>
                </form>
            </div>
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

