<?php
// index.php - Halaman Utama / Landing Page
require_once 'php/config.php';
require_once 'php/database.php';
require_once 'php/session.php';
require_once 'php/helpers.php';

startSession();

$db = Database::getInstance();

// Data paket
$packages = $db->fetchAll(
    "SELECT * FROM packages WHERE status = 'active' ORDER BY is_featured DESC, price ASC LIMIT 6"
);

// Testimonial
$testimonials = $db->fetchAll(
    "SELECT * FROM testimonials WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 6"
);

// Bank accounts
$banks = $db->fetchAll("SELECT * FROM bank_accounts WHERE is_active = 1");

// Stats
$totalJamaah = $db->count("SELECT COUNT(*) FROM users WHERE role='user' AND status='active'");
$totalPaket  = $db->count("SELECT COUNT(*) FROM packages WHERE status='active'");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PT Raudhah Amanah Wisata - Perjalanan Suci Menuju Baitullah</title>
    <meta name="description" content="Travel umroh terpercaya dengan paket terlengkap dan harga terjangkau. Bergabunglah dengan ribuan jamaah yang telah kami layani.">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar" id="navbar">
    <div class="container navbar-inner">
        <a href="index.php" class="navbar-brand">
            <div class="brand-icon"><img src="assets/images/logo.jpg" alt="Logo" style="height: 40px; width: auto;"></div>
            <div class="brand-text">
                <div class="brand-name">Raudhah Amanah Wisata</div>
                <div class="brand-sub">TRAVEL & TOUR UMROH</div>
            </div>
        </a>

        <ul class="navbar-menu" id="navbar-menu">
            <li><a href="#home"       class="nav-link active">Beranda</a></li>
            <li><a href="#paket"      class="nav-link">Paket Umroh</a></li>
            <li><a href="#keunggulan" class="nav-link">Keunggulan</a></li>
            <li><a href="#tentang"    class="nav-link">Tentang Kami</a></li>
            <li><a href="#kontak"     class="nav-link">Kontak</a></li>
        </ul>

        <div class="navbar-actions">
            <?php if (isLoggedIn()): ?>
                <a href="<?= isAdmin() ? 'admin/dashboard.php' : 'dashboard/index.php' ?>" class="btn btn-primary btn-sm">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline-gold btn-sm">
                    <i class="fas fa-sign-in-alt"></i> Masuk
                </a>
            <?php endif; ?>
        </div>

        <button class="hamburger" id="hamburger" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
    </div>
</nav>

<!-- HERO -->
<section class="hero" id="home">
    <div class="hero-bg"></div>
    <div class="hero-overlay"></div>
    <div class="hero-pattern"></div>

    <div class="container hero-content">
        <div style="max-width: 650px;">
            <div class="hero-badge" data-aos>
                <i class="fas fa-star"></i>
                Travel Umroh Terpercaya & Amanah Sejak 2010
            </div>

            <h1 data-aos data-aos-delay="100">
                Wujudkan <span class="highlight">Impian Ibadah</span><br>
                ke Tanah Suci Bersama Kami
            </h1>

            <p class="hero-desc" data-aos data-aos-delay="200">
                PT Raudhah Amanah Wisata hadir untuk membantu perjalanan umroh Anda dengan
                pelayanan profesional, akomodasi nyaman, dan bimbingan ibadah terbaik.
            </p>

            <div class="hero-actions" data-aos data-aos-delay="300">
                <a href="#paket" class="btn btn-primary btn-lg">
                    <i class="fas fa-kaaba"></i> Lihat Paket Umroh
                </a>
                <a href="#kontak" class="btn btn-outline-gold btn-lg">
                    <i class="fab fa-whatsapp"></i> Konsultasi Gratis
                </a>
            </div>

            <div class="hero-stats" data-aos data-aos-delay="400">
                <div class="hero-stat-item">
                    <span class="number"><?= number_format($totalJamaah + 5200) ?>+</span>
                    <div class="label">Jamaah Dilayani</div>
                </div>
                <div class="hero-stat-item">
                    <span class="number">14+</span>
                    <div class="label">Tahun Pengalaman</div>
                </div>
                <div class="hero-stat-item">
                    <span class="number"><?= $totalPaket ?>+</span>
                    <div class="label">Paket Tersedia</div>
                </div>
                <div class="hero-stat-item">
                    <span class="number">98%</span>
                    <div class="label">Kepuasan Jamaah</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- REKENING PT -->
