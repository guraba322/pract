<?php
session_start();
require_once 'config/database.php';

// Функция для отправки HTML-письма
function sendEmail($to, $subject, $html_body) {
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Фитнес-клуб 'Энергия' <info@energia-fitness.ru>\r\n";
    $headers .= "Reply-To: info@energia-fitness.ru\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    return @mail($to, $subject, $html_body, $headers);
}

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($db === null) {
        $_SESSION['error'] = "Ошибка подключения к базе данных. Попробуйте позже.";
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $message = trim($_POST['message'] ?? '');

        // Валидация обязательных полей
        if (empty($name) || empty($email)) {
            $_SESSION['error'] = "Пожалуйста, заполните все обязательные поля (Имя и Email).";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Пожалуйста, введите корректный email адрес.";
        } else {
            try {
                $query = "INSERT INTO contacts (name, email, phone, message, created_at) VALUES (?, ?, ?, ?, NOW())";
                $stmt = $db->prepare($query);
                
                if ($stmt->execute([$name, $email, $phone, $message])) {
                    // Загрузка CSS стилей из файла
                    $css_file = __DIR__ . '/email_styles.css';
                    $email_css = file_exists($css_file) ? file_get_contents($css_file) : '';
                    
                    // Подготовка данных для письма
                    $escaped_name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
                    $escaped_email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
                    $escaped_phone = !empty($phone) ? htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') : 'Не указан';
                    $escaped_message = !empty($message) ? nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) : 'Не указано';
                    
                    // HTML-шаблон письма для клиента
                    $client_subject = "Запись на пробную тренировку - Фитнес-клуб 'Энергия'";
                    $client_html = "
                    <!DOCTYPE html>
                    <html lang='ru'>
                    <head>
                        <meta charset='UTF-8'>
                        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                        <style>
                            {$email_css}
                            .header { background: linear-gradient(135deg, #2c3e50, #34495e); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                            .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
                            .info-box { background: white; padding: 20px; margin: 20px 0; border-radius: 5px; border-left: 4px solid #3498db; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h1>Фитнес-клуб 'Энергия'</h1>
                                <p>Запись на пробную тренировку</p>
                            </div>
                            <div class='content'>
                                <p>Здравствуйте, <strong>{$escaped_name}</strong>!</p>
                                <p>Благодарим вас за запись на пробную тренировку в фитнес-клубе 'Энергия'!</p>
                                
                                <div class='info-box'>
                                    <h3 style='margin-top: 0; color: #2c3e50;'>Ваши данные:</h3>
                                    <div class='info-row'>
                                        <span class='info-label'>Имя:</span> {$escaped_name}
                                    </div>
                                    <div class='info-row'>
                                        <span class='info-label'>Email:</span> {$escaped_email}
                                    </div>
                                    <div class='info-row'>
                                        <span class='info-label'>Телефон:</span> {$escaped_phone}
                                    </div>
                                    <div class='info-row'>
                                        <span class='info-label'>Сообщение:</span><br>
                                        {$escaped_message}
                                    </div>
                                </div>
                                
                                <p>Мы свяжемся с вами в ближайшее время для подтверждения записи и уточнения деталей.</p>
                                
                                <div class='footer'>
                                    <p><strong>С уважением,<br>Команда фитнес-клуба 'Энергия'</strong></p>
                                    <div class='contact-info'>
                                        <p>📞 Телефон: <a href='tel:+79991234567' style='color: #3498db;'>+7 (999) 123-45-67</a></p>
                                        <p>✉️ Email: <a href='mailto:info@energia-fitness.ru' style='color: #3498db;'>info@energia-fitness.ru</a></p>
                                        <p>📍 Адрес: г. Москва, ул. Фитнесная, д. 10</p>
                                        <p>🕐 Режим работы: Пн-Вс: 06:00 - 23:00</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </body>
                    </html>";
                    
                    // HTML-шаблон письма для администратора
                    $admin_email = "info@energia-fitness.ru"; // Email администратора
                    $admin_subject = "Новая заявка на пробную тренировку от {$escaped_name}";
                    $admin_html = "
                    <!DOCTYPE html>
                    <html lang='ru'>
                    <head>
                        <meta charset='UTF-8'>
                        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                        <style>
                            {$email_css}
                            .header { background: linear-gradient(135deg, #e74c3c, #c0392b); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                            .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
                            .info-box { background: white; padding: 20px; margin: 20px 0; border-radius: 5px; border-left: 4px solid #e74c3c; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h1>Новая заявка на пробную тренировку</h1>
                            </div>
                            <div class='content'>
                                <div class='info-box'>
                                    <h3 style='margin-top: 0; color: #2c3e50;'>Данные клиента:</h3>
                                    <div class='info-row'>
                                        <span class='info-label'>Имя:</span> {$escaped_name}
                                    </div>
                                    <div class='info-row'>
                                        <span class='info-label'>Email:</span> <a href='mailto:{$escaped_email}'>{$escaped_email}</a>
                                    </div>
                                    <div class='info-row'>
                                        <span class='info-label'>Телефон:</span> <a href='tel:{$escaped_phone}'>{$escaped_phone}</a>
                                    </div>
                                    <div class='info-row'>
                                        <span class='info-label'>Сообщение:</span><br>
                                        {$escaped_message}
                                    </div>
                                </div>
                                <div class='timestamp'>
                                    <p>Дата и время заявки: " . date('d.m.Y H:i:s') . "</p>
                                </div>
                            </div>
                        </div>
                    </body>
                    </html>";
                    
                    // Отправка письма клиенту
                    $client_mail_sent = sendEmail($email, $client_subject, $client_html);
                    
                    // Отправка письма администратору
                    $admin_mail_sent = sendEmail($admin_email, $admin_subject, $admin_html);
                    
                    if ($client_mail_sent) {
                        $_SESSION['success'] = "Заявка успешно отправлена! Письмо с подтверждением отправлено на ваш email.";
                    } else {
                        $_SESSION['success'] = "Заявка успешно отправлена! Мы свяжемся с вами в ближайшее время.";
                    }
                } else {
                    $_SESSION['error'] = "Ошибка при отправке заявки. Попробуйте еще раз.";
                }
            } catch(PDOException $e) {
                // Более информативное сообщение об ошибке
                $error_message = $e->getMessage();
                if (strpos($error_message, "Table") !== false && strpos($error_message, "doesn't exist") !== false) {
                    $_SESSION['error'] = "Ошибка: таблица contacts не найдена в базе данных. Обратитесь к администратору.";
                } else {
                    $_SESSION['error'] = "Ошибка при отправке заявки: " . $error_message;
                }
            }
        }
    }
} else {
    // Если запрос не POST, перенаправляем на главную
    $_SESSION['error'] = "Неверный метод запроса.";
}

header('Location: index.php');
exit;
?>