<?php

require_once __DIR__ . '/../config/database.php';

class Restaurant
{
    public $conn;

    public function __construct()
    {
        $this->conn = ketNoiCSDL();
        // Sau khi chờ khóa bàn, đọc dữ liệu vừa được nhân viên khác lưu.
        $this->conn->query('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');
    }

    // Dùng dấu ? thay cho nối dữ liệu người dùng vào SQL.
    // Gom thao tác bind_param tại đây để các hàm nghiệp vụ dễ đọc.
    public function query($sql, $params = array())
    {
        $statement = $this->conn->prepare($sql);
        if (count($params) > 0) {
            $types = str_repeat('s', count($params));
            $statement->bind_param($types, ...$params);
        }
        $statement->execute();
        return $statement;
    }

    public function all($sql, $params = array())
    {
        return $this->query($sql, $params)->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function one($sql, $params = array())
    {
        return $this->query($sql, $params)->get_result()->fetch_assoc();
    }

    public function tables()
    {
        return $this->all('SELECT t.*, s.order_id, o.total, o.status AS order_status
                          FROM tables t
                          LEFT JOIN service_sessions s ON s.table_id = t.id
                          LEFT JOIN orders o ON o.id = s.order_id
                          ORDER BY t.id');
    }

    public function products()
    {
        return $this->all("SELECT p.*, c.name AS category
                           FROM products p LEFT JOIN categories c ON c.id = p.category_id
                           WHERE p.status = 'Đang bán' AND p.price >= 0
                           ORDER BY p.category_id, p.id");
    }

    public function order($id)
    {
        return $this->one('SELECT o.*, t.name AS table_name
                          FROM orders o JOIN tables t ON t.id = o.table_id
                          WHERE o.id = ?', array($id));
    }

    public function tickets($orderId)
    {
        return $this->all('SELECT * FROM kitchen_tickets WHERE order_id = ? ORDER BY id', array($orderId));
    }

    public function ticketItems($ticketId)
    {
        return $this->all('SELECT d.id, k.product_name AS name, d.quantity, d.price, d.note,
                                 k.item_ready_at, k.item_served_at
                          FROM kitchen_ticket_items k
                          JOIN order_details d ON d.id = k.detail_id
                          WHERE k.ticket_id = ? ORDER BY k.id', array($ticketId));
    }

    public function orderItems($orderId)
    {
        return $this->all('SELECT d.id, COALESCE(k.product_name, p.name) AS name,
                                 d.quantity, d.price, d.note
                          FROM order_details d
                          JOIN products p ON p.id = d.product_id
                          LEFT JOIN kitchen_ticket_items k ON k.detail_id = d.id
                          WHERE d.order_id = ? ORDER BY d.id', array($orderId));
    }

    public function total($orderId)
    {
        $row = $this->one('SELECT COALESCE(SUM(quantity * price), 0) AS total
                          FROM order_details WHERE order_id = ?', array($orderId));
        return $row['total'];
    }

    // Lấy hóa đơn theo ngày thanh toán. Ngày luôn được kiểm tra lại ở server.
    public function receiptsByDate($date)
    {
        if (!is_string($date) || !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D', $date)) {
            throw new DomainException('Ngày xem hóa đơn không hợp lệ.');
        }
        $day = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$day || $day->format('Y-m-d') !== $date || (int) $day->format('Y') < 1000) {
            throw new DomainException('Ngày xem hóa đơn không tồn tại. Hãy chọn lại ngày.');
        }
        $start = $day->format('Y-m-d') . ' 00:00:00';
        $end = $day->modify('+1 day')->format('Y-m-d') . ' 00:00:00';
        return $this->all(
            'SELECT p.id, p.total, p.payment_method, p.created_at, r.table_name
             FROM payments p
             JOIN payment_receipts r ON r.payment_id = p.id
             WHERE p.created_at >= ? AND p.created_at < ?
             ORDER BY p.created_at DESC, p.id DESC',
            array($start, $end)
        );
    }

    // Số việc đang chờ ở từng khu vực để hiển thị trên sidebar nhân viên.
    public function sidebarNotifications()
    {
        $row = $this->one(
            "SELECT
                (SELECT COUNT(*) FROM service_sessions) AS tables,
                (SELECT COUNT(*) FROM kitchen_tickets WHERE status = 'Chờ in') AS orders,
                (SELECT COUNT(*) FROM kitchen_tickets
                    WHERE status IN ('Chờ bếp', 'Đang nấu')) AS kitchen,
                (SELECT COUNT(*) FROM orders o
                    JOIN service_sessions s ON s.order_id = o.id
                    WHERE o.status IN ('Đang phục vụ', 'Chờ thanh toán')
                      AND EXISTS (SELECT 1 FROM kitchen_tickets k WHERE k.order_id = o.id)
                      AND NOT EXISTS (SELECT 1 FROM kitchen_tickets k
                          WHERE k.order_id = o.id
                            AND k.status NOT IN ('Chờ kiểm món', 'Đã kiểm đủ', 'Đã phục vụ'))
                      AND NOT EXISTS (SELECT 1 FROM kitchen_tickets k
                          JOIN kitchen_ticket_items i ON i.ticket_id = k.id
                          WHERE k.order_id = o.id
                            AND (i.item_ready_at IS NULL OR i.item_served_at IS NULL))) AS payments"
        );

        return array(
            'tables' => (int) $row['tables'],
            'orders' => (int) $row['orders'],
            'kitchen' => (int) $row['kitchen'],
            'payments' => (int) $row['payments']
        );
    }

    // Mọi thay đổi một bàn đều khóa cùng dòng bàn trước khi xử lý.
    // Nhờ vậy hai nhân viên không thể đồng thời mở hai order cho cùng bàn.
    public function lockTable($id)
    {
        $table = $this->one('SELECT * FROM tables WHERE id = ? FOR UPDATE', array($id));
        if (!$table) {
            throw new DomainException('Không tìm thấy bàn.');
        }
        return $table;
    }

    public function lockOrder($id)
    {
        $order = $this->order($id);
        if (!$order) {
            throw new DomainException('Không tìm thấy order.');
        }
        $this->lockTable($order['table_id']);
        $order = $this->one('SELECT * FROM orders WHERE id = ? FOR UPDATE', array($id));
        $session = $this->one('SELECT * FROM service_sessions WHERE order_id = ?', array($id));
        if (!$session || $order['status'] === 'Đã thanh toán') {
            throw new DomainException('Lượt phục vụ đã kết thúc.');
        }
        return $order;
    }

    public function openTable($tableId, $userId)
    {
        $table = $this->lockTable($tableId);
        $session = $this->one('SELECT * FROM service_sessions WHERE table_id = ?', array($tableId));
        if ($session) {
            return $session['order_id'];
        }
        if ($table['status'] !== 'Trống') {
            throw new DomainException('Bàn chưa trống. Hãy kiểm tra lượt phục vụ hiện có.');
        }
        $this->query("INSERT INTO orders(table_id, user_id, total, status)
                      VALUES (?, ?, 0, 'Đang phục vụ')", array($tableId, $userId));
        $orderId = $this->conn->insert_id;
        $this->query('INSERT INTO service_sessions(table_id, order_id) VALUES (?, ?)', array($tableId, $orderId));
        $this->query("UPDATE tables SET status = 'Có khách' WHERE id = ?", array($tableId));
        return $orderId;
    }

    public function addTicket($orderId, $draftItems, $requestKey)
    {
        $order = $this->lockOrder($orderId);
        if (!is_string($requestKey) || !preg_match('/^[a-f0-9]{32}$/', $requestKey)) {
            throw new DomainException('Phiếu không hợp lệ. Hãy mở lại trang gọi món.');
        }
        $old = $this->one('SELECT * FROM kitchen_tickets WHERE request_key = ?', array($requestKey));
        if ($old) {
            if ((int) $old['order_id'] !== (int) $orderId) {
                throw new DomainException('Mã phiếu không khớp order.');
            }
            return $old['id'];
        }
        if ($order['status'] !== 'Đang phục vụ') {
            throw new DomainException('Bàn đang chờ thanh toán. Chọn tiếp tục gọi món trước.');
        }
        if (!is_array($draftItems) || count($draftItems) > 200) {
            throw new DomainException('Danh sách món không hợp lệ.');
        }
        $items = array();
        foreach ($draftItems as $draftItem) {
            if (!is_array($draftItem) ||
                !array_key_exists('product_id', $draftItem) ||
                !array_key_exists('quantity', $draftItem) ||
                !array_key_exists('note', $draftItem)) {
                throw new DomainException('Dòng món không hợp lệ.');
            }
            $productId = soNguyenDuong($draftItem['product_id']);
            $quantity = filter_var($draftItem['quantity'], FILTER_VALIDATE_INT);
            if ($quantity === false || $quantity < 1 || $quantity > 99) {
                throw new DomainException('Số lượng mỗi dòng món phải từ 1 đến 99.');
            }
            $note = ghiChuMon($draftItem['note']);
            $product = $this->one("SELECT * FROM products WHERE id = ?
                                   AND status = 'Đang bán' AND price >= 0 FOR UPDATE", array($productId));
            if (!$product) {
                throw new DomainException('Có món đã ngừng bán. Hãy tải lại thực đơn.');
            }
            $product['quantity'] = $quantity;
            $product['note'] = $note;
            $items[] = $product;
        }
        if (count($items) === 0) {
            throw new DomainException('Hãy chọn ít nhất một món.');
        }
        $this->query("INSERT INTO kitchen_tickets(order_id, request_key, status)
                      VALUES (?, ?, 'Chờ in')", array($orderId, $requestKey));
        $ticketId = $this->conn->insert_id;
        foreach ($items as $item) {
            // Giá luôn lấy ở MySQL tại lúc xác nhận, không lấy từ trình duyệt.
            $this->query('INSERT INTO order_details(order_id, product_id, quantity, price, note)
                          VALUES (?, ?, ?, ?, ?)', array($orderId, $item['id'], $item['quantity'], $item['price'], $item['note']));
            $detailId = $this->conn->insert_id;
            $this->query('INSERT INTO kitchen_ticket_items(ticket_id, detail_id, product_name)
                          VALUES (?, ?, ?)', array($ticketId, $detailId, $item['name']));
        }
        $this->query('UPDATE orders SET total = ? WHERE id = ?', array($this->total($orderId), $orderId));
        return $ticketId;
    }

    public function moveTicket($ticketId, $action, $checked)
    {
        if ($action === 'check' || $action === 'serve') {
            throw new DomainException('Hãy xác nhận đã mang từng món ra bàn, không xác nhận gộp cả phiếu.');
        }
        $ticket = $this->one('SELECT * FROM kitchen_tickets WHERE id = ?', array($ticketId));
        if (!$ticket) {
            throw new DomainException('Không tìm thấy phiếu.');
        }
        $this->lockOrder($ticket['order_id']);
        $ticket = $this->one('SELECT * FROM kitchen_tickets WHERE id = ? FOR UPDATE', array($ticketId));

        // Mỗi bước chỉ được đi tiếp từ đúng trạng thái liền trước.
        switch ($action) {
            case 'send':
                $from = 'Chờ in'; $to = 'Chờ bếp'; $column = 'printed_at';
                break;
            case 'accept':
                $from = 'Chờ bếp'; $to = 'Đang nấu'; $column = 'accepted_at';
                break;
            case 'ready':
                foreach ($this->ticketItems($ticketId) as $item) {
                    if (!$item['item_ready_at'] || !$item['item_served_at']) {
                        throw new DomainException('Còn món chưa nấu xong. Hãy tích từng món trước khi giao phiếu.');
                    }
                }
                $from = 'Đang nấu'; $to = 'Chờ kiểm món'; $column = 'ready_at';
                break;
            default:
                throw new DomainException('Thao tác không hợp lệ.');
        }
        if ($ticket['status'] === $to) {
            return $ticket['order_id'];
        }
        if ($ticket['status'] !== $from) {
            throw new DomainException('Trạng thái phiếu đã thay đổi. Hãy tải lại trang.');
        }
        // $column chỉ lấy từ switch ở trên, không lấy từ dữ liệu người dùng.
        $this->query("UPDATE kitchen_tickets SET status = ?, $column = NOW() WHERE id = ?", array($to, $ticketId));
        return $ticket['order_id'];
    }

    // Controller đã mở transaction. Khóa cùng bàn/phiếu để hai người không ghi chồng.
    public function markTicketItem($ticketId, $detailId, $action)
    {
        if ($action !== 'item-ready') {
            throw new DomainException('Thao tác món không hợp lệ.');
        }
        $ticket = $this->one('SELECT * FROM kitchen_tickets WHERE id = ?', array($ticketId));
        if (!$ticket) {
            throw new DomainException('Không tìm thấy phiếu.');
        }
        $this->lockOrder($ticket['order_id']);
        $ticket = $this->one('SELECT * FROM kitchen_tickets WHERE id = ? FOR UPDATE', array($ticketId));
        $item = $this->one('SELECT * FROM kitchen_ticket_items WHERE ticket_id = ? AND detail_id = ? FOR UPDATE', array($ticketId, $detailId));
        if (!$item) {
            throw new DomainException('Món không thuộc phiếu đang chọn.');
        }
        if ($item['item_ready_at'] && $item['item_served_at']) {
            return $ticket['order_id'];
        }
        if (!in_array($ticket['status'], array('Đang nấu', 'Chờ kiểm món', 'Đã kiểm đủ'), true)) {
            throw new DomainException('Bếp phải nhận phiếu trước khi xác nhận món.');
        }
        // Một lần lưu xác nhận cả nấu xong và mang ra bàn.
        $this->query('UPDATE kitchen_ticket_items
                      SET item_ready_at = COALESCE(item_ready_at, NOW()),
                          item_served_at = COALESCE(item_served_at, NOW())
                      WHERE id = ?', array($item['id']));
        return $ticket['order_id'];
    }

    // Tự hoàn tất phiếu khi tất cả dòng món đã được xác nhận tại bếp.
    public function finishServedTicket($ticketId)
    {
        $ticket = $this->one('SELECT status FROM kitchen_tickets WHERE id = ?', array($ticketId));
        if (!in_array($ticket['status'], array('Đang nấu', 'Chờ kiểm món', 'Đã kiểm đủ'), true)) {
            return;
        }
        $items = $this->ticketItems($ticketId);
        if (!$items) {
            return;
        }
        foreach ($items as $item) {
            if (!$item['item_ready_at'] || !$item['item_served_at']) {
                return;
            }
        }
        $this->query("UPDATE kitchen_tickets SET status = 'Đã phục vụ',
                      ready_at = COALESCE(ready_at, NOW()),
                      checked_at = COALESCE(checked_at, NOW()), served_at = NOW()
                      WHERE id = ?", array($ticketId));
    }

    public function ensureServed($orderId)
    {
        $tickets = $this->tickets($orderId);
        if (count($tickets) === 0) {
            throw new DomainException('Chưa có món để thanh toán.');
        }
        foreach ($tickets as $ticket) {
            if (!in_array($ticket['status'], array('Chờ kiểm món', 'Đã kiểm đủ', 'Đã phục vụ'), true)) {
                throw new DomainException('Phải kiểm đủ món và phục vụ tất cả phiếu trước khi thanh toán.');
            }
            foreach ($this->ticketItems($ticket['id']) as $item) {
                if (!$item['item_ready_at'] || !$item['item_served_at']) {
                    throw new DomainException('Còn món chưa xác nhận đã mang ra bàn.');
                }
            }
        }
    }

    public function waitPayment($orderId)
    {
        $order = $this->lockOrder($orderId);
        $this->ensureServed($orderId);
        $this->query("UPDATE orders SET status = 'Chờ thanh toán' WHERE id = ?", array($orderId));
        $this->query("UPDATE tables SET status = 'Chờ thanh toán' WHERE id = ?", array($order['table_id']));
    }

    public function resume($orderId)
    {
        $order = $this->lockOrder($orderId);
        $this->query("UPDATE orders SET status = 'Đang phục vụ' WHERE id = ?", array($orderId));
        $this->query("UPDATE tables SET status = 'Có khách' WHERE id = ?", array($order['table_id']));
    }

    public function closeEmpty($orderId)
    {
        $order = $this->lockOrder($orderId);
        $row = $this->one('SELECT COUNT(*) AS count FROM order_details WHERE order_id = ?', array($orderId));
        if ($row['count'] > 0) {
            throw new DomainException('Chỉ được trả bàn chưa xác nhận món nào.');
        }
        $this->query("UPDATE orders SET status = 'Đã hủy' WHERE id = ?", array($orderId));
        $this->query('DELETE FROM service_sessions WHERE order_id = ?', array($orderId));
        $this->query("UPDATE tables SET status = 'Trống' WHERE id = ?", array($order['table_id']));
    }

    public function pay($orderId, $method, $received, $staffName)
    {
        $order = $this->order($orderId);
        if (!$order) {
            throw new DomainException('Không tìm thấy order.');
        }
        $this->lockTable($order['table_id']);
        // Nhấn thanh toán hai lần chỉ trả về hóa đơn cũ, không thu tiền hai lần.
        $old = $this->one('SELECT payment_id FROM payment_receipts WHERE order_id = ?', array($orderId));
        if ($old) {
            return $old['payment_id'];
        }
        $order = $this->lockOrder($orderId);
        if (!in_array($order['status'], array('Đang phục vụ', 'Chờ thanh toán'), true)) {
            throw new DomainException('Order không còn có thể thanh toán.');
        }
        $this->ensureServed($orderId);
        if (!in_array($method, array('Tiền mặt', 'Chuyển khoản', 'Thẻ'), true)) {
            throw new DomainException('Phương thức thanh toán không hợp lệ.');
        }
        $total = $this->total($orderId);
        if ($received !== 'yes') {
            throw new DomainException('Chỉ xác nhận thanh toán khi đã nhận đủ tiền.');
        }
        $this->query('INSERT INTO payments(order_id, total, payment_method) VALUES (?, ?, ?)', array($orderId, $total, $method));
        $paymentId = $this->conn->insert_id;
        $table = $this->one('SELECT name FROM tables WHERE id = ?', array($order['table_id']));
        $this->query('INSERT INTO payment_receipts(payment_id, order_id, table_name, staff_name, items_json)
                      VALUES (?, ?, ?, ?, ?)', array($paymentId, $orderId, $table['name'], $staffName, json_encode($this->orderItems($orderId), JSON_UNESCAPED_UNICODE)));
        $this->query("UPDATE orders SET total = ?, status = 'Đã thanh toán' WHERE id = ?", array($total, $orderId));
        // Thanh toán xong là kết thúc lượt phục vụ; in lại không giữ bàn.
        $this->finishReceipt($paymentId);
        return $paymentId;
    }

    public function finishReceipt($paymentId)
    {
        $receipt = $this->one('SELECT r.order_id, o.table_id FROM payment_receipts r
                              JOIN orders o ON o.id = r.order_id
                              WHERE r.payment_id = ? AND o.status = ?', array($paymentId, 'Đã thanh toán'));
        if (!$receipt) {
            throw new DomainException('Không có hóa đơn đã thanh toán để kết thúc.');
        }
        $this->lockTable($receipt['table_id']);
        $session = $this->one('SELECT * FROM service_sessions WHERE order_id = ?', array($receipt['order_id']));
        if (!$session) {
            // Xác nhận lại hóa đơn cũ không ảnh hưởng khách mới đang ngồi bàn.
            return;
        }
        $this->query('DELETE FROM service_sessions WHERE order_id = ?', array($receipt['order_id']));
        $this->query("UPDATE tables SET status = 'Trống' WHERE id = ?", array($receipt['table_id']));
    }
}
