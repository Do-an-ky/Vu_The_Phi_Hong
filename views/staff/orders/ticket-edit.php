<?php
$editItems = $model->all('SELECT d.* FROM order_details d JOIN kitchen_ticket_items k ON k.detail_id = d.id WHERE k.ticket_id = ? ORDER BY k.id', array($id));
$products = $model->products();
$version = hash('sha256', json_encode($model->ticketItems($id)));
?>
<div class="page-heading"><h1>Sửa món · <?php echo e($ticket['table_name']); ?></h1></div>
<form method="post" class="panel">
    <?php csrfInput(); ?>
    <input type="hidden" name="ticket_id" value="<?php echo (int) $id; ?>">
    <input type="hidden" name="version" value="<?php echo e($version); ?>">
    <?php foreach ($editItems as $index => $item): ?>
        <div class="item-progress-row">
            <label for="edit-product-<?php echo $index; ?>">Món ăn</label>
            <select id="edit-product-<?php echo $index; ?>" name="product_id[]" required>
                <?php if (!in_array($item['product_id'], array_column($products, 'id'))): ?>
                    <option value="" selected disabled>Món đã ngừng bán · Chọn lại món</option>
                <?php endif; ?>
                <?php foreach ($products as $product): ?>
                    <option value="<?php echo (int) $product['id']; ?>" <?php if ($product['id'] == $item['product_id']) echo 'selected'; ?>><?php echo e($product['name']); ?> · <?php echo tien($product['price']); ?></option>
                <?php endforeach; ?>
            </select>
            <label for="edit-quantity-<?php echo $index; ?>">Số lượng</label>
            <input id="edit-quantity-<?php echo $index; ?>" type="number" name="quantity[]" min="1" max="99" value="<?php echo (int) $item['quantity']; ?>" required>
            <label for="edit-note-<?php echo $index; ?>">Ghi chú</label>
            <input id="edit-note-<?php echo $index; ?>" name="note[]" value="<?php echo e($item['note']); ?>">
        </div>
    <?php endforeach; ?>
    <div class="actions">
        <button class="button primary" name="action" value="update-ticket">Lưu và xem phiếu</button>
        <a class="button" href="?page=ticket&id=<?php echo (int) $id; ?>">Hủy</a>
    </div>
</form>