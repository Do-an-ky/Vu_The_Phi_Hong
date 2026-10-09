<?php
$id = soNguyenDuong($_GET['id'] ?? null);
$ticket = $model->one('SELECT k.*, t.name AS table_name FROM kitchen_tickets k
                      JOIN orders o ON o.id = k.order_id JOIN tables t ON t.id = o.table_id
                      WHERE k.id = ?', array($id));
if (!$ticket) {
    throw new DomainException('Không tìm thấy phiếu.');
}
if (($_GET['edit'] ?? '') === '1') {
    if ($ticket['status'] !== 'Chờ in') {
        throw new DomainException('Phiếu đã chuyển bếp, không thể sửa món.');
    }
    require __DIR__ . '/ticket-edit.php';
    return;
}
// In lại từ theo dõi: lấy tất cả món của cùng lượt khách, không đổi trạng thái.
$printWholeTable = ($_GET['scope'] ?? '') === 'table';
$items = array();
$pendingTickets = array();
if ($printWholeTable) {
    foreach ($model->tickets($ticket['order_id']) as $part) {
        if ($part['status'] === 'Chờ in') {
            $pendingTickets[] = $part['id'];
        }
        foreach ($model->ticketItems($part['id']) as $item) {
            $items[] = $item;
        }
    }
} else {
    $items = $model->ticketItems($id);
}
?>
<div class="page-heading no-print">
    <h1><?php echo $printWholeTable ? 'In lại toàn bộ món của bàn' : 'In phiếu bếp'; ?></h1>
    <button type="button" class="button primary" data-print>In phiếu bếp</button>
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
        <p><?php echo $pendingTickets ? 'Còn món chưa gửi bếp. Sau khi in, bấm gửi món mới xuống bếp.' : 'Tất cả món đã gửi bếp. In lại không gửi lại món cũ.'; ?></p>
    <?php elseif ($ticket['status'] !== 'Chờ in'): ?>
        <p>Phiếu hiện ở trạng thái: <?php echo e($ticket['status']); ?>. In lại không tạo phiếu mới.</p>
    <?php endif; ?>
    <div class="actions">
        <?php if ($printWholeTable && $pendingTickets): ?>
            <form method="post">
                <?php csrfInput(); ?>
                <input type="hidden" name="order_id" value="<?php echo (int) $ticket['order_id']; ?>">
                <?php foreach ($pendingTickets as $pendingId): ?>
                    <input type="hidden" name="ticket_ids[]" value="<?php echo (int) $pendingId; ?>">
                <?php endforeach; ?>
                <button class="button primary" name="action" value="send-table">Đã in phiếu · Gửi món mới xuống bếp</button>
            </form>
        <?php endif; ?>
        
        <?php if (!$printWholeTable && $ticket['status'] === 'Chờ in'): ?>
            <a class="button" href="?page=orders&id=<?php echo (int) $ticket['order_id']; ?>&ticket_id=<?php echo $id; ?>">Quay lại gọi món / Sửa món</a>
            <form method="post">
                <?php csrfInput(); ?>
                <input type="hidden" name="ticket_id" value="<?php echo $id; ?>">
                <button class="button primary" name="action" value="send">Đã in phiếu · Chuyển bếp</button>
            </form>
        <?php endif; ?>
        <?php if ($printWholeTable || $ticket['status'] !== 'Chờ in'): ?>
            <a class="button" href="?page=order-detail&id=<?php echo (int) $ticket['order_id']; ?>"><?php echo $printWholeTable ? 'Quay lại' : 'Quay về order'; ?></a>
        <?php endif; ?>
    </div>
</div>