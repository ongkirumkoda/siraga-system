<?php
// modules/nakes/riwayat.php
session_start();

// Cek login dan role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'nakes') {
    header('Location: ../../index.php');
    exit;
}

$user = $_SESSION['user'];
$anak_data = $_SESSION['anak_data'] ?? [];
$pemeriksaan_data = $_SESSION['pemeriksaan'] ?? [];
$imunisasi_data = $_SESSION['imunisasi'] ?? [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pasien - SIRAGA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: #f5f7fa;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
            color: white;
            padding: 25px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .back-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .back-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .content {
            padding: 30px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: #f8fafc;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            border: 1px solid #e0e0e0;
        }
        
        .stat-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .section-title {
            font-size: 20px;
            color: #2c3e50;
            margin: 30px 0 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 0;
        }
        
        .tab-btn {
            background: none;
            border: none;
            padding: 12px 25px;
            cursor: pointer;
            font-size: 15px;
            color: #7f8c8d;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .tab-btn.active {
            color: #9b59b6;
            border-bottom-color: #9b59b6;
            font-weight: 600;
        }
        
        .tab-btn:hover:not(.active) {
            color: #2c3e50;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.5s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .table-container {
            overflow-x: auto;
            margin-top: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #e0e0e0;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            color: #333;
        }
        
        tr:hover {
            background: #f8fafc;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px;
            color: #7f8c8d;
        }
        
        .empty-state i {
            font-size: 60px;
            margin-bottom: 20px;
            color: #bdc3c7;
        }
        
        .btn-export {
            background: #27ae60;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
        }
        
        .btn-export:hover {
            background: #219653;
        }
        
        .search-box {
            margin-bottom: 20px;
        }
        
        .search-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #9b59b6;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .tabs {
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
                <i class="fas fa-history"></i>
                Riwayat Pasien
            </h1>
            <a href="../../dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
            </a>
        </div>
        
        <!-- CONTENT -->
        <div class="content">
            <!-- STATISTICS -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="color: #3498db;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-value"><?php echo count($anak_data); ?></div>
                    <div class="stat-label">Total Pasien</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="color: #27ae60;">
                        <i class="fas fa-notes-medical"></i>
                    </div>
                    <div class="stat-value"><?php echo count($pemeriksaan_data); ?></div>
                    <div class="stat-label">Pemeriksaan</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="color: #f39c12;">
                        <i class="fas fa-syringe"></i>
                    </div>
                    <div class="stat-value"><?php echo count($imunisasi_data); ?></div>
                    <div class="stat-label">Imunisasi</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="color: #9b59b6;">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <div class="stat-value"><?php echo $user['name']; ?></div>
                    <div class="stat-label">Tenaga Kesehatan</div>
                </div>
            </div>
            
            <!-- SEARCH -->
            <div class="search-box">
                <input type="text" class="search-input" id="searchInput" 
                       placeholder="Cari berdasarkan nama anak, tanggal, atau jenis pemeriksaan...">
            </div>
            
            <!-- TABS -->
            <div class="tabs">
                <button class="tab-btn active" onclick="showTab('pasien')">
                    <i class="fas fa-users"></i> Data Pasien
                </button>
                <button class="tab-btn" onclick="showTab('pemeriksaan')">
                    <i class="fas fa-notes-medical"></i> Pemeriksaan
                </button>
                <button class="tab-btn" onclick="showTab('imunisasi')">
                    <i class="fas fa-syringe"></i> Imunisasi
                </button>
                <button class="tab-btn" onclick="showTab('semua')">
                    <i class="fas fa-list-alt"></i> Semua Riwayat
                </button>
            </div>
            
            <!-- TAB 1: DATA PASIEN -->
            <div id="tab-pasien" class="tab-content active">
                <h2 class="section-title">
                    <i class="fas fa-user-injured"></i> Daftar Pasien
                </h2>
                
                <?php if (!empty($anak_data)): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Lengkap</th>
                                <th>Tanggal Lahir</th>
                                <th>Jenis Kelamin</th>
                                <th>Tanggal Daftar</th>
                                <th>Didaftarkan Oleh</th>
                                <th>Total Pemeriksaan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($anak_data as $index => $anak): 
                                // Hitung total pemeriksaan untuk anak ini
                                $total_pemeriksaan = 0;
                                foreach ($pemeriksaan_data as $pemeriksaan) {
                                    if ($pemeriksaan['anak_id'] == $index) {
                                        $total_pemeriksaan++;
                                    }
                                }
                            ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><strong><?php echo htmlspecialchars($anak['nama']); ?></strong></td>
                                <td><?php echo $anak['tanggal_lahir']; ?></td>
                                <td><?php echo $anak['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan'; ?></td>
                                <td><?php echo $anak['tanggal_daftar']; ?></td>
                                <td><?php echo $anak['didaftarkan_oleh']; ?></td>
                                <td><?php echo $total_pemeriksaan; ?> kali</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-user-slash"></i>
                    <h3>Belum ada data pasien</h3>
                    <p>Tambahkan data pasien terlebih dahulu di menu "Input Data Anak".</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- TAB 2: PEMERIKSAAN -->
            <div id="tab-pemeriksaan" class="tab-content">
                <h2 class="section-title">
                    <i class="fas fa-stethoscope"></i> Riwayat Pemeriksaan
                </h2>
                
                <?php if (!empty($pemeriksaan_data)): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Anak</th>
                                <th>Tanggal Periksa</th>
                                <th>Berat (kg)</th>
                                <th>Tinggi (cm)</th>
                                <th>L. Kepala</th>
                                <th>Petugas</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pemeriksaan_data as $index => $data): 
                                $anak_nama = 'Tidak diketahui';
                                if (isset($anak_data[$data['anak_id']])) {
                                    $anak_nama = $anak_data[$data['anak_id']]['nama'];
                                }
                            ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><strong><?php echo htmlspecialchars($anak_nama); ?></strong></td>
                                <td><?php echo $data['tanggal_periksa']; ?></td>
                                <td><?php echo $data['berat_badan']; ?> kg</td>
                                <td><?php echo $data['tinggi_badan']; ?> cm</td>
                                <td><?php echo $data['lingkar_kepala'] ?? '-'; ?> cm</td>
                                <td><?php echo $data['diperiksa_oleh']; ?></td>
                                <td><?php echo $data['catatan'] ?? '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>Belum ada data pemeriksaan</h3>
                    <p>Input data pemeriksaan terlebih dahulu.</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- TAB 3: IMUNISASI -->
            <div id="tab-imunisasi" class="tab-content">
                <h2 class="section-title">
                    <i class="fas fa-shield-virus"></i> Riwayat Imunisasi
                </h2>
                
                <?php if (!empty($imunisasi_data)): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Anak</th>
                                <th>Jenis Imunisasi</th>
                                <th>Tanggal</th>
                                <th>Batch</th>
                                <th>Next Due</th>
                                <th>Petugas</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($imunisasi_data as $index => $data): 
                                $anak_nama = 'Tidak diketahui';
                                if (isset($anak_data[$data['anak_id']])) {
                                    $anak_nama = $anak_data[$data['anak_id']]['nama'];
                                }
                            ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><strong><?php echo htmlspecialchars($anak_nama); ?></strong></td>
                                <td><?php echo $data['jenis_imunisasi']; ?></td>
                                <td><?php echo $data['tanggal_imunisasi']; ?></td>
                                <td><?php echo $data['batch_number'] ?? '-'; ?></td>
                                <td><?php echo $data['next_due_date'] ?? '-'; ?></td>
                                <td><?php echo $data['diberikan_oleh']; ?></td>
                                <td><?php echo $data['keterangan'] ?? '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-syringe"></i>
                    <h3>Belum ada data imunisasi</h3>
                    <p>Catat imunisasi terlebih dahulu.</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- TAB 4: SEMUA RIWAYAT -->
            <div id="tab-semua" class="tab-content">
                <h2 class="section-title">
                    <i class="fas fa-timeline"></i> Timeline Semua Aktivitas
                </h2>
                
                <?php if (!empty($anak_data) || !empty($pemeriksaan_data) || !empty($imunisasi_data)): 
                    // Gabungkan semua aktivitas
                    $all_activities = [];
                    
                    // Tambah data pendaftaran anak
                    foreach ($anak_data as $index => $anak) {
                        $all_activities[] = [
                            'type' => 'pendaftaran',
                            'time' => $anak['tanggal_daftar'],
                            'title' => 'Pendaftaran Pasien Baru',
                            'description' => 'Anak: ' . $anak['nama'] . ' (' . $anak['jenis_kelamin'] . ')',
                            'by' => $anak['didaftarkan_oleh']
                        ];
                    }
                    
                    // Tambah data pemeriksaan
                    foreach ($pemeriksaan_data as $data) {
                        $anak_nama = 'Tidak diketahui';
                        if (isset($anak_data[$data['anak_id']])) {
                            $anak_nama = $anak_data[$data['anak_id']]['nama'];
                        }
                        
                        $all_activities[] = [
                            'type' => 'pemeriksaan',
                            'time' => $data['waktu_input'],
                            'title' => 'Pemeriksaan Rutin',
                            'description' => 'Anak: ' . $anak_nama . ' | BB: ' . $data['berat_badan'] . 'kg, TB: ' . $data['tinggi_badan'] . 'cm',
                            'by' => $data['diperiksa_oleh']
                        ];
                    }
                    
                    // Tambah data imunisasi
                    foreach ($imunisasi_data as $data) {
                        $anak_nama = 'Tidak diketahui';
                        if (isset($anak_data[$data['anak_id']])) {
                            $anak_nama = $anak_data[$data['anak_id']]['nama'];
                        }
                        
                        $all_activities[] = [
                            'type' => 'imunisasi',
                            'time' => $data['waktu_input'],
                            'title' => 'Imunisasi: ' . $data['jenis_imunisasi'],
                            'description' => 'Anak: ' . $anak_nama,
                            'by' => $data['diberikan_oleh']
                        ];
                    }
                    
                    // Urutkan berdasarkan waktu (terbaru pertama)
                    usort($all_activities, function($a, $b) {
                        return strtotime($b['time']) - strtotime($a['time']);
                    });
                ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Aktivitas</th>
                                <th>Deskripsi</th>
                                <th>Petugas</th>
                                <th>Jenis</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_activities as $activity): 
                                $icon = '';
                                $color = '';
                                
                                switch($activity['type']) {
                                    case 'pendaftaran':
                                        $icon = 'fas fa-user-plus';
                                        $color = '#3498db';
                                        break;
                                    case 'pemeriksaan':
                                        $icon = 'fas fa-stethoscope';
                                        $color = '#27ae60';
                                        break;
                                    case 'imunisasi':
                                        $icon = 'fas fa-syringe';
                                        $color = '#f39c12';
                                        break;
                                }
                            ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($activity['time'])); ?></td>
                                <td>
                                    <i class="<?php echo $icon; ?>" style="color: <?php echo $color; ?>; margin-right: 8px;"></i>
                                    <?php echo $activity['title']; ?>
                                </td>
                                <td><?php echo $activity['description']; ?></td>
                                <td><?php echo $activity['by']; ?></td>
                                <td>
                                    <span style="
                                        background: <?php echo $color; ?>20;
                                        color: <?php echo $color; ?>;
                                        padding: 3px 10px;
                                        border-radius: 15px;
                                        font-size: 12px;
                                        font-weight: 500;
                                    ">
                                        <?php echo ucfirst($activity['type']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-timeline"></i>
                    <h3>Belum ada aktivitas</h3>
                    <p>Mulai dengan mendaftarkan pasien baru.</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- EXPORT BUTTON -->
            <button class="btn-export" onclick="exportData()">
                <i class="fas fa-download"></i> Export Data (PDF)
            </button>
        </div>
    </div>
    
    <script>
        // Tab functionality
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById('tab-' + tabName).classList.add('active');
            
            // Activate selected button
            event.target.classList.add('active');
        }
        
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const activeTab = document.querySelector('.tab-content.active');
            
            if (activeTab) {
                const rows = activeTab.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            }
        });
        
        // Export data (placeholder)
        function exportData() {
            alert('Fitur export PDF akan tersedia dalam versi berikutnya.\n\nUntuk sementara, data disimpan di session browser Anda.');
        }
        
        // Auto refresh setiap 30 detik
        setInterval(() => {
            console.log('Auto-refresh riwayat...');
        }, 30000);
    </script>
</body>
</html>