<?php
$id = soNguyenDuong($_GET['id'] ?? null);
$order = $model->order($id);
if (!$order) {
    throw new DomainException('Không tìm thấy order.');
}
// Theo dõi chỉ tính phiếu đã được nhân viên gửi xuống bếp.
$tickets = array_values(array_filter($model->tickets($id), function ($ticket) {
    return $ticket['status'] !== 'Chờ in';
}));
$items = $model->orderItems($id);
?>
<div class="page-heading">
    <div>
        <p class="eyebrow">ORDER #<?php echo $id; ?></p>
        <h1><?php echo e($order['table_name']); ?> · Theo dõi phục vụ</h1>
        <p><?php echo e($order['status']); ?> · Mở lúc <?php echo e($order['created_at']); ?></p>
    </div>
    <a class="button" href="?page=order-detail&id=<?php echo $id; ?>">Làm mới</a>
</div>
<section class="panel">
    <h2>Món đã gửi bếp</h2>
    <?php if ($items): ?>
        <?php require __DIR__ . '/items.php'; ?>
    <?php else: ?>
        <p class="empty">Bàn chưa gọi món.</p>
    <?php endif; ?>
    <div class="ticket-total"><span>Tổng order</span><strong><?php echo tien($model->total($id)); ?></strong></div>
    <div class="actions">
        <?php if ($order['status'] === 'Đang phục vụ'): ?>
            <a class="button primary" href="?page=orders&id=<?php echo $id; ?>">＋ Gọi món / Gọi bổ sung</a>
            <?php if (count($items) === 0): ?>
                <form method="post" data-confirm="Trả bàn chưa gọi món về trạng thái trống?">
                    <?php csrfInput(); ?>
                    <input type="hidden" name="order_id" value="<?php echo $id; ?>">
                    <button class="button" name="action" value="close-empty">Khách chưa gọi món · Trả bàn</button>
                </form>
            <?php endif; ?>
        <?php elseif ($order['status'] === 'Chờ thanh toán'): ?>
            <a class="button primary" href="?page=payments&id=<?php echo $id; ?>">Thanh toán</a>
            <form method="post">
                <?php csrfInput(); ?>
                <input type="hidden" name="order_id" value="<?php echo $id; ?>">
                <button class="button" name="action" value="resume">Khách gọi thêm · Tiếp tục gọi món</button>
            </form>
        <?php elseif ($order['status'] === 'Đã thanh toán'): ?>
            <?php $receipt = $model->one('SELECT payment_id FROM payment_receipts WHERE order_id = ?', array($id)); ?>
            <?php if ($receipt): ?>
                <a class="button primary" href="?page=invoice&id=<?php echo $receipt['payment_id']; ?>">In hóa đơn / Kết thúc lượt phục vụ</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
<?php if ($tickets): ?>
<section class="panel section-title">
    <h2>Phiếu bếp – <?php echo e($order['table_name']); ?></h2>

    <?php if ($tickets): ?>
        <?php require __DIR__ . '/common-ticket-items.php'; ?>
        <div class="actions">
            <a class="button primary" href="?page=ticket&id=<?php echo (int) $tickets[0]['id']; ?>&scope=table">In phiếu bếp</a>

        </div>
    <?php else: ?>
        <p class="empty">Bàn chưa có món đã xác nhận.</p>
    <?php endif; ?>
</section>
<?php endif; ?>
