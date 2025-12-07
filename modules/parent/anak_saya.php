<?php
/**
 * DATA ANAK SAYA - SIRAGA
 * Lokasi: modules/parent/anak_saya.php
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

// ======================== AMBIL DATA ANAK ========================
try {
    // Query yang DIPERBAIKI - sesuai struktur database
    $children = $db->fetchAll("
        SELECT 
            c.id,
            c.parent_id,
            c.full_name as name,  -- PERUBAHAN: full_name bukan name
            c.birth_date,
            c.gender,
            c.birth_weight,
            c.birth_height,
            c.birth_place,
            c.blood_type,
            c.allergy as allergies,  -- PERUBAHAN: allergy bukan allergies
            c.notes,
            c.status,
            c.created_at,
            c.updated_at,
            TIMESTAMPDIFF(MONTH, c.birth_date, CURDATE()) as age_months,
            c.mother_name,
            c.father_name
        FROM children c
        WHERE c.parent_id = ? AND c.status = 'active'
        ORDER BY c.birth_date DESC
    ", [$user['id']]);
    
    // Ambil data tambahan
    foreach ($children as &$child) {
        // Hitung jumlah pemeriksaan - DIPERBAIKI
        $exam_count = $db->fetchOne("
            SELECT COUNT(*) as count 
            FROM examinations 
            WHERE child_id = ?
        ", [$child['id']]);
        $child['exam_count'] = $exam_count['count'] ?? 0;
        
        // Hitung jumlah imunisasi - DIPERBAIKI
        $immunization_count = $db->fetchOne("
            SELECT COUNT(*) as count 
            FROM immunizations 
            WHERE child_id = ?
        ", [$child['id']]);
        $child['immunization_count'] = $immunization_count['count'] ?? 0;
        
        // Ambil tanggal pemeriksaan terakhir - DIPERBAIKI: gunakan examination_date bukan check_date
        $last_check = $db->fetchOne("
            SELECT MAX(examination_date) as last_date 
            FROM examinations 
            WHERE child_id = ?
        ", [$child['id']]);
        $child['last_check'] = $last_check['last_date'] ?? null;
        
        // Ambil tanggal imunisasi terakhir - DIPERBAIKI: gunakan vaccine_date bukan date_given
        $last_immunization = $db->fetchOne("
            SELECT MAX(vaccine_date) as last_date 
            FROM immunizations 
            WHERE child_id = ?
        ", [$child['id']]);
        $child['last_immunization'] = $last_immunization['last_date'] ?? null;
    }
    unset($child);
    
} catch (Exception $e) {
    die("Error fetching children data: " . $e->getMessage());
}

$total_children = count($children);

// ======================== HANDLE ACTIONS ========================
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_child') {
        try {
            // Validasi data
            $required_fields = ['name', 'birth_date', 'gender', 'birth_weight', 'birth_height'];
            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("Field " . str_replace('_', ' ', $field) . " wajib diisi!");
                }
            }
            
            // Data anak sesuai struktur database - DIPERBAIKI
            $child_data = [
                'parent_id' => $user['id'],
                'nakes_id' => 3, // Default nakes (dr. Violeta) - sesuaikan dengan ID nakes di database
                'full_name' => trim($_POST['name']),  // PERUBAHAN: full_name bukan name
                'birth_date' => $_POST['birth_date'],
                'gender' => $_POST['gender'],
                'birth_weight' => floatval($_POST['birth_weight']),
                'birth_height' => floatval($_POST['birth_height']),
                'birth_place' => trim($_POST['birth_place'] ?? ''),
                'blood_type' => $_POST['blood_type'] ?? '',
                'allergy' => trim($_POST['allergies'] ?? ''),  // PERUBAHAN: allergy bukan allergies
                'notes' => trim($_POST['notes'] ?? ''),
                'mother_name' => $user['name'], // Default isi dengan nama user/orang tua
                'father_name' => '', // Bisa kosong dulu
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $child_id = $db->insert('children', $child_data);
            
            // Tambah pemeriksaan pertama (data lahir) - DIPERBAIKI
            $examination_data = [
                'child_id' => $child_id,
                'nakes_id' => 3, // Default nakes
                'examination_date' => $_POST['birth_date'],  // PERUBAHAN: examination_date bukan check_date
                'weight' => $_POST['birth_weight'],
                'height' => $_POST['birth_height'],
                'age_months' => 0, // Baru lahir
                'notes' => 'Data lahir',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Tambahkan optional fields
            if (!empty($_POST['head_circumference'])) {
                $examination_data['head_circumference'] = floatval($_POST['head_circumference']);
            }
            
            if (!empty($_POST['temperature'])) {
                $examination_data['temperature'] = floatval($_POST['temperature']);
            }
            
            $db->insert('examinations', $examination_data);
            
            $success_msg = "✅ Data anak berhasil ditambahkan!";
            
            // Refresh data
            header("Location: ?success=" . urlencode($success_msg));
            exit;
            
        } catch (Exception $e) {
            $error_msg = "❌ " . $e->getMessage();
        }
    }
    
    elseif ($action === 'edit_child') {
        $child_id = intval($_POST['child_id'] ?? 0);
        
        if ($child_id > 0) {
            try {
                // Data update - DIPERBAIKI
                $child_data = [
                    'full_name' => trim($_POST['name']),  // PERUBAHAN
                    'birth_date' => $_POST['birth_date'],
                    'gender' => $_POST['gender'],
                    'birth_weight' => floatval($_POST['birth_weight']),
                    'birth_height' => floatval($_POST['birth_height']),
                    'birth_place' => trim($_POST['birth_place'] ?? ''),
                    'blood_type' => $_POST['blood_type'] ?? '',
                    'allergy' => trim($_POST['allergies'] ?? ''),  // PERUBAHAN
                    'notes' => trim($_POST['notes'] ?? ''),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                $db->update('children', $child_data, 'id = ? AND parent_id = ?', [$child_id, $user['id']]);
                
                // Update data pemeriksaan pertama jika ada - DIPERBAIKI
                $first_exam = $db->fetchOne("
                    SELECT id 
                    FROM examinations 
                    WHERE child_id = ? AND notes = 'Data lahir' 
                    ORDER BY examination_date ASC 
                    LIMIT 1
                ", [$child_id]);
                
                if ($first_exam) {
                    $exam_data = [
                        'examination_date' => $_POST['birth_date'],  // PERUBAHAN
                        'weight' => floatval($_POST['birth_weight']),
                        'height' => floatval($_POST['birth_height'])
                    ];
                    
                    if (!empty($_POST['head_circumference'])) {
                        $exam_data['head_circumference'] = floatval($_POST['head_circumference']);
                    }
                    
                    if (!empty($_POST['temperature'])) {
                        $exam_data['temperature'] = floatval($_POST['temperature']);
                    }
                    
                    $db->update('examinations', $exam_data, 'id = ?', [$first_exam['id']]);
                }
                
                $success_msg = "✅ Data anak berhasil diperbarui!";
                
            } catch (Exception $e) {
                $error_msg = "❌ " . $e->getMessage();
            }
        }
    }
    
    elseif ($action === 'delete_child') {
        $child_id = intval($_POST['child_id'] ?? 0);
        
        if ($child_id > 0) {
            try {
                // Soft delete - DIPERBAIKI: status sesuai ENUM di database
                $db->update('children', [
                    'status' => 'inactive',  // PERUBAHAN: 'inactive' bukan 'deleted'
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = ? AND parent_id = ?', [$child_id, $user['id']]);
                
                $success_msg = "✅ Data anak berhasil dihapus!";
                
            } catch (Exception $e) {
                $error_msg = "❌ " . $e->getMessage();
            }
        }
    }
}

// ======================== GET DATA FOR EDIT ========================
$edit_child = [];
$edit_examination = [];
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $child_id = intval($_GET['edit']);
    
    // Ambil data anak - DIPERBAIKI
    $edit_child = $db->fetchOne("
        SELECT * FROM children 
        WHERE id = ? AND parent_id = ? AND status = 'active'
    ", [$child_id, $user['id']]);
    
    // Ubah field name untuk konsistensi
    if ($edit_child) {
        $edit_child['name'] = $edit_child['full_name'];
        $edit_child['allergies'] = $edit_child['allergy'];
        
        // Ambil data pemeriksaan pertama (data lahir) - DIPERBAIKI
        $edit_examination = $db->fetchOne("
            SELECT * FROM examinations 
            WHERE child_id = ? 
            ORDER BY examination_date ASC 
            LIMIT 1
        ", [$child_id]);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Anak Saya - SIRAGA</title>
    <style>
        /* CSS TETAP SAMA */
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
            background: linear-gradient(135deg, var(--secondary) 0%, #1a2530 100%);
            color: white;
            padding: 25px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-name {
            font-size: 16px;
            font-weight: 500;
        }
        
        .main-content {
            padding: 30px;
        }
        
        .nav-menu {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .nav-menu a {
            padding: 12px 25px;
            background: #ecf0f1;
            color: var(--secondary);
            text-decoration: none;
            border-radius: 10px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }
        
        .nav-menu a:hover {
            background: #dfe6e9;
            transform: translateY(-2px);
        }
        
        .nav-menu a.active {
            background: var(--primary);
            color: white;
        }
        
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 40px;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 10px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #7f8c8d;
            font-weight: 500;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            font-size: 15px;
            text-decoration: none;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .btn-success {
            background: var(--success);
            color: white;
        }
        
        .btn-warning {
            background: var(--warning);
            color: white;
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn-sm {
            padding: 8px 15px;
            font-size: 13px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: #f8f9fa;
            border-radius: 15px;
            border: 2px dashed #bdc3c7;
        }
        
        .empty-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            font-size: 24px;
            color: var(--secondary);
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: #7f8c8d;
            font-size: 16px;
        }
        
        .children-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 25px;
        }
        
        .child-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
            transition: transform 0.3s;
        }
        
        .child-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        
        .child-header {
            background: linear-gradient(135deg, var(--primary) 0%, #2980b9 100%);
            color: white;
            padding: 20px;
        }
        
        .child-avatar {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        .child-name {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .child-details {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .child-body {
            padding: 20px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .info-item {
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .info-label {
            font-size: 12px;
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 14px;
            font-weight: 500;
            color: var(--secondary);
        }
        
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        /* MODAL STYLES */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .modal-content {
            background: white;
            border-radius: 15px;
            width: 100%;
            max-width: 700px;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideIn 0.3s;
        }
        
        .modal-header {
            padding: 20px 30px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            font-size: 22px;
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 28px;
            color: #7f8c8d;
            cursor: pointer;
            transition: color 0.3s;
        }
        
        .modal-close:hover {
            color: var(--danger);
        }
        
        .modal-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--secondary);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideIn {
            from { 
                opacity: 0;
                transform: translateY(-30px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .children-grid {
                grid-template-columns: 1fr;
            }
            
            .nav-menu {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <h1>
                <i class="fas fa-child"></i>
                Data Anak Saya
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
                <a href="anak_saya.php" class="active">
                    <i class="fas fa-child"></i> Data Anak
                </a>
                <a href="grafik.php">
                    <i class="fas fa-chart-line"></i> Grafik
                </a>
                <a href="jadwal.php">
                    <i class="fas fa-calendar-check"></i> Jadwal
                </a>
                <a href="notifikasi.php">
                    <i class="fas fa-bell"></i> Notifikasi
                </a>
                <a href="/siraga/dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </div>
            
            <!-- ALERTS -->
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>
            
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
            
            <!-- STATISTICS -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_children; ?></div>
                    <div class="stat-label">Jumlah Anak</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number">
                        <?php 
                        $total_exams = array_sum(array_column($children, 'exam_count'));
                        echo $total_exams;
                        ?>
                    </div>
                    <div class="stat-label">Total Pemeriksaan</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number">
                        <?php 
                        $total_immunizations = array_sum(array_column($children, 'immunization_count'));
                        echo $total_immunizations;
                        ?>
                    </div>
                    <div class="stat-label">Total Imunisasi</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number">
                        <?php 
                        $boys = count(array_filter($children, function($child) {
                            return $child['gender'] === 'L';
                        }));
                        $girls = $total_children - $boys;
                        echo "{$boys}L {$girls}P";
                        ?>
                    </div>
                    <div class="stat-label">Anak Laki / Perempuan</div>
                </div>
            </div>
            
            <!-- ACTION BUTTONS -->
            <div class="action-buttons">
                <button class="btn btn-primary" onclick="showAddChildModal()">
                    <i class="fas fa-user-plus"></i> Tambah Data Anak
                </button>
                
                <?php if (!empty($edit_child)): ?>
                    <a href="?" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Batal Edit
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- CHILDREN LIST -->
            <?php if (empty($children)): ?>
                <div class="empty-state">
                    <div class="empty-icon">👶</div>
                    <h3>Belum ada data anak</h3>
                    <p style="margin-bottom: 30px;">Tambahkan data anak Anda untuk memulai monitoring tumbuh kembang.</p>
                    <button class="btn btn-primary" onclick="showAddChildModal()">
                        <i class="fas fa-user-plus"></i> Tambah Anak Pertama
                    </button>
                </div>
            <?php else: ?>
                <div class="children-grid">
                    <?php foreach ($children as $child): ?>
                    <div class="child-card">
                        <div class="child-header">
                            <div class="child-avatar">
                                <?php echo strtoupper(substr($child['name'], 0, 1)); ?>
                            </div>
                            <div class="child-name"><?php echo htmlspecialchars($child['name']); ?></div>
                            <div class="child-details">
                                <?php 
                                $gender_icon = $child['gender'] === 'L' ? '👦' : '👧';
                                $gender_text = $child['gender'] === 'L' ? 'Laki-laki' : 'Perempuan';
                                echo "{$gender_icon} {$gender_text} • {$child['age_months']} bulan";
                                ?>
                            </div>
                        </div>
                        
                        <div class="child-body">
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-label">Tanggal Lahir</div>
                                    <div class="info-value">
                                        <?php echo date('d/m/Y', strtotime($child['birth_date'])); ?>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-label">Berat Lahir</div>
                                    <div class="info-value">
                                        <?php echo number_format($child['birth_weight'], 1); ?> kg
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-label">Tinggi Lahir</div>
                                    <div class="info-value">
                                        <?php echo number_format($child['birth_height'], 1); ?> cm
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-label">Golongan Darah</div>
                                    <div class="info-value">
                                        <?php echo $child['blood_type'] ?: '-'; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($child['mother_name']): ?>
                            <div style="margin-bottom: 15px;">
                                <div style="font-size: 12px; color: #7f8c8d;">Nama Orang Tua:</div>
                                <div style="font-size: 14px;">
                                    👩 <?php echo htmlspecialchars($child['mother_name']); ?>
                                    <?php if ($child['father_name']): ?>
                                        &nbsp;&nbsp;&nbsp;👨 <?php echo htmlspecialchars($child['father_name']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                <div>
                                    <span class="badge badge-info">
                                        <i class="fas fa-stethoscope"></i> 
                                        <?php echo $child['exam_count']; ?> Pemeriksaan
                                    </span>
                                    <span class="badge badge-success" style="margin-left: 10px;">
                                        <i class="fas fa-syringe"></i> 
                                        <?php echo $child['immunization_count']; ?> Imunisasi
                                    </span>
                                </div>
                                
                                <div style="font-size: 12px; color: #7f8c8d;">
                                    <?php if ($child['last_check']): ?>
                                        Terakhir diperiksa: <?php echo date('d/m/Y', strtotime($child['last_check'])); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($child['allergies']): ?>
                                <div style="margin-bottom: 15px;">
                                    <div style="font-size: 12px; color: #7f8c8d;">Alergi:</div>
                                    <div style="font-size: 14px; color: var(--danger);">
                                        <i class="fas fa-exclamation-triangle"></i> 
                                        <?php echo htmlspecialchars($child['allergies']); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <a href="grafik.php?child_id=<?php echo $child['id']; ?>" class="btn btn-success btn-sm">
                                    <i class="fas fa-chart-line"></i> Grafik
                                </a>
                                
                                <a href="jadwal.php?child_id=<?php echo $child['id']; ?>" class="btn btn-warning btn-sm">
                                    <i class="fas fa-calendar"></i> Jadwal
                                </a>
                                
                                <a href="?edit=<?php echo $child['id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_child">
                                    <input type="hidden" name="child_id" value="<?php echo $child['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" 
                                            onclick="return confirm('Hapus data anak <?php echo addslashes($child['name']); ?>?')">
                                        <i class="fas fa-trash"></i> Hapus
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- ADD/EDIT CHILD MODAL -->
    <div class="modal" id="childModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-user-plus"></i>
                    <?php echo !empty($edit_child) ? 'Edit Data Anak' : 'Tambah Data Anak Baru'; ?>
                </h3>
                <button class="modal-close" onclick="closeModal('childModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="childForm">
                    <input type="hidden" name="action" value="<?php echo !empty($edit_child) ? 'edit_child' : 'add_child'; ?>">
                    <input type="hidden" name="child_id" value="<?php echo $edit_child['id'] ?? ''; ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nama Lengkap Anak *</label>
                            <input type="text" name="name" class="form-control" required
                                   value="<?php echo htmlspecialchars($edit_child['name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Tanggal Lahir *</label>
                            <input type="date" name="birth_date" class="form-control" required
                                   value="<?php echo $edit_child['birth_date'] ?? ''; ?>"
                                   max="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Jenis Kelamin *</label>
                            <select name="gender" class="form-control" required>
                                <option value="">-- Pilih --</option>
                                <option value="L" <?php echo ($edit_child['gender'] ?? '') === 'L' ? 'selected' : ''; ?>>👦 Laki-laki</option>
                                <option value="P" <?php echo ($edit_child['gender'] ?? '') === 'P' ? 'selected' : ''; ?>>👧 Perempuan</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Tempat Lahir</label>
                            <input type="text" name="birth_place" class="form-control"
                                   value="<?php echo htmlspecialchars($edit_child['birth_place'] ?? ''); ?>"
                                   placeholder="Rumah Sakit/Klinik">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Berat Lahir (kg) *</label>
                            <input type="number" name="birth_weight" class="form-control" step="0.1" min="0.5" max="10" required
                                   value="<?php echo $edit_child['birth_weight'] ?? ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Tinggi Lahir (cm) *</label>
                            <input type="number" name="birth_height" class="form-control" step="0.1" min="30" max="70" required
                                   value="<?php echo $edit_child['birth_height'] ?? ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Lingkar Kepala Lahir (cm)</label>
                            <input type="number" name="head_circumference" class="form-control" step="0.1" min="20" max="50"
                                   value="<?php echo $edit_examination['head_circumference'] ?? ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Suhu Badan Lahir (°C)</label>
                            <input type="number" name="temperature" class="form-control" step="0.1" min="30" max="45"
                                   value="<?php echo $edit_examination['temperature'] ?? ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Golongan Darah</label>
                            <select name="blood_type" class="form-control">
                                <option value="">-- Tidak Tahu --</option>
                                <option value="A" <?php echo ($edit_child['blood_type'] ?? '') === 'A' ? 'selected' : ''; ?>>A</option>
                                <option value="B" <?php echo ($edit_child['blood_type'] ?? '') === 'B' ? 'selected' : ''; ?>>B</option>
                                <option value="AB" <?php echo ($edit_child['blood_type'] ?? '') === 'AB' ? 'selected' : ''; ?>>AB</option>
                                <option value="O" <?php echo ($edit_child['blood_type'] ?? '') === 'O' ? 'selected' : ''; ?>>O</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Alergi (jika ada)</label>
                            <input type="text" name="allergies" class="form-control"
                                   value="<?php echo htmlspecialchars($edit_child['allergies'] ?? ''); ?>"
                                   placeholder="Contoh: Susu sapi, debu, seafood">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Catatan Tambahan</label>
                        <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($edit_child['notes'] ?? ''); ?></textarea>
                    </div>
                    
                    <div style="display: flex; gap: 15px; margin-top: 30px;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">
                            <i class="fas fa-save"></i> Simpan Data
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal('childModal')" style="flex: 1;">
                            <i class="fas fa-times"></i> Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- SCRIPTS -->
    <script>
    <?php if (!empty($edit_child) || empty($children)): ?>
    document.addEventListener('DOMContentLoaded', function() {
        showAddChildModal();
    });
    <?php endif; ?>
    
    function showAddChildModal() {
        document.getElementById('childModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            closeModal(event.target.id);
        }
    }
    
    document.getElementById('childForm')?.addEventListener('submit', function(e) {
        const birthDate = new Date(this.querySelector('input[name="birth_date"]').value);
        const today = new Date();
        
        if (birthDate > today) {
            alert('Tanggal lahir tidak boleh lebih dari hari ini!');
            e.preventDefault();
        }
        
        const weight = parseFloat(this.querySelector('input[name="birth_weight"]').value);
        if (weight < 0.5 || weight > 10) {
            alert('Berat lahir harus antara 0.5 - 10 kg!');
            e.preventDefault();
        }
        
        const height = parseFloat(this.querySelector('input[name="birth_height"]').value);
        if (height < 30 || height > 70) {
            alert('Tinggi lahir harus antara 30 - 70 cm!');
            e.preventDefault();
        }
    });
    
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