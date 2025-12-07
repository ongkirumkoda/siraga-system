<?php
// create_locations.php
// File untuk membuat tabel locations secara otomatis

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'siraga_db';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Membuat Tabel Locations</h2>";

// SQL untuk membuat tabel locations
$sql = "CREATE TABLE IF NOT EXISTS locations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_lokasi VARCHAR(100) NOT NULL,
    alamat TEXT NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    kategori ENUM('kantor', 'gudang', 'toko', 'pabrik', 'lainnya', 'puskesmas', 'rumah_sakit', 'klinik') NOT NULL,
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    deskripsi TEXT,
    user_id INT NULL,
    telepon VARCHAR(20),
    email VARCHAR(100),
    website VARCHAR(200),
    jam_operasional TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_kategori (kategori),
    INDEX idx_status (status),
    INDEX idx_lat_long (latitude, longitude)
) ENGINE=InnoDB";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color: green;'>✅ Tabel 'locations' berhasil dibuat!</p>";
    
    // Insert sample data
    $insertSQL = "INSERT INTO locations (nama_lokasi, alamat, latitude, longitude, kategori, status, deskripsi) VALUES
    ('Kantor Pusat SIRAGA', 'Jl. Sudirman No. 123, Jakarta Pusat', -6.208763, 106.845599, 'kantor', 'aktif', 'Kantor pusat sistem SIRAGA'),
    ('Puskesmas Merdeka', 'Jl. Merdeka No. 45, Jakarta Selatan', -6.261493, 106.810600, 'puskesmas', 'aktif', 'Puskesmas wilayah Jakarta Selatan'),
    ('Rumah Sakit Umum Daerah', 'Jl. Kesehatan No. 56, Tangerang', -6.177434, 106.630241, 'rumah_sakit', 'aktif', 'Rumah sakit umum daerah'),
    ('Klinik Anak Sejahtera', 'Jl. Anak No. 7, Bogor', -6.597147, 106.806038, 'klinik', 'aktif', 'Klinik khusus anak'),
    ('Gudang Obat Sentral', 'Jl. Gatot Subroto No. 78, Jakarta', -6.224699, 106.845703, 'gudang', 'aktif', 'Gudang penyimpanan obat dan vaksin'),
    ('Apotek Sehat', 'Jl. Thamrin No. 12, Jakarta', -6.186486, 106.822999, 'toko', 'aktif', 'Apotek 24 jam'),
    ('Pabrik Vaksin Nasional', 'Jl. Industri No. 99, Bekasi', -6.234899, 107.000500, 'pabrik', 'aktif', 'Pabrik produksi vaksin'),
    ('Posyandu Melati', 'Jl. Melati No. 3, Depok', -6.402445, 106.794243, 'lainnya', 'aktif', 'Posyandu wilayah Depok')";
    
    if ($conn->query($insertSQL) === TRUE) {
        echo "<p style='color: green;'>✅ Data sample berhasil dimasukkan (8 lokasi)</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Data sample gagal: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Error membuat tabel: " . $conn->error . "</p>";
}

// Cek apakah tabel sudah ada
$checkTable = $conn->query("SHOW TABLES LIKE 'locations'");
if ($checkTable->num_rows > 0) {
    echo "<p style='color: green;'>✅ Tabel 'locations' sudah ada di database.</p>";
    
    // Hitung jumlah data
    $countResult = $conn->query("SELECT COUNT(*) as total FROM locations");
    $row = $countResult->fetch_assoc();
    echo "<p>📊 Total data locations: " . $row['total'] . " lokasi</p>";
    
    // Tampilkan data
    $result = $conn->query("SELECT id, nama_lokasi, kategori, status FROM locations LIMIT 10");
    if ($result->num_rows > 0) {
        echo "<h3>Data Locations:</h3>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Nama Lokasi</th><th>Kategori</th><th>Status</th></tr>";
        while($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['nama_lokasi'] . "</td>";
            echo "<td>" . $row['kategori'] . "</td>";
            echo "<td>" . $row['status'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}

$conn->close();
?>

<div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 5px;">
    <h3>📋 Instruksi:</h3>
    <ol>
        <li>Simpan file ini sebagai <code>create_locations.php</code> di folder <code>siraga</code></li>
        <li>Akses melalui browser: <code>http://localhost/siraga/create_locations.php</code></li>
        <li>Setelah berhasil, hapus file ini untuk keamanan</li>
        <li>Akses peta: <code>http://localhost/siraga/modules/government/peta.php</code></li>
    </ol>
    
    <p><strong>Note:</strong> Pastikan database <code>siraga_db</code> sudah ada.</p>
</div>