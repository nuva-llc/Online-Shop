
<?php
/**
 * Weapons Store - نظام إدارة الجلسات والصلاحيات المحسّن
 * يتحكم هذا الملف في بقاء المستخدم متصلاً ويتحقق من هويته
 * تم تحسينه بمعايير أمان OWASP
 */

// ============================
// إعدادات الجلسة الآمنة (Secure Session Configuration)
// ============================
ini_set('session.cookie_httponly', 1);  // منع JavaScript من الوصول للـ Session Cookie
ini_set('session.cookie_samesite', 'Strict'); // حماية من CSRF
ini_set('session.use_strict_mode', 1);  // رفض Session IDs غير معروفة
ini_set('session.use_only_cookies', 1); // استخدام الكوكيز فقط

// في بيئة الإنتاج، فعّل هذا السطر لإجبار HTTPS
// ini_set('session.cookie_secure', 1);

// التأكد من بدء الجلسة في جميع الصفحات التي تستدعي هذا الملف
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================
// نظام حماية CSRF (Cross-Site Request Forgery Protection)
// ============================

/**
 * توليد رمز CSRF فريد للحماية من الهجمات
 * @return string
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * التحقق من صحة رمز CSRF المُرسل
 * @param string $token الرمز المُرسل من النموذج
 * @return bool
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * طباعة حقل CSRF مخفي في النماذج (HTML Input)
 */
function csrfField() {
    echo '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}

// ============================
// إدارة تسجيل الدخول والصلاحيات
// ============================

/**
 * التحقق من تسجيل دخول المستخدم ومنعه من الوصول لصفحات الأعضاء إذا كان غير مسجل
 * يتم استدعاؤها في الصفحات الحساسة (مثل البروفايل، السلة)
 */
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        // إذا لم يكن مسجلاً، يتم توجيهه لصفحة الدخول
        header("Location: " . SITE_URL . "auth.php");
        exit;
    }
}

/**
 * التحقق من صلاحيات المدير (Admin) ومنع المستخدمين العاديين من دخول لوحة التحكم
 * يتم استدعاؤها في صفحات مجلد admin/
 */
function checkAdmin() {
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
        // إذا لم يكن مديراً، يتم توجيهه للصفحة الرئيسية
        header("Location: " . SITE_URL . "index.php");
        exit;
    }
}

// ============================
// Rate Limiting لمنع هجمات Brute Force
// ============================

/**
 * التحقق من عدد محاولات تسجيل الدخول لمنع Brute Force
 * @param string $identifier اسم المستخدم أو البريد الإلكتروني
 * @return bool true إذا كان مسموحاً، false إذا تم تجاوز الحد
 */
function checkLoginAttempts($identifier) {
    $key = "login_attempts_" . md5($identifier . $_SERVER['REMOTE_ADDR']);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'time' => time()];
    }
    
    // إعادة تعيين المحاولات بعد 15 دقيقة (900 ثانية)
    if (time() - $_SESSION[$key]['time'] > 900) {
        $_SESSION[$key] = ['count' => 0, 'time' => time()];
    }
    
    // السماح بـ 5 محاولات فقط خلال 15 دقيقة
    if ($_SESSION[$key]['count'] >= 5) {
        return false;
    }
    
    return true;
}

/**
 * تسجيل محاولة تسجيل دخول فاشلة
 * @param string $identifier
 */
function recordFailedLogin($identifier) {
    $key = "login_attempts_" . md5($identifier . $_SERVER['REMOTE_ADDR']);
    if (isset($_SESSION[$key])) {
        $_SESSION[$key]['count']++;
    }
}

/**
 * الحصول على الوقت المتبقي للانتظار بعد تجاوز عدد المحاولات
 * @param string $identifier
 * @return int عدد الثواني المتبقية
 */
function getRemainingLockoutTime($identifier) {
    $key = "login_attempts_" . md5($identifier . $_SERVER['REMOTE_ADDR']);
    if (isset($_SESSION[$key])) {
        $elapsed = time() - $_SESSION[$key]['time'];
        return max(0, 900 - $elapsed);
    }
    return 0;
}

/**
 * إعادة تعيين محاولات تسجيل الدخول (عند النجاح)
 * @param string $identifier
 */
function resetLoginAttempts($identifier) {
    $key = "login_attempts_" . md5($identifier . $_SERVER['REMOTE_ADDR']);
    unset($_SESSION[$key]);
}
?>
