<?php
/**
 * NOTIFIKASI - SIRAGA
 * Lokasi: modules/parent/notifikasi.php
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ======================== CEK SESSION ========================
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'parent') {
    header('Location: /siraga/auth/login.php');
    exit;
}

$user = $_SESSION['user'];

// ======================== KONFIGURASI ========================
define('BASE_URL', '/siraga');
define('ROOT_PATH', dirname(__DIR__, 2));
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/includes/functions.php';

// ======================== INISIALISASI DATABASE ========================
try {
    $db = Database::getInstance();
} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}

// ======================== AMBIL DATA NOTIFIKASI ========================
// PERBAIKAN: Query yang benar sesuai struktur database
$notifications = $db->fetchAll("
    SELECT 
        n.*,
        c.full_name as child_name,
        DATE_FORMAT(n.created_at, '%d/%m/%Y %H:%i') as created_formatted,
        CASE 
            WHEN n.type = 'info' THEN 'ℹ️'
            WHEN n.type = 'warning' THEN '⚠️'
            WHEN n.type = 'danger' THEN '🚨'
            WHEN n.type = 'success' THEN '✅'
            ELSE '📢'
        END as type_icon
    FROM notifications n
    LEFT JOIN children c ON n.related_id = c.id AND n.related_module = 'children'
    WHERE n.user_id = ?
    ORDER BY n.is_read ASC, n.created_at DESC
    LIMIT 50
", [$user['id']]);

// Hitung notifikasi belum dibaca - PERBAIKAN
$unread_count = $db->fetchOne("
    SELECT COUNT(*) as count FROM notifications 
    WHERE user_id = ? AND is_read = 0
", [$user['id']]);
$unread_count = $unread_count['count'] ?? 0;

// ======================== HANDLE ACTIONS ========================
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'mark_as_read') {
            $notification_id = intval($_POST['notification_id'] ?? 0);
            if ($notification_id > 0) {
                $db->update('notifications', 
                    ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')], 
                    'id = ? AND user_id = ?', 
                    [$notification_id, $user['id']]
                );
                $success_msg = "✅ Notifikasi ditandai sudah dibaca";
            }
        }
        
        elseif ($action === 'mark_all_read') {
            $db->update('notifications', 
                ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')], 
                'user_id = ? AND is_read = 0', 
                [$user['id']]
            );
            $success_msg = "✅ Semua notifikasi ditandai sudah dibaca";
        }
        
        elseif ($action === 'delete_notification') {
            $notification_id = intval($_POST['notification_id'] ?? 0);
            if ($notification_id > 0) {
                $db->delete('notifications', 'id = ? AND user_id = ?', [$notification_id, $user['id']]);
                $success_msg = "✅ Notifikasi dihapus";
            }
        }
        
        elseif ($action === 'clear_all') {
            $db->delete('notifications', 'user_id = ?', [$user['id']]);
            $success_msg = "✅ Semua notifikasi dihapus";
        }
        
        // Refresh data notifications
        $notifications = $db->fetchAll("
            SELECT 
                n.*,
                c.full_name as child_name,
                DATE_FORMAT(n.created_at, '%d/%m/%Y %H:%i') as created_formatted,
                CASE 
                    WHEN n.type = 'info' THEN 'ℹ️'
                    WHEN n.type = 'warning' THEN '⚠️'
                    WHEN n.type = 'danger' THEN '🚨'
                    WHEN n.type = 'success' THEN '✅'
                    ELSE '📢'
                END as type_icon
            FROM notifications n
            LEFT JOIN children c ON n.related_id = c.id AND n.related_module = 'children'
            WHERE n.user_id = ?
            ORDER BY n.is_read ASC, n.created_at DESC
            LIMIT 50
        ", [$user['id']]);
        
        // Recalculate unread count
        $unread_count = $db->fetchOne("
            SELECT COUNT(*) as count FROM notifications 
            WHERE user_id = ? AND is_read = 0
        ", [$user['id']]);
        $unread_count = $unread_count['count'] ?? 0;
        
    } catch (Exception $e) {
        $error_msg = "❌ " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi - SIRAGA</title>
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2c3e50;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --info: #17a2b8;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 70px rgba(0,0,0,0.4);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(to right, var(--secondary), #1a252f);
            color: white;
            padding: 30px 40px;
        }
        
        .header h1 {
            font-size: 32px;
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 10px;
        }
        
        .user-info {
            background: rgba(255,255,255,0.1);
            padding: 15px 25px;
            border-radius: 12px;
            display: inline-block;
        }
        
        .main-content {
            padding: 40px;
        }
        
        /* NAV MENU */
        .nav-menu {
            display: flex;
            gap: 15px;
            margin-bottom: 40px;
            flex-wrap: wrap;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 15px;
            border: 1px solid #e0e0e0;
        }
        
        .nav-menu a {
            color: var(--secondary);
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 10px;
            background: white;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }
        
        .nav-menu a:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .nav-menu a.active {
            background: var(--primary);
            color: white;
        }
        
        /* ALERTS */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: fadeIn 0.5s;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 5px solid var(--success);
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 5px solid var(--danger);
        }
        
        /* ACTION BUTTONS */
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(to right, var(--primary), #2980b9);
            color: white;
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(52, 152, 219, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(to right, var(--success), #229954);
            color: white;
        }
        
        .btn-success:hover {
            transform: translateY(-3px);
        }
        
        .btn-warning {
            background: linear-gradient(to right, var(--warning), #e67e22);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(to right, var(--danger), #c0392b);
            color: white;
        }
        
        .btn-danger:hover {
            transform: translateY(-3px);
        }
        
        .btn-sm {
            padding: 8px 15px;
            font-size: 13px;
        }
        
        /* NOTIFICATION LIST */
        .notification-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .notification-item {
            background: white;
            border-radius: 12px;
            padding: 20px;
            border-left: 5px solid;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s;
            position: relative;
        }
        
        .notification-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .notification-item.unread {
            background: #f0f7ff;
            border-left-color: var(--primary);
        }
        
        .notification-item.read {
            background: #f8f9fa;
            border-left-color: #95a5a6;
            opacity: 0.9;
        }
        
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        
        .notification-type {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: var(--secondary);
            font-weight: 600;
        }
        
        .notification-time {
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .notification-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--secondary);
            margin-bottom: 8px;
        }
        
        .notification-message {
            color: #5a6268;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 15px;
        }
        
        .notification-child {
            display: inline-block;
            padding: 4px 10px;
            background: #e8f4fc;
            color: var(--primary);
            border-radius: 15px;
            font-size: 12px;
            margin-bottom: 10px;
        }
        
        .notification-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        /* IMPORTANT NOTIFICATION */
        .notification-item.important {
            border-left-color: var(--warning);
            background: linear-gradient(to right, #fff8e1, #fff3cd);
        }
        
        /* TYPE COLORS */
        .notification-item.type-info { border-left-color: var(--info); }
        .notification-item.type-warning { border-left-color: var(--warning); }
        .notification-item.type-danger { border-left-color: var(--danger); }
        .notification-item.type-success { border-left-color: var(--success); }
        
        /* EMPTY STATE */
        .empty-state {
            text-align: center;
            padding: 60px 40px;
            color: #7f8c8d;
        }
        
        .empty-icon {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        /* STATS BADGE */
        .stats-badge {
            display: inline-block;
            padding: 10px 20px;
            background: linear-gradient(to right, #e8f4fc, #d4edf7);
            color: var(--primary);
            border-radius: 10px;
            font-weight: 600;
            margin-left: 10px;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            .main-content {
                padding: 25px;
            }
            
            .header {
                padding: 25px;
            }
            
            .header h1 {
                font-size: 26px;
            }
            
            .nav-menu {
                flex-direction: column;
            }
            
            .nav-menu a {
                justify-content: center;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .notification-header {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <h1>
                <i class="fas fa-bell"></i>
                Notifikasi
                <?php if ($unread_count > 0): ?>
                    <span style="background: var(--danger); color: white; border-radius: 50%; padding: 8px 15px; font-size: 14px; margin-left: 10px;">
                        <?php echo $unread_count; ?>
                    </span>
                <?php endif; ?>
            </h1>
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                <span style="background: #f39c12; color: white; padding: 3px 12px; border-radius: 20px; font-size: 12px;">
                    👪 Orang Tua
                </span>
            </div>
        </div>
        
        <div class="main-content">
            <!-- NAV MENU -->
            <div class="nav-menu">
                <a href="anak_saya.php">
                    <i class="fas fa-child"></i> Data Anak
                </a>
                <a href="grafik.php">
                    <i class="fas fa-chart-line"></i> Grafik
                </a>
                <a href="jadwal.php">
                    <i class="fas fa-calendar-check"></i> Jadwal
                </a>
                <a href="notifikasi.php" class="active">
                    <i class="fas fa-bell"></i> Notifikasi
                    <?php if ($unread_count > 0): ?>
                        <span style="background: var(--danger); color: white; border-radius: 50%; padding: 2px 8px; font-size: 12px;">
                            <?php echo $unread_count; ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a href="/siraga/dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </div>
            
            <!-- ALERTS -->
            <?php if ($success_msg): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_msg): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>
            
            <!-- ACTION BUTTONS -->
            <div class="action-buttons">
                <?php if ($unread_count > 0): ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="mark_all_read">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check-double"></i> Tandai Semua Sudah Dibaca
                        </button>
                    </form>
                <?php endif; ?>
                
                <?php if (!empty($notifications)): ?>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Hapus semua notifikasi?')">
                        <input type="hidden" name="action" value="clear_all">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Hapus Semua
                        </button>
                    </form>
                <?php endif; ?>
                
                <div style="flex-grow: 1;"></div>
                
                <span style="align-self: center; color: var(--secondary); font-size: 14px; font-weight: 600;">
                    <span class="stats-badge">
                        <?php echo count($notifications); ?> Notifikasi
                        <?php if ($unread_count > 0): ?>
                            (<span style="color: var(--danger);"><?php echo $unread_count; ?></span> belum dibaca)
                        <?php endif; ?>
                    </span>
                </span>
            </div>
            
            <!-- NOTIFICATION LIST -->
            <?php if (empty($notifications)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🔔</div>
                    <h3>Tidak ada notifikasi</h3>
                    <p>Anda tidak memiliki notifikasi saat ini.</p>
                    <p style="margin-top: 10px; font-size: 14px; color: #7f8c8d;">
                        Notifikasi akan muncul saat ada jadwal imunisasi, pemeriksaan, atau informasi penting.
                    </p>
                </div>
            <?php else: ?>
                <div class="notification-list">
                    <?php foreach ($notifications as $notif): ?>
                    <div class="notification-item <?php echo $notif['is_read'] ? 'read' : 'unread'; ?> type-<?php echo $notif['type']; ?> <?php echo $notif['is_important'] ? 'important' : ''; ?>">
                        <div class="notification-header">
                            <div class="notification-type">
                                <span style="font-size: 18px;"><?php echo $notif['type_icon']; ?></span>
                                <?php 
                                $type_names = [
                                    'info' => 'Informasi',
                                    'warning' => 'Peringatan',
                                    'danger' => 'Penting',
                                    'success' => 'Berhasil'
                                ];
                                echo $type_names[$notif['type']] ?? 'Notifikasi';
                                ?>
                                <?php if ($notif['is_important']): ?>
                                    <span style="color: var(--warning); font-size: 12px;">
                                        <i class="fas fa-exclamation-triangle"></i> Penting
                                    </span>
                                <?php endif; ?>
                                <?php if ($notif['child_name']): ?>
                                    <span class="notification-child">
                                        <i class="fas fa-child"></i> <?php echo htmlspecialchars($notif['child_name']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="notification-time">
                                <?php echo $notif['created_formatted']; ?>
                                <?php if ($notif['is_read'] && $notif['read_at']): ?>
                                    <br><small>Dibaca: <?php echo date('d/m/Y H:i', strtotime($notif['read_at'])); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="notification-title">
                            <?php echo htmlspecialchars($notif['title']); ?>
                        </div>
                        
                        <div class="notification-message">
                            <?php echo nl2br(htmlspecialchars($notif['message'])); ?>
                        </div>
                        
                        <div class="notification-actions">
                            <?php if (!$notif['is_read']): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="mark_as_read">
                                    <input type="hidden" name="notification_id" value="<?php echo $notif['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="fas fa-check"></i> Tandai Sudah Dibaca
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <form method="POST" style="display: inline;" 
                                  onsubmit="return confirm('Hapus notifikasi ini?')">
                                <input type="hidden" name="action" value="delete_notification">
                                <input type="hidden" name="notification_id" value="<?php echo $notif['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="fas fa-trash"></i> Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- AUTO-REFRESH IF UNREAD NOTIFICATIONS -->
    <?php if ($unread_count > 0): ?>
    <script>
    // Auto-refresh every 30 seconds if there are unread notifications
    setTimeout(function() {
        location.reload();
    }, 30000);
    
    // Auto-hide alerts
    setTimeout(function() {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.opacity = '0';
            setTimeout(() => alert.style.display = 'none', 500);
        });
    }, 5000);
    </script>
    <?php endif; ?>
    
    <script>
    // Auto-hide alerts
    setTimeout(function() {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.opacity = '0';
            setTimeout(() => alert.style.display = 'none', 500);
        });
    }, 5000);
    </script>
    
    <!-- FONT AWESOME -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</body>
</html>