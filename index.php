<?php
// C:\xampp\htdocs\siraga\index.php
// LANDING PAGE SIRAGA - VERSI PROFESIONAL FIXED
session_start();

// Cek jika sudah login
if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}

// Clear login error jika ada
if (isset($_SESSION['login_error'])) {
    $login_error = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
} else {
    $login_error = '';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIRAGA - Sistem Pencatatan Tumbuh Kembang Anak</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2c3e50;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --light: #ecf0f1;
            --dark: #2c3e50;
            --gray: #7f8c8d;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        html {
            scroll-behavior: smooth;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #333;
            line-height: 1.6;
        }
        
        /* HEADER */
        .header {
            background: white;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            padding: 15px 0;
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 30px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }
        
        .logo-icon {
            background: var(--primary);
            color: white;
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        }
        
        .logo-text h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark);
            line-height: 1.2;
        }
        
        .logo-text p {
            font-size: 11px;
            color: var(--gray);
            font-weight: 500;
        }
        
        .nav-menu {
            display: flex;
            gap: 40px;
            align-items: center;
        }
        
        .nav-link {
            color: var(--dark);
            text-decoration: none;
            font-weight: 500;
            font-size: 15px;
            transition: color 0.3s;
            position: relative;
            padding: 5px 0;
        }
        
        .nav-link:hover {
            color: var(--primary);
        }
        
        .nav-link.active {
            color: var(--primary);
        }
        
        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--primary);
            border-radius: 3px;
        }
        
        .btn-login-header {
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.2);
        }
        
        .btn-login-header:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.3);
        }
        
        /* HERO SECTION */
        .hero {
            padding: 160px 30px 100px;
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 60px;
        }
        
        .hero-content {
            flex: 1;
        }
        
        .hero-badge {
            display: inline-block;
            background: rgba(52, 152, 219, 0.1);
            color: var(--primary);
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 20px;
            border: 1px solid rgba(52, 152, 219, 0.2);
        }
        
        .hero-title {
            font-size: 48px;
            font-weight: 700;
            line-height: 1.2;
            color: var(--dark);
            margin-bottom: 20px;
        }
        
        .hero-title span {
            color: var(--primary);
            position: relative;
        }
        
        .hero-title span::after {
            content: '';
            position: absolute;
            bottom: 5px;
            left: 0;
            width: 100%;
            height: 8px;
            background: rgba(52, 152, 219, 0.2);
            z-index: -1;
        }
        
        .hero-subtitle {
            font-size: 18px;
            color: var(--gray);
            margin-bottom: 40px;
            max-width: 600px;
        }
        
        .hero-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
            border: none;
            padding: 14px 30px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            font-size: 15px;
        }
        
        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.3);
        }
        
        .btn-secondary {
            background: white;
            color: var(--dark);
            border: 2px solid var(--light);
            padding: 14px 30px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        
        .btn-secondary:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-2px);
        }
        
        .hero-image {
            flex: 1;
            text-align: center;
        }
        
        .hero-image img {
            max-width: 100%;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
        }
        
        .image-placeholder {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            width: 100%;
            height: 400px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .image-placeholder::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="none" stroke="white" stroke-width="2" stroke-dasharray="5,5"/></svg>');
            opacity: 0.1;
            animation: rotate 20s linear infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .placeholder-content {
            position: relative;
            z-index: 2;
            text-align: center;
            color: white;
        }
        
        .placeholder-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        
        /* FEATURES SECTION */
        .features {
            padding: 100px 30px;
            background: white;
        }
        
        .section-header {
            text-align: center;
            max-width: 800px;
            margin: 0 auto 60px;
        }
        
        .section-subtitle {
            color: var(--gray);
            font-size: 18px;
            margin-top: 15px;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .feature-card {
            background: white;
            padding: 40px 30px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            transition: all 0.3s;
            border: 1px solid #f0f0f0;
            text-align: center;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 50px rgba(0,0,0,0.12);
            border-color: var(--primary);
        }
        
        .feature-icon {
            width: 70px;
            height: 70px;
            border-radius: 20px;
            background: rgba(52, 152, 219, 0.1);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            margin: 0 auto 25px;
        }
        
        .feature-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--dark);
        }
        
        .feature-desc {
            color: var(--gray);
            font-size: 15px;
            line-height: 1.6;
        }
        
        /* LOGIN ROLES SECTION */
        .roles-section {
            padding: 100px 30px;
            background: #f8fafc;
        }
        
        .roles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .role-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 50px rgba(0,0,0,0.08);
            transition: all 0.3s;
        }
        
        .role-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 60px rgba(0,0,0,0.12);
        }
        
        .role-header {
            padding: 30px;
            text-align: center;
            background: linear-gradient(135deg, var(--primary) 0%, #2980b9 100%);
            color: white;
        }
        
        .role-card.government .role-header {
            background: linear-gradient(135deg, #3498db 0%, #2c3e50 100%);
        }
        
        .role-card.nakes .role-header {
            background: linear-gradient(135deg, #27ae60 0%, #219653 100%);
        }
        
        .role-card.parent .role-header {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
        }
        
        .role-icon {
            font-size: 50px;
            margin-bottom: 20px;
        }
        
        .role-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .role-body {
            padding: 30px;
        }
        
        .role-features {
            list-style: none;
            margin-bottom: 30px;
        }
        
        .role-features li {
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .role-features li:last-child {
            border-bottom: none;
        }
        
        .role-features i {
            color: var(--success);
        }
        
        .role-login-btn {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 15px;
        }
        
        .role-login-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        
        /* ADMIN LOGIN SECTION */
        .admin-section {
            padding: 100px 30px;
            background: white;
            text-align: center;
        }
        
        .admin-card {
            max-width: 600px;
            margin: 0 auto;
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 50px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        }
        
        .admin-icon {
            font-size: 60px;
            margin-bottom: 20px;
            color: var(--primary);
        }
        
        /* CONTACT SECTION */
        .contact-section {
            padding: 80px 30px;
            background: #f8fafc;
            text-align: center;
        }
        
        .whatsapp-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #25D366;
            color: white;
            padding: 14px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
            transition: all 0.3s;
        }
        
        .whatsapp-btn:hover {
            background: #128C7E;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 211, 102, 0.3);
        }
        
        /* FOOTER */
        .footer {
            background: var(--dark);
            color: white;
            padding: 60px 30px 30px;
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }
        
        .footer-logo {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 15px;
            color: white;
        }
        
        .footer-about {
            color: rgba(255,255,255,0.7);
            font-size: 14px;
            line-height: 1.6;
        }
        
        .footer-links h4 {
            font-size: 16px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .footer-links ul {
            list-style: none;
        }
        
        .footer-links li {
            margin-bottom: 12px;
        }
        
        .footer-links a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: color 0.3s;
            font-size: 14px;
        }
        
        .footer-links a:hover {
            color: white;
        }
        
        .copyright {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.5);
            font-size: 14px;
        }
        
        /* MODAL */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            width: 90%;
            max-width: 450px;
            border-radius: 20px;
            overflow: hidden;
            animation: modalSlide 0.3s ease;
        }
        
        @keyframes modalSlide {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .modal-header {
            background: var(--primary);
            color: white;
            padding: 25px 30px;
            text-align: center;
        }
        
        .modal-header h2 {
            font-size: 24px;
            font-weight: 600;
        }
        
        .modal-header p {
            opacity: 0.9;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .modal-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .btn-submit {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 15px;
            margin-top: 10px;
        }
        
        .btn-submit:hover {
            background: #2980b9;
        }
        
        .close-modal {
            position: absolute;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
        }
        
        /* SCROLL TO TOP */
        .scroll-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--primary);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: all 0.3s;
            z-index: 1000;
            border: none;
        }
        
        .scroll-top:hover {
            background: #2980b9;
            transform: translateY(-3px);
        }
        
        /* DEMO BUTTONS */
        .demo-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin-top: 15px;
        }
        
        .demo-btn {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .demo-btn:hover {
            background: #e9ecef;
            border-color: var(--primary);
        }
        
        /* ERROR MESSAGE */
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
            font-size: 14px;
        }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            .hero {
                flex-direction: column;
                padding: 140px 20px 60px;
                text-align: center;
            }
            
            .hero-title {
                font-size: 36px;
            }
            
            .nav-menu {
                display: none;
            }
            
            .hero-buttons {
                justify-content: center;
            }
            
            .roles-grid {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                width: 95%;
                margin: 20px;
            }
            
            .demo-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <header class="header">
        <div class="nav-container">
            <a href="#" class="logo">
                <div class="logo-icon">
                    <i class="fas fa-heartbeat"></i>
                </div>
                <div class="logo-text">
                    <h1>SIRAGA</h1>
                    <p>Sistem Pencatatan Tumbuh Anak</p>
                </div>
            </a>
            
            <nav class="nav-menu">
                <a href="#home" class="nav-link active">Beranda</a>
                <a href="#features" class="nav-link">Fitur</a>
                <a href="#roles" class="nav-link">Login</a>
                <a href="#admin" class="nav-link">Administrator</a>
                <a href="#contact" class="nav-link">Kontak</a>
                <button class="btn-login-header" onclick="scrollToSection('roles')">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </nav>
        </div>
    </header>

    <!-- HERO SECTION -->
    <section class="hero" id="home">
        <div class="hero-content">
            <span class="hero-badge">✨ Platform Terbaru 2025</span>
            <h1 class="hero-title">
                Pantau Tumbuh Kembang <span>Anak Indonesia</span>
            </h1>
            <p class="hero-subtitle">
                SIRAGA membantu tenaga kesehatan, pemerintah, dan orang tua dalam memantau 
                perkembangan anak dengan sistem terintegrasi yang mudah digunakan.
            </p>
            <div class="hero-buttons">
                <a href="#roles" class="btn-primary">
                    <i class="fas fa-play-circle"></i> Mulai Sekarang
                </a>
                <a href="#features" class="btn-secondary">
                    <i class="fas fa-info-circle"></i> Pelajari Fitur
                </a>
            </div>
        </div>
        <div class="hero-image">
            <div class="image-placeholder">
                <div class="placeholder-content">
                    <div class="placeholder-icon">
                        <i class="fas fa-child"></i>
                    </div>
                    <h3 style="font-size: 24px; margin-bottom: 10px;">SIRAGA</h3>
                    <p>Sistem Pencatatan Tumbuh Anak</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FEATURES -->
    <section class="features" id="features">
        <div class="section-header">
            <h2 style="font-size: 36px; font-weight: 700; color: var(--dark);">Fitur Unggulan SIRAGA</h2>
            <p class="section-subtitle">
                Platform lengkap untuk monitoring tumbuh kembang anak dari usia 0-5 tahun
            </p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-weight-scale"></i>
                </div>
                <h3 class="feature-title">Pemantauan Pertumbuhan</h3>
                <p class="feature-desc">
                    Grafik tinggi badan, berat badan, dan lingkar kepala sesuai standar WHO.
                    Peringatan otomatis jika ada penyimpangan.
                </p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-syringe"></i>
                </div>
                <h3 class="feature-title">Jadwal Imunisasi</h3>
                <p class="feature-desc">
                    Sistem pengingat imunisasi wajib dan tambahan. Terintegrasi dengan data
                    kesehatan nasional.
                </p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3 class="feature-title">Laporan Real-time</h3>
                <p class="feature-desc">
                    Dashboard statistik untuk pemerintah dan tenaga kesehatan.
                    Analisis data wilayah dan tren perkembangan.
                </p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-mobile-screen"></i>
                </div>
                <h3 class="feature-title">Akses Mobile</h3>
                <p class="feature-desc">
                    Pantau perkembangan anak kapan saja melalui smartphone.
                    Notifikasi real-time untuk orang tua.
                </p>
            </div>
        </div>
    </section>

    <!-- LOGIN ROLES SECTION -->
    <section class="roles-section" id="roles">
        <div class="section-header">
            <h2 style="font-size: 36px; font-weight: 700; color: var(--dark);">Pilih Jenis Login</h2>
            <p class="section-subtitle">
                Masuk sesuai dengan peran Anda untuk mengakses fitur yang tersedia
            </p>
        </div>
        
        <div class="roles-grid">
            <!-- GOVERNMENT -->
            <div class="role-card government">
                <div class="role-header">
                    <div class="role-icon">
                        <i class="fas fa-landmark"></i>
                    </div>
                    <h3 class="role-title">Pemerintah</h3>
                    <p>Monitoring Wilayah & Analisis Data</p>
                </div>
                <div class="role-body">
                    <ul class="role-features">
                        <li><i class="fas fa-check-circle"></i> Data agregat seluruh wilayah</li>
                        <li><i class="fas fa-check-circle"></i> Analisis statistik perkembangan</li>
                        <li><i class="fas fa-check-circle"></i> Laporan cakupan imunisasi</li>
                        <li><i class="fas fa-check-circle"></i> Monitoring status gizi anak</li>
                        <li><i class="fas fa-check-circle"></i> Peta sebaran wilayah</li>
                    </ul>
                    <button class="role-login-btn" onclick="openRoleLogin('government')">
                        <i class="fas fa-sign-in-alt"></i> Login sebagai Pemerintah
                    </button>
                </div>
            </div>
            
            <!-- NAKES -->
            <div class="role-card nakes">
                <div class="role-header">
                    <div class="role-icon">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <h3 class="role-title">Tenaga Kesehatan</h3>
                    <p>Input Data & Pemantauan Pasien</p>
                </div>
                <div class="role-body">
                    <ul class="role-features">
                        <li><i class="fas fa-check-circle"></i> Input data anak baru</li>
                        <li><i class="fas fa-check-circle"></i> Pencatatan imunisasi</li>
                        <li><i class="fas fa-check-circle"></i> Monitoring perkembangan</li>
                        <li><i class="fas fa-check-circle"></i> Jadwal pemeriksaan</li>
                        <li><i class="fas fa-check-circle"></i> Laporan harian/mingguan</li>
                    </ul>
                    <button class="role-login-btn" onclick="openRoleLogin('nakes')">
                        <i class="fas fa-sign-in-alt"></i> Login sebagai Tenaga Kesehatan
                    </button>
                </div>
            </div>
            
            <!-- PARENT -->
            <div class="role-card parent">
                <div class="role-header">
                    <div class="role-icon">
                        <i class="fas fa-home"></i>
                    </div>
                    <h3 class="role-title">Orang Tua</h3>
                    <p>Pantau Perkembangan Anak</p>
                </div>
                <div class="role-body">
                    <ul class="role-features">
                        <li><i class="fas fa-check-circle"></i> Pantau perkembangan anak</li>
                        <li><i class="fas fa-check-circle"></i> Jadwal imunisasi</li>
                        <li><i class="fas fa-check-circle"></i> Grafik pertumbuhan</li>
                        <li><i class="fas fa-check-circle"></i> Notifikasi penting</li>
                        <li><i class="fas fa-check-circle"></i> Riwayat kesehatan</li>
                    </ul>
                    <button class="role-login-btn" onclick="openRoleLogin('parent')">
                        <i class="fas fa-sign-in-alt"></i> Login sebagai Orang Tua
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- ADMIN SECTION -->
    <section class="admin-section" id="admin">
        <div class="admin-card">
            <div class="admin-icon">
                <i class="fas fa-user-shield"></i>
            </div>
            <h2 style="font-size: 32px; margin-bottom: 15px;">Login Administrator</h2>
            <p style="margin-bottom: 30px; opacity: 0.9; font-size: 16px;">
                Akses penuh untuk administrator sistem. Hanya untuk tim pengembang dan super admin.
            </p>
            <button class="btn-primary" onclick="openAdminLogin()" style="background: var(--dark); border: 2px solid var(--primary);">
                <i class="fas fa-lock"></i> Login sebagai Administrator
            </button>
        </div>
    </section>

    <!-- CONTACT SECTION -->
    <section class="contact-section" id="contact">
        <div class="section-header">
            <h2 style="font-size: 36px; font-weight: 700; color: var(--dark);">Butuh Bantuan?</h2>
            <p class="section-subtitle">
                Hubungi tim kami untuk informasi lebih lanjut
            </p>
        </div>
        
        <div style="max-width: 600px; margin: 0 auto; padding: 30px; background: white; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.08);">
            <div style="font-size: 48px; color: var(--primary); margin-bottom: 20px;">
                <i class="fas fa-headset"></i>
            </div>
            <h3 style="font-size: 24px; margin-bottom: 15px;">Tim Support SIRAGA</h3>
            <p style="color: var(--gray); margin-bottom: 20px;">
                Kami siap membantu Anda dari Senin - Jumat, 08:00 - 17:00 WIB.
            </p>
            
            <a href="https://wa.me/6287832608497?text=Halo%20SIRAGA,%20saya%20butuh%20bantuan%20mengenai%20aplikasi" 
               target="_blank" 
               class="whatsapp-btn">
                <i class="fab fa-whatsapp"></i> Chat via WhatsApp: 0878-3260-8497
            </a>
            
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #f0f0f0;">
                <div style="display: grid; grid-template-columns: 1fr; gap: 15px;">
                    <p style="color: var(--gray); font-size: 14px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-envelope" style="color: var(--primary);"></i> 
                        Email: ongkiid81@gmail.com
                    </p>
                    
                    <p style="color: var(--gray); font-size: 14px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-phone" style="color: var(--success);"></i> 
                        Telepon: 0878-3260-8497
                    </p>
                    
                    <p style="color: var(--gray); font-size: 14px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-map-marker-alt" style="color: var(--danger);"></i> 
                        Lokasi: <span id="userLocation">Mendeteksi lokasi...</span>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="footer-content">
            <div>
                <div class="footer-logo">SIRAGA</div>
                <p class="footer-about">
                    Sistem Pencatatan Tumbuh Kembang Anak Indonesia. 
                    Platform terintegrasi untuk monitoring perkembangan anak usia dini.
                </p>
                <div style="margin-top: 15px;">
                    <p style="font-size: 12px; color: rgba(255,255,255,0.6);">
                        <i class="fas fa-envelope"></i> ongkiid81@gmail.com<br>
                        <i class="fab fa-whatsapp"></i> 0878-3260-8497
                    </p>
                </div>
            </div>
            
            <div class="footer-links">
                <h4>Menu</h4>
                <ul>
                    <li><a href="#home">Beranda</a></li>
                    <li><a href="#features">Fitur</a></li>
                    <li><a href="#roles">Login</a></li>
                    <li><a href="#admin">Administrator</a></li>
                    <li><a href="#contact">Kontak</a></li>
                </ul>
            </div>
            
            <div class="footer-links">
                <h4>Kontak Kami</h4>
                <ul>
                    <li><a href="mailto:ongkiid81@gmail.com"><i class="fas fa-envelope"></i> Email</a></li>
                    <li><a href="https://wa.me/6287832608497" target="_blank"><i class="fab fa-whatsapp"></i> WhatsApp</a></li>
                    <li><a href="tel:+6287832608497"><i class="fas fa-phone"></i> Telepon</a></li>
                    <li><a href="#contact" onclick="getUserLocation()"><i class="fas fa-map-marker-alt"></i> Lokasi Anda</a></li>
                </ul>
            </div>
            
            <div class="footer-links">
                <h4>Informasi</h4>
                <ul>
                    <li><a href="#about">Tentang Kami</a></li>
                    <li><a href="#privacy">Kebijakan Privasi</a></li>
                    <li><a href="#terms">Syarat & Ketentuan</a></li>
                    <li><a href="#faq">FAQ</a></li>
                </ul>
            </div>
        </div>
        
        <div class="copyright">
            © 2025 SIRAGA - Sistem Pencatatan Tumbuh Anak.<br>
            Kontak: ongkiid81@gmail.com | WhatsApp: 0878-3260-8497
        </div>
    </footer>

    <!-- SCROLL TO TOP -->
    <button class="scroll-top" onclick="scrollToTop()">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- LOGIN MODAL FOR ROLES -->
    <div id="roleLoginModal" class="modal">
        <div class="modal-content">
            <div class="modal-header" id="roleModalHeader">
                <h2>Login</h2>
                <p id="roleModalSubtitle">Masuk ke sistem</p>
            </div>
            <div class="modal-body">
                <?php if ($login_error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $login_error; ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="auth/login.php" id="roleLoginForm">
                    <input type="hidden" name="role" id="loginRole">
                    
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" id="loginEmail" 
                               placeholder="Masukkan email" required autocomplete="off">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" id="loginPassword" 
                               placeholder="Masukkan password" required autocomplete="off">
                    </div>
                    
                    <button type="submit" class="btn-submit" id="loginSubmitBtn">
                        <i class="fas fa-sign-in-alt"></i> Masuk ke Sistem
                    </button>
                </form>
                
                <!-- TOMBOL DEMO -->
                <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid #eee;">
                    <p style="font-size: 13px; color: #7f8c8d; margin-bottom: 12px; text-align: center;">
                        <i class="fas fa-key"></i> Gunakan akun demo:
                    </p>
                    <div class="demo-buttons">
                        <button type="button" class="demo-btn" onclick="fillDemoCredentials('gov@siraga.com', 'gov123', 'government')">
                            <i class="fas fa-landmark"></i> Pemerintah
                        </button>
                        <button type="button" class="demo-btn" onclick="fillDemoCredentials('nakes@siraga.com', 'nakes123', 'nakes')">
                            <i class="fas fa-user-md"></i> Tenaga Kesehatan
                        </button>
                        <button type="button" class="demo-btn" onclick="fillDemoCredentials('parent@siraga.com', 'parent123', 'parent')">
                            <i class="fas fa-home"></i> Orang Tua
                        </button>
                    </div>
                </div>
            </div>
            <button class="close-modal" onclick="closeRoleLogin()">×</button>
        </div>
    </div>

    <!-- ADMIN LOGIN MODAL -->
    <div id="adminLoginModal" class="modal">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--dark);">
                <h2>Login Administrator</h2>
                <p>Akses khusus untuk administrator sistem</p>
            </div>
            <div class="modal-body">
                <form method="POST" action="auth/login.php" id="adminLoginForm">
                    <input type="hidden" name="role" value="admin">
                    
                    <div class="form-group">
                        <label class="form-label">Email Administrator</label>
                        <input type="email" name="email" class="form-control" id="adminEmail" 
                               value="ongkiid81@gmail.com" required readonly style="background: #f8f9fa;">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" id="adminPassword" 
                               value="#Rumkoda@73" required readonly style="background: #f8f9fa;">
                    </div>
                    
                    <button type="submit" class="btn-submit" style="background: var(--dark);" id="adminSubmitBtn">
                        <i class="fas fa-user-shield"></i> Login sebagai Administrator
                    </button>
                </form>
                
                <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; text-align: center;">
                    <p style="font-size: 12px; color: var(--gray); margin: 0;">
                        <i class="fas fa-info-circle"></i> Akun administrator sudah terisi otomatis
                    </p>
                </div>
            </div>
            <button class="close-modal" onclick="closeAdminLogin()">×</button>
        </div>
    </div>

    <script>
        // ========== SCROLL FUNCTIONS ==========
        function scrollToSection(sectionId) {
            document.getElementById(sectionId).scrollIntoView({ 
                behavior: 'smooth' 
            });
        }
        
        function scrollToTop() {
            window.scrollTo({ 
                top: 0, 
                behavior: 'smooth' 
            });
        }
        
        // Show scroll button when scrolling
        window.addEventListener('scroll', function() {
            const scrollButton = document.querySelector('.scroll-top');
            if (window.scrollY > 500) {
                scrollButton.style.display = 'flex';
            } else {
                scrollButton.style.display = 'none';
            }
        });
        
        // ========== MODAL FUNCTIONS ==========
        function openRoleLogin(role) {
            const modal = document.getElementById('roleLoginModal');
            const header = document.getElementById('roleModalHeader');
            const subtitle = document.getElementById('roleModalSubtitle');
            const roleInput = document.getElementById('loginRole');
            const emailInput = document.getElementById('loginEmail');
            const passwordInput = document.getElementById('loginPassword');
            
            // Set role-specific content
            let roleName = '';
            let bgColor = '';
            
            switch(role) {
                case 'government':
                    roleName = 'Pemerintah';
                    bgColor = '#3498db';
                    break;
                case 'nakes':
                    roleName = 'Tenaga Kesehatan';
                    bgColor = '#27ae60';
                    break;
                case 'parent':
                    roleName = 'Orang Tua';
                    bgColor = '#f39c12';
                    break;
            }
            
            header.style.background = `linear-gradient(135deg, ${bgColor} 0%, ${darkenColor(bgColor, 20)} 100%)`;
            subtitle.textContent = `Masuk sebagai ${roleName}`;
            roleInput.value = role;
            
            // Kosongkan input setiap kali buka modal
            if (emailInput) emailInput.value = '';
            if (passwordInput) passwordInput.value = '';
            
            // Focus ke email input
            setTimeout(() => {
                if (emailInput) emailInput.focus();
            }, 300);
            
            modal.style.display = 'flex';
        }
        
        function closeRoleLogin() {
            document.getElementById('roleLoginModal').style.display = 'none';
        }
        
        function openAdminLogin() {
            const modal = document.getElementById('adminLoginModal');
            modal.style.display = 'flex';
        }
        
        function closeAdminLogin() {
            document.getElementById('adminLoginModal').style.display = 'none';
        }
        
        function darkenColor(color, percent) {
            return color;
        }
        
        // Close modals on outside click
        window.onclick = function(event) {
            const roleModal = document.getElementById('roleLoginModal');
            const adminModal = document.getElementById('adminLoginModal');
            
            if (event.target === roleModal) {
                closeRoleLogin();
            }
            if (event.target === adminModal) {
                closeAdminLogin();
            }
        }
        
        // ========== LOGIN FUNCTIONS ==========
        function fillDemoCredentials(email, password, role) {
            const roleInput = document.getElementById('loginRole');
            const emailInput = document.getElementById('loginEmail');
            const passwordInput = document.getElementById('loginPassword');
            const submitBtn = document.getElementById('loginSubmitBtn');
            
            if (roleInput && emailInput && passwordInput && submitBtn) {
                roleInput.value = role;
                emailInput.value = email;
                passwordInput.value = password;
                
                // Highlight inputs
                emailInput.style.borderColor = '#27ae60';
                passwordInput.style.borderColor = '#27ae60';
                
                // Tampilkan loading state
                const originalHTML = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Login otomatis...';
                submitBtn.disabled = true;
                
                // Auto submit setelah 1.5 detik
                setTimeout(() => {
                    document.getElementById('roleLoginForm').submit();
                }, 1500);
                
                // Reset setelah 5 detik jika gagal
                setTimeout(() => {
                    submitBtn.innerHTML = originalHTML;
                    submitBtn.disabled = false;
                    emailInput.style.borderColor = '#e0e0e0';
                    passwordInput.style.borderColor = '#e0e0e0';
                }, 5000);
            }
        }
        
        // Form validation
        document.getElementById('roleLoginForm')?.addEventListener('submit', function(e) {
            const email = this.querySelector('input[name="email"]').value;
            const password = this.querySelector('input[name="password"]').value;
            const role = this.querySelector('input[name="role"]').value;
            const submitBtn = this.querySelector('.btn-submit');
            
            if (!email || !password || !role) {
                e.preventDefault();
                alert('Harap isi semua field!');
                return false;
            }
            
            // Tampilkan loading
            if (submitBtn) {
                const originalHTML = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses login...';
                submitBtn.disabled = true;
                
                // Reset setelah 5 detik
                setTimeout(() => {
                    submitBtn.innerHTML = originalHTML;
                    submitBtn.disabled = false;
                }, 5000);
            }
            
            return true;
        });
        
        // ========== LOCATION FUNCTIONS ==========
        function getUserLocation() {
            const locationElement = document.getElementById('userLocation');
            
            if (!navigator.geolocation) {
                locationElement.textContent = 'Browser tidak mendukung deteksi lokasi';
                return;
            }
            
            locationElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mendeteksi lokasi...';
            
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    getCityName(lat, lng);
                },
                function(error) {
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            locationElement.textContent = 'Izin lokasi ditolak';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            locationElement.textContent = 'Informasi lokasi tidak tersedia';
                            break;
                        case error.TIMEOUT:
                            locationElement.textContent = 'Permintaan lokasi timeout';
                            break;
                        default:
                            locationElement.textContent = 'Error mendeteksi lokasi';
                    }
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        }

        function getCityName(lat, lng) {
            const locationElement = document.getElementById('userLocation');
            const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=10`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.address) {
                        const city = data.address.city || 
                                    data.address.town || 
                                    data.address.village || 
                                    data.address.county || 
                                    'Lokasi tidak diketahui';
                        
                        locationElement.innerHTML = `<strong>${city}</strong>`;
                        sessionStorage.setItem('userCity', city);
                    } else {
                        locationElement.textContent = 'Lokasi tidak dapat diidentifikasi';
                    }
                })
                .catch(error => {
                    console.error('Error getting city:', error);
                    locationElement.textContent = `Koordinat: ${lat.toFixed(4)}, ${lng.toFixed(4)}`;
                });
        }
        
        // ========== NAVIGATION ACTIVE STATE ==========
        window.addEventListener('scroll', function() {
            const sections = document.querySelectorAll('section');
            const navLinks = document.querySelectorAll('.nav-link');
            
            let current = '';
            
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                if (scrollY >= (sectionTop - 100)) {
                    current = section.getAttribute('id');
                }
            });
            
            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href').substring(1) === current) {
                    link.classList.add('active');
                }
            });
        });
        
        // ========== INITIALIZATION ==========
        document.addEventListener('DOMContentLoaded', function() {
            // Hide scroll button initially
            document.querySelector('.scroll-top').style.display = 'none';
            
            // Auto detect location after 2 seconds
            setTimeout(() => {
                getUserLocation();
            }, 2000);
            
            // Check for sessionStorage errors
            const loginError = sessionStorage.getItem('login_error');
            if (loginError) {
                alert('Login Error: ' + loginError);
                sessionStorage.removeItem('login_error');
            }
            
            // Debug info
            console.log('SIRAGA Landing Page Loaded');
            console.log('PHP Session:', <?php echo json_encode($_SESSION); ?>);
        });
        
        // ========== DIRECT LOGIN FOR TESTING ==========
        function testDirectLogin(role) {
            let email, password;
            
            switch(role) {
                case 'government':
                    email = 'gov@siraga.com';
                    password = 'gov123';
                    break;
                case 'nakes':
                    email = 'nakes@siraga.com';
                    password = 'nakes123';
                    break;
                case 'parent':
                    email = 'parent@siraga.com';
                    password = 'parent123';
                    break;
                case 'admin':
                    email = 'ongkiid81@gmail.com';
                    password = '#Rumkoda@73';
                    break;
            }
            
            // Create and submit form directly
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'auth/login.php';
            form.style.display = 'none';
            
            const emailInput = document.createElement('input');
            emailInput.type = 'hidden';
            emailInput.name = 'email';
            emailInput.value = email;
            
            const passInput = document.createElement('input');
            passInput.type = 'hidden';
            passInput.name = 'password';
            passInput.value = password;
            
            const roleInput = document.createElement('input');
            roleInput.type = 'hidden';
            roleInput.name = 'role';
            roleInput.value = role;
            
            form.appendChild(emailInput);
            form.appendChild(passInput);
            form.appendChild(roleInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>