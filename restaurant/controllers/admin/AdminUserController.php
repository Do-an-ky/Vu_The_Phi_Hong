<?php

function xuLyAdminUser($model)
{
    $action = adminAction();
    if ($action === 'delete') {
        $model->deleteUser(soNguyenDuong($_POST['id'] ?? null));
        return;
    }
    if ($action !== 'save') {
        throw new DomainException('Thao tác nhân viên không hợp lệ.');
    }
    $id = ($_POST['id'] ?? '') === '' ? null : soNguyenDuong($_POST['id']);
    $name = adminText($_POST['name'] ?? '', 'Họ tên', 100);
    $username = adminText($_POST['username'] ?? '', 'Tên đăng nhập', 50);
    if (!preg_match('/^[a-zA-Z0-9_.-]+$/D', $username)) {
        throw new DomainException('Tên đăng nhập chỉ dùng chữ không dấu, số, dấu chấm, gạch dưới hoặc gạch ngang.');
    }
    $password = $_POST['password'] ?? '';
    if (!is_string($password) || strpos($password, "\0") !== false ||
        ((!$id || $password !== '') && (strlen($password) < 8 || strlen($password) > 72))) {
        throw new DomainException('Mật khẩu mới cần từ 8 đến 72 byte. Để trống khi sửa nếu muốn giữ mật khẩu cũ.');
    }
    // Vai trò luôn là nhân viên; không nhận role từ form.
    $model->saveUser($id, $name, $username, $password);
}
