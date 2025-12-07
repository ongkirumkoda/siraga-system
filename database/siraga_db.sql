-- ============================================
-- SIRAGA DATABASE STRUCTURE - COMPLETE VERSION
-- ============================================
-- Created for: dr. Violeta Mairuhu
-- Date: 2025
-- ============================================

-- 1. CREATE DATABASE (Jika belum ada)
CREATE DATABASE IF NOT EXISTS siraga_db 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE siraga_db;

-- ============================================
-- TABLE 1: USERS (Semua pengguna sistem)
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'government', 'nakes', 'parent') NOT NULL,
    
    -- Additional info
    phone VARCHAR(20),
    address TEXT,
    photo VARCHAR(255), -- path foto
    is_active BOOLEAN DEFAULT TRUE,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_role (role),
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- ============================================
-- TABLE 2: CHILDREN (Data anak)
-- ============================================
CREATE TABLE IF NOT EXISTS children (
    id INT PRIMARY KEY AUTO_INCREMENT,
    child_code VARCHAR(20) UNIQUE, -- Kode unik: CHILD-001
    parent_id INT NULL, -- relasi ke users (role parent)
    nakes_id INT NOT NULL, -- relasi ke users (role nakes) yang input
    
    -- Basic info
    full_name VARCHAR(100) NOT NULL,
    birth_date DATE NOT NULL,
    gender ENUM('L', 'P') NOT NULL,
    birth_weight DECIMAL(5,2), -- kg
    birth_height DECIMAL(5,2), -- cm
    birth_place VARCHAR(100),
    
    -- Parents info
    mother_name VARCHAR(100),
    father_name VARCHAR(100),
    mother_phone VARCHAR(20),
    father_phone VARCHAR(20),
    
    -- Address & Contact
    address TEXT,
    village VARCHAR(100),
    district VARCHAR(100),
    city VARCHAR(100),
    province VARCHAR(100),
    
    -- Medical history
    blood_type ENUM('A', 'B', 'AB', 'O'),
    allergy TEXT,
    congenital_disease TEXT,
    
    -- Status
    status ENUM('active', 'inactive', 'moved', 'deceased') DEFAULT 'active',
    notes TEXT,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (nakes_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_parent (parent_id),
    INDEX idx_nakes (nakes_id),
    INDEX idx_birth_date (birth_date),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ============================================
-- TABLE 3: EXAMINATIONS (Pemeriksaan rutin)
-- ============================================
CREATE TABLE IF NOT EXISTS examinations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    child_id INT NOT NULL,
    nakes_id INT NOT NULL,
    
    -- Examination date
    examination_date DATE NOT NULL,
    age_months INT, -- Usia dalam bulan
    
    -- Measurements
    weight DECIMAL(5,2) NOT NULL, -- kg
    height DECIMAL(5,2) NOT NULL, -- cm
    head_circumference DECIMAL(5,2), -- cm
    temperature DECIMAL(4,1), -- °C
    
    -- Nutrition status (WHO Z-Score)
    weight_for_age_zscore DECIMAL(4,2),
    height_for_age_zscore DECIMAL(4,2),
    weight_for_height_zscore DECIMAL(4,2),
    
    -- Classification
    nutrition_status ENUM(
        'normal', 
        'underweight', 
        'severely_underweight',
        'stunting',
        'severely_stunting',
        'wasting',
        'severely_wasting',
        'overweight'
    ),
    
    -- Additional info
    muac DECIMAL(4,1), -- Mid-upper arm circumference (cm)
    edema ENUM('yes', 'no'), -- Edema nutritional
    development_notes TEXT, -- Perkembangan
    feeding_practice TEXT, -- Pola makan
    
    -- Recommendations
    recommendation TEXT,
    next_checkup_date DATE,
    
    -- System
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (child_id) REFERENCES children(id) ON DELETE CASCADE,
    FOREIGN KEY (nakes_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_child (child_id),
    INDEX idx_date (examination_date),
    INDEX idx_nutrition (nutrition_status)
) ENGINE=InnoDB;

-- ============================================
-- TABLE 4: VACCINE_MASTER (Master data vaksin)
-- ============================================
CREATE TABLE IF NOT EXISTS vaccine_master (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(20) UNIQUE NOT NULL, -- Kode: BCG, DPT-HB-Hib-1, dll
    name VARCHAR(100) NOT NULL,
    description TEXT,
    
    -- Schedule
    recommended_age_months INT, -- Usia rekomendasi (bulan)
    min_age_months INT, -- Usia minimal
    max_age_months INT, -- Usia maksimal
    dose_number INT, -- Dosis ke-berapa
    
    -- Type
    type ENUM('wajib', 'tambahan', 'campuran'),
    is_required BOOLEAN DEFAULT TRUE,
    
    -- Additional
    contraindications TEXT, -- Kontraindikasi
    side_effects TEXT, -- Efek samping
    
    -- System
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- TABLE 5: IMMUNIZATIONS (Pencatatan imunisasi)
-- ============================================
CREATE TABLE IF NOT EXISTS immunizations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    child_id INT NOT NULL,
    nakes_id INT NOT NULL,
    vaccine_id INT NOT NULL, -- relasi ke vaccine_master
    
    -- Vaccination details
    vaccine_date DATE NOT NULL,
    batch_number VARCHAR(50),
    manufacturer VARCHAR(100),
    expiry_date DATE,
    
    -- Next schedule
    next_due_date DATE,
    next_vaccine_id INT, -- vaksin berikutnya
    
    -- Status
    status ENUM('done', 'pending', 'missed', 'delayed', 'contraindicated') DEFAULT 'done',
    
    -- Effects
    side_effects TEXT,
    reaction_grade ENUM('none', 'mild', 'moderate', 'severe'),
    
    -- Additional
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (child_id) REFERENCES children(id) ON DELETE CASCADE,
    FOREIGN KEY (nakes_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (vaccine_id) REFERENCES vaccine_master(id) ON DELETE CASCADE,
    FOREIGN KEY (next_vaccine_id) REFERENCES vaccine_master(id) ON DELETE SET NULL,
    
    -- Indexes
    INDEX idx_child_vaccine (child_id, vaccine_id),
    INDEX idx_date (vaccine_date),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ============================================
-- TABLE 6: GROWTH_STANDARDS (Standar WHO)
-- ============================================
CREATE TABLE IF NOT EXISTS growth_standards (
    id INT PRIMARY KEY AUTO_INCREMENT,
    gender ENUM('L', 'P') NOT NULL,
    age_months DECIMAL(4,1) NOT NULL, -- Bisa 0.5 untuk 2 minggu
    
    -- Weight (kg) - WHO standards
    weight_median DECIMAL(5,3), -- Median
    weight_sd1 DECIMAL(5,3),   -- -1 SD
    weight_sd2 DECIMAL(5,3),   -- -2 SD
    weight_sd3 DECIMAL(5,3),   -- -3 SD
    
    -- Height (cm)
    height_median DECIMAL(5,2),
    height_sd1 DECIMAL(5,2),
    height_sd2 DECIMAL(5,2),
    height_sd3 DECIMAL(5,2),
    
    -- Head circumference (cm)
    hc_median DECIMAL(5,2),
    hc_sd1 DECIMAL(5,2),
    hc_sd2 DECIMAL(5,2),
    hc_sd3 DECIMAL(5,2),
    
    -- BMI (kg/m²)
    bmi_median DECIMAL(4,2),
    bmi_sd1 DECIMAL(4,2),
    bmi_sd2 DECIMAL(4,2),
    bmi_sd3 DECIMAL(4,2),
    
    -- System
    source VARCHAR(50) DEFAULT 'WHO',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Unique constraint
    UNIQUE KEY unique_gender_age (gender, age_months)
) ENGINE=InnoDB;

-- ============================================
-- TABLE 7: ACTIVITY_LOGS (Log aktivitas sistem)
-- ============================================
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    
    -- Action details
    action VARCHAR(50) NOT NULL, -- login, create, update, delete
    module VARCHAR(50), -- users, children, examinations, immunizations
    record_id INT, -- ID record yang diubah
    description TEXT,
    
    -- Request info
    ip_address VARCHAR(45),
    user_agent TEXT,
    request_method VARCHAR(10),
    request_url TEXT,
    
    -- Timestamp
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- ============================================
-- TABLE 8: NOTIFICATIONS (Notifikasi sistem)
-- ============================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    
    -- Notification content
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'danger', 'success') DEFAULT 'info',
    
    -- Related data
    related_module VARCHAR(50), -- children, examinations, immunizations
    related_id INT, -- ID terkait
    
    -- Status
    is_read BOOLEAN DEFAULT FALSE,
    is_important BOOLEAN DEFAULT FALSE,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    
    -- Foreign key
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_user_unread (user_id, is_read),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- ============================================
-- TABLE 9: SETTINGS (Pengaturan sistem)
-- ============================================
CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'integer', 'boolean', 'json', 'text') DEFAULT 'string',
    category VARCHAR(50), -- general, notification, backup, etc
    description TEXT,
    
    -- System
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_key (setting_key),
    INDEX idx_category (category)
) ENGINE=InnoDB;

-- ============================================
-- TABLE 10: BACKUP_LOGS (Log backup database)
-- ============================================
CREATE TABLE IF NOT EXISTS backup_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    filename VARCHAR(255) NOT NULL,
    filepath TEXT,
    filesize BIGINT, -- bytes
    backup_type ENUM('full', 'incremental', 'differential') DEFAULT 'full',
    
    -- Status
    status ENUM('success', 'failed', 'pending') DEFAULT 'success',
    error_message TEXT,
    
    -- System
    created_by INT, -- user_id
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================
-- INSERT DEFAULT DATA
-- ============================================

-- 1. INSERT DEFAULT USERS
INSERT INTO users (email, name, password, role, phone) VALUES
-- ADMIN (AKUN ANDA)
('ongkiid81@gmail.com', 'Administrator SIRAGA', '#Rumkoda@73', 'admin', '087832608497'),

-- PEMERINTAH
('gov@siraga.com', 'Admin Pemerintah', 'gov123', 'government', '02112345678'),

-- TENAGA KESEHATAN (dr. Violeta Mairuhu)
('nakes@siraga.com', 'dr. Violeta Mairuhu', 'nakes123', 'nakes', '081234567890'),

-- ORANG TUA
('parent@siraga.com', 'Budi Santoso', 'parent123', 'parent', '081298765432');

-- 2. INSERT VACCINE MASTER DATA (Imunisasi wajib Indonesia)
INSERT INTO vaccine_master (code, name, description, recommended_age_months, dose_number, type, is_required) VALUES
-- Bayi baru lahir
('BCG', 'Bacillus Calmette-Guérin', 'Mencegah tuberkulosis (TBC)', 0, 1, 'wajib', TRUE),
('HB-0', 'Hepatitis B-0', 'Vaksin hepatitis B dosis pertama', 0, 1, 'wajib', TRUE),

-- Usia 1 bulan
('Polio-1', 'Polio 1', 'Vaksin polio tetes (OPV)', 1, 1, 'wajib', TRUE),

-- Usia 2 bulan
('DPT-HB-Hib-1', 'DPT-HB-Hib 1', 'Difteri, Pertusis, Tetanus, Hepatitis B, Hib', 2, 1, 'wajib', TRUE),
('Polio-2', 'Polio 2', 'Vaksin polio tetes (OPV)', 2, 2, 'wajib', TRUE),

-- Usia 3 bulan
('DPT-HB-Hib-2', 'DPT-HB-Hib 2', 'Difteri, Pertusis, Tetanus, Hepatitis B, Hib', 3, 2, 'wajib', TRUE),
('Polio-3', 'Polio 3', 'Vaksin polio tetes (OPV)', 3, 3, 'wajib', TRUE),

-- Usia 4 bulan
('DPT-HB-Hib-3', 'DPT-HB-Hib 3', 'Difteri, Pertusis, Tetanus, Hepatitis B, Hib', 4, 3, 'wajib', TRUE),
('Polio-4', 'Polio 4', 'Vaksin polio tetes (OPV)', 4, 4, 'wajib', TRUE),

-- Usia 9 bulan
('Campak', 'Campak', 'Vaksin campak', 9, 1, 'wajib', TRUE),

-- Usia 18 bulan
('DPT-HB-Hib-4', 'DPT-HB-Hib 4', 'Booster', 18, 4, 'wajib', TRUE),
('Polio-5', 'Polio 5', 'Booster polio', 18, 5, 'wajib', TRUE),

-- Usia 24 bulan
('Campak-2', 'Campak 2', 'Booster campak', 24, 2, 'wajib', TRUE),

-- Vaksin tambahan (optional)
('PCV', 'Pneumococcal Conjugate Vaccine', 'Mencegah pneumonia dan meningitis', 2, 1, 'tambahan', FALSE),
('Rotavirus', 'Rotavirus', 'Mencegah diare akibat rotavirus', 2, 1, 'tambahan', FALSE),
('Influenza', 'Influenza', 'Vaksin flu tahunan', 6, 1, 'tambahan', FALSE);

-- 3. INSERT GROWTH STANDARDS (Contoh data WHO - untuk 0-12 bulan)
INSERT INTO growth_standards (gender, age_months, weight_median, weight_sd1, weight_sd2, weight_sd3, height_median, height_sd1, height_sd2, height_sd3) VALUES
-- Male 0-12 months
('L', 0, 3.346, 2.946, 2.526, 2.106, 49.884, 46.102, 43.628, 40.384),
('L', 1, 4.470, 3.901, 3.371, 2.841, 54.715, 50.754, 47.996, 44.784),
('L', 2, 5.567, 4.880, 4.196, 3.512, 58.424, 54.417, 51.535, 48.252),
('L', 3, 6.376, 5.622, 4.868, 4.114, 61.418, 57.341, 54.435, 51.081),
('L', 4, 7.002, 6.200, 5.398, 4.596, 63.886, 59.752, 56.795, 53.388),
('L', 5, 7.511, 6.667, 5.831, 4.995, 65.902, 61.778, 58.785, 55.450),

-- Female 0-12 months
('P', 0, 3.232, 2.842, 2.452, 2.062, 49.147, 45.408, 42.968, 39.888),
('P', 1, 4.187, 3.667, 3.147, 2.627, 53.697, 49.784, 47.157, 44.078),
('P', 2, 5.128, 4.511, 3.894, 3.277, 57.067, 53.026, 50.263, 47.158),
('P', 3, 5.845, 5.157, 4.469, 3.781, 59.802, 55.691, 52.862, 49.691),
('P', 4, 6.424, 5.683, 4.942, 4.201, 62.089, 57.931, 55.050, 51.827),
('P', 5, 6.915, 6.131, 5.347, 4.563, 64.030, 59.843, 56.920, 53.665);

-- 4. INSERT DEFAULT SETTINGS
INSERT INTO settings (setting_key, setting_value, setting_type, category, description) VALUES
-- General
('app_name', 'SIRAGA', 'string', 'general', 'Nama aplikasi'),
('app_version', '1.0.0', 'string', 'general', 'Versi aplikasi'),
('hospital_name', 'Puskesmas Contoh', 'string', 'general', 'Nama puskesmas/rumah sakit'),
('hospital_address', 'Jl. Contoh No. 123, Jakarta', 'string', 'general', 'Alamat puskesmas'),

-- Notification
('notify_vaccine_due_days', '7', 'integer', 'notification', 'Notifikasi sebelum jadwal imunisasi (hari)'),
('notify_checkup_due_days', '30', 'integer', 'notification', 'Notifikasi sebelum jadwal pemeriksaan (hari)'),
('enable_email_notification', 'false', 'boolean', 'notification', 'Aktifkan notifikasi email'),
('enable_whatsapp_notification', 'true', 'boolean', 'notification', 'Aktifkan notifikasi WhatsApp'),

-- Backup
('auto_backup', 'true', 'boolean', 'backup', 'Backup otomatis'),
('backup_frequency', 'daily', 'string', 'backup', 'Frekuensi backup'),
('keep_backup_days', '30', 'integer', 'backup', 'Simpan backup (hari)'),

-- WhatsApp
('whatsapp_number', '6287832608497', 'string', 'whatsapp', 'Nomor WhatsApp untuk notifikasi'),
('whatsapp_api_key', '', 'string', 'whatsapp', 'API Key WhatsApp'),

-- Location
('default_city', 'Jakarta', 'string', 'location', 'Kota default'),
('default_province', 'DKI Jakarta', 'string', 'location', 'Provinsi default');

-- ============================================
-- CREATE TRIGGERS (Otomatisasi)
-- ============================================

-- Trigger 1: Auto generate child_code
DELIMITER $$
CREATE TRIGGER before_child_insert 
BEFORE INSERT ON children 
FOR EACH ROW
BEGIN
    IF NEW.child_code IS NULL THEN
        -- Format: CHILD-YYYYMM-001
        SET @next_id = (SELECT IFNULL(MAX(id), 0) + 1 FROM children);
        SET NEW.child_code = CONCAT('CHILD-', DATE_FORMAT(NOW(), '%Y%m'), '-', LPAD(@next_id, 3, '0'));
    END IF;
END$$
DELIMITER ;

-- Trigger 2: Auto calculate age_months in examinations
DELIMITER $$
CREATE TRIGGER before_examination_insert 
BEFORE INSERT ON examinations 
FOR EACH ROW
BEGIN
    DECLARE birth_date DATE;
    
    -- Get child's birth date
    SELECT birth_date INTO birth_date 
    FROM children 
    WHERE id = NEW.child_id;
    
    -- Calculate age in months
    IF birth_date IS NOT NULL THEN
        SET NEW.age_months = TIMESTAMPDIFF(MONTH, birth_date, NEW.examination_date);
    END IF;
END$$
DELIMITER ;

-- ============================================
-- CREATE STORED PROCEDURES (Prosedur)
-- ============================================

-- Procedure 1: Get child growth history
DELIMITER $$
CREATE PROCEDURE GetChildGrowthHistory(IN child_id INT)
BEGIN
    SELECT 
        e.*,
        c.full_name,
        c.birth_date,
        TIMESTAMPDIFF(MONTH, c.birth_date, e.examination_date) as age_months_calc,
        gs.weight_median,
        gs.height_median
    FROM examinations e
    JOIN children c ON e.child_id = c.id
    LEFT JOIN growth_standards gs ON 
        gs.gender = c.gender AND 
        gs.age_months = TIMESTAMPDIFF(MONTH, c.birth_date, e.examination_date)
    WHERE e.child_id = child_id
    ORDER BY e.examination_date ASC;
END$$
DELIMITER ;

-- Procedure 2: Get immunization schedule
DELIMITER $$
CREATE PROCEDURE GetImmunizationSchedule(IN child_id INT)
BEGIN
    SELECT 
        vm.*,
        i.vaccine_date,
        i.status,
        i.next_due_date,
        CASE 
            WHEN i.status = 'done' THEN 'Selesai'
            WHEN i.status = 'pending' AND i.next_due_date > CURDATE() THEN 'Menunggu jadwal'
            WHEN i.status = 'pending' AND i.next_due_date <= CURDATE() THEN 'Terlambat'
            WHEN i.status = 'missed' THEN 'Terlewat'
            ELSE 'Tidak diketahui'
        END as status_text,
        DATEDIFF(i.next_due_date, CURDATE()) as days_until_due
    FROM vaccine_master vm
    LEFT JOIN immunizations i ON 
        i.vaccine_id = vm.id AND 
        i.child_id = child_id
    WHERE vm.is_required = TRUE
    ORDER BY vm.recommended_age_months ASC;
END$$
DELIMITER ;

-- ============================================
-- CREATE VIEWS (Tampilan)
-- ============================================

-- View 1: Child summary view
CREATE VIEW v_child_summary AS
SELECT 
    c.*,
    u.name as parent_name,
    u2.name as nakes_name,
    COUNT(e.id) as total_examinations,
    COUNT(i.id) as total_immunizations,
    MAX(e.examination_date) as last_examination,
    MIN(e.examination_date) as first_examination
FROM children c
LEFT JOIN users u ON c.parent_id = u.id
LEFT JOIN users u2 ON c.nakes_id = u2.id
LEFT JOIN examinations e ON c.id = e.child_id
LEFT JOIN immunizations i ON c.id = i.child_id
GROUP BY c.id;

-- View 2: Monthly statistics
CREATE VIEW v_monthly_stats AS
SELECT 
    DATE_FORMAT(e.examination_date, '%Y-%m') as month,
    COUNT(DISTINCT e.child_id) as total_children,
    COUNT(e.id) as total_examinations,
    AVG(e.weight) as avg_weight,
    AVG(e.height) as avg_height,
    SUM(CASE WHEN e.nutrition_status IN ('stunting', 'severely_stunting') THEN 1 ELSE 0 END) as stunting_cases,
    SUM(CASE WHEN e.nutrition_status IN ('underweight', 'severely_underweight') THEN 1 ELSE 0 END) as underweight_cases
FROM examinations e
GROUP BY DATE_FORMAT(e.examination_date, '%Y-%m')
ORDER BY month DESC;

-- View 3: Upcoming schedules
CREATE VIEW v_upcoming_schedules AS
SELECT 
    'immunization' as schedule_type,
    c.full_name,
    c.birth_date,
    vm.name as vaccine_name,
    i.next_due_date,
    DATEDIFF(i.next_due_date, CURDATE()) as days_left,
    u.name as parent_name,
    u.phone as parent_phone
FROM immunizations i
JOIN children c ON i.child_id = c.id
JOIN vaccine_master vm ON i.vaccine_id = vm.id
JOIN users u ON c.parent_id = u.id
WHERE i.next_due_date >= CURDATE() 
  AND i.status = 'pending'
  
UNION ALL

SELECT 
    'examination' as schedule_type,
    c.full_name,
    c.birth_date,
    'Pemeriksaan Rutin' as vaccine_name,
    e.next_checkup_date as next_due_date,
    DATEDIFF(e.next_checkup_date, CURDATE()) as days_left,
    u.name as parent_name,
    u.phone as parent_phone
FROM examinations e
JOIN children c ON e.child_id = c.id
JOIN users u ON c.parent_id = u.id
WHERE e.next_checkup_date >= CURDATE()
ORDER BY next_due_date ASC;

-- ============================================
-- FINISH
-- ============================================
SELECT 'Database SIRAGA berhasil dibuat!' as message;