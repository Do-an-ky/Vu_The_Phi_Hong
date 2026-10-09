<?php
$tables = $model->tables();
$filter = $_GET['filter'] ?? 'Tất cả';
$counts = array('Tất cả' => count($tables), 'Trống' => 0, 'Có khách' => 0);
if (!is_string($filter) || !array_key_exists($filter, $counts)) {
    $filter = 'Tất cả';
}
foreach ($tables as &$table) {
    // Lượt cũ đã thu tiền nhưng chưa trả bàn vẫn cần kết thúc bằng POST.
    $table['paid_session'] = $table['order_id'] && $table['order_status'] === 'Đã thanh toán';
    // Bàn chưa kết thúc phục vụ vẫn thuộc nhóm Có khách.
    $table['display_status'] = ($table['paid_session'] || $table['status'] === 'Chờ thanh toán')
        ? 'Có khách' : $table['status'];
    if (isset($counts[$table['display_status']])) {
        $counts[$table['display_status']]++;
    }
}
unset($table);
?>
<div class="page-heading">
    <div>
        <p class="eyebrow">KHÔNG GIAN PHỤC VỤ</p>
        <h1>Sơ đồ bàn</h1>
        <p>Đón khách, sắp xếp bàn và theo dõi từng lượt phục vụ.</p>
    </div>
</div>
<section class="table-overview" aria-label="Lọc trạng thái bàn">
    <?php foreach ($counts as $label => $count): ?>
        <a class="table-filter <?php if ($filter === $label) echo 'is-active'; ?>" href="?page=tables&filter=<?php echo urlencode($label); ?>" <?php if ($filter === $label) echo 'aria-current="true"'; ?>>
            <span><?php echo e($label); ?></span>
            <strong><?php echo $count; ?></strong>
        </a>
    <?php endforeach; ?>
</section>
<div class="table-toolbar">
    <div><h2>Danh sách bàn</h2><p><?php echo e($filter); ?> · <?php echo $counts[$filter]; ?> bàn</p></div>
</div>
<section class="table-grid table-directory">
    <?php $visible = 0; ?>
    <?php foreach ($tables as $table): ?>
        <?php if ($filter !== 'Tất cả' && $table['display_status'] !== $filter) continue; ?>
        <?php $visible++; ?>
        <?php $stateClass = $table['display_status'] === 'Trống' ? 'is-free' : 'is-occupied'; ?>
        <article class="table-card <?php echo $stateClass; ?>">
            <div class="table-card-heading">
                <span class="table-symbol" aria-hidden="true">
                    <svg viewBox="0 0 64 64" fill="none">
                        <rect x="16" y="16" width="32" height="32" rx="10" fill="currentColor" fill-opacity=".1" stroke="currentColor" stroke-width="2"/>
                        <path d="M25 10h14M25 54h14M10 25v14M54 25v14" stroke="currentColor" stroke-width="5" stroke-linecap="round"/>
                        <circle cx="32" cy="32" r="7" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M29 32h6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                </span>
                <span class="table-state"><i aria-hidden="true"></i><?php echo e($table['display_status']); ?></span>
            </div>
            <h2><?php echo e($table['name']); ?></h2>
            <?php if ($table['paid_session']): ?>
                <p class="table-description">Đã thanh toán · Kết thúc lượt phục vụ cũ</p>
                <?php $receipt = $model->one('SELECT payment_id FROM payment_receipts WHERE order_id = ?', array($table['order_id'])); ?>
                <?php if ($receipt): ?>
                    <form method="post">
                        <?php csrfInput(); ?>
                        <input type="hidden" name="payment_id" value="<?php echo $receipt['payment_id']; ?>">
                        <button class="button primary full" name="action" value="finish-receipt">Trả bàn về trống</button>
                    </form>
                <?php endif; ?>
            <?php elseif ($table['order_id']): ?>
                <p class="table-description">Order #<?php echo $table['order_id']; ?></p>
                <div class="table-order-total"><span>Tổng tạm tính</span><strong><?php echo tien($model->total($table['order_id'])); ?></strong></div>
                <div class="table-card-actions">
                    <a class="button table-tracking-button full" href="?page=order-detail&id=<?php echo $table['order_id']; ?>">Theo dõi phục vụ →</a>
                </div>
            <?php elseif ($table['display_status'] === 'Trống'): ?>

                
                <form method="post">
                    <?php csrfInput(); ?>
                    <input type="hidden" name="table_id" value="<?php echo $table['id']; ?>">
                    <button class="button primary full" name="action" value="open">Xếp khách vào bàn →</button>
                </form>
            <?php else: ?>
                <p class="table-description">Cần kiểm tra lượt phục vụ của bàn.</p>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
</section>
<?php if ($visible === 0): ?><p class="empty">Không có bàn ở trạng thái này.</p><?php endif; ?>
