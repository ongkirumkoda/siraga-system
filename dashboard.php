<?php
// dashboard.php - FIXED VERSION 100% WORKING
session_start();

// DEBUG MODE ON
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CEK SESSION PALING AMAN
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    // Hapus semua session
    session_unset();
    session_destroy();
    // Redirect ke index
    header('Location: index.php');
    exit;
}

// AMBIL DATA USER
$user = $_SESSION['user'];

// PASTIKAN SEMUA KEY YANG DIBUTUHKAN ADA
$defaultUser = [
    'id' => 0,
    'email' => 'unknown@email.com',
    'name' => 'User',
    'role' => 'guest',
    'login_time' => date('Y-m-d H:i:s'),
    'location' => ''
];

// GABUNG DENGAN DEFAULT JIKA ADA YANG KOSONG
$user = array_merge($defaultUser, $user);

// AMBIL ROLE
$role = $user['role'];

// VALIDASI ROLE
$validRoles = ['government', 'nakes', 'parent', 'admin'];
if (!in_array($role, $validRoles)) {
    // Role tidak valid, logout
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}

// Role names
$roleNames = [
    'government' => 'Pemerintah',
    'nakes' => 'Tenaga Kesehatan', 
    'parent' => 'Orang Tua',
    'admin' => 'Administrator'
];

// Role colors
$roleColors = [
    'government' => '#3498db',
    'nakes' => '#27ae60',
    'parent' => '#f39c12',
    'admin' => '#9b59b6'
];

