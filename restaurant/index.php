<?php

// Đầu vào chung: đăng nhập rồi chuyển đến khu vực theo quyền trong CSDL.
require_once __DIR__ . '/config/bootstrap.php';
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $model) {
        kiemTraCsrf();
        if (($_POST['action'] ?? '') !== 'login') {
            throw new DomainException('Hãy mở admin.php hoặc staff.php để thao tác.');
        }
        dangNhap($model, $_POST['username'] ?? '', $_POST['password'] ?? '');
        chuyenTrang('index.php');
    }
    if ($user) {
        chuyenTrang($user['role'] === 'admin' ? 'admin.php' : 'staff.php');
    }
} catch (DomainException $error) {
    $errorMessage = $error->getMessage();
} catch (Throwable $error) {
    error_log($error->getMessage());
    $errorMessage = 'Không đăng nhập được. Hãy thử lại.';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập | RESTAURANT</title>
    <link rel="stylesheet" href="public/css/admin.css?v=<?php echo filemtime(__DIR__ . '/public/css/admin.css'); ?>">
    <link rel="stylesheet" href="public/css/ui-refresh.css?v=<?php echo filemtime(__DIR__ . '/public/css/ui-refresh.css'); ?>">
</head>
<body class="login-screen">
    <main class="login-card">
        <div class="login-brand">RESTAURANT</div>
        <p class="eyebrow">CHÀO MỪNG TRỞ LẠI</p>
        <h1>Đăng nhập</h1>
        <p class="muted">Sử dụng tài khoản được cấp để vào hệ thống.</p>
        <?php if ($errorMessage !== ''): ?>
            <div class="notice error" role="alert"><?php echo e($errorMessage); ?></div>
        <?php endif; ?>
        <form method="post" action="index.php" class="editor-form">
            <?php csrfInput(); ?>
            <label>Tên đăng nhập
                <input name="username" autocomplete="username" maxlength="50" required>
            </label>
            <label>Mật khẩu
                <input name="password" type="password" autocomplete="current-password" required>
            </label>
            <button class="button primary" name="action" value="login">Đăng nhập →</button>
        </form>
    </main>
</body>
</html>
