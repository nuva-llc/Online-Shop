<?php
/**
 * Weapons Store - الصفحة الرئيسية
 * تعرض قسم الترحيب (Hero Section) والمميزات الأساسية للمتجر
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/session.php';
require_once 'includes/functions.php';

// تضمين رأس الصفحة (تنسيقات، نافبار)
require_once 'components/header.php';

// جلب أحدث 3 منتجات لعرضها في الصفحة الرئيسية
$stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 3");
$featuredProducts = $stmt->fetchAll();
?>

<!-- قسم الترحيب الرئيسي (Hero Section) -->
<section class="hero glass">
    <div class="container hero-content">
        <h1 class="mb-3"><span data-i18n="hero-title-1">القمة في عالم</span> <span class="text-accent" data-i18n="hero-title-2">التجهيزات التكتيكية</span></h1>
        <p data-i18n="hero-subtitle">نقدم لك أجود أنواع الأسلحة والمعدات بمعايير عالمية. دقة، أمان، واحترافية في متناول يدك.</p>
        <div class="hero-btns">
            <a href="home.php" class="btn" data-i18n="btn-shop-now">استعرض المتجر</a>
            <?php if(!isLoggedIn()): ?>
                <a href="auth.php" class="btn btn-secondary" data-i18n="btn-signup">انضم إلينا</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- قسم أحدث المنتجات (Featured Products) -->
<section class="featured-products container mt-5">
    <div class="d-flex justify-between align-center mb-4">
        <h2 data-i18n="featured-title">أحدث التجهيزات الواصلة</h2>
        <a href="home.php" class="btn btn-secondary btn-sm" data-i18n="btn-view-all">عرض الكل</a>
    </div>
    
    <div class="products-grid">
        <?php if (count($featuredProducts) > 0): ?>
            <?php foreach ($featuredProducts as $product): ?>
                <div class="card product-card">
                    <div class="product-badge"><?= e($product['category']) ?></div>
                    <?php $img = $product['image_1'] ? SITE_URL . 'uploads/products/' . e($product['image_1']) : SITE_URL . 'assets/images/no-image.jpg'; ?>
                    <img src="<?= $img ?>" alt="<?= e($product['name']) ?>" onerror="this.src='https://via.placeholder.com/300x200?text=No+Image'">
                    <h3><?= e($product['name']) ?></h3>
                    <div class="d-flex justify-between align-center mt-2">
                        <span class="product-price"><?= formatPrice($product['price']) ?></span>
                        <a href="<?= SITE_URL ?>product.php?id=<?= $product['id'] ?>" class="btn btn-secondary btn-sm" data-i18n="btn-details">Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info text-center w-100" data-i18n="no-products-found">لا توجد منتجات معروضة حالياً.</div>
        <?php endif; ?>
    </div>
</section>

<!-- قسم المميزات (Features) -->
<section class="features container mt-3">
    <div class="products-grid">
        <div class="card text-center">
            <i class="fas fa-shield-alt fa-3x text-accent mb-3"></i>
            <h3 data-i18n="feature-1-title">أمان تام</h3>
            <p data-i18n="feature-1-desc">جميع منتجاتنا مرخصة وتخضع لأعلى معايير الفحص والجودة.</p>
        </div>
        <div class="card text-center">
            <i class="fas fa-truck fa-3x text-accent mb-3"></i>
            <h3 data-i18n="feature-2-title">توصيل سريع</h3>
            <p data-i18n="feature-2-desc">خدمة شحن تكتيكية سريعة ومؤمنة لجميع المناطق.</p>
        </div>
        <div class="card text-center">
            <i class="fas fa-headset fa-3x text-accent mb-3"></i>
            <h3 data-i18n="feature-3-title">دعم فني 24/7</h3>
            <p data-i18n="feature-3-desc">فريقنا المتخصص مستعد للإجابة على جميع استفساراتك التكتيكية.</p>
        </div>
    </div>
</section>

<?php 
// تضمين تذييل الصفحة
require_once 'components/footer.php'; 
?>
