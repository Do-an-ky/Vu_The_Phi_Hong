<?php
$id = soNguyenDuong($_GET['id'] ?? null);
$order = $model->order($id);
if (!$order) {
    throw new DomainException('Không tìm thấy order.');
}
$tickets = $model->tickets($id);
$items = $model->orderItems($id);
$allServed = count($tickets) > 0;
foreach ($tickets as $ticket) {
    if ($ticket['status'] !== 'Đã phục vụ') {
        $allServed = false;
    }
}
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
            <?php if ($allServed): ?>
                <form method="post">
                    <?php csrfInput(); ?>
                    <input type="hidden" name="order_id" value="<?php echo $id; ?>">
                    <button class="button" name="action" value="wait">Khách yêu cầu thanh toán</button>
                </form>
            <?php endif; ?>
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
<h2 class="section-title">Theo dõi từng phiếu</h2>
<div class="ticket-list">
    <?php foreach ($tickets as $ticket): ?>
        <section class="panel">
            <div class="card-top">
                <h2>Phiếu #<?php echo $ticket['id']; ?></h2>
                <span class="badge"><?php echo e($ticket['status']); ?></span>
            </div>
            <p class="muted"><?php echo e($ticket['created_at']); ?></p>
            <?php $items = $model->ticketItems($ticket['id']); ?>
            <?php require __DIR__ . '/items.php'; ?>
            <div class="actions">
                <a class="button" href="?page=ticket&id=<?php echo $ticket['id']; ?>">Xem / In phiếu</a>
                <?php if ($ticket['status'] === 'Chờ kiểm món'): ?>
                    <form method="post" class="check-list">
                        <?php csrfInput(); ?>
                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                        <p>Đối chiếu món thực tế với phiếu trước khi xác nhận:</p>
                        <?php foreach ($items as $item): ?>
                            <label><input type="checkbox" name="checked[]" value="<?php echo $item['id']; ?>" required> Đủ <?php echo $item['quantity']; ?> × <?php echo e($item['name']); ?></label>
                        <?php endforeach; ?>
                        <button class="button primary" name="action" value="check">Đã kiểm đủ tất cả món</button>
                    </form>
                <?php elseif ($ticket['status'] === 'Đã kiểm đủ'): ?>
                    <form method="post">
                        <?php csrfInput(); ?>
                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                        <button class="button primary" name="action" value="serve">Đã mang món phục vụ khách</button>
                    </form>
                <?php endif; ?>
            </div>
        </section>
    <?php endforeach; ?>
</div>
