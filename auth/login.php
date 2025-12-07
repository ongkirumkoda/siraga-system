<?php
// auth/login.php - FIXED 100% WORKING VERSION
session_start();

// HAPUS SEMUA SESSION LAMA AGAR FRESH
session_unset();

// SET DEBUG MODE
error_reporting(E_ALL);
ini_set('display_errors', 1);

// SET WAKTU INDONESIA
date_default_timezone_set('Asia/Jakarta');

// LOG REQUEST
error_log("=== LOGIN ATTEMPT ===");
error_log("Time: " . date('Y-m-d H:i:s'));
error_log("IP: " . $_SERVER['REMOTE_ADDR']);

// AMBIL DATA POST
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$selectedRole = isset($_POST['role']) ? trim($_POST['role']) : '';

// LOG DATA
error_log("Email: $email");
error_log("Role selected: $selectedRole");
error_log("Password length: " . strlen($password));

// VALIDASI 1: CEK METODE REQUEST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("ERROR: Bukan request POST");
    $_SESSION['login_error'] = 'Metode request tidak valid!';
    header('Location: ../index.php');
    exit;
}

// VALIDASI 2: CEK DATA KOSONG
if (empty($email) || empty($password) || empty($selectedRole)) {
    error_log("ERROR: Data kosong");
    $_SESSION['login_error'] = 'Semua field harus diisi!';
    header('Location: ../index.php');
    exit;
}

// DATABASE DEMO - SEMUA AKUN
$users = [
    // ADMIN (AKUN ANDA)
    'ongkiid81@gmail.com' => [
        'password' => '#Rumkoda@73',
        'name' => 'Administrator SIRAGA',
        'role' => 'admin',
        'id' => 99
    ],
    // PEMERINTAH
    'gov@siraga.com' => [
        'password' => 'gov123',
        'name' => 'Admin Pemerintah',
        'role' => 'government', 
        'id' => 1
    ],
    // TENAGA KESEHATAN (NAKES) - FIXED
    'nakes@siraga.com' => [
        'password' => 'nakes123',
        'name' => 'dr. Andi Pratama',
        'role' => 'nakes',  // PASTIKAN 'nakes' BUKAN 'health' ATAU LAINNYA
        'id' => 2
    ],
    // ORANG TUA
    'parent@siraga.com' => [
        'password' => 'parent123',
        'name' => 'Budi Santoso',
        'role' => 'parent',
        'id' => 3
    ]
];

// VALIDASI 3: CEK EMAIL TERDAFTAR
if (!isset($users[$email])) {
    error_log("ERROR: Email tidak terdaftar: $email");
    $_SESSION['login_error'] = 'Email tidak terdaftar dalam sistem!';
    header('Location: ../index.php');
    exit;
}

$userData = $users[$email];
error_log("User found: " . $userData['name'] . " with role: " . $userData['role']);

// VALIDASI 4: CEK PASSWORD
if ($userData['password'] !== $password) {
    error_log("ERROR: Password salah untuk: $email");
    $_SESSION['login_error'] = 'Password salah!';
    header('Location: ../index.php');
    exit;
}

// VALIDASI 5: CEK ROLE COCOK - CASE SENSITIVE CHECK
if ($userData['role'] !== $selectedRole) {
    error_log("ERROR: Role mismatch. User role: " . $userData['role'] . ", Selected: $selectedRole");
    $_SESSION['login_error'] = 'Role tidak sesuai! Akun ini untuk: ' . $userData['role'];
    header('Location: ../index.php');
    exit;
}

// === LOGIN BERHASIL ===
// BUAT SESSION YANG KONSISTEN
$_SESSION['user'] = [
    'id' => $userData['id'],
    'email' => $email,
    'name' => $userData['name'],
    'role' => $userData['role'],  // PASTIKAN KEY 'role' BUKAN 'user_role'
    'login_time' => date('Y-m-d H:i:s'),
    'login_ip' => $_SERVER['REMOTE_ADDR']
];

// TAMBAH AVATAR JIKA PERLU
$_SESSION['user']['avatar'] = strtoupper(substr($userData['name'], 0, 1));

// SIMPAN LOKASI JIKA ADA
if (isset($_SESSION['userCity']) && !empty($_SESSION['userCity'])) {
    $_SESSION['user']['location'] = $_SESSION['userCity'];
}

// LOG SUCCESS
error_log("SUCCESS: Login berhasil untuk $email sebagai " . $userData['role']);
error_log("Session created: " . print_r($_SESSION['user'], true));

// SET COOKIE TAMBAHAN UNTUK KEAMANAN
$session_id = session_id();
setcookie('siraga_session', $session_id, time() + (86400 * 7), "/"); // 7 hari

// REDIRECT KE DASHBOARD DENGAN PARAMETER VERIFIKASI
$redirect_url = '../dashboard.php?login=success&role=' . urlencode($userData['role']);
error_log("Redirecting to: $redirect_url");

header('Location: ' . $redirect_url);
exit;
?>