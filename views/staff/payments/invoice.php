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
    <div><h1>Thanh toán thành công</h1><p>In hóa đơn rồi xác nhận kết thúc lượt phục vụ.</p></div>
    <button class="button primary" type="button" data-print>In hóa đơn</button>
</div>
<section class="invoice">
    <div class="invoice-heading">
        <h2>HỒNG RESTAURANT</h2>
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
        <p>Bàn đang chờ in hóa đơn. Nếu máy in lỗi, bạn có thể quay lại đây để in lại.</p>
        <form method="post">
            <?php csrfInput(); ?>
            <input type="hidden" name="payment_id" value="<?php echo $id; ?>">
            <label class="check-list"><input type="checkbox" name="printed" value="yes" required> Đã in hóa đơn thành công</label>
            <button class="button primary" name="action" value="finish-receipt">Kết thúc · Trả bàn về trống</button>
        </form>
    </section>
<?php else: ?>
    <p class="notice no-print">Lượt phục vụ này đã kết thúc. In lại không thay đổi trạng thái bàn.</p>
<?php endif; ?>
<a class="text-link no-print" href="?page=tables">← Về danh sách bàn</a>
