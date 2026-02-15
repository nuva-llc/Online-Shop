<?php
/**
 * Weapons Store - متجر المنتجات
 * يعرض قائمة المنتجات مع ميزة البحث والتصنيف و Pagination
 */

require_once 'components/header.php';

// إعدادات Pagination
$productsPerPage = 12;
$currentPage = max(1, (int)($_GET['page'] ?? 1));
$offset = ($currentPage - 1) * $productsPerPage;

// جلب معطيات البحث والتصنيف من الروابط (URL)
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

// بناء الاستعلام الأساسي لقاعدة البيانات
$whereClause = "WHERE 1=1";
$params = [];

// إضافة فلتر البحث إذا وُجد
if ($search) {
    $whereClause .= " AND (name LIKE ? OR brand LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// إضافة فلتر التصنيف إذا وُجد
if ($category) {
    $whereClause .= " AND category = ?";
    $params[] = $category;
}

// حساب إجمالي عدد المنتجات (للـ Pagination)
$countSql = "SELECT COUNT(*) FROM products $whereClause";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalProducts = $countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $productsPerPage);

// تنفيذ الاستعلام مع LIMIT للـ Pagination
$sql = "SELECT * FROM products $whereClause ORDER BY created_at DESC LIMIT $productsPerPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<div class="container" style="padding: var(--section-padding) 0;">
    
    <!-- شريط البحث والفلاتر (Search Bar + Filters) -->
    <div class="search-bar mb-3">
        <form method="GET" action="" class="d-flex" style="gap: 10px; flex-wrap: wrap;">
            <input type="text" name="search" class="form-control" style="flex: 2; min-width: 200px;" 
                   placeholder="بحث عن منتج (مثلاً: كلاشينكوف، جلوك)..." value="<?= sanitize($search) ?>" data-i18n="search-placeholder">
            
            <!-- فلتر الفئات -->
            <?php 
            // جلب الفئات المتاحة من قاعدة البيانات
            $categoriesStmt = $pdo->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category");
            $categories = $categoriesStmt->fetchAll(PDO::FETCH_COLUMN);
            ?>
            <select name="category" class="form-control" style="flex: 1; min-width: 150px;">
                <option value="" data-i18n="filter-all-categories">جميع الفئات</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit" class="btn"><i class="fas fa-search"></i> <span data-i18n="btn-search">بحث</span></button>
            
            <?php if ($search || $category): ?>
                <a href="home.php" class="btn btn-secondary" title="إعادة تعيين الفلاتر">
                    <i class="fas fa-times"></i> <span data-i18n="btn-reset">إعادة تعيين</span>
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- قسم عرض المنتجات -->
    <div class="d-flex justify-between align-center mb-3" style="flex-wrap: wrap; gap: 10px;">
        <h2 style="margin: 0;" data-i18n="products-title">المنتجات المتاحة</h2>
        <span class="text-muted" style="font-size: 0.9rem;"><?= $totalProducts ?> <span data-i18n="products-count">منتج</span></span>
    </div>

    
    <?php if (count($products) > 0): ?>
        <div class="products-grid">
            <?php foreach ($products as $product): ?>
                <div class="card product-card">
                    <?php 
                        // معالجة صورة المنتج، عرض صورة افتراضية في حال عدم وجود صورة
                        $img = $product['image_1'] ? SITE_URL . 'uploads/products/' . e($product['image_1']) : SITE_URL . 'assets/images/no-image.jpg'; 
                    ?>
                    <div class="product-badge"><?= e($product['category']) ?></div>
                    <img src="<?= $img ?>" alt="<?= e($product['name']) ?>" onerror="this.src='https://via.placeholder.com/300x200?text=No+Image'">
                    
                    <div class="product-info-top">
                        <span class="brand-tag"><?= e($product['brand']) ?></span>
                        <span class="country-tag"><i class="fas fa-globe-americas"></i> <?= e($product['manufacturing_country']) ?></span>
                    </div>

                    <h3><?= e($product['name']) ?></h3>
                    <p class="product-desc-short"><?= e(mb_strimwidth($product['description'], 0, 80, "...")) ?></p>
                    
                    <div class="d-flex justify-between align-center mt-3">
                        <span class="product-price"><?= formatPrice($product['price']) ?></span>
                        <a href="<?= SITE_URL ?>product.php?id=<?= $product['id'] ?>" class="btn btn-secondary" data-i18n="btn-details">Details</a>
                    </div>

                    <!-- زر الإضافة السريعة للسلة باستخدام AJAX -->
                    <button class="btn mt-3 add-to-cart" data-id="<?= $product['id'] ?>" style="width: 100%;">
                        <i class="fas fa-cart-plus"></i> <span data-i18n="btn-add-cart">Add to Cart</span>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination Navigation -->
        <?php if ($totalPages > 1): ?>
            <nav class="pagination-nav" style="display: flex; justify-content: center; gap: 10px; margin-top: 40px; flex-wrap: wrap;">
                <?php 
                // بناء URL مع الحفاظ على معاملات البحث
                $queryParams = [];
                if ($search) $queryParams['search'] = $search;
                if ($category) $queryParams['category'] = $category;
                
                // زر الصفحة السابقة
                if ($currentPage > 1): 
                    $queryParams['page'] = $currentPage - 1;
                ?>
                    <a href="?<?= http_build_query($queryParams) ?>" class="btn btn-secondary" style="padding: 8px 16px;">
                        <i class="fas fa-chevron-right"></i> <span data-i18n="pagination-prev">السابق</span>
                    </a>
                <?php endif; ?>
                
                <!-- أرقام الصفحات -->
                <?php 
                $startPage = max(1, $currentPage - 2);
                $endPage = min($totalPages, $currentPage + 2);
                
                for ($i = $startPage; $i <= $endPage; $i++): 
                    $queryParams['page'] = $i;
                    $isActive = ($i === $currentPage);
                ?>
                    <a href="?<?= http_build_query($queryParams) ?>" 
                       class="btn <?= $isActive ? '' : 'btn-secondary' ?>" 
                       style="padding: 8px 14px; <?= $isActive ? 'pointer-events: none;' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
                
                <!-- زر الصفحة التالية -->
                <?php if ($currentPage < $totalPages): 
                    $queryParams['page'] = $currentPage + 1;
                ?>
                    <a href="?<?= http_build_query($queryParams) ?>" class="btn btn-secondary" style="padding: 8px 16px;">
                        <span data-i18n="pagination-next">التالي</span> <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>
            </nav>
            
            <!-- معلومات الصفحة -->
            <p class="text-center text-muted mt-3" style="font-size: 0.9rem;">
                <span data-i18n="pagination-info">الصفحة</span> <?= $currentPage ?> <span data-i18n="pagination-of">من</span> <?= $totalPages ?> 
                (<?= $totalProducts ?> <span data-i18n="pagination-products">منتج</span>)
            </p>
        <?php endif; ?>

    <?php else: ?>
        <div class="alert alert-error text-center" data-i18n="no-products-found">لا توجد منتجات مطابقة لعملية البحث الحالية.</div>
    <?php endif; ?>
</div>

<script>
/**
 * معالجة إضافة منتج للسلة دون إعادة تحميل الصفحة (AJAX)
 */
document.querySelectorAll('.add-to-cart').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        
        fetch(window.SITE_URL + 'handlers/add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-TOKEN': window.CSRF_TOKEN
            },
            body: 'product_id=' + id + '&quantity=1&csrf_token=' + window.CSRF_TOKEN
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                // عرض تنبيه نجاح وتحديث الصفحة لرؤية التغيير في النافبار
                if(window.showToast) {
                    window.showToast('تمت إضافة المنتج إلى سلة مشترياتك بنجاح', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert('تمت الإضافة للسلة');
                    location.reload();
                }
            } else {
                // عرض رسالة خطأ (مثلاً إذا لم يسجل المستخدم دخوله)
                if(window.showToast) {
                    window.showToast(data.message || 'حدث خطأ غير متوقع أثناء الإضافة', 'error');
                    if(data.redirect) setTimeout(() => window.location.href = data.redirect, 1500);
                } else {
                    if(window.showToast) {
                        window.showToast(data.message || 'خطأ في الإضافة', 'error');
                    } else {
                        alert(data.message || 'خطأ');
                    }
                    if(data.redirect) window.location.href = data.redirect;
                }

            }
        })
        .catch(error => {
            console.error('Error:', error);
            if(window.showToast) {
                window.showToast('عذراً، حدث خطأ في الاتصال بالخادم.', 'error');
            } else {
                alert('خطأ في الاتصال بالخادم');
            }
        });
    });
});
</script>

<?php 
// تضمين تذييل الصفحة
require_once 'components/footer.php'; 
?>
