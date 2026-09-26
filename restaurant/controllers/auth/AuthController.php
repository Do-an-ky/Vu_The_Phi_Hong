<?php

// Hỗ trợ tài khoản hiện tại. Mật khẩu cũ dạng thường được băm sau khi đăng nhập đúng.
function dangNhap($model, $username, $password)
{
    if (!is_string($username) || !is_string($password)) {
        throw new DomainException('Thông tin đăng nhập không hợp lệ.');
    }
    $user = $model->one('SELECT * FROM users WHERE username = ?', array(trim($username)));
    $valid = false;
    if ($user && is_string($user['password'])) {
        $info = password_get_info($user['password']);
        if ($info['algoName'] === 'unknown') {
            $valid = hash_equals($user['password'], $password);
        } else {
            $valid = password_verify($password, $user['password']);
        }
    }
    if (!$valid || !in_array($user['role'], array('nhanvien', 'staff', 'admin'), true)) {
        throw new DomainException('Sai tài khoản, mật khẩu hoặc tài khoản không có quyền nhân viên.');
    }
    if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
        $model->query('UPDATE users SET password = ? WHERE id = ?', array(password_hash($password, PASSWORD_DEFAULT), $user['id']));
    }
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
