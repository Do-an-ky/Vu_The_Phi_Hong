<div class="management-grid">
    <section class="panel">
        <div class="panel-heading"><h2>Danh sách danh mục</h2><span class="count"><?php echo count($rows); ?> danh mục</span></div>
        <div class="table-scroll">
            <table>
                <thead><tr><th>Mã</th><th>Tên danh mục</th><th>Số sản phẩm</th><th>Thao tác</th></tr></thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td>#<?php echo e($row['id']); ?></td>
                            <td><strong><?php echo e($row['name']); ?></strong></td>
                            <td><?php echo e($row['product_count']); ?></td>
                            <td>
                                <div class="row-actions">
                                    <a class="button small" href="admin.php?page=categories&edit=<?php echo e($row['id']); ?>#editor">Sửa</a>
                                    <form method="post" action="admin.php?page=categories" data-confirm="Xóa danh mục này?">
                                        <?php csrfInput(); ?>
                                        <input type="hidden" name="id" value="<?php echo e($row['id']); ?>">
                                        <button class="button small danger" name="action" value="delete">Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$rows): ?><tr><td colspan="4" class="empty">Chưa có danh mục. Thêm danh mục trước khi thêm sản phẩm.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    <section class="panel editor" id="editor">
        <p class="eyebrow">SẮP XẾP THỰC ĐƠN</p>
        <h2><?php echo $editId ? 'Sửa danh mục' : 'Thêm danh mục'; ?></h2>
        <form method="post" class="editor-form">
            <?php csrfInput(); ?>
            <input type="hidden" name="id" value="<?php echo e($form['id']); ?>">
            <label>Tên danh mục<input name="name" maxlength="100" placeholder="Ví dụ: Đồ uống" value="<?php echo e($form['name']); ?>" required></label>
            <p class="form-help">Danh mục sẽ xuất hiện tại thực đơn để nhân viên chọn món.</p>
            <div class="form-actions">
                <button class="button primary" name="action" value="save"><?php echo $editId ? 'Lưu thay đổi' : 'Thêm danh mục'; ?></button>
                <?php if ($editId): ?><a class="button" href="admin.php?page=categories">Hủy sửa</a><?php endif; ?>
            </div>
        </form>
    </section>
</div>
