<?php

// Khởi tạo dùng chung cho trang đăng nhập và khu vực admin.
date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start(array('cookie_httponly' => true, 'cookie_samesite' => 'Lax', 'use_strict_mode' => true));
header('Cache-Control: no-store');
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../models/Restaurant.php';
require_once __DIR__ . '/../controllers/auth/AuthController.php';

if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$user = null;
$model = null;
$errorMessage = '';
try {
    $model = new Restaurant();
    if (isset($_SESSION['user_id'])) {
        $user = $model->one('SELECT id, name, username, role FROM users WHERE id = ?', array($_SESSION['user_id']));
        if (!$user || !in_array($user['role'], array('admin', 'staff', 'nhanvien'), true)) {
            unset($_SESSION['user_id']);
            $user = null;
        }
    }
} catch (Throwable $error) {
    error_log($error->getMessage());
    $errorMessage = 'Không kết nối được CSDL. Hãy kiểm tra MySQL trong XAMPP.';
}
