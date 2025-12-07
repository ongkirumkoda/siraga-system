<?php
/**
 * MANAJEMEN USER - SIRAGA
 * Lokasi: modules/admin/user_management.php
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ======================== CEK SESSION ========================
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: /siraga/dashboard.php');
    exit;
}

$admin_user = $_SESSION['user'];

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
$user_data = [];
$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Filter parameters
$filter_role = $_GET['role'] ?? 'all';
$filter_status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Role options
$role_options = [
    'admin' => ['name' => 'Administrator', 'color' => '#9b59b6', 'icon' => '👑'],
    'nakes' => ['name' => 'Tenaga Kesehatan', 'color' => '#3498db', 'icon' => '⚕️'],
    'parent' => ['name' => 'Orang Tua', 'color' => '#f39c12', 'icon' => '👪'],
    'government' => ['name' => 'Pemerintah', 'color' => '#2ecc71', 'icon' => '🏛️']
];

// Status options
$status_options = [
    'active' => ['name' => 'Aktif', 'color' => '#27ae60', 'icon' => '✅'],
    'inactive' => ['name' => 'Nonaktif', 'color' => '#e74c3c', 'icon' => '⛔'],
    'pending' => ['name' => 'Pending', 'color' => '#f39c12', 'icon' => '⏳']
];

// ======================== HANDLE ACTIONS ========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db_connected) {
    $action = $_POST['action'] ?? '';
    
    // 1. ADD/EDIT USER
    if ($action === 'save_user') {
        try {
            $user_id = intval($_POST['id'] ?? 0);
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            $role = $_POST['role'];
            $status = $_POST['status'] ?? 'active';
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $notes = trim($_POST['notes'] ?? '');
            
            // Validation
            if (empty($name) || empty($email) || empty($role)) {
                throw new Exception("Nama, email, dan role wajib diisi!");
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Format email tidak valid!");
            }
            
            // Check email uniqueness (except for current user)
            $existing_user = $db->fetchOne("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $user_id]);
            if ($existing_user) {
                throw new Exception("Email sudah digunakan oleh user lain!");
            }
            
            $user_data = [
                'name' => $name,
                'email' => $email,
                'role' => $role,
                'status' => $status,
                'phone' => $phone,
                'address' => $address,
                'notes' => $notes,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Handle password
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            if (!empty($password)) {
                if ($password !== $confirm_password) {
                    throw new Exception("Password dan konfirmasi password tidak cocok!");
                }
                if (strlen($password) < 6) {
                    throw new Exception("Password minimal 6 karakter!");
                }
                $user_data['password'] = password_hash($password, PASSWORD_DEFAULT);
            }
            
            if ($user_id > 0) {
                // UPDATE EXISTING USER
                $db->update('users', $user_data, 'id = ?', [$user_id]);
                $success_msg = "✅ User berhasil diperbarui!";
                log_activity($admin_user['id'], 'UPDATE_USER', "Memperbarui user: {$name} ({$email})");
            } else {
                // ADD NEW USER
                $user_data['created_at'] = date('Y-m-d H:i:s');
                
                // Set default password if not provided
                if (empty($password)) {
                    $default_password = 'siraga123'; // Default password
                    $user_data['password'] = password_hash($default_password, PASSWORD_DEFAULT);
                }
                
                $new_user_id = $db->insert('users', $user_data);
                $success_msg = "✅ User baru berhasil ditambahkan!";
                
                // Send welcome email (optional)
                // send_welcome_email($email, $name, $default_password ?? $password);
                
                log_activity($admin_user['id'], 'ADD_USER', "Menambahkan user baru: {$name} ({$email})");
            }
            
            // Refresh page to show updated data
            header("Location: ?success=" . urlencode($success_msg));
            exit;
            
        } catch (Exception $e) {
            $error_msg = "❌ " . $e->getMessage();
            // Keep form data for re-fill
            $user_data = $_POST;
        }
    }
    
    // 2. DELETE USER
    elseif ($action === 'delete_user') {
        $user_id = intval($_POST['user_id'] ?? 0);
        
        if ($user_id === $admin_user['id']) {
            $error_msg = "❌ Tidak bisa menghapus akun sendiri!";
        } elseif ($user_id > 0) {
            try {
                // Get user info before deletion for logging
                $user = $db->fetchOne("SELECT name, email FROM users WHERE id = ?", [$user_id]);
                
                if ($user) {
                    // Soft delete (update status to deleted) or hard delete
                    $db->update('users', [
                        'status' => 'deleted',
                        'deleted_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ], 'id = ?', [$user_id]);
                    
                    // Or hard delete:
                    // $db->delete('users', 'id = ?', [$user_id]);
                    
                    $success_msg = "✅ User berhasil dihapus!";
                    log_activity($admin_user['id'], 'DELETE_USER', "Menghapus user: {$user['name']} ({$user['email']})");
                }
            } catch (Exception $e) {
                $error_msg = "❌ Gagal menghapus user: " . $e->getMessage();
            }
        }
    }
    
    // 3. BULK ACTIONS
    elseif ($action === 'bulk_action') {
        $bulk_action = $_POST['bulk_action'] ?? '';
        $selected_users = $_POST['selected_users'] ?? [];
        
        if (empty($selected_users)) {
            $error_msg = "❌ Pilih user terlebih dahulu!";
        } else {
            try {
                $user_ids = array_filter(array_map('intval', $selected_users));
                
                switch ($bulk_action) {
                    case 'activate':
                        $db->query("UPDATE users SET status = 'active', updated_at = NOW() WHERE id IN (" . implode(',', $user_ids) . ")");
                        $success_msg = "✅ " . count($user_ids) . " user diaktifkan!";
                        log_activity($admin_user['id'], 'BULK_ACTIVATE', "Mengaktifkan " . count($user_ids) . " user");
                        break;
                        
                    case 'deactivate':
                        $db->query("UPDATE users SET status = 'inactive', updated_at = NOW() WHERE id IN (" . implode(',', $user_ids) . ")");
                        $success_msg = "✅ " . count($user_ids) . " user dinonaktifkan!";
                        log_activity($admin_user['id'], 'BULK_DEACTIVATE', "Menonaktifkan " . count($user_ids) . " user");
                        break;
                        
                    case 'delete':
                        // Don't allow self-deletion
                        if (in_array($admin_user['id'], $user_ids)) {
                            $error_msg = "❌ Tidak bisa menghapus akun sendiri!";
                        } else {
                            $db->query("UPDATE users SET status = 'deleted', deleted_at = NOW(), updated_at = NOW() WHERE id IN (" . implode(',', $user_ids) . ")");
                            $success_msg = "✅ " . count($user_ids) . " user dihapus!";
                            log_activity($admin_user['id'], 'BULK_DELETE', "Menghapus " . count($user_ids) . " user");
                        }
                        break;
                        
                    case 'send_email':
                        $success_msg = "✅ Email akan dikirim ke " . count($user_ids) . " user!";
                        // Implement email sending here
                        break;
                }
            } catch (Exception $e) {
                $error_msg = "❌ Gagal melakukan bulk action: " . $e->getMessage();
            }
        }
    }
    
    // 4. RESET PASSWORD
    elseif ($action === 'reset_password') {
        $user_id = intval($_POST['user_id'] ?? 0);
        
        if ($user_id > 0) {
            try {
                $new_password = generate_random_password();
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                $db->update('users', [
                    'password' => $hashed_password,
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = ?', [$user_id]);
                
                // Get user info
                $user = $db->fetchOne("SELECT name, email FROM users WHERE id = ?", [$user_id]);
                
                $success_msg = "✅ Password berhasil direset untuk {$user['name']}!<br>Password baru: <code>{$new_password}</code>";
                log_activity($admin_user['id'], 'RESET_PASSWORD', "Mereset password user: {$user['name']}");
                
            } catch (Exception $e) {
                $error_msg = "❌ Gagal reset password: " . $e->getMessage();
            }
        }
    }
}

// ======================== AMBIL DATA USER UNTUK EDIT ========================
if ($action === 'edit' && $id > 0 && $db_connected) {
    $user_data = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$id]);
    if (!$user_data) {
        $error_msg = "❌ User tidak ditemukan!";
        $action = '';
    }
}

// ======================== AMBIL LIST USERS ========================
$users = [];
$total_users = 0;
$stats = [
    'total' => 0,
    'active' => 0,
    'inactive' => 0,
    'pending' => 0,
    'by_role' => []
];

if ($db_connected) {
    try {
        // Build query conditions
        $where_conditions = [];
        $params = [];
        
        if ($filter_role !== 'all') {
            $where_conditions[] = "u.role = ?";
            $params[] = $filter_role;
        }
        
        if ($filter_status !== 'all') {
            $where_conditions[] = "u.status = ?";
            $params[] = $filter_status;
        }
        
        if ($search) {
            $where_conditions[] = "(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        // Exclude deleted users
        $where_conditions[] = "u.status != 'deleted'";
        
        $where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";
        
        // Get users with pagination
        $query = "
            SELECT u.*, 
                   COUNT(DISTINCT c.id) as children_count,
                   COUNT(DISTINCT al.id) as activity_count,
                   DATE_FORMAT(u.created_at, '%d/%m/%Y %H:%i') as created_formatted
            FROM users u
            LEFT JOIN children c ON u.id = c.parent_id
            LEFT JOIN activity_logs al ON u.id = al.user_id
            $where_clause
            GROUP BY u.id
            ORDER BY u.created_at DESC
            LIMIT ? OFFSET ?
        ";
        
        $query_params = array_merge($params, [$limit, $offset]);
        $users = $db->fetchAll($query, $query_params);
        
        // Get total count for pagination
        $count_query = "SELECT COUNT(*) as total FROM users u $where_clause";
        $total_result = $db->fetchOne($count_query, $params);
        $total_users = $total_result['total'] ?? 0;
        
        // Get statistics
        $stats_query = "
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                role,
                COUNT(*) as role_count
            FROM users 
            WHERE status != 'deleted'
            GROUP BY role
        ";
        
        $stats_result = $db->fetchAll($stats_query);
        
        foreach ($stats_result as $row) {
            $stats['total'] = $row['total'];
            $stats['active'] = $row['active'];
            $stats['inactive'] = $row['inactive'];
            $stats['pending'] = $row['pending'];
            $stats['by_role'][$row['role']] = $row['role_count'];
        }
        
    } catch (Exception $e) {
        $error_msg = "❌ Error mengambil data user: " . $e->getMessage();
    }
}

// ======================== HELPER FUNCTIONS ========================
function generate_random_password($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

function get_role_badge($role) {
    global $role_options;
    $role_info = $role_options[$role] ?? ['name' => ucfirst($role), 'color' => '#95a5a6', 'icon' => '👤'];
    return '<span class="role-badge" style="background-color:' . $role_info['color'] . '">' 
           . $role_info['icon'] . ' ' . $role_info['name'] . '</span>';
}

function get_status_badge($status) {
    global $status_options;
    $status_info = $status_options[$status] ?? ['name' => ucfirst($status), 'color' => '#95a5a6', 'icon' => '❓'];
    return '<span class="status-badge" style="background-color:' . $status_info['color'] . '">' 
           . $status_info['icon'] . ' ' . $status_info['name'] . '</span>';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - SIRAGA</title>
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
            max-width: 1600px;
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
            top: -100px;
            right: -100px;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(52, 152, 219, 0.1) 0%, transparent 70%);
        }
        
        .header h1 {
            font-size: 34px;
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 10px;
        }
        
        .header-subtitle {
            color: #bdc3c7;
            font-size: 17px;
            margin-bottom: 20px;
        }
        
        .user-info {
            background: rgba(255,255,255,0.1);
            padding: 15px 25px;
            border-radius: 12px;
            display: inline-block;
            backdrop-filter: blur(10px);
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        
        .stat-icon {
            font-size: 36px;
            margin-bottom: 15px;
            display: block;
        }
        
        .stat-number {
            font-size: 38px;
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
        
        /* FILTER SECTION */
        .filter-section {
            background: var(--light);
            padding: 25px;
            border-radius: 15px;
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
            padding: 12px 15px;
            border: 2px solid #dfe6e9;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
            background: white;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.2);
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
        
        .btn-sm {
            padding: 8px 15px;
            font-size: 13px;
            border-radius: 8px;
        }
        
        /* ACTION BUTTONS */
        .action-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }
        
        /* BULK ACTIONS */
        .bulk-actions {
            background: #fff3cd;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            border-left: 5px solid var(--warning);
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .bulk-select {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* TABLE */
        .table-container {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid #e0e0e0;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }
        
        .table th {
            background: linear-gradient(to right, var(--secondary), var(--dark));
            color: white;
            padding: 18px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 15px;
            border: none;
        }
        
        .table th.checkbox-cell {
            width: 40px;
            text-align: center;
        }
        
        .table td {
            padding: 16px 15px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
            vertical-align: middle;
        }
        
        .table tr:hover {
            background: #f8fafc;
        }
        
        .table tr.selected {
            background: #e8f4fc;
        }
        
        /* BADGES */
        .role-badge, .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: white;
        }
        
        /* USER AVATAR */
        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
        }
        
        /* ACTION CELL */
        .action-cell {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        /* PAGINATION */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .pagination a, .pagination span {
            padding: 10px 18px;
            border: 1px solid #ddd;
            border-radius: 8px;
            text-decoration: none;
            color: var(--dark);
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
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
        
        /* MODAL STYLES */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .modal-content {
            background: white;
            border-radius: 15px;
            width: 100%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalSlide 0.3s ease-out;
        }
        
        @keyframes modalSlide {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .modal-header {
            padding: 25px 30px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            font-size: 22px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 5px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .modal-close:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .modal-body {
            padding: 30px;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .password-hint {
            font-size: 13px;
            color: #7f8c8d;
            margin-top: 5px;
        }
        
        /* USER DETAILS */
        .user-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .detail-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid var(--primary);
        }
        
        .detail-label {
            font-size: 13px;
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        
        .detail-value {
            font-weight: 600;
            color: var(--dark);
            font-size: 16px;
        }
        
        /* RESPONSIVE */
        @media (max-width: 1200px) {
            .main-content {
                padding: 30px;
            }
            
            .header {
                padding: 25px 30px;
            }
            
            .header h1 {
                font-size: 28px;
            }
        }
        
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .container {
                border-radius: 15px;
            }
            
            .nav-menu {
                flex-direction: column;
            }
            
            .nav-menu a {
                justify-content: center;
            }
            
            .form-row {
                flex-direction: column;
                gap: 15px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .bulk-actions {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .modal-content {
                max-width: 95%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <h1>
                <i class="fas fa-users-cog"></i>
                Manajemen Pengguna
            </h1>
            <div class="header-subtitle">
                Kelola semua user SIRAGA: tambah, edit, hapus, dan atur hak akses
            </div>
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($admin_user['name']); ?></div>
                <span class="user-role" style="background: #9b59b6;">👑 Administrator</span>
            </div>
        </div>
        
        <div class="main-content">
            <!-- NAV MENU -->
            <div class="nav-menu">
                <a href="/siraga/dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="user_management.php" class="active">
                    <i class="fas fa-users"></i> Users
                </a>
                <a href="database.php">
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
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-number"><?php echo number_format($stats['total']); ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-number"><?php echo number_format($stats['active']); ?></div>
                    <div class="stat-label">Aktif</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">⛔</div>
                    <div class="stat-number"><?php echo number_format($stats['inactive']); ?></div>
                    <div class="stat-label">Nonaktif</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-number"><?php echo number_format($stats['pending']); ?></div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
            
            <!-- FILTER SECTION -->
            <div class="filter-section">
                <h3 class="filter-title">
                    <i class="fas fa-filter"></i> Filter & Cari Users
                </h3>
                
                <form method="GET" class="filter-form">
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" class="form-control">
                            <option value="all" <?php echo $filter_role === 'all' ? 'selected' : ''; ?>>Semua Role</option>
                            <?php foreach ($role_options as $key => $option): ?>
                                <option value="<?php echo $key; ?>" <?php echo $filter_role === $key ? 'selected' : ''; ?>>
                                    <?php echo $option['icon'] . ' ' . $option['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>Semua Status</option>
                            <?php foreach ($status_options as $key => $option): ?>
                                <option value="<?php echo $key; ?>" <?php echo $filter_status === $key ? 'selected' : ''; ?>>
                                    <?php echo $option['icon'] . ' ' . $option['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Cari User</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Nama, email, atau telepon..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Terapkan Filter
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- ACTION BUTTONS -->
            <div class="action-buttons">
                <button class="btn btn-primary" onclick="showAddUserModal()">
                    <i class="fas fa-user-plus"></i> Tambah User Baru
                </button>
                
                <?php if ($action === 'edit' && !empty($user_data)): ?>
                    <a href="?" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Batal Edit
                    </a>
                <?php endif; ?>
                
                <div style="flex-grow: 1;"></div>
                
                <a href="?export=csv" class="btn btn-success">
                    <i class="fas fa-file-export"></i> Export CSV
                </a>
                
                <button class="btn btn-warning" onclick="showBulkActionModal()">
                    <i class="fas fa-tasks"></i> Bulk Actions
                </button>
            </div>
            
            <!-- BULK ACTIONS -->
            <form method="POST" id="bulkForm">
                <input type="hidden" name="action" value="bulk_action">
                
                <div class="bulk-actions">
                    <div class="bulk-select">
                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)">
                        <label for="selectAll" style="font-weight: 600;">Pilih Semua</label>
                    </div>
                    
                    <select name="bulk_action" class="form-control" style="width: 200px;">
                        <option value="">-- Pilih Aksi --</option>
                        <option value="activate">Aktifkan User</option>
                        <option value="deactivate">Nonaktifkan User</option>
                        <option value="delete">Hapus User</option>
                        <option value="send_email">Kirim Email</option>
                    </select>
                    
                    <button type="submit" class="btn btn-warning" onclick="return confirmBulkAction()">
                        <i class="fas fa-play"></i> Jalankan
                    </button>
                    
                    <span style="color: #7f8c8d; font-size: 14px;">
                        <i class="fas fa-info-circle"></i> 
                        <?php echo count($users); ?> user ditampilkan (Total: <?php echo $total_users; ?>)
                    </span>
                </div>
            
            <!-- USER TABLE -->
            <?php if (empty($users)): ?>
                <div style="text-align: center; padding: 60px; color: #7f8c8d;">
                    <i class="fas fa-user-slash" style="font-size: 70px; margin-bottom: 20px; opacity: 0.5;"></i>
                    <h3>Tidak ada user ditemukan</h3>
                    <p><?php echo $search ? 'Coba gunakan kata kunci lain' : 'Gunakan filter yang berbeda atau tambah user baru'; ?></p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="table" id="usersTable">
                        <thead>
                            <tr>
                                <th class="checkbox-cell">
                                    <input type="checkbox" id="selectAllHeader" onchange="toggleSelectAll(this)">
                                </th>
                                <th>User</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Kontak</th>
                                <th>Terdaftar</th>
                                <th>Aktivitas</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr id="user-<?php echo $user['id']; ?>" class="<?php echo $user['id'] == ($user_data['id'] ?? 0) ? 'selected' : ''; ?>">
                                <td style="text-align: center;">
                                    <?php if ($user['id'] != $admin_user['id']): ?>
                                        <input type="checkbox" name="selected_users[]" value="<?php echo $user['id']; ?>" 
                                               class="user-checkbox" onchange="updateSelectAll()">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 15px;">
                                        <div class="user-avatar">
                                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 600; color: var(--dark);">
                                                <?php echo htmlspecialchars($user['name']); ?>
                                                <?php if ($user['id'] == $admin_user['id']): ?>
                                                    <span style="font-size: 12px; color: var(--primary);">(Anda)</span>
                                                <?php endif; ?>
                                            </div>
                                            <div style="font-size: 13px; color: #7f8c8d;">
                                                ID: #<?php echo $user['id']; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php echo get_role_badge($user['role']); ?>
                                </td>
                                <td>
                                    <?php echo get_status_badge($user['status']); ?>
                                </td>
                                <td>
                                    <div style="font-size: 13px;">
                                        <div style="margin-bottom: 3px;">
                                            <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?>
                                        </div>
                                        <?php if ($user['phone']): ?>
                                            <div>
                                                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($user['phone']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size: 13px;">
                                        <?php echo $user['created_formatted']; ?>
                                        <?php if ($user['children_count'] > 0): ?>
                                            <div style="margin-top: 5px;">
                                                <i class="fas fa-child"></i> <?php echo $user['children_count']; ?> anak
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size: 13px;">
                                        <?php if ($user['activity_count'] > 0): ?>
                                            <i class="fas fa-history"></i> <?php echo $user['activity_count']; ?> aktivitas
                                        <?php else: ?>
                                            <span style="color: #95a5a6;">Belum ada aktivitas</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-cell">
                                        <a href="?action=edit&id=<?php echo $user['id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        
                                        <?php if ($user['id'] != $admin_user['id']): ?>
                                            <button class="btn btn-warning btn-sm" 
                                                    onclick="showResetPasswordModal(<?php echo $user['id']; ?>, '<?php echo addslashes($user['name']); ?>')">
                                                <i class="fas fa-key"></i> Reset PW
                                            </button>
                                            
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" 
                                                        onclick="return confirm('Hapus user <?php echo addslashes($user['name']); ?>?')">
                                                    <i class="fas fa-trash"></i> Hapus
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            </form>
            
            <!-- PAGINATION -->
            <?php if ($total_users > $limit): ?>
                <div class="pagination">
                    <?php $total_pages = ceil($total_users / $limit); ?>
                    
                    <?php if ($page > 1): ?>
                        <a href="?page=1&role=<?php echo $filter_role; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search); ?>">
                            &laquo; First
                        </a>
                        <a href="?page=<?php echo $page-1; ?>&role=<?php echo $filter_role; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search); ?>">
                            &lsaquo; Prev
                        </a>
                    <?php endif; ?>
                    
                    <?php 
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    for ($p = $start_page; $p <= $end_page; $p++): 
                    ?>
                        <?php if ($p == $page): ?>
                            <span class="active"><?php echo $p; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $p; ?>&role=<?php echo $filter_role; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search); ?>">
                                <?php echo $p; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page+1; ?>&role=<?php echo $filter_role; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search); ?>">
                            Next &rsaquo;
                        </a>
                        <a href="?page=<?php echo $total_pages; ?>&role=<?php echo $filter_role; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search); ?>">
                            Last &raquo;
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <!-- FOOTER -->
            <div style="text-align: center; margin-top: 50px; padding-top: 30px; border-top: 1px solid #eee;">
                <a href="/siraga/dashboard.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                </a>
                
                <button class="btn btn-secondary" onclick="location.reload()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
    </div>
    
    <!-- ADD/EDIT USER MODAL -->
    <div class="modal" id="userModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-user-plus"></i>
                    <?php echo $action === 'edit' ? 'Edit User' : 'Tambah User Baru'; ?>
                </h3>
                <button class="modal-close" onclick="closeModal('userModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="userForm">
                    <input type="hidden" name="action" value="save_user">
                    <input type="hidden" name="id" value="<?php echo $user_data['id'] ?? ''; ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nama Lengkap *</label>
                            <input type="text" name="name" class="form-control" required
                                   value="<?php echo htmlspecialchars($user_data['name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Email *</label>
                            <input type="email" name="email" class="form-control" required
                                   value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Role *</label>
                            <select name="role" class="form-control" required>
                                <option value="">-- Pilih Role --</option>
                                <?php foreach ($role_options as $key => $option): ?>
                                    <option value="<?php echo $key; ?>" 
                                            <?php echo ($user_data['role'] ?? '') === $key ? 'selected' : ''; ?>
                                            style="color: <?php echo $option['color']; ?>">
                                        <?php echo $option['icon'] . ' ' . $option['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Status *</label>
                            <select name="status" class="form-control" required>
                                <?php foreach ($status_options as $key => $option): ?>
                                    <option value="<?php echo $key; ?>" 
                                            <?php echo ($user_data['status'] ?? 'active') === $key ? 'selected' : ''; ?>
                                            style="color: <?php echo $option['color']; ?>">
                                        <?php echo $option['icon'] . ' ' . $option['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Telepon</label>
                            <input type="tel" name="phone" class="form-control"
                                   value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Alamat</label>
                        <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($user_data['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Catatan</label>
                        <textarea name="notes" class="form-control" rows="2"><?php echo htmlspecialchars($user_data['notes'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Password <?php echo $action === 'edit' ? '(kosongkan jika tidak diubah)' : '*'; ?></label>
                            <input type="password" name="password" class="form-control" 
                                   <?php echo $action === 'edit' ? '' : 'required'; ?>>
                            <div class="password-hint">
                                Minimal 6 karakter
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Konfirmasi Password *</label>
                            <input type="password" name="confirm_password" class="form-control" 
                                   <?php echo $action === 'edit' ? '' : 'required'; ?>>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 15px; margin-top: 30px;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">
                            <i class="fas fa-save"></i> Simpan
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal('userModal')" style="flex: 1;">
                            <i class="fas fa-times"></i> Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- RESET PASSWORD MODAL -->
    <div class="modal" id="resetPasswordModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-key"></i> Reset Password
                </h3>
                <button class="modal-close" onclick="closeModal('resetPasswordModal')">&times;</button>
            </div>
            <div class="modal-body">
                <p id="resetPasswordText">Yakin ingin reset password untuk user ini?</p>
                <form method="POST" id="resetPasswordForm">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="user_id" id="resetUserId">
                    
                    <div style="display: flex; gap: 15px; margin-top: 30px;">
                        <button type="submit" class="btn btn-warning" style="flex: 1;">
                            <i class="fas fa-redo"></i> Reset Password
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal('resetPasswordModal')" style="flex: 1;">
                            <i class="fas fa-times"></i> Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- SCRIPTS -->
    <script>
    // Auto-show modal if in edit mode
    <?php if ($action === 'edit' && !empty($user_data)): ?>
    document.addEventListener('DOMContentLoaded', function() {
        showAddUserModal();
    });
    <?php endif; ?>
    
    // Modal functions
    function showAddUserModal() {
        document.getElementById('userModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function showResetPasswordModal(userId, userName) {
        document.getElementById('resetUserId').value = userId;
        document.getElementById('resetPasswordText').innerHTML = 
            `Reset password untuk <strong>${userName}</strong>?<br><small>Password baru akan digenerate secara acak.</small>`;
        document.getElementById('resetPasswordModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function showBulkActionModal() {
        // Show bulk action confirmation
        const selectedCount = document.querySelectorAll('.user-checkbox:checked').length;
        if (selectedCount === 0) {
            alert('Pilih user terlebih dahulu!');
            return;
        }
        // The bulk form will be submitted with confirmation
    }
    
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    // Close modal on outside click
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    }
    
    // Bulk selection functions
    function toggleSelectAll(source) {
        const checkboxes = document.querySelectorAll('.user-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = source.checked;
            updateRowSelection(checkbox);
        });
        document.getElementById('selectAll').checked = source.checked;
        document.getElementById('selectAllHeader').checked = source.checked;
    }
    
    function updateSelectAll() {
        const checkboxes = document.querySelectorAll('.user-checkbox');
        const selectAllCheckbox = document.getElementById('selectAll');
        const selectAllHeader = document.getElementById('selectAllHeader');
        
        const allChecked = Array.from(checkboxes).every(checkbox => checkbox.checked);
        const anyChecked = Array.from(checkboxes).some(checkbox => checkbox.checked);
        
        selectAllCheckbox.checked = allChecked;
        selectAllCheckbox.indeterminate = anyChecked && !allChecked;
        selectAllHeader.checked = allChecked;
        selectAllHeader.indeterminate = anyChecked && !allChecked;
    }
    
    function updateRowSelection(checkbox) {
        const row = checkbox.closest('tr');
        if (checkbox.checked) {
            row.classList.add('selected');
        } else {
            row.classList.remove('selected');
        }
    }
    
    // Confirm bulk action
    function confirmBulkAction() {
        const selectedCount = document.querySelectorAll('.user-checkbox:checked').length;
        const action = document.querySelector('select[name="bulk_action"]').value;
        
        if (selectedCount === 0) {
            alert('Pilih user terlebih dahulu!');
            return false;
        }
        
        if (!action) {
            alert('Pilih aksi terlebih dahulu!');
            return false;
        }
        
        const actionNames = {
            'activate': 'mengaktifkan',
            'deactivate': 'menonaktifkan',
            'delete': 'menghapus',
            'send_email': 'mengirim email ke'
        };
        
        return confirm(`Yakin ingin ${actionNames[action]} ${selectedCount} user?`);
    }
    
    // Form validation
    document.getElementById('userForm')?.addEventListener('submit', function(e) {
        const password = this.querySelector('input[name="password"]').value;
        const confirmPassword = this.querySelector('input[name="confirm_password"]').value;
        
        if (password && password !== confirmPassword) {
            alert('Password dan konfirmasi password tidak cocok!');
            e.preventDefault();
        }
        
        if (password && password.length < 6) {
            alert('Password minimal 6 karakter!');
            e.preventDefault();
        }
    });
    
    // Auto-hide alerts
    setTimeout(function() {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.opacity = '0';
            setTimeout(() => alert.style.display = 'none', 500);
        });
    }, 8000);
    
    // Table search highlight
    <?php if ($search): ?>
    document.addEventListener('DOMContentLoaded', function() {
        const searchTerm = '<?php echo addslashes($search); ?>';
        const regex = new RegExp(searchTerm, 'gi');
        
        document.querySelectorAll('#usersTable td').forEach(td => {
            const html = td.innerHTML;
            const newHtml = html.replace(regex, match => `<mark style="background: yellow;">${match}</mark>`);
            td.innerHTML = newHtml;
        });
    });
    <?php endif; ?>
    
    // Initialize checkboxes
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.user-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateRowSelection(this);
                updateSelectAll();
            });
        });
    });
    </script>
    
    <!-- FONT AWESOME -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</body>
</html>