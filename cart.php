<?php
/**
 * Weapons Store - سلة المشتريات
 * عرض المنتجات المختارة، تعديل الكميات، وإتمام الطلب
 */

require_once 'components/header.php';

// منع الوصول للسلة إذا لم يسجل المستخدم دخوله
if (!isLoggedIn()) {
    redirect('auth.php');
}

// جلب بيانات السلة من الجلسة (Session)
$cartItems = $_SESSION['cart'] ?? [];
$total = 0;

// حساب المجموع الكلي للطلب
foreach ($cartItems as $item) {
    $total += $item['price'] * $item['quantity'];
}

// جلب رصيد المستخدم الحالي للتحقق من إمكانية الدفع
$stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userBalance = $stmt->fetchColumn();
?>

<div class="container" style="padding: var(--section-padding) 0;">
    <a href="javascript:history.back()" class="btn btn-back"><i class="fas fa-chevron-left"></i> <span data-i18n="nav-back">العودة</span></a>
    <h2 class="mb-3" data-i18n="cart-title">🛒 سلة المشتريات</h2>


    <div class="d-flex" style="gap: 30px; flex-wrap: wrap;">
        
        <!-- قائمة منتجات السلة -->
        <div style="flex: 2; min-width: 300px;">
            <?php if (empty($cartItems)): ?>
                <div class="card text-center p-5">
                    <i class="fas fa-shopping-basket fa-4x text-muted mb-3"></i>
                    <p class="text-muted" data-i18n="cart-empty">سلة مشترياتك فارغة حالياً.</p>
                    <a href="<?= SITE_URL ?>home.php" class="btn mt-3" data-i18n="btn-shop-now">تصفح المنتجات الآن</a>
                </div>

            <?php else: ?>
                <?php foreach ($cartItems as $id => $item): ?>
                    <div class="card d-flex align-center mb-3" style="gap: 20px;">
                        <!-- صورة المصغر للمنتج -->
                        <img src="<?= $item['image'] ? SITE_URL . 'uploads/products/'.$item['image'] : SITE_URL . 'assets/images/no-image.jpg' ?>" 
                             style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px;">
                        
                        <div style="flex: 1;">
                            <h3 style="font-size: 1.1rem;"><?= e($item['name']) ?></h3>
                            <p class="text-accent" style="font-weight: 700;"><?= formatPrice($item['price']) ?></p>
                        </div>

                        <!-- أدوات التحكم بالكمية -->
                        <div class="d-flex align-center" style="gap: 10px;">
                            <button class="btn btn-secondary update-qty" data-id="<?= $id ?>" data-action="decrease" style="padding: 5px 12px;">-</button>
                            <span style="font-weight: bold; width: 30px; text-align: center;"><?= $item['quantity'] ?></span>
                            <button class="btn btn-secondary update-qty" data-id="<?= $id ?>" data-action="increase" style="padding: 5px 12px;">+</button>
                        </div>

                        <!-- حساب المجموع الفرعي لهذا المنتج -->
                        <div style="font-weight: bold; min-width: 80px; text-align: left;">
                            <?= formatPrice($item['price'] * $item['quantity']) ?>
                        </div>

                        <!-- زر حذف المنتج من السلة -->
                        <button class="btn remove-item" data-id="<?= $id ?>" style="background-color: var(--danger); padding: 8px 12px;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- ملخص الطلب وعملية الدفع -->
        <div style="flex: 1; min-width: 250px;">
            <div class="card glass">
                <h3 class="mb-3" data-i18n="cart-summary">ملخص الطلب</h3>
                
                <div class="d-flex justify-between mt-2 mb-2">
                    <span data-i18n="cart-total">المجموع الكلي:</span>
                    <span class="text-accent" style="font-size: 1.4rem; font-weight: 800;"><?= formatPrice($total) ?></span>
                </div>
                
                <hr style="border: none; border-top: 1px solid var(--border-glass); margin: 15px 0;">
                
                <div class="d-flex justify-between mb-3" style="font-size: 0.95rem;">
                    <span data-i18n="cart-balance">رصيدك الحالي:</span>
                    <span class="<?= $userBalance >= $total ? 'text-success' : 'text-danger' ?>" style="font-weight: bold;">
                        <?= formatPrice($userBalance) ?>
                    </span>
                </div>
                
                <?php if (!empty($cartItems)): ?>
                    <?php if ($userBalance >= $total): ?>
                        <form action="<?= SITE_URL ?>handlers/checkout.php" method="POST">
                            <?php csrfField(); ?>
                            <button type="submit" class="btn" style="width: 100%; font-size: 1.1rem;" data-i18n="btn-checkout">إتمام عملية الشراء</button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-error text-center" style="font-size: 0.9rem;" data-i18n="insufficient-balance">نعتذر، رصيدك الحالي لا يغطي تكلفة الطلب.</div>
                    <?php endif; ?>
                <?php endif; ?>

                
                <p class="text-center text-muted mt-3" style="font-size: 0.8rem;" data-i18n="shipping-note">📦 شحن سريع ومؤمن لجميع الطلبيات</p>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * تحديث الكميات في السلة (زيادة/نقصان)
 */
document.querySelectorAll('.update-qty').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        const action = this.getAttribute('data-action');
        
        fetch(window.SITE_URL + 'handlers/update_cart.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `id=${id}&action=${action}`
        }).then(() => location.reload());
    });
});

/**
 * حذف منتج تماماً من السلة
 */
document.querySelectorAll('.remove-item').forEach(btn => {
    btn.addEventListener('click', function() {
        const lang = localStorage.getItem('lang') || 'ar';
        const msg = (window.translations && window.translations[lang]) 
                    ? window.translations[lang]['cart-remove-confirm'] 
                    : 'هل أنت متأكد من حذف هذا المنتج؟';

        if(confirm(msg)) {
            const id = this.getAttribute('data-id');
            fetch(window.SITE_URL + 'handlers/update_cart.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `id=${id}&action=remove`
            }).then(() => {
                location.reload();
            });
        }
    });
});
</script>

<?php 
// تضمين تذييل الصفحة
require_once 'components/footer.php'; 
?>
