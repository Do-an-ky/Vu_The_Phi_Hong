<?php
$id = soNguyenDuong($_GET['id'] ?? null);
$order = $model->order($id);
if (!$order) {
    throw new DomainException('Không tìm thấy order.');
}
$tickets = $model->tickets($id);
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
    <h2>Tất cả món đã xác nhận</h2>
    <?php require __DIR__ . '/items.php'; ?>
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
<section class="panel section-title">
    <h2>Phiếu bếp – <?php echo e($order['table_name']); ?></h2>
    <?php if ($tickets): ?>
        <?php require __DIR__ . '/common-ticket-items.php'; ?>
        <div class="actions">
            <a class="button primary" href="?page=ticket&id=<?php echo (int) $tickets[0]['id']; ?>&scope=table">In lại toàn bộ món của bàn</a>
            <?php foreach ($tickets as $part): ?>
                <?php if ($part['status'] !== 'Chờ in') continue; ?>
                <a class="button" href="?page=ticket&id=<?php echo (int) $part['id']; ?>">
                    In món mới
                    · <?php echo e($part['created_at']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="empty">Bàn chưa có món đã xác nhận.</p>
    <?php endif; ?>
</section>