// LOG SUCCESS
error_log("DASHBOARD ACCESS: User {$user['email']} as {$role}");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SIRAGA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2c3e50;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --dark: #2c3e50;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: #f8fafc;
            color: #333;
        }
        
        /* HEADER */
        .header {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--dark);
        }
        
        .logo i {
            color: var(--primary);
            font-size: 28px;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-info {
            text-align: right;
        }
        
        .user-name {
            font-weight: 600;
            color: var(--dark);
        }
        
        .user-role {
            display: inline-block;
            padding: 3px 12px;
            background: <?php echo $roleColors[$role]; ?>;
            color: white;
            border-radius: 15px;
            font-size: 12px;
            margin-top: 3px;
        }
        
        .btn-logout {
            background: var(--danger);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn-logout:hover {
            background: #c0392b;
        }
        
        /* MAIN CONTENT */
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .welcome-box {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        .welcome-title {
            font-size: 28px;
            margin-bottom: 10px;
            color: var(--dark);
        }
        
        /* MODULE CARDS */
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }
        
        .module-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .module-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
        }
        
        .module-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 20px;
            color: white;
        }
        
        .module-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark);
        }
        
        .module-desc {
            color: #7f8c8d;
            font-size: 14px;
            line-height: 1.5;
        }
        
        /* ERROR MESSAGE */
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border: 1px solid #f5c6cb;
        }
        
        /* LOADING */
        .loading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- LOADING OVERLAY -->
    <div class="loading" id="loading">
        <div class="loading-spinner"></div>
    </div>
    
    <!-- HEADER -->
    <div class="header">
        <a href="dashboard.php" class="logo">
            <i class="fas fa-heartbeat"></i>
            <div>
                <h2>SIRAGA</h2>
                <small>Sistem Pencatatan Tumbuh Anak</small>
            </div>
        </a>
        
        <div class="user-menu">
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                <span class="user-role"><?php echo $roleNames[$role]; ?></span>
            </div>
            <button class="btn-logout" onclick="logout()">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </div>
    </div>
    
    <!-- MAIN CONTENT -->
    <div class="container">
        <!-- WELCOME -->
        <div class="welcome-box">
            <h1 class="welcome-title">Selamat Datang di SIRAGA! 👋</h1>
            <p>Anda login sebagai: <strong><?php echo $roleNames[$role]; ?></strong></p>
            <p>Email: <?php echo htmlspecialchars($user['email']); ?></p>
            <p>Login: <?php echo $user['login_time']; ?></p>
            
            <?php if (!empty($user['location'])): ?>
            <p style="margin-top: 10px;">
                <i class="fas fa-map-marker-alt"></i> Lokasi: <?php echo htmlspecialchars($user['location']); ?>
            </p>
            <?php endif; ?>
        </div>
        
        <!-- ERROR DISPLAY -->
        <?php if (isset($_SESSION['error'])): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-triangle"></i> 
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
        <?php endif; ?>
        
        <!-- MODULES BERDASARKAN ROLE -->
        <h2 style="margin: 30px 0 20px; color: var(--dark);">
            <i class="fas fa-th-large"></i> Menu Utama
        </h2>
        
        <div class="modules-grid">
            <?php if ($role === 'government'): ?>
                <!-- MODUL PEMERINTAH -->
                <a href="/siraga/modules/government/statistik.php" class="module-card" onclick="showLoading()">
                    <div class="module-icon" style="background: #3498db;">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3 class="module-title">Statistik Wilayah</h3>
                    <p class="module-desc">Lihat data agregat perkembangan anak per wilayah</p>
                </a>
                
                <a href="/siraga/modules/government/laporan.php" class="module-card" onclick="showLoading()">
                    <div class="module-icon" style="background: #2ecc71;">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h3 class="module-title">Laporan</h3>
                    <p class="module-desc">Generate laporan PDF/Excel untuk monitoring</p>
                </a>
                
                <a href="/siraga/modules/government/peta.php" class="module-card" onclick="showLoading()">
                    <div class="module-icon" style="background: #9b59b6;">
                        <i class="fas fa-map-marked-alt"></i>
                    </div>
                    <h3 class="module-title">Peta Sebaran</h3>
                    <p class="module-desc">Monitoring sebaran kasus stunting wilayah</p>
                </a>
                
                <a href="/siraga/modules/government/imunisasi.php" class="module-card" onclick="showLoading()">
                    <div class="module-icon" style="background: #e74c3c;">
                        <i class="fas fa-syringe"></i>
                    </div>
                    <h3 class="module-title">Cakupan Imunisasi</h3>
                    <p class="module-desc">Monitoring persentase imunisasi per wilayah</p>
                </a>
                
            <?php elseif ($role === 'nakes'): ?>
                <!-- MODUL NAKES -->
                <a href="/siraga/modules/nakes/anak_baru.php" class="module-card" onclick="showLoading()">
                    <div class="module-icon" style="background: #27ae60;">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h3 class="module-title">Input Data Anak</h3>
                    <p class="module-desc">Input data anak baru untuk monitoring</p>
                </a>
                
                <a href="/siraga/modules/nakes/pemeriksaan.php" class="module-card" onclick="showLoading()">
                    <div class="module-icon" style="background: #3498db;">
                        <i class="fas fa-notes-medical"></i>
                    </div>
                    <h3 class="module-title">Pemeriksaan</h3>
                    <p class="module-desc">Input hasil pemeriksaan berat/tinggi badan</p>
                </a>
                
                <a href="/siraga/modules/nakes/imunisasi.php" class="module-card" onclick="showLoading()">
                    <div class="module-icon" style="background: #f39c12;">
                        <i class="fas fa-syringe"></i>
                    </div>
                    <h3 class="module-title">Imunisasi</h3>
                    <p class="module-desc">Catat imunisasi yang telah diberikan</p>
                </a>
                
                <a href="/siraga/modules/nakes/riwayat.php" class="module-card" onclick="showLoading()">
                    <div class="module-icon" style="background: #9b59b6;">
                        <i class="fas fa-history"></i>
                    </div>
                    <h3 class="module-title">Riwayat Pasien</h3>
                    <p class="module-desc">Lihat riwayat pemeriksaan semua pasien</p>
                </a>
                
            <?php elseif ($role === 'parent'): ?>
                <!-- MODUL ORANG TUA -->
                <a href="/siraga/modules/parent/anak_saya.php" class="module-card" onclick="showLoading()">
                    <div class="module-icon" style="background: #f39c12;">
                        <i class="fas fa-child"></i>
                    </div>
                    <h3 class="module-title">Data Anak Saya</h3>
                    <p class="module-desc">Lihat data anak dan profil lengkap</p>
                </a>
                
                <a href="/siraga/modules/parent/grafik.php" class="module-card" onclick="showLoading()">
                    <div class="module-icon" style="background: #3498db;">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 class="module-title">Grafik Pertumbuhan</h3>
                    <p class="module-desc">Pantau perkembangan berat & tinggi badan</p>
                </a>
                
                <a href="/siraga/modules/parent/jadwal.php" class="module-card" onclick="showLoading()">
                    <div class="module-icon" style="background: #2ecc71;">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h3 class="module-title">Jadwal</h3>
                    <p class="module-desc">Lihat jadwal imunisasi dan pemeriksaan</p>
                </a>
                
                <a href="/siraga/modules/parent/notifikasi.php" class="module-card" onclick="showLoading()">
                    <div class="module-icon" style="background: #e74c3c;">
                        <i class="fas fa-bell"></i>
                    </div>
                    <h3 class="module-title">Notifikasi</h3>
                    <p class="module-desc">Pengingat jadwal dan informasi penting</p>
                </a>
                
            <?php elseif ($role === 'admin'): ?>
                <!-- MODUL ADMIN - PATH FIXED 100% -->
                <a href="/siraga/modules/admin/user_management.php" class="module-card" onclick="showLoading()">
                    <div class="module-icon" style="background: #9b59b6;">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <h3 class="module-title">Manajemen User</h3>
                    <p class="module-desc">Tambah/edit/hapus user semua role</p>
                </a>
                
                <a href="/siraga/modules/admin/database.php" class="module-card" onclick="showLoading()">
                    <div class="module-icon" style="background: #3498db;">
                        <i class="fas fa-database"></i>
                    </div>
                    <h3 class="module-title">Database</h3>
                    <p class="module-desc">Backup, restore, dan optimasi database</p>
                </a>
                
                <a href="/siraga/modules/admin/settings.php" class="module-card" onclick="showLoading()">
                    <div class="module-icon" style="background: #2ecc71;">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <h3 class="module-title">Pengaturan Sistem</h3>
                    <p class="module-desc">Konfigurasi sistem dan aplikasi</p>
                </a>
                
                <a href="/siraga/modules/admin/logs.php" class="module-card" onclick="showLoading()">
                    <div class="module-icon" style="background: #e74c3c;">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <h3 class="module-title">System Logs</h3>
                    <p class="module-desc">Monitoring aktivitas sistem</p>
                </a>
            <?php endif; ?>
        </div>
        
        <!-- QUICK INFO -->
        <div style="margin-top: 40px; padding: 20px; background: white; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.08);">
            <h3 style="margin-bottom: 15px; color: var(--dark);">
                <i class="fas fa-info-circle"></i> Informasi Sistem
            </h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <strong>Versi SIRAGA:</strong> 1.0.0
                </div>
                <div>
                    <strong>Support:</strong> ongkiid81@gmail.com
                </div>
                <div>
                    <strong>WhatsApp:</strong> 0878-3260-8497
                </div>
                <div>
                    <strong>Status:</strong> <span style="color: #27ae60;">● Online</span>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Fungsi untuk logout dengan konfirmasi
        function logout() {
            if (confirm('Yakin ingin logout?')) {
                showLoading();
                window.location.href = 'auth/logout.php';
            }
        }
        
        // Fungsi untuk menampilkan loading
        function showLoading() {
            document.getElementById('loading').style.display = 'flex';
        }
        
        // Sembunyikan loading saat halaman selesai load
        window.addEventListener('load', function() {
            document.getElementById('loading').style.display = 'none';
        });
        
        // Cek sessionStorage untuk error login
        document.addEventListener('DOMContentLoaded', function() {
            const loginError = sessionStorage.getItem('login_error');
            if (loginError) {
                alert('Login Error: ' + loginError);
                sessionStorage.removeItem('login_error');
            }
            
            // Update lokasi dari sessionStorage jika ada
            const userCity = sessionStorage.getItem('userCity');
            if (userCity) {
                console.log('User location detected:', userCity);
                // Bisa dikirim ke server via AJAX jika perlu
            }
            
            // Debug info
            console.log('Dashboard loaded for role: <?php echo $role; ?>');
            console.log('User: <?php echo $user["name"]; ?>');
        });
        
        // Handle link clicks untuk modul yang belum ada
        document.querySelectorAll('.module-card').forEach(link => {
            link.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                // Cek apakah file modul ada
                if (href && !href.includes('#')) {
                    fetch(href, { method: 'HEAD' })
                        .then(response => {
                            if (!response.ok) {
                                e.preventDefault();
                                showLoading();
                                setTimeout(() => {
                                    hideLoading();
                                    alert('Modul ini sedang dalam pengembangan.\n\nHubungi support: ongkiid81@gmail.com\nWhatsApp: 0878-3260-8497');
                                }, 500);
                            }
                        })
                        .catch(() => {
                            e.preventDefault();
                            alert('Modul belum tersedia. Akan segera diluncurkan!');
                        });
                }
            });
        });
        
        function hideLoading() {
            document.getElementById('loading').style.display = 'none';
        }
    </script>
</body>
</html>