<?php
// imunisasi.php - WITH SAFE JAVASCRIPT
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cakupan Imunisasi - SIRAGA</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f5f7fa; }
        .container { max-width: 1200px; margin: 20px auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: #27ae60; color: white; padding: 30px; }
        .header h1 { margin: 0; }
        .nav { background: #2ecc71; }
        .nav a { display: inline-block; padding: 15px 20px; color: white; text-decoration: none; }
        .nav a:hover { background: #27ae60; }
        .content { padding: 30px; }
        .back-btn { display: inline-block; margin-bottom: 20px; padding: 10px 20px; background: #27ae60; color: white; text-decoration: none; border-radius: 5px; }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 30px 0; }
        .stat-card { background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center; }
        .stat-number { font-size: 36px; font-weight: bold; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background: #f8f9fa; padding: 15px; text-align: left; font-weight: bold; border-bottom: 2px solid #ddd; }
        td { padding: 12px 15px; border-bottom: 1px solid #eee; }
        .progress { height: 20px; background: #ecf0f1; border-radius: 10px; overflow: hidden; margin: 5px 0; }
        .progress-bar { height: 100%; background: #2ecc71; border-radius: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Cakupan Imunisasi - SIRAGA</h1>
            <p>Monitoring persentase imunisasi per wilayah</p>
        </div>
        
        <div class="nav">
            <a href="dashboard.php">🏠 Dashboard</a>
            <a href="statistik.php">📊 Statistik Wilayah</a>
            <a href="imunisasi.php">💉 Cakupan Imunisasi</a>
            <a href="laporan.php">📄 Laporan</a>
            <a href="peta.php">🗺️ Peta Sebaran</a>
        </div>
        
        <div class="content">
            <a href="dashboard.php" class="back-btn">← Kembali ke Dashboard</a>
            
            <h2>📈 Statistik Cakupan Imunisasi</h2>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div>Total Vaksinasi</div>
                    <div class="stat-number" data-value="423">0</div>
                    <div>Pemberian vaksin</div>
                </div>
                <div class="stat-card">
                    <div>Anak Tervaksinasi</div>
                    <div class="stat-number" data-value="134">0</div>
                    <div>85.9% cakupan</div>
                </div>
                <div class="stat-card">
                    <div>Jenis Vaksin</div>
                    <div class="stat-number" data-value="8">0</div>
                    <div>Jenis tersedia</div>
                </div>
            </div>
            
            <h2>💉 Data Vaksinasi per Jenis</h2>
            
            <table id="tabelVaksin">
                <thead>
                    <tr>
                        <th>Jenis Vaksin</th>
                        <th>Total Pemberian</th>
                        <th>Anak Tervaksinasi</th>
                        <th>Cakupan</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>BCG</strong></td>
                        <td>156</td>
                        <td>145</td>
                        <td>
                            <div class="progress">
                                <div class="progress-bar" style="width: 93%" data-percent="93"></div>
                            </div>
                            93%
                        </td>
                        <td><span class="status-badge" style="color: #27ae60;">Baik</span></td>
                    </tr>
                    <tr>
                        <td><strong>Hepatitis B</strong></td>
                        <td>142</td>
                        <td>130</td>
                        <td>
                            <div class="progress">
                                <div class="progress-bar" style="width: 91%" data-percent="91"></div>
                            </div>
                            91%
                        </td>
                        <td><span class="status-badge" style="color: #27ae60;">Baik</span></td>
                    </tr>
                    <tr>
                        <td><strong>Polio</strong></td>
                        <td>138</td>
                        <td>125</td>
                        <td>
                            <div class="progress">
                                <div class="progress-bar" style="width: 90%" data-percent="90"></div>
                            </div>
                            90%
                        </td>
                        <td><span class="status-badge" style="color: #27ae60;">Baik</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- SAFE JAVASCRIPT - IMUNISASI SPECIFIC -->
    <script>
    // Imunisasi JavaScript - Isolated Scope
    (function() {
        'use strict';
        
        // Animate numbers
        function animateNumbers() {
            var numbers = document.querySelectorAll('.stat-number[data-value]');
            numbers.forEach(function(number) {
                var finalValue = parseInt(number.getAttribute('data-value'));
                var current = 0;
                var increment = finalValue / 30;
                var timer = setInterval(function() {
                    current += increment;
                    if (current >= finalValue) {
                        clearInterval(timer);
                        number.textContent = finalValue;
                    } else {
                        number.textContent = Math.floor(current);
                    }
                }, 30);
            });
        }
        
        // Animate progress bars
        function animateProgressBars() {
            var bars = document.querySelectorAll('.progress-bar[data-percent]');
            bars.forEach(function(bar) {
                var percent = parseInt(bar.getAttribute('data-percent'));
                var current = 0;
                var increment = percent / 30;
                var timer = setInterval(function() {
                    current += increment;
                    if (current >= percent) {
                        clearInterval(timer);
                        bar.style.width = percent + '%';
                    } else {
                        bar.style.width = Math.floor(current) + '%';
                    }
                }, 30);
            });
        }
        
        // Table interactions
        function setupTable() {
            var rows = document.querySelectorAll('#tabelVaksin tbody tr');
            rows.forEach(function(row) {
                row.addEventListener('mouseenter', function() {
                    this.style.backgroundColor = '#f0f9f0';
                });
                row.addEventListener('mouseleave', function() {
                    this.style.backgroundColor = '';
                });
                row.addEventListener('click', function() {
                    var vaksin = this.querySelector('td:first-child strong').textContent;
                    var cakupan = this.querySelector('.progress-bar').style.width;
                    alert('Vaksin: ' + vaksin + '\nCakupan: ' + cakupan);
                });
            });
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            animateNumbers();
            animateProgressBars();
            setupTable();
            console.log('Imunisasi JS loaded');
        });
        
    })();
    </script>
</body>
</html>