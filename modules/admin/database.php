<?php
/**
 * DATABASE MANAGEMENT - SIRAGA
 * Lokasi: modules/admin/database.php
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
define('BACKUP_DIR', ROOT_PATH . '/backups');

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

// ======================== VARIABEL ========================
$success_msg = '';
$error_msg = '';
$action_result = [];

// ======================== CREATE BACKUP DIR ========================
if (!is_dir(BACKUP_DIR)) {
    mkdir(BACKUP_DIR, 0755, true);
}

// ======================== HANDLE ACTIONS ========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db_connected) {
    $action = $_POST['action'] ?? '';
    
    // 1. CREATE BACKUP
    if ($action === 'create_backup') {
        try {
            $backup_name = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            $backup_path = BACKUP_DIR . '/' . $backup_name;
            
            // Get database config
            $config = [
                'host' => '127.0.0.1',
                'user' => 'root',
                'pass' => '', // Kosong di XAMPP default
                'name' => 'siraga_db1'
            ];
            
            // Build mysqldump command
            $command = sprintf(
                'mysqldump --host=%s --user=%s --password=%s %s > "%s"',
                escapeshellarg($config['host']),
                escapeshellarg($config['user']),
                escapeshellarg($config['pass']),
                escapeshellarg($config['name']),
                escapeshellarg($backup_path)
            );
            
            // Execute command
            exec($command, $output, $return_var);
            
            if ($return_var === 0 && file_exists($backup_path)) {
                // Save backup info to database
                $db->insert('backup_logs', [
                    'filename' => $backup_name,
                    'path' => $backup_path,
                    'size' => filesize($backup_path),
                    'created_by' => $user['id'],
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                $success_msg = "✅ Backup berhasil dibuat: " . $backup_name;
                $action_result = [
                    'file' => $backup_name,
                    'size' => format_size(filesize($backup_path)),
                    'path' => $backup_path
                ];
                
                // Log activity
                log_activity($user['id'], 'DATABASE_BACKUP', 'Membuat backup database: ' . $backup_name);
            } else {
                throw new Exception("Gagal menjalankan mysqldump command");
            }
            
        } catch (Exception $e) {
            $error_msg = "❌ Gagal membuat backup: " . $e->getMessage();
            
            // Fallback: Manual backup via PHP
            try {
                $backup_name = 'backup_manual_' . date('Y-m-d_H-i-s') . '.sql';
                $backup_path = BACKUP_DIR . '/' . $backup_name;
                
                $tables = $db->fetchAll("SHOW TABLES");
                $sql_content = "";
                
                foreach ($tables as $table) {
                    $table_name = array_values($table)[0];
                    
                    // Get create table statement
                    $create_table = $db->fetchOne("SHOW CREATE TABLE `$table_name`");
                    $sql_content .= "\n\n-- Table structure for table `$table_name`\n";
                    $sql_content .= "DROP TABLE IF EXISTS `$table_name`;\n";
                    $sql_content .= $create_table['Create Table'] . ";\n\n";
                    
                    // Get table data
                    $rows = $db->fetchAll("SELECT * FROM `$table_name`");
                    if (!empty($rows)) {
                        $sql_content .= "-- Dumping data for table `$table_name`\n";
                        foreach ($rows as $row) {
                            $values = array_map(function($value) use ($db) {
                                if ($value === null) return 'NULL';
                                return "'" . $db->escape($value) . "'";
                            }, $row);
                            
                            $sql_content .= "INSERT INTO `$table_name` VALUES (" . implode(', ', $values) . ");\n";
                        }
                    }
                }
                
                if (file_put_contents($backup_path, $sql_content)) {
                    $db->insert('backup_logs', [
                        'filename' => $backup_name,
                        'path' => $backup_path,
                        'size' => filesize($backup_path),
                        'created_by' => $user['id'],
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    $success_msg = "✅ Backup manual berhasil dibuat: " . $backup_name;
                    $action_result = [
                        'file' => $backup_name,
                        'size' => format_size(filesize($backup_path)),
                        'path' => $backup_path
                    ];
                }
            } catch (Exception $e2) {
                $error_msg = "❌ Gagal membuat backup manual juga: " . $e2->getMessage();
            }
        }
    }
    
    // 2. OPTIMIZE DATABASE
    elseif ($action === 'optimize_database') {
        try {
            $tables = $db->fetchAll("SHOW TABLES");
            $optimized_tables = [];
            
            foreach ($tables as $table) {
                $table_name = array_values($table)[0];
                $db->query("OPTIMIZE TABLE `$table_name`");
                $optimized_tables[] = $table_name;
            }
            
            $success_msg = "✅ Database berhasil dioptimasi!";
            $action_result = [
                'tables' => $optimized_tables,
                'count' => count($optimized_tables)
            ];
            
            log_activity($user['id'], 'DATABASE_OPTIMIZE', 'Mengoptimasi database: ' . count($optimized_tables) . ' tabel');
            
        } catch (Exception $e) {
            $error_msg = "❌ Gagal mengoptimasi database: " . $e->getMessage();
        }
    }
    
    // 3. REPAIR DATABASE
    elseif ($action === 'repair_database') {
        try {
            $tables = $db->fetchAll("SHOW TABLES");
            $repaired_tables = [];
            
            foreach ($tables as $table) {
                $table_name = array_values($table)[0];
                $result = $db->fetchOne("REPAIR TABLE `$table_name`");
                if ($result && strpos($result['Msg_text'] ?? '', 'OK') !== false) {
                    $repaired_tables[] = $table_name;
                }
            }
            
            $success_msg = "✅ Database berhasil diperbaiki!";
            $action_result = [
                'tables' => $repaired_tables,
                'count' => count($repaired_tables)
            ];
            
            log_activity($user['id'], 'DATABASE_REPAIR', 'Memperbaiki database: ' . count($repaired_tables) . ' tabel');
            
        } catch (Exception $e) {
            $error_msg = "❌ Gagal memperbaiki database: " . $e->getMessage();
        }
    }
    
    // 4. ANALYZE DATABASE
    elseif ($action === 'analyze_database') {
        try {
            $tables = $db->fetchAll("SHOW TABLES");
            $analyzed_tables = [];
            
            foreach ($tables as $table) {
                $table_name = array_values($table)[0];
                $db->query("ANALYZE TABLE `$table_name`");
                $analyzed_tables[] = $table_name;
            }
            
            $success_msg = "✅ Database berhasil dianalisa!";
            $action_result = [
                'tables' => $analyzed_tables,
                'count' => count($analyzed_tables)
            ];
            
        } catch (Exception $e) {
            $error_msg = "❌ Gagal menganalisa database: " . $e->getMessage();
        }
    }
    
    // 5. RESTORE DATABASE
    elseif ($action === 'restore_database' && isset($_FILES['backup_file'])) {
        $backup_file = $_FILES['backup_file'];
        
        if ($backup_file['error'] === UPLOAD_ERR_OK) {
            $temp_path = $backup_file['tmp_name'];
            $file_ext = strtolower(pathinfo($backup_file['name'], PATHINFO_EXTENSION));
            
            if ($file_ext === 'sql') {
                try {
                    // Read SQL file
                    $sql_content = file_get_contents($temp_path);
                    
                    // Split by semicolon (basic SQL parsing)
                    $queries = array_filter(array_map('trim', explode(';', $sql_content)));
                    
                    // Execute each query
                    $executed_queries = 0;
                    foreach ($queries as $query) {
                        if (!empty($query)) {
                            $db->query($query);
                            $executed_queries++;
                        }
                    }
                    
                    $success_msg = "✅ Database berhasil direstore!";
                    $action_result = [
                        'queries' => $executed_queries,
                        'file' => $backup_file['name']
                    ];
                    
                    log_activity($user['id'], 'DATABASE_RESTORE', 'Merestore database dari file: ' . $backup_file['name']);
                    
                } catch (Exception $e) {
                    $error_msg = "❌ Gagal merestore database: " . $e->getMessage();
                }
            } else {
                $error_msg = "❌ File harus berekstensi .sql";
            }
        } else {
            $error_msg = "❌ Error upload file: " . $backup_file['error'];
        }
    }
    
    // 6. DELETE BACKUP
    elseif ($action === 'delete_backup' && isset($_POST['backup_file'])) {
        $backup_file = $_POST['backup_file'];
        $backup_path = BACKUP_DIR . '/' . basename($backup_file);
        
        if (file_exists($backup_path) && unlink($backup_path)) {
            // Delete from database
            $db->delete('backup_logs', 'filename = ?', [$backup_file]);
            $success_msg = "✅ Backup berhasil dihapus: " . $backup_file;
            
            log_activity($user['id'], 'BACKUP_DELETE', 'Menghapus backup: ' . $backup_file);
        } else {
            $error_msg = "❌ Gagal menghapus backup";
        }
    }
}

// ======================== FUNGSI HELPER ========================
function format_size($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

// ======================== AMBIL DATA ========================
$database_info = [];
$table_info = [];
$backup_list = [];
$database_size = 0;

if ($db_connected) {
    try {
        // Get database info
        $database_info = $db->fetchOne("SELECT 
            @@version as version,
            @@version_comment as version_comment,
            @@hostname as hostname,
            DATABASE() as db_name,
            NOW() as server_time
        ");
        
        // Get table info
        $tables = $db->fetchAll("SHOW TABLE STATUS");
        foreach ($tables as $table) {
            $table_info[] = [
                'name' => $table['Name'],
                'rows' => $table['Rows'],
                'data_length' => $table['Data_length'],
                'index_length' => $table['Index_length'],
                'engine' => $table['Engine'],
                'collation' => $table['Collation']
            ];
            
            $database_size += $table['Data_length'] + $table['Index_length'];
        }
        
        // Get backup list
        $backup_files = glob(BACKUP_DIR . '/*.sql');
        foreach ($backup_files as $file) {
            $backup_list[] = [
                'filename' => basename($file),
                'path' => $file,
                'size' => filesize($file),
                'modified' => filemtime($file),
                'created' => date('Y-m-d H:i:s', filemtime($file))
            ];
        }
        
        // Sort by modified time (newest first)
        usort($backup_list, function($a, $b) {
            return $b['modified'] - $a['modified'];
        });
        
        // Get backup logs from database
        $backup_logs = $db->fetchAll("
            SELECT bl.*, u.name as created_by_name 
            FROM backup_logs bl 
            LEFT JOIN users u ON bl.created_by = u.id 
            ORDER BY bl.created_at DESC 
            LIMIT 20
        ");
        
    } catch (Exception $e) {
        $error_msg = "❌ Error mengambil data: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Management - SIRAGA</title>
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
            background: linear-gradient(135deg, #1a2980 0%, #26d0ce 100%);
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
            background: linear-gradient(to right, var(--dark), #1a252f);
            color: white;
            padding: 30px 40px;
            position: relative;
            overflow: hidden;
        }
        
        .header:before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(52, 152, 219, 0.1) 0%, transparent 70%);
        }
        
        .header h1 {
            font-size: 32px;
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 10px;
        }
        
        .header-subtitle {
            color: #bdc3c7;
            font-size: 16px;
            margin-bottom: 20px;
        }
        
        .user-info {
            background: rgba(255,255,255,0.1);
            padding: 15px 25px;
            border-radius: 12px;
            display: inline-block;
            backdrop-filter: blur(10px);
        }
        
        .user-name {
            font-weight: 600;
            font-size: 16px;
        }
        
        .user-role {
            background: var(--primary);
            color: white;
            padding: 4px 15px;
            border-radius: 20px;
            font-size: 12px;
            margin-top: 5px;
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
            background: var(--light);
            padding: 20px;
            border-radius: 15px;
            border: 1px solid #e0e0e0;
        }
        
        .nav-menu a {
            color: var(--dark);
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
            padding: 20px 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            border-left: 6px solid;
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .alert-success {
            background: linear-gradient(to right, #d4edda, #c3e6cb);
            color: #155724;
            border-left-color: var(--success);
        }
        
        .alert-danger {
            background: linear-gradient(to right, #f8d7da, #f5c6cb);
            color: #721c24;
            border-left-color: var(--danger);
        }
        
        .alert-info {
            background: linear-gradient(to right, #d1ecf1, #bee5eb);
            color: #0c5460;
            border-left-color: #17a2b8;
        }
        
        /* STATS GRID */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            border-top: 5px solid;
        }
        
        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .stat-card:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: inherit;
        }
        
        .stat-card.db-size { border-color: var(--primary); }
        .stat-card.tables { border-color: var(--success); }
        .stat-card.backups { border-color: var(--warning); }
        .stat-card.version { border-color: var(--danger); }
        
        .stat-icon {
            font-size: 40px;
            margin-bottom: 20px;
            color: inherit;
        }
        
        .stat-number {
            font-size: 42px;
            font-weight: bold;
            margin-bottom: 10px;
            color: var(--dark);
            line-height: 1;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 15px;
            font-weight: 600;
        }
        
        /* ACTION CARDS */
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .action-card {
            background: white;
            padding: 35px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .action-card:hover {
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        
        .card-title {
            color: var(--dark);
            margin-bottom: 25px;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .card-title i {
            font-size: 26px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--dark);
            font-size: 15px;
        }
        
        .form-control {
            width: 100%;
            padding: 14px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
            background: #fcfcfc;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.2);
            background: white;
        }
        
        .file-input {
            padding: 12px;
            background: #f8f9fa;
            border: 2px dashed #bdc3c7;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .file-input:hover {
            border-color: var(--primary);
            background: #f0f7ff;
        }
        
        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s;
            min-width: 180px;
            justify-content: center;
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
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }
        
        .btn-warning {
            background: linear-gradient(to right, var(--warning), #e67e22);
            color: white;
            box-shadow: 0 5px 15px rgba(243, 156, 18, 0.3);
        }
        
        .btn-danger {
            background: linear-gradient(to right, var(--danger), #c0392b);
            color: white;
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
        }
        
        .btn-secondary {
            background: linear-gradient(to right, #95a5a6, #7f8c8d);
            color: white;
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 14px;
            min-width: auto;
        }
        
        .warning-box {
            background: linear-gradient(to right, #fff3cd, #ffeaa7);
            color: #856404;
            padding: 20px;
            border-radius: 10px;
            border-left: 5px solid var(--warning);
            margin: 20px 0;
        }
        
        /* TABLES */
        .table-container {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid #e0e0e0;
            margin-top: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }
        
        .table th {
            background: linear-gradient(to right, var(--secondary), var(--dark));
            color: white;
            padding: 18px 20px;
            text-align: left;
            font-weight: 600;
            font-size: 15px;
            border: none;
        }
        
        .table td {
            padding: 16px 20px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
            vertical-align: middle;
        }
        
        .table tr:hover {
            background: #f8fafc;
        }
        
        .table tr:nth-child(even) {
            background: #fcfcfc;
        }
        
        .badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-success { background: #d4edda; color: #155724; }
        .badge-primary { background: #d1ecf1; color: #0c5460; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-info { background: #e8f4fc; color: #2c3e50; }
        
        .progress-bar {
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 5px;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(to right, var(--primary), var(--success));
            border-radius: 4px;
            transition: width 1s ease-in-out;
        }
        
        /* TABS */
        .tab-container {
            margin-bottom: 40px;
        }
        
        .tab-buttons {
            display: flex;
            border-bottom: 2px solid #eee;
            margin-bottom: 0;
            flex-wrap: wrap;
        }
        
        .tab-btn {
            padding: 18px 35px;
            background: var(--light);
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-weight: 600;
            color: #7f8c8d;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 16px;
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
            animation: fadeIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* ACTION RESULT */
        .result-box {
            background: #f8fafc;
            padding: 25px;
            border-radius: 12px;
            margin-top: 20px;
            border-left: 5px solid var(--primary);
        }
        
        .result-title {
            color: var(--dark);
            margin-bottom: 15px;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .result-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .result-item {
            padding: 15px;
            background: white;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }
        
        .result-label {
            font-size: 13px;
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        
        .result-value {
            font-weight: 600;
            color: var(--dark);
            font-size: 16px;
        }
        
        /* FOOTER */
        .footer {
            text-align: center;
            margin-top: 50px;
            padding-top: 30px;
            border-top: 1px solid #eee;
        }
        
        /* RESPONSIVE */
        @media (max-width: 992px) {
            .main-content {
                padding: 25px;
            }
            
            .header {
                padding: 25px;
            }
            
            .header h1 {
                font-size: 26px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .action-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .container {
                border-radius: 15px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .tab-btn {
                padding: 15px 20px;
                font-size: 14px;
            }
            
            .nav-menu {
                flex-direction: column;
            }
            
            .nav-menu a {
                justify-content: center;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <h1>
                <i class="fas fa-database"></i>
                Database Management
            </h1>
            <div class="header-subtitle">
                Backup, restore, optimasi, dan monitoring database SIRAGA
            </div>
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
                <a href="database.php" class="active">
                    <i class="fas fa-database"></i> Database
                </a>
                <a href="settings.php">
                    <i class="fas fa-cogs"></i> Settings
                </a>
                <a href="logs.php">
                    <i class="fas fa-clipboard-list"></i> Logs
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
            
            <?php if (!$db_connected): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-database"></i> 
                    <strong>Database Error:</strong> <?php echo $db_error ?? 'Tidak dapat terhubung ke database'; ?>
                </div>
            <?php endif; ?>
            
            <!-- STATISTICS -->
            <div class="stats-grid">
                <div class="stat-card db-size">
                    <div class="stat-icon">💾</div>
                    <div class="stat-number"><?php echo format_size($database_size); ?></div>
                    <div class="stat-label">Ukuran Database</div>
                </div>
                
                <div class="stat-card tables">
                    <div class="stat-icon">📊</div>
                    <div class="stat-number"><?php echo count($table_info); ?></div>
                    <div class="stat-label">Jumlah Tabel</div>
                </div>
                
                <div class="stat-card backups">
                    <div class="stat-icon">📁</div>
                    <div class="stat-number"><?php echo count($backup_list); ?></div>
                    <div class="stat-label">Backup Tersedia</div>
                </div>
                
                <div class="stat-card version">
                    <div class="stat-icon">⚡</div>
                    <div class="stat-number">MySQL <?php echo $database_info['version'] ?? '?'; ?></div>
                    <div class="stat-label">Database Version</div>
                </div>
            </div>
            
            <!-- ACTION RESULT -->
            <?php if (!empty($action_result)): ?>
                <div class="result-box">
                    <div class="result-title">
                        <i class="fas fa-info-circle"></i> Hasil Aksi
                    </div>
                    <div class="result-details">
                        <?php foreach ($action_result as $key => $value): ?>
                            <?php if (is_array($value)): ?>
                                <div class="result-item">
                                    <div class="result-label"><?php echo ucfirst(str_replace('_', ' ', $key)); ?></div>
                                    <div class="result-value"><?php echo implode(', ', $value); ?></div>
                                </div>
                            <?php else: ?>
                                <div class="result-item">
                                    <div class="result-label"><?php echo ucfirst(str_replace('_', ' ', $key)); ?></div>
                                    <div class="result-value"><?php echo $value; ?></div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- ACTION CARDS -->
            <div class="action-grid">
                <!-- BACKUP CARD -->
                <div class="action-card">
                    <h3 class="card-title" style="color: #3498db;">
                        <i class="fas fa-save"></i> Backup Database
                    </h3>
                    <p style="margin-bottom: 25px; color: #7f8c8d;">
                        Buat salinan lengkap database untuk keamanan data.
                    </p>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="create_backup">
                        <button type="submit" class="btn btn-primary" onclick="return confirm('Buat backup database sekarang?')">
                            <i class="fas fa-download"></i> Buat Backup Sekarang
                        </button>
                    </form>
                    
                    <div class="warning-box" style="margin-top: 20px;">
                        <i class="fas fa-info-circle"></i>
                        <strong>Info:</strong> Backup akan disimpan di folder <code>/backups/</code>
                    </div>
                </div>
                
                <!-- RESTORE CARD -->
                <div class="action-card">
                    <h3 class="card-title" style="color: #f39c12;">
                        <i class="fas fa-upload"></i> Restore Database
                    </h3>
                    <p style="margin-bottom: 25px; color: #7f8c8d;">
                        Pulihkan database dari file backup (.sql)
                    </p>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="restore_database">
                        
                        <div class="form-group">
                            <label>Pilih File Backup</label>
                            <div class="file-input">
                                <input type="file" name="backup_file" accept=".sql" required
                                       onchange="document.getElementById('fileName').textContent = this.files[0].name">
                                <div id="fileName" style="margin-top: 10px; color: #7f8c8d;">
                                    Klik untuk memilih file .sql
                                </div>
                            </div>
                        </div>
                        
                        <div class="warning-box">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Peringatan:</strong> Restore akan menimpa data saat ini!
                        </div>
                        
                        <button type="submit" class="btn btn-warning" 
                                onclick="return confirm('Yakin ingin restore database? Semua data saat ini akan ditimpa!')">
                            <i class="fas fa-upload"></i> Restore Database
                        </button>
                    </form>
                </div>
                
                <!-- OPTIMIZATION CARD -->
                <div class="action-card">
                    <h3 class="card-title" style="color: #27ae60;">
                        <i class="fas fa-bolt"></i> Optimasi Database
                    </h3>
                    <p style="margin-bottom: 25px; color: #7f8c8d;">
                        Optimasi tabel untuk meningkatkan performa database.
                    </p>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <form method="POST" style="grid-column: span 2;">
                            <input type="hidden" name="action" value="optimize_database">
                            <button type="submit" class="btn btn-success" onclick="return confirm('Optimasi semua tabel?')">
                                <i class="fas fa-bolt"></i> Optimasi
                            </button>
                        </form>
                        
                        <form method="POST">
                            <input type="hidden" name="action" value="repair_database">
                            <button type="submit" class="btn btn-warning" onclick="return confirm('Perbaiki semua tabel?')">
                                <i class="fas fa-tools"></i> Repair
                            </button>
                        </form>
                        
                        <form method="POST">
                            <input type="hidden" name="action" value="analyze_database">
                            <button type="submit" class="btn btn-primary" onclick="return confirm('Analisa semua tabel?')">
                                <i class="fas fa-chart-bar"></i> Analisa
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- TABS -->
            <div class="tab-container">
                <div class="tab-buttons">
                    <button class="tab-btn active" data-tab="backups">
                        <i class="fas fa-archive"></i> Backup Files
                        <span class="badge badge-primary"><?php echo count($backup_list); ?></span>
                    </button>
                    
                    <button class="tab-btn" data-tab="tables">
                        <i class="fas fa-table"></i> Table Info
                        <span class="badge badge-success"><?php echo count($table_info); ?></span>
                    </button>
                    
                    <button class="tab-btn" data-tab="logs">
                        <i class="fas fa-history"></i> Backup Logs
                        <span class="badge badge-warning"><?php echo count($backup_logs ?? []); ?></span>
                    </button>
                </div>
                
                <!-- TAB 1: BACKUP FILES -->
                <div class="tab-content active" id="backups">
                    <?php if (empty($backup_list)): ?>
                        <div style="text-align: center; padding: 50px; color: #7f8c8d;">
                            <i class="fas fa-archive" style="font-size: 60px; margin-bottom: 20px; opacity: 0.5;"></i>
                            <h3>Belum ada backup</h3>
                            <p>Gunakan fitur "Backup Database" untuk membuat backup pertama.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Nama File</th>
                                        <th>Ukuran</th>
                                        <th>Tanggal Dibuat</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($backup_list as $backup): ?>
                                    <tr>
                                        <td style="font-family: 'Courier New', monospace;">
                                            <i class="fas fa-file-alt" style="color: #3498db; margin-right: 10px;"></i>
                                            <?php echo htmlspecialchars($backup['filename']); ?>
                                        </td>
                                        <td>
                                            <strong><?php echo format_size($backup['size']); ?></strong>
                                            <div class="progress-bar">
                                                <?php 
                                                $max_size = 100 * 1024 * 1024; // 100MB
                                                $percent = min(100, ($backup['size'] / $max_size) * 100);
                                                ?>
                                                <div class="progress-fill" style="width: <?php echo $percent; ?>%"></div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo date('d/m/Y H:i:s', $backup['modified']); ?>
                                            <br>
                                            <small style="color: #7f8c8d;">
                                                <?php echo time_ago($backup['modified']); ?> yang lalu
                                            </small>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                                <a href="<?php echo BASE_URL; ?>/backups/<?php echo urlencode($backup['filename']); ?>" 
                                                   class="btn btn-primary btn-sm" download>
                                                    <i class="fas fa-download"></i> Download
                                                </a>
                                                
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete_backup">
                                                    <input type="hidden" name="backup_file" value="<?php echo $backup['filename']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" 
                                                            onclick="return confirm('Hapus backup <?php echo $backup['filename']; ?>?')">
                                                        <i class="fas fa-trash"></i> Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div style="margin-top: 20px; text-align: center;">
                            <p style="color: #7f8c8d;">
                                <i class="fas fa-info-circle"></i>
                                Total backup: <?php echo count($backup_list); ?> file, 
                                Total size: <?php 
                                    $total_size = array_sum(array_column($backup_list, 'size'));
                                    echo format_size($total_size);
                                ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- TAB 2: TABLE INFO -->
                <div class="tab-content" id="tables">
                    <?php if (empty($table_info)): ?>
                        <div style="text-align: center; padding: 50px; color: #7f8c8d;">
                            <i class="fas fa-database" style="font-size: 60px; margin-bottom: 20px; opacity: 0.5;"></i>
                            <h3>Tidak ada tabel</h3>
                            <p>Database tidak memiliki tabel atau tidak dapat diakses.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Tabel</th>
                                        <th>Rows</th>
                                        <th>Data Size</th>
                                        <th>Index Size</th>
                                        <th>Engine</th>
                                        <th>Collation</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($table_info as $table): ?>
                                    <tr>
                                        <td style="font-weight: 600;">
                                            <i class="fas fa-table" style="color: #3498db; margin-right: 10px;"></i>
                                            <?php echo htmlspecialchars($table['name']); ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-info"><?php echo number_format($table['rows']); ?></span>
                                        </td>
                                        <td><?php echo format_size($table['data_length']); ?></td>
                                        <td><?php echo format_size($table['index_length']); ?></td>
                                        <td>
                                            <span class="badge badge-primary"><?php echo $table['engine']; ?></span>
                                        </td>
                                        <td>
                                            <small style="font-family: 'Courier New', monospace;">
                                                <?php echo $table['collation']; ?>
                                            </small>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr style="background: #f8f9fa; font-weight: bold;">
                                        <td>TOTAL</td>
                                        <td><?php echo number_format(array_sum(array_column($table_info, 'rows'))); ?></td>
                                        <td><?php echo format_size(array_sum(array_column($table_info, 'data_length'))); ?></td>
                                        <td><?php echo format_size(array_sum(array_column($table_info, 'index_length'))); ?></td>
                                        <td colspan="2">
                                            Total Size: <?php echo format_size($database_size); ?>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- TAB 3: BACKUP LOGS -->
                <div class="tab-content" id="logs">
                    <?php if (empty($backup_logs)): ?>
                        <div style="text-align: center; padding: 50px; color: #7f8c8d;">
                            <i class="fas fa-history" style="font-size: 60px; margin-bottom: 20px; opacity: 0.5;"></i>
                            <h3>Belum ada log backup</h3>
                            <p>Log akan muncul setelah Anda membuat backup pertama.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Waktu</th>
                                        <th>File Name</th>
                                        <th>Ukuran</th>
                                        <th>Dibuat Oleh</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($backup_logs as $log): ?>
                                    <tr>
                                        <td>
                                            <?php echo date('d/m/Y', strtotime($log['created_at'])); ?>
                                            <br>
                                            <small><?php echo date('H:i:s', strtotime($log['created_at'])); ?></small>
                                        </td>
                                        <td style="font-family: 'Courier New', monospace;">
                                            <i class="fas fa-file-alt" style="color: #3498db; margin-right: 10px;"></i>
                                            <?php echo htmlspecialchars($log['filename']); ?>
                                        </td>
                                        <td><?php echo format_size($log['size']); ?></td>
                                        <td>
                                            <?php if ($log['created_by_name']): ?>
                                                <?php echo htmlspecialchars($log['created_by_name']); ?>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">System</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (file_exists($log['path'])): ?>
                                                <span class="badge badge-success">Tersedia</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Hilang</span>
                                            <?php endif; ?>
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
            <div class="footer">
                <a href="/siraga/dashboard.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                </a>
                
                <button class="btn btn-secondary" onclick="location.reload()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
                
                <a href="?action=phpinfo" class="btn btn-info" target="_blank">
                    <i class="fas fa-info-circle"></i> PHP Info
                </a>
            </div>
        </div>
    </div>
    
    <!-- SCRIPTS -->
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
                
                // Save active tab to localStorage
                localStorage.setItem('activeDbTab', tabId);
            });
        });
        
        // Restore active tab from localStorage
        const savedTab = localStorage.getItem('activeDbTab') || 'backups';
        const savedTabButton = document.querySelector(`[data-tab="${savedTab}"]`);
        if (savedTabButton) {
            savedTabButton.click();
        }
        
        // Auto-hide alerts
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.style.display = 'none', 500);
            });
        }, 8000);
        
        // File input preview
        const fileInput = document.querySelector('input[type="file"]');
        if (fileInput) {
            fileInput.addEventListener('change', function() {
                const fileName = document.getElementById('fileName');
                if (this.files.length > 0) {
                    fileName.innerHTML = `<i class="fas fa-file-alt"></i> ${this.files[0].name}`;
                    fileName.style.color = '#27ae60';
                    fileName.style.fontWeight = '600';
                } else {
                    fileName.innerHTML = 'Klik untuk memilih file .sql';
                    fileName.style.color = '#7f8c8d';
                    fileName.style.fontWeight = 'normal';
                }
            });
        }
        
        // Confirm dangerous actions
        document.querySelectorAll('form').forEach(form => {
            const button = form.querySelector('button[type="submit"]');
            if (button && (button.textContent.includes('Restore') || button.textContent.includes('Hapus'))) {
                form.addEventListener('submit', function(e) {
                    if (!confirm('Tindakan ini berisiko! Lanjutkan?')) {
                        e.preventDefault();
                    }
                });
            }
        });
        
        // Progress bar animation
        document.querySelectorAll('.progress-fill').forEach(bar => {
            const width = bar.style.width;
            bar.style.width = '0%';
            setTimeout(() => {
                bar.style.width = width;
            }, 300);
        });
    });
    
    // Time ago helper
    function time_ago(timestamp) {
        const seconds = Math.floor((new Date() - timestamp * 1000) / 1000);
        let interval = Math.floor(seconds / 31536000);
        if (interval >= 1) return interval + " tahun";
        interval = Math.floor(seconds / 2592000);
        if (interval >= 1) return interval + " bulan";
        interval = Math.floor(seconds / 86400);
        if (interval >= 1) return interval + " hari";
        interval = Math.floor(seconds / 3600);
        if (interval >= 1) return interval + " jam";
        interval = Math.floor(seconds / 60);
        if (interval >= 1) return interval + " menit";
        return Math.floor(seconds) + " detik";
    }
    
    // Update time ago
    document.querySelectorAll('small').forEach(el => {
        if (el.textContent.includes('yang lalu')) {
            const timestamp = el.closest('tr').querySelector('td:nth-child(3)').getAttribute('data-timestamp');
            if (timestamp) {
                setInterval(() => {
                    el.textContent = time_ago(parseInt(timestamp)) + ' yang lalu';
                }, 60000);
            }
        }
    });
    </script>
    
    <!-- FONT AWESOME -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</body>
</html>

<?php
// Helper function for time ago
function time_ago($timestamp) {
    $seconds = time() - $timestamp;
    
    if ($seconds < 60) return "baru saja";
    
    $minutes = floor($seconds / 60);
    if ($minutes < 60) return $minutes . " menit yang lalu";
    
    $hours = floor($minutes / 60);
    if ($hours < 24) return $hours . " jam yang lalu";
    
    $days = floor($hours / 24);
    if ($days < 30) return $days . " hari yang lalu";
    
    $months = floor($days / 30);
    if ($months < 12) return $months . " bulan yang lalu";
    
    $years = floor($months / 12);
    return $years . " tahun yang lalu";
}
?>