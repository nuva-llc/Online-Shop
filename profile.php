<?php
/**
 * Weapons Store - الملف الشخصي للمستخدم
 * يتيح عرض وتعديل البيانات الشخصية، تغيير كلمة المرور، وعرض سجل الطلبات
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/session.php';
require_once 'includes/functions.php';

// منع الوصول غير المصرح به
if (!isLoggedIn()) {
    redirect('auth.php');
}

$userId = $_SESSION['user_id'];
$msg = '';
$error = '';

/**
 * معالجة طلبات تحديث المعلومات (POST)
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. تحديث المعلومات الشخصية
    if (isset($_POST['update_info'])) {
        // التحقق من CSRF Token
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $error = "خطأ في التحقق من الطلب. الرجاء المحاولة مرة أخرى.";
        } else {
            $name = sanitize($_POST['name']);
            $email = sanitize($_POST['email']);
            $gender = sanitize($_POST['gender']);
            $birth_date = sanitize($_POST['birth_date']);
            
            // التحقق من صحة البريد الإلكتروني
            if (!validateEmail($email)) {
                $error = "البريد الإلكتروني غير صالح.";
            } else {
                // معالجة رفع الصورة الشخصية الجديدة (إن وُجدت)
                $pfp = null;
                if (!empty($_FILES['pfp_img']['name'])) {
                    $pfp = uploadImage($_FILES['pfp_img'], 'uploads/users/');
                    if (!$pfp) {
                        $error = "عذراً، فشل رفع الصورة الشخصية. تأكد من حجم ونوع الملف.";
                    }
                }

                if (!$error) {
                    $sql = "UPDATE users SET name = ?, email = ?, gender = ?, birth_date = ?";
                    $params = [$name, $email, $gender, $birth_date];
                    
                    // إضافة الصورة للاستعلام في حال تم رفع واحدة جديدة
                    if ($pfp) {
                        $sql .= ", pfp_img = ?";
                        $params[] = $pfp;
                    }
                    
                    $sql .= " WHERE id = ?";
                    $params[] = $userId;
                    
                    $stmt = $pdo->prepare($sql);
                    if ($stmt->execute($params)) {
                        triggerPortableBackup(); // حفظ التغييرات محمولاً
                        $_SESSION['flash_msg'] = "تم تحديث بياناتك الشخصية بنجاح.";
                        $_SESSION['flash_type'] = "success";
                        header("Location: profile.php");
                        exit;
                    } else {
                        $error = "حدث خطأ أثناء محاولة التحديث، يرجى المحاولة لاحقاً.";
                    }
                }
            }
        }
    }

    // 2. تغيير كلمة المرور
    if (isset($_POST['change_password'])) {
        // التحقق من CSRF Token
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $error = "خطأ في التحقق من الطلب. الرجاء المحاولة مرة أخرى.";
        } else {
            $old_pass = $_POST['old_password'];
            $new_pass = $_POST['new_password'];

            // التحقق من قوة كلمة المرور الجديدة
            if (!validatePasswordStrength($new_pass)) {
                $error = "كلمة المرور الجديدة يجب أن تحتوي على 8 أحرف على الأقل، حرف كبير، حرف صغير، ورقم.";
            } else {
                // جلب كلمة المرور الحالية للمقارنة
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $current = $stmt->fetchColumn();

                if (password_verify($old_pass, $current)) {
                    $hash = password_hash($new_pass, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    if ($stmt->execute([$hash, $userId])) {
                        triggerPortableBackup(); // حفظ التغييرات محمولاً
                        $_SESSION['flash_msg'] = "تم تغيير كلمة المرور الخاصة بك بنجاح.";
                        $_SESSION['flash_type'] = "success";
                        header("Location: profile.php");
                        exit;
                    }
                } else {
                    $error = "كلمة المرور الحالية التي أدخلتها غير صحيحة.";
                }
            }
        }
    }
}

/**
 * جلب بيانات المستخدم الحالية لعرضها في النماذج
 */
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

/**
 * جلب سجل الطلبات الخاص بالمستخدم
 */
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll();

require_once 'components/header.php';
?>

<div class="container" style="padding: var(--section-padding) 0;">
    <a href="javascript:history.back()" class="btn btn-back"><i class="fas fa-chevron-left"></i> <span data-i18n="nav-back">العودة</span></a>
    <div class="d-flex" style="gap: 30px; flex-wrap: wrap;">
        
        <!-- العمود الجانبي: معلومات الحساب السريعة -->
        <div class="profile-side" style="flex: 1; min-width: 280px;">
            <div class="card text-center glass">
                <div class="pfp-wrapper mb-3" style="position: relative;">
                    <img src="<?= $user['pfp_img'] ? 'uploads/users/'.$user['pfp_img'] : 'assets/images/default-user.png' ?>" 
                         style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid var(--primary-glow); box-shadow: 0 0 20px var(--primary-glow);"
                         onerror="this.src='https://via.placeholder.com/150?text=User'">
                </div>
                <h3><?= e($user['name']) ?></h3>
                <p class="text-muted">@<?= e($user['username']) ?></p>
                <div class="mt-4 p-3 glass" style="border-radius: 15px; border: 1px dashed var(--primary-glow); background: rgba(16, 185, 129, 0.05);">
                    <p class="text-muted mb-1" style="font-size: 0.9rem;" data-i18n="profile-balance">الرصيد المتاح</p>
                    <h2 class="<?= $user['balance'] <= 0 ? 'text-danger' : 'text-success' ?>" style="font-size: 2rem; font-weight: 800;"><?= formatPrice($user['balance']) ?></h2>
                </div>


            </div>

            <!-- نموذج تعديل البيانات -->
            <div class="card mt-3 glass" style="border: 1px solid var(--border-glass);">
                <h3 class="mb-3"><i class="fas fa-user-edit text-accent"></i> <span data-i18n="profile-edit">تعديل البيانات الشخصية</span></h3>
                <?php if($msg): ?>
                    <div class="alert alert-success">
                        <svg stroke="currentColor" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M13 16h-1v-4h1m0-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"></path></svg>
                        <p><?= $msg ?></p>
                    </div>
                <?php endif; ?>
                <?php if($error): ?>
                    <div class="alert alert-error">
                        <svg stroke="currentColor" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M13 16h-1v-4h1m0-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"></path></svg>
                        <p><?= $error ?></p>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <?php csrfField(); ?>
                    <div class="form-group">
                        <label data-i18n="label-fullname">الاسم الكامل</label>
                        <input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label data-i18n="label-email">البريد الإلكتروني</label>
                        <input type="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label data-i18n="label-pfp">الصورة الشخصية</label>
                        <input type="file" name="pfp_img" class="form-control" accept="image/*">
                    </div>
                    <div class="d-flex" style="gap: 10px;">
                        <div style="flex: 1;">
                            <label data-i18n="label-gender">الجنس</label>
                            <select name="gender" class="form-control">
                                <option value="male" <?= $user['gender']=='male'?'selected':'' ?> data-i18n="gender-male">ذكر</option>
                                <option value="female" <?= $user['gender']=='female'?'selected':'' ?> data-i18n="gender-female">أنثى</option>
                            </select>
                        </div>
                        <div style="flex: 1;">
                            <label data-i18n="label-birthdate">تاريخ الميلاد</label>
                            <input type="date" name="birth_date" class="form-control" value="<?= e($user['birth_date']) ?>">
                        </div>
                    </div>
                    <button type="submit" name="update_info" class="btn mt-3" style="width: 100%;" data-i18n="btn-save">حفظ التغييرات</button>
                </form>
            </div>
            
            <!-- نموذج تغيير كلمة المرور -->
            <div class="card mt-3 glass" style="border: 1px solid var(--border-glass);">
                <h3 class="mb-3"><i class="fas fa-key text-accent"></i> <span data-i18n="change-password">تغيير كلمة المرور</span></h3>
                <form method="POST">
                    <?php csrfField(); ?>
                    <div class="form-group">
                        <input type="password" name="old_password" class="form-control" placeholder="كلمة المرور الحالية" required data-i18n="placeholder-old-pass">
                    </div>
                    <div class="form-group">
                        <input type="password" name="new_password" class="form-control" placeholder="كلمة المرور الجديدة" required data-i18n="placeholder-new-pass">
                    </div>
                    <button type="submit" name="change_password" class="btn btn-secondary" style="width: 100%;" data-i18n="btn-update-pass">تحديث كلمة المرور</button>
                </form>
            </div>
        </div>

        <!-- العمود الرئيسي: سجل الطلبات -->
        <div class="profile-main" style="flex: 2; min-width: 300px;">
            <h2 class="mb-3"><i class="fas fa-history"></i> <span data-i18n="profile-history">سجل العمليات والطلبات</span></h2>

            <?php if (empty($orders)): ?>
                <div class="card text-center text-muted p-5">
                    <i class="fas fa-box-open fa-3x mb-3"></i>
                    <p data-i18n="profile-orders-empty">لم تقم بأي عمليات شراء حتى الآن.</p>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="card mb-3 glass">
                        <div class="d-flex justify-between align-center border-bottom pb-2 mb-2" style="border-bottom: 1px solid var(--border-glass);">
                            <div>
                                <span class="text-muted" data-i18n="order-number">رقم العملية:</span> <strong>#<?= $order['id'] ?></strong>
                                <br>
                                <span class="text-muted" style="font-size: 0.85rem;"><i class="fas fa-calendar-alt"></i> <?= date('Y/m/d H:i', strtotime($order['created_at'])) ?></span>
                            </div>

                            <div>
                                <?php 
                                    $badgeClass = 'badge-warning';
                                    $statusKey = 'order-status-pending';
                                    $statusText = 'قيد الانتظار';
                                    if ($order['status'] == 'completed') { 
                                        $badgeClass = 'badge-success'; 
                                        $statusKey = 'order-status-completed';
                                        $statusText = 'مكتملة'; 
                                    }
                                ?>
                                <span class="badge <?= $badgeClass ?>" style="min-width: 100px;" data-i18n="<?= $statusKey ?>"><?= $statusText ?></span>


                            </div>
                        </div>
                        <div class="d-flex justify-between align-center">
                            <span style="font-size: 1.1rem;"><span data-i18n="order-amount">إجمالي المبلغ:</span> <strong class="text-accent"><?= formatPrice($order['total_amount']) ?></strong></span>
                            <span class="text-muted" style="font-size: 0.9rem;" data-i18n="order-by-balance">بواسطة: رصيد المتجر</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
// تضمين تذييل الصفحة
require_once 'components/footer.php'; 
?>
