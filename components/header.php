<?php
// تضمين ملفات الأساس فقط إذا لم يتم تضمينها مسبقاً
if (!defined('DB_HOST')) require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php'; 
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// ============================
// إضافة Headers الأمانية (Security Headers)
// ============================
header("X-Frame-Options: DENY"); // منع Clickjacking
header("X-Content-Type-Options: nosniff"); // منع MIME sniffing
header("X-XSS-Protection: 1; mode=block"); // حماية XSS في المتصفحات القديمة
header("Referrer-Policy: strict-origin-when-cross-origin"); // التحكم في معلومات الإحالة
header("Permissions-Policy: geolocation=(), microphone=(), camera=()"); // تقييد أذونات المتصفح
// Content Security Policy - للحماية من XSS وحقن الأكواد
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:;");

$cartCount = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weapons Store - متجر الأسلحة</title>
    <link rel="stylesheet" href="<?= SITE_URL ?>assets/vendor/fonts/fonts.css?v=<?= APP_VERSION ?>">
    <link rel="stylesheet" href="<?= SITE_URL ?>assets/css/main.css?v=<?= APP_VERSION ?>">
    <link rel="stylesheet" href="<?= SITE_URL ?>assets/vendor/fontawesome/css/all.min.css?v=<?= APP_VERSION ?>">
    <script>
        // تصدير المعطيات الأساسية للجافاسكريبت
        window.SITE_URL = "<?= SITE_URL ?>";
        window.APP_VERSION = "<?= APP_VERSION ?>";
        window.CSRF_TOKEN = "<?= generateCSRFToken() ?>";
    </script>
    <script src="<?= SITE_URL ?>assets/js/translations.js?v=<?= APP_VERSION ?>"></script>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="<?= SITE_URL ?>index.php" class="logo">Weapons<span class="text-accent">Store</span></a>
            <div class="nav-links" id="nav-menu">
                <a href="<?= SITE_URL ?>home.php" data-i18n="nav-products">المنتجات</a>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if(isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin'): ?>
                        <a href="<?= SITE_URL ?>admin/dashboard.php" data-i18n="nav-dashboard">لوحة التحكم</a>
                    <?php endif; ?>
                    <a href="<?= SITE_URL ?>profile.php" data-i18n="nav-profile">حسابي</a>
                    <a href="<?= SITE_URL ?>logout.php" data-i18n="nav-logout" onclick="return confirm(window.translations[localStorage.getItem('lang') || 'ar']['logout-confirm'])">خروج</a>
                <?php else: ?>
                    <a href="<?= SITE_URL ?>auth.php" data-i18n="nav-auth">دخول / تسجيل</a>
                <?php endif; ?>
                <a href="<?= SITE_URL ?>cart.php" class="cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if($cartCount > 0): ?>
                        <span class="badge-dot"><?= $cartCount ?></span>
                    <?php endif; ?>
                </a>
            </div>

            <div class="nav-actions">
                <!-- Language Toggle Button -->
                <button id="lang-toggle" class="btn btn-secondary lang-btn">
                    <span>EN</span>
                </button>

                <!-- User-Provided Premium Theme Switch -->
                <div class="theme-switch-wrapper">
                    <label class="ui-switch">
                        <input type="checkbox" id="theme-checkbox">
                        <div class="slider">
                            <div class="circle"></div>
                        </div>
                    </label>
                </div>

                <!-- Hamburger Menu Button -->
                <button class="menu-toggle" id="menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </nav>


    <!-- Flash Message Container -->
    <?php if(isset($_SESSION['flash_msg'])): ?>
        <div id="flash-message" 
             data-message="<?= $_SESSION['flash_msg'] ?>" 
             data-type="<?= $_SESSION['flash_type'] ?? 'success' ?>"></div>
        <?php 
            unset($_SESSION['flash_msg']);
            unset($_SESSION['flash_type']);
        ?>
    <?php endif; ?>
