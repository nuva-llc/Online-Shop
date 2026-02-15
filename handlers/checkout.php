<?php
require_once '../includes/session.php';
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) redirect('../auth.php');

// التحقق من طلب POST و CSRF Token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // التحقق من CSRF Token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_msg'] = "خطأ في التحقق من الطلب. الرجاء المحاولة مرة أخرى.";
        $_SESSION['flash_type'] = "error";
        redirect('../cart.php');
    }
    
    $cart = $_SESSION['cart'] ?? [];
    if (empty($cart)) redirect('../cart.php');

    $userId = $_SESSION['user_id'];
    $total = 0;

    // إعادة حساب المجموع للتحقق
    foreach ($cart as $item) {
        $total += $item['price'] * $item['quantity'];
    }

    try {
        $pdo->beginTransaction();

        // 1. التحقق من الرصيد
        $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ? FOR UPDATE"); // Lock row
        $stmt->execute([$userId]);
        $currentBalance = $stmt->fetchColumn();

        if ($currentBalance < $total) {
            throw new Exception("رصيدك غير كافي لإتمام العملية.");
        }

        // 2. إنشاء الطلب مباشرة بحالة completed
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'completed')");
        $stmt->execute([$userId, $total]);
        $orderId = $pdo->lastInsertId();

        // 3. إدراج العناصر وتحديث المخزون
        foreach ($cart as $id => $item) {
            // التحقق من المخزون الحالي
            $stmt = $pdo->prepare("SELECT quantity FROM products WHERE id = ? FOR UPDATE");
            $stmt->execute([$id]);
            $stock = $stmt->fetchColumn();

            if ($stock < $item['quantity']) {
                throw new Exception("الكمية المطلوبة من " . $item['name'] . " غير متوفرة.");
            }

            // إنقاص المخزون
            $stmt = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
            $stmt->execute([$item['quantity'], $id]);

            // إضافة عنصر الطلب
            $subtotal = $item['price'] * $item['quantity'];
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$orderId, $id, $item['quantity'], $item['price'], $subtotal]);
        }

        // 4. خصم الرصيد
        $stmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
        $stmt->execute([$total, $userId]);

        $pdo->commit();
        triggerPortableBackup(); // حفظ التغييرات محمولاً

        // 5. إفراغ السلة
        unset($_SESSION['cart']);

        // 6. توجيه مع رسالة نجاح
        $_SESSION['flash_msg'] = "تم الطلب بنجاح! رقم الطلب: $orderId";
        $_SESSION['flash_type'] = "success";
        redirect('../profile.php');

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['flash_msg'] = "خطأ: " . $e->getMessage();
        $_SESSION['flash_type'] = "error";
        redirect('../cart.php');
    }
} else {
    redirect('../cart.php');
}
?>
