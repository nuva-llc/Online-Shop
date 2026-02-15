<?php
/**
 * Weapons Store - صفحة صيانة البيانات وقابلية النقل
 * تسمح للمسؤول بإدارة النسخ الاحتياطي المحمول
 */

require_once '../includes/session.php';
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/backup_manager.php';

// التحقق من صلاحيات المدير
checkAdmin();

$msg = '';
$err = '';
$pde = new BackupManager($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $err = "خطأ في التحقق من الطلب.";
    } else {
        if (isset($_POST['trigger_backup'])) {
            if ($pde->export()) {
                $msg = "تم إنشاء نسخة احتياطية محمولة بنجاح في مجلد database/.portable";
            } else {
                $err = "فشل في إنشاء النسخة الاحتياطية. تأكد من صلاحيات الكتابة.";
            }
        }
    }
}

$lastBackup = file_exists($pde->getBackupPath()) ? date("Y-m-d H:i:s", filemtime($pde->getBackupPath())) : "لا يوجد نسخة حالياً";

require_once '../components/header.php';
?>

<div class="container" style="padding: 40px 0;">
    <a href="dashboard.php" class="btn btn-back"><i class="fas fa-chevron-left"></i> <span data-i18n="nav-back">العودة</span></a>
    
    <h1 class="mb-4"><i class="fas fa-sync-alt"></i> <span data-i18n="admin-maintenance-title">صيانة البيانات وقابلية النقل</span></h1>
    
    <?php if($msg) echo "<div class='alert alert-success'><svg stroke='currentColor' viewBox='0 0 24 24' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M13 16h-1v-4h1m0-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' stroke-width='2' stroke-linejoin='round' stroke-linecap='round'></path></svg><p>$msg</p></div>"; ?>
    <?php if($err) echo "<div class='alert alert-error'><svg stroke='currentColor' viewBox='0 0 24 24' fill='none' xmlns='http://www.w3.org/2000/svg'><path d='M13 16h-1v-4h1m0-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' stroke-width='2' stroke-linejoin='round' stroke-linecap='round'></path></svg><p>$err</p></div>"; ?>

    <div class="grid-form" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
        
        <!-- قسم المحرك المحمول -->
        <div class="card glass">
            <h2 class="mb-3" style="font-size: 1.3rem;"><i class="fas fa-database text-accent"></i> محرك البيانات المحمول (PDE)</h2>
            <p class="text-muted mb-4" style="font-size: 0.9rem;">
                تم تصميم هذا النظام لجعل موقعك قابلاً للنقل بنسبة 100%. يقوم النظام تلقائياً بحفظ كل البيانات (المنتجات، الحسابات، الطلبات) في ملف SQL داخل مجلد المشروع.
            </p>
            
            <div style="background: rgba(16, 185, 129, 0.05); padding: 15px; border-radius: 10px; border: 1px solid var(--border-glass);" class="mb-4">
                <p style="font-size: 0.85rem;"><strong>آخر نسخة تلقائية:</strong> <span class="text-accent"><?= $lastBackup ?></span></p>
                <p style="font-size: 0.85rem; margin-top: 5px;"><strong>المسار:</strong> <code>database/.portable/portable_data.sql</code></p>
            </div>

            <form method="POST">
                <?php csrfField(); ?>
                <button type="submit" name="trigger_backup" class="btn" style="width: 100%;">
                    <i class="fas fa-file-export"></i> تحديث النسخة المحمولة يدوياً
                </button>
            </form>
            
            <p class="mt-3" style="font-size: 0.8rem; color: var(--text-muted);">
                <i class="fas fa-info-circle"></i> يتم تحديث هذه النسخة تلقائياً بعد كل عملية إضافة أو حذف هامة في الموقع.
            </p>
        </div>

        <!-- قسم الأمان والتعليمات -->
        <div class="card glass">
            <h2 class="mb-3" style="font-size: 1.3rem;"><i class="fas fa-shield-alt text-success"></i> أمان البيانات</h2>
            <ul style="list-style: none; padding: 0; font-size: 0.9rem;">
                <li class="mb-3 d-flex" style="gap: 10px;">
                    <i class="fas fa-check-circle text-success mt-1"></i>
                    <span>عند نقل مجلد الموقع إلى جهاز جديد، سيقوم النظام تلقائياً بتغذية قاعدة البيانات من ملف النسخة المحمولة.</span>
                </li>
                <li class="mb-3 d-flex" style="gap: 10px;">
                    <i class="fas fa-check-circle text-success mt-1"></i>
                    <span>يتم حماية ملف النسخة المحمولة بملف <code>.htaccess</code> لمنع الوصول الخارجي إليه.</span>
                </li>
                <li class="mb-3 d-flex" style="gap: 10px;">
                    <i class="fas fa-check-circle text-success mt-1"></i>
                    <span>لأمان إضافي، احتفظ دائماً بنسخة من ملف <code>portable_data.sql</code> خارج السيرفر.</span>
                </li>
            </ul>
            
            <div style="margin-top: 25px;">
                <a href="../BACKUP_GUIDE.md" target="_blank" class="text-accent" style="text-decoration: none; font-weight: 600;">
                    <i class="fas fa-book"></i> عرض دليل النسخ الاحتياطي الكامل
                </a>
            </div>
        </div>

    </div>
</div>

<?php require_once '../components/header.php'; ?>
