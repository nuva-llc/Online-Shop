<?php
/**
 * Weapons Store - لإدارة المستخدمين والصلاحيات
 * يسمح للمسؤول بتفعيل/إيقاف الحسابات، حذف المستخدمين، وعرض تفاصيلهم
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
 * 1. حذف مستخدم من النظام (POST only for security)
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $err = "msg-csrf-error";
    } else {
        $id = (int)$_POST['user_id'];
        // منع المدير من حذف حسابه الشخصي أثناء استخدامه للوحة التحكم
        if ($id != $_SESSION['user_id']) {
            $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
            triggerPortableBackup(); // حفظ التغييرات محمولاً
            $msg = "msg-user-deleted";
        } else {
            $err = "msg-delete-self-error";
        }
    }
}

/**
 * 2. تفعيل أو تعطيل حساب مستخدم (POST)
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_active'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $err = "msg-csrf-error";
    } else {
        $id = (int)$_POST['user_id'];
        $currentStatus = (int)$_POST['status'];
        $newStatus = $currentStatus == 1 ? 0 : 1;
        $pdo->prepare("UPDATE users SET activation=? WHERE id=?")->execute([$newStatus, $id]);
        triggerPortableBackup(); // حفظ التغييرات محمولاً
        $msg = "msg-status-updated";
    }
}

/**
 * 3. تحديث بيانات المستخدم (POST)
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $err = "msg-csrf-error";
    } else {
        $id = (int)$_POST['user_id'];
        $name = sanitize($_POST['name'] ?? '');
        $username = sanitize($_POST['username'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $user_type = sanitize($_POST['user_type'] ?? 'client');
        $balance = (float)($_POST['balance'] ?? 0);

        if (!validateEmail($email)) {
            $err = "msg-invalid-email";
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name=?, username=?, email=?, user_type=?, balance=? WHERE id=?");
            if ($stmt->execute([$name, $username, $email, $user_type, $balance, $id])) {
                triggerPortableBackup(); // حفظ التغييرات محمولاً
                $msg = "msg-user-updated";
            } else {
                $err = "msg-update-error";
            }
        }
    }
}

/**
 * جلب قائمة كافة المستخدمين المسجلين في الموقع
 */
$users = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();

require_once '../components/header.php';

// جلب بيانات المستخدم للتعديل (إن وُجد)
$editUser = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editUser = $stmt->fetch();
}
?>

<div class="container" style="padding: 40px 0;">
    <a href="dashboard.php" class="btn btn-back"><i class="fas fa-chevron-left"></i> <span data-i18n="nav-back">العودة</span></a>
    <h1 class="mb-3"><i class="fas fa-user-shield"></i> <span data-i18n="admin-users-title">إدارة قاعدة بيانات المستخدمين</span></h1>
    
    <?php if ($msg): ?>
        <div class="alert alert-success" data-i18n="<?= $msg ?>">
            <?= ($msg === 'msg-user-deleted' ? 'تم حذف المستخدم بنجاح.' : ($msg === 'msg-status-updated' ? 'تم تحديث حالة الحساب بنجاح.' : 'تم تحديث بيانات المستخدم بنجاح.')) ?>
        </div>
    <?php endif; ?>

    <?php if ($err): ?>
        <div class="alert alert-error" data-i18n="<?= $err ?>">
            <?= ($err === 'msg-csrf-error' ? 'خطأ في التحقق من الطلب.' : ($err === 'msg-invalid-email' ? 'البريد الإلكتروني غير صالح.' : 'حدث خطأ ما.')) ?>
        </div>
    <?php endif; ?>

    <?php if ($editUser): ?>
    <!-- نموذج تعديل المستخدم -->
    <div class="card glass mb-4">
        <h3><i class="fas fa-edit"></i> <span data-i18n="edit-user-title">تعديل بيانات المستخدم:</span> <span class="text-accent"><?= e($editUser['username']) ?></span></h3>
        <form method="POST" class="mt-3">
            <?php csrfField(); ?>
            <input type="hidden" name="update_user" value="1">
            <input type="hidden" name="user_id" value="<?= $editUser['id'] ?>">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label data-i18n="label-fullname">الاسم الكامل</label>
                    <input type="text" name="name" class="form-control" value="<?= e($editUser['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label data-i18n="label-username">اسم المستخدم</label>
                    <input type="text" name="username" class="form-control" value="<?= e($editUser['username']) ?>" required>
                </div>
                <div class="form-group">
                    <label data-i18n="label-email">البريد الإلكتروني</label>
                    <input type="email" name="email" class="form-control" value="<?= e($editUser['email']) ?>" required>
                </div>
                <div class="form-group">
                    <label data-i18n="th-balance">الرصيد ($)</label>
                    <input type="number" step="0.01" name="balance" class="form-control" value="<?= $editUser['balance'] ?>" required>
                </div>
                <div class="form-group">
                    <label data-i18n="th-type">نوع الحساب</label>
                    <select name="user_type" class="form-control">
                        <option value="client" <?= $editUser['user_type'] == 'client' ? 'selected' : '' ?> data-i18n="user-type-client">عميل</option>
                        <option value="admin" <?= $editUser['user_type'] == 'admin' ? 'selected' : '' ?> data-i18n="user-type-admin">مسؤول</option>
                    </select>
                </div>
            </div>
            
            <div class="d-flex" style="gap: 10px;">
                <button type="submit" class="btn" data-i18n="btn-save">حفظ التغييرات</button>
                <a href="users.php" class="btn btn-secondary" data-i18n="btn-cancel">إلغاء</a>
            </div>
        </form>
    </div>
    <?php endif; ?>
    
    <div class="card glass mt-4">
        <h3 class="mb-4" data-i18n="admin-users-subtitle">سجل المستخدمين المسجلين</h3>
        <div class="table-responsive">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-glass);">
                        <th style="padding: 12px;">ID</th>
                        <th style="padding: 12px;" data-i18n="th-username">اسم المستخدم</th>
                        <th style="padding: 12px;" data-i18n="th-email">البريد الإلكتروني</th>
                        <th style="padding: 12px;" data-i18n="th-type">النوع</th>
                        <th style="padding: 12px;" data-i18n="th-balance">الرصيد</th>
                        <th style="padding: 12px;" data-i18n="th-acc-status">حالة الحساب</th>
                        <th style="padding: 12px;" data-i18n="th-actions">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="7" class="text-center p-3 text-muted" data-i18n="no-users-found">لا يوجد مستخدمين مسجلين حالياً.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                        <tr style="border-bottom: 1px solid var(--border-glass);">
                            <td style="padding: 12px;">#<?= $u['id'] ?></td>
                            <td style="padding: 12px; font-weight: bold;">
                                <i class="fas fa-user-circle"></i> <?= e($u['username']) ?>
                                <?php if($u['user_type'] == 'admin'): ?>
                                    <span class="badge" style="background: var(--accent); font-size: 0.65rem;" data-i18n="user-type-admin">Admin</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px; font-size: 0.9rem;"><?= e($u['email']) ?></td>
                            <td style="padding: 12px;" data-i18n="<?= $u['user_type'] == 'admin' ? 'user-type-admin' : 'user-type-client' ?>"><?= $u['user_type'] == 'admin' ? 'مسؤول' : 'عميل' ?></td>
                            <td style="padding: 12px; color: var(--success); font-weight: bold;"><?= formatPrice($u['balance']) ?></td>
                            <td style="padding: 12px;">
                                <?php if($u['activation']): ?>
                                    <span class="badge badge-success" data-i18n="status-active">نشط</span>
                                <?php else: ?>
                                    <span class="badge badge-danger" data-i18n="status-inactive">موقوف</span>
                                <?php endif; ?>
                            </td>

                            <td style="padding: 12px; display: flex; gap: 8px;">
                                <!-- زر التعديل -->
                                <a href="?edit=<?= $u['id'] ?>" class="btn btn-secondary" style="padding: 4px 10px; font-size: 0.8rem;">
                                    <i class="fas fa-edit"></i> <span data-i18n="btn-edit">تعديل</span>
                                </a>
                                
                                <!-- زر التفعيل/التعطيل -->
                                <form method="POST" style="display: inline;">
                                    <?php csrfField(); ?>
                                    <input type="hidden" name="toggle_active" value="1">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <input type="hidden" name="status" value="<?= $u['activation'] ?>">
                                    <button type="submit" class="btn btn-secondary" style="padding: 4px 10px; font-size: 0.8rem; height: 32px;">
                                        <?= $u['activation'] ? '<i class="fas fa-user-slash"></i>' : '<i class="fas fa-check"></i>' ?>
                                    </button>
                                </form>

                                <!-- زر الحذف -->
                                <?php if($u['id'] != $_SESSION['user_id']): ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm(window.translations[localStorage.getItem('lang') || 'ar']['confirm-delete-user'])">
                                        <?php csrfField(); ?>
                                        <input type="hidden" name="delete_user" value="1">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn" style="background-color: var(--danger); padding: 4px 10px; font-size: 0.8rem; height: 32px; border: none; cursor: pointer;">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
// تضمين التذييل
require_once '../components/footer.php'; 
?>
