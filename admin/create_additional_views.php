<?php
/**
 * Скрипт для создания дополнительных представлений (views) в базе данных
 * Фитнес-клуб "Энергия"
 */

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    die("Ошибка подключения к базе данных");
}

// Чтение SQL файла с представлениями
$sql_file = __DIR__ . '/additional_views.sql';
if (!file_exists($sql_file)) {
    die("Файл additional_views.sql не найден!");
}

$sql = file_get_contents($sql_file);

// Разделение SQL на отдельные запросы
$sql = preg_replace('/--.*$/m', '', $sql); // Удаляем однострочные комментарии
$sql = preg_replace('/\/\*.*?\*\//s', '', $sql); // Удаляем многострочные комментарии

// Разделяем на отдельные запросы
$queries = array_filter(
    array_map('trim', explode(';', $sql)),
    function($query) {
        return !empty($query) && stripos($query, 'CREATE') !== false;
    }
);

$success_count = 0;
$error_count = 0;
$errors = [];

echo "<!DOCTYPE html>
<html lang='ru'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Создание дополнительных представлений</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; margin-bottom: 20px; }
        .success { color: #27ae60; padding: 10px; background: #d4edda; border-radius: 5px; margin: 10px 0; }
        .error { color: #e74c3c; padding: 10px; background: #f8d7da; border-radius: 5px; margin: 10px 0; }
        .info { color: #3498db; padding: 10px; background: #d1ecf1; border-radius: 5px; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .summary { margin-top: 30px; padding: 20px; background: #ecf0f1; border-radius: 5px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Создание дополнительных представлений</h1>";

try {
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='info'>Найдено запросов для выполнения: " . count($queries) . "</div>";
    
    foreach ($queries as $index => $query) {
        $query = trim($query);
        if (empty($query)) continue;
        
        // Извлекаем имя представления из запроса
        if (preg_match('/CREATE\s+(?:OR\s+REPLACE\s+)?VIEW\s+(\w+)/i', $query, $matches)) {
            $view_name = $matches[1];
            
            try {
                $db->exec($query);
                $success_count++;
                echo "<div class='success'>✓ Представление '$view_name' успешно создано</div>";
            } catch(PDOException $e) {
                $error_count++;
                $error_msg = "Ошибка при создании представления '$view_name': " . $e->getMessage();
                $errors[] = $error_msg;
                echo "<div class='error'>✗ $error_msg</div>";
            }
        }
    }
    
    echo "<div class='summary'>
            <h2>Итоги выполнения:</h2>
            <p><strong>Успешно создано:</strong> $success_count представлений</p>
            <p><strong>Ошибок:</strong> $error_count</p>";
    
    if ($error_count > 0) {
        echo "<h3>Список ошибок:</h3><pre>";
        foreach ($errors as $error) {
            echo htmlspecialchars($error) . "\n";
        }
        echo "</pre>";
    }
    
    if ($success_count > 0) {
        echo "<h3>Созданные представления:</h3>
              <ul>
                <li>v_trainers_info - Список тренеров</li>
                <li>v_clients_by_class_type - Клиенты по типу тренировки</li>
                <li>v_trainer_statistics - Статистика тренеров</li>
                <li>v_clients_with_active_subscriptions - Клиенты с активными абонементами</li>
                <li>v_clients_expiring_by_month - Абонементы по месяцам истечения</li>
                <li>v_clients_expiring_7_days - Абонементы истекают через 7 дней</li>
                <li>v_clients_without_visits - Клиенты без посещений</li>
                <li>v_classes_by_room_and_time - Занятия по залам и времени</li>
                <li>v_trainer_detailed_stats - Детальная статистика тренеров</li>
                <li>v_room_schedule - Расписание по залам</li>
              </ul>
              <p><strong>Теперь эти представления доступны в админ-панели!</strong></p>
              <p><a href='admin.php' style='display: inline-block; padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; margin-top: 10px;'>Открыть админ-панель</a></p>";
    }
    
    echo "</div>";
    
} catch(PDOException $e) {
    echo "<div class='error'>Критическая ошибка: " . $e->getMessage() . "</div>";
}

echo "</div></body></html>";
?>

