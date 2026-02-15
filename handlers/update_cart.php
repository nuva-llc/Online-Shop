<?php
require_once '../includes/session.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) exit;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $action = $_POST['action'];

    if (isset($_SESSION['cart'][$id])) {
        if ($action === 'increase') {
            $_SESSION['cart'][$id]['quantity']++;
        } elseif ($action === 'decrease') {
            $_SESSION['cart'][$id]['quantity']--;
            if ($_SESSION['cart'][$id]['quantity'] < 1) {
                unset($_SESSION['cart'][$id]);
            }
        } elseif ($action === 'remove') {
            unset($_SESSION['cart'][$id]);
        }
    }
}
?>
