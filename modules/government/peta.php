<?php
// peta.php - WITH SAFE JAVASCRIPT
// Versi sederhana seperti statistik.php
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peta Sebaran - SIRAGA</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            background: #f5f7fa; 
        }
        .container { 
            max-width: 1400px; 
            margin: 20px auto; 
            background: white; 
            border-radius: 10px; 
            overflow: hidden; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
        }
        .header { 
            background: linear-gradient(to right, #3498db, #2c3e50);
            color: white; 
            padding: 30px; 
            text-align: center;
        }
        .header h1 { 
            margin: 0; 
            font-size: 28px;
        }
        .nav { 
            background: #34495e; 
            padding: 0 20px;
            display: flex;
            flex-wrap: wrap;
        }
        .nav a { 
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 15px 20px; 
            color: white; 
            text-decoration: none; 
            transition: background 0.3s;
        }
        .nav a:hover { 
            background: #2c3e50; 
        }
        .nav a.active {
            background: #3498db;
        }
        .content { 
            padding: 30px; 
        }
        .back-btn { 
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px; 
            padding: 10px 20px; 
            background: #3498db; 
            color: white; 
            text-decoration: none; 
            border-radius: 5px; 
            border: none;
            cursor: pointer;
        }
        .back-btn:hover { 
            background: #2980b9;
        }
        .filter { 
            margin: 20px 0; 
            padding: 20px; 
            background: #f8f9fa; 
            border-radius: 8px; 
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        .filter select, .filter input {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            background: white;
            min-width: 200px;
        }
        .map-container { 
            background: #f8f9fa; 
            padding: 20px; 
            border-radius: 8px; 
            margin: 20px 0; 
            border: 1px solid #e0e0e0;
        }
        #map { 
            height: 600px; 
            width: 100%; 
            border-radius: 8px;
        }
        .stats { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 15px; 
            margin: 20px 0; 
        }
        .stat-card { 
            background: white; 
            padding: 20px; 
            border-radius: 10px; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.05); 
            text-align: center; 
            border-top: 4px solid #3498db; 
        }
        .stat-card h3 { 
            margin: 0 0 10px 0; 
            color: #7f8c8d; 
            font-size: 14px; 
        }
        .stat-card .value { 
            font-size: 28px; 
            font-weight: bold; 
            color: #2c3e50; 
        }
        .legend { 
            display: flex; 
            justify-content: center; 
            gap: 20px; 
            margin: 20px 0; 
            flex-wrap: wrap; 
        }
        .legend-item { 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            padding: 8px 15px; 
            border-radius: 6px; 
            background: #f8f9fa; 
        }
        .color-box { 
            width: 20px; 
            height: 20px; 
            border-radius: 4px; 
            border: 1px solid rgba(0,0,0,0.1); 
        }
        .info-box {
            background: #e8f4fc;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin: 20px 0;
            border-radius: 6px;
        }
        @media (max-width: 768px) {
            .content { padding: 20px; }
            #map { height: 400px; }
            .filter { flex-direction: column; align-items: stretch; }
            .filter select, .filter input { min-width: 100%; }
        }
    </style>
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🗺️ Peta Sebaran Fasilitas Kesehatan - SIRAGA</h1>
            <p>Sistem Informasi Geografis Monitoring Fasilitas Kesehatan</p>
        </div>
        
        <div class="nav">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="statistik.php"><i class="fas fa-chart-bar"></i> Statistik</a>
            <a href="imunisasi.php"><i class="fas fa-syringe"></i> Imunisasi</a>
            <a href="laporan.php"><i class="fas fa-file"></i> Laporan</a>
            <a href="peta.php" class="active"><i class="fas fa-map"></i> Peta Sebaran</a>
            <a href="logout.php" style="margin-left: auto; background: #e74c3c;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
        
        <div class="content">
            <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>
            
            <div class="info-box">
                <i class="fas fa-info-circle"></i> Peta ini menampilkan sebaran fasilitas kesehatan berdasarkan koordinat GPS. Klik marker untuk detail informasi.
            </div>
            
            <div class="stats">
                <div class="stat-card">
                    <h3><i class="fas fa-hospital"></i> Total Fasilitas</h3>
                    <div class="value" id="total-lokasi">0</div>
                </div>
                <div class="stat-card">
                    <h3><i class="fas fa-check-circle"></i> Aktif</h3>
                    <div class="value" id="lokasi-aktif">0</div>
                </div>
                <div class="stat-card">
                    <h3><i class="fas fa-map-marker-alt"></i> Marker</h3>
                    <div class="value" id="jumlah-marker">0</div>
                </div>
            </div>
            
            <div class="filter">
                <select id="kategori-filter">
                    <option value="">Semua Kategori</option>
                    <option value="puskesmas">Puskesmas</option>
                    <option value="rumah_sakit">Rumah Sakit</option>
                    <option value="klinik">Klinik</option>
                    <option value="posyandu">Posyandu</option>
                    <option value="apotek">Apotek</option>
                </select>
                
                <input type="text" id="search-lokasi" placeholder="Cari nama fasilitas...">
                
                <button id="btn-filter" class="back-btn" style="background: #2c3e50;">
                    <i class="fas fa-filter"></i> Filter
                </button>
                <button id="btn-reset" class="back-btn" style="background: #95a5a6;">
                    <i class="fas fa-redo"></i> Reset
                </button>
            </div>
            
            <div class="map-container">
                <div id="map"></div>
            </div>
            
            <div class="legend">
                <div class="legend-item">
                    <div class="color-box" style="background: #3498db;"></div>
                    <span><i class="fas fa-hospital"></i> Puskesmas</span>
                </div>
                <div class="legend-item">
                    <div class="color-box" style="background: #e74c3c;"></div>
                    <span><i class="fas fa-hospital-alt"></i> Rumah Sakit</span>
                </div>
                <div class="legend-item">
                    <div class="color-box" style="background: #2ecc71;"></div>
                    <span><i class="fas fa-stethoscope"></i> Klinik</span>
                </div>
                <div class="legend-item">
                    <div class="color-box" style="background: #f39c12;"></div>
                    <span><i class="fas fa-home"></i> Posyandu</span>
                </div>
                <div class="legend-item">
                    <div class="color-box" style="background: #9b59b6;"></div>
                    <span><i class="fas fa-store"></i> Apotek</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Font Awesome untuk icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- SAFE JAVASCRIPT - PETA SPECIFIC -->
    <script>
    // Peta JavaScript - Isolated Scope
    (function() {
        'use strict';
        
        // Data lokasi statis (bisa diganti dengan data dari PHP/API)
        const locations = [
            {
                id: 1,
                nama: "Puskesmas Jakarta Pusat",
                alamat: "Jl. Kesehatan No. 123, Jakarta Pusat",
                kategori: "puskesmas",
                latitude: -6.2088,
                longitude: 106.8456,
                status: "aktif"
            },
            {
                id: 2,
                nama: "RSUD Jakarta Selatan",
                alamat: "Jl. Rumah Sakit No. 45, Jakarta Selatan",
                kategori: "rumah_sakit",
                latitude: -6.2615,
                longitude: 106.8106,
                status: "aktif"
            },
            {
                id: 3,
                nama: "Klinik Sehat Bogor",
                alamat: "Jl. Klinik No. 7, Bogor",
                kategori: "klinik",
                latitude: -6.5971,
                longitude: 106.8060,
                status: "aktif"
            },
            {
                id: 4,
                nama: "Posyandu Melati",
                alamat: "Jl. Melati No. 3, Depok",
                kategori: "posyandu",
                latitude: -6.4024,
                longitude: 106.7942,
                status: "aktif"
            },
            {
                id: 5,
                nama: "Apotek Sehat Tangerang",
                alamat: "Jl. Apotek No. 12, Tangerang",
                kategori: "apotek",
                latitude: -6.1774,
                longitude: 106.6302,
                status: "aktif"
            },
            {
                id: 6,
                nama: "Puskesmas Bekasi Barat",
                alamat: "Jl. Bekasi No. 99, Bekasi",
                kategori: "puskesmas",
                latitude: -6.2349,
                longitude: 107.0005,
                status: "aktif"
            }
        ];
        
        // Variabel global dalam scope
        let map = null;
        let markers = [];
        let currentFilter = '';
        let currentSearch = '';
        
        // Warna berdasarkan kategori
        const categoryColors = {
            'puskesmas': '#3498db',
            'rumah_sakit': '#e74c3c',
            'klinik': '#2ecc71',
            'posyandu': '#f39c12',
            'apotek': '#9b59b6',
            'default': '#7f8c8d'
        };
        
        // Icon berdasarkan kategori
        const categoryIcons = {
            'puskesmas': 'hospital',
            'rumah_sakit': 'hospital-alt',
            'klinik': 'stethoscope',
            'posyandu': 'home',
            'apotek': 'store',
            'default': 'map-marker'
        };
        
        // Nama kategori lengkap
        const categoryNames = {
            'puskesmas': 'Puskesmas',
            'rumah_sakit': 'Rumah Sakit',
            'klinik': 'Klinik',
            'posyandu': 'Posyandu',
            'apotek': 'Apotek',
            'default': 'Lainnya'
        };
        
        // Fungsi inisialisasi peta
        function initMap() {
            // Default center (Jakarta)
            const defaultCenter = [-6.2088, 106.8456];
            
            // Buat peta
            map = L.map('map').setView(defaultCenter, 10);
            
            // Tambahkan tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors | SIRAGA System',
                maxZoom: 19
            }).addTo(map);
            
            // Tambahkan marker untuk setiap lokasi
            addMarkersToMap(locations);
            
            // Update statistik
            updateStats();
        }
        
        // Fungsi untuk menambahkan marker ke peta
        function addMarkersToMap(locList) {
            // Hapus marker lama
            markers.forEach(marker => map.removeLayer(marker));
            markers = [];
            
            // Tambahkan marker baru
            locList.forEach(loc => {
                const marker = L.marker([loc.latitude, loc.longitude], {
                    title: loc.nama
                });
                
                // Buat popup content
                const popupContent = `
                    <div style="min-width: 250px;">
                        <div style="background: ${categoryColors[loc.kategori] || categoryColors.default}; color: white; padding: 10px; border-radius: 5px 5px 0 0;">
                            <h3 style="margin: 0; font-size: 16px;">
                                <i class="fas fa-${categoryIcons[loc.kategori] || categoryIcons.default}"></i> ${escapeHtml(loc.nama)}
                            </h3>
                        </div>
                        <div style="padding: 10px;">
                            <p style="margin: 5px 0;"><strong>Alamat:</strong> ${escapeHtml(loc.alamat)}</p>
                            <p style="margin: 5px 0;"><strong>Kategori:</strong> ${categoryNames[loc.kategori] || categoryNames.default}</p>
                            <p style="margin: 5px 0;"><strong>Status:</strong> <span style="color: ${loc.status === 'aktif' ? '#27ae60' : '#e74c3c'}">${loc.status === 'aktif' ? 'Aktif' : 'Nonaktif'}</span></p>
                            <p style="margin: 5px 0;"><strong>Koordinat:</strong><br>${loc.latitude.toFixed(6)}, ${loc.longitude.toFixed(6)}</p>
                            <div style="margin-top: 10px; display: flex; gap: 5px;">
                                <button onclick="showDetail(${loc.id})" style="background: #3498db; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">
                                    <i class="fas fa-info-circle"></i> Detail
                                </button>
                                <button onclick="showNavigation(${loc.latitude}, ${loc.longitude})" style="background: #2ecc71; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">
                                    <i class="fas fa-directions"></i> Navigasi
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                
                marker.bindPopup(popupContent);
                marker.addTo(map);
                markers.push(marker);
            });
            
            // Fit bounds jika ada marker
            if (markers.length > 0) {
                const group = new L.featureGroup(markers);
                map.fitBounds(group.getBounds().pad(0.1));
            }
        }
        
        // Fungsi untuk filter lokasi
        function filterLocations() {
            const categoryFilter = document.getElementById('kategori-filter').value;
            const searchFilter = document.getElementById('search-lokasi').value.toLowerCase();
            
            currentFilter = categoryFilter;
            currentSearch = searchFilter;
            
            let filteredLocations = locations;
            
            // Filter berdasarkan kategori
            if (categoryFilter) {
                filteredLocations = filteredLocations.filter(loc => loc.kategori === categoryFilter);
            }
            
            // Filter berdasarkan pencarian
            if (searchFilter) {
                filteredLocations = filteredLocations.filter(loc => 
                    loc.nama.toLowerCase().includes(searchFilter) ||
                    loc.alamat.toLowerCase().includes(searchFilter)
                );
            }
            
            // Update peta dengan lokasi yang difilter
            addMarkersToMap(filteredLocations);
            updateStats(filteredLocations);
        }
        
        // Fungsi untuk reset filter
        function resetFilter() {
            document.getElementById('kategori-filter').value = '';
            document.getElementById('search-lokasi').value = '';
            currentFilter = '';
            currentSearch = '';
            
            addMarkersToMap(locations);
            updateStats(locations);
        }
        
        // Fungsi untuk update statistik
        function updateStats(locList = locations) {
            const total = locList.length;
            const active = locList.filter(loc => loc.status === 'aktif').length;
            
            document.getElementById('total-lokasi').textContent = total;
            document.getElementById('lokasi-aktif').textContent = active;
            document.getElementById('jumlah-marker').textContent = markers.length;
        }
        
        // Fungsi untuk escape HTML (mencegah XSS)
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Fungsi untuk menampilkan detail lokasi
        window.showDetail = function(id) {
            const loc = locations.find(l => l.id === id);
            if (loc) {
                alert(`Detail Fasilitas:\n\nNama: ${loc.nama}\nAlamat: ${loc.alamat}\nKategori: ${categoryNames[loc.kategori]}\nStatus: ${loc.status}\nKoordinat: ${loc.latitude}, ${loc.longitude}`);
            }
        };
        
        // Fungsi untuk navigasi
        window.showNavigation = function(lat, lng) {
            const confirmNav = confirm('Buka Google Maps untuk navigasi ke lokasi ini?');
            if (confirmNav) {
                window.open(`https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`, '_blank');
            }
        };
        
        // Fungsi untuk export data
        function exportData() {
            try {
                const dataToExport = locations.map(loc => ({
                    'Nama Fasilitas': loc.nama,
                    'Alamat': loc.alamat,
                    'Kategori': categoryNames[loc.kategori] || categoryNames.default,
                    'Latitude': loc.latitude,
                    'Longitude': loc.longitude,
                    'Status': loc.status === 'aktif' ? 'Aktif' : 'Nonaktif'
                }));
                
                const csvContent = "data:text/csv;charset=utf-8," 
                    + "Nama Fasilitas,Alamat,Kategori,Latitude,Longitude,Status\n"
                    + dataToExport.map(e => Object.values(e).join(",")).join("\n");
                
                const encodedUri = encodeURI(csvContent);
                const link = document.createElement("a");
                link.setAttribute("href", encodedUri);
                link.setAttribute("download", `data_fasilitas_${new Date().toISOString().slice(0,10)}.csv`);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                alert('Data berhasil diekspor ke CSV!');
            } catch (error) {
                console.error('Export error:', error);
                alert('Gagal mengekspor data. Silakan coba lagi.');
            }
        }
        
        // Setup event listeners
        function setupEventListeners() {
            // Filter button
            document.getElementById('btn-filter').addEventListener('click', filterLocations);
            
            // Reset button
            document.getElementById('btn-reset').addEventListener('click', resetFilter);
            
            // Enter key pada search input
            document.getElementById('search-lokasi').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    filterLocations();
                }
            });
            
            // Export button (jika ada)
            const exportBtn = document.createElement('button');
            exportBtn.className = 'back-btn';
            exportBtn.style.background = '#27ae60';
            exportBtn.innerHTML = '<i class="fas fa-download"></i> Export Data';
            exportBtn.addEventListener('click', exportData);
            
            // Tambahkan export button ke filter area
            document.querySelector('.filter').appendChild(exportBtn);
            
            // Responsive map resize
            window.addEventListener('resize', function() {
                if (map) {
                    map.invalidateSize();
                }
            });
        }
        
        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initMap();
            setupEventListeners();
            console.log('Peta JS loaded successfully');
        });
        
    })(); // Immediately Invoked Function Expression
    </script>
</body>
</html><?php
// peta.php - WITH DATABASE CONNECTION
session_start();

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Koneksi ke database
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'siraga_db1';

// Buat koneksi
$conn = new mysqli($host, $username, $password, $database);

// Cek koneksi
if ($conn->connect_error) {
    // Jika koneksi gagal, gunakan data dummy
    $locations = getDummyData();
    $error = "Koneksi database gagal: " . $conn->connect_error;
} else {
    // Set charset
    $conn->set_charset("utf8mb4");
    
    // Query untuk mengambil data lokasi
    $sql = "SELECT id, nama_lokasi, alamat, latitude, longitude, kategori, status 
            FROM locations 
            WHERE status = 'aktif' 
            ORDER BY nama_lokasi";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $locations = [];
        while($row = $result->fetch_assoc()) {
            $locations[] = [
                'id' => (int)$row['id'],
                'nama' => htmlspecialchars($row['nama_lokasi']),
                'alamat' => htmlspecialchars($row['alamat']),
                'kategori' => htmlspecialchars($row['kategori']),
                'latitude' => floatval($row['latitude']),
                'longitude' => floatval($row['longitude']),
                'status' => htmlspecialchars($row['status'])
            ];
        }
    } else {
        // Jika tidak ada data, gunakan data dummy
        $locations = getDummyData();
        if ($result) {
            $warning = "Tidak ada data lokasi ditemukan di database.";
        } else {
            $error = "Error query: " . $conn->error;
        }
    }
    
    $conn->close();
}

// Fungsi data dummy untuk fallback
function getDummyData() {
    return [
        [
            'id' => 1,
            'nama' => "Puskesmas Jakarta Pusat (Demo)",
            'alamat' => "Jl. Kesehatan No. 123, Jakarta Pusat",
            'kategori' => "puskesmas",
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'status' => "aktif"
        ],
        [
            'id' => 2,
            'nama' => "RSUD Jakarta Selatan (Demo)",
            'alamat' => "Jl. Rumah Sakit No. 45, Jakarta Selatan",
            'kategori' => "rumah_sakit",
            'latitude' => -6.2615,
            'longitude' => 106.8106,
            'status' => "aktif"
        ]
    ];
}

// Konversi data ke JSON untuk JavaScript
$locations_json = json_encode($locations, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peta Sebaran - SIRAGA</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            background: #f5f7fa; 
        }
        .container { 
            max-width: 1400px; 
            margin: 20px auto; 
            background: white; 
            border-radius: 10px; 
            overflow: hidden; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
        }
        .header { 
            background: linear-gradient(to right, #3498db, #2c3e50);
            color: white; 
            padding: 30px; 
            text-align: center;
        }
        .header h1 { 
            margin: 0; 
            font-size: 28px;
        }
        .nav { 
            background: #34495e; 
            padding: 0 20px;
            display: flex;
            flex-wrap: wrap;
        }
        .nav a { 
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 15px 20px; 
            color: white; 
            text-decoration: none; 
            transition: background 0.3s;
        }
        .nav a:hover { 
            background: #2c3e50; 
        }
        .nav a.active {
            background: #3498db;
        }
        .content { 
            padding: 30px; 
        }
        .back-btn { 
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px; 
            padding: 10px 20px; 
            background: #3498db; 
            color: white; 
            text-decoration: none; 
            border-radius: 5px; 
            border: none;
            cursor: pointer;
        }
        .filter { 
            margin: 20px 0; 
            padding: 20px; 
            background: #f8f9fa; 
            border-radius: 8px; 
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        .filter select, .filter input {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            background: white;
            min-width: 200px;
        }
        .map-container { 
            background: #f8f9fa; 
            padding: 20px; 
            border-radius: 8px; 
            margin: 20px 0; 
            border: 1px solid #e0e0e0;
        }
        #map { 
            height: 600px; 
            width: 100%; 
            border-radius: 8px;
        }
        .stats { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 15px; 
            margin: 20px 0; 
        }
        .stat-card { 
            background: white; 
            padding: 20px; 
            border-radius: 10px; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.05); 
            text-align: center; 
            border-top: 4px solid #3498db; 
        }
        .info-box {
            background: #e8f4fc;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin: 20px 0;
            border-radius: 6px;
        }
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 6px;
            color: #856404;
        }
        .error-box {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 20px 0;
            border-radius: 6px;
            color: #721c24;
        }
        @media (max-width: 768px) {
            .content { padding: 20px; }
            #map { height: 400px; }
            .filter { flex-direction: column; align-items: stretch; }
            .filter select, .filter input { min-width: 100%; }
        }
    </style>
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🗺️ Peta Sebaran Fasilitas Kesehatan - SIRAGA</h1>
            <p>Sistem Informasi Geografis Monitoring Fasilitas Kesehatan</p>
        </div>
        
        <div class="nav">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="statistik.php"><i class="fas fa-chart-bar"></i> Statistik</a>
            <a href="imunisasi.php"><i class="fas fa-syringe"></i> Imunisasi</a>
            <a href="laporan.php"><i class="fas fa-file"></i> Laporan</a>
            <a href="peta.php" class="active"><i class="fas fa-map"></i> Peta Sebaran</a>
            <a href="logout.php" style="margin-left: auto; background: #e74c3c;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
        
        <div class="content">
            <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>
            
            <?php if (isset($error)): ?>
            <div class="error-box">
                <i class="fas fa-exclamation-circle"></i> <strong>Error Database:</strong> <?php echo $error; ?>
                <p style="margin-top: 10px; font-size: 14px;">Menampilkan data demo. Periksa koneksi database.</p>
            </div>
            <?php endif; ?>
            
            <?php if (isset($warning)): ?>
            <div class="warning-box">
                <i class="fas fa-exclamation-triangle"></i> <strong>Peringatan:</strong> <?php echo $warning; ?>
                <p style="margin-top: 10px; font-size: 14px;">Menampilkan data demo.</p>
            </div>
            <?php endif; ?>
            
            <div class="info-box">
                <i class="fas fa-info-circle"></i> 
                <?php if (isset($error) || isset($warning)): ?>
                Menampilkan data demo. 
                <?php else: ?>
                Data diambil dari database.
                <?php endif; ?>
                Total: <strong><?php echo count($locations); ?> fasilitas</strong>
            </div>
            
            <div class="stats">
                <div class="stat-card">
                    <h3><i class="fas fa-hospital"></i> Total Fasilitas</h3>
                    <div class="value" id="total-lokasi"><?php echo count($locations); ?></div>
                </div>
                <div class="stat-card">
                    <h3><i class="fas fa-check-circle"></i> Aktif</h3>
                    <div class="value" id="lokasi-aktif"><?php 
                        $active = array_filter($locations, function($loc) {
                            return $loc['status'] === 'aktif';
                        });
                        echo count($active);
                    ?></div>
                </div>
                <div class="stat-card">
                    <h3><i class="fas fa-map-marker-alt"></i> Marker</h3>
                    <div class="value" id="jumlah-marker"><?php echo count($locations); ?></div>
                </div>
            </div>
            
            <div class="filter">
                <select id="kategori-filter">
                    <option value="">Semua Kategori</option>
                    <option value="puskesmas">Puskesmas</option>
                    <option value="rumah_sakit">Rumah Sakit</option>
                    <option value="klinik">Klinik</option>
                    <option value="posyandu">Posyandu</option>
                    <option value="apotek">Apotek</option>
                    <option value="kantor">Kantor</option>
                    <option value="gudang">Gudang</option>
                    <option value="pabrik">Pabrik</option>
                </select>
                
                <input type="text" id="search-lokasi" placeholder="Cari nama fasilitas...">
                
                <button id="btn-filter" class="back-btn" style="background: #2c3e50;">
                    <i class="fas fa-filter"></i> Filter
                </button>
                <button id="btn-reset" class="back-btn" style="background: #95a5a6;">
                    <i class="fas fa-redo"></i> Reset
                </button>
                <button id="btn-export" class="back-btn" style="background: #27ae60;">
                    <i class="fas fa-download"></i> Export
                </button>
            </div>
            
            <div class="map-container">
                <div id="map"></div>
            </div>
        </div>
    </div>
    
    <!-- Font Awesome untuk icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- SAFE JAVASCRIPT - PETA SPECIFIC -->
    <script>
    // Peta JavaScript - Isolated Scope
    (function() {
        'use strict';
        
        // Data lokasi dari PHP (database)
        const locations = <?php echo $locations_json; ?>;
        
        console.log('Data dari database:', locations);
        console.log('Jumlah data:', locations.length);
        
        // Variabel global dalam scope
        let map = null;
        let markers = [];
        
        // Warna berdasarkan kategori
        const categoryColors = {
            'puskesmas': '#3498db',
            'rumah_sakit': '#e74c3c',
            'klinik': '#2ecc71',
            'posyandu': '#f39c12',
            'apotek': '#9b59b6',
            'kantor': '#34495e',
            'gudang': '#d35400',
            'pabrik': '#8e44ad',
            'default': '#7f8c8d'
        };
        
        // Icon berdasarkan kategori
        const categoryIcons = {
            'puskesmas': 'hospital',
            'rumah_sakit': 'hospital-alt',
            'klinik': 'stethoscope',
            'posyandu': 'home',
            'apotek': 'store',
            'kantor': 'building',
            'gudang': 'warehouse',
            'pabrik': 'industry',
            'default': 'map-marker'
        };
        
        // Nama kategori lengkap
        const categoryNames = {
            'puskesmas': 'Puskesmas',
            'rumah_sakit': 'Rumah Sakit',
            'klinik': 'Klinik',
            'posyandu': 'Posyandu',
            'apotek': 'Apotek',
            'kantor': 'Kantor',
            'gudang': 'Gudang',
            'pabrik': 'Pabrik',
            'default': 'Lainnya'
        };
        
        // Fungsi inisialisasi peta
        function initMap() {
            // Default center (Jakarta) atau rata-rata dari data
            let defaultCenter = [-6.2088, 106.8456]; // Jakarta default
            
            // Jika ada data, hitung rata-rata koordinat
            if (locations.length > 0) {
                let totalLat = 0;
                let totalLng = 0;
                let validCount = 0;
                
                locations.forEach(loc => {
                    if (loc.latitude && loc.longitude) {
                        totalLat += loc.latitude;
                        totalLng += loc.longitude;
                        validCount++;
                    }
                });
                
                if (validCount > 0) {
                    defaultCenter = [totalLat / validCount, totalLng / validCount];
                }
            }
            
            // Buat peta
            map = L.map('map').setView(defaultCenter, 10);
            
            // Tambahkan tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors | SIRAGA System',
                maxZoom: 19
            }).addTo(map);
            
            // Tambahkan marker untuk setiap lokasi
            addMarkersToMap(locations);
            
            // Update statistik
            updateStats(locations);
        }
        
        // Fungsi untuk menambahkan marker ke peta
        function addMarkersToMap(locList) {
            // Hapus marker lama
            markers.forEach(marker => map.removeLayer(marker));
            markers = [];
            
            // Filter hanya yang punya koordinat valid
            const validLocations = locList.filter(loc => 
                loc.latitude && loc.longitude && 
                !isNaN(loc.latitude) && !isNaN(loc.longitude)
            );
            
            console.log('Valid locations for map:', validLocations.length);
            
            // Tambahkan marker baru
            validLocations.forEach(loc => {
                try {
                    const marker = L.marker([loc.latitude, loc.longitude], {
                        title: loc.nama
                    });
                    
                    // Buat popup content
                    const popupContent = `
                        <div style="min-width: 250px; max-width: 300px;">
                            <div style="background: ${categoryColors[loc.kategori] || categoryColors.default}; color: white; padding: 10px; border-radius: 5px 5px 0 0;">
                                <h3 style="margin: 0; font-size: 16px;">
                                    <i class="fas fa-${categoryIcons[loc.kategori] || categoryIcons.default}"></i> ${loc.nama}
                                </h3>
                            </div>
                            <div style="padding: 10px;">
                                <p style="margin: 5px 0; font-size: 13px;"><strong><i class="fas fa-map-marker-alt"></i> Alamat:</strong><br>${loc.alamat}</p>
                                <p style="margin: 5px 0; font-size: 13px;"><strong><i class="fas fa-tag"></i> Kategori:</strong> ${categoryNames[loc.kategori] || categoryNames.default}</p>
                                <p style="margin: 5px 0; font-size: 13px;"><strong><i class="fas fa-circle"></i> Status:</strong> <span style="color: ${loc.status === 'aktif' ? '#27ae60' : '#e74c3c'}">${loc.status === 'aktif' ? 'Aktif' : 'Nonaktif'}</span></p>
                                <p style="margin: 5px 0; font-size: 13px;"><strong><i class="fas fa-compass"></i> Koordinat:</strong><br>${loc.latitude.toFixed(6)}, ${loc.longitude.toFixed(6)}</p>
                                <div style="margin-top: 10px; display: flex; gap: 5px; flex-wrap: wrap;">
                                    <button onclick="showDetail(${loc.id})" style="background: #3498db; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; font-size: 12px;">
                                        <i class="fas fa-info-circle"></i> Detail
                                    </button>
                                    <button onclick="showNavigation(${loc.latitude}, ${loc.longitude})" style="background: #2ecc71; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; font-size: 12px;">
                                        <i class="fas fa-directions"></i> Navigasi
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    marker.bindPopup(popupContent);
                    marker.addTo(map);
                    markers.push(marker);
                } catch (error) {
                    console.error('Error creating marker for location:', loc, error);
                }
            });
            
            // Fit bounds jika ada marker
            if (markers.length > 0) {
                try {
                    const group = new L.featureGroup(markers);
                    map.fitBounds(group.getBounds().pad(0.1));
                } catch (error) {
                    console.error('Error fitting bounds:', error);
                }
            }
            
            // Update jumlah marker di statistik
            document.getElementById('jumlah-marker').textContent = markers.length;
        }
        
        // Fungsi untuk filter lokasi
        function filterLocations() {
            const categoryFilter = document.getElementById('kategori-filter').value;
            const searchFilter = document.getElementById('search-lokasi').value.toLowerCase();
            
            let filteredLocations = locations;
            
            // Filter berdasarkan kategori
            if (categoryFilter) {
                filteredLocations = filteredLocations.filter(loc => loc.kategori === categoryFilter);
            }
            
            // Filter berdasarkan pencarian
            if (searchFilter) {
                filteredLocations = filteredLocations.filter(loc => 
                    loc.nama.toLowerCase().includes(searchFilter) ||
                    loc.alamat.toLowerCase().includes(searchFilter)
                );
            }
            
            // Update peta dengan lokasi yang difilter
            addMarkersToMap(filteredLocations);
            updateStats(filteredLocations);
        }
        
        // Fungsi untuk reset filter
        function resetFilter() {
            document.getElementById('kategori-filter').value = '';
            document.getElementById('search-lokasi').value = '';
            
            addMarkersToMap(locations);
            updateStats(locations);
        }
        
        // Fungsi untuk update statistik
        function updateStats(locList = locations) {
            const total = locList.length;
            const active = locList.filter(loc => loc.status === 'aktif').length;
            
            document.getElementById('total-lokasi').textContent = total;
            document.getElementById('lokasi-aktif').textContent = active;
        }
        
        // Fungsi untuk menampilkan detail lokasi
        window.showDetail = function(id) {
            const loc = locations.find(l => l.id === id);
            if (loc) {
                alert(`📋 Detail Fasilitas Kesehatan\n\n🏥 Nama: ${loc.nama}\n📍 Alamat: ${loc.alamat}\n🏷️ Kategori: ${categoryNames[loc.kategori]}\n✅ Status: ${loc.status}\n🌐 Koordinat: ${loc.latitude}, ${loc.longitude}\n🆔 ID: ${loc.id}`);
            } else {
                alert('Data lokasi tidak ditemukan.');
            }
        };
        
        // Fungsi untuk navigasi
        window.showNavigation = function(lat, lng) {
            const confirmNav = confirm('Buka Google Maps untuk navigasi ke lokasi ini?');
            if (confirmNav) {
                window.open(`https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`, '_blank', 'noopener,noreferrer');
            }
        };
        
        // Fungsi untuk export data
        function exportData() {
            try {
                const dataToExport = locations.map(loc => ({
                    'Nama Fasilitas': loc.nama,
                    'Alamat': loc.alamat,
                    'Kategori': categoryNames[loc.kategori] || categoryNames.default,
                    'Latitude': loc.latitude,
                    'Longitude': loc.longitude,
                    'Status': loc.status === 'aktif' ? 'Aktif' : 'Nonaktif'
                }));
                
                // Buat CSV
                const headers = Object.keys(dataToExport[0]).join(',');
                const rows = dataToExport.map(e => 
                    Object.values(e).map(v => 
                        typeof v === 'string' ? `"${v.replace(/"/g, '""')}"` : v
                    ).join(',')
                ).join('\n');
                
                const csvContent = headers + '\n' + rows;
                
                // Buat blob dan download
                const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement("a");
                const url = URL.createObjectURL(blob);
                
                link.setAttribute("href", url);
                link.setAttribute("download", `data_fasilitas_${new Date().toISOString().slice(0,10)}.csv`);
                link.style.visibility = 'hidden';
                
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                // Tampilkan notifikasi
                showNotification('success', 'Data berhasil diekspor!', 'File CSV telah diunduh.');
            } catch (error) {
                console.error('Export error:', error);
                showNotification('error', 'Gagal mengekspor data', 'Silakan coba lagi.');
            }
        }
        
        // Fungsi notifikasi
        function showNotification(type, title, message) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                background: ${type === 'success' ? '#27ae60' : '#e74c3c'};
                color: white;
                border-radius: 8px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
                z-index: 9999;
                min-width: 300px;
                max-width: 400px;
                animation: slideIn 0.3s ease;
            `;
            
            notification.innerHTML = `
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                    <div>
                        <strong style="display: block; margin-bottom: 5px;">${title}</strong>
                        <div style="font-size: 14px;">${message}</div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Hapus setelah 5 detik
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 5000);
        }
        
        // Setup event listeners
        function setupEventListeners() {
            // Filter button
            document.getElementById('btn-filter').addEventListener('click', filterLocations);
            
            // Reset button
            document.getElementById('btn-reset').addEventListener('click', resetFilter);
            
            // Export button
            document.getElementById('btn-export').addEventListener('click', exportData);
            
            // Enter key pada search input
            document.getElementById('search-lokasi').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    filterLocations();
                }
            });
            
            // Responsive map resize
            window.addEventListener('resize', function() {
                if (map) {
                    setTimeout(() => map.invalidateSize(), 100);
                }
            });
        }
        
        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initMap();
            setupEventListeners();
            
            // Tambahkan style untuk animasi notifikasi
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes slideOut {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100%); opacity: 0; }
                }
            `;
            document.head.appendChild(style);
            
            console.log('Peta SIRAGA loaded successfully');
            console.log('Total data from database:', locations.length);
        });
        
    })(); // Immediately Invoked Function Expression
    </script>
</body>
</html>