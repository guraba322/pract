<?php
session_start();
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$user = isset($_SESSION['user']) ? $_SESSION['user'] : null;

// Определение текущей страницы
$current_page = basename($_SERVER['PHP_SELF']);
$page_title = "Главная";
$breadcrumbs = [
    ['title' => 'Главная', 'url' => 'index.php', 'active' => true]
];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Фитнес-клуб "Энергия"</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="animations.css">
    <script src="animations.js" defer></script>
</head>
<body>
    <header class="header">
        <div class="logo">Фитнес-клуб "Энергия"</div>
        <nav class="nav">
            <a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">Главная</a>
            <a href="#services" class="<?php echo (isset($_GET['section']) && $_GET['section'] == 'services') ? 'active' : ''; ?>">Услуги</a>
            <a href="schedule.php" class="<?php echo ($current_page == 'schedule.php') ? 'active' : ''; ?>">Расписание</a>
            <a href="#contact" class="<?php echo (isset($_GET['section']) && $_GET['section'] == 'contact') ? 'active' : ''; ?>">Контакты</a>
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
            <?php if (count($breadcrumbs) > 1): ?>
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
            <?php endif; ?>
        </div>
    </nav>

    <section class="hero">
        <div class="hero-content">
            <h1>Начни свой путь к идеальной форме</h1>
            <p>Профессиональное оборудование и лучшие тренеры</p>
            <?php if(!$user): ?>
                <a href="register/register.php" class="cta-button">Начать тренироваться</a>
            <?php endif; ?>
        </div>
    </section>

    <section class="services" id="services">
        <h2 class="animate-on-scroll">Наши услуги</h2>
        <div class="services-grid">
            <div class="service-card animate-on-scroll">
                <i class="fas fa-dumbbell"></i>
                <h3>Персональные тренировки</h3>
                <p>Индивидуальный подход к каждому клиенту</p>
            </div>
            <div class="service-card animate-on-scroll">
                <i class="fas fa-users"></i>
                <h3>Групповые занятия</h3>
                <p>Йога, пилатес, функциональный тренинг</p>
            </div>
            <div class="service-card animate-on-scroll">
                <i class="fas fa-heartbeat"></i>
                <h3>Кардио-зона</h3>
                <p>Современное кардио-оборудование</p>
            </div>
            <div class="service-card animate-on-scroll">
                <i class="fas fa-spa"></i>
                <h3>Массаж</h3>
                <p>Расслабляющий и лечебный массаж</p>
            </div>
            <div class="service-card animate-on-scroll">
                <i class="fas fa-hot-tub"></i>
                <h3>Сауна и Хаммам</h3>
                <p>Релаксация и восстановление после тренировок</p>
            </div>
            <div class="service-card animate-on-scroll">
                <i class="fas fa-sun"></i>
                <h3>Солярий</h3>
                <p>Современные вертикальные солярии</p>
            </div>
            <div class="service-card animate-on-scroll">
                <i class="fas fa-child"></i>
                <h3>Детский фитнес</h3>
                <p>Специальные программы для детей</p>
            </div>
            <div class="service-card animate-on-scroll">
                <i class="fas fa-fire"></i>
                <h3>Кроссфит</h3>
                <p>Высокоинтенсивные функциональные тренировки</p>
            </div>
            <div class="service-card animate-on-scroll">
                <i class="fas fa-music"></i>
                <h3>Танцевальные классы</h3>
                <p>Zumba, латина, современные танцы</p>
            </div>
        </div>
    </section>

    <section class="contact-form-section" id="contact">
        <div class="container">
            <h2 class="animate-on-scroll">Записаться на пробную тренировку</h2>
            <form action="submit_contact.php" method="POST" class="contact-form animate-on-scroll">
                <div class="form-group">
                    <input type="text" name="name" placeholder="Ваше имя" required 
                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                </div>
                <div class="form-group">
                    <input type="email" name="email" placeholder="Ваш email" required
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                <div class="form-group">
                    <input type="tel" name="phone" placeholder="Ваш телефон"
                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                </div>
                <div class="form-group">
                    <textarea name="message" placeholder="Сообщение (необязательно)"><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                </div>
                <button type="submit" class="submit-btn">Отправить заявку</button>
            </form>
            
            <?php if(isset($_SESSION['success'])): ?>
                <div class="success-message">
                    <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['error'])): ?>
                <div class="error-message">
                    <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <footer class="footer">
        <div class="footer-container">
            <div class="footer-section">
                <h3>Фитнес-клуб "Энергия"</h3>
                <p>Ваш путь к здоровому образу жизни начинается здесь. Профессиональное оборудование, опытные тренеры и индивидуальный подход.</p>
            </div>
            
            <div class="footer-section">
                <h3>Контакты</h3>
                <ul class="footer-contacts">
                    <li>
                        <i class="fas fa-map-marker-alt"></i>
                        <span>г. Москва, ул. Фитнесная, д. 10</span>
                    </li>
                    <li>
                        <i class="fas fa-phone"></i>
                        <a href="tel:+79991234567">+7 (999) 123-45-67</a>
                    </li>
                    <li>
                        <i class="fas fa-envelope"></i>
                        <a href="mailto:info@energia-fitness.ru">info@energia-fitness.ru</a>
                    </li>
                    <li>
                        <i class="fas fa-clock"></i>
                        <span>Пн-Вс: 06:00 - 23:00</span>
                    </li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Быстрые ссылки</h3>
                <ul class="footer-links">
                    <li><a href="index.php">Главная</a></li>
                    <li><a href="#services">Услуги</a></li>
                    <li><a href="#contact">Контакты</a></li>
                    <?php if($user): ?>
                        <li><a href="profile/profile.php">Мой профиль</a></li>
                    <?php else: ?>
                        <li><a href="login/login.php">Войти</a></li>
                        <li><a href="register/register.php">Регистрация</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Мы в соцсетях</h3>
                <div class="social-links">
                    <a href="#" class="social-link" aria-label="ВКонтакте">
                        <i class="fab fa-vk"></i>
                    </a>
                    <a href="#" class="social-link" aria-label="Telegram">
                        <i class="fab fa-telegram"></i>
                    </a>
                    <a href="#" class="social-link" aria-label="Instagram">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" class="social-link" aria-label="WhatsApp">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>© 2025 Фитнес-клуб "Энергия". Все права защищены.</p>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>