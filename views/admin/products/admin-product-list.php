<div class="management-grid product-management">
    <section class="panel">
        <div class="panel-heading"><h2>Thực đơn nhà hàng</h2><span class="count"><?php echo count($rows); ?> sản phẩm</span></div>
        <div class="table-scroll">
            <table>
                <thead><tr><th>Sản phẩm</th><th class="money">Giá bán</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><strong><?php echo e($row['name']); ?></strong><small>#<?php echo e($row['id']); ?> · <?php echo e($row['category'] ?? 'Chưa phân loại'); ?></small></td>
                            <td class="money"><?php echo tien($row['price']); ?></td>
                            <td>
                                <span class="badge <?php echo $row['status'] === 'Đang bán' ? 'available' : ''; ?>"><?php echo e($row['status']); ?></span>
                                <form method="post" action="admin.php?page=products" class="status-form">
                                    <?php csrfInput(); ?>
                                    <input type="hidden" name="id" value="<?php echo e($row['id']); ?>">
                                    <input type="hidden" name="status" value="<?php echo $row['status'] === 'Đang bán' ? 'Ngừng bán' : 'Đang bán'; ?>">
                                    <button class="text-button" name="action" value="status"><?php echo $row['status'] === 'Đang bán' ? 'Ngừng bán' : 'Bán trở lại'; ?></button>
                                </form>
                            </td>
                            <td>
                                <div class="row-actions">
                                    <a class="button small" href="admin.php?page=products&edit=<?php echo e($row['id']); ?>#editor">Sửa</a>
                                    <form method="post" action="admin.php?page=products" data-confirm="Xóa sản phẩm này?">
                                        <?php csrfInput(); ?>
                                        <input type="hidden" name="id" value="<?php echo e($row['id']); ?>">
                                        <button class="button small danger" name="action" value="delete">Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$rows): ?><tr><td colspan="4" class="empty">Chưa có sản phẩm trong thực đơn.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    <section class="panel editor" id="editor">
        <p class="eyebrow">THÔNG TIN MÓN</p>
        <h2><?php echo $editId ? 'Sửa sản phẩm' : 'Thêm sản phẩm'; ?></h2>
        <?php if (!$categories): ?>
            <p class="form-help">Bạn cần thêm danh mục trước khi thêm sản phẩm.</p>
            <a class="button primary" href="admin.php?page=categories#editor">Thêm danh mục</a>
        <?php else: ?>
            <form method="post" class="editor-form">
                <?php csrfInput(); ?>
                <input type="hidden" name="id" value="<?php echo e($form['id']); ?>">
                <label>Tên sản phẩm<input name="name" maxlength="100" value="<?php echo e($form['name']); ?>" required></label>
                <label>Danh mục
                    <select name="category_id" required>
                        <option value="">Chọn danh mục</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo e($category['id']); ?>" <?php if ((string) $form['category_id'] === (string) $category['id']) echo 'selected'; ?>><?php echo e($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Giá bán (đ)<input name="price" type="number" min="0" max="99999999.99" step="0.01" value="<?php echo e($form['price']); ?>" required></label>
                <label>Trạng thái
                    <select name="status">
                        <option value="Đang bán" <?php if ($form['status'] === 'Đang bán') echo 'selected'; ?>>Đang bán</option>
                        <option value="Ngừng bán" <?php if ($form['status'] === 'Ngừng bán') echo 'selected'; ?>>Ngừng bán</option>
                    </select>
                </label>
                <div class="form-actions">
                    <button class="button primary" name="action" value="save"><?php echo $editId ? 'Lưu thay đổi' : 'Thêm sản phẩm'; ?></button>
                    <?php if ($editId): ?><a class="button" href="admin.php?page=products">Hủy sửa</a><?php endif; ?>
                </div>
            </form>
        <?php endif; ?>
    </section>
</div>