<section style="background: var(--gold); padding: 1.5rem 0;">
    <div class="container">
        <div class="d-flex align-center justify-between flex-wrap gap-2">
            <div class="d-flex align-center gap-2">
                <i class="fas fa-university" style="color: var(--white); font-size: 1.3rem;"></i>
                <span style="color: var(--white); font-weight: 700; font-size: 1.05rem;">Rekening Resmi PT Raudhah Amanah Wisata:</span>
            </div>
            <div class="d-flex align-center gap-3 flex-wrap">
                <?php foreach ($banks as $bank): ?>
                    <span style="color: rgba(255,255,255,0.9); font-size: 0.9rem;">
                        <strong><?= e($bank['bank_name']) ?></strong> — <?= e($bank['account_number']) ?> (<?= e($bank['account_holder']) ?>)
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- PAKET UMROH -->
<section id="paket" style="background: var(--cream);">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-label" data-aos>✦ PAKET PERJALANAN</span>
            <h2 class="section-title" data-aos data-aos-delay="100">Pilih Paket Umroh Anda</h2>
            <div class="section-divider center"></div>
            <p class="section-subtitle mx-auto" data-aos data-aos-delay="200">
                Kami menyediakan berbagai pilihan paket umroh yang sesuai dengan kebutuhan dan anggaran Anda.
                Semua paket telah termasuk akomodasi, transportasi, dan bimbingan ibadah.
            </p>
        </div>

        <div class="packages-grid">
            <?php foreach ($packages as $i => $pkg): ?>
                <div class="package-card <?= $pkg['is_featured'] ? 'featured' : '' ?>" data-aos data-aos-delay="<?= $i * 100 ?>">
                    <div class="package-image">
                        <?php if ($pkg['thumbnail']): ?>
                            <img src="<?= getUploadUrl($pkg['thumbnail']) ?>" alt="<?= e($pkg['name']) ?>">
                        <?php else: ?>
                            <div style="text-align:center; color:rgba(255,255,255,0.4);">
                                <i class="fas fa-kaaba" style="font-size:4rem;"></i>
                            </div>
                        <?php endif; ?>
                        <span class="duration-badge"><i class="fas fa-clock"></i> <?= $pkg['duration_days'] ?> Hari</span>
                    </div>
                    <div class="package-body">
                        <h3 class="package-name"><?= e($pkg['name']) ?></h3>
                        <div class="package-price">
                            <?= formatRupiah($pkg['price']) ?>
                            <span>/ orang</span>
                        </div>
                        <div class="package-features">
                            <div class="package-feature">
                                <i class="fas fa-hotel"></i>
                                Hotel Makkah: <?= e($pkg['hotel_makkah'] ?: 'Bintang 4') ?>
                            </div>
                            <div class="package-feature">
                                <i class="fas fa-mosque"></i>
                                Hotel Madinah: <?= e($pkg['hotel_madinah'] ?: 'Bintang 4') ?>
                            </div>
                            <div class="package-feature">
                                <i class="fas fa-plane"></i>
                                Maskapai: <?= e($pkg['airline'] ?: 'Saudi Airlines') ?>
                            </div>
                            <?php if ($pkg['departure_date']): ?>
                                <div class="package-feature">
                                    <i class="fas fa-calendar"></i>
                                    Berangkat: <?= formatDate($pkg['departure_date']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php $pct = $pkg['quota'] > 0 ? round($pkg['filled'] / $pkg['quota'] * 100) : 0; ?>
                        <div class="package-quota">
                            <span><?= $pkg['filled'] ?>/<?= $pkg['quota'] ?> Kursi</span>
                            <div class="quota-bar"><div class="quota-fill" style="width:<?= $pct ?>%"></div></div>
                            <span><?= $pct ?>%</span>
                        </div>
                        <a href="#kontak" class="btn btn-primary w-100">
                            <i class="fas fa-paper-plane"></i> Daftar Sekarang
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- KEUNGGULAN -->
<section id="keunggulan" class="section-bg-gold">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-label" data-aos>✦ KENAPA KAMI</span>
            <h2 class="section-title" data-aos data-aos-delay="100">Keunggulan Kami</h2>
            <div class="section-divider center"></div>
        </div>
        <div class="features-grid">
            <?php
            $features = [
                ['icon' => 'fas fa-shield-alt', 'title' => 'Terdaftar Resmi Kemenag', 'desc' => 'Izin resmi dari Kementerian Agama RI dengan legalitas yang jelas dan dapat dipertanggungjawabkan.'],
                ['icon' => 'fas fa-pray',        'title' => 'Bimbingan Muthawif',     'desc' => 'Muthawif berpengalaman dan bersertifikat siap membimbing ibadah Anda selama di tanah suci.'],
                ['icon' => 'fas fa-star',         'title' => 'Hotel Terbaik',          'desc' => 'Akomodasi bintang 3 hingga 5 dengan lokasi strategis dekat Masjidil Haram dan Masjid Nabawi.'],
                ['icon' => 'fas fa-users',        'title' => 'Program Referral',       'desc' => 'Dapatkan komisi Rp 500.000 untuk setiap anggota yang mendaftar menggunakan kode referral Anda.'],
                ['icon' => 'fas fa-headset',      'title' => 'Support 24/7',           'desc' => 'Tim kami siap membantu Anda 24 jam sehari, 7 hari seminggu sebelum dan selama perjalanan.'],
                ['icon' => 'fas fa-tags',         'title' => 'Harga Transparan',       'desc' => 'Tidak ada biaya tersembunyi. Semua fasilitas dan biaya sudah tertera jelas di setiap paket.'],
                ['icon' => 'fas fa-plane-departure', 'title' => 'Maskapai Pilihan',   'desc' => 'Penerbangan langsung (direct flight) dengan maskapai terpercaya untuk kenyamanan perjalanan Anda.'],
                ['icon' => 'fas fa-utensils',     'title' => 'Konsumsi Lengkap',       'desc' => 'Makan 3 kali sehari dengan menu halal berkualitas selama berada di Makkah dan Madinah.'],
            ];
            foreach ($features as $i => $f):
            ?>
            <div class="feature-item" data-aos data-aos-delay="<?= $i * 80 ?>">
                <div class="feature-icon"><i class="<?= $f['icon'] ?>"></i></div>
                <h4 class="feature-title"><?= $f['title'] ?></h4>
                <p class="feature-desc"><?= $f['desc'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- TENTANG PROGRAM REFERRAL / MLM -->
<section id="referral" style="background: var(--green-dark); padding: 5rem 0;">
    <div class="container">
        <div class="row align-center gap-4">
            <div class="col">
                <span class="section-label" style="color: var(--gold-light);" data-aos>✦ PROGRAM KEMITRAAN</span>
                <h2 style="color: var(--white);" data-aos data-aos-delay="100">
                    Bergabung & Raih Komisi Bersama Kami
                </h2>
                <div class="section-divider" data-aos data-aos-delay="150"></div>
                <p style="color: rgba(255,255,255,0.75); font-size: 1.05rem; line-height: 1.8;" data-aos data-aos-delay="200">
                    Program referral kami memberikan keuntungan berlipat ganda. Ajak teman & keluarga
                    bergabung dan raih komisi <strong style="color: var(--gold-light);">Rp 500.000</strong>
                    untuk setiap member yang mendaftar menggunakan kode referral Anda.
                </p>
                <div style="margin-top: 1.5rem;" data-aos data-aos-delay="300">
                    <?php
                    $steps = [
                        ['num' => '01', 'title' => 'Daftar & Deposit', 'desc' => 'Transfer min. Rp 2.000.000 ke rekening PT sebagai bukti serius bergabung'],
                        ['num' => '02', 'title' => 'Dapatkan Kode Referral', 'desc' => 'Setelah diverifikasi admin, Anda mendapat kode referral unik'],
                        ['num' => '03', 'title' => 'Ajak Anggota Baru', 'desc' => 'Bagikan kode Anda ke kenalan (maks. 2 anggota langsung: kiri & kanan)'],
                        ['num' => '04', 'title' => 'Terima Komisi', 'desc' => 'Komisi Rp 500.000 langsung masuk ke saldo akun Anda'],
                    ];
                    foreach ($steps as $s):
                    ?>
                    <div class="d-flex gap-2 mb-3">
                        <div style="width:42px;height:42px;background:var(--gold);border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--white);font-weight:700;font-size:0.85rem;flex-shrink:0;">
                            <?= $s['num'] ?>
                        </div>
                        <div>
                            <div style="color:var(--white);font-weight:600;font-size:0.95rem;"><?= $s['title'] ?></div>
                            <div style="color:rgba(255,255,255,0.6);font-size:0.88rem;"><?= $s['desc'] ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-4" data-aos data-aos-delay="400">
                    <a href="#kontak" class="btn btn-primary btn-lg">
                        <i class="fas fa-handshake"></i> Bergabung Sekarang
                    </a>
                </div>
            </div>
            <div class="col" data-aos data-aos-delay="200">
                <div style="background:rgba(255,255,255,0.06);border-radius:var(--radius-xl);padding:2.5rem;border:1px solid rgba(255,255,255,0.1);">
                    <h3 style="color:var(--gold-light);font-family:var(--font-serif);margin-bottom:1.5rem;text-align:center;">Struktur Pohon Referral</h3>
                    <!-- Simple tree illustration -->
                    <div style="text-align:center;padding:1rem;">
                        <div style="display:flex;flex-direction:column;align-items:center;gap:0;">
                            <div style="background:linear-gradient(135deg,var(--gold),var(--gold-light));color:var(--white);padding:0.6rem 1.2rem;border-radius:10px;font-weight:700;font-size:0.9rem;">ANDA</div>
                            <div style="width:2px;height:25px;background:rgba(255,255,255,0.3);"></div>
                            <div style="display:flex;gap:3rem;position:relative;">
                                <div style="display:flex;flex-direction:column;align-items:center;gap:0;">
                                    <div style="background:rgba(255,255,255,0.15);color:var(--white);padding:0.5rem 1rem;border-radius:10px;font-size:0.85rem;border:1px solid rgba(255,255,255,0.2);">Kaki Kiri</div>
                                    <div style="width:2px;height:20px;background:rgba(255,255,255,0.2);"></div>
                                    <div style="display:flex;gap:1.5rem;">
                                        <div style="background:rgba(255,255,255,0.08);color:rgba(255,255,255,0.6);padding:0.4rem 0.7rem;border-radius:8px;font-size:0.75rem;border:1px dashed rgba(255,255,255,0.2);">Kiri</div>
                                        <div style="background:rgba(255,255,255,0.08);color:rgba(255,255,255,0.6);padding:0.4rem 0.7rem;border-radius:8px;font-size:0.75rem;border:1px dashed rgba(255,255,255,0.2);">Kanan</div>
                                    </div>
                                </div>
                                <div style="display:flex;flex-direction:column;align-items:center;gap:0;">
                                    <div style="background:rgba(255,255,255,0.15);color:var(--white);padding:0.5rem 1rem;border-radius:10px;font-size:0.85rem;border:1px solid rgba(255,255,255,0.2);">Kaki Kanan</div>
                                    <div style="width:2px;height:20px;background:rgba(255,255,255,0.2);"></div>
                                    <div style="display:flex;gap:1.5rem;">
                                        <div style="background:rgba(255,255,255,0.08);color:rgba(255,255,255,0.6);padding:0.4rem 0.7rem;border-radius:8px;font-size:0.75rem;border:1px dashed rgba(255,255,255,0.2);">Kiri</div>
                                        <div style="background:rgba(255,255,255,0.08);color:rgba(255,255,255,0.6);padding:0.4rem 0.7rem;border-radius:8px;font-size:0.75rem;border:1px dashed rgba(255,255,255,0.2);">Kanan</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div style="margin-top:2rem;padding:1rem;background:rgba(201,146,42,0.12);border-radius:var(--radius-md);border:1px solid rgba(201,146,42,0.25);">
                        <div style="color:var(--gold-light);font-weight:700;margin-bottom:0.5rem;font-size:0.9rem;">💡 Catatan Penting:</div>
                        <ul style="color:rgba(255,255,255,0.7);font-size:0.85rem;line-height:1.8;padding-left:1rem;">
                            <li>Setiap anggota maksimal 2 kaki (kiri & kanan)</li>
                            <li>Pohon berkembang ke bawah tanpa batas</li>
                            <li>Anda hanya bisa melihat struktur di bawah Anda</li>
                            <li>Komisi Rp 500.000 per referral langsung</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- TESTIMONIALS -->
<section id="testimoni" style="background: var(--cream);">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-label" data-aos>✦ TESTIMONI</span>
            <h2 class="section-title" data-aos data-aos-delay="100">Kata Mereka Tentang Kami</h2>
            <div class="section-divider center"></div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:2rem;">
            <?php foreach ($testimonials as $i => $t): ?>
                <div class="testimonial-card" data-aos data-aos-delay="<?= $i * 100 ?>">
                    <div class="stars">
                        <?= str_repeat('★', $t['rating']) ?><?= str_repeat('☆', 5 - $t['rating']) ?>
                    </div>
                    <p class="testimonial-text">"<?= e($t['content']) ?>"</p>
                    <div class="testimonial-author">
                        <div class="testimonial-avatar">
                            <?php if ($t['photo']): ?>
                                <img src="<?= getUploadUrl($t['photo']) ?>" alt="">
                            <?php else: ?>
                                <?= strtoupper(substr($t['name'], 0, 1)) ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="testimonial-name"><?= e($t['name']) ?></div>
                            <?php if ($t['package_name']): ?>
                                <div class="testimonial-pkg"><?= e($t['package_name']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- TENTANG KAMI -->
<section id="tentang" style="background: var(--white);">
    <div class="container">
        <div class="row align-center gap-4">
            <div class="col" data-aos>
                <div style="background:linear-gradient(135deg,var(--green-dark),var(--green));border-radius:var(--radius-xl);padding:3rem;color:var(--white);text-align:center;position:relative;overflow:hidden;">
                    <div style="position:absolute;top:-30px;right:-30px;width:150px;height:150px;background:rgba(201,146,42,0.1);border-radius:50%;"></div>
                    <div style="margin-bottom:1rem;"><img src="assets/images/logo.jpg" alt="Logo" style="height: 80px; width: auto;"></div>
                    <h3 style="color:var(--gold-light);font-family:var(--font-serif);font-size:1.5rem;margin-bottom:1rem;">PT Raudhah Amanah Wisata</h3>
                    <p style="color:rgba(255,255,255,0.8);font-size:0.95rem;line-height:1.8;">
                        Berizin resmi Kementerian Agama RI<br>
                        SK Operasional: No. D/333/2010<br>
                        NPWP: 01.234.567.8-123.000
                    </p>
                    <hr style="border-color:rgba(255,255,255,0.1);margin:1.5rem 0;">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;text-align:left;">
                        <?php foreach ([['14+','Tahun Beroperasi'],['5200+','Jamaah'],['98%','Kepuasan'],['24/7','Pelayanan']] as $s): ?>
                            <div style="background:rgba(255,255,255,0.08);padding:1rem;border-radius:var(--radius-sm);">
                                <div style="font-family:var(--font-serif);font-size:1.5rem;color:var(--gold-light);font-weight:700;"><?= $s[0] ?></div>
                                <div style="font-size:0.8rem;color:rgba(255,255,255,0.6);"><?= $s[1] ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="col" data-aos data-aos-delay="200">
                <span class="section-label">✦ TENTANG KAMI</span>
                <h2 class="section-title">Kepercayaan Anda adalah Amanah Kami</h2>
                <div class="section-divider"></div>
                <p style="color:var(--gray-600);line-height:1.9;margin-bottom:1.5rem;">
                    PT Raudhah Amanah Wisata adalah perusahaan perjalanan haji dan umroh terpercaya
                    yang telah berpengalaman melayani jamaah Indonesia sejak 2010. Nama "Raudhah"
                    diambil dari taman surga di Masjid Nabawi, mencerminkan komitmen kami untuk
                    menghadirkan pengalaman ibadah yang syahdu dan bermakna.
                </p>
                <p style="color:var(--gray-600);line-height:1.9;margin-bottom:2rem;">
                    Dengan tim profesional berpengalaman dan jaringan mitra terpercaya di Arab Saudi,
                    kami memastikan setiap jamaah mendapatkan pelayanan terbaik dari keberangkatan
                    hingga kepulangan ke tanah air.
                </p>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                    <?php foreach (['Izin Resmi Kemenag','Tim Berpengalaman','Akomodasi Premium','Layanan 24 Jam'] as $item): ?>
                        <div class="d-flex align-center gap-2" style="font-size:0.9rem;color:var(--gray-700);">
                            <i class="fas fa-check-circle" style="color:var(--gold);"></i>
                            <?= $item ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- KONTAK / CTA -->
<section id="kontak" style="background: linear-gradient(135deg, var(--green-dark), var(--green));">
    <div class="container">
        <div class="row gap-4">
            <div class="col" data-aos>
                <span class="section-label" style="color:var(--gold-light);">✦ HUBUNGI KAMI</span>
                <h2 style="color:var(--white);">Konsultasikan Perjalanan Umroh Anda</h2>
                <div class="section-divider"></div>
                <p style="color:rgba(255,255,255,0.75);line-height:1.8;margin-bottom:2rem;">
                    Tim konsultan kami siap membantu Anda memilih paket yang sesuai dan
                    menjawab semua pertanyaan tentang program kemitraan kami.
                </p>
                <div style="display:flex;flex-direction:column;gap:1rem;">
                    <?php $settings = [
                        ['fas fa-phone', getSiteSetting('site_phone', '+62 812-3456-7890')],
                        ['fas fa-envelope', getSiteSetting('site_email', 'info@raudhah.com')],
                        ['fab fa-whatsapp', 'WhatsApp: ' . getSiteSetting('site_whatsapp', '081234567890')],
                        ['fas fa-map-marker-alt', getSiteSetting('site_address', 'Jakarta Pusat')],
                    ];
                    foreach ($settings as $s): ?>
                        <div class="d-flex align-center gap-2" style="color:rgba(255,255,255,0.85);">
                            <i class="<?= $s[0] ?>" style="color:var(--gold-light);width:20px;"></i>
                            <span><?= e($s[1]) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-4">
                    <?php $wa = getSiteSetting('site_whatsapp', '6281234567890'); ?>
                    <a href="https://wa.me/<?= $wa ?>?text=Assalamu'alaikum, saya ingin info paket umroh" target="_blank" class="btn btn-primary btn-lg">
                        <i class="fab fa-whatsapp"></i> Chat WhatsApp Sekarang
                    </a>
                </div>
            </div>
            <div class="col" data-aos data-aos-delay="200">
                <div style="background:var(--white);border-radius:var(--radius-xl);padding:2.5rem;">
                    <h3 style="font-family:var(--font-serif);color:var(--green-dark);margin-bottom:1.5rem;">Kirim Pesan</h3>
                    <form action="php/contact_form.php" method="POST">
                        <?= csrfField() ?>
                        <div class="form-group">
                            <label class="form-label">Nama Lengkap *</label>
                            <input type="text" name="name" class="form-control" placeholder="Masukkan nama Anda" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">No. WhatsApp *</label>
                            <input type="text" name="phone" class="form-control" placeholder="08xx-xxxx-xxxx" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Paket yang Diminati</label>
                            <select name="package" class="form-control">
                                <option value="">-- Pilih Paket --</option>
                                <?php foreach ($packages as $p): ?>
                                    <option value="<?= e($p['name']) ?>"><?= e($p['name']) ?></option>
                                <?php endforeach; ?>
                                <option value="Program Kemitraan / MLM">Program Kemitraan / MLM</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Pesan</label>
                            <textarea name="message" class="form-control" rows="4" placeholder="Tulis pesan Anda..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-paper-plane"></i> Kirim Pesan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <div class="footer-logo-text"><img src="assets/images/logo.jpg" alt="Logo" style="height: 30px; width: auto; display: inline-block; margin-right: 8px; vertical-align: middle;"> PT Raudhah Amanah Wisata</div>
                <p class="footer-desc">
                    Travel umroh terpercaya dan amanah. Kami berkomitmen mengantarkan Anda
                    ke Baitullah dengan pelayanan terbaik dan biaya yang transparan.
                </p>
                <div class="footer-socials">
                    <?php foreach (['fab fa-facebook-f','fab fa-instagram','fab fa-youtube','fab fa-tiktok'] as $icon): ?>
                        <a href="#" class="social-btn"><i class="<?= $icon ?>"></i></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div>
                <h4 class="footer-heading">Paket Umroh</h4>
                <ul class="footer-links">
                    <?php foreach ($packages as $p): ?>
                        <li><a href="#paket"><?= e($p['name']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div>
                <h4 class="footer-heading">Layanan</h4>
                <ul class="footer-links">
                    <?php foreach (['Pendaftaran Umroh','Program Kemitraan','Konsultasi Gratis','Manasik Umroh','City Tour'] as $l): ?>
                        <li><a href="#"><?= $l ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div>
                <h4 class="footer-heading">Kontak</h4>
                <?php foreach ($settings as $s): ?>
                    <div class="footer-contact-item">
                        <i class="<?= $s[0] ?>"></i>
                        <span><?= e($s[1]) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container" style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
            <span>&copy; <?= date('Y') ?> PT Raudhah Amanah Wisata. Hak Cipta Dilindungi.</span>
            <span>Dibuat dengan ❤️ untuk jamaah Indonesia</span>
        </div>
    </div>
</footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js" defer></script>
<script src="js/main.js"></script>
<script>
document.getElementById('hamburger')?.addEventListener('click', function () {
    document.getElementById('navbar-menu').classList.toggle('open');
    this.classList.toggle('active');
});
</script>
</body>
</html>
