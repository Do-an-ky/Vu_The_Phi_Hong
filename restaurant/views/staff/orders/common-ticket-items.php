<?php
// Ghép các lần gọi món trong cùng lượt khách thành một danh sách.
$commonItems = array();
foreach ($tickets as $part) {
    foreach ($model->ticketItems($part['id']) as $food) {
        if ($food['item_served_at']) {
            $food['progress'] = 'Đã mang ra bàn';
        } elseif ($food['item_ready_at']) {
            $food['progress'] = 'Đã nấu xong';
        } elseif ($part['status'] === 'Chờ in') {
            $food['progress'] = 'Món mới · Chưa gửi bếp';
        } elseif ($part['status'] === 'Chờ bếp') {
            $food['progress'] = 'Chờ bếp nhận';
        } else {
            $food['progress'] = 'Đang nấu';
        }
        $commonItems[] = $food;
    }
}
?>
<table class="data-table">
    <thead>
        <tr><th>Món / Yêu cầu</th><th>Số lượng</th><th>Tiến độ</th></tr>
    </thead>
    <tbody>
        <?php foreach ($commonItems as $food): ?>
            <tr>
                <td>
                    <strong><?php echo e($food['name']); ?></strong>
                    <?php if ($food['note'] !== ''): ?>
                        <div class="food-note"><?php echo e($food['note']); ?></div>
                    <?php endif; ?>
                </td>
                <td><?php echo (int) $food['quantity']; ?></td>
                <td><?php echo e($food['progress']); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
