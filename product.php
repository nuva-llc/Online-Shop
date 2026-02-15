<?php
/**
 * Weapons Store - تفاصيل المنتج
 * عرض بيانات المنتج الكاملة، الصور، وخيارات الإضافة للسلة
 */

require_once 'components/header.php';

// جلب معرف المنتج من الروابط - تحويل لـ integer لمنع SQL Injection
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect('home.php'); // العودة للمتجر في حال عدم وجود معرف صالح
}

// جلب بيانات المنتج من قاعدة البيانات
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

// التعامل مع حالة عدم وجود المنتج
if (!$product) {
    echo "<div class='container mt-5 text-center p-5 card glass'>
            <i class='fas fa-exclamation-triangle fa-4x text-warning mb-3'></i>
            <h1 data-i18n='product-not-available'>عذراً، المنتج غير متاح</h1>
            <p class='text-muted' data-i18n='product-not-available-desc'>ربما تم حذفه أو تم نقله لمكان آخر.</p>
            <a href='home.php' class='btn mt-3' data-i18n='btn-back-store'>العودة للمتجر</a>
          </div>";
    require_once 'components/footer.php';
    exit;
}
?>

<div class="container" style="padding: var(--section-padding) 0;">
    <a href="javascript:history.back()" class="btn btn-back"><i class="fas fa-chevron-left"></i> <span data-i18n="nav-back">العودة</span></a>
    <div class="d-flex" style="flex-wrap: wrap; gap: 40px;">
        
        <!-- معرض صور المنتج (Gallery) -->
        <div style="flex: 1; min-width: 300px;">
            <?php 
                // الصورة الأساسية (تخدم كصورة أولية)
                $mainImg = $product['image_1'] ? SITE_URL . 'uploads/products/' . e($product['image_1']) : SITE_URL . 'assets/images/no-image.jpg';
            ?>
            <div class="card p-2 glass" style="margin-bottom: 20px;">
                <img id="mainImage" src="<?= $mainImg ?>" alt="<?= e($product['name']) ?>" 
                     style="width: 100%; border-radius: 12px; height: 400px; object-fit: cover;">
            </div>
            
            <!-- مصغرات الصور الإضافية -->
            <div class="d-flex" style="gap: 10px; overflow-x: auto; padding-bottom: 10px;">
                <?php for($i=1; $i<=5; $i++): ?>
                    <?php if($product["image_$i"]): ?>
                        <div class="card p-1" style="flex: 0 0 80px;">
                            <img src="uploads/products/<?= $product["image_$i"] ?>" 
                                 style="width: 100%; height: 70px; object-fit: cover; border-radius: 8px; cursor: pointer; opacity: 0.7; transition: 0.3s;" 
                                 onclick="document.getElementById('mainImage').src=this.src; document.querySelectorAll('.thumb').forEach(t=>t.style.opacity=0.7); this.style.opacity=1;"
                                 class="thumb">
                        </div>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
        </div>

        <!-- معلومات المنتج (Info) -->
        <div style="flex: 1; min-width: 300px;">
            <div style="display: flex; margin-bottom: 15px;">
                <span class="badge badge-accent" style="padding: 6px 16px;"><?= e($product['category']) ?></span>
            </div>
            <h1 style="font-size: 2.8rem; font-weight: 800; margin-bottom: 10px; color: var(--text-main);"><?= e($product['name']) ?></h1>
            <p class="text-muted" style="font-size: 1.1rem; margin-bottom: 20px;"><span data-i18n="brand-label">العلامة التجارية:</span> <span class="text-accent" style="font-weight: 700;"><?= e($product['brand']) ?></span></p>
            
            <h2 class="product-price" style="font-size: 2.2rem; margin-bottom: 20px;"><?= formatPrice($product['price']) ?></h2>
            
            <div style="line-height: 1.8; margin-bottom: 30px; color: var(--text-muted);">
                <?= nl2br(e($product['description'])) ?>
            </div>

            <!-- جدول المواصفات الفنية -->
            <div class="card glass mb-4">
                <h3 class="mb-3" style="font-size: 1.2rem; border-bottom: 1px solid var(--border-glass); padding-bottom: 10px;" data-i18n="tech-specs">المواصفات الفنية</h3>
                <table class="specs-table">
                    <tr>
                        <td class="spec-label text-muted" data-i18n="origin-country">بلد المنشأ:</td>
                        <td class="spec-value"><?= e($product['manufacturing_country']) ?></td>
                    </tr>
                    <tr>
                        <td class="spec-label text-muted" data-i18n="available-qty">الكمية المتاحة:</td>
                        <td class="spec-value" style="color: <?= $product['quantity'] > 0 ? 'var(--success)' : 'var(--danger)' ?>;">
                            <?php if($product['quantity'] > 0): ?>
                                <?= $product['quantity'] ?> <span data-i18n="qty-unit">قطعة</span>
                            <?php else: ?>
                                <span data-i18n="out-of-stock">نفذت الكمية</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- نموذج الإضافة للسلة -->
            <?php if($product['quantity'] > 0): ?>
                <form id="addToCartForm" class="d-flex align-center" style="gap: 15px;">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <div style="display: flex; flex-direction: column;">
                        <label class="text-muted" style="font-size: 0.8rem; margin-bottom: 5px;" data-i18n="label-qty">الكمية:</label>
                        <input type="number" name="quantity" value="1" min="1" max="<?= $product['quantity'] ?>" 
                               class="form-control" style="width: 100px; text-align: center; height: 45px;">
                    </div>
                    <button type="submit" class="btn" style="flex: 1; height: 45px; margin-top: 23px;" data-i18n="btn-add-cart">
                        <i class="fas fa-cart-plus"></i> إضافة للسلة
                    </button>
                </form>
            <?php else: ?>
                <div class="alert alert-error text-center" data-i18n="out-of-stock-msg">🚫 هذا المنتج غير متوفر حالياً في المخزون.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
/**
 * معالجة الإضافة للسلة عبر نموذج التفاصيل
 */
document.getElementById('addToCartForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('csrf_token', window.CSRF_TOKEN);
    
    fetch(window.SITE_URL + 'handlers/add_to_cart.php', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': window.CSRF_TOKEN
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            if(window.showToast) {
                window.showToast('تمت إضافة المنتج بنجاح لمسيرة مشترياتك', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                alert('تمت الإضافة بنجاح');
                location.reload();
            }
        } else {
            if(window.showToast) {
                window.showToast(data.message || 'حدث خطأ غير معروف', 'error');
                if(data.redirect) setTimeout(() => window.location.href = data.redirect, 1500);
            } else {
                alert(data.message || 'خطأ');
                if(data.redirect) window.location.href = data.redirect;
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if(window.showToast) {
            window.showToast('عذراً، حدث خطأ في الاتصال بالخادم. يرجى المحاولة لاحقاً.', 'error');
        } else {
            alert('حدث خطأ في الاتصال بالخادم.');
        }
    });
});
</script>

<?php 
// تضمين تذييل الصفحة
require_once 'components/footer.php'; 
?>
