<?php
// modules/nakes/pemeriksaan.php
session_start();

// Cek login dan role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'nakes') {
    header('Location: ../../index.php');
    exit;
}

$user = $_SESSION['user'];

// Ambil data anak dari session (sementara)
$anak_data = $_SESSION['anak_data'] ?? [];

// Proses form jika ada POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $anak_id = $_POST['anak_id'] ?? '';
    $tanggal_periksa = $_POST['tanggal_periksa'] ?? '';
    $berat_badan = $_POST['berat_badan'] ?? '';
    $tinggi_badan = $_POST['tinggi_badan'] ?? '';
    $lingkar_kepala = $_POST['lingkar_kepala'] ?? '';
    $catatan = $_POST['catatan'] ?? '';
    
    // Simpan ke session (sementara)
    $_SESSION['pemeriksaan'][] = [
        'anak_id' => $anak_id,
        'tanggal_periksa' => $tanggal_periksa,
        'berat_badan' => $berat_badan,
        'tinggi_badan' => $tinggi_badan,
        'lingkar_kepala' => $lingkar_kepala,
        'catatan' => $catatan,
        'diperiksa_oleh' => $user['name'],
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
    <title>Input Pemeriksaan - SIRAGA</title>
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
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
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
            border-color: #3498db;
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
                <i class="fas fa-notes-medical"></i>
                Input Hasil Pemeriksaan Anak
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
                Data pemeriksaan berhasil disimpan!
            </div>
            <?php endif; ?>
            
            <!-- FORM INPUT -->
            <div class="form-section">
                <h2 class="section-title">
                    <i class="fas fa-edit"></i> Form Pemeriksaan
                </h2>
                
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Pilih Anak</label>
                            <select name="anak_id" required>
                                <option value="">Pilih anak yang diperiksa</option>
                                <?php foreach ($anak_data as $index => $anak): ?>
                                <option value="<?php echo $index; ?>">
                                    <?php echo htmlspecialchars($anak['nama']); ?> 
                                    (<?php echo $anak['jenis_kelamin']; ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Tanggal Pemeriksaan</label>
                            <input type="date" name="tanggal_periksa" required 
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Berat Badan (kg)</label>
                            <input type="number" name="berat_badan" step="0.01" 
                                   placeholder="Contoh: 8.5" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Tinggi Badan (cm)</label>
                            <input type="number" name="tinggi_badan" step="0.1" 
                                   placeholder="Contoh: 72.5" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Lingkar Kepala (cm)</label>
                            <input type="number" name="lingkar_kepala" step="0.1" 
                                   placeholder="Contoh: 45.2">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Catatan / Observasi</label>
                        <textarea name="catatan" rows="3" 
                                  placeholder="Masukkan catatan pemeriksaan..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> Simpan Hasil Pemeriksaan
                    </button>
                </form>
            </div>
            
            <!-- DATA YANG SUDAH DIINPUT -->
            <div class="data-section">
                <h2 class="section-title">
                    <i class="fas fa-history"></i> Riwayat Pemeriksaan
                </h2>
                
                <?php if (!empty($_SESSION['pemeriksaan'])): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Anak</th>
                                <th>Tanggal</th>
                                <th>Berat (kg)</th>
                                <th>Tinggi (cm)</th>
                                <th>L. Kepala</th>
                                <th>Petugas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($_SESSION['pemeriksaan'] as $index => $data): 
                                // Dapatkan nama anak dari ID
                                $anak_nama = 'Tidak diketahui';
                                if (isset($anak_data[$data['anak_id']])) {
                                    $anak_nama = $anak_data[$data['anak_id']]['nama'];
                                }
                            ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($anak_nama); ?></td>
                                <td><?php echo $data['tanggal_periksa']; ?></td>
                                <td><?php echo $data['berat_badan']; ?> kg</td>
                                <td><?php echo $data['tinggi_badan']; ?> cm</td>
                                <td><?php echo $data['lingkar_kepala'] ?? '-'; ?> cm</td>
                                <td><?php echo $data['diperiksa_oleh']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>Belum ada data pemeriksaan</h3>
                    <p>Input data pemeriksaan pertama Anda menggunakan form di atas.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-calculate jika ada
        document.addEventListener('DOMContentLoaded', function() {
            // Format tanggal hari ini
            const today = new Date().toISOString().split('T')[0];
            document.querySelector('input[name="tanggal_periksa"]').value = today;
            
            // Focus ke field pertama
            document.querySelector('select[name="anak_id"]').focus();
        });
    </script>
</body>
</html>