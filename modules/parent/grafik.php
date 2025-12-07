<?php
/**
 * GRAFIK PERTUMBUHAN - SIRAGA
 * Lokasi: modules/parent/grafik.php
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

// ======================== AMBIL DATA GRAFIK ========================
$selected_child_id = $_GET['child_id'] ?? ($children[0]['id'] ?? 0);
$growth_data = [];
$child_info = [];

if ($selected_child_id > 0) {
    // Ambil info anak
    $child_info = $db->fetchOne("
        SELECT full_name, birth_date, gender, birth_weight, birth_height
        FROM children 
        WHERE id = ?
    ", [$selected_child_id]);
    
    // Ambil data pemeriksaan untuk grafik
    $growth_data = $db->fetchAll("
        SELECT 
            examination_date as date,
            weight,
            height,
            head_circumference,
            temperature,
            age_months,
            nutrition_status,
            notes
        FROM examinations 
        WHERE child_id = ?
        ORDER BY examination_date ASC
    ", [$selected_child_id]);
    
    // Ambil data WHO growth standards untuk perbandingan
    $gender = $child_info['gender'];
    $age_months_list = [];
    foreach ($growth_data as $data) {
        $age_months_list[] = $data['age_months'];
    }
    
    if (!empty($age_months_list)) {
        $placeholders = implode(',', array_fill(0, count($age_months_list), '?'));
        $who_standards = $db->fetchAll("
            SELECT age_months, weight_median, height_median
            FROM growth_standards 
            WHERE gender = ? AND age_months IN ($placeholders)
            ORDER BY age_months
        ", array_merge([$gender], $age_months_list));
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grafik Pertumbuhan - SIRAGA</title>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        /* CHILD INFO */
        .child-info-card {
            background: linear-gradient(to right, #e8f4fc, #d4edf7);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            border-left: 5px solid var(--primary);
        }
        
        .child-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }
        
        .info-item {
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }
        
        .info-label {
            font-size: 12px;
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 18px;
            font-weight: 600;
            color: var(--secondary);
        }
        
        /* CHARTS CONTAINER */
        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(600px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .chart-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
        }
        
        .chart-title {
            color: var(--secondary);
            margin-bottom: 20px;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .chart-wrapper {
            position: relative;
            height: 400px;
            width: 100%;
        }
        
        /* GROWTH TABLE */
        .growth-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
            margin-top: 40px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: var(--primary);
            color: white;
        }
        
        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        /* NUTRITION STATUS BADGES */
        .status-badge {
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-normal { background: #d4edda; color: #155724; }
        .status-underweight { background: #fff3cd; color: #856404; }
        .status-stunting { background: #f8d7da; color: #721c24; }
        .status-wasting { background: #f8d7da; color: #721c24; }
        .status-overweight { background: #cce5ff; color: #004085; }
        
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
            
            .charts-container {
                grid-template-columns: 1fr;
            }
            
            .chart-card {
                padding: 15px;
            }
            
            .chart-wrapper {
                height: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <h1>
                <i class="fas fa-chart-line"></i>
                Grafik Pertumbuhan
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
                <a href="grafik.php" class="active">
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
            
            <!-- CHILD SELECTOR -->
            <div class="child-selector">
                <form method="GET" id="childSelectForm">
                    <div class="form-group">
                        <label>Pilih Anak untuk Melihat Grafik:</label>
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
                    <div class="empty-icon">📊</div>
                    <h3>Belum ada data anak</h3>
                    <p>Tambahkan data anak terlebih dahulu untuk melihat grafik pertumbuhan.</p>
                    <a href="anak_saya.php" style="display: inline-block; margin-top: 20px; padding: 12px 25px; 
                       background: var(--primary); color: white; text-decoration: none; border-radius: 8px;">
                        <i class="fas fa-user-plus"></i> Tambah Data Anak
                    </a>
                </div>
            <?php elseif ($selected_child_id > 0): ?>
                <!-- CHILD INFO -->
                <div class="child-info-card">
                    <h3 style="color: var(--secondary); margin-bottom: 15px;">
                        <i class="fas fa-child"></i> <?php echo htmlspecialchars($child_info['full_name']); ?>
                    </h3>
                    <div class="child-info-grid">
                        <div class="info-item">
                            <div class="info-label">Tanggal Lahir</div>
                            <div class="info-value"><?php echo date('d/m/Y', strtotime($child_info['birth_date'])); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Jenis Kelamin</div>
                            <div class="info-value"><?php echo $child_info['gender'] === 'L' ? 'Laki-laki' : 'Perempuan'; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Berat Lahir</div>
                            <div class="info-value"><?php echo number_format($child_info['birth_weight'], 1); ?> kg</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Tinggi Lahir</div>
                            <div class="info-value"><?php echo number_format($child_info['birth_height'], 1); ?> cm</div>
                        </div>
                    </div>
                </div>
                
                <?php if (empty($growth_data)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📈</div>
                        <h3>Belum ada data pemeriksaan</h3>
                        <p>Belum ada data pemeriksaan untuk menampilkan grafik pertumbuhan.</p>
                        <p style="font-size: 14px; color: #7f8c8d; margin-top: 10px;">
                            Data pemeriksaan akan ditambahkan oleh tenaga kesehatan.
                        </p>
                    </div>
                <?php else: ?>
                    <!-- CHARTS -->
                    <div class="charts-container">
                        <!-- WEIGHT CHART -->
                        <div class="chart-card">
                            <h3 class="chart-title">
                                <i class="fas fa-weight"></i> Grafik Berat Badan
                            </h3>
                            <div class="chart-wrapper">
                                <canvas id="weightChart"></canvas>
                            </div>
                        </div>
                        
                        <!-- HEIGHT CHART -->
                        <div class="chart-card">
                            <h3 class="chart-title">
                                <i class="fas fa-ruler-vertical"></i> Grafik Tinggi Badan
                            </h3>
                            <div class="chart-wrapper">
                                <canvas id="heightChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- GROWTH TABLE -->
                    <div class="growth-table">
                        <h3 style="padding: 20px; color: var(--secondary); border-bottom: 1px solid #eee;">
                            <i class="fas fa-table"></i> Data Pemeriksaan
                        </h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Usia (bln)</th>
                                    <th>Berat (kg)</th>
                                    <th>Tinggi (cm)</th>
                                    <th>Lingkar Kepala (cm)</th>
                                    <th>Status Gizi</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($growth_data as $data): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($data['date'])); ?></td>
                                    <td><?php echo $data['age_months']; ?></td>
                                    <td><?php echo number_format($data['weight'], 1); ?></td>
                                    <td><?php echo number_format($data['height'], 1); ?></td>
                                    <td><?php echo $data['head_circumference'] ? number_format($data['head_circumference'], 1) : '-'; ?></td>
                                    <td>
                                        <?php if ($data['nutrition_status']): ?>
                                            <?php 
                                            $status_map = [
                                                'normal' => 'Normal',
                                                'underweight' => 'Kurang',
                                                'severely_underweight' => 'Sangat Kurang',
                                                'stunting' => 'Pendek',
                                                'severely_stunting' => 'Sangat Pendek',
                                                'wasting' => 'Kurus',
                                                'severely_wasting' => 'Sangat Kurus',
                                                'overweight' => 'Lebih'
                                            ];
                                            $status_class = strtolower(explode('_', $data['nutrition_status'])[0]);
                                            ?>
                                            <span class="status-badge status-<?php echo $status_class; ?>">
                                                <?php echo $status_map[$data['nutrition_status']] ?? $data['nutrition_status']; ?>
                                            </span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size: 12px; color: #7f8c8d;">
                                        <?php echo $data['notes'] ? htmlspecialchars($data['notes']) : '-'; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- CHART SCRIPT -->
                    <script>
                    // Prepare data for charts
                    const dates = <?php echo json_encode(array_map(function($d) { 
                        return date('d/m/Y', strtotime($d['date'])); 
                    }, $growth_data)); ?>;
                    
                    const weights = <?php echo json_encode(array_column($growth_data, 'weight')); ?>;
                    const heights = <?php echo json_encode(array_column($growth_data, 'height')); ?>;
                    const ages = <?php echo json_encode(array_column($growth_data, 'age_months')); ?>;
                    
                    <?php if (!empty($who_standards)): ?>
                    const whoAges = <?php echo json_encode(array_column($who_standards, 'age_months')); ?>;
                    const whoWeightMedian = <?php echo json_encode(array_column($who_standards, 'weight_median')); ?>;
                    const whoHeightMedian = <?php echo json_encode(array_column($who_standards, 'height_median')); ?>;
                    <?php endif; ?>
                    
                    // WEIGHT CHART
                    const weightCtx = document.getElementById('weightChart').getContext('2d');
                    const weightChart = new Chart(weightCtx, {
                        type: 'line',
                        data: {
                            labels: dates,
                            datasets: [
                                {
                                    label: 'Berat Badan (kg)',
                                    data: weights,
                                    borderColor: '#3498db',
                                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                                    borderWidth: 3,
                                    fill: true,
                                    tension: 0.4
                                }
                                <?php if (!empty($who_standards)): ?>
                                ,{
                                    label: 'Standar WHO (Median)',
                                    data: whoWeightMedian,
                                    borderColor: '#95a5a6',
                                    borderWidth: 2,
                                    borderDash: [5, 5],
                                    fill: false,
                                    tension: 0.4
                                }
                                <?php endif; ?>
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top',
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return `${context.dataset.label}: ${context.parsed.y} kg`;
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    title: {
                                        display: true,
                                        text: 'Berat (kg)'
                                    },
                                    grid: {
                                        color: 'rgba(0,0,0,0.05)'
                                    }
                                },
                                x: {
                                    title: {
                                        display: true,
                                        text: 'Tanggal Pemeriksaan'
                                    },
                                    grid: {
                                        color: 'rgba(0,0,0,0.05)'
                                    }
                                }
                            }
                        }
                    });
                    
                    // HEIGHT CHART
                    const heightCtx = document.getElementById('heightChart').getContext('2d');
                    const heightChart = new Chart(heightCtx, {
                        type: 'line',
                        data: {
                            labels: dates,
                            datasets: [
                                {
                                    label: 'Tinggi Badan (cm)',
                                    data: heights,
                                    borderColor: '#27ae60',
                                    backgroundColor: 'rgba(39, 174, 96, 0.1)',
                                    borderWidth: 3,
                                    fill: true,
                                    tension: 0.4
                                }
                                <?php if (!empty($who_standards)): ?>
                                ,{
                                    label: 'Standar WHO (Median)',
                                    data: whoHeightMedian,
                                    borderColor: '#95a5a6',
                                    borderWidth: 2,
                                    borderDash: [5, 5],
                                    fill: false,
                                    tension: 0.4
                                }
                                <?php endif; ?>
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top',
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return `${context.dataset.label}: ${context.parsed.y} cm`;
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    title: {
                                        display: true,
                                        text: 'Tinggi (cm)'
                                    },
                                    grid: {
                                        color: 'rgba(0,0,0,0.05)'
                                    }
                                },
                                x: {
                                    title: {
                                        display: true,
                                        text: 'Tanggal Pemeriksaan'
                                    },
                                    grid: {
                                        color: 'rgba(0,0,0,0.05)'
                                    }
                                }
                            }
                        }
                    });
                    
                    // Update charts on window resize
                    window.addEventListener('resize', function() {
                        weightChart.resize();
                        heightChart.resize();
                    });
                    </script>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- FONT AWESOME -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</body>
</html>