<?php
// dashboard.php - WITH SAFE JAVASCRIPT
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SIRAGA Government</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f5f7fa; }
        .container { max-width: 1200px; margin: 20px auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: #2c3e50; color: white; padding: 30px; }
        .header h1 { margin: 0; }
        .nav { background: #34495e; }
        .nav a { display: inline-block; padding: 15px 20px; color: white; text-decoration: none; }
        .nav a:hover { background: #2c3e50; }
        .content { padding: 30px; }
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 30px 0; }
        .stat-card { background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid; text-align: center; }
        .stat-card:nth-child(1) { border-color: #3498db; }
        .stat-card:nth-child(2) { border-color: #2ecc71; }
        .stat-card:nth-child(3) { border-color: #e74c3c; }
        .stat-card:nth-child(4) { border-color: #f39c12; }
        .stat-number { font-size: 32px; font-weight: bold; margin: 10px 0; }
        .menu-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin: 30px 0; }
        .menu-card { background: #f8f9fa; padding: 25px; border-radius: 8px; text-decoration: none; color: #333; border: 2px solid transparent; }
        .menu-card:hover { border-color: #3498db; background: white; }
        .menu-icon { font-size: 40px; margin-bottom: 15px; }
        .footer { text-align: center; padding: 20px; color: #7f8c8d; border-top: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Selamat Datang di SIRAGA!</h1>
            <p>Anda login sebagai: <strong>Pemerintah</strong></p>
            <p>Email: gov@siraga.com | Login: <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
        
        <div class="nav">
            <a href="dashboard.php">🏠 Dashboard</a>
            <a href="statistik.php">📊 Statistik Wilayah</a>
            <a href="imunisasi.php">💉 Cakupan Imunisasi</a>
            <a href="laporan.php">📄 Laporan</a>
            <a href="peta.php">🗺️ Peta Sebaran</a>
        </div>
        
        <div class="content">
            <h2>Menu Utama</h2>
            
            <div class="menu-grid">
                <a href="statistik.php" class="menu-card">
                    <div class="menu-icon">📊</div>
                    <h3>Statistik Wilayah</h3>
                    <p>Lihat data agregat perkembangan anak per wilayah</p>
                </a>
                
                <a href="laporan.php" class="menu-card">
                    <div class="menu-icon">📄</div>
                    <h3>Generate Laporan</h3>
                    <p>PDF/Excel untuk monitoring</p>
                </a>
                
                <a href="imunisasi.php" class="menu-card">
                    <div class="menu-icon">💉</div>
                    <h3>Cakupan Imunisasi</h3>
                    <p>Monitoring persentase imunisasi per wilayah</p>
                </a>
                
                <a href="peta.php" class="menu-card">
                    <div class="menu-icon">🗺️</div>
                    <h3>Peta Sebaran</h3>
                    <p>Monitoring sebaran kasus stunting wilayah</p>
                </a>
            </div>
            
            <h2>Statistik Sistem</h2>
            
            <div class="stats">
                <div class="stat-card">
                    <div>Total Anak</div>
                    <div class="stat-number">156</div>
                    <div style="font-size: 14px; color: #7f8c8d;">Terdata</div>
                </div>
                
                <div class="stat-card">
                    <div>Kasus Stunting</div>
                    <div class="stat-number">23</div>
                    <div style="font-size: 14px; color: #7f8c8d;">Terdeteksi</div>
                </div>
                
                <div class="stat-card">
                    <div>Imunisasi</div>
                    <div class="stat-number">134</div>
                    <div style="font-size: 14px; color: #7f8c8d;">Tervaksinasi</div>
                </div>
                
                <div class="stat-card">
                    <div>Pemeriksaan</div>
                    <div class="stat-number">423</div>
                    <div style="font-size: 14px; color: #7f8c8d;">Rekam medis</div>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>Sistem Informasi Gizi dan Anak (SIRAGA) &copy; <?php echo date('Y'); ?></p>
        </div>
    </div>
    
    <!-- SAFE JAVASCRIPT - DASHBOARD SPECIFIC -->
    <script>
    // Dashboard JavaScript - Isolated Scope
    (function() {
        'use strict';
        
        // Animate stat numbers
        function animateStats() {
            var statNumbers = document.querySelectorAll('.stat-number');
            statNumbers.forEach(function(stat) {
                var finalValue = parseInt(stat.textContent);
                if (!isNaN(finalValue)) {
                    var current = 0;
                    var increment = finalValue / 30;
                    var timer = setInterval(function() {
                        current += increment;
                        if (current >= finalValue) {
                            clearInterval(timer);
                            stat.textContent = finalValue.toLocaleString();
                        } else {
                            stat.textContent = Math.floor(current).toLocaleString();
                        }
                    }, 30);
                }
            });
        }
        
        // Menu card hover effects
        function setupMenuCards() {
            var menuCards = document.querySelectorAll('.menu-card');
            menuCards.forEach(function(card) {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.boxShadow = '0 10px 20px rgba(0,0,0,0.1)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = 'none';
                });
            });
        }
        
        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            animateStats();
            setupMenuCards();
            console.log('Dashboard JS loaded');
        });
        
    })(); // Immediately Invoked Function Expression
    </script>
</body>
</html>