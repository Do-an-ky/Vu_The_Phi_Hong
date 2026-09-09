<?php $displayDate = date('d/m/Y', strtotime($stats['date'])); ?>
<form method="get" action="admin.php" class="panel dashboard-filter">
    <input type="hidden" name="page" value="dashboard">
    <label for="dashboard-date">Chọn ngày xem thống kê
        <input id="dashboard-date" type="date" name="date" min="1000-01-01" max="9999-12-31" value="<?php echo e($stats['date']); ?>" required>
    </label>
    <button class="button primary" type="submit">Xem thống kê</button>
    <a class="button" href="admin.php?page=dashboard">Hôm nay</a>
    <p class="muted">Đang xem ngày <strong><?php echo e($displayDate); ?></strong></p>
</form>
<section class="stats-grid" aria-label="Thống kê ngày đã chọn">
    <article class="stat-card">
        <p>Tổng số đơn ngày <?php echo e($displayDate); ?></p>
        <strong><?php echo e($stats['orders']); ?></strong>
        <span>Đơn tạo trong ngày, gồm cả đơn đã hủy</span>
    </article>
    <article class="stat-card revenue">
        <p>Doanh thu ngày <?php echo e($displayDate); ?></p>
        <strong><?php echo tien($stats['revenue']); ?></strong>
        <span>Tính theo thời điểm thanh toán</span>
    </article>
    <article class="stat-card">
        <p>Bàn đang sử dụng hiện tại</p>
        <strong><?php echo e($stats['tables']); ?></strong>
        <span>Lượt phục vụ chưa kết thúc</span>
    </article>
</section>
<section class="payment-breakdown" aria-label="Doanh thu theo phương thức thanh toán">
    <article class="panel payment-card">
        <p>Tiền mặt</p>
        <strong><?php echo tien($stats['cash']); ?></strong>
    </article>
    <article class="panel payment-card">
        <p>Chuyển khoản</p>
        <strong><?php echo tien($stats['transfer']); ?></strong>
    </article>
    <article class="panel payment-card">
        <p>Thẻ</p>
        <strong><?php echo tien($stats['card']); ?></strong>
    </article>
    <?php if ((float) $stats['other'] != 0): ?>
        <article class="panel payment-card">
            <p>Khác / chưa xác định</p>
            <strong><?php echo tien($stats['other']); ?></strong>
        </article>
    <?php endif; ?>
</section>
<section class="panel">
    <div class="panel-heading">
        <h2>Đơn hàng ngày <?php echo e($displayDate); ?></h2>
        <span class="muted"><?php echo e($stats['orders']); ?> đơn tạo trong ngày</span>
    </div>
    <div class="table-scroll">
        <table>
            <thead><tr><th>Mã đơn</th><th>Bàn</th><th>Nhân viên</th><th>Thời gian</th><th class="money">Tổng tiền</th><th>Trạng thái</th></tr></thead>
            <tbody>
                <?php foreach ($stats['day_orders'] as $order): ?>
                    <tr>
                        <td><strong>#<?php echo e($order['id']); ?></strong></td>
                        <td><?php echo e($order['table_name'] ?? '—'); ?></td>
                        <td><?php echo e($order['staff_name'] ?? '—'); ?></td>
                        <td><?php echo e(date('d/m H:i', strtotime($order['created_at']))); ?></td>
                        <td class="money"><?php echo tien($order['total']); ?></td>
                        <td><span class="badge"><?php echo e($order['status']); ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$stats['day_orders']): ?>
                    <tr><td colspan="6" class="empty">Không có đơn hàng trong ngày đã chọn.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
