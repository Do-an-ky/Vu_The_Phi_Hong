<div class="management-grid">
    <section class="panel">
        <div class="panel-heading"><h2>Danh sách bàn</h2><span class="count"><?php echo count($rows); ?> bàn</span></div>
        <div class="table-scroll">
            <table>
                <thead><tr><th>Mã</th><th>Tên bàn</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td>#<?php echo e($row['id']); ?></td>
                            <td><strong><?php echo e($row['name']); ?></strong></td>
                            <td><span class="badge <?php echo $row['status'] === 'Trống' ? 'available' : ''; ?>"><?php echo e($row['status']); ?></span></td>
                            <td>
                                <div class="row-actions">
                                    <a class="button small" href="admin.php?page=tables&edit=<?php echo e($row['id']); ?>#editor">Sửa</a>
                                    <form method="post" action="admin.php?page=tables" data-confirm="Xóa bàn này?">
                                        <?php csrfInput(); ?>
                                        <input type="hidden" name="id" value="<?php echo e($row['id']); ?>">
                                        <button class="button small danger" name="action" value="delete">Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$rows): ?><tr><td colspan="4" class="empty">Chưa có bàn. Thêm bàn để bắt đầu phục vụ.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    <section class="panel editor" id="editor">
        <p class="eyebrow">KHÔNG GIAN NHÀ HÀNG</p>
        <h2><?php echo $editId ? 'Sửa bàn' : 'Thêm bàn'; ?></h2>
        <form method="post" class="editor-form">
            <?php csrfInput(); ?>
            <input type="hidden" name="id" value="<?php echo e($form['id']); ?>">
            <label>Tên bàn<input name="name" maxlength="50" placeholder="Ví dụ: Bàn 01" value="<?php echo e($form['name']); ?>" required></label>
            <p class="form-help">Bàn mới có trạng thái Trống. Trạng thái sử dụng được cập nhật theo hoạt động phục vụ.</p>
            <div class="form-actions">
                <button class="button primary" name="action" value="save"><?php echo $editId ? 'Lưu thay đổi' : 'Thêm bàn'; ?></button>
                <?php if ($editId): ?><a class="button" href="admin.php?page=tables">Hủy sửa</a><?php endif; ?>
            </div>
        </form>
    </section>
</div>
