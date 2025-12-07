<?php
// test_statistik.php
// File ini untuk testing query statistik wilayah

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing Statistik Wilayah</h2>";

// 1. KONEKSI DATABASE
$host = 'localhost';
$db   = 'siraga_db1';
$user = 'root';  // ganti jika berbeda
$pass = '';      // ganti jika ada password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "<p style='color: green;'>✓ Koneksi database BERHASIL</p>";
} catch (PDOException $e) {
    die("<p style='color: red;'>✗ Koneksi database GAGAL: " . $e->getMessage() . "</p>");
}

// 2. CEK STRUKTUR TABEL children
echo "<h3>Cek Kolom Tabel children:</h3>";
try {
    $stmt = $pdo->query("DESCRIBE children");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Cari kolom wilayah
    $wilayah_columns = [];
    foreach ($columns as $col) {
        $field = strtolower($col['Field']);
        if (strpos($field, 'village') !== false || 
            strpos($field, 'district') !== false || 
            strpos($field, 'regency') !== false || 
            strpos($field, 'province') !== false || 
            strpos($field, 'region') !== false ||
            strpos($field, 'desa') !== false ||
            strpos($field, 'kecamatan') !== false ||
            strpos($field, 'kabupaten') !== false) {
            $wilayah_columns[] = $col['Field'];
        }
    }
    
    if (!empty($wilayah_columns)) {
        echo "<p style='color: green;'>✓ Kolom wilayah ditemukan: " . implode(', ', $wilayah_columns) . "</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Tidak ditemukan kolom wilayah yang jelas. Cek manual.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// 3. QUERY TEST SEDERHANA
echo "<h3>Test Query Statistik Dasar:</h3>";

// Query 1: Hitung total anak
try {
    $query1 = "SELECT COUNT(*) as total_anak FROM children";
    $stmt = $pdo->query($query1);
    $result = $stmt->fetch();
    echo "<p>Total anak dalam database: <strong>" . $result['total_anak'] . "</strong></p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error hitung anak: " . $e->getMessage() . "</p>";
}

// Query 2: Cek data pemeriksaan terbaru
try {
    $query2 = "SELECT 
                COUNT(DISTINCT child_id) as anak_diperiksa,
                AVG(height) as rata_tinggi,
                SUM(CASE WHEN height_for_age_zscore < -2 THEN 1 ELSE 0 END) as stunting
               FROM examinations 
               WHERE examination_date = (SELECT MAX(examination_date) FROM examinations)";
    $stmt = $pdo->query($query2);
    $result = $stmt->fetch();
    
    echo "<p>Anak yang sudah diperiksa: <strong>" . $result['anak_diperiksa'] . "</strong></p>";
    echo "<p>Rata-rata tinggi: <strong>" . ($result['rata_tinggi'] ? round($result['rata_tinggi'], 2) : 0) . " cm</strong></p>";
    echo "<p>Kasus stunting: <strong>" . $result['stunting'] . "</strong></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error query pemeriksaan: " . $e->getMessage() . "</p>";
}

// 4. TEST QUERY GROUP BY (coba kolom umum)
echo "<h3>Test Group by Wilayah (mencoba kolom umum):</h3>";

$possible_columns = ['district', 'village', 'region', 'kecamatan', 'desa', 'address'];
$found = false;

foreach ($possible_columns as $col) {
    try {
        $query = "SHOW COLUMNS FROM children LIKE '$col'";
        $stmt = $pdo->query($query);
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✓ Kolom '$col' ditemukan di tabel children</p>";
            
            // Coba query group by
            $groupQuery = "SELECT $col as wilayah, COUNT(*) as jumlah 
                          FROM children 
                          WHERE $col IS NOT NULL AND $col != '' 
                          GROUP BY $col 
                          ORDER BY jumlah DESC 
                          LIMIT 5";
            $stmt2 = $pdo->query($groupQuery);
            $results = $stmt2->fetchAll();
            
            if (!empty($results)) {
                echo "<p>Contoh data untuk kolom <strong>$col</strong>:</p>";
                echo "<table border='1' cellpadding='5'>";
                echo "<tr><th>Wilayah ($col)</th><th>Jumlah Anak</th></tr>";
                foreach ($results as $row) {
                    echo "<tr><td>" . htmlspecialchars($row['wilayah']) . "</td><td>" . $row['jumlah'] . "</td></tr>";
                }
                echo "</table>";
                $found = true;
                break; // Stop setelah menemukan kolom yang berhasil
            }
        }
    } catch (Exception $e) {
        // Kolom tidak ada atau error, lanjut ke berikutnya
    }
}

if (!$found) {
    echo "<p style='color: orange;'>⚠ Tidak bisa melakukan GROUP BY. Mungkin kolom wilayah belum ada atau kosong.</p>";
    echo "<p>Silakan cek struktur tabel children untuk kolom wilayah.</p>";
}

echo "<hr><p><strong>Jika semua test berhasil, kita bisa lanjut buat statistik_wilayah.php lengkap.</strong></p>";
?>