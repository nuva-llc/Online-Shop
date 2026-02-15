<?php
/**
 * Weapons Store - ملف الإعدادات العام
 * يحتوي على إعدادات قاعدة البيانات والروابط الأساسية للموقع
 */

// إعدادات قاعدة البيانات (MariaDB/MySQL)
define('DB_HOST', 'localhost');
define('DB_NAME', 'weapons_store');
define('DB_USER', 'root');
define('DB_PASS', '');

// مسارات الموقع والروابط الأساسية
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];


// تحسين حساب مسار المشروع البرمجي ليكون أكثر مرونة على مختلف أنظمة التشغيل والـ Document Roots
$docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
$dirPath = str_replace('\\', '/', realpath(__DIR__));

// نقوم بعزل المسار النسبي للموقع عن المسار الفيزيائي للجهاز
$projectRoot = str_ireplace($docRoot, '', str_replace('\\', '/', dirname($dirPath)));

define('SITE_URL', $protocol . "://" . $host . $projectRoot . "/"); 
define('ROOT_PATH', dirname(__DIR__)); // المسار الفيزيائي للمجلد الرئيسي

// إصدار التطبيق (يستخدم لمنع التخزين المؤقت للمتصفح - Cache Busting)
define('APP_VERSION', '1.0.3'); 
?>
