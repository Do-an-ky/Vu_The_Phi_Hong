<?php

date_default_timezone_set('Asia/Ho_Chi_Minh');

// Cổng quản trị: xác thực -> controller -> model -> view.
require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/models/AdminModel.php';
require_once __DIR__ . '/controllers/admin/AdminValidation.php';
require_once __DIR__ . '/controllers/admin/AdminDashboardController.php';
require_once __DIR__ . '/controllers/admin/AdminUserController.php';
require_once __DIR__ . '/controllers/admin/AdminTableController.php';
require_once __DIR__ . '/controllers/admin/AdminCategoryController.php';
require_once __DIR__ . '/controllers/admin/AdminProductController.php';

if (!$user) {
    chuyenTrang('index.php');
}
if ($user['role'] !== 'admin') {
    http_response_code(403);
    echo '<meta charset="utf-8"><p>Bạn không có quyền quản trị.</p><a href="staff.php">Về khu vực nhân viên</a>';
    exit;
}

// Chỉ nạp file trong danh sách này, không dùng đường dẫn do người dùng gửi lên.
$pages = array(
    'dashboard' => array('Tổng quan', 'dashboard/admin-dashboard.php'),
    'users' => array('Quản lý nhân viên', 'users/admin-user-list.php'),
    'tables' => array('Quản lý bàn', 'tables/admin-table-list.php'),
    'categories' => array('Quản lý danh mục', 'categories/admin-category-list.php'),
    'products' => array('Quản lý sản phẩm', 'products/admin-product-list.php')
);
$page = $_GET['page'] ?? 'dashboard';
if (!is_string($page) || !isset($pages[$page])) {
    http_response_code(404);
    echo '<meta charset="utf-8"><p>Không tìm thấy trang.</p><a href="admin.php">Về tổng quan</a>';
    exit;
}
$title = $pages[$page][0];
$rows = array();
$categories = array();
$stats = array();
$form = array('id' => '', 'name' => '', 'username' => '', 'category_id' => '', 'price' => '', 'status' => 'Đang bán');
$editId = null;
$dataReady = false;

try {
    $adminModel = new AdminModel();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        kiemTraCsrf();
        if (($_POST['action'] ?? '') === 'logout') {
            $_SESSION = array();
            session_regenerate_id(true);
            chuyenTrang('index.php');
        }
        $adminModel->conn->begin_transaction();
        try {
            switch ($page) {
                case 'users':
                    xuLyAdminUser($adminModel);
                    break;
                case 'tables':
                    xuLyAdminTable($adminModel);
                    break;
                case 'categories':
                    xuLyAdminCategory($adminModel);
                    break;
                case 'products':
                    xuLyAdminProduct($adminModel);
                    break;
                default:
                    throw new DomainException('Không có thao tác lưu tại trang tổng quan.');
            }
            $adminModel->conn->commit();
        } catch (Throwable $error) {
            $adminModel->conn->rollback();
            throw $error;
        }
        $_SESSION['admin_message'] = 'Đã cập nhật thành công.';
        chuyenTrang('admin.php?page=' . $page);
    }
} catch (DomainException $error) {
    $errorMessage = $error->getMessage();
} catch (Throwable $error) {
    error_log($error->getMessage());
    $errorMessage = 'Chưa lưu được dữ liệu. Có thể dữ liệu bị trùng hoặc đã thay đổi, hãy tải lại trang.';
}

