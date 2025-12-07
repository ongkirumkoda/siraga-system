<?php
// database/setup.php
session_start();

// Hanya admin yang boleh akses
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    die("<h2 style='color:red; padding:20px;'>⚠️ HANYA ADMIN YANG BOLEH AKSES!</h2>
         <p>Login sebagai admin dulu: <a href='../index.php'>Login</a></p>");
}

// Koneksi MySQL
$conn = @mysqli_connect('localhost', 'root', '');

if (!$conn) {
    die("<h2 style='color:red;'>❌ KONEKSI MYSQL GAGAL!</h2>
         <p>Pastikan XAMPP MySQL berjalan.</p>");
}

echo "<h1>🔧 SETUP DATABASE SIRAGA</h1>";

// 1. BUAT DATABASE
$sql = "CREATE DATABASE IF NOT EXISTS siraga_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
mysqli_query($conn, $sql);
mysqli_select_db($conn, 'siraga_db');

// 2. BUAT TABEL USERS
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','government','nakes','parent') NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $sql);

// 3. INSERT USER DEMO
$check = mysqli_query($conn, "SELECT COUNT(*) as count FROM users");
$row = mysqli_fetch_assoc($check);

if ($row['count'] == 0) {
    $demoUsers = [
        ['ongkiid81@gmail.com', 'Administrator SIRAGA', '#Rumkoda@73', 'admin', '087832608497'],
        ['gov@siraga.com', 'Admin Pemerintah', 'gov123', 'government', '081234567890'],
        ['nakes@siraga.com', 'dr. Andi Pratama', 'nakes123', 'nakes', '081298765432'],
        ['parent@siraga.com', 'Budi Santoso', 'parent123', 'parent', '081312345678']
    ];
    
    foreach ($demoUsers as $user) {
        $stmt = mysqli_prepare($conn, 
            "INSERT INTO users (email, name, password, role, phone) 
             VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'sssss', $user[0], $user[1], $user[2], $user[3], $user[4]);
        mysqli_stmt_execute($stmt);
    }
    
    echo "<p>✅ Database berhasil dibuat dengan 4 user demo.</p>";
} else {
    echo "<p>✅ Database sudah ada dengan {$row['count']} user.</p>";
}

echo "<p><a href='../dashboard.php'>Kembali ke Dashboard</a></p>";
mysqli_close($conn);
?>