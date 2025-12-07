<?php
// modules/nakes/imunisasi.php
session_start();

// Cek login dan role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'nakes') {
    header('Location: ../../index.php');
    exit;
}

$user = $_SESSION['user'];
$anak_data = $_SESSION['anak_data'] ?? [];

// Daftar imunisasi wajib
$imunisasi_wajib = [
    'BCG' => 'Tuberkulosis',
    'HB-0' => 'Hepatitis B (dosis 0)',
    'DPT-HB-Hib-1' => 'Difteri, Pertusis, Tetanus, Hepatitis B, Hib (dosis 1)',
    'Polio-1' => 'Polio (dosis 1)',
    'DPT-HB-Hib-2' => 'Difteri, Pertusis, Tetanus, Hepatitis B, Hib (dosis 2)',
    'Polio-2' => 'Polio (dosis 2)',
    'DPT-HB-Hib-3' => 'Difteri, Pertusis, Tetanus, Hepatitis B, Hib (dosis 3)',
    'Polio-3' => 'Polio (dosis 3)',
    'Polio-4' => 'Polio (dosis 4)',
    'Campak' => 'Campak'
];

// Proses form jika ada POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $anak_id = $_POST['anak_id'] ?? '';
    $jenis_imunisasi = $_POST['jenis_imunisasi'] ?? '';
    $tanggal_imunisasi = $_POST['tanggal_imunisasi'] ?? '';
    $batch_number = $_POST['batch_number'] ?? '';
    $next_due_date = $_POST['next_due_date'] ?? '';
    $keterangan = $_POST['keterangan'] ?? '';
    
    // Simpan ke session
    $_SESSION['imunisasi'][] = [
        'anak_id' => $anak_id,
        'jenis_imunisasi' => $jenis_imunisasi,
        'tanggal_imunisasi' => $tanggal_imunisasi,
        'batch_number' => $batch_number,
        'next_due_date' => $next_due_date,
        'keterangan' => $keterangan,
        'diberikan_oleh' => $user['name'],
        'waktu_input' => date('Y-m-d H:i:s')
    ];
    
    $success = true;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catat Imunisasi - SIRAGA</title>
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
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
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
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            border: 1px solid #c3e6cb;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-section {
            background: #f8fafc;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 18px;
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2c3e50;
            font-size: 14px;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #f39c12;
        }
        
        .btn-submit {
            background: #27ae60;
            color: white;
            border: none;
            padding: 14px 30px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 16px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: background 0.3s;
        }
        
        .btn-submit:hover {
            background: #219653;
        }
        
        .btn-warning {
            background: #f39c12;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-warning:hover {
            background: #e67e22;
        }
        
        .data-section {
            margin-top: 40px;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
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
        
        .status-complete {
            background: #d4edda;
            color: #155724;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }
        
        .empty-state i {
            font-size: 50px;
            margin-bottom: 15px;
            color: #bdc3c7;
        }
        
        .info-box {
            background: #e8f4fc;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .form-grid {
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
                <i class="fas fa-syringe"></i>
                Catat Imunisasi Anak
            </h1>
            <a href="../../dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
            </a>
        </div>
        
        <!-- CONTENT -->
        <div class="content">
            <?php if (isset($success)): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                Data imunisasi berhasil disimpan!
            </div>
            <?php endif; ?>
            
            <!-- INFO BOX -->
            <div class="info-box">
                <i class="fas fa-info-circle"></i> 
                <strong>Imunisasi Wajib:</strong> BCG, Hepatitis B, DPT-HB-Hib, Polio, Campak
            </div>
            
            <!-- FORM INPUT -->
            <div class="form-section">
                <h2 class="section-title">
                    <i class="fas fa-edit"></i> Form Pencatatan Imunisasi
                </h2>
                
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Pilih Anak</label>
                            <select name="anak_id" required>
                                <option value="">Pilih anak yang diimunisasi</option>
                                <?php foreach ($anak_data as $index => $anak): ?>
                                <option value="<?php echo $index; ?>">
                                    <?php echo htmlspecialchars($anak['nama']); ?> 
                                    (<?php echo $anak['jenis_kelamin']; ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Jenis Imunisasi</label>
                            <select name="jenis_imunisasi" required>
                                <option value="">Pilih jenis imunisasi</option>
                                <?php foreach ($imunisasi_wajib as $kode => $nama): ?>
                                <option value="<?php echo $kode; ?>">
                                    <?php echo $kode; ?> - <?php echo $nama; ?>
                                </option>
                                <?php endforeach; ?>
                                <option value="lainnya">Lainnya</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Tanggal Imunisasi</label>
                            <input type="date" name="tanggal_imunisasi" required 
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Nomor Batch Vaksin</label>
                            <input type="text" name="batch_number" 
                                   placeholder="Contoh: BATCH-2025-001">
                        </div>
                        
                        <div class="form-group">
                            <label>Tanggal Imunisasi Berikutnya</label>
                            <input type="date" name="next_due_date" 
                                   placeholder="Untuk imunisasi berulang">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Keterangan / Efek Samping</label>
                        <textarea name="keterangan" rows="3" 
                                  placeholder="Catatan tambahan, efek samping, dll..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-syringe"></i> Simpan Data Imunisasi
                    </button>
                </form>
            </div>
            
            <!-- DATA YANG SUDAH DIINPUT -->
            <div class="data-section">
                <h2 class="section-title">
                    <i class="fas fa-history"></i> Riwayat Imunisasi
                </h2>
                
                <?php if (!empty($_SESSION['imunisasi'])): ?>
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
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($_SESSION['imunisasi'] as $index => $data): 
                                $anak_nama = 'Tidak diketahui';
                                if (isset($anak_data[$data['anak_id']])) {
                                    $anak_nama = $anak_data[$data['anak_id']]['nama'];
                                }
                                
                                // Tentukan status
                                $today = date('Y-m-d');
                                $status_class = 'status-complete';
                                $status_text = 'Selesai';
                                
                                if (!empty($data['next_due_date']) && $data['next_due_date'] > $today) {
                                    $status_class = 'status-pending';
                                    $status_text = 'Menunggu dosis berikutnya';
                                }
                            ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($anak_nama); ?></td>
                                <td><?php echo $data['jenis_imunisasi']; ?></td>
                                <td><?php echo $data['tanggal_imunisasi']; ?></td>
                                <td><?php echo $data['batch_number'] ?? '-'; ?></td>
                                <td><?php echo $data['next_due_date'] ?? '-'; ?></td>
                                <td><?php echo $data['diberikan_oleh']; ?></td>
                                <td><span class="<?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-syringe"></i>
                    <h3>Belum ada data imunisasi</h3>
                    <p>Catat imunisasi pertama Anda menggunakan form di atas.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Set tanggal hari ini
            const today = new Date().toISOString().split('T')[0];
            document.querySelector('input[name="tanggal_imunisasi"]').value = today;
            
            // Auto-set next due date (default + 1 bulan)
            const nextMonth = new Date();
            nextMonth.setMonth(nextMonth.getMonth() + 1);
            const nextMonthStr = nextMonth.toISOString().split('T')[0];
            document.querySelector('input[name="next_due_date"]').value = nextMonthStr;
        });
    </script>
</body>
</html>