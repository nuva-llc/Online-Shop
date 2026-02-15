<?php
/**
 * Weapons Store - لإدارة مخزون المنتجات
 * يسمح للمسؤول بإضافة، تعديل، وحذف الأسلحة والمعدات وتحديث الصور
 */

require_once '../includes/session.php';
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// التحقق من صلاحيات المدير
checkAdmin();

$msg = '';
$err = '';

/**
 * 1. حذف منتج من قاعدة البيانات (POST only for security)
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    // التحقق من CSRF Token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $err = "خطأ في التحقق من الطلب. الرجاء المحاولة مرة أخرى.";
    } else {
        $id = (int)$_POST['product_id'];
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        if ($stmt->execute([$id])) {
            triggerPortableBackup(); // حفظ التغييرات محمولاً
            $msg = "تم حذف المنتج والبيانات المرتبطة به بنجاح.";
        }
    }
}

/**
 * 2. إضافة منتج جديد أو تحديث منتج موجود (POST)
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_product'])) {
    // التحقق من CSRF Token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $err = "خطأ في التحقق من الطلب. الرجاء المحاولة مرة أخرى.";
    } else {
        $name = sanitize($_POST['name'] ?? '');
        $brand = sanitize($_POST['brand'] ?? '');
        $price = $_POST['price'] ?? 0;
        $quantity = $_POST['quantity'] ?? 0;
        $desc = sanitize($_POST['description'] ?? '');
        $country = sanitize($_POST['manufacturing_country'] ?? '');
        $cat = sanitize($_POST['category'] ?? '');
        
        // معالجة رفع الصورة الأساسية
        $img1 = null;
        if (!empty($_FILES['image_1']['name'])) {
            $img1 = uploadImage($_FILES['image_1'], '../uploads/products/');
            if (!$img1) $err = "فشل رفع الصورة. تأكد من نوع وحجم الملف.";
        }

        if (!$err) {
            if (isset($_POST['id']) && !empty($_POST['id'])) {
                // حالة التحديث (Update)
                $sql = "UPDATE products SET name=?, brand=?, price=?, quantity=?, description=?, manufacturing_country=?, category=?";
                $params = [$name, $brand, $price, $quantity, $desc, $country, $cat];
                if ($img1) {
                    $sql .= ", image_1=?";
                    $params[] = $img1;
                }
                $sql .= " WHERE id=?";
                $params[] = $_POST['id'];
                
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute($params)) {
                    triggerPortableBackup(); // حفظ التغييرات محمولاً
                    $msg = "تم تحديث بيانات المنتج بنجاح.";
                } else $err = "فشل في تحديث بيانات المنتج، يرجى المحاولة لاحقاً.";
            } else {
                // حالة الإضافة (Insert)
                $sql = "INSERT INTO products (name, brand, price, quantity, description, manufacturing_country, category, image_1) VALUES (?,?,?,?,?,?,?,?)";
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute([$name, $brand, $price, $quantity, $desc, $country, $cat, $img1])) {
                    triggerPortableBackup(); // حفظ التغييرات محمولاً
                    $msg = "تمت إضافة المنتج الجديد بنجاح إلى المخزون.";
                } else $err = "حدث خطأ أثناء إضافة المنتج الجديد.";
            }
        }
    }
}

/**
 * جلب بيانات منتج معين في حال كان المسؤول في وضع التعديل
 */
$editProduct = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editProduct = $stmt->fetch();
}

/**
 * جلب كافة المنتجات لعرضها في الجدول
 */
$products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();

require_once '../components/header.php';
?>

<div class="container" style="padding: 40px 0;">
    <a href="dashboard.php" class="btn btn-back"><i class="fas fa-chevron-left"></i> <span data-i18n="nav-back">العودة</span></a>
    <h1 class="mb-4"><i class="fas fa-boxes"></i> <span data-i18n="admin-products-title">إدارة مخزون المنتجات</span></h1>
    
    <?php if($msg) echo "<div class='alert alert-success'>$msg</div>"; ?>
    <?php if($err) echo "<div class='alert alert-error'>$err</div>"; ?>

    <!-- نموذج الإضافة والتعديل -->
    <div class="card mb-3 glass">
        <h3 data-i18n="<?= $editProduct ? 'edit-product-title' : 'add-product-title' ?>"><?= $editProduct ? '✏️ تعديل بيانات المنتج' : '➕ إضافة منتج تكتيكي جديد' ?></h3>
        <form method="POST" enctype="multipart/form-data">
            <?php csrfField(); ?>
            <?php if($editProduct): ?>
                <input type="hidden" name="id" value="<?= $editProduct['id'] ?>">
            <?php endif; ?>
            
            <div class="grid-form" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px;">
                <div class="form-group">
                    <label data-i18n="label-product-name">اسم المنتج</label>
                    <input type="text" name="name" class="form-control" placeholder="مثلاً: AK-47" required value="<?= e($editProduct['name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label data-i18n="label-brand">الماركة / المصنع</label>
                    <input type="text" name="brand" class="form-control" placeholder="مثلاً: Kalashnikov" required value="<?= e($editProduct['brand'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label data-i18n="label-price">السعر ($)</label>
                    <input type="number" step="0.01" name="price" class="form-control" required value="<?= $editProduct['price'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label data-i18n="label-quantity">الكمية المتاحة</label>
                    <input type="number" name="quantity" class="form-control" required value="<?= $editProduct['quantity'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label data-i18n="label-origin">بلد التصنيع</label>
                    <input type="text" name="manufacturing_country" class="form-control" value="<?= $editProduct['manufacturing_country'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label data-i18n="label-category">الفئة</label>
                    <input type="text" name="category" class="form-control" placeholder="مثلاً: بنادق هجومية" value="<?= $editProduct['category'] ?? '' ?>">
                </div>
            </div>
            
            <div class="form-group mt-3">
                <label data-i18n="label-desc">وصف المنتج الشامل</label>
                <textarea name="description" class="form-control" style="height: 100px;" placeholder="اكتب تفاصيل المنتج الفنية هنا..." data-i18n="placeholder-desc"><?= $editProduct['description'] ?? '' ?></textarea>
            </div>
            
            <div class="mt-3">
                <label data-i18n="label-main-img">الصورة الأساسية للمنتج</label>
                <input type="file" name="image_1" class="form-control" accept="image/*">
                <?php if($editProduct && $editProduct['image_1']): ?>
                    <p class="text-muted" style="font-size: 0.8rem;">الصورة الحالية: <?= $editProduct['image_1'] ?></p>
                <?php endif; ?>
            </div>

            <div class="d-flex" style="gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn" data-i18n="<?= $editProduct ? 'btn-save-changes' : 'btn-add-product' ?>"><?= $editProduct ? 'حفظ التعديلات' : 'إدراج المنتج في المتجر' ?></button>
                <?php if($editProduct): ?>
                    <a href="products.php" class="btn btn-secondary" data-i18n="btn-cancel">إلغاء التعديل</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="card glass mt-4">
        <h3 class="mb-3" data-i18n="inventory-list">قائمة المخزون الحالي</h3>
        <div class="table-responsive">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-glass);">
                        <th style="padding: 10px;">ID</th>
                        <th style="padding: 10px;" data-i18n="th-image">صورة</th>
                        <th style="padding: 10px;" data-i18n="th-name">الاسم</th>
                        <th style="padding: 10px;" data-i18n="label-price">السعر</th>
                        <th style="padding: 10px;" data-i18n="label-quantity">الكمية</th>
                        <th style="padding: 10px;" data-i18n="th-actions">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                    <tr style="border-bottom: 1px solid var(--border-glass);">
                        <td style="padding: 10px;">#<?= $p['id'] ?></td>
                        <td style="padding: 10px;">
                            <?php if($p['image_1']): ?>
                            <img src="../uploads/products/<?= $p['image_1'] ?>" width="60" style="border-radius: 5px;">
                            <?php else: ?>
                            <i class="fas fa-image text-muted"></i>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 10px;"><?= e($p['name']) ?></td>
                        <td style="padding: 10px; color: var(--accent);"><?= formatPrice($p['price']) ?></td>
                        <td style="padding: 10px;"><?= $p['quantity'] ?></td>
                        <td style="padding: 10px;">
                            <a href="?edit=<?= $p['id'] ?>" class="btn btn-secondary" style="padding: 5px 12px; font-size: 0.85rem;"><i class="fas fa-edit"></i> <span data-i18n="btn-edit">تعديل</span></a>
                            
                            <!-- نموذج حذف آمن بـ POST و CSRF -->
                           <form method="POST" style="display: inline;" onsubmit="return confirm(window.translations[localStorage.getItem('lang') || 'ar']['confirm-delete-product'])">
                                <?php csrfField(); ?>
                                <input type="hidden" name="delete_product" value="1">
                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                <button type="submit" class="btn" style="background-color: var(--danger); padding: 5px 12px; font-size: 0.85rem; border: none; cursor: pointer;"><i class="fas fa-trash"></i> <span data-i18n="btn-delete">حذف</span></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
// تضمين التذييل
require_once '../components/footer.php'; 
?>
