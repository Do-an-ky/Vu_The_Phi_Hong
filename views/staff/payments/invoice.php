<?php
$id = soNguyenDuong($_GET['id'] ?? null);
$receipt = $model->one('SELECT p.*, r.table_name, r.staff_name, r.items_json
                       FROM payments p JOIN payment_receipts r ON r.payment_id = p.id
                       WHERE p.id = ?', array($id));
if (!$receipt) {
    throw new DomainException('Không tìm thấy hóa đơn.');
}
$items = json_decode($receipt['items_json'], true);
$session = $model->one('SELECT * FROM service_sessions WHERE order_id = ?', array($receipt['order_id']));
?>
<div class="page-heading no-print">
    <div><h1>Hóa đơn thanh toán</h1><p>Đã thanh toán. Bạn có thể in hoặc in lại hóa đơn tại đây.</p></div>
    <button class="button primary" type="button" data-print>In hóa đơn</button>
</div>
<section class="invoice">
    <div class="invoice-heading">
        <h2>RESTAURANT</h2>
        <h2>HÓA ĐƠN THANH TOÁN</h2>
        <p>#<?php echo $id; ?> · Order #<?php echo $receipt['order_id']; ?></p>
        <p><?php echo e($receipt['created_at']); ?></p>
    </div>
    <p><?php echo e($receipt['table_name']); ?> · Nhân viên: <?php echo e($receipt['staff_name']); ?></p>
    <?php require __DIR__ . '/../orders/items.php'; ?>
    <div class="ticket-total"><span>Tổng thanh toán</span><strong><?php echo tien($receipt['total']); ?></strong></div>
    <p>Phương thức: <?php echo e($receipt['payment_method']); ?></p>
    <p>Đã nhận đủ tiền</p>
    <p class="hint">Cảm ơn quý khách. Hẹn gặp lại!</p>
</section>
<?php if ($session): ?>
    <section class="panel section-title no-print">
        <p>Lượt phục vụ cũ đã thanh toán, cần trả bàn về trống.</p>
        <form method="post">
            <?php csrfInput(); ?>
            <input type="hidden" name="payment_id" value="<?php echo $id; ?>">
            <button class="button primary" name="action" value="finish-receipt">Kết thúc · Trả bàn về trống</button>
        </form>
    </section>
<?php else: ?>
    <p class="notice no-print">Lượt phục vụ này đã kết thúc. In lại không thay đổi trạng thái bàn.</p>
<?php endif; ?>
<a class="button no-print" href="?page=payments">← Quay lại trang thanh toán</a>
