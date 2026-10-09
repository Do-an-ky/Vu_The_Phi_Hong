<?php
$openOrders = $model->all('SELECT o.*, t.name AS table_name FROM service_sessions s
                          JOIN orders o ON o.id = s.order_id
                          JOIN tables t ON t.id = s.table_id ORDER BY t.id');
$id = (int) ($_GET['id'] ?? 0);
$order = $model->order($id);
$receiptDate = $_GET['receipt_date'] ?? date('Y-m-d');
$receipts = $model->receiptsByDate($receiptDate);
$receiptDisplayDate = date('d/m/Y', strtotime($receiptDate));
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
            <?php if (in_array($order['status'], array('Đang phục vụ', 'Chờ thanh toán'), true)): ?>
                <?php
                $paymentBlocked = '';
                try {
                    $model->ensureServed($id);
                } catch (DomainException $error) {
                    $paymentBlocked = $error->getMessage();
                }
                ?>
                <?php if ($paymentBlocked !== ''): ?>
                    <p class="notice error"><?php echo e($paymentBlocked); ?></p>
                <?php endif; ?>
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
                    <button class="button primary full" name="action" value="pay" <?php if ($paymentBlocked !== '') echo 'disabled'; ?>>Thanh toán & xem hóa đơn</button>
                </form>
            <?php elseif ($order['status'] === 'Đã thanh toán'): ?>
                <?php $receipt = $model->one('SELECT payment_id FROM payment_receipts WHERE order_id = ?', array($id)); ?>
                <p>Order đã thanh toán.</p>
                <?php if ($receipt): ?><a class="button" href="?page=invoice&id=<?php echo $receipt['payment_id']; ?>">Xem hóa đơn</a><?php endif; ?>
            <?php else: ?>
                <p>Order này không còn có thể thanh toán.</p>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>
<section class="panel section-title">
    <div class="receipt-heading">
        <div>
            <p class="eyebrow">LỊCH SỬ THANH TOÁN</p>
            <h2>Hóa đơn ngày <?php echo e($receiptDisplayDate); ?></h2>
        </div>
        <span class="receipt-count"><?php echo count($receipts); ?> hóa đơn</span>
    </div>
    <form method="get" action="staff.php" class="receipt-filter">
        <input type="hidden" name="page" value="payments">
        <?php if ($id > 0): ?>
            <input type="hidden" name="id" value="<?php echo $id; ?>">
        <?php endif; ?>
        <label for="receipt-date">Chọn ngày thanh toán
            <input id="receipt-date" type="date" name="receipt_date" min="1000-01-01" max="9999-12-31" value="<?php echo e($receiptDate); ?>" required>
        </label>
        <button class="button primary" type="submit">Xem hóa đơn</button>
        <a class="button" href="?page=payments<?php if ($id > 0) echo '&id=' . $id; ?>">Hôm nay</a>
    </form>
    <div class="receipt-list">
    <?php foreach ($receipts as $receipt): ?>
        <div class="receipt-link">
            <span class="receipt-main">
                <strong>Hóa đơn #<?php echo $receipt['id']; ?> · <?php echo e($receipt['table_name']); ?></strong>
                <small><?php echo e($receipt['payment_method']); ?> · <?php echo e(date('H:i', strtotime($receipt['created_at']))); ?></small>
            </span>
            <span class="receipt-actions">
                <strong><?php echo tien($receipt['total']); ?></strong>
                <a class="receipt-view-button" href="?page=invoice&id=<?php echo $receipt['id']; ?>">Xem hóa đơn →</a>
            </span>
        </div>
    <?php endforeach; ?>
    <?php if (count($receipts) === 0): ?><p class="empty">Không có hóa đơn trong ngày đã chọn.</p><?php endif; ?>
    </div>
</section>
