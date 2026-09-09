<?php
$id = soNguyenDuong($_GET['id'] ?? null);
$ticket = $model->one('SELECT k.*, t.name AS table_name FROM kitchen_tickets k
                      JOIN orders o ON o.id = k.order_id JOIN tables t ON t.id = o.table_id
                      WHERE k.id = ?', array($id));
if (!$ticket) {
    throw new DomainException('Không tìm thấy phiếu.');
}
$items = $model->ticketItems($id);
?>
<div class="page-heading no-print">
    <h1>In phiếu order</h1>
    <button type="button" class="button primary" data-print>In phiếu</button>
</div>
<section class="invoice">
    <div class="invoice-heading">
        <h2>HỒNG RESTAURANT</h2>
        <h2>PHIẾU ORDER #<?php echo $id; ?></h2>
        <p><?php echo e($ticket['table_name']); ?> · Order #<?php echo $ticket['order_id']; ?></p>
        <p><?php echo e($ticket['created_at']); ?></p>
    </div>
    <table class="data-table">
        <thead><tr><th>Món cần chế biến</th><th>Số lượng</th></tr></thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <?php echo e($item['name']); ?>
                        <?php if ($item['note'] !== ''): ?>
                            <div class="food-note"><strong>Yêu cầu:</strong> <?php echo e($item['note']); ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $item['quantity']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p class="hint">Bếp gửi món kèm phiếu cho nhân viên kiểm tra.</p>
</section>
<div class="panel no-print section-title">
    <?php if ($ticket['status'] === 'Chờ in'): ?>
        <p>Sau khi in thành công, xác nhận bên dưới để bếp nhận phiếu. Hủy hộp thoại in sẽ không tự chuyển trạng thái.</p>
        <form method="post">
            <?php csrfInput(); ?>
            <input type="hidden" name="ticket_id" value="<?php echo $id; ?>">
            <label class="check-list"><input type="checkbox" name="printed" value="yes" required> Tôi đã in phiếu thành công</label>
            <button class="button primary" name="action" value="send">Đã in phiếu · Chuyển bếp</button>
        </form>
    <?php else: ?>
        <p>Phiếu hiện ở trạng thái: <?php echo e($ticket['status']); ?>. In lại không tạo phiếu mới.</p>
    <?php endif; ?>
    <a class="text-link" href="?page=order-detail&id=<?php echo $ticket['order_id']; ?>">Quay về order</a>
</div>
