<?php
$id = (int) ($_GET['id'] ?? 0);
$order = $model->order($id);
if (!$order) {
    echo '<div class="panel"><h1>Chọn bàn trước khi gọi món</h1><p>Xếp khách vào bàn trống hoặc mở bàn đang phục vụ.</p><a class="button primary" href="?page=tables">Chọn bàn</a></div>';
    return;
}
if ($order['status'] !== 'Đang phục vụ') {
    echo '<div class="panel"><p>Bàn không ở trạng thái đang phục vụ.</p><a class="button" href="?page=order-detail&id=' . $id . '">Xem order / Tiếp tục gọi món</a></div>';
    return;
}
$products = $model->products();
$quantities = $errorMessage === '' ? ($_POST['quantity'] ?? array()) : array();
$notes = $errorMessage === '' ? ($_POST['note'] ?? array()) : array();
if (!is_array($notes)) {
    throw new DomainException('Ghi chú không hợp lệ.');
}
if (!is_array($quantities)) {
    $quantities = array();
}
$requestKey = $orderDraft['request_key'] ?? '';
$isReview = ($_POST['action'] ?? '') === 'review' && $errorMessage === '';
$items = array();
$total = 0;
$categories = array();
foreach ($products as $product) {
    $categoryName = $product['category'] ?? 'Chưa phân loại';
    $categories[$categoryName] = true;
    $quantity = filter_var($quantities[$product['id']] ?? 0, FILTER_VALIDATE_INT);
    if ($quantity === false || $quantity < 0 || $quantity > 99) {
        throw new DomainException('Số lượng mỗi món phải từ 0 đến 99.');
    }
    if ($quantity > 0) {
        $product['quantity'] = $quantity;
        $product['note'] = ghiChuMon($notes[$product['id']] ?? '');
        $items[] = $product;
        $total += $quantity * $product['price'];
    }
}
?>
<div class="page-heading">
    <div>
        <p class="eyebrow">ORDER #<?php echo $id; ?></p>
        <h1><?php echo e($order['table_name']); ?> · Gọi món</h1>
        <p>Mỗi lần xác nhận tạo một phiếu riêng. Món đã xác nhận không sửa tại đây.</p>
    </div>
    <a class="button" href="?page=order-detail&id=<?php echo $id; ?>">Theo dõi order</a>
</div>
<?php if ($isReview && count($items) > 0): ?>
    <section class="panel">
        <h2>Kiểm tra món trước khi xác nhận</h2>
        <?php require __DIR__ . '/items.php'; ?>
        <div class="ticket-total"><span>Tổng phiếu này</span><strong><?php echo tien($total); ?></strong></div>
        <form method="post">
            <?php csrfInput(); ?>
            <input type="hidden" name="order_id" value="<?php echo $id; ?>">
            <input type="hidden" name="request_key" value="<?php echo $requestKey; ?>">
            <?php foreach ($items as $item): ?>
                <input type="hidden" name="quantity[<?php echo $item['id']; ?>]" value="<?php echo $item['quantity']; ?>">
                <input type="hidden" name="note[<?php echo $item['id']; ?>]" value="<?php echo e($item['note']); ?>">
            <?php endforeach; ?>
            <p class="muted">Giá được lấy từ thực đơn tại lúc xác nhận. Sau xác nhận, in phiếu rồi chuyển bếp.</p>
            <div class="actions">
                <button class="button" name="action" value="edit">Quay lại sửa món</button>
                <button class="button primary" name="action" value="confirm">Xác nhận món & tạo phiếu</button>
            </div>
        </form>
    </section>
<?php else: ?>
    <?php if ($isReview): ?><div class="notice error">Hãy chọn ít nhất một món.</div><?php endif; ?>
    <form method="post" id="menu-form">
        <?php csrfInput(); ?>
        <input type="hidden" name="order_id" value="<?php echo $id; ?>">
        <div class="order-layout">
            <section class="menu-workspace">
                <aside class="category-sidebar" aria-label="Danh mục món">
                    <p class="category-sidebar-title">DANH MỤC MÓN</p>
                    <button type="button" class="category-button active" data-category="">
                        <span>Tất cả món</span>
                        <strong><?php echo count($products); ?></strong>
                    </button>
                    <?php foreach ($categories as $name => $unused): ?>
                        <?php
                        $categoryCount = 0;
                        foreach ($products as $product) {
                            if (($product['category'] ?? 'Chưa phân loại') === $name) {
                                $categoryCount++;
                            }
                        }
                        ?>
                        <button type="button" class="category-button" data-category="<?php echo e($name); ?>">
                            <span><?php echo e($name); ?></span>
                            <strong><?php echo $categoryCount; ?></strong>
                        </button>
                    <?php endforeach; ?>
                </aside>

                <div class="menu-content">
                    <input id="search" class="search" type="search" placeholder="Tìm món…" aria-label="Tìm món">
                    <div class="menu-grid">
                        <?php foreach ($products as $product): ?>
                            <article class="menu-item" data-name="<?php echo e($product['name']); ?>" data-category="<?php echo e($product['category'] ?? 'Chưa phân loại'); ?>" data-price="<?php echo e($product['price']); ?>">
                                <p class="food-category"><?php echo e($product['category']); ?></p>
                                <h3><?php echo e($product['name']); ?></h3>
                                <strong><?php echo tien($product['price']); ?></strong>
                                <button type="button" class="button dish-action" data-edit-dish>
                                    <span>＋</span> Chọn món
                                </button>
                                <div class="quantity">
                                    <button type="button" data-step="-1" aria-label="Giảm <?php echo e($product['name']); ?>">−</button>
                                    <input class="quantity-input" name="quantity[<?php echo $product['id']; ?>]" value="<?php echo (int) ($quantities[$product['id']] ?? 0); ?>" type="number" min="0" max="99" required aria-label="Số lượng <?php echo e($product['name']); ?>">
                                    <button type="button" data-step="1" aria-label="Tăng <?php echo e($product['name']); ?>">+</button>
                                    <button type="button" data-clear="yes" class="remove">Xóa</button>
                                </div>
                                <label class="dish-note-label">Ghi chú cho món
                                    <textarea class="dish-note" name="note[<?php echo $product['id']; ?>]" maxlength="300" rows="2"><?php echo e(ghiChuMon($notes[$product['id']] ?? '')); ?></textarea>
                                </label>
                                <p class="dish-selection muted"></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    <p id="no-results" class="empty" hidden>Không tìm thấy món phù hợp.</p>
                    <?php if (count($products) === 0): ?><p class="empty">Chưa có món đang bán trong CSDL.</p><?php endif; ?>
                </div>
            </section>
            <aside class="ticket">
                <p class="eyebrow">PHIẾU MỚI / BỔ SUNG</p>
                <div class="draft-heading">
                    <h2>Món đang chọn</h2>
                    <button type="button" class="button clear-draft" id="clear-draft" disabled>Làm trống</button>
                </div>
                <div id="cart-summary"><p>Chọn số lượng ở thực đơn bên cạnh.</p></div>
                <div class="ticket-total"><span>Tạm tính</span><strong id="draft-total">0 đ</strong></div>
                <button class="button primary full" name="action" value="review">Kiểm tra & xác nhận →</button>
            </aside>
        </div>
    </form>
    <dialog id="dish-modal" aria-labelledby="dish-modal-title" aria-describedby="dish-modal-help">
        <form id="dish-modal-form">
            <div class="card-top">
                <h2 id="dish-modal-title">Chọn món</h2>
            </div>
            <p id="dish-modal-price" class="muted"></p>
            <label>Số lượng
                <input id="dish-modal-quantity" type="number" min="1" max="99" value="1" required>
            </label>
            <label>Ghi chú yêu cầu của khách
                <textarea id="dish-modal-note" rows="4" maxlength="300" placeholder="Ví dụ: ít cay, không hành, nước chấm để riêng…"></textarea>
            </label>
            <div class="actions">
                <button type="button" class="button" data-close-dish>Hủy</button>
                <button class="button primary" type="submit">Lưu món vào phiếu</button>
            </div>
        </form>
    </dialog>
<?php endif; ?>
