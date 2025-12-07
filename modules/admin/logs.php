<?php
/**
 * SYSTEM LOGS - SIRAGA
 * Lokasi: modules/admin/logs.php
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ======================== CEK SESSION ========================
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: /siraga/dashboard.php');
    exit;
}

$user = $_SESSION['user'];

// ======================== KONFIGURASI PATH ========================
define('BASE_URL', '/siraga');
define('ROOT_PATH', dirname(__DIR__, 2));

// ======================== INCLUDE FILE ========================
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/includes/functions.php';

// ======================== INISIALISASI DATABASE ========================
try {
    $db = Database::getInstance();
    $db_connected = true;
} catch (Exception $e) {
    $db_connected = false;
    $db_error = $e->getMessage();
}

// ======================== VARIABEL & FILTER ========================
$success_msg = '';
$error_msg = '';

// Filter parameters
$filter_type = $_GET['type'] ?? 'all';
$filter_date = $_GET['date'] ?? '';
$filter_user = $_GET['user'] ?? '';
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// ======================== HANDLE ACTIONS ========================
if (isset($_GET['action']) && $db_connected) {
    $action = $_GET['action'];
    
    if ($action === 'clear_logs') {
        $type = $_GET['log_type'] ?? '';
        
        if ($type === 'activity') {
            $db->query("TRUNCATE TABLE activity_logs");
            $success_msg = "✅ Activity logs berhasil dibersihkan!";
        } elseif ($type === 'error') {
            $db->query("TRUNCATE TABLE error_logs");
            $success_msg = "✅ Error logs berhasil dibersihkan!";
        } elseif ($type === 'all') {
            $db->query("TRUNCATE TABLE activity_logs");
            $db->query("TRUNCATE TABLE error_logs");
            $success_msg = "✅ Semua logs berhasil dibersihkan!";
        }
    }
    
    if ($action === 'export') {
        $type = $_GET['export_type'] ?? 'activity';
        export_logs($db, $type);
        exit;
    }
}

// ======================== FUNGSI EXPORT ========================
function export_logs($db, $type) {
    if ($type === 'activity') {
        $logs = $db->fetchAll("
            SELECT al.*, u.name as user_name, u.email 
            FROM activity_logs al 
            LEFT JOIN users u ON al.user_id = u.id 
            ORDER BY al.created_at DESC
        ");
        $filename = 'activity_logs_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Tanggal', 'User', 'Email', 'Aksi', 'Deskripsi', 'IP Address', 'User Agent']);
        
        foreach ($logs as $log) {
            fputcsv($output, [
                $log['id'],
                $log['created_at'],
                $log['user_name'] ?? 'System',
                $log['email'] ?? '',
                $log['action'],
                $log['description'],
                $log['ip_address'],
                $log['user_agent']
            ]);
        }
        fclose($output);
        
    } elseif ($type === 'error') {
        $logs = $db->fetchAll("SELECT * FROM error_logs ORDER BY created_at DESC");
        $filename = 'error_logs_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Tanggal', 'Error Type', 'Message', 'File', 'Line', 'Trace']);
        
        foreach ($logs as $log) {
            fputcsv($output, [
                $log['id'],
                $log['created_at'],
                $log['error_type'],
                $log['message'],
                $log['file'],
                $log['line'],
                $log['trace']
            ]);
        }
        fclose($output);
    }
}

// ======================== AMBIL DATA LOGS ========================
$activity_logs = [];
$error_logs = [];
$stats = [
    'total_activities' => 0,
    'today_activities' => 0,
    'total_errors' => 0,
    'today_errors' => 0,
    'total_users' => 0
];

if ($db_connected) {
    try {
        // Build query conditions
        $where_conditions = [];
        $params = [];
        
        if ($filter_type === 'today') {
            $where_conditions[] = "DATE(al.created_at) = CURDATE()";
        } elseif ($filter_date) {
            $where_conditions[] = "DATE(al.created_at) = ?";
            $params[] = $filter_date;
        }
        
        if ($filter_user) {
            $where_conditions[] = "(u.name LIKE ? OR u.email LIKE ?)";
            $params[] = "%$filter_user%";
            $params[] = "%$filter_user%";
        }
        
        if ($search) {
            $where_conditions[] = "(al.action LIKE ? OR al.description LIKE ? OR al.ip_address LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";
        
        // Get activity logs
        $activity_query = "
            SELECT al.*, u.name as user_name, u.email, u.role as user_role
            FROM activity_logs al 
            LEFT JOIN users u ON al.user_id = u.id 
            $where_clause
            ORDER BY al.created_at DESC 
            LIMIT ? OFFSET ?
        ";
        
        $activity_params = array_merge($params, [$limit, $offset]);
        $activity_logs = $db->fetchAll($activity_query, $activity_params);
        
        // Get error logs
        $error_logs = $db->fetchAll("
            SELECT * FROM error_logs 
            ORDER BY created_at DESC 
            LIMIT $limit
        ");
        
        // Get statistics
        $stats['total_activities'] = $db->count('activity_logs');
        $stats['today_activities'] = $db->count('activity_logs', "DATE(created_at) = CURDATE()");
        $stats['total_errors'] = $db->count('error_logs');
        $stats['today_errors'] = $db->count('error_logs', "DATE(created_at) = CURDATE()");
        $stats['total_users'] = $db->count('users');
        
    } catch (Exception $e) {
        $error_msg = "❌ Error mengambil logs: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs - SIRAGA</title>
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2c3e50;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --dark: #2c3e50;
            --light: #f8f9fa;
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
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(to right, var(--secondary), var(--dark));
            color: white;
            padding: 25px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .header h1 {
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-info {
            text-align: right;
        }
        
        .user-name {
            font-weight: 600;
            font-size: 16px;
        }
        
        .user-role {
            background: var(--primary);
            color: white;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 12px;
            margin-top: 5px;
            display: inline-block;
        }
        
        .main-content {
            padding: 30px;
        }
        
        /* STATS CARDS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            text-align: center;
            border-top: 5px solid;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card.activity { border-color: var(--primary); }
        .stat-card.error { border-color: var(--danger); }
        .stat-card.today { border-color: var(--success); }
        .stat-card.users { border-color: var(--warning); }
        
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 10px;
            color: var(--dark);
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        /* FILTER SECTION */
        .filter-section {
            background: var(--light);
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            border: 1px solid #e0e0e0;
        }
        
        .filter-title {
            color: var(--dark);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
        }
        
        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #dfe6e9;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(to right, var(--primary), #2980b9);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }
        
        .btn-success {
            background: linear-gradient(to right, var(--success), #229954);
            color: white;
        }
        
        .btn-warning {
            background: linear-gradient(to right, var(--warning), #e67e22);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(to right, var(--danger), #c0392b);
            color: white;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        /* ACTION BUTTONS */
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        
        /* ALERTS */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 5px solid;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left-color: var(--success);
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left-color: var(--danger);
        }
        
        /* TABS */
        .tab-container {
            margin-bottom: 30px;
        }
        
        .tab-buttons {
            display: flex;
            border-bottom: 2px solid #eee;
            margin-bottom: 0;
            flex-wrap: wrap;
        }
        
        .tab-btn {
            padding: 15px 30px;
            background: var(--light);
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-weight: 600;
            color: #7f8c8d;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .tab-btn:hover {
            color: var(--dark);
            background: #e9ecef;
        }
        
        .tab-btn.active {
            background: white;
            border-bottom: 3px solid var(--primary);
            color: var(--primary);
        }
        
        .tab-content {
            display: none;
            padding: 0;
            background: white;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* TABLES */
        .table-container {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            margin-top: 0;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }
        
        .table th {
            background: var(--secondary);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            border: none;
        }
        
        .table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
            vertical-align: top;
        }
        
        .table tr:hover {
            background: #f8f9fa;
        }
        
        .table tr:nth-child(even) {
            background: #fcfcfc;
        }
        
        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-success { background: #d4edda; color: #155724; }
        .badge-primary { background: #d1ecf1; color: #0c5460; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        
        .log-action {
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .log-details {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .log-details:hover {
            white-space: normal;
            overflow: visible;
            background: white;
            position: absolute;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 100;
        }
        
        .timestamp {
            color: #7f8c8d;
            font-size: 12px;
            white-space: nowrap;
        }
        
        .user-cell {
            min-width: 150px;
        }
        
        /* PAGINATION */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .pagination a, .pagination span {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-decoration: none;
            color: var(--dark);
            font-size: 14px;
        }
        
        .pagination a:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .pagination .active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        /* NAV MENU */
        .nav-menu {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .nav-menu a {
            color: var(--primary);
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 8px;
            background: var(--light);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .nav-menu a:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            .container {
                margin: 10px;
            }
            
            .main-content {
                padding: 20px;
            }
            
            .header {
                flex-direction: column;
                text-align: center;
                padding: 20px;
            }
            
            .user-info {
                text-align: center;
            }
            
            .tab-btn {
                padding: 12px 20px;
                font-size: 14px;
            }
            
            .filter-form {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <h1>📊 System Logs Monitor</h1>
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                <span class="user-role">Administrator</span>
            </div>
        </div>
        
        <div class="main-content">
            <!-- NAV MENU -->
            <div class="nav-menu">
                <a href="/siraga/dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="user_management.php">
                    <i class="fas fa-users"></i> Users
                </a>
                <a href="database.php">
                    <i class="fas fa-database"></i> Database
                </a>
                <a href="settings.php">
                    <i class="fas fa-cogs"></i> Settings
                </a>
                <a href="logs.php" style="background: var(--primary); color: white;">
                    <i class="fas fa-clipboard-list"></i> Logs
                </a>
            </div>
            
            <!-- ALERTS -->
            <?php if ($success_msg): ?>
                <div class="alert alert-success"><?php echo $success_msg; ?></div>
            <?php endif; ?>
            
            <?php if ($error_msg): ?>
                <div class="alert alert-danger"><?php echo $error_msg; ?></div>
            <?php endif; ?>
            
            <!-- STATISTICS -->
            <div class="stats-grid">
                <div class="stat-card activity">
                    <div class="stat-number"><?php echo number_format($stats['total_activities']); ?></div>
                    <div class="stat-label">Total Aktivitas</div>
                </div>
                
                <div class="stat-card today">
                    <div class="stat-number"><?php echo number_format($stats['today_activities']); ?></div>
                    <div class="stat-label">Aktivitas Hari Ini</div>
                </div>
                
                <div class="stat-card error">
                    <div class="stat-number"><?php echo number_format($stats['total_errors']); ?></div>
                    <div class="stat-label">Total Error</div>
                </div>
                
                <div class="stat-card users">
                    <div class="stat-number"><?php echo number_format($stats['total_users']); ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
            </div>
            
            <!-- FILTER SECTION -->
            <div class="filter-section">
                <h3 class="filter-title">
                    <i class="fas fa-filter"></i> Filter Logs
                </h3>
                
                <form method="GET" class="filter-form">
                    <div class="form-group">
                        <label>Tipe Log</label>
                        <select name="type" class="form-control">
                            <option value="all" <?php echo $filter_type === 'all' ? 'selected' : ''; ?>>Semua Log</option>
                            <option value="today" <?php echo $filter_type === 'today' ? 'selected' : ''; ?>>Hari Ini</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Tanggal Spesifik</label>
                        <input type="date" name="date" class="form-control" 
                               value="<?php echo htmlspecialchars($filter_date); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Cari User</label>
                        <input type="text" name="user" class="form-control" 
                               placeholder="Nama atau email user..." 
                               value="<?php echo htmlspecialchars($filter_user); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Cari Kata Kunci</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Aksi, deskripsi, atau IP..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- ACTION BUTTONS -->
            <div class="action-buttons">
                <a href="?action=export&export_type=activity" class="btn btn-success">
                    <i class="fas fa-file-export"></i> Export Activity Logs (CSV)
                </a>
                
                <a href="?action=export&export_type=error" class="btn btn-warning">
                    <i class="fas fa-file-export"></i> Export Error Logs (CSV)
                </a>
                
                <a href="?action=clear_logs&log_type=activity" class="btn btn-danger" 
                   onclick="return confirm('Hapus SEMUA activity logs?')">
                    <i class="fas fa-trash"></i> Clear Activity Logs
                </a>
                
                <a href="?action=clear_logs&log_type=error" class="btn btn-danger"
                   onclick="return confirm('Hapus SEMUA error logs?')">
                    <i class="fas fa-trash"></i> Clear Error Logs
                </a>
            </div>
            
            <!-- TABS -->
            <div class="tab-container">
                <div class="tab-buttons">
                    <button class="tab-btn active" data-tab="activity">
                        <i class="fas fa-history"></i> Activity Logs
                        <span class="badge badge-primary"><?php echo count($activity_logs); ?></span>
                    </button>
                    
                    <button class="tab-btn" data-tab="error">
                        <i class="fas fa-exclamation-triangle"></i> Error Logs
                        <span class="badge badge-danger"><?php echo count($error_logs); ?></span>
                    </button>
                </div>
                
                <!-- TAB 1: ACTIVITY LOGS -->
                <div class="tab-content active" id="activity">
                    <?php if (empty($activity_logs)): ?>
                        <div style="text-align: center; padding: 40px; color: #7f8c8d;">
                            <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 20px;"></i>
                            <h3>Tidak ada activity logs</h3>
                            <p>Belum ada aktivitas yang tercatat.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th style="width: 150px;">Waktu</th>
                                        <th style="width: 180px;">User</th>
                                        <th style="width: 120px;">Role</th>
                                        <th style="width: 120px;">Aksi</th>
                                        <th>Deskripsi</th>
                                        <th style="width: 120px;">IP Address</th>
                                        <th style="width: 80px;">Info</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($activity_logs as $log): ?>
                                    <tr>
                                        <td class="timestamp">
                                            <?php echo date('d/m/Y', strtotime($log['created_at'])); ?><br>
                                            <small><?php echo date('H:i:s', strtotime($log['created_at'])); ?></small>
                                        </td>
                                        <td class="user-cell">
                                            <?php if ($log['user_name']): ?>
                                                <strong><?php echo htmlspecialchars($log['user_name']); ?></strong><br>
                                                <small><?php echo htmlspecialchars($log['email']); ?></small>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">System</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($log['user_role']): ?>
                                                <?php 
                                                $role_badge = [
                                                    'admin' => 'badge-danger',
                                                    'nakes' => 'badge-success', 
                                                    'parent' => 'badge-warning',
                                                    'government' => 'badge-primary'
                                                ];
                                                ?>
                                                <span class="badge <?php echo $role_badge[$log['user_role']] ?? 'badge-info'; ?>">
                                                    <?php echo strtoupper($log['user_role']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="log-action">
                                                <?php echo htmlspecialchars($log['action']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="log-details" title="<?php echo htmlspecialchars($log['description']); ?>">
                                                <?php echo htmlspecialchars($log['description']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <code><?php echo htmlspecialchars($log['ip_address']); ?></code>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-secondary" 
                                                    onclick="showLogDetails(<?php echo $log['id']; ?>)"
                                                    title="View details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- PAGINATION -->
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page-1; ?>&type=<?php echo $filter_type; ?>&date=<?php echo $filter_date; ?>">
                                    &laquo; Previous
                                </a>
                            <?php endif; ?>
                            
                            <span class="active">Halaman <?php echo $page; ?></span>
                            
                            <?php if (count($activity_logs) == $limit): ?>
                                <a href="?page=<?php echo $page+1; ?>&type=<?php echo $filter_type; ?>&date=<?php echo $filter_date; ?>">
                                    Next &raquo;
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- TAB 2: ERROR LOGS -->
                <div class="tab-content" id="error">
                    <?php if (empty($error_logs)): ?>
                        <div style="text-align: center; padding: 40px; color: #7f8c8d;">
                            <i class="fas fa-check-circle" style="font-size: 48px; margin-bottom: 20px; color: #27ae60;"></i>
                            <h3>Tidak ada error logs</h3>
                            <p>Sistem berjalan dengan baik tanpa error.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th style="width: 150px;">Waktu</th>
                                        <th style="width: 100px;">Tipe Error</th>
                                        <th>Message</th>
                                        <th style="width: 200px;">File</th>
                                        <th style="width: 60px;">Line</th>
                                        <th style="width: 80px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($error_logs as $error): ?>
                                    <tr>
                                        <td class="timestamp">
                                            <?php echo date('d/m/Y H:i:s', strtotime($error['created_at'])); ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-danger">
                                                <?php echo htmlspecialchars($error['error_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="log-details" title="<?php echo htmlspecialchars($error['message']); ?>">
                                                <?php echo htmlspecialchars($error['message']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <small><?php echo htmlspecialchars(basename($error['file'])); ?></small>
                                            <br>
                                            <small style="color: #7f8c8d;"><?php echo htmlspecialchars(dirname($error['file'])); ?></small>
                                        </td>
                                        <td><?php echo $error['line']; ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-danger" 
                                                    onclick="showErrorDetails(<?php echo $error['id']; ?>)"
                                                    title="View error details">
                                                <i class="fas fa-bug"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- FOOTER -->
            <div style="text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee;">
                <a href="/siraga/dashboard.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                </a>
                
                <button class="btn btn-secondary" onclick="location.reload()">
                    <i class="fas fa-sync-alt"></i> Refresh Page
                </button>
            </div>
        </div>
    </div>
    
    <!-- MODAL SCRIPT -->
    <script>
    // Tab Navigation
    document.addEventListener('DOMContentLoaded', function() {
        const tabButtons = document.querySelectorAll('.tab-btn');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                
                // Remove active class from all buttons and contents
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));
                
                // Add active class to clicked button and corresponding content
                this.classList.add('active');
                document.getElementById(tabId).classList.add('active');
            });
        });
        
        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.style.display = 'none', 500);
            });
        }, 5000);
        
        // Show log details on hover
        document.querySelectorAll('.log-details').forEach(item => {
            item.addEventListener('mouseenter', function(e) {
                if (this.offsetWidth < this.scrollWidth) {
                    this.title = this.textContent;
                }
            });
        });
    });
    
    // Show log details modal
    function showLogDetails(logId) {
        alert('Log ID: ' + logId + '\n\nDetail view akan ditampilkan di sini.\n(Fitur akan dilengkapi)');
    }
    
    // Show error details modal
    function showErrorDetails(errorId) {
        alert('Error ID: ' + errorId + '\n\nDetail error akan ditampilkan di sini.\n(Fitur akan dilengkapi)');
    }
    
    // Confirm before clearing logs
    function confirmClearLogs(type) {
        return confirm('Yakin ingin menghapus SEMUA ' + type + ' logs?\nTindakan ini tidak dapat dibatalkan!');
    }
    
    // Export logs
    function exportLogs(type) {
        window.location.href = '?action=export&export_type=' + type;
    }
    </script>
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</body>
</html>