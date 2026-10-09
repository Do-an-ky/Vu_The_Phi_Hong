<?php
$tickets = $model->all("SELECT k.*, t.name AS table_name FROM kitchen_tickets k
    JOIN orders o ON o.id = k.order_id
    JOIN service_sessions s ON s.order_id = o.id
    JOIN tables t ON t.id = o.table_id
    WHERE k.status <> 'Chờ in' AND o.status <> 'Đã thanh toán'
    ORDER BY k.id");
$tables = array();
foreach ($tickets as $ticket) {
    $ticket['items'] = $model->ticketItems($ticket['id']);
    $stage = $ticket['status'] === 'Chờ kiểm món' ? 'Chờ hoàn tất' : $ticket['status'];
    $tables[$stage][$ticket['order_id']][] = $ticket;
}
$columns = array('Chờ bếp', 'Đang nấu', 'Chờ hoàn tất');
?>
<div class="page-heading">
    <div><p class="eyebrow">CHẾ BIẾN</p><h1>Khu vực bếp</h1><p>Các món cùng bàn được gom theo từng trạng thái. Món gọi bổ sung chờ bếp nhận riêng.</p></div>
    <a class="button" href="?page=kitchen">Làm mới</a>
</div>
<div class="kitchen-board">
<?php foreach ($columns as $column): ?>
    <section class="kitchen-column">
        <h2><?php echo e($column); ?></h2>
        <label class="kitchen-table-search">Tìm tên bàn
            <input data-kitchen-table-search type="search" placeholder="Nhập tên hoặc số bàn…">
        </label>
        <?php $count = 0; ?>
        <?php foreach (($tables[$column] ?? array()) as $orderId => $tableTickets): ?>
            <?php
            $count++;
            $allReady = true;
            ?>
            <article class="kitchen-card" data-order-id="<?php echo (int) $orderId; ?>" data-table-name="<?php echo e($tableTickets[0]['table_name']); ?>">
                <h3>Phiếu bếp – <?php echo e($tableTickets[0]['table_name']); ?></h3>
                <?php if ($column === 'Đang nấu'): ?>
                    <button type="button" class="button" data-select-all>Chọn tất cả các món</button>
                <?php endif; ?>
                <div class="item-progress-list">
                <?php foreach ($tableTickets as $ticket): ?>
                    <?php foreach ($ticket['items'] as $item): ?>
                        <?php
                        $done = $item['item_ready_at'] && $item['item_served_at'];
                        if (!$done) $allReady = false;
                        ?>
                        <div class="item-progress-row">
                            <strong><?php echo e($item['quantity']); ?> × <?php echo e($item['name']); ?></strong>
                            <?php if ($item['note'] !== ''): ?><p class="food-note"><?php echo e($item['note']); ?></p><?php endif; ?>
                            <p class="item-progress-status"><?php echo $done ? '✓ Đã mang ra bàn' : ($ticket['status'] === 'Chờ bếp' ? 'Món mới · Chờ nhận' : 'Chưa nấu xong'); ?></p>
                            <?php if (!$done && $ticket['status'] !== 'Chờ bếp'): ?>
                                <div class="item-progress-form">
                                    <?php csrfInput(); ?>
                                    <input type="hidden" name="ticket_id" value="<?php echo (int) $ticket['id']; ?>">
                                    <input type="hidden" name="detail_id" value="<?php echo (int) $item['id']; ?>">
                                    <label><input type="checkbox" name="done" value="yes"> Đã nấu xong đủ số lượng và mang ra bàn</label>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
                </div>
                <?php if ($column !== 'Chờ hoàn tất'): ?>
                    <form method="post">
                        <?php csrfInput(); ?>
                        <input type="hidden" name="order_id" value="<?php echo (int) $orderId; ?>">
                        <?php foreach ($tableTickets as $ticket): ?>
                            <input type="hidden" name="ticket_ids[]" value="<?php echo (int) $ticket['id']; ?>">
                        <?php endforeach; ?>
                        <?php if ($column === 'Chờ bếp'): ?>
                            <button class="button primary full" name="action" value="accept-table">Nhận phiếu · Nấu các món mới</button>
                        <?php else: ?>
                            <button class="button primary full" name="action" value="ready-table" disabled>Xác nhận</button>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
        <?php if (!$count): ?><p class="empty">Chưa có bàn.</p><?php endif; ?>
        <p class="empty kitchen-search-empty" hidden>Không tìm thấy bàn phù hợp.</p>
    </section>
<?php endforeach; ?>
</div>

