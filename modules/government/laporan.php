<?php
// laporan.php - WITH SAFE JAVASCRIPT
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Laporan - SIRAGA</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f5f7fa; }
        .container { max-width: 1200px; margin: 20px auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: #f39c12; color: white; padding: 30px; }
        .header h1 { margin: 0; }
        .nav { background: #f1c40f; }
        .nav a { display: inline-block; padding: 15px 20px; color: white; text-decoration: none; }
        .nav a:hover { background: #f39c12; }
        .content { padding: 30px; }
        .back-btn { display: inline-block; margin-bottom: 20px; padding: 10px 20px; background: #f39c12; color: white; text-decoration: none; border-radius: 5px; }
        .report-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin: 30px 0; }
        .report-card { background: #f8f9fa; padding: 25px; border-radius: 8px; border-left: 4px solid #f39c12; }
        .report-card h3 { color: #2c3e50; margin-top: 0; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; margin: 5px; }
        .btn-pdf { background: #e74c3c; color: white; }
        .btn-excel { background: #27ae60; color: white; }
        .btn:hover { opacity: 0.9; }
        .settings { background: #e8f4fc; padding: 20px; border-radius: 8px; margin: 30px 0; }
        select, input { padding: 8px; margin: 0 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Generate Laporan - SIRAGA</h1>
            <p>PDF/Excel untuk monitoring</p>
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
            
            <h2>📋 Pilih Jenis Laporan</h2>
            
            <div class="report-grid" id="reportGrid">
                <div class="report-card">
                    <h3>📊 Laporan Statistik Wilayah</h3>
                    <p>Data agregat perkembangan anak per wilayah</p>
                    <button class="btn btn-pdf" data-report="statistik" data-format="pdf">📥 Download PDF</button>
                    <button class="btn btn-excel" data-report="statistik" data-format="excel">📥 Download Excel</button>
                </div>
                
                <div class="report-card">
                    <h3>💉 Laporan Cakupan Imunisasi</h3>
                    <p>Monitoring persentase imunisasi per wilayah</p>
                    <button class="btn btn-pdf" data-report="imunisasi" data-format="pdf">📥 Download PDF</button>
                    <button class="btn btn-excel" data-report="imunisasi" data-format="excel">📥 Download Excel</button>
                </div>
                
                <div class="report-card">
                    <h3>⚠️ Laporan Kasus Stunting</h3>
                    <p>Detail kasus stunting dan intervensi</p>
                    <button class="btn btn-pdf" data-report="stunting" data-format="pdf">📥 Download PDF</button>
                    <button class="btn btn-excel" data-report="stunting" data-format="excel">📥 Download Excel</button>
                </div>
                
                <div class="report-card">
                    <h3>📈 Laporan Bulanan</h3>
                    <p>Ringkasan kegiatan bulanan pemerintah</p>
                    <button class="btn btn-pdf" data-report="bulanan" data-format="pdf">📥 Download PDF</button>
                    <button class="btn btn-excel" data-report="bulanan" data-format="excel">📥 Download Excel</button>
                </div>
            </div>
            
            <div class="settings">
                <h3>⚙️ Pengaturan Laporan</h3>
                
                <div style="margin: 15px 0;">
                    <label><strong>Periode Laporan:</strong></label>
                    <select id="periodeLaporan">
                        <option value="bulan-ini">Bulan Ini</option>
                        <option value="3-bulan">3 Bulan Terakhir</option>
                        <option value="tahun-ini">Tahun Ini</option>
                    </select>
                </div>
                
                <div style="margin: 15px 0;">
                    <label><strong>Format File:</strong></label>
                    <label><input type="radio" name="format" value="pdf" checked> PDF</label>
                    <label><input type="radio" name="format" value="excel"> Excel</label>
                </div>
                
                <button id="generateAll" style="padding: 12px 30px; background: #9b59b6; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">
                    🚀 Generate Semua Laporan
                </button>
            </div>
        </div>
    </div>
    
    <!-- SAFE JAVASCRIPT - LAPORAN SPECIFIC -->
    <script>
    // Laporan JavaScript - Isolated Scope
    (function() {
        'use strict';
        
        // Download button handlers
        function setupDownloadButtons() {
            var buttons = document.querySelectorAll('.btn');
            buttons.forEach(function(button) {
                button.addEventListener('click', function() {
                    var report = this.getAttribute('data-report');
                    var format = this.getAttribute('data-format');
                    var periode = document.getElementById('periodeLaporan').value;
                    
                    // Simulate download
                    this.innerHTML = '⏳ Memproses...';
                    this.disabled = true;
                    
                    setTimeout(function() {
                        alert('Laporan ' + report + ' dalam format ' + format.toUpperCase() + ' periode ' + periode + ' berhasil diunduh!');
                        button.innerHTML = format === 'pdf' ? '📥 Download PDF' : '📥 Download Excel';
                        button.disabled = false;
                    }, 1500);
                });
            });
        }
        
        // Generate all reports
        function setupGenerateAll() {
            var generateBtn = document.getElementById('generateAll');
            
            generateBtn.addEventListener('click', function() {
                var periode = document.getElementById('periodeLaporan').value;
                var format = document.querySelector('input[name="format"]:checked').value;
                
                generateBtn.innerHTML = '⏳ Memproses semua laporan...';
                generateBtn.disabled = true;
                
                setTimeout(function() {
                    alert('Semua laporan periode ' + periode + ' dalam format ' + format.toUpperCase() + ' sedang diproses.\nFile akan tersedia dalam beberapa saat.');
                    generateBtn.innerHTML = '🚀 Generate Semua Laporan';
                    generateBtn.disabled = false;
                }, 2000);
            });
        }
        
        // Report card hover effects
        function setupReportCards() {
            var cards = document.querySelectorAll('.report-card');
            cards.forEach(function(card) {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.boxShadow = '0 10px 25px rgba(0,0,0,0.15)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = 'none';
                });
            });
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            setupDownloadButtons();
            setupGenerateAll();
            setupReportCards();
            console.log('Laporan JS loaded');
        });
        
    })();
    </script>
</body>
</html>