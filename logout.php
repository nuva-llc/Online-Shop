<?php
require_once 'includes/session.php';
require_once 'includes/functions.php';

// إنهاء الجلسة
session_unset();
session_destroy();

// إعادة التوجيه للصفحة الرئيسية
redirect('index.php');
?>
