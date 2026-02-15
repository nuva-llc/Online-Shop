<?php
require_once '../includes/session.php';
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول أولاً.', 'redirect' => SITE_URL . 'auth.php']);
    exit;
}

// التحقق من CSRF Token (من Header أو POST)
$csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!verifyCSRFToken($csrfToken)) {
    echo json_encode(['success' => false, 'message' => 'خطأ في التحقق من الطلب. يرجى تحديث الصفحة.']);
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = $_POST['product_id'] ?? null;
    $quantity = (int) ($_POST['quantity'] ?? 1);

    if (!$productId || $quantity < 1) {
        echo json_encode(['success' => false, 'message' => 'بيانات غير صالحة.']);
        exit;
    }

    // التحقق من المنتج والكمية المتوفرة
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'المنتج غير موجود.']);
        exit;
    }

    if ($product['quantity'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'الكمية المطلوبة غير متوفرة.']);
        exit;
    }

    // إضافة للسلة في الجلسة
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if (isset($_SESSION['cart'][$productId])) {
        // تحديث الكمية إذا كان موجود مسبقاً
        $_SESSION['cart'][$productId]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$productId] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'image' => $product['image_1'],
            'quantity' => $quantity
        ];
    }

    echo json_encode(['success' => true, 'message' => 'تمت الإضافة للسلة.']);
}
?>
