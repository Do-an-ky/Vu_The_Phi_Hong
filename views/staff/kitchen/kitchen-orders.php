<?php
$tickets = $model->all("SELECT k.*, t.name AS table_name FROM kitchen_tickets k
                        JOIN orders o ON o.id = k.order_id
                        JOIN tables t ON t.id = o.table_id
                        WHERE k.status IN ('Chờ bếp', 'Đang nấu', 'Chờ kiểm món')
                        ORDER BY k.created_at, k.id");
$columns = array('Chờ bếp', 'Đang nấu', 'Chờ kiểm món');
?>
<div class="page-heading">
    <div><p class="eyebrow">CHẾ BIẾN</p><h1>Khu vực bếp</h1><p>Nhận phiếu theo thứ tự. Khi nấu xong, gửi món cùng phiếu cho nhân viên.</p></div>
    <a class="button" href="?page=kitchen">Làm mới</a>
</div>
<div class="kitchen-board">
    <?php foreach ($columns as $column): ?>
        <section class="kitchen-column">
            <h2><?php echo e($column); ?></h2>
            <?php $count = 0; ?>
            <?php foreach ($tickets as $ticket): ?>
                <?php if ($ticket['status'] !== $column) continue; ?>
                <?php $count++; ?>
                <article class="kitchen-card">
                    <h3><?php echo e($ticket['table_name']); ?> · Phiếu #<?php echo $ticket['id']; ?></h3>
                    <p class="muted"><?php echo e($ticket['created_at']); ?></p>
                    <ul>
                        <?php foreach ($model->ticketItems($ticket['id']) as $item): ?>
                            <li>
                                <strong><?php echo $item['quantity']; ?> ×</strong> <?php echo e($item['name']); ?>
                                <?php if ($item['note'] !== ''): ?>
                                    <div class="food-note">Yêu cầu: <?php echo e($item['note']); ?></div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($column !== 'Chờ kiểm món'): ?>
                        <form method="post">
                            <?php csrfInput(); ?>
                            <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                            <?php if ($column === 'Chờ bếp'): ?>
                                <button class="button primary full" name="action" value="accept">Nhận phiếu · Bắt đầu nấu</button>
                            <?php else: ?>
                                <button class="button primary full" name="action" value="ready">Nấu xong · Giao món và phiếu</button>
                            <?php endif; ?>
                        </form>
                    <?php else: ?>
                        <a class="button full" href="?page=order-detail&id=<?php echo $ticket['order_id']; ?>">Nhân viên kiểm món →</a>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
            <?php if ($count === 0): ?><p class="empty">Chưa có phiếu.</p><?php endif; ?>
        </section>
    <?php endforeach; ?>
</div>
