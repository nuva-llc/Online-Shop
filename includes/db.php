<?php
/**
 * Weapons Store - ملف الاتصال بقاعدة البيانات المحسّن أمنياً
 * يستخدم تقنية PDO لضمان الأمان والكفاءة
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

try {
    // إنشاء الاتصال بقاعدة البيانات مع تعيين ترميز اللغة UTF8
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
        DB_USER, 
        DB_PASS
    );
    
    // ضبط إعدادات PDO للتعامل مع الأخطاء والنتائج
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // عرض الاستثناءات عند حدوث أخطاء
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // جلب البيانات كصفوف مرتبطة
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); // تعطيل المحاكاة لزيادة الأمان
    
    // ============================
    // محرك البيانات المحمول (Portable Data Engine)
    // ============================
    require_once __DIR__ . '/backup_manager.php';
    $pde = new BackupManager($pdo);

    try {
        // التحقق مما إذا كانت قاعدة البيانات فارغة (لا توجد جداول أو لا توجد بيانات في جدول المستخدمين)
        $hasUsers = false;
        try {
            $check = $pdo->query("SELECT count(*) FROM users")->fetchColumn();
            $hasUsers = ($check > 0);
        } catch (Exception $e) {
            // الجداول غير موجودة أصلاً
            $hasUsers = false;
        }

        if (!$hasUsers) {
            if ($pde->hasBackup()) {
                // استرجاع البيانات الأصلية للمستخدم إذا كانت موجودة في المجلد المحمول
                $pde->import();
            } else {
                // إذا لم يوجد باك اب محمول، نستخدم البيانات الافتراضية لأول مرة
                $seederPath = __DIR__ . '/../handlers/seed_db.php';
                if (file_exists($seederPath)) {
                    ob_start();
                    include $seederPath;
                    ob_end_clean();
                }
            }
        }
    } catch (Exception $e) {
        // فشل صامت لضمان عدم توقف الموقع
    }
    
} catch(PDOException $e) {
    // تسجيل الخطأ في ملف سري (للمطورين فقط)
    logError("DB Connection Failed: " . $e->getMessage(), 'critical');
    
    // عرض رسالة عامة للمستخدم دون كشف تفاصيل قاعدة البيانات
    die("⚠️ عذراً، حدث خطأ في الاتصال بالخادم. الرجاء المحاولة لاحقاً.");
}
?>
