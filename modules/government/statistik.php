<?php
// statistik.php - WITH SAFE JAVASCRIPT
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistik Wilayah - SIRAGA</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f5f7fa; }
        .container { max-width: 1200px; margin: 20px auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: #2c3e50; color: white; padding: 30px; }
        .header h1 { margin: 0; }
        .nav { background: #34495e; }
        .nav a { display: inline-block; padding: 15px 20px; color: white; text-decoration: none; }
        .nav a:hover { background: #2c3e50; }
        .content { padding: 30px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background: #f8f9fa; padding: 15px; text-align: left; font-weight: bold; border-bottom: 2px solid #ddd; }
        td { padding: 12px 15px; border-bottom: 1px solid #eee; }
        .badge { padding: 5px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; display: inline-block; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .back-btn { display: inline-block; margin-bottom: 20px; padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; }
        .filter { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Statistik Wilayah - SIRAGA</h1>
            <p>Lihat data agregat perkembangan anak per wilayah</p>
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
            
            <div class="filter">
                <label>Filter Wilayah:</label>
                <select id="filterWilayah">
                    <option value="">Semua Wilayah</option>
                    <option value="Jakarta">Jakarta</option>
                    <option value="Bandung">Bandung</option>
                    <option value="Surabaya">Surabaya</option>
                </select>
                <button id="btnFilter">Terapkan Filter</button>
            </div>
            
            <h2>Data Statistik per Wilayah</h2>
            
            <table id="tabelStatistik">
                <thead>
                    <tr>
                        <th>Wilayah</th>
                        <th>Total Anak</th>
                        <th>Kasus Stunting</th>
                        <th>Persentase</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr data-wilayah="Jakarta">
                        <td><strong>Jakarta Pusat</strong></td>
                        <td>45</td>
                        <td>5</td>
                        <td>11.1%</td>
                        <td><span class="badge badge-success">Rendah</span></td>
                    </tr>
                    <tr data-wilayah="Bandung">
                        <td><strong>Bandung Timur</strong></td>
                        <td>38</td>
                        <td>3</td>
                        <td>7.9%</td>
                        <td><span class="badge badge-success">Rendah</span></td>
                    </tr>
                    <tr data-wilayah="Surabaya">
                        <td><strong>Surabaya Barat</strong></td>
                        <td>52</td>
                        <td>7</td>
                        <td>13.5%</td>
                        <td><span class="badge badge-warning">Sedang</span></td>
                    </tr>
                    <tr data-wilayah="Medan">
                        <td><strong>Medan Utara</strong></td>
                        <td>41</td>
                        <td>4</td>
                        <td>9.8%</td>
                        <td><span class="badge badge-success">Rendah</span></td>
                    </tr>
                    <tr data-wilayah="Yogyakarta">
                        <td><strong>Yogyakarta</strong></td>
                        <td>29</td>
                        <td>2</td>
                        <td>6.9%</td>
                        <td><span class="badge badge-success">Rendah</span></td>
                    </tr>
                </tbody>
            </table>
            
            <div style="margin-top: 30px; padding: 20px; background: #e8f4fc; border-radius: 8px;">
                <h3>📊 Ringkasan Statistik</h3>
                <p>Total wilayah: <strong>5 wilayah</strong></p>
                <p>Total anak terdata: <strong>205 anak</strong></p>
                <p>Total kasus stunting: <strong>21 kasus</strong> (10.2%)</p>
                <p>Status keseluruhan: <span style="color: #27ae60; font-weight: bold;">Baik</span></p>
            </div>
        </div>
    </div>
    
    <!-- SAFE JAVASCRIPT - STATISTIK SPECIFIC -->
    <script>
    // Statistik JavaScript - Isolated Scope
    (function() {
        'use strict';
        
        // Table filtering
        function setupFilter() {
            var filterSelect = document.getElementById('filterWilayah');
            var filterButton = document.getElementById('btnFilter');
            var tableRows = document.querySelectorAll('#tabelStatistik tbody tr');
            
            filterButton.addEventListener('click', function() {
                var filterValue = filterSelect.value.toLowerCase();
                
                tableRows.forEach(function(row) {
                    var wilayah = row.getAttribute('data-wilayah').toLowerCase();
                    
                    if (filterValue === '' || wilayah === filterValue) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
        
        // Table row hover effects
        function setupTableHover() {
            var tableRows = document.querySelectorAll('#tabelStatistik tbody tr');
            
            tableRows.forEach(function(row) {
                row.addEventListener('mouseenter', function() {
                    this.style.backgroundColor = '#f8f9fa';
                    this.style.cursor = 'pointer';
                });
                
                row.addEventListener('mouseleave', function() {
                    this.style.backgroundColor = '';
                });
                
                row.addEventListener('click', function() {
                    var wilayah = this.querySelector('td:first-child strong').textContent;
                    var stunting = this.querySelector('td:nth-child(3)').textContent;
                    alert('Wilayah: ' + wilayah + '\nKasus Stunting: ' + stunting);
                });
            });
        }
        
        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            setupFilter();
            setupTableHover();
            console.log('Statistik JS loaded');
        });
        
    })(); // Immediately Invoked Function Expression
    </script>
</body>
</html>