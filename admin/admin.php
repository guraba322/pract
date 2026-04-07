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

// Проверка прав администратора
$user_id = $_SESSION['user']['id'];
$admin_emails = ['admin@ru', 'admin@energia-fitness.ru', 'info@energia-fitness.ru']; // Список админских email

// Проверяем наличие таблицы admins
try {
    $check_table_query = "SHOW TABLES LIKE 'admins'";
    $check_table_stmt = $db->query($check_table_query);
    $has_admins_table = $check_table_stmt->rowCount() > 0;
} catch(PDOException $e) {
    $has_admins_table = false;
}

if ($has_admins_table) {
    // Используем таблицу admins
    $admin_query = "SELECT a.*, u.email as user_email FROM admins a 
                    INNER JOIN users u ON a.user_id = u.id 
                    WHERE a.user_id = ? AND a.is_active = 1";
    $admin_stmt = $db->prepare($admin_query);
    $admin_stmt->execute([$user_id]);
    $admin = $admin_stmt->fetch(PDO::FETCH_ASSOC);
    $is_admin = !empty($admin);
    
    if ($is_admin) {
        // Обновляем время последнего входа
        $update_login_query = "UPDATE admins SET last_login = NOW() WHERE user_id = ?";
        $update_login_stmt = $db->prepare($update_login_query);
        $update_login_stmt->execute([$user_id]);
    }
} else {
    // Fallback: проверяем поле is_admin или email
    try {
        $check_column_query = "SHOW COLUMNS FROM users LIKE 'is_admin'";
        $check_column_stmt = $db->query($check_column_query);
        $has_is_admin_field = $check_column_stmt->rowCount() > 0;
    } catch(PDOException $e) {
        $has_is_admin_field = false;
    }
    
    if ($has_is_admin_field) {
        // Используем поле is_admin
        $user_query = "SELECT email, is_admin FROM users WHERE id = ?";
        $user_stmt = $db->prepare($user_query);
        $user_stmt->execute([$user_id]);
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
        $is_admin = ($user['is_admin'] == 1) || ($user_id == 1);
    } else {
        // Используем проверку по email или ID
        $user_query = "SELECT email FROM users WHERE id = ?";
        $user_stmt = $db->prepare($user_query);
        $user_stmt->execute([$user_id]);
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
        $is_admin = in_array($user['email'], $admin_emails) || $user_id == 1;
    }
}

if (!$is_admin) {
    die("Доступ запрещен. У вас нет прав администратора.");
}

// Список представлений
$views = [
    // Основные представления
    'v_active_users' => 'Активные пользователи',
    'v_active_subscriptions' => 'Активные абонементы',
    'v_upcoming_bookings' => 'Предстоящие записи',
    'v_user_statistics' => 'Статистика пользователей',
    'v_monthly_revenue' => 'Доходы по месяцам',
    'v_popular_classes' => 'Популярные занятия',
    'v_active_tickets' => 'Активные билеты',
    'v_today_bookings' => 'Записи на сегодня',
    'v_expiring_subscriptions' => 'Истекающие абонементы',
    'v_club_statistics' => 'Общая статистика клуба',
    // Дополнительные представления
    'v_trainers_info' => 'Список тренеров',
    'v_clients_by_class_type' => 'Клиенты по типу тренировки',
    'v_trainer_statistics' => 'Статистика тренеров',
    'v_clients_with_active_subscriptions' => 'Клиенты с активными абонементами',
    'v_clients_expiring_by_month' => 'Абонементы по месяцам истечения',
    'v_clients_expiring_7_days' => 'Абонементы истекают через 7 дней',
    'v_clients_without_visits' => 'Клиенты без посещений',
    'v_classes_by_room_and_time' => 'Занятия по залам и времени',
    'v_trainer_detailed_stats' => 'Детальная статистика тренеров',
    'v_room_schedule' => 'Расписание по залам'
];

// Список таблиц
$tables = [
    'users' => 'Пользователи',
    'admins' => 'Администраторы',
    'tickets' => 'Билеты',
    'subscriptions' => 'Абонементы',
    'contacts' => 'Контакты',
    'class_bookings' => 'Записи на тренировки'
];

// Получение данных для отображения
$current_view = $_GET['view'] ?? null;
$current_table = $_GET['table'] ?? null;
$action = $_GET['action'] ?? 'view';
$edit_id = $_GET['id'] ?? null;

$data = [];
$columns = [];
$error = '';
$success = '';

// Загрузка данных из представления
if ($current_view && isset($views[$current_view])) {
    try {
        $query = "SELECT * FROM `{$current_view}` LIMIT 1000";
        $stmt = $db->query($query);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($data)) {
            $columns = array_keys($data[0]);
        } else {
            // Если данных нет, получаем структуру
            $stmt = $db->query("DESCRIBE `{$current_view}`");
            $columns_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $columns = array_column($columns_info, 'Field');
        }
    } catch(PDOException $e) {
        $error = "Ошибка загрузки представления: " . $e->getMessage();
    }
}

// Загрузка данных из таблицы
if ($current_table && isset($tables[$current_table])) {
    try {
        if ($action == 'edit' && $edit_id) {
            // Загрузка одной записи для редактирования
            $query = "SELECT * FROM `{$current_table}` WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$edit_id]);
            $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            // Загрузка всех записей
            $query = "SELECT * FROM `{$current_table}` ORDER BY id DESC LIMIT 500";
            $stmt = $db->query($query);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($data)) {
                $columns = array_keys($data[0]);
            } else {
                // Получаем структуру таблицы
                $stmt = $db->query("DESCRIBE `{$current_table}`");
                $columns_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $columns = array_column($columns_info, 'Field');
            }
        }
    } catch(PDOException $e) {
        $error = "Ошибка загрузки таблицы: " . $e->getMessage();
    }
}

// Обработка CRUD операций
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $table = $_POST['table'] ?? '';
    
    if (isset($_POST['delete'])) {
        // Удаление
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0 && isset($tables[$table])) {
            try {
                $query = "DELETE FROM `{$table}` WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$id]);
                $success = "Запись успешно удалена!";
                header("Location: admin.php?table={$table}");
                exit;
            } catch(PDOException $e) {
                $error = "Ошибка удаления: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['add']) || isset($_POST['update'])) {
        // Добавление или обновление
        if (isset($tables[$table])) {
            try {
                // Получаем структуру таблицы
                $stmt = $db->query("DESCRIBE `{$table}`");
                $table_structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $fields = [];
                $values = [];
                $placeholders = [];
                
                foreach ($table_structure as $column) {
                    $field = $column['Field'];
                    
                    // Пропускаем AUTO_INCREMENT поля при добавлении
                    if (isset($_POST['add']) && $column['Extra'] == 'auto_increment') {
                        continue;
                    }
                    
                    // Пропускаем поля с DEFAULT значениями, если они не переданы
                    if (isset($_POST[$field])) {
                        $fields[] = $field;
                        $values[] = $_POST[$field];
                        $placeholders[] = '?';
                    } elseif ($column['Null'] == 'YES' && !isset($_POST['add'])) {
                        // Для NULL полей при обновлении
                        $fields[] = $field;
                        $values[] = null;
                        $placeholders[] = '?';
                    }
                }
                
                if (isset($_POST['add'])) {
                    // Добавление
                    $query = "INSERT INTO `{$table}` (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
                    $stmt = $db->prepare($query);
                    $stmt->execute($values);
                    $success = "Запись успешно добавлена!";
                } else {
                    // Обновление
                    $id = intval($_POST['id'] ?? 0);
                    $set_clause = [];
                    foreach ($fields as $field) {
                        $set_clause[] = "`{$field}` = ?";
                    }
                    $query = "UPDATE `{$table}` SET " . implode(', ', $set_clause) . " WHERE id = ?";
                    $values[] = $id;
                    $stmt = $db->prepare($query);
                    $stmt->execute($values);
                    $success = "Запись успешно обновлена!";
                }
                
                header("Location: admin.php?table={$table}");
                exit;
            } catch(PDOException $e) {
                $error = "Ошибка сохранения: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Административная панель - Фитнес-клуб "Энергия"</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <h1><i class="fas fa-shield-alt"></i> Административная панель</h1>
            <div class="admin-user">
                <span>Администратор: <?php echo htmlspecialchars($_SESSION['user']['name']); ?></span>
                <a href="../index.php" class="btn-secondary"><i class="fas fa-home"></i> На сайт</a>
                <a href="../logout.php" class="btn-secondary"><i class="fas fa-sign-out-alt"></i> Выйти</a>
            </div>
        </header>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="admin-content">
            <aside class="admin-sidebar">
                <nav class="admin-nav">
                    <div class="nav-section">
                        <h3><i class="fas fa-table"></i> Основные представления</h3>
                        <ul>
                            <?php 
                            $main_views = array_slice($views, 0, 10, true);
                            foreach ($main_views as $view_name => $view_title): ?>
                                <li>
                                    <a href="?view=<?php echo urlencode($view_name); ?>" 
                                       class="<?php echo $current_view == $view_name ? 'active' : ''; ?>">
                                        <?php echo htmlspecialchars($view_title); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <div class="nav-section">
                        <h3><i class="fas fa-chart-line"></i> Дополнительные представления</h3>
                        <ul>
                            <?php 
                            $additional_views = array_slice($views, 10, null, true);
                            foreach ($additional_views as $view_name => $view_title): ?>
                                <li>
                                    <a href="?view=<?php echo urlencode($view_name); ?>" 
                                       class="<?php echo $current_view == $view_name ? 'active' : ''; ?>">
                                        <?php echo htmlspecialchars($view_title); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <div class="nav-section">
                        <h3><i class="fas fa-database"></i> Таблицы</h3>
                        <ul>
                            <?php foreach ($tables as $table_name => $table_title): ?>
                                <li>
                                    <a href="?table=<?php echo urlencode($table_name); ?>" 
                                       class="<?php echo $current_table == $table_name ? 'active' : ''; ?>">
                                        <?php echo htmlspecialchars($table_title); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </nav>
            </aside>

            <main class="admin-main">
                <?php if (!$current_view && !$current_table): ?>
                    <div class="welcome-screen">
                        <h2>Добро пожаловать в административную панель!</h2>
                        <p>Выберите представление или таблицу из меню слева для просмотра и управления данными.</p>
                        
                        <div class="stats-grid">
                            <div class="stat-card">
                                <i class="fas fa-users"></i>
                                <h3><?php 
                                    try {
                                        $stmt = $db->query("SELECT COUNT(*) as cnt FROM users");
                                        echo $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
                                    } catch(PDOException $e) { echo '0'; }
                                ?></h3>
                                <p>Пользователей</p>
                            </div>
                            <div class="stat-card">
                                <i class="fas fa-id-card"></i>
                                <h3><?php 
                                    try {
                                        $stmt = $db->query("SELECT COUNT(*) as cnt FROM subscriptions WHERE status = 'active'");
                                        echo $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
                                    } catch(PDOException $e) { echo '0'; }
                                ?></h3>
                                <p>Активных абонементов</p>
                            </div>
                            <div class="stat-card">
                                <i class="fas fa-calendar-check"></i>
                                <h3><?php 
                                    try {
                                        $stmt = $db->query("SELECT COUNT(*) as cnt FROM class_bookings WHERE status = 'booked'");
                                        echo $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
                                    } catch(PDOException $e) { echo '0'; }
                                ?></h3>
                                <p>Активных записей</p>
                            </div>
                            <div class="stat-card">
                                <i class="fas fa-ticket-alt"></i>
                                <h3><?php 
                                    try {
                                        $stmt = $db->query("SELECT COUNT(*) as cnt FROM tickets WHERE status = 'active'");
                                        echo $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
                                    } catch(PDOException $e) { echo '0'; }
                                ?></h3>
                                <p>Активных билетов</p>
                            </div>
                        </div>
                    </div>
                <?php elseif ($current_view): ?>
                    <!-- Просмотр представления -->
                    <div class="data-view">
                        <div class="view-header">
                            <h2><?php echo htmlspecialchars($views[$current_view]); ?></h2>
                            <span class="badge">Представление (только просмотр)</span>
                        </div>
                        
                        <?php if (!empty($data)): ?>
                            <div class="table-container">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <?php foreach ($columns as $col): ?>
                                                <th><?php echo htmlspecialchars($col); ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($data as $row): ?>
                                            <tr>
                                                <?php foreach ($columns as $col): ?>
                                                    <td><?php echo htmlspecialchars($row[$col] ?? ''); ?></td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <p class="info-text">Показано записей: <?php echo count($data); ?></p>
                        <?php else: ?>
                            <p class="info-text">Данные отсутствуют</p>
                        <?php endif; ?>
                    </div>
                <?php elseif ($current_table): ?>
                    <!-- Работа с таблицей -->
                    <?php if ($action == 'add' || ($action == 'edit' && $edit_id)): ?>
                        <!-- Форма добавления/редактирования -->
                        <div class="form-view">
                            <div class="form-header">
                                <h2><?php echo $action == 'add' ? 'Добавить запись' : 'Редактировать запись'; ?></h2>
                                <a href="?table=<?php echo urlencode($current_table); ?>" class="btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Назад
                                </a>
                            </div>
                            
                            <?php
                            // Получаем структуру таблицы
                            try {
                                $stmt = $db->query("DESCRIBE `{$current_table}`");
                                $table_structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            } catch(PDOException $e) {
                                $table_structure = [];
                            }
                            ?>
                            
                            <form method="POST" class="admin-form">
                                <input type="hidden" name="table" value="<?php echo htmlspecialchars($current_table); ?>">
                                <?php if ($action == 'edit'): ?>
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($edit_id); ?>">
                                <?php endif; ?>
                                
                                <?php foreach ($table_structure as $column): 
                                    $field = $column['Field'];
                                    $type = $column['Type'];
                                    $null = $column['Null'];
                                    $default = $column['Default'];
                                    $extra = $column['Extra'];
                                    
                                    // Пропускаем AUTO_INCREMENT поля
                                    if ($extra == 'auto_increment') continue;
                                    
                                    $value = '';
                                    if ($action == 'edit' && isset($edit_data[$field])) {
                                        $value = $edit_data[$field];
                                    } elseif (isset($_POST[$field])) {
                                        $value = $_POST[$field];
                                    } elseif ($default !== null) {
                                        $value = $default;
                                    }
                                    
                                    $required = ($null == 'NO' && $default === null && $extra != 'auto_increment') ? 'required' : '';
                                ?>
                                    <div class="form-group">
                                        <label for="<?php echo htmlspecialchars($field); ?>">
                                            <?php echo htmlspecialchars($field); ?>
                                            <?php if ($required): ?>
                                                <span class="required">*</span>
                                            <?php endif; ?>
                                        </label>
                                        
                                        <?php if (stripos($type, 'text') !== false): ?>
                                            <textarea name="<?php echo htmlspecialchars($field); ?>" 
                                                      id="<?php echo htmlspecialchars($field); ?>" 
                                                      class="form-control" 
                                                      <?php echo $required; ?>><?php echo htmlspecialchars($value); ?></textarea>
                                        <?php elseif (stripos($type, 'date') !== false): ?>
                                            <input type="date" 
                                                   name="<?php echo htmlspecialchars($field); ?>" 
                                                   id="<?php echo htmlspecialchars($field); ?>" 
                                                   class="form-control" 
                                                   value="<?php echo htmlspecialchars($value); ?>" 
                                                   <?php echo $required; ?>>
                                        <?php elseif (stripos($type, 'time') !== false): ?>
                                            <input type="time" 
                                                   name="<?php echo htmlspecialchars($field); ?>" 
                                                   id="<?php echo htmlspecialchars($field); ?>" 
                                                   class="form-control" 
                                                   value="<?php echo htmlspecialchars($value); ?>" 
                                                   <?php echo $required; ?>>
                                        <?php elseif (stripos($type, 'int') !== false || stripos($type, 'decimal') !== false || stripos($type, 'float') !== false): ?>
                                            <input type="number" 
                                                   step="<?php echo stripos($type, 'decimal') !== false || stripos($type, 'float') !== false ? '0.01' : '1'; ?>"
                                                   name="<?php echo htmlspecialchars($field); ?>" 
                                                   id="<?php echo htmlspecialchars($field); ?>" 
                                                   class="form-control" 
                                                   value="<?php echo htmlspecialchars($value); ?>" 
                                                   <?php echo $required; ?>>
                                        <?php else: ?>
                                            <input type="text" 
                                                   name="<?php echo htmlspecialchars($field); ?>" 
                                                   id="<?php echo htmlspecialchars($field); ?>" 
                                                   class="form-control" 
                                                   value="<?php echo htmlspecialchars($value); ?>" 
                                                   <?php echo $required; ?>>
                                        <?php endif; ?>
                                        
                                        <small class="form-hint">
                                            Тип: <?php echo htmlspecialchars($type); ?>
                                            <?php if ($null == 'YES'): ?>
                                                (может быть NULL)
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                                
                                <div class="form-actions">
                                    <button type="submit" name="<?php echo $action == 'add' ? 'add' : 'update'; ?>" class="btn-primary">
                                        <i class="fas fa-save"></i> Сохранить
                                    </button>
                                    <a href="?table=<?php echo urlencode($current_table); ?>" class="btn-secondary">
                                        Отмена
                                    </a>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <!-- Просмотр таблицы -->
                        <div class="data-view">
                            <div class="view-header">
                                <h2><?php echo htmlspecialchars($tables[$current_table]); ?></h2>
                                <a href="?table=<?php echo urlencode($current_table); ?>&action=add" class="btn-primary">
                                    <i class="fas fa-plus"></i> Добавить запись
                                </a>
                            </div>
                            
                            <?php if (!empty($data)): ?>
                                <div class="table-container">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <?php foreach ($columns as $col): ?>
                                                    <th><?php echo htmlspecialchars($col); ?></th>
                                                <?php endforeach; ?>
                                                <th>Действия</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($data as $row): ?>
                                                <tr>
                                                    <?php foreach ($columns as $col): ?>
                                                        <td><?php echo htmlspecialchars($row[$col] ?? ''); ?></td>
                                                    <?php endforeach; ?>
                                                    <td class="actions">
                                                        <a href="?table=<?php echo urlencode($current_table); ?>&action=edit&id=<?php echo $row['id']; ?>" 
                                                           class="btn-edit" title="Редактировать">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <form method="POST" style="display: inline;" 
                                                              onsubmit="return confirm('Вы уверены, что хотите удалить эту запись?');">
                                                            <input type="hidden" name="table" value="<?php echo htmlspecialchars($current_table); ?>">
                                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                            <button type="submit" name="delete" class="btn-delete" title="Удалить">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <p class="info-text">Показано записей: <?php echo count($data); ?></p>
                            <?php else: ?>
                                <p class="info-text">Данные отсутствуют</p>
                                <a href="?table=<?php echo urlencode($current_table); ?>&action=add" class="btn-primary">
                                    <i class="fas fa-plus"></i> Добавить первую запись
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </main>
        </div>
    </div>
</body>
</html>

