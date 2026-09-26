<div class="table-scroll">
    <table class="data-table">
        <thead><tr><th>Món</th><th>SL</th><th>Đơn giá</th><th>Thành tiền</th></tr></thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <?php echo e($item['name']); ?>
                        <?php if (!empty($item['note'])): ?>
                            <div class="food-note">Ghi chú: <?php echo e($item['note']); ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?php echo (int) $item['quantity']; ?></td>
                    <td><?php echo tien($item['price']); ?></td>
                    <td><?php echo tien($item['quantity'] * $item['price']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
