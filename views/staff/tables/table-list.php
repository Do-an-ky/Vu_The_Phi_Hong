<?php
$tables = $model->tables();
$filter = $_GET['filter'] ?? 'Tất cả';
$counts = array('Tất cả' => count($tables), 'Trống' => 0, 'Có khách' => 0, 'Chờ thanh toán' => 0, 'Chờ in hóa đơn' => 0);
foreach ($tables as $table) {
    if (isset($counts[$table['status']])) {
        $counts[$table['status']]++;
    }
}
?>
<div class="page-heading">
    <div>
        <p class="eyebrow">PHỤC VỤ TẠI BÀN</p>
        <h1>Quản lý bàn</h1>
        <p>Xếp bàn cho khách, theo dõi món và kết thúc lượt phục vụ.</p>
    </div>
    <a class="button" href="?page=tables">Làm mới</a>
</div>
<section class="stats">
    <?php foreach ($counts as $label => $count): ?>
        <a class="stat" href="?page=tables&filter=<?php echo urlencode($label); ?>">
            <div><span><?php echo e($label); ?></span><strong><?php echo $count; ?></strong></div>
        </a>
    <?php endforeach; ?>
</section>
<section class="table-grid">
    <?php $visible = 0; ?>
    <?php foreach ($tables as $table): ?>
        <?php if ($filter !== 'Tất cả' && $table['status'] !== $filter) continue; ?>
        <?php $visible++; ?>
        <article class="table-card">
            <div class="card-top">
                <h2><?php echo e($table['name']); ?></h2>
                <span class="badge"><?php echo e($table['status']); ?></span>
            </div>
            <?php if ($table['order_id']): ?>
                <p>Order #<?php echo $table['order_id']; ?> · <?php echo tien($table['total']); ?></p>
                <a class="button full" href="?page=order-detail&id=<?php echo $table['order_id']; ?>">Xem lượt phục vụ →</a>
            <?php elseif ($table['status'] === 'Trống'): ?>
                <p class="muted">Sẵn sàng đón khách</p>
                <form method="post">
                    <?php csrfInput(); ?>
                    <input type="hidden" name="table_id" value="<?php echo $table['id']; ?>">
                    <button class="button primary full" name="action" value="open">Xếp khách vào bàn</button>
                </form>
            <?php else: ?>
                <p class="muted">Bàn chưa có lượt phục vụ tương ứng. Cần kiểm tra dữ liệu bàn.</p>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
    <?php if ($visible === 0): ?><p class="empty">Không có bàn ở trạng thái này.</p><?php endif; ?>
</section>
