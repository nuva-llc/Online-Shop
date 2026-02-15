<?php
/**
 * Weapons Store - لإدارة ومتابعة طلبات الشراء
 * يسمح للمسؤول برؤية كافة العمليات التي تمت في الموقع وحالتها
 */

require_once '../includes/session.php';
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// التحقق من صلاحيات المدير
checkAdmin();

/**
 * جلب قائمة كافة الطلبات المسجلة مع اسم المستخدم المرتبط بكل طلب
 */
$sql = "SELECT orders.*, users.username 
        FROM orders 
        LEFT JOIN users ON orders.user_id = users.id 
        ORDER BY created_at DESC";

$orders = $pdo->query($sql)->fetchAll();

require_once '../components/header.php';
?>

<div class="container" style="padding: 40px 0;">
    <a href="dashboard.php" class="btn btn-back"><i class="fas fa-chevron-left"></i> <span data-i18n="nav-back">العودة</span></a>
    <h1 class="mb-4"><i class="fas fa-shopping-cart"></i> <span data-i18n="admin-orders-title">إدارة ومتابعة الطلبات</span></h1>
    
    <div class="card glass mt-4">
        <h3 class="mb-4" data-i18n="sales-history">سجل المبيعات والعمليات الكلي</h3>
        <div class="table-responsive">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-glass);">
                        <th style="padding: 12px;" data-i18n="th-order-id">رقم الطلب</th>
                        <th style="padding: 12px;" data-i18n="th-client">العميل (اسم المستخدم)</th>
                        <th style="padding: 12px;" data-i18n="th-total">قيمة الطلب</th>
                        <th style="padding: 12px;" data-i18n="th-status">الحالة</th>
                        <th style="padding: 12px;" data-i18n="th-order-date">تاريخ العملية</th>
                        <th style="padding: 12px;" data-i18n="th-notes">ملاحظات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="6" class="text-center p-3 text-muted" data-i18n="no-orders-found">لا توجد عمليات شراء مسجلة في النظام بعد.</td></tr>
                    <?php else: ?>
                        <?php foreach ($orders as $o): ?>
                        <tr style="border-bottom: 1px solid var(--border-glass);">
                            <td style="padding: 12px;"><strong>#<?= $o['id'] ?></strong></td>
                            <td style="padding: 12px;"><?= $o['username'] ?: '<span class="text-muted" data-i18n="user-deleted">مستخدم محذوف</span>' ?></td>
                            <td style="padding: 12px; font-weight: bold; color: var(--accent);"><?= formatPrice($o['total_amount']) ?></td>
                            <td style="padding: 12px;">
                                <?php if($o['status'] == 'completed'): ?>
                                    <span class="badge badge-success"><i class="fas fa-check"></i> <span data-i18n="order-status-completed">مكتملة</span></span>
    
                                <?php elseif($o['status'] == 'cancelled'): ?>
                                    <span class="badge badge-danger" data-i18n="order-status-cancelled">ملغي</span>
                                <?php else: ?>
                                    <span class="badge badge-warning" data-i18n="order-status-pending">قيد الانتظار</span>
                                <?php endif; ?>
                            </td>
    
                            <td style="padding: 12px; font-size: 0.9rem; color: var(--text-muted);"><?= date('Y/m/d H:i', strtotime($o['created_at'])) ?></td>
                            <td style="padding: 12px;">
                                <span class="text-muted" style="font-size: 0.8rem;" data-i18n="note-direct-purchase">نظام شراء مباشر</span>
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
