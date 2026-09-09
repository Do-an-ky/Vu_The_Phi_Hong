<?php
$openOrders = $model->all('SELECT o.*, t.name AS table_name FROM service_sessions s
                          JOIN orders o ON o.id = s.order_id
                          JOIN tables t ON t.id = s.table_id ORDER BY t.id');
$id = (int) ($_GET['id'] ?? 0);
$order = $model->order($id);
$receipts = $model->all('SELECT p.id, p.total, p.created_at, r.table_name
                        FROM payments p JOIN payment_receipts r ON r.payment_id = p.id
                        ORDER BY p.id DESC LIMIT 20');
?>
<div class="page-heading"><div><p class="eyebrow">HOÀN TẤT PHỤC VỤ</p><h1>Thanh toán</h1><p>Chỉ xác nhận khi đã nhận đủ tiền từ khách.</p></div></div>
<div class="checkout-layout">
    <section class="panel">
        <h2>Bàn đang phục vụ</h2>
        <?php foreach ($openOrders as $openOrder): ?>
            <a class="payment-choice" href="?page=payments&id=<?php echo $openOrder['id']; ?>">
                <strong><?php echo e($openOrder['table_name']); ?></strong>
                <small><?php echo e($openOrder['status']); ?> · <?php echo tien($openOrder['total']); ?></small>
            </a>
        <?php endforeach; ?>
        <?php if (count($openOrders) === 0): ?><p class="empty">Không có bàn đang phục vụ.</p><?php endif; ?>
    </section>
    <section class="panel">
        <?php if (!$order): ?>
            <p class="empty">Chọn một bàn để kiểm tra order.</p>
        <?php else: ?>
            <h2><?php echo e($order['table_name']); ?> · Order #<?php echo $id; ?></h2>
            <?php $items = $model->orderItems($id); ?>
            <?php require __DIR__ . '/../orders/items.php'; ?>
            <div class="ticket-total"><span>Tổng thanh toán</span><strong><?php echo tien($model->total($id)); ?></strong></div>
            <?php if ($order['status'] === 'Chờ thanh toán'): ?>
                <form method="post" data-confirm="Bạn đã nhận đủ tiền và muốn hoàn tất thanh toán?">
                    <?php csrfInput(); ?>
                    <input type="hidden" name="order_id" value="<?php echo $id; ?>">
                    <fieldset class="payment-method">
                        <legend>Phương thức thanh toán</legend>
                        <label><input type="radio" name="method" value="Tiền mặt" checked> Tiền mặt</label>
                        <label><input type="radio" name="method" value="Chuyển khoản"> Chuyển khoản</label>
                        <label><input type="radio" name="method" value="Thẻ"> Thẻ</label>
                    </fieldset>
                    <label class="check-list"><input type="checkbox" name="received" value="yes" required> Đã kiểm tra và nhận đủ tiền</label>
                    <button class="button primary full" name="action" value="pay">Hoàn tất thanh toán & xem hóa đơn</button>
                </form>
            <?php elseif ($order['status'] === 'Đã thanh toán'): ?>
                <?php $receipt = $model->one('SELECT payment_id FROM payment_receipts WHERE order_id = ?', array($id)); ?>
                <p>Order đã thanh toán.</p>
                <?php if ($receipt): ?><a class="button" href="?page=invoice&id=<?php echo $receipt['payment_id']; ?>">Xem hóa đơn</a><?php endif; ?>
            <?php else: ?>
                <p>Hoàn tất phục vụ các phiếu và chuyển bàn sang chờ thanh toán trước.</p>
                <a class="button" href="?page=order-detail&id=<?php echo $id; ?>">Kiểm tra tiến độ phục vụ</a>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>
<section class="panel section-title">
    <h2>20 hóa đơn gần nhất</h2>
    <?php foreach ($receipts as $receipt): ?>
        <a class="receipt-link" href="?page=invoice&id=<?php echo $receipt['id']; ?>">
            #<?php echo $receipt['id']; ?> · <?php echo e($receipt['table_name']); ?> · <?php echo tien($receipt['total']); ?> · <?php echo e($receipt['created_at']); ?> → In lại
        </a>
    <?php endforeach; ?>
    <?php if (count($receipts) === 0): ?><p class="muted">Chưa có hóa đơn.</p><?php endif; ?>
</section>
