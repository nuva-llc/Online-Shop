<?php
/**
 * Weapons Store - لوحة تحكم المسؤول (Dashboard)
 * تعرض إحصائيات عامة عن المتجر، الربح، وأحدث النشاطات
 */

require_once '../includes/session.php';
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// التحقق من صلاحيات المسؤول قبل عرض المحتوى
checkAdmin();

/**
 * جلب الإحصائيات الأساسية لقاعدة البيانات
 */
$stats = [
    'users'    => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'products' => $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'orders'   => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'revenue'  => $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status = 'completed'")->fetchColumn() ?: 0
];

/**
 * جلب آخر 5 طلبات تمت في الموقع لعرضها في الجدول السريع
 */
$recentOrders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5")->fetchAll();

require_once '../components/header.php';
?>

<div class="container" style="padding: 40px 0;">
    <h1 class="mb-3"><i class="fas fa-chart-line"></i> <span data-i18n="admin-title">لوحة التحكم الرئيسية</span></h1>
    
    <!-- صناديق الإحصائيات السريعة (Stats Cards) -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px; margin-bottom: 40px;">
        
        <a href="users.php" class="card d-flex align-center justify-between glass" style="text-decoration: none; color: inherit; transition: 0.3s;">
            <div>
                <p class="text-muted mb-3" data-i18n="total-users">إجمالي المستخدمين</p>
                <h2 style="font-size: 2.5rem;"><?= $stats['users'] ?></h2>
            </div>
            <div style="background: rgba(16, 185, 129, 0.1); padding: 15px; border-radius: 12px;">
                <i class="fas fa-users fa-2x text-accent"></i>
            </div>
        </a>
        
        <a href="products.php" class="card d-flex align-center justify-between glass" style="text-decoration: none; color: inherit; transition: 0.3s;">
            <div>
                <p class="text-muted mb-3" data-i18n="available-products">المنتجات المتوفرة</p>
                <h2 style="font-size: 2.5rem;"><?= $stats['products'] ?></h2>
            </div>
            <div style="background: rgba(16, 185, 129, 0.1); padding: 15px; border-radius: 12px;">
                <i class="fas fa-box-open fa-2x text-accent"></i>
            </div>
        </a>
        
        <a href="orders.php" class="card d-flex align-center justify-between glass" style="text-decoration: none; color: inherit; transition: 0.3s;">
            <div>
                <p class="text-muted mb-3" data-i18n="total-orders">إجمالي الطلبات</p>
                <h2 style="font-size: 2.5rem;"><?= $stats['orders'] ?></h2>
            </div>
            <div style="background: rgba(16, 185, 129, 0.1); padding: 15px; border-radius: 12px;">
                <i class="fas fa-shopping-bag fa-2x text-accent"></i>
            </div>
        </a>
        
        <div class="card d-flex align-center justify-between glass" style="border-right: 4px solid var(--success); padding: 20px;">
            <div style="flex: 1;">
                <p class="text-muted mb-2" style="font-size: 0.85rem;" data-i18n="net-profit">صافي الأرباح (المكتملة)</p>
                <h2 style="font-size: 1.6rem; font-weight: 800; color: var(--success); line-height: 1.2;"><?= formatPrice($stats['revenue']) ?></h2>
            </div>
            <div style="background: rgba(16, 185, 129, 0.2); padding: 12px; border-radius: 12px; margin-right: 10px;">
                <i class="fas fa-wallet fa-xl text-success"></i>
            </div>
        </div>

        <a href="maintenance.php" class="card d-flex align-center justify-between glass" style="text-decoration: none; color: inherit; transition: 0.3s; border: 1px dashed var(--accent);">
            <div style="flex: 1;">
                <p class="text-muted mb-2" style="font-size: 0.85rem;" data-i18n="admin-maintenance">الصيانة وقابلية النقل</p>
                <h3 style="font-size: 1.2rem; font-weight: 700;">Portable Data</h3>
            </div>
            <div style="background: rgba(16, 185, 129, 0.1); padding: 12px; border-radius: 12px;">
                <i class="fas fa-sync-alt fa-xl text-accent"></i>
            </div>
        </a>

    </div>

    <!-- جدول أحدث الطلبات -->
    <h2 class="mb-3"><i class="fas fa-history"></i> <span data-i18n="recent-orders">أحدث الطلبات المستلمة</span></h2>
    <div class="card glass">
        <div class="table-responsive">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-glass);">
                        <th style="padding: 15px;" data-i18n="th-order-id">رقم الطلب</th>
                        <th style="padding: 15px;" data-i18n="th-user-id">المستخدم ID</th>
                        <th style="padding: 15px;" data-i18n="th-total">المبلغ الإجمالي</th>
                        <th style="padding: 15px;" data-i18n="th-status">الحالة</th>
                        <th style="padding: 15px;" data-i18n="th-date">تاريخ الطلب</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentOrders)): ?>
                        <tr><td colspan="5" class="text-center p-3 text-muted" data-i18n="no-recent-orders">لا توجد طلبات مسجلة حالياً.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentOrders as $order): ?>
                        <tr style="border-bottom: 1px solid var(--border-glass);">
                            <td style="padding: 15px;"><strong>#<?= $order['id'] ?></strong></td>
                            <td style="padding: 15px;"><?= $order['user_id'] ?></td>
                            <td style="padding: 15px; font-weight: bold; color: var(--accent);"><?= formatPrice($order['total_amount']) ?></td>
                            <td style="padding: 15px;">
                                 <span class="badge badge-success" data-i18n="order-status-completed">مكتملة</span>
    
                            </td>
    
                            <td style="padding: 15px; color: var(--text-muted); font-size: 0.9rem;"><?= $order['created_at'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="text-center mt-3" style="padding: 15px;">
            <a href="orders.php" class="btn btn-secondary" style="font-size: 0.9rem;" data-i18n="btn-view-all-orders">عرض كافة الطلبات</a>
        </div>
    </div>
</div>

<?php 
// تضمين التذييل
require_once '../components/footer.php'; 
?>
