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

// Создание таблицы для записей, если её нет
try {
    $create_table_query = "CREATE TABLE IF NOT EXISTS class_bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        class_name VARCHAR(255) NOT NULL,
        class_time TIME NOT NULL,
        class_date DATE NOT NULL,
        trainer VARCHAR(255) NOT NULL,
        room VARCHAR(100) NOT NULL,
        duration VARCHAR(50) NOT NULL,
        status VARCHAR(50) DEFAULT 'booked',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $db->exec($create_table_query);
} catch(PDOException $e) {
    // Таблица уже существует или ошибка создания
}

// Обработка записи на тренировку
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_class'])) {
    $class_name = trim($_POST['class_name'] ?? '');
    $class_time = trim($_POST['class_time'] ?? '');
    $class_date = trim($_POST['class_date'] ?? '');
    $trainer = trim($_POST['trainer'] ?? '');
    $room = trim($_POST['room'] ?? '');
    $duration = trim($_POST['duration'] ?? '');
    
    // Валидация
    if (empty($class_name) || empty($class_time) || empty($class_date)) {
        $message = "Ошибка: не все данные заполнены.";
        $message_type = 'error';
    } else {
        try {
            // Проверка, не записан ли уже пользователь на это занятие
            $check_query = "SELECT id FROM class_bookings 
                           WHERE user_id = ? AND class_name = ? AND class_time = ? AND class_date = ? AND status = 'booked'";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute([$user_id, $class_name, $class_time, $class_date]);
            
            if ($check_stmt->rowCount() > 0) {
                $message = "Вы уже записаны на это занятие!";
                $message_type = 'error';
            } else {
                // Запись на тренировку
                $insert_query = "INSERT INTO class_bookings (user_id, class_name, class_time, class_date, trainer, room, duration, status) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, 'booked')";
                $insert_stmt = $db->prepare($insert_query);
                
                if ($insert_stmt->execute([$user_id, $class_name, $class_time, $class_date, $trainer, $room, $duration])) {
                    $message = "Вы успешно записаны на тренировку!";
                    $message_type = 'success';
                } else {
                    $message = "Ошибка при записи на тренировку.";
                    $message_type = 'error';
                }
            }
        } catch(PDOException $e) {
            $message = "Ошибка: " . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Обработка отмены записи
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    $booking_id = intval($_POST['booking_id'] ?? 0);
    
    if ($booking_id > 0) {
        try {
            // Проверка, что запись принадлежит пользователю
            $check_query = "SELECT id FROM class_bookings WHERE id = ? AND user_id = ?";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute([$booking_id, $user_id]);
            
            if ($check_stmt->rowCount() > 0) {
                // Отмена записи
                $cancel_query = "UPDATE class_bookings SET status = 'cancelled' WHERE id = ?";
                $cancel_stmt = $db->prepare($cancel_query);
                
                if ($cancel_stmt->execute([$booking_id])) {
                    $message = "Запись на тренировку отменена.";
                    $message_type = 'success';
                } else {
                    $message = "Ошибка при отмене записи.";
                    $message_type = 'error';
                }
            } else {
                $message = "Запись не найдена.";
                $message_type = 'error';
            }
        } catch(PDOException $e) {
            $message = "Ошибка: " . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Перенаправление обратно на страницу расписания или профиля
$redirect = $_POST['redirect'] ?? ($_GET['redirect'] ?? 'schedule.php');
$_SESSION['booking_message'] = $message;
$_SESSION['booking_message_type'] = $message_type;

if ($redirect == 'profile.php') {
    header('Location: profile/profile.php');
} else {
    header('Location: ' . $redirect);
}
exit;
?>

