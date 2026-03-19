-- =====================================================
-- DATABASE: PT RAUDHAH AMANAH WISATA
-- Travel Umroh MLM System
-- =====================================================

CREATE DATABASE IF NOT EXISTS raudhah_travel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE raudhah_travel;

-- =====================================================
-- TABLE: users
-- =====================================================
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    referral_code VARCHAR(20) NOT NULL UNIQUE,
    referred_by INT UNSIGNED NULL,
    position ENUM('left','right') NULL COMMENT 'Posisi di bawah parent (kiri/kanan)',
    parent_id INT UNSIGNED NULL COMMENT 'User ID parent langsung',
    role ENUM('admin','user') NOT NULL DEFAULT 'user',
    status ENUM('pending','active','suspended') NOT NULL DEFAULT 'pending',
    profile_photo VARCHAR(255) NULL,
    address TEXT NULL,
    nik VARCHAR(20) NULL COMMENT 'Nomor Induk Kependudukan',
    bank_name VARCHAR(100) NULL,
    bank_account VARCHAR(50) NULL,
    bank_holder VARCHAR(150) NULL,
    total_commission DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    withdrawn_commission DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (referred_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: packages (Paket Umroh) - DIPINDAH KE ATAS
-- =====================================================
CREATE TABLE packages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    description TEXT NULL,
    duration_days INT NOT NULL DEFAULT 9,
    price DECIMAL(15,2) NOT NULL,
    quota INT NOT NULL DEFAULT 50,
    filled INT NOT NULL DEFAULT 0,
    departure_date DATE NULL,
    return_date DATE NULL,
    hotel_makkah VARCHAR(200) NULL,
    hotel_madinah VARCHAR(200) NULL,
    airline VARCHAR(100) NULL,
    facilities TEXT NULL COMMENT 'JSON array of facilities',
    thumbnail VARCHAR(255) NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('active','inactive','full','completed') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: registrations (Pendaftaran & Pembayaran)
-- =====================================================
CREATE TABLE registrations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    package_id INT UNSIGNED NOT NULL,
    registration_number VARCHAR(30) NOT NULL UNIQUE,
    payment_proof VARCHAR(255) NULL COMMENT 'Bukti transfer',
    payment_amount DECIMAL(15,2) NOT NULL DEFAULT 2000000.00,
    payment_status ENUM('unpaid','pending_verification','verified','rejected') NOT NULL DEFAULT 'unpaid',
    payment_date TIMESTAMP NULL,
    verified_by INT UNSIGNED NULL COMMENT 'Admin yang verifikasi',
    verified_at TIMESTAMP NULL,
    rejection_reason TEXT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES packages(id),
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: commissions (Komisi MLM)
-- =====================================================
CREATE TABLE commissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL COMMENT 'Penerima komisi',
    from_user_id INT UNSIGNED NOT NULL COMMENT 'Pendaftar yang menggunakan referral',
    registration_id INT UNSIGNED NOT NULL,
    amount DECIMAL(15,2) NOT NULL DEFAULT 500000.00,
    level INT NOT NULL DEFAULT 1 COMMENT 'Level referral (1=langsung)',
    position ENUM('left','right') NULL,
    status ENUM('pending','approved','paid','cancelled') NOT NULL DEFAULT 'pending',
    paid_at TIMESTAMP NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (registration_id) REFERENCES registrations(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: withdrawals (Penarikan Komisi)
-- =====================================================
CREATE TABLE withdrawals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    bank_name VARCHAR(100) NOT NULL,
    bank_account VARCHAR(50) NOT NULL,
    bank_holder VARCHAR(150) NOT NULL,
    status ENUM('pending','approved','rejected','paid') NOT NULL DEFAULT 'pending',
    processed_by INT UNSIGNED NULL,
    processed_at TIMESTAMP NULL,
    transfer_proof VARCHAR(255) NULL,
    rejection_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: bank_accounts (Rekening PT)
-- =====================================================
CREATE TABLE bank_accounts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bank_name VARCHAR(100) NOT NULL,
    account_number VARCHAR(50) NOT NULL,
    account_holder VARCHAR(150) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: notifications
-- =====================================================
CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info','success','warning','danger') NOT NULL DEFAULT 'info',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    link VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: gallery
-- =====================================================
CREATE TABLE gallery (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NULL,
    image_path VARCHAR(255) NOT NULL,
    category VARCHAR(100) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: testimonials
-- =====================================================
CREATE TABLE testimonials (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    photo VARCHAR(255) NULL,
    content TEXT NOT NULL,
    rating TINYINT NOT NULL DEFAULT 5,
    package_name VARCHAR(200) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: site_settings
-- =====================================================
CREATE TABLE site_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    setting_type ENUM('text','textarea','image','number','boolean') NOT NULL DEFAULT 'text',
    label VARCHAR(200) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- TABLE: activity_logs
-- =====================================================
CREATE TABLE activity_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(200) NOT NULL,
    description TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- INDEXES
-- =====================================================
ALTER TABLE users ADD INDEX idx_referral_code (referral_code);
ALTER TABLE users ADD INDEX idx_parent_id (parent_id);
ALTER TABLE users ADD INDEX idx_referred_by (referred_by);
ALTER TABLE users ADD INDEX idx_status (status);
ALTER TABLE registrations ADD INDEX idx_user_id (user_id);
ALTER TABLE registrations ADD INDEX idx_payment_status (payment_status);
ALTER TABLE commissions ADD INDEX idx_user_id (user_id);
ALTER TABLE commissions ADD INDEX idx_status (status);

-- =====================================================
-- DEFAULT DATA
-- =====================================================

-- Admin default (password: Admin@1234)
INSERT INTO users (username, full_name, email, phone, password, referral_code, role, status) VALUES
('admin', 'Administrator', 'admin@raudhah.com', '08123456789', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TiGc2L2SJFXe6DHNMQ5SiW9Ozt0G', 'RAW-ADMIN', 'admin', 'active');

-- Rekening PT
INSERT INTO bank_accounts (bank_name, account_number, account_holder) VALUES
('Bank BCA', '1234567890', 'PT RAUDHAH AMANAH WISATA'),
('Bank BRI', '0987654321', 'PT RAUDHAH AMANAH WISATA'),
('Bank Mandiri', '1122334455', 'PT RAUDHAH AMANAH WISATA');

-- Paket Umroh
INSERT INTO packages (name, slug, description, duration_days, price, quota, departure_date, return_date, hotel_makkah, hotel_madinah, airline, facilities, is_featured, status) VALUES
('Paket Umroh Ekonomi 9 Hari', 'paket-ekonomi-9-hari', 'Paket umroh hemat namun tetap nyaman dengan hotel bintang 3 di Makkah dan Madinah.', 9, 24500000.00, 50, '2025-03-15', '2025-03-24', 'Hotel Zam Zam Tower', 'Hotel Dallah Taibah', 'Saudi Airlines', '["Tiket Pesawat PP", "Visa Umroh", "Hotel Bintang 3", "Makan 3x Sehari", "Transportasi AC", "Muthawif", "Air Zam-zam 5L", "Kain Ihram"]', 0, 'active'),
('Paket Umroh Reguler 12 Hari', 'paket-reguler-12-hari', 'Paket umroh reguler dengan waktu ibadah lebih leluasa dan akomodasi bintang 4.', 12, 32000000.00, 40, '2025-04-10', '2025-04-22', 'Hotel Hilton Suites Makkah', 'Hotel Pullman Zamzam', 'Garuda Indonesia', '["Tiket Pesawat PP", "Visa Umroh", "Hotel Bintang 4", "Makan 3x Sehari", "Transportasi AC Mewah", "Muthawif", "Air Zam-zam 10L", "Kain Ihram", "Baju Seragam", "Manasik Umroh"]', 1, 'active'),
('Paket Umroh VIP 15 Hari', 'paket-vip-15-hari', 'Paket umroh VIP eksklusif dengan fasilitas premium hotel bintang 5 dekat Masjidil Haram.', 15, 55000000.00, 20, '2025-05-01', '2025-05-16', 'Hotel Swissotel Al Maqam', 'Hotel Oberoi Madinah', 'Garuda Indonesia Business Class', '["Tiket Pesawat PP Business Class", "Visa Umroh", "Hotel Bintang 5", "Makan 3x Sehari Premium", "Transportasi Mewah Khusus", "Muthawif Pribadi", "Air Zam-zam 20L", "Kain Ihram Premium", "Baju Seragam Batik", "Manasik Umroh", "City Tour Makkah-Madinah", "Asuransi Perjalanan"]', 1, 'active');

-- Testimonial
INSERT INTO testimonials (name, content, rating, package_name, is_active) VALUES
('Haji Budi Santoso', 'Alhamdulillah, perjalanan umroh bersama PT Raudhah Amanah Wisata sangat berkesan. Pelayanan sangat profesional dan penuh tanggung jawab.', 5, 'Paket Umroh Reguler 12 Hari', 1),
('Ibu Siti Rahayu', 'Sangat puas dengan paket VIP yang ditawarkan. Hotel sangat dekat dengan Masjidil Haram, bisa sholat 5 waktu berjamaah dengan mudah.', 5, 'Paket Umroh VIP 15 Hari', 1),
('Bapak Ahmad Fauzi', 'Untuk pertama kali umroh, saya sangat terbantu dengan bimbingan muthawif yang sabar dan berpengalaman. Terima kasih PT Raudhah!', 5, 'Paket Umroh Ekonomi 9 Hari', 1);

-- Site Settings
INSERT INTO site_settings (setting_key, setting_value, setting_type, label) VALUES
('site_name', 'PT Raudhah Amanah Wisata', 'text', 'Nama Website'),
('site_tagline', 'Perjalanan Suci Menuju Baitullah', 'text', 'Tagline'),
('site_phone', '+62 812-3456-7890', 'text', 'Nomor Telepon'),
('site_email', 'info@raudhah.com', 'text', 'Email'),
('site_address', 'Jl. Sudirman No. 123, Jakarta Pusat, DKI Jakarta 10220', 'textarea', 'Alamat'),
('site_whatsapp', '6281234567890', 'text', 'Nomor WhatsApp'),
('registration_fee', '2000000', 'number', 'Biaya Pendaftaran Minimum (Rp)'),
('referral_commission', '500000', 'number', 'Komisi Referral (Rp)'),
('about_text', 'PT Raudhah Amanah Wisata adalah perusahaan perjalanan haji dan umroh terpercaya yang telah berpengalaman melayani jamaah Indonesia sejak 2010. Kami berkomitmen memberikan pelayanan terbaik dengan amanah dan profesionalisme tinggi.', 'textarea', 'Tentang Kami');