<?php
/**
 * PENGATURAN SISTEM - SIRAGA
 * Lokasi: modules/admin/settings.php
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

// ======================== AMBIL PENGATURAN ========================
$settings = [];

if ($db_connected) {
    try {
        // Cek apakah tabel settings ada
        $tables = $db->fetchAll("SHOW TABLES LIKE 'settings'");
        
        if (!empty($tables)) {
            // Ambil semua settings dengan error handling
            $settings_result = $db->fetchAll("SELECT setting_key, value FROM settings");
            
            // Pastikan hasil query valid
            if ($settings_result && is_array($settings_result)) {
                foreach ($settings_result as $row) {
                    // Pastikan array memiliki key yang diperlukan
                    if (isset($row['setting_key']) && isset($row['value'])) {
                        $settings[$row['setting_key']] = $row['value'];
                    }
                }
            }
        }
    } catch (Exception $e) {
        // Tabel mungkin belum ada, tidak perlu error
        // $error_msg = "Info: Tabel settings belum tersedia";
    }
}

// Default settings (fallback jika tidak ada di database)
$default_settings = [
    'app_name' => 'SIRAGA',
    'app_version' => '1.0.0',
    'admin_email' => 'ongkiid81@gmail.com',
    'support_phone' => '0878-3260-8497',
    'maintenance_mode' => '0',
    'logo_url' => '/siraga/assets/img/logo.png',
    'smtp_host' => '',
    'smtp_port' => '587',
    'smtp_user' => '',
    'smtp_pass' => '',
    'max_login_attempts' => '5',
    'session_timeout' => '30',
    'backup_auto' => '1',
    'backup_frequency' => 'daily'
];

// Gabungkan dengan default
foreach ($default_settings as $key => $value) {
    if (!isset($settings[$key]) || $settings[$key] === null) {
        $settings[$key] = $value;
    }
}

// ======================== HANDLE FORM ========================
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db_connected) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_settings') {
        try {
            foreach ($_POST as $key => $value) {
                if (strpos($key, 'setting_') === 0) {
                    $setting_key = substr($key, 8);
                    $setting_value = trim($value);
                    
                    // Cek apakah setting sudah ada
                    $exists = $db->fetchOne("SELECT 1 FROM settings WHERE setting_key = ?", [$setting_key]);
                    
                    if ($exists) {
                        // Update existing
                        $db->update('settings', 
                            ['value' => $setting_value, 'updated_at' => date('Y-m-d H:i:s')],
                            'setting_key = ?',
                            [$setting_key]
                        );
                    } else {
                        // Insert new
                        $db->insert('settings', [
                            'setting_key' => $setting_key,
                            'value' => $setting_value,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                    }
                }
            }
            
            $success_msg = "✅ Pengaturan berhasil disimpan!";
            
            // Refresh settings setelah update
            $settings_result = $db->fetchAll("SELECT setting_key, value FROM settings");
            $settings = [];
            if ($settings_result && is_array($settings_result)) {
                foreach ($settings_result as $row) {
                    if (isset($row['setting_key']) && isset($row['value'])) {
                        $settings[$row['setting_key']] = $row['value'];
                    }
                }
            }
            
            // Gabungkan kembali dengan default
            foreach ($default_settings as $key => $value) {
                if (!isset($settings[$key]) || $settings[$key] === null) {
                    $settings[$key] = $value;
                }
            }
            
        } catch (Exception $e) {
            $error_msg = "❌ Error menyimpan pengaturan: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Sistem - SIRAGA</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .container {
            max-width: 1100px;
            margin: 30px auto;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .page-title {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 15px;
            margin-bottom: 30px;
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .page-title:before {
            content: "⚙️";
            font-size: 32px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 5px solid;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left-color: #28a745;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left-color: #dc3545;
        }
        
        .nav-menu {
            background: linear-gradient(to right, #3498db, #2c3e50);
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            color: white;
        }
        
        .nav-menu a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            background: rgba(255,255,255,0.1);
            transition: all 0.3s;
        }
        
        .nav-menu a:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
        }
        
        .card {
            border: none;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            background: linear-gradient(145deg, #ffffff, #f0f0f0);
            box-shadow: 5px 5px 15px rgba(0,0,0,0.1);
        }
        
        .card-title {
            color: #2c3e50;
            margin-bottom: 25px;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-title:before {
            font-size: 24px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #34495e;
            font-size: 15px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 18px;
            border: 2px solid #dfe6e9;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
            background: white;
        }
        
        .form-control:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(to right, #3498db, #2980b9);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(52, 152, 219, 0.3);
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 15px 0;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .system-info {
            background: linear-gradient(145deg, #2c3e50, #34495e);
            padding: 30px;
            border-radius: 12px;
            margin-top: 40px;
            color: white;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .info-item {
            padding: 15px;
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .info-item strong {
            color: #3498db;
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .footer-links {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 30px;
            margin-right: 15px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .toggle-slider {
            background-color: #2ecc71;
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(30px);
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 15px;
                padding: 20px;
            }
            
            .form-row {
                flex-direction: column;
                gap: 15px;
            }
            
            .nav-menu {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="page-title">Pengaturan Sistem</h1>
        
        <!-- NAV MENU -->
        <div class="nav-menu">
            <span>👤 Login sebagai: <strong><?php echo htmlspecialchars($user['name']); ?></strong></span>
            <div style="flex-grow: 1;"></div>
            <a href="/siraga/dashboard.php">🏠 Dashboard</a>
            <a href="user_management.php">👥 Users</a>
            <a href="database.php">💾 Database</a>
            <a href="logs.php">📊 Logs</a>
        </div>
        
        <!-- NOTIFICATIONS -->
        <?php if ($success_msg): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        
        <?php if ($error_msg): ?>
            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>
        
        <?php if (!$db_connected): ?>
            <div class="alert alert-danger">
                ❌ <strong>Database Error:</strong> <?php echo $db_error ?? 'Tidak dapat terhubung ke database'; ?>
            </div>
        <?php endif; ?>
        
        <!-- MAIN FORM -->
        <form method="POST" id="settingsForm">
            <input type="hidden" name="action" value="save_settings">
            
            <!-- CARD 1: APP INFO -->
            <div class="card">
                <h3 class="card-title" style="color: #3498db;">📱 Informasi Aplikasi</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Nama Aplikasi</label>
                        <input type="text" name="setting_app_name" class="form-control" 
                               value="<?php echo htmlspecialchars($settings['app_name'] ?? 'SIRAGA'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Versi</label>
                        <input type="text" name="setting_app_version" class="form-control" 
                               value="<?php echo htmlspecialchars($settings['app_version'] ?? '1.0.0'); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Email Admin</label>
                        <input type="email" name="setting_admin_email" class="form-control" 
                               value="<?php echo htmlspecialchars($settings['admin_email'] ?? 'ongkiid81@gmail.com'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Telepon Support</label>
                        <input type="text" name="setting_support_phone" class="form-control" 
                               value="<?php echo htmlspecialchars($settings['support_phone'] ?? '0878-3260-8497'); ?>">
                    </div>
                </div>
            </div>
            
            <!-- CARD 2: SECURITY -->
            <div class="card">
                <h3 class="card-title" style="color: #e74c3c;">🔐 Keamanan</h3>
                
                <div class="checkbox-group">
                    <label class="toggle-switch">
                        <input type="checkbox" name="setting_maintenance_mode" value="1" 
                               <?php echo ($settings['maintenance_mode'] ?? '0') == '1' ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                    <span style="font-weight: 600;">Mode Maintenance</span>
                </div>
                <small style="display: block; margin-left: 70px; color: #7f8c8d; margin-top: -10px;">
                    Jika aktif, hanya admin yang bisa mengakses sistem
                </small>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Max Login Attempts</label>
                        <input type="number" name="setting_max_login_attempts" class="form-control" 
                               value="<?php echo htmlspecialchars($settings['max_login_attempts'] ?? '5'); ?>"
                               min="1" max="10">
                        <small>Jumlah maksimal percobaan login gagal</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Session Timeout (menit)</label>
                        <input type="number" name="setting_session_timeout" class="form-control" 
                               value="<?php echo htmlspecialchars($settings['session_timeout'] ?? '30'); ?>"
                               min="5" max="1440">
                        <small>Waktu tidak aktif sebelum logout otomatis</small>
                    </div>
                </div>
            </div>
            
            <!-- CARD 3: EMAIL SETTINGS -->
            <div class="card">
                <h3 class="card-title" style="color: #2ecc71;">📧 Email Settings</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>SMTP Host</label>
                        <input type="text" name="setting_smtp_host" class="form-control" 
                               value="<?php echo htmlspecialchars($settings['smtp_host'] ?? ''); ?>"
                               placeholder="smtp.gmail.com">
                    </div>
                    
                    <div class="form-group">
                        <label>SMTP Port</label>
                        <input type="text" name="setting_smtp_port" class="form-control" 
                               value="<?php echo htmlspecialchars($settings['smtp_port'] ?? '587'); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>SMTP Username</label>
                        <input type="text" name="setting_smtp_user" class="form-control" 
                               value="<?php echo htmlspecialchars($settings['smtp_user'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>SMTP Password</label>
                        <input type="password" name="setting_smtp_pass" class="form-control" 
                               value="<?php echo htmlspecialchars($settings['smtp_pass'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            
            <!-- ACTION BUTTONS -->
            <div style="text-align: center; margin: 40px 0;">
                <button type="submit" class="btn btn-primary">
                    💾 Simpan Pengaturan
                </button>
                
                <a href="?refresh=1" class="btn btn-secondary" style="margin-left: 15px;">
                    🔄 Refresh
                </a>
                
                <a href="/siraga/dashboard.php" class="btn btn-secondary" style="margin-left: 15px;">
                    ← Kembali
                </a>
            </div>
        </form>
        
        <!-- SYSTEM INFO -->
        <div class="system-info">
            <h3 style="color: white; margin-top: 0;">🖥️ Informasi Sistem</h3>
            
            <div class="info-grid">
                <div class="info-item">
                    <strong>PHP Version:</strong>
                    <?php echo phpversion(); ?>
                </div>
                
                <div class="info-item">
                    <strong>Server:</strong>
                    <?php echo $_SERVER['SERVER_SOFTWARE']; ?>
                </div>
                
                <div class="info-item">
                    <strong>Database Status:</strong>
                    <?php echo $db_connected ? '✅ Terhubung' : '❌ Error'; ?>
                </div>
                
                <div class="info-item">
                    <strong>Waktu Server:</strong>
                    <?php echo date('d/m/Y H:i:s'); ?>
                </div>
                
                <div class="info-item">
                    <strong>IP Address:</strong>
                    <?php echo $_SERVER['REMOTE_ADDR']; ?>
                </div>
                
                <div class="info-item">
                    <strong>Memory Usage:</strong>
                    <?php echo round(memory_get_usage() / 1024 / 1024, 2); ?> MB
                </div>
            </div>
        </div>
        
        <!-- DEBUG LINK -->
        <div class="footer-links">
            <a href="?debug=1" style="color: #3498db; text-decoration: none;">
                🐛 Debug Mode
            </a>
        </div>
    </div>
    
    <script>
    // Auto-hide alerts
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.opacity = '0';
            setTimeout(() => alert.style.display = 'none', 500);
        });
    }, 5000);
    
    // Form change detection
    const form = document.getElementById('settingsForm');
    let formChanged = false;
    
    form.querySelectorAll('input, select, textarea').forEach(input => {
        input.addEventListener('change', () => formChanged = true);
    });
    
    window.addEventListener('beforeunload', (e) => {
        if (formChanged) {
            e.preventDefault();
            e.returnValue = 'Perubahan belum disimpan. Yakin ingin meninggalkan halaman?';
        }
    });
    
    // Form submit
    form.addEventListener('submit', () => {
        formChanged = false;
        document.querySelector('.btn-primary').innerHTML = '⏳ Menyimpan...';
        document.querySelector('.btn-primary').disabled = true;
    });
    </script>
</body>
</html>