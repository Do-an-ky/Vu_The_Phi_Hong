<?php
$id = soNguyenDuong($_GET['id'] ?? null);
$ticket = $model->one('SELECT k.*, t.name AS table_name FROM kitchen_tickets k
                      JOIN orders o ON o.id = k.order_id JOIN tables t ON t.id = o.table_id
                      WHERE k.id = ?', array($id));
if (!$ticket) {
    throw new DomainException('Không tìm thấy phiếu.');
}
// In lại từ theo dõi: lấy tất cả món của cùng lượt khách, không đổi trạng thái.
$printWholeTable = ($_GET['scope'] ?? '') === 'table';
$items = array();
if ($printWholeTable) {
    foreach ($model->tickets($ticket['order_id']) as $part) {
        foreach ($model->ticketItems($part['id']) as $item) {
            $items[] = $item;
        }
    }
} else {
    $items = $model->ticketItems($id);
}
?>
<div class="page-heading no-print">
    <h1><?php echo $printWholeTable ? 'In lại toàn bộ món của bàn' : 'In phiếu order'; ?></h1>
    <button type="button" class="button primary" data-print>In phiếu</button>
</div>
<section class="invoice">
    <div class="invoice-heading">
        <h2>RESTAURANT</h2>
        <h2>Phiếu bếp – <?php echo e($ticket['table_name']); ?></h2>
        <p><?php echo e($ticket['table_name']); ?> · Order #<?php echo $ticket['order_id']; ?></p>
        <p><?php echo e($ticket['created_at']); ?></p>
    </div>
    <table class="data-table">
        <thead><tr><th><?php echo $printWholeTable ? 'Món đã gọi' : 'Món cần chế biến'; ?></th><th>Số lượng</th></tr></thead>
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
    <p class="hint"><?php echo $printWholeTable ? 'Bản in lại toàn bộ món của lượt khách này, gồm các lần gọi bổ sung.' : 'Bếp gửi món kèm phiếu cho nhân viên kiểm tra.'; ?></p>
</section>
<div class="panel no-print section-title">
    <?php if ($printWholeTable): ?>
        <p>In lại không gửi món xuống bếp và không thay đổi tiến độ phục vụ.</p>
    <?php elseif ($ticket['status'] === 'Chờ in'): ?>
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
