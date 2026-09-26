<?php
// Dùng chung cho bếp và nhân viên. Mỗi dòng tương ứng một món và số lượng trên phiếu.
$allReady = count($items) > 0;
foreach ($items as $item) {
    if (!$item['item_ready_at'] || !$item['item_served_at']) {
        $allReady = false;
    }
}
?>
<div class="item-progress-list">
    <?php foreach ($items as $item): ?>
        <div class="item-progress-row">
            <strong><?php echo e($item['quantity']); ?> × <?php echo e($item['name']); ?></strong>
            <?php if ($item['note'] !== ''): ?>
                <p class="food-note"><?php echo e($item['note']); ?></p>
            <?php endif; ?>
            <p class="item-progress-status">
                <?php if ($item['item_served_at']): ?>
                    ✓ Đã mang ra bàn
                <?php elseif ($item['item_ready_at']): ?>
                    ✓ Bếp đã xong · Chờ mang ra bàn
                <?php else: ?>
                    Chưa nấu xong
                <?php endif; ?>
            </p>
            <?php
            $canMarkReady = $progressKitchen
                && in_array($ticket['status'], array('Đang nấu', 'Chờ kiểm món', 'Đã kiểm đủ'), true)
                && (!$item['item_ready_at'] || !$item['item_served_at']);
            ?>
            <?php if ($canMarkReady): ?>
                <form method="post" class="item-progress-form">
                    <?php csrfInput(); ?>
                    <input type="hidden" name="ticket_id" value="<?php echo e($ticket['id']); ?>">
                    <input type="hidden" name="detail_id" value="<?php echo e($item['id']); ?>">
                    <label>
                        <input type="checkbox" name="done" value="yes" required>
                        Đã nấu xong đủ số lượng và mang ra bàn
                    </label>
                    <button class="button" name="action" value="item-ready">Lưu xác nhận</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
