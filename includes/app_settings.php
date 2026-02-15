<?php
/**
 * Weapons Store - ملف الإعدادات المركزية للتطبيق
 * يسمح بتخصيص المشروع لأفكار مختلفة (متجر ملابس، إلكترونيات، إلخ)
 */

return [
    // ============================
    // إعدادات التطبيق الأساسية
    // ============================
    'app' => [
        'name' => 'Weapons Store',
        'name_ar' => 'متجر الأسلحة',
        'tagline' => 'Your Premier Tactical Destination',
        'tagline_ar' => 'وجهتك الأولى للتجهيزات التكتيكية',
        'version' => '1.0.4',
        'theme' => 'tactical', // Options: tactical, fashion, electronics, general
    ],
    
    // ============================
    // إعدادات العملة والأسعار
    // ============================
    'currency' => [
        'symbol' => '$',
        'code' => 'USD',
        'position' => 'after', // before or after
        'decimals' => 2,
    ],
    
    // ============================
    // الميزات المفعلة/المعطلة
    // ============================
    'features' => [
        'wishlist' => false,           // قائمة الأمنيات
        'reviews' => false,            // نظام التقييمات
        'multi_currency' => false,     // عملات متعددة
        'coupons' => false,            // كوبونات الخصم
        'social_login' => false,       // تسجيل عبر السوشيال
        'newsletter' => false,         // النشرة البريدية
        'compare_products' => false,   // مقارنة المنتجات
        'stock_alerts' => false,       // تنبيهات المخزون
    ],
    
    // ============================
    // إعدادات التحميل والصور
    // ============================
    'uploads' => [
        'max_size' => 5 * 1024 * 1024, // 5MB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'products_path' => 'uploads/products/',
        'users_path' => 'uploads/users/',
    ],
    
    // ============================
    // إعدادات الأمان
    // ============================
    'security' => [
        'session_lifetime' => 3600,        // ساعة واحدة
        'max_login_attempts' => 5,
        'lockout_duration' => 900,         // 15 دقيقة
        'password_min_length' => 8,
        'require_email_verification' => false,
    ],
    
    // ============================
    // إعدادات Pagination
    // ============================
    'pagination' => [
        'products_per_page' => 12,
        'orders_per_page' => 10,
        'users_per_page' => 20,
    ],
    
    // ============================
    // إعدادات SEO
    // ============================
    'seo' => [
        'site_title' => 'Weapons Store - Premium Tactical Gear',
        'meta_description' => 'Your premier destination for tactical weapons and gear',
        'meta_keywords' => 'weapons, tactical, guns, ammunition, gear',
    ],
    
    // ============================
    // روابط التواصل الاجتماعي
    // ============================
    'social' => [
        'facebook' => 'https://facebook.com',
        'twitter' => 'https://twitter.com',
        'instagram' => 'https://www.instagram.com/vip.qiu/',
        'youtube' => '',
        'tiktok' => '',
    ],
    
    // ============================
    // الفئات الافتراضية (يمكن تحميلها من DB)
    // ============================
    'default_categories' => [
        'بنادق هجومية',
        'مسدسات',
        'ذخيرة',
        'معدات تكتيكية',
        'ملحقات',
    ],
];
