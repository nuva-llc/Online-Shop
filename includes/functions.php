<?php
/**
 * Weapons Store - مكتبة الدوال الأساسية المحسّنة أمنياً
 * تم تطويرها وفق معايير OWASP للأمان
 */

// ============================
// دوال التنظيف والحماية من XSS
// ============================

/**
 * تنظيف البيانات المدخلة من الفراغات والرموز الضارة
 * @param string $data
 * @return string
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * عرض نص آمن من XSS (للاستخدام في HTML)
 * @param mixed $value
 * @return string
 */
function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * التحقق من صحة البريد الإلكتروني
 * @param string $email
 * @return bool
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * التحقق من قوة كلمة المرور
 * @param string $password
 * @return bool
 */
function validatePasswordStrength($password) {
    // 8 أحرف على الأقل، حرف كبير، حرف صغير، رقم
    if (strlen($password) < 8) return false;
    if (!preg_match('/[A-Z]/', $password)) return false;
    if (!preg_match('/[a-z]/', $password)) return false;
    if (!preg_match('/[0-9]/', $password)) return false;
    return true;
}

// ============================
// رفع الملفات الآمن (Secure File Upload)
// ============================

/**
 * معالجة ورفع الصور بشكل آمن مع التحقق الصارم والحماية من تنفيذ الأكواد الخبيثة
 * @param array $file (مصفوفة الملف من $_FILES)
 * @param string $targetDir (مسار الحفظ)
 * @return string|false (اسم الملف الجديد أو false عند الفشل)
 */
function uploadImage($file, $targetDir) {
    // 1. التحقق من عدم وجود خطأ في الرفع
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    // 2. التحقق من الحجم (الحد الأقصى 5 ميجابايت)
    if ($file['size'] > 5 * 1024 * 1024) {
        return false;
    }

    // 3. التحقق من نوع MIME الحقيقي (من محتوى الملف وليس الامتداد)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowedMimes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp'
    ];

    if (!in_array($mimeType, $allowedMimes)) {
        return false;
    }

    // 4. التحقق من امتداد الملف
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($extension, $allowedExtensions)) {
        return false;
    }

    // 5. توليد اسم فريد وآمن
    $filename = bin2hex(random_bytes(16)) . '.' . $extension;
    $targetPath = $targetDir . $filename;

    // التأكد من وجود المجلد
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    // 6. التحقق من توفر مكتبة GD لإعادة معالجة الصورة (زيادة الأمان)
    if (function_exists('imagecreatefromstring')) {
        $img = @imagecreatefromstring(file_get_contents($file['tmp_name']));
        if ($img) {
            $saved = false;
            switch ($mimeType) {
                case 'image/jpeg':
                    $saved = imagejpeg($img, $targetPath, 90);
                    break;
                case 'image/png':
                    $saved = imagepng($img, $targetPath, 9);
                    break;
                case 'image/gif':
                    $saved = imagegif($img, $targetPath);
                    break;
                case 'image/webp':
                    $saved = imagewebp($img, $targetPath, 90);
                    break;
            }
            imagedestroy($img);
            
            if ($saved) {
                return $filename;
            }
        }
    }

    // 7. Fallback: إذا لم تكن GD موجودة أو فشلت المعالجة، نستخدم الرفع المباشر الآمن بعد التحقق من MIME
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $filename;
    }

    return false;
}

// ============================
// دوال التنقل والعرض
// ============================

/**
 * إعادة توجيه المستخدم لصفحة معينة مع إيقاف تنفيذ الكود
 * @param string $url
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * تنسيق الأرقام لتظهر بصيغة العملة (دولار)
 * @param float $price
 * @return string
 */
function formatPrice($price) {
    $p = (float)$price;
    if ($p < 0) $p = 0; // القيمة بالسالب تساوي صفر
    return number_format($p, 2) . ' $';
}

// ============================
// دوال التحقق من الصلاحيات
// ============================

/**
 * التحقق مما إذا كان الزائر قد سجل دخوله بالفعل
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * التحقق مما إذا كان المستخدم الحالي هو مسؤول (Admin)
 * @return bool
 */
function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

// ============================
// دوال إدارة الرصيد
// ============================

/**
 * التحقق من كفاية رصيد المستخدم لإتمام عملية شراء
 * @param int $userId
 * @param float $amount
 * @param PDO $pdo
 * @return bool
 */
function checkBalance($userId, $amount, $pdo) {
    $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $balance = $stmt->fetchColumn();
    return $balance >= $amount;
}

/**
 * تحديث رصيد المستخدم (خصم المبلغ)
 * @param int $userId
 * @param float $amount
 * @param PDO $pdo
 * @return bool
 */
function updateBalance($userId, $amount, $pdo) {
    $stmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
    return $stmt->execute([$amount, $userId]);
}

// ============================
// معالجة الأخطاء الآمنة
// ============================

/**
 * تسجيل الأخطاء في ملف سري بدلاً من عرضها للمستخدم
 * @param string $message
 * @param string $type نوع الخطأ (error, warning, info)
 */
function logError($message, $type = 'error') {
    $logDir = __DIR__ . '/../logs/';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . 'app_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$type] $message\n";
    
    error_log($logMessage, 3, $logFile);
}

/**
 * إطلاق عملية التوريد التلقائي للبيانات (Portable Data Engine)
 * يتم استدعاؤها بعد كل عملية تغيير هامة لضمان قابلية النقل
 */
function triggerPortableBackup() {
    global $pdo;
    require_once __DIR__ . '/backup_manager.php';
    $pde = new BackupManager($pdo);
    return $pde->export();
}
?>
