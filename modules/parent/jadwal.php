<?php
/**
 * JADWAL - SIRAGA
 * Lokasi: modules/parent/jadwal.php
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
$children = $db->fetchAll("
    SELECT id, full_name as name, birth_date 
    FROM children 
    WHERE parent_id = ? AND status = 'active'
    ORDER BY birth_date DESC
", [$user['id']]);

// ======================== AMBIL JADWAL ========================
$selected_child_id = $_GET['child_id'] ?? ($children[0]['id'] ?? 0);
$upcoming_schedules = [];
$past_schedules = [];
$recommended_vaccines = [];
$today = date('Y-m-d');

if ($selected_child_id > 0) {
    // Jadwal yang akan datang (minggu depan)
    $next_week = date('Y-m-d', strtotime('+7 days'));
    
    // PERBAIKAN: Query untuk upcoming schedules
    $upcoming_schedules = $db->fetchAll("
        SELECT 
            'imunisasi' as type,
            i.vaccine_date as schedule_date,
            vm.name as title,
            CONCAT('Imunisasi: ', vm.name) as description,
            i.status,
            i.notes,
            CASE 
                WHEN i.vaccine_date < ? THEN 'overdue'
                WHEN i.vaccine_date = ? THEN 'today'
                ELSE 'upcoming'
            END as urgency
        FROM immunizations i
        JOIN vaccine_master vm ON i.vaccine_id = vm.id
        WHERE i.child_id = ? AND i.vaccine_date BETWEEN ? AND ?
        
        UNION ALL
        
        SELECT 
            'pemeriksaan' as type,
            e.examination_date as schedule_date,
            'Pemeriksaan Rutin' as title,
            'Pemeriksaan berat & tinggi badan' as description,
            'scheduled' as status,
            e.notes,
            CASE 
                WHEN e.examination_date < ? THEN 'overdue'
                WHEN e.examination_date = ? THEN 'today'
                ELSE 'upcoming'
            END as urgency
        FROM examinations e
        WHERE e.child_id = ? AND e.examination_date BETWEEN ? AND ?
        
        ORDER BY schedule_date ASC
    ", [
        $today, $today, $selected_child_id, $today, $next_week,
        $today, $today, $selected_child_id, $today, $next_week
    ]);
    
    // Jadwal yang sudah lewat (bulan ini)
    $start_of_month = date('Y-m-01');
    
    // PERBAIKAN: Query untuk past schedules
    $past_schedules = $db->fetchAll("
        SELECT 
            'imunisasi' as type,
            i.vaccine_date as schedule_date,
            vm.name as title,
            CONCAT('Imunisasi: ', vm.name) as description,
            i.status,
            i.notes,
            'past' as urgency
        FROM immunizations i
        JOIN vaccine_master vm ON i.vaccine_id = vm.id
        WHERE i.child_id = ? AND i.vaccine_date BETWEEN ? AND ?
        
        UNION ALL
        
        SELECT 
            'pemeriksaan' as type,
            e.examination_date as schedule_date,
            'Pemeriksaan Rutin' as title,
            'Pemeriksaan berat & tinggi badan' as description,
            'completed' as status,
            e.notes,
            'past' as urgency
        FROM examinations e
        WHERE e.child_id = ? AND e.examination_date BETWEEN ? AND ?
        
        ORDER BY schedule_date DESC
    ", [
        $selected_child_id, $start_of_month, $today,
        $selected_child_id, $start_of_month, $today
    ]);
    
    // Jadwal imunisasi berdasarkan usia anak
    $child_info = $db->fetchOne("
        SELECT birth_date, gender 
        FROM children 
        WHERE id = ?
    ", [$selected_child_id]);
    
    if ($child_info) {
        $child_age_months = calculate_age_months($child_info['birth_date']);
        
        // PERBAIKAN: Ambil dari tabel vaccine_master yang sesuai
        $recommended_vaccines = $db->fetchAll("
            SELECT 
                vm.*,
                CASE 
                    WHEN EXISTS (
                        SELECT 1 FROM immunizations i 
                        WHERE i.child_id = ? AND i.vaccine_id = vm.id
                    ) THEN 'done'
                    ELSE 'pending'
                END as status
            FROM vaccine_master vm 
            WHERE vm.is_required = TRUE 
            AND vm.recommended_age_months BETWEEN ? AND ?
            ORDER BY vm.recommended_age_months
        ", [$selected_child_id, $child_age_months, $child_age_months + 3]);
    }
}

// Helper function
function calculate_age_months($birth_date) {
    $birth = new DateTime($birth_date);
    $today = new DateTime();
    $interval = $today->diff($birth);
    return ($interval->y * 12) + $interval->m;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal - SIRAGA</title>
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
        
        /* SELECTOR */
        .child-selector {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            border: 1px solid #e0e0e0;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--secondary);
        }
        
        .form-control {
            padding: 12px 15px;
            border: 2px solid #dfe6e9;
            border-radius: 8px;
            font-size: 15px;
            width: 300px;
            max-width: 100%;
        }
        
        /* SCHEDULE GRID */
        .schedule-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .schedule-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
            transition: transform 0.3s;
        }
        
        .schedule-card:hover {
            transform: translateY(-5px);
        }
        
        .schedule-header {
            padding: 20px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .schedule-header.imunisasi { background: linear-gradient(to right, var(--primary), var(--info)); }
        .schedule-header.pemeriksaan { background: linear-gradient(to right, var(--success), #229954); }
        
        .schedule-type {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .schedule-date {
            font-size: 24px;
            font-weight: bold;
        }
        
        .schedule-day {
            font-size: 14px;
        }
        
        .schedule-body {
            padding: 20px;
        }
        
        .schedule-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--secondary);
            margin-bottom: 10px;
        }
        
        .schedule-desc {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .urgency-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .urgency-today { background: #fff3cd; color: #856404; }
        .urgency-overdue { background: #f8d7da; color: #721c24; }
        .urgency-upcoming { background: #d4edda; color: #155724; }
        
        /* RECOMMENDED VACCINES */
        .recommended-section {
            background: #e8f4fc;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            border-left: 5px solid var(--primary);
        }
        
        .section-title {
            color: var(--secondary);
            margin-bottom: 20px;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .vaccine-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .vaccine-item {
            background: white;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #e0e0e0;
        }
        
        .vaccine-name {
            font-weight: 600;
            color: var(--secondary);
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .vaccine-details {
            font-size: 13px;
            color: #7f8c8d;
        }
        
        .status-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .status-done {
            background: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
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
            
            .schedule-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <h1>
                <i class="fas fa-calendar-check"></i>
                Jadwal
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
                <a href="jadwal.php" class="active">
                    <i class="fas fa-calendar-check"></i> Jadwal
                </a>
                <a href="notifikasi.php">
                    <i class="fas fa-bell"></i> Notifikasi
                </a>
                <a href="/siraga/dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </div>
            
            <!-- CHILD SELECTOR -->
            <div class="child-selector">
                <form method="GET" id="childSelectForm">
                    <div class="form-group">
                        <label>Pilih Anak untuk Melihat Jadwal:</label>
                        <select name="child_id" class="form-control" onchange="document.getElementById('childSelectForm').submit()">
                            <?php if (empty($children)): ?>
                                <option value="">Belum ada data anak</option>
                            <?php else: ?>
                                <?php foreach ($children as $child): ?>
                                    <option value="<?php echo $child['id']; ?>" 
                                            <?php echo $child['id'] == $selected_child_id ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($child['name']); ?> 
                                        (<?php echo date('d/m/Y', strtotime($child['birth_date'])); ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </form>
            </div>
            
            <?php if (empty($children)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📅</div>
                    <h3>Belum ada data anak</h3>
                    <p>Tambahkan data anak terlebih dahulu untuk melihat jadwal.</p>
                    <a href="anak_saya.php" style="display: inline-block; margin-top: 20px; padding: 12px 25px; 
                       background: var(--primary); color: white; text-decoration: none; border-radius: 8px;">
                        <i class="fas fa-user-plus"></i> Tambah Data Anak
                    </a>
                </div>
            <?php elseif ($selected_child_id > 0): ?>
                <!-- RECOMMENDED VACCINES -->
                <?php if (!empty($recommended_vaccines)): ?>
                <div class="recommended-section">
                    <h3 class="section-title">
                        <i class="fas fa-syringe"></i> Rekomendasi Imunisasi (Berdasarkan Usia)
                    </h3>
                    <div class="vaccine-list">
                        <?php foreach ($recommended_vaccines as $vaccine): ?>
                        <div class="vaccine-item">
                            <div class="vaccine-name">
                                <span><?php echo htmlspecialchars($vaccine['name']); ?></span>
                                <span class="status-badge status-<?php echo $vaccine['status']; ?>">
                                    <?php echo $vaccine['status'] === 'done' ? '✓ Selesai' : '⏳ Menunggu'; ?>
                                </span>
                            </div>
                            <div class="vaccine-details">
                                Usia: <?php echo $vaccine['recommended_age_months']; ?> bulan<br>
                                Dosis ke-<?php echo $vaccine['dose_number']; ?><br>
                                <small><?php echo htmlspecialchars($vaccine['description']); ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- UPCOMING SCHEDULES -->
                <h3 class="section-title" style="margin-top: 40px;">
                    <i class="fas fa-clock"></i> Jadwal Mendatang (7 Hari ke Depan)
                </h3>
                
                <?php if (empty($upcoming_schedules)): ?>
                    <div style="text-align: center; padding: 40px; color: #7f8c8d; background: #f8f9fa; border-radius: 12px;">
                        <i class="fas fa-calendar" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                        <p>Tidak ada jadwal dalam 7 hari ke depan</p>
                    </div>
                <?php else: ?>
                    <div class="schedule-grid">
                        <?php foreach ($upcoming_schedules as $schedule): ?>
                        <div class="schedule-card">
                            <div class="schedule-header <?php echo $schedule['type']; ?>">
                                <div>
                                    <div class="schedule-type">
                                        <?php echo $schedule['type'] === 'imunisasi' ? '💉 Imunisasi' : '🩺 Pemeriksaan'; ?>
                                    </div>
                                    <div class="schedule-date">
                                        <?php echo date('d', strtotime($schedule['schedule_date'])); ?>
                                    </div>
                                    <div class="schedule-day">
                                        <?php echo date('D, M Y', strtotime($schedule['schedule_date'])); ?>
                                    </div>
                                </div>
                                <div>
                                    <span class="urgency-badge urgency-<?php echo $schedule['urgency']; ?>">
                                        <?php 
                                        $urgency_text = [
                                            'today' => 'Hari Ini',
                                            'overdue' => 'Terlewat',
                                            'upcoming' => 'Akan Datang'
                                        ];
                                        echo $urgency_text[$schedule['urgency']] ?? $schedule['urgency'];
                                        ?>
                                    </span>
                                </div>
                            </div>
                            <div class="schedule-body">
                                <div class="schedule-title"><?php echo htmlspecialchars($schedule['title']); ?></div>
                                <div class="schedule-desc"><?php echo htmlspecialchars($schedule['description']); ?></div>
                                <?php if ($schedule['notes']): ?>
                                    <div style="font-size: 13px; color: #7f8c8d; margin-top: 10px;">
                                        <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($schedule['notes']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- PAST SCHEDULES -->
                <h3 class="section-title" style="margin-top: 40px;">
                    <i class="fas fa-history"></i> Jadwal Bulan Ini
                </h3>
                
                <?php if (empty($past_schedules)): ?>
                    <div style="text-align: center; padding: 40px; color: #7f8c8d; background: #f8f9fa; border-radius: 12px;">
                        <i class="fas fa-history" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                        <p>Belum ada jadwal di bulan ini</p>
                    </div>
                <?php else: ?>
                    <div class="schedule-grid">
                        <?php foreach ($past_schedules as $schedule): ?>
                        <div class="schedule-card">
                            <div class="schedule-header <?php echo $schedule['type']; ?>">
                                <div>
                                    <div class="schedule-type">
                                        <?php echo $schedule['type'] === 'imunisasi' ? '💉 Imunisasi' : '🩺 Pemeriksaan'; ?>
                                    </div>
                                    <div class="schedule-date">
                                        <?php echo date('d', strtotime($schedule['schedule_date'])); ?>
                                    </div>
                                    <div class="schedule-day">
                                        <?php echo date('D, M Y', strtotime($schedule['schedule_date'])); ?>
                                    </div>
                                </div>
                                <div>
                                    <span class="urgency-badge" style="background: #d1ecf1; color: #0c5460;">
                                        <?php 
                                        if ($schedule['type'] === 'imunisasi') {
                                            echo $schedule['status'] === 'done' ? 'Selesai' : 'Terjadwal';
                                        } else {
                                            echo 'Selesai';
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                            <div class="schedule-body">
                                <div class="schedule-title"><?php echo htmlspecialchars($schedule['title']); ?></div>
                                <div class="schedule-desc"><?php echo htmlspecialchars($schedule['description']); ?></div>
                                <?php if ($schedule['notes']): ?>
                                    <div style="font-size: 13px; color: #7f8c8d; margin-top: 10px;">
                                        <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($schedule['notes']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- FONT AWESOME -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</body>
</html>