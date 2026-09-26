<?php

// Controller nhận form POST và gọi đúng hàm xử lý trong model.
// Toàn bộ thay đổi của một thao tác cùng thành công hoặc cùng hủy.
function xuLyNhanVien($model, $user)
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        throw new DomainException('Thao tác này phải gửi bằng POST.');
    }
    if (!$user || !in_array($user['role'] ?? '', array('nhanvien', 'staff', 'admin'), true)) {
        throw new DomainException('Bạn không có quyền thao tác nhân viên.');
    }
    kiemTraCsrf();
    $action = $_POST['action'] ?? '';
    if (!is_string($action)) {
        throw new DomainException('Thao tác không hợp lệ.');
    }

    if ($action === 'review' || $action === 'edit') {
        return kiemTraFormGoiMon($model, $action);
    }

    $redirect = '?page=tables';
    $model->conn->begin_transaction();

    try {
        switch ($action) {
            case 'open':
                $id = $model->openTable(soNguyenDuong($_POST['table_id'] ?? null), $user['id']);
                $redirect = '?page=orders&id=' . $id;
                break;
            case 'confirm':
                $draft = layPhieuDaKiemTra();
                // Chỉ lưu bản đã kiểm tra trên server, không dùng giá/tổng từ form.
                $id = $model->addTicket($draft['order_id'], $draft['quantity'], $draft['request_key'], $draft['note']);
                $redirect = '?page=ticket&id=' . $id;
                break;
            case 'send':
            case 'accept':
            case 'ready':
            case 'check':
            case 'serve':
                if ($action === 'send' && ($_POST['printed'] ?? '') !== 'yes') {
                    throw new DomainException('Hãy xác nhận đã in phiếu thành công.');
                }
                $id = $model->moveTicket(soNguyenDuong($_POST['ticket_id'] ?? null), $action, $_POST['checked'] ?? array());
                $redirect = '?page=order-detail&id=' . $id;
                if ($action === 'accept' || $action === 'ready') {
                    $redirect = '?page=kitchen';
                }
                break;
            case 'wait':
                $id = soNguyenDuong($_POST['order_id'] ?? null);
                $model->waitPayment($id);
                $redirect = '?page=payments&id=' . $id;
                break;
            case 'resume':
                $id = soNguyenDuong($_POST['order_id'] ?? null);
                $model->resume($id);
                $redirect = '?page=orders&id=' . $id;
                break;
            case 'close-empty':
                $model->closeEmpty(soNguyenDuong($_POST['order_id'] ?? null));
                break;
            case 'pay':
                $id = $model->pay(soNguyenDuong($_POST['order_id'] ?? null), $_POST['method'] ?? '', $_POST['received'] ?? '', $user['name']);
                $redirect = '?page=invoice&id=' . $id;
                break;
            case 'finish-receipt':
                if (($_POST['printed'] ?? '') !== 'yes') {
                    throw new DomainException('Hãy xác nhận đã in hóa đơn thành công.');
                }
                $model->finishReceipt(soNguyenDuong($_POST['payment_id'] ?? null));
                break;
            default:
                throw new DomainException('Thao tác không hợp lệ.');
        }
        $model->conn->commit();
    } catch (Throwable $error) {
        $model->conn->rollback();
        throw $error;
    }

    $_SESSION['message'] = 'Đã cập nhật thành công.';
    chuyenTrang($redirect);
}

// Kiểm tra toàn bộ dữ liệu gửi lên, kể cả món không có trong giao diện.
function docMonTuForm($quantities, $notes)
{
    if (!is_array($quantities) || count($quantities) > 200 ||
        !is_array($notes) || count($notes) > 200) {
        throw new DomainException('Danh sách món không hợp lệ.');
    }

    $result = array('quantity' => array(), 'note' => array());
    foreach ($quantities as $id => $value) {
        $id = soNguyenDuong($id);
        if (!is_string($value) && !is_int($value)) {
            throw new DomainException('Số lượng không hợp lệ.');
        }
        $quantity = filter_var($value, FILTER_VALIDATE_INT);
        if ($quantity === false || $quantity < 0 || $quantity > 99) {
            throw new DomainException('Số lượng mỗi món phải từ 0 đến 99.');
        }
        $note = ghiChuMon($notes[$id] ?? '');
        if ($quantity > 0) {
            $result['quantity'][$id] = $quantity;
            $result['note'][$id] = $note;
        }
    }
    foreach ($notes as $id => $note) {
        soNguyenDuong($id);
        ghiChuMon($note);
        if (!array_key_exists($id, $quantities)) {
            throw new DomainException('Ghi chú không khớp danh sách món.');
        }
    }
    ksort($result['quantity']);
    ksort($result['note']);
    return $result;
}

function kiemTraFormGoiMon($model, $action)
{
    if (($_GET['page'] ?? '') !== 'orders') {
        throw new DomainException('Hãy mở trang gọi món để thao tác.');
    }
    $orderId = soNguyenDuong($_POST['order_id'] ?? null);
    if ($orderId !== soNguyenDuong($_GET['id'] ?? null)) {
        throw new DomainException('Order gửi lên không khớp bàn đang mở.');
    }
    $order = $model->order($orderId);
    if (!$order || $order['status'] !== 'Đang phục vụ') {
        throw new DomainException('Order không còn ở trạng thái đang phục vụ.');
    }

    $draft = docMonTuForm($_POST['quantity'] ?? array(), $_POST['note'] ?? array());
    $products = array_column($model->products(), null, 'id');
    foreach ($draft['quantity'] as $id => $quantity) {
        if (!isset($products[$id])) {
            throw new DomainException('Món không tồn tại hoặc đã ngừng bán. Hãy tải lại thực đơn.');
        }
    }
    if ($action === 'review' && count($draft['quantity']) === 0) {
        throw new DomainException('Hãy chọn ít nhất một món.');
    }
    if ($action === 'edit') {
        $key = $_POST['request_key'] ?? '';
        if (is_string($key)) {
            unset($_SESSION['order_drafts'][$key]);
        }
        return null;
    }

    $draft['order_id'] = $orderId;
    $draft['request_key'] = bin2hex(random_bytes(16));
    $draft['created_at'] = time();

    // Giữ nhiều phiếu để các tab không ghi đè nhau; tự bỏ phiếu quá 2 giờ.
    $drafts = $_SESSION['order_drafts'] ?? array();
    foreach ($drafts as $key => $saved) {
        if ($saved['created_at'] < time() - 7200) {
            unset($drafts[$key]);
        }
    }
    if (count($drafts) >= 30) {
        array_shift($drafts);
    }
    $drafts[$draft['request_key']] = $draft;
    $_SESSION['order_drafts'] = $drafts;
    return $draft;
}

function layPhieuDaKiemTra()
{
    $key = $_POST['request_key'] ?? '';
    if (!is_string($key) || !preg_match('/^[a-f0-9]{32}$/', $key)) {
        throw new DomainException('Mã phiếu không hợp lệ.');
    }
    $draft = $_SESSION['order_drafts'][$key] ?? null;
    if (!$draft || $draft['created_at'] < time() - 7200) {
        throw new DomainException('Hãy kiểm tra món lại trước khi xác nhận phiếu.');
    }
    $orderId = soNguyenDuong($_POST['order_id'] ?? null);
    $submitted = docMonTuForm($_POST['quantity'] ?? array(), $_POST['note'] ?? array());
    if ($orderId !== $draft['order_id'] ||
        $submitted['quantity'] !== $draft['quantity'] ||
        $submitted['note'] !== $draft['note']) {
        throw new DomainException('Phiếu đã bị thay đổi. Hãy quay lại kiểm tra món.');
    }
    return $draft;
}
