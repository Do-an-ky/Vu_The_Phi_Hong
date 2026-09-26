<?php
$displayDate = date('d/m/Y', strtotime($stats['date']));
$channels = array(
    array('Tiền mặt', $stats['cash'], 'cash', '₫'),
    array('Chuyển khoản', $stats['transfer'], 'transfer', '↗'),
    array('Thẻ', $stats['card'], 'card', '▣')
);
if ((float) $stats['other'] !== 0.0) {
    $channels[] = array('Khác', $stats['other'], 'other', '＋');
}
?>
<div class="admin-dashboard">
    <section class="dashboard-control">
        <div class="dashboard-control-copy">
            <span class="dashboard-control-icon" aria-hidden="true">▦</span>
            <div><h2>Thống kê theo ngày</h2><p>Đang xem dữ liệu ngày <strong><?php echo e($displayDate); ?></strong></p></div>
        </div>
        <form method="get" action="admin.php" class="dashboard-date-form">
            <input type="hidden" name="page" value="dashboard">
            <label for="dashboard-date">Ngày thống kê
                <input id="dashboard-date" type="date" name="date" min="1000-01-01" max="9999-12-31" value="<?php echo e($stats['date']); ?>" onchange="this.form.submit()" required>
            </label>
            <a class="button" href="admin.php?page=dashboard">Hôm nay</a>
        </form>
    </section>

    <section class="dashboard-kpis" aria-label="Chỉ số tổng quan">
        <article class="dashboard-kpi orders">
            <div class="kpi-top"><span class="kpi-icon">▤</span><span class="kpi-label">Hóa đơn đã thanh toán</span></div>
            <strong><?php echo e($stats['invoices']); ?></strong>
            <p>Hóa đơn của các order được tạo ngày <?php echo e($displayDate); ?></p>
        </article>
        <article class="dashboard-kpi revenue">
            <div class="kpi-top"><span class="kpi-icon">₫</span><span class="kpi-label">Doanh thu</span></div>
            <strong><?php echo tien($stats['revenue']); ?></strong>
            <p>Doanh thu theo ngày tạo order</p>
        </article>
        <article class="dashboard-kpi tables">
            <div class="kpi-top"><span class="kpi-icon">▦</span><span class="kpi-label">Bàn đang sử dụng</span></div>
            <strong><?php echo e($stats['tables']); ?></strong>
            <p>Lượt phục vụ đang hoạt động hiện tại</p>
        </article>
    </section>

    <section class="dashboard-revenue-panel">
        <div class="dashboard-section-heading">
            <div><p class="eyebrow">CƠ CẤU DOANH THU</p><h2>Phương thức thanh toán</h2></div>
            <div class="revenue-total"><span>Tổng doanh thu</span><strong><?php echo tien($stats['revenue']); ?></strong></div>
        </div>
        <div class="revenue-channels">
            <?php foreach ($channels as $channel): ?>
                <article class="revenue-channel <?php echo e($channel[2]); ?>">
                    <div class="channel-heading"><span class="channel-icon"><?php echo e($channel[3]); ?></span><span><?php echo e($channel[0]); ?></span></div>
                    <strong><?php echo tien($channel[1]); ?></strong>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="dashboard-orders-panel">
        <div class="dashboard-section-heading">
            <div><p class="eyebrow">THANH TOÁN TRONG NGÀY</p><h2>Hóa đơn ngày <?php echo e($displayDate); ?></h2></div>
            <span class="dashboard-order-count"><?php echo e($stats['invoices']); ?> hóa đơn</span>
        </div>
        <div class="table-scroll">
            <table class="dashboard-orders-table">
                <thead><tr><th>Hóa đơn</th><th>Order</th><th>Bàn</th><th>Nhân viên</th><th>Giờ tạo đơn</th><th>Thanh toán lúc</th><th>Phương thức</th><th class="money">Tổng tiền</th></tr></thead>
                <tbody>
                    <?php foreach ($stats['day_invoices'] as $order): ?>
                        <tr>
                            <td><strong class="order-code">#<?php echo e($order['id']); ?></strong></td>
                            <td>#<?php echo e($order['order_id']); ?></td>
                            <td><strong><?php echo e($order['table_name'] ?? '—'); ?></strong></td>
                            <td><?php echo e($order['staff_name'] ?? '—'); ?></td>
                            <td><?php echo e(date('H:i', strtotime($order['order_created_at']))); ?></td>
                            <td><?php echo e(date('d/m H:i', strtotime($order['paid_at']))); ?></td>
                            <td><span class="dashboard-status paid"><?php echo e($order['payment_method']); ?></span></td>
                            <td class="money"><strong><?php echo tien($order['total']); ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$stats['day_invoices']): ?>
                        <tr><td colspan="8"><div class="dashboard-empty"><span>▤</span><strong>Chưa có hóa đơn</strong><p>Chưa có order của ngày này được thanh toán.</p></div></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
