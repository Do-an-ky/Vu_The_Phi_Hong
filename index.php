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
    <link rel="icon" type="image/svg+xml" href="public/favicon.svg?v=1">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập | RESTAURANT</title>
    <link rel="stylesheet" href="public/css/login.css?v=<?php echo filemtime(__DIR__ . '/public/css/login.css'); ?>">
    <script src="public/js/login.js?v=<?php echo filemtime(__DIR__ . '/public/js/login.js'); ?>" defer></script>
</head>
<body>
    <main class="login-layout">
<section class="form-panel" aria-labelledby="login-title">
            <div class="login-form-wrap">
                <p class="form-eyebrow">RESTAURANT</p>
                <h1 id="login-title">Đăng nhập</h1>
                <p class="intro">Đăng nhập để vào hệ thống quản lý nhà hàng.</p>
                <?php if ($errorMessage !== ''): ?>
                    <div class="login-error" role="alert"><?php echo e($errorMessage); ?></div>
                <?php endif; ?>
                <form method="post" action="index.php">
                    <?php csrfInput(); ?>
                    <div class="field">
                        <label for="username">Tên đăng nhập</label>
                        <input id="username" name="username" autocomplete="username" autocapitalize="none" spellcheck="false" maxlength="50" placeholder="Nhập tên đăng nhập" value="<?php echo e(is_string($_POST['username'] ?? null) ? $_POST['username'] : ''); ?>" required>
                    </div>
                    <div class="field">
                        <label for="password">Mật khẩu</label>
                        <div class="password-field">
                            <input id="password" name="password" type="password" autocomplete="current-password" placeholder="Nhập mật khẩu" required>
                            <button type="button" id="toggle-password" aria-controls="password" aria-label="Hiện mật khẩu" aria-pressed="false" hidden>Hiện</button>
                        </div>
                    </div>
                    <button class="submit-button" name="action" value="login">Đăng nhập <span aria-hidden="true">→</span></button>
                </form>
                <p class="help-text">Cần hỗ trợ tài khoản? Liên hệ quản trị viên.</p>
            </div>
        </section>
    </main>
</body>
</html>