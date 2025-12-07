<?php
// modules/nakes/anak_baru.php - CONTOH MODUL YANG LANGSUNG BEKERJA
session_start();

// Cek login dan role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'nakes') {
    header('Location: ../../index.php');
    exit;
}

$user = $_SESSION['user'];

// Proses form jika ada POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_anak = $_POST['nama_anak'] ?? '';
    $tanggal_lahir = $_POST['tanggal_lahir'] ?? '';
    $jenis_kelamin = $_POST['jenis_kelamin'] ?? '';
    
    // Simpan ke session (sementara, nanti ke database)
    $_SESSION['anak_data'][] = [
        'nama' => $nama_anak,
        'tanggal_lahir' => $tanggal_lahir,
        'jenis_kelamin' => $jenis_kelamin,
        'didaftarkan_oleh' => $user['name'],
        'tanggal_daftar' => date('Y-m-d H:i:s')
    ];
    
    $success = true;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Input Data Anak - SIRAGA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Arial', sans-serif; }
        body { background: #f5f7fa; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        h1 { color: #27ae60; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .btn { background: #27ae60; color: white; border: none; padding: 12px 25px; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #219653; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .back-link { display: inline-block; margin-top: 20px; color: #3498db; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <a href="../../dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>
        
        <h1><i class="fas fa-user-plus"></i> Input Data Anak Baru</h1>
        
        <?php if (isset($success)): ?>
        <div class="success">
            <i class="fas fa-check-circle"></i> Data anak berhasil disimpan!
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Nama Lengkap Anak</label>
                <input type="text" name="nama_anak" required placeholder="Contoh: Ahmad Budi">
            </div>
            
            <div class="form-group">
                <label>Tanggal Lahir</label>
                <input type="date" name="tanggal_lahir" required>
            </div>
            
            <div class="form-group">
                <label>Jenis Kelamin</label>
                <select name="jenis_kelamin" required>
                    <option value="">Pilih</option>
                    <option value="L">Laki-laki</option>
                    <option value="P">Perempuan</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Nama Orang Tua</label>
                <input type="text" name="nama_ortu" placeholder="Nama ayah/ibu">
            </div>
            
            <div class="form-group">
                <label>Alamat</label>
                <textarea name="alamat" rows="3" placeholder="Alamat lengkap"></textarea>
            </div>
            
            <button type="submit" class="btn">
                <i class="fas fa-save"></i> Simpan Data Anak
            </button>
        </form>
        
        <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 5px;">
            <h3><i class="fas fa-history"></i> Data yang Telah Diinput:</h3>
            <?php if (!empty($_SESSION['anak_data'])): ?>
                <ul style="margin-top: 10px;">
                    <?php foreach ($_SESSION['anak_data'] as $data): ?>
                    <li><?php echo $data['nama']; ?> (<?php echo $data['jenis_kelamin']; ?>) - <?php echo $data['tanggal_lahir']; ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>Belum ada data anak yang diinput.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>