<?php

date_default_timezone_set('Asia/Ho_Chi_Minh');
session_start(array('cookie_httponly' => true, 'cookie_samesite' => 'Lax', 'use_strict_mode' => true));
header('Cache-Control: no-store');
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/models/Restaurant.php';
require_once __DIR__ . '/controllers/auth/AuthController.php';
require_once __DIR__ . '/controllers/staff/StaffOrderController.php';

if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$page = $_GET['page'] ?? 'tables';
if (!is_string($page)) {
    $page = 'tables';
}
$errorMessage = '';
$user = null;
$model = null;
$orderDraft = null;
$sidebarCounts = array('tables' => 0, 'orders' => 0, 'kitchen' => 0, 'payments' => 0);

try {
    $model = new Restaurant();
    if (isset($_SESSION['user_id'])) {
        $user = $model->one('SELECT id, name, username, role FROM users WHERE id = ?', array($_SESSION['user_id']));
        if (!$user || !in_array($user['role'], array('nhanvien', 'staff', 'admin'), true)) {
            unset($_SESSION['user_id']);
            $user = null;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        kiemTraCsrf();
        $action = $_POST['action'] ?? '';
        if ($action === 'login') {
            dangNhap($model, $_POST['username'] ?? '', $_POST['password'] ?? '');
            chuyenTrang('index.php');
        }
        if (!$user) {
            throw new DomainException('Bạn cần đăng nhập để thao tác.');
        }
        if ($action === 'logout') {
            $_SESSION = array();
            session_regenerate_id(true);
            chuyenTrang('index.php');
        }
        $orderDraft = xuLyNhanVien($model, $user);
    }
    if ($user) {
        $sidebarCounts = $model->sidebarNotifications();
    }
} catch (DomainException $error) {
    $errorMessage = $error->getMessage();
} catch (Throwable $error) {
    error_log($error->getMessage());
    $errorMessage = 'Không thể xử lý. Kiểm tra MySQL và chạy database/upgrade.sql trước khi sử dụng.';
}

if (!$user) {
    if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'KitchenFetch') {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(401);
        echo json_encode(array('success' => false, 'message' => 'Bạn cần đăng nhập lại.'));
        exit;
    }
    chuyenTrang('index.php');
}

if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'KitchenFetch' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(400);
    echo json_encode(array('success' => false, 'message' => $errorMessage ?: 'Không thể lưu xác nhận.'));
    exit;
}

// JavaScript gọi đường dẫn này để cập nhật số thông báo mà không tải lại trang.
if (($_GET['api'] ?? '') === 'sidebar-counts') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($sidebarCounts);
    exit;
}

$views = array(
    'login' => array('Đăng nhập', 'auth/login.php'),
    'tables' => array('Quản lý bàn', 'staff/tables/table-list.php'),
    'orders' => array('Gọi món', 'staff/orders/order-create.php'),
    'order-detail' => array('Theo dõi phục vụ', 'staff/orders/order-detail.php'),
    'ticket' => array('Phiếu order', 'staff/orders/ticket.php'),
    'kitchen' => array('Khu vực bếp', 'staff/kitchen/kitchen-orders.php'),
    'payments' => array('Thanh toán', 'staff/payments/checkout.php'),
    'invoice' => array('Hóa đơn', 'staff/payments/invoice.php')
);
if (!isset($views[$page])) {
    $page = 'tables';
}
$title = $views[$page][0];

// Render trước để lỗi CSDL không làm xuất hiện trang HTML bị cắt giữa chừng.
ob_start();
try {
    require __DIR__ . '/views/' . $views[$page][1];
    $content = ob_get_clean();
} catch (Throwable $error) {
    ob_end_clean();
    if ($error instanceof DomainException) {
        $errorMessage = $error->getMessage();
    } else {
        error_log($error->getMessage());
        $errorMessage = 'Chưa tải được dữ liệu. Kiểm tra MySQL và chạy database/upgrade.sql.';
    }
    $content = '<a class="button" href="?page=tables">Về danh sách bàn</a>';
}
?>
<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php echo e($title); ?> | RESTAURANT</title>
        <link rel="stylesheet" href="public/css/style.css?v=<?php echo filemtime(__DIR__ . '/public/css/style.css'); ?>">
        <link rel="stylesheet" href="public/css/order-layout.css?v=cart-column-2">
        <link rel="stylesheet" href="public/css/item-progress.css?v=1">
        <script defer src="public/js/script.js?v=<?php echo filemtime(__DIR__ . '/public/js/script.js'); ?>"></script>
        <link rel="stylesheet" href="public/css/ui-refresh.css?v=<?php echo filemtime(__DIR__ . '/public/css/ui-refresh.css'); ?>">
        <link rel="stylesheet" href="public/css/staff-design.css?v=<?php echo filemtime(__DIR__ . '/public/css/staff-design.css'); ?>">
    </head>
    <body class="staff-shell <?php if ($page === 'tables') echo 'staff-tables-page'; ?>">
        <aside class="sidebar no-print">
            <a class="brand" href="?page=tables">RESTAURANT</a>
            <div class="nav-label">KHÔNG GIAN NHÂN VIÊN</div>
            <?php if ($user): ?>
                <nav>
                    <?php if ($user['role'] === 'admin'): ?>
                        <a class="admin-return-link" href="admin.php">← Quay lại trang quản trị</a>
                    <?php endif; ?>
                    <a href="?page=tables" data-notification-link="tables" class="<?php if ($page === 'tables') echo 'active'; ?>">
                        <span class="nav-text">▦ Quản lý bàn</span>
                        <span class="nav-badge" data-notification="tables" <?php if ($sidebarCounts['tables'] === 0) echo 'hidden'; ?>><?php echo $sidebarCounts['tables']; ?></span>
                    </a>
                    <a href="?page=orders" data-notification-link="orders" class="<?php if ($page === 'orders') echo 'active'; ?>">
                        <span class="nav-text">＋ Gọi món</span>
                        <span class="nav-badge" data-notification="orders" <?php if ($sidebarCounts['orders'] === 0) echo 'hidden'; ?>><?php echo $sidebarCounts['orders']; ?></span>
                    </a>
                    <a href="?page=kitchen" data-notification-link="kitchen" class="<?php if ($page === 'kitchen') echo 'active'; ?>">
                        <span class="nav-text">◷ Khu vực bếp</span>
                        <span class="nav-badge" data-notification="kitchen" <?php if ($sidebarCounts['kitchen'] === 0) echo 'hidden'; ?>><?php echo $sidebarCounts['kitchen']; ?></span>
                    </a>
                    <a href="?page=payments" data-notification-link="payments" class="<?php if ($page === 'payments') echo 'active'; ?>">
                        <span class="nav-text">₫ Thanh toán</span>
                        <span class="nav-badge" data-notification="payments" <?php if ($sidebarCounts['payments'] === 0) echo 'hidden'; ?>><?php echo $sidebarCounts['payments']; ?></span>
                    </a>
                </nav>
                <div class="sidebar-bottom">
                    <p><?php echo e($user['name']); ?></p>
                    <form method="post">
                        <?php csrfInput(); ?>
                        <button class="button" name="action" value="logout">Đăng xuất</button>
                    </form>
                </div>
            <?php endif; ?>
        </aside>
        <div class="workspace">
            <header class="no-print">
                <span>Nhà hàng / <?php echo e($title); ?></span>
                <div class="staff-header-profile">
                    <time datetime="<?php echo date('c'); ?>"><?php echo date('H:i · d/m/Y'); ?></time>
                    <span class="staff-avatar" aria-hidden="true">NV</span>
                    <span class="staff-header-name"><?php echo e($user['name']); ?></span>
                </div>
            </header>
            <main>
                <?php if ($errorMessage !== ''): ?>
                    <div class="notice error no-print" role="alert"><?php echo e($errorMessage); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="notice no-print" role="status"><?php echo e($_SESSION['message']); ?></div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>
                <?php echo $content; ?>
            </main>
        </div>
    </body>
</html>
