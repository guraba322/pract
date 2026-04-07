<?php
session_start();
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$user = isset($_SESSION['user']) ? $_SESSION['user'] : null;

// Получение записей пользователя, если он авторизован
$user_bookings = [];
if ($user) {
    try {
        $bookings_query = "SELECT * FROM class_bookings WHERE user_id = ? AND status = 'booked'";
        $bookings_stmt = $db->prepare($bookings_query);
        $bookings_stmt->execute([$user['id']]);
        $user_bookings = $bookings_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $user_bookings = [];
    }
}

// Функция для получения даты по дню недели
function getDateForDay($day_name) {
    $days = ['Понедельник' => 1, 'Вторник' => 2, 'Среда' => 3, 'Четверг' => 4, 'Пятница' => 5, 'Суббота' => 6, 'Воскресенье' => 0];
    $day_num = $days[$day_name] ?? 1;
    $today = new DateTime();
    $today_day = (int)$today->format('w');
    $diff = ($day_num - $today_day + 7) % 7;
    if ($diff == 0) $diff = 7; // Если сегодня этот день, берем следующий
    $target_date = clone $today;
    $target_date->modify("+{$diff} days");
    return $target_date->format('Y-m-d');
}

// Определение текущей страницы
$current_page = basename($_SERVER['PHP_SELF']);
$breadcrumbs = [
    ['title' => 'Главная', 'url' => 'index.php', 'active' => false],
    ['title' => 'Расписание', 'url' => 'schedule.php', 'active' => true]
];

// Сообщения
$message = isset($_SESSION['booking_message']) ? $_SESSION['booking_message'] : '';
$message_type = isset($_SESSION['booking_message_type']) ? $_SESSION['booking_message_type'] : '';
unset($_SESSION['booking_message']);
unset($_SESSION['booking_message_type']);

// Расписание занятий
$schedule = [
    'Понедельник' => [
        ['time' => '07:00', 'class' => 'Йога', 'trainer' => 'Анна Петрова', 'room' => 'Зал 1', 'duration' => '60 мин'],
        ['time' => '09:00', 'class' => 'Пилатес', 'trainer' => 'Мария Иванова', 'room' => 'Зал 2', 'duration' => '45 мин'],
        ['time' => '10:30', 'class' => 'Кроссфит', 'trainer' => 'Дмитрий Сидоров', 'room' => 'Зал 3', 'duration' => '60 мин'],
        ['time' => '12:00', 'class' => 'Zumba', 'trainer' => 'Елена Козлова', 'room' => 'Зал 1', 'duration' => '60 мин'],
        ['time' => '18:00', 'class' => 'Силовая тренировка', 'trainer' => 'Алексей Волков', 'room' => 'Зал 3', 'duration' => '60 мин'],
        ['time' => '19:30', 'class' => 'Стретчинг', 'trainer' => 'Анна Петрова', 'room' => 'Зал 2', 'duration' => '45 мин'],
        ['time' => '20:30', 'class' => 'Функциональный тренинг', 'trainer' => 'Дмитрий Сидоров', 'room' => 'Зал 3', 'duration' => '60 мин']
    ],
    'Вторник' => [
        ['time' => '07:00', 'class' => 'Пилатес', 'trainer' => 'Мария Иванова', 'room' => 'Зал 2', 'duration' => '45 мин'],
        ['time' => '09:00', 'class' => 'Йога', 'trainer' => 'Анна Петрова', 'room' => 'Зал 1', 'duration' => '60 мин'],
        ['time' => '10:30', 'class' => 'Аэробика', 'trainer' => 'Елена Козлова', 'room' => 'Зал 1', 'duration' => '60 мин'],
        ['time' => '12:00', 'class' => 'Кроссфит', 'trainer' => 'Дмитрий Сидоров', 'room' => 'Зал 3', 'duration' => '60 мин'],
        ['time' => '18:00', 'class' => 'Танцевальные классы (Латина)', 'trainer' => 'Елена Козлова', 'room' => 'Зал 1', 'duration' => '60 мин'],
        ['time' => '19:30', 'class' => 'Силовая тренировка', 'trainer' => 'Алексей Волков', 'room' => 'Зал 3', 'duration' => '60 мин'],
        ['time' => '20:30', 'class' => 'Йога', 'trainer' => 'Анна Петрова', 'room' => 'Зал 2', 'duration' => '60 мин']
    ],
    'Среда' => [
        ['time' => '07:00', 'class' => 'Кроссфит', 'trainer' => 'Дмитрий Сидоров', 'room' => 'Зал 3', 'duration' => '60 мин'],
        ['time' => '09:00', 'class' => 'Стретчинг', 'trainer' => 'Анна Петрова', 'room' => 'Зал 2', 'duration' => '45 мин'],
        ['time' => '10:30', 'class' => 'Пилатес', 'trainer' => 'Мария Иванова', 'room' => 'Зал 2', 'duration' => '45 мин'],
        ['time' => '12:00', 'class' => 'Zumba', 'trainer' => 'Елена Козлова', 'room' => 'Зал 1', 'duration' => '60 мин'],
        ['time' => '18:00', 'class' => 'Функциональный тренинг', 'trainer' => 'Дмитрий Сидоров', 'room' => 'Зал 3', 'duration' => '60 мин'],
        ['time' => '19:30', 'class' => 'Йога', 'trainer' => 'Анна Петрова', 'room' => 'Зал 1', 'duration' => '60 мин'],
        ['time' => '20:30', 'class' => 'Силовая тренировка', 'trainer' => 'Алексей Волков', 'room' => 'Зал 3', 'duration' => '60 мин']
    ],
    'Четверг' => [
        ['time' => '07:00', 'class' => 'Йога', 'trainer' => 'Анна Петрова', 'room' => 'Зал 1', 'duration' => '60 мин'],
        ['time' => '09:00', 'class' => 'Аэробика', 'trainer' => 'Елена Козлова', 'room' => 'Зал 1', 'duration' => '60 мин'],
        ['time' => '10:30', 'class' => 'Кроссфит', 'trainer' => 'Дмитрий Сидоров', 'room' => 'Зал 3', 'duration' => '60 мин'],
        ['time' => '12:00', 'class' => 'Пилатес', 'trainer' => 'Мария Иванова', 'room' => 'Зал 2', 'duration' => '45 мин'],
        ['time' => '18:00', 'class' => 'Танцевальные классы (Хип-хоп)', 'trainer' => 'Елена Козлова', 'room' => 'Зал 1', 'duration' => '60 мин'],
        ['time' => '19:30', 'class' => 'Стретчинг', 'trainer' => 'Анна Петрова', 'room' => 'Зал 2', 'duration' => '45 мин'],
        ['time' => '20:30', 'class' => 'Силовая тренировка', 'trainer' => 'Алексей Волков', 'room' => 'Зал 3', 'duration' => '60 мин']
    ],
    'Пятница' => [
        ['time' => '07:00', 'class' => 'Пилатес', 'trainer' => 'Мария Иванова', 'room' => 'Зал 2', 'duration' => '45 мин'],
        ['time' => '09:00', 'class' => 'Кроссфит', 'trainer' => 'Дмитрий Сидоров', 'room' => 'Зал 3', 'duration' => '60 мин'],
        ['time' => '10:30', 'class' => 'Йога', 'trainer' => 'Анна Петрова', 'room' => 'Зал 1', 'duration' => '60 мин'],
        ['time' => '12:00', 'class' => 'Zumba', 'trainer' => 'Елена Козлова', 'room' => 'Зал 1', 'duration' => '60 мин'],
        ['time' => '18:00', 'class' => 'Функциональный тренинг', 'trainer' => 'Дмитрий Сидоров', 'room' => 'Зал 3', 'duration' => '60 мин'],
        ['time' => '19:30', 'class' => 'Йога', 'trainer' => 'Анна Петрова', 'room' => 'Зал 2', 'duration' => '60 мин'],
        ['time' => '20:30', 'class' => 'Танцевальные классы (Современные танцы)', 'trainer' => 'Елена Козлова', 'room' => 'Зал 1', 'duration' => '60 мин']
    ],
    'Суббота' => [
        ['time' => '09:00', 'class' => 'Йога', 'trainer' => 'Анна Петрова', 'room' => 'Зал 1', 'duration' => '60 мин'],
        ['time' => '10:30', 'class' => 'Кроссфит', 'trainer' => 'Дмитрий Сидоров', 'room' => 'Зал 3', 'duration' => '60 мин'],
        ['time' => '12:00', 'class' => 'Zumba', 'trainer' => 'Елена Козлова', 'room' => 'Зал 1', 'duration' => '60 мин'],
        ['time' => '14:00', 'class' => 'Пилатес', 'trainer' => 'Мария Иванова', 'room' => 'Зал 2', 'duration' => '45 мин'],
        ['time' => '16:00', 'class' => 'Силовая тренировка', 'trainer' => 'Алексей Волков', 'room' => 'Зал 3', 'duration' => '60 мин'],
        ['time' => '17:30', 'class' => 'Стретчинг', 'trainer' => 'Анна Петрова', 'room' => 'Зал 2', 'duration' => '45 мин']
    ],
    'Воскресенье' => [
        ['time' => '09:00', 'class' => 'Йога', 'trainer' => 'Анна Петрова', 'room' => 'Зал 1', 'duration' => '60 мин'],
        ['time' => '10:30', 'class' => 'Пилатес', 'trainer' => 'Мария Иванова', 'room' => 'Зал 2', 'duration' => '45 мин'],
        ['time' => '12:00', 'class' => 'Кроссфит', 'trainer' => 'Дмитрий Сидоров', 'room' => 'Зал 3', 'duration' => '60 мин'],
        ['time' => '14:00', 'class' => 'Танцевальные классы (Бальные танцы)', 'trainer' => 'Елена Козлова', 'room' => 'Зал 1', 'duration' => '60 мин'],
        ['time' => '16:00', 'class' => 'Стретчинг', 'trainer' => 'Анна Петрова', 'room' => 'Зал 2', 'duration' => '45 мин'],
        ['time' => '17:30', 'class' => 'Йога', 'trainer' => 'Анна Петрова', 'room' => 'Зал 1', 'duration' => '60 мин']
    ]
];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Расписание - Фитнес-клуб "Энергия"</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="animations.css">
    <script src="animations.js" defer></script>
    <style>
        .schedule-container {
            max-width: 1400px;
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
        
        .page-header p {
            color: #7f8c8d;
            font-size: 1.1rem;
        }
        
        .schedule-tabs {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .schedule-tab {
            padding: 0.75rem 1.5rem;
            background: white;
            border: 2px solid #ecf0f1;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
            color: #2c3e50;
        }
        
        .schedule-tab:hover {
            border-color: #3498db;
            color: #3498db;
        }
        
        .schedule-tab.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        .schedule-day {
            display: none;
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .schedule-day.active {
            display: block;
        }
        
        .day-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 3px solid #3498db;
        }
        
        .day-header h2 {
            color: #2c3e50;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .schedule-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .schedule-table thead {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
        }
        
        .schedule-table th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
        }
        
        .schedule-table td {
            padding: 1rem;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .schedule-table tbody tr {
            transition: background 0.3s;
        }
        
        .schedule-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .class-name {
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.1rem;
        }
        
        .class-time {
            font-weight: 600;
            color: #3498db;
            font-size: 1.1rem;
        }
        
        .class-trainer {
            color: #7f8c8d;
        }
        
        .class-room {
            color: #95a5a6;
            font-size: 0.9rem;
        }
        
        .class-duration {
            color: #95a5a6;
            font-size: 0.9rem;
        }
        
        .class-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-top: 0.25rem;
        }
        
        .badge-yoga {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .badge-pilates {
            background: #e3f2fd;
            color: #1565c0;
        }
        
        .badge-crossfit {
            background: #fff3e0;
            color: #e65100;
        }
        
        .badge-dance {
            background: #f3e5f5;
            color: #6a1b9a;
        }
        
        .badge-strength {
            background: #ffebee;
            color: #c62828;
        }
        
        .badge-aerobics {
            background: #e0f2f1;
            color: #00695c;
        }
        
        .badge-stretching {
            background: #f1f8e9;
            color: #558b2f;
        }
        
        .badge-functional {
            background: #fff9c4;
            color: #f57f17;
        }
        
        .register-btn {
            padding: 0.5rem 1rem;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.3s;
            font-size: 0.9rem;
        }
        
        .register-btn:hover {
            background: #219a52;
        }
        
        .register-btn:disabled {
            background: #95a5a6;
            cursor: not-allowed;
        }
        
        .registered-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: #d4edda;
            color: #155724;
            border-radius: 5px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .message-box {
            max-width: 1400px;
            margin: 1rem auto;
            padding: 0 2rem;
        }
        
        .message {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @media (max-width: 768px) {
            .schedule-table {
                font-size: 0.9rem;
            }
            
            .schedule-table th,
            .schedule-table td {
                padding: 0.75rem 0.5rem;
            }
            
            .schedule-table thead {
                display: none;
            }
            
            .schedule-table tbody tr {
                display: block;
                margin-bottom: 1rem;
                border: 1px solid #ecf0f1;
                border-radius: 5px;
                padding: 1rem;
            }
            
            .schedule-table tbody td {
                display: block;
                text-align: right;
                padding: 0.5rem 0;
                border: none;
            }
            
            .schedule-table tbody td::before {
                content: attr(data-label);
                float: left;
                font-weight: 600;
                color: #2c3e50;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo">Фитнес-клуб "Энергия"</div>
        <nav class="nav">
            <a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">Главная</a>
            <a href="index.php#services" class="<?php echo ($current_page == 'index.php' && isset($_GET['section']) && $_GET['section'] == 'services') ? 'active' : ''; ?>">Услуги</a>
            <a href="schedule.php" class="<?php echo ($current_page == 'schedule.php') ? 'active' : ''; ?>">Расписание</a>
            <a href="index.php#contact" class="<?php echo ($current_page == 'index.php' && isset($_GET['section']) && $_GET['section'] == 'contact') ? 'active' : ''; ?>">Контакты</a>
        </nav>
        <div class="profile-icon" onclick="toggleProfileMenu()">
            <i class="fas fa-user-circle fa-2x"></i>
            <div class="profile-menu" id="profileMenu">
                <?php if($user): ?>
                    <span>Привет, <?php echo htmlspecialchars($user['name']); ?>!</span>
                    <a href="profile/profile.php">Мой профиль</a>
                    <a href="my_subscriptions.php">Мои абонементы</a>
                    <a href="buy_tickets.php">Купить билеты</a>
                    <a href="logout.php">Выйти</a>
                <?php else: ?>
                    <a href="login/login.php">Войти</a>
                    <a href="register/register.php">Регистрация</a>
                <?php endif; ?>
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

    <?php if ($message): ?>
        <div class="message-box">
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="schedule-container">
        <div class="page-header">
            <h1><i class="fas fa-calendar-alt"></i> Расписание занятий</h1>
            <p>Выберите день недели, чтобы посмотреть расписание тренировок</p>
            <?php if (!$user): ?>
                <p style="color: #e74c3c; margin-top: 0.5rem;"><i class="fas fa-info-circle"></i> Для записи на тренировки необходимо <a href="login/login.php" style="color: #3498db;">войти</a> или <a href="register/register.php" style="color: #3498db;">зарегистрироваться</a></p>
            <?php endif; ?>
        </div>

        <div class="schedule-tabs">
            <?php 
            $days = array_keys($schedule);
            $first_day = true;
            foreach ($days as $day): 
            ?>
                <div class="schedule-tab <?php echo $first_day ? 'active' : ''; ?>" onclick="showDay('<?php echo $day; ?>')">
                    <?php echo $day; ?>
                </div>
            <?php 
                $first_day = false;
            endforeach; 
            ?>
        </div>

        <?php foreach ($schedule as $day => $classes): ?>
            <div class="schedule-day <?php echo ($day == $days[0]) ? 'active' : ''; ?>" id="day-<?php echo $day; ?>">
                <div class="day-header">
                    <h2><?php echo $day; ?></h2>
                </div>
                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-clock"></i> Время</th>
                            <th><i class="fas fa-dumbbell"></i> Занятие</th>
                            <th><i class="fas fa-user"></i> Тренер</th>
                            <th><i class="fas fa-door-open"></i> Зал</th>
                            <th><i class="fas fa-hourglass-half"></i> Длительность</th>
                            <?php if ($user): ?>
                                <th><i class="fas fa-calendar-check"></i> Запись</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($classes as $class): 
                            $badge_class = '';
                            $class_lower = mb_strtolower($class['class']);
                            if (strpos($class_lower, 'йога') !== false) $badge_class = 'badge-yoga';
                            elseif (strpos($class_lower, 'пилатес') !== false) $badge_class = 'badge-pilates';
                            elseif (strpos($class_lower, 'кроссфит') !== false) $badge_class = 'badge-crossfit';
                            elseif (strpos($class_lower, 'танц') !== false || strpos($class_lower, 'zumba') !== false) $badge_class = 'badge-dance';
                            elseif (strpos($class_lower, 'силов') !== false) $badge_class = 'badge-strength';
                            elseif (strpos($class_lower, 'аэроб') !== false) $badge_class = 'badge-aerobics';
                            elseif (strpos($class_lower, 'стретч') !== false) $badge_class = 'badge-stretching';
                            elseif (strpos($class_lower, 'функциональн') !== false) $badge_class = 'badge-functional';
                        ?>
                            <tr>
                                <td data-label="Время:">
                                    <span class="class-time"><?php echo htmlspecialchars($class['time']); ?></span>
                                </td>
                                <td data-label="Занятие:">
                                    <span class="class-name"><?php echo htmlspecialchars($class['class']); ?></span>
                                    <?php if ($badge_class): ?>
                                        <span class="class-badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($class['class']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Тренер:">
                                    <span class="class-trainer"><?php echo htmlspecialchars($class['trainer']); ?></span>
                                </td>
                                <td data-label="Зал:">
                                    <span class="class-room"><i class="fas fa-door-open"></i> <?php echo htmlspecialchars($class['room']); ?></span>
                                </td>
                                <td data-label="Длительность:">
                                    <span class="class-duration"><?php echo htmlspecialchars($class['duration']); ?></span>
                                </td>
                                <?php if ($user): 
                                    $class_date = getDateForDay($day);
                                    $is_registered = false;
                                    foreach ($user_bookings as $booking) {
                                        if ($booking['class_name'] == $class['class'] && 
                                            $booking['class_time'] == $class['time'] . ':00' && 
                                            $booking['class_date'] == $class_date) {
                                            $is_registered = true;
                                            break;
                                        }
                                    }
                                ?>
                                    <td data-label="Запись:">
                                        <?php if ($is_registered): ?>
                                            <span class="registered-badge">
                                                <i class="fas fa-check"></i> Записан
                                            </span>
                                        <?php else: ?>
                                            <form method="POST" action="register_class.php" style="display: inline;">
                                                <input type="hidden" name="register_class" value="1">
                                                <input type="hidden" name="class_name" value="<?php echo htmlspecialchars($class['class']); ?>">
                                                <input type="hidden" name="class_time" value="<?php echo htmlspecialchars($class['time']); ?>">
                                                <input type="hidden" name="class_date" value="<?php echo $class_date; ?>">
                                                <input type="hidden" name="trainer" value="<?php echo htmlspecialchars($class['trainer']); ?>">
                                                <input type="hidden" name="room" value="<?php echo htmlspecialchars($class['room']); ?>">
                                                <input type="hidden" name="duration" value="<?php echo htmlspecialchars($class['duration']); ?>">
                                                <input type="hidden" name="redirect" value="schedule.php">
                                                <button type="submit" class="register-btn">
                                                    <i class="fas fa-calendar-plus"></i> Записаться
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
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
    <script>
        function showDay(day) {
            // Скрыть все дни
            document.querySelectorAll('.schedule-day').forEach(function(dayEl) {
                dayEl.classList.remove('active');
            });
            
            // Убрать активный класс со всех вкладок
            document.querySelectorAll('.schedule-tab').forEach(function(tab) {
                tab.classList.remove('active');
            });
            
            // Показать выбранный день
            document.getElementById('day-' + day).classList.add('active');
            
            // Добавить активный класс к выбранной вкладке
            event.target.classList.add('active');
        }
    </script>
</body>
</html>

