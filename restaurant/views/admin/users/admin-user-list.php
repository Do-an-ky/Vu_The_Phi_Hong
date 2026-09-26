<div class="management-grid">
    <section class="panel">
        <div class="panel-heading"><h2>Danh sách nhân viên</h2><span class="count"><?php echo count($rows); ?> người</span></div>
        <div class="table-scroll">
            <table>
                <thead><tr><th>Mã</th><th>Họ tên</th><th>Tên đăng nhập</th><th>Thao tác</th></tr></thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td>#<?php echo e($row['id']); ?></td>
                            <td><strong><?php echo e($row['name']); ?></strong></td>
                            <td><?php echo e($row['username']); ?></td>
                            <td>
                                <div class="row-actions">
                                    <a class="button small" href="admin.php?page=users&edit=<?php echo e($row['id']); ?>#editor">Sửa</a>
                                    <form method="post" action="admin.php?page=users" data-confirm="Xóa nhân viên này?">
                                        <?php csrfInput(); ?>
                                        <input type="hidden" name="id" value="<?php echo e($row['id']); ?>">
                                        <button class="button small danger" name="action" value="delete">Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$rows): ?><tr><td colspan="4" class="empty">Chưa có nhân viên. Thêm nhân viên bằng form bên cạnh.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    <section class="panel editor" id="editor">
        <p class="eyebrow">TÀI KHOẢN NHÂN VIÊN</p>
        <h2><?php echo $editId ? 'Sửa nhân viên' : 'Thêm nhân viên'; ?></h2>
        <form method="post" class="editor-form">
            <?php csrfInput(); ?>
            <input type="hidden" name="id" value="<?php echo e($form['id']); ?>">
            <label>Họ tên<input name="name" maxlength="100" value="<?php echo e($form['name']); ?>" required></label>
            <label>Tên đăng nhập<input name="username" maxlength="50" autocomplete="off" value="<?php echo e($form['username']); ?>" required></label>
            <label><?php echo $editId ? 'Mật khẩu mới (không bắt buộc)' : 'Mật khẩu'; ?>
                <input name="password" type="password" autocomplete="new-password" minlength="8" maxlength="72" <?php if (!$editId) echo 'required'; ?>>
            </label>
            <p class="form-help"><?php echo $editId ? 'Để trống mật khẩu nếu không muốn thay đổi.' : 'Dùng ít nhất 8 ký tự. Tài khoản được cấp quyền nhân viên.'; ?></p>
            <div class="form-actions">
                <button class="button primary" name="action" value="save"><?php echo $editId ? 'Lưu thay đổi' : 'Thêm nhân viên'; ?></button>
                <?php if ($editId): ?><a class="button" href="admin.php?page=users">Hủy sửa</a><?php endif; ?>
            </div>
        </form>
    </section>
</div>
