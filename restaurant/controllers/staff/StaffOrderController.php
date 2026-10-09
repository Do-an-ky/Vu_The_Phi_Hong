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
            case 'accept-table':
            case 'ready-table':
                $orderId = soNguyenDuong($_POST['order_id'] ?? null);
                $model->lockOrder($orderId);
                $status = $action === 'accept-table' ? 'Chờ bếp' : 'Đang nấu';
                $tickets = $model->all("SELECT * FROM kitchen_tickets WHERE order_id = ? AND status = ? ORDER BY id", array($orderId, $status));
                if (!$tickets) {
                    throw new DomainException('Bàn chưa có món gửi tới bếp.');
                }
                $submitted = $_POST['ticket_ids'] ?? array();
                $expected = array_map('strval', array_column($tickets, 'id'));
                if (!is_array($submitted) || array_values($submitted) !== $expected) {
                    throw new DomainException('Bàn có món mới hoặc phiếu đã thay đổi. Hãy tải lại bếp để kiểm tra.');
                }
                $selected = $_POST['detail_ids'] ?? array();
                if (!is_array($selected)) {
                    throw new DomainException('Danh sách món không hợp lệ.');
                }
                $selected = array_map('soNguyenDuong', $selected);
                $owners = array();
                foreach ($tickets as $ticket) {
                    foreach ($model->ticketItems($ticket['id']) as $item) {
                        $owners[$item['id']] = $ticket['id'];
                    }
                }
                foreach ($selected as $detailId) {
                    if (!isset($owners[$detailId])) {
                        throw new DomainException('Món không thuộc bàn này.');
                    }
                }
                foreach ($tickets as $ticket) {
                    if ($action === 'accept-table' && $ticket['status'] === 'Chờ bếp') {
                        $model->moveTicket($ticket['id'], 'accept', array());
                    }
                    if ($action === 'ready-table') {
                        if ($ticket['status'] === 'Chờ bếp') {
                            throw new DomainException('Hãy nhận các món mới trước khi hoàn thành.');
                        }
                        foreach ($model->ticketItems($ticket['id']) as $item) {
                            if (in_array((int) $item['id'], $selected, true)) {
                                $model->markTicketItem($ticket['id'], $item['id'], 'item-ready');
                            } elseif (!$item['item_ready_at'] || !$item['item_served_at']) {
                                throw new DomainException('Còn món chưa xác nhận hoàn thành.');
                            }
                        }
                        if ($ticket['status'] === 'Đang nấu') {
                            $model->moveTicket($ticket['id'], 'ready', array());
                        }
                    }
                }
                $redirect = '?page=kitchen';
                break;
            case 'item-ready':
                if (($_POST['done'] ?? '') !== 'yes') {
                    throw new DomainException('Hãy tích xác nhận món trước khi lưu.');
                }
                $id = $model->markTicketItem(
                    soNguyenDuong($_POST['ticket_id'] ?? null),
                    soNguyenDuong($_POST['detail_id'] ?? null),
                    $action
                );
                $redirect = '?page=kitchen';
                break;
            case 'open':
                $id = $model->openTable(soNguyenDuong($_POST['table_id'] ?? null), $user['id']);
                $redirect = '?page=orders&id=' . $id;
                break;
            case 'confirm':
                $draft = layPhieuDaKiemTra();
                // Chỉ lưu bản đã kiểm tra trên server, không dùng giá/tổng từ form.
                $id = $model->addTicket($draft['order_id'], $draft['items'], $draft['request_key']);
                $redirect = '?page=ticket&id=' . $id;
                break;
            case 'send':
            case 'accept':
            case 'ready':
            case 'check':
            case 'serve':
                if ($action === 'ready') {
                    $detailIds = $_POST['detail_ids'] ?? array();
                    if (!is_array($detailIds) || count($detailIds) > 200) {
                        throw new DomainException('Danh sách món xác nhận không hợp lệ.');
                    }
                    foreach ($detailIds as $detailId) {
                        $model->markTicketItem(
                            soNguyenDuong($_POST['ticket_id'] ?? null),
                            soNguyenDuong($detailId),
                            'item-ready'
                        );
                    }
                }
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
                $model->finishReceipt(soNguyenDuong($_POST['payment_id'] ?? null));
                $redirect = '?page=payments';
                break;
            default:
                throw new DomainException('Thao tác không hợp lệ.');
        }
        $model->conn->commit();
    } catch (Throwable $error) {
        $model->conn->rollback();
        throw $error;
    }

    if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'KitchenFetch') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('success' => true));
        exit;
    }
    $_SESSION['message'] = 'Đã cập nhật thành công.';
    chuyenTrang($redirect);
}

// Mỗi phần tử là một dòng món. Một sản phẩm có thể xuất hiện nhiều lần.
function docMonTuForm($productIds, $quantities, $notes)
{
    if (!is_array($productIds) || !is_array($quantities) || !is_array($notes) ||
        count($productIds) > 200 || count($quantities) > 200 || count($notes) > 200 ||
        array_keys($productIds) !== array_keys($quantities) ||
        array_keys($productIds) !== array_keys($notes)) {
        throw new DomainException('Danh sách món không hợp lệ.');
    }

    $items = array();
    foreach ($productIds as $index => $productId) {
        $productId = soNguyenDuong($productId);
        $value = $quantities[$index];
        if (!is_string($value) && !is_int($value)) {
            throw new DomainException('Số lượng không hợp lệ.');
        }
        $quantity = filter_var($value, FILTER_VALIDATE_INT);
        if ($quantity === false || $quantity < 1 || $quantity > 99) {
            throw new DomainException('Số lượng mỗi dòng món phải từ 1 đến 99.');
        }
        $items[] = array(
            'product_id' => $productId,
            'quantity' => $quantity,
            'note' => ghiChuMon($notes[$index])
        );
    }
    return $items;
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

    $draft = array(
        'items' => docMonTuForm(
            $_POST['product_id'] ?? array(),
            $_POST['quantity'] ?? array(),
            $_POST['note'] ?? array()
        )
    );
    $products = array_column($model->products(), null, 'id');
    foreach ($draft['items'] as $item) {
        if (!isset($products[$item['product_id']])) {
            throw new DomainException('Món không tồn tại hoặc đã ngừng bán. Hãy tải lại thực đơn.');
        }
    }
    if ($action === 'review' && count($draft['items']) === 0) {
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
    $submitted = docMonTuForm(
        $_POST['product_id'] ?? array(),
        $_POST['quantity'] ?? array(),
        $_POST['note'] ?? array()
    );
    if ($orderId !== $draft['order_id'] ||
        $submitted !== $draft['items']) {
        throw new DomainException('Phiếu đã bị thay đổi. Hãy quay lại kiểm tra món.');
    }
    return $draft;
}