try {
    switch ($page) {
        case 'dashboard':
            $stats = duLieuAdminDashboard($adminModel);
            break;
        case 'users':
            $rows = $adminModel->users();
            break;
        case 'tables':
            $rows = $adminModel->adminTables();
            break;
        case 'categories':
            $rows = $adminModel->categories();
            break;
        case 'products':
            $rows = $adminModel->adminProducts();
            $categories = $adminModel->categories();
            break;
    }
    $editId = adminEditId();
    if ($editId) {
        $found = false;
        foreach ($rows as $row) {
            if ((int) $row['id'] === $editId) {
                $form = array_merge($form, $row);
                $found = true;
                break;
            }
        }
        if (!$found) {
            throw new DomainException('Không tìm thấy bản ghi cần sửa.');
        }
    }
    // Giữ nội dung vừa nhập khi báo lỗi, nhưng không đưa mật khẩu trở lại HTML.
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
        foreach (array('name', 'username', 'category_id', 'price', 'status') as $field) {
            if (isset($_POST[$field]) && is_string($_POST[$field])) {
                $form[$field] = $_POST[$field];
            }
        }
    }
    $dataReady = true;
} catch (DomainException $error) {
    $errorMessage = $error->getMessage();
} catch (Throwable $error) {
    error_log($error->getMessage());
    $errorMessage = 'Chưa tải được dữ liệu. Hãy kiểm tra MySQL và cấu trúc CSDL.';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($title); ?> | RESTAURANT Admin</title>
    <link rel="stylesheet" href="public/css/admin.css?v=<?php echo filemtime(__DIR__ . '/public/css/admin.css'); ?>">
    <script src="public/js/admin.js?v=<?php echo filemtime(__DIR__ . '/public/js/admin.js'); ?>" defer></script>
    <link rel="stylesheet" href="public/css/ui-refresh.css?v=<?php echo filemtime(__DIR__ . '/public/css/ui-refresh.css'); ?>">
</head>
<body class="admin-shell">
    <a class="skip-link" href="#main-content">Đến nội dung chính</a>
    <aside class="admin-sidebar">
        <a class="brand" href="admin.php">RESTAURANT <span class="brand-role">ADMIN</span></a>
        <p class="nav-label">KHÔNG GIAN QUẢN TRỊ</p>
        <nav aria-label="Menu quản trị">
            <?php $number = 1; ?>
            <?php foreach ($pages as $key => $info): ?>
                <a href="admin.php?page=<?php echo e($key); ?>" class="<?php echo $page === $key ? 'active' : ''; ?>" <?php if ($page === $key) echo 'aria-current="page"'; ?>>
                    <span class="nav-number"><?php echo '0' . $number++; ?></span>
                    <?php echo e($info[0]); ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-footer">
            <a class="staff-link" href="staff.php">Mở khu vực nhân viên ↗</a>
            <strong><?php echo e($user['name']); ?></strong>
            <span class="muted-light">Quản trị viên</span>
            <form method="post" action="admin.php">
                <?php csrfInput(); ?>
                <button class="logout" name="action" value="logout">Đăng xuất</button>
            </form>
        </div>
    </aside>
    <div class="admin-workspace">
        <header class="topbar">
            <span>Nhà hàng <span class="muted">/</span> <?php echo e($title); ?></span>
            <time datetime="<?php echo date('c'); ?>"><?php echo date('H:i'); ?> · <?php echo date('d/m/Y'); ?></time>
        </header>
        <main id="main-content" class="<?php echo $page === 'dashboard' ? 'dashboard-page' : ''; ?>">
            <div class="page-heading">
                <div><p class="eyebrow">RESTAURANT · ADMIN</p><h1><?php echo e($title); ?></h1></div>
                <?php if ($page !== 'dashboard'): ?>
                    <a class="button primary" href="admin.php?page=<?php echo e($page); ?>#editor">＋ Thêm mới</a>
                <?php endif; ?>
            </div>
            <?php if ($errorMessage !== ''): ?>
                <div class="notice error" role="alert"><?php echo e($errorMessage); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['admin_message'])): ?>
                <div class="notice success" role="status"><?php echo e($_SESSION['admin_message']); ?></div>
                <?php unset($_SESSION['admin_message']); ?>
            <?php endif; ?>
            <?php if ($dataReady): ?>
                <?php require __DIR__ . '/views/admin/' . $pages[$page][1]; ?>
            <?php else: ?>
                <a class="button" href="admin.php?page=<?php echo e($page); ?>">Tải lại danh sách</a>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
