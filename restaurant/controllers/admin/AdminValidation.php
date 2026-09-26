<?php

// Controller kiểm tra dữ liệu; model chỉ nhận dữ liệu đã hợp lệ.
function adminText($value, $label, $max)
{
    if (!is_string($value)) {
        throw new DomainException($label . ' không hợp lệ.');
    }
    $value = trim($value);
    if ($value === '' || mb_strlen($value, 'UTF-8') > $max) {
        throw new DomainException($label . ' phải có từ 1 đến ' . $max . ' ký tự.');
    }
    return $value;
}

function adminPrice($value)
{
    // DECIMAL(10,2): tối đa 8 chữ số trước dấu chấm và 2 số lẻ.
    if (!is_string($value) || !preg_match('/^\d{1,8}(\.\d{1,2})?$/D', $value)) {
        throw new DomainException('Giá phải từ 0 đến 99.999.999,99, tối đa 2 chữ số lẻ.');
    }
    return $value;
}

function adminAction()
{
    $action = $_POST['action'] ?? '';
    if (!is_string($action) || !in_array($action, array('save', 'delete', 'status'), true)) {
        throw new DomainException('Thao tác không hợp lệ.');
    }
    return $action;
}

function adminEditId()
{
    return isset($_GET['edit']) ? soNguyenDuong($_GET['edit']) : null;
}

function adminSaleStatus($value)
{
    if (!in_array($value, array('Đang bán', 'Ngừng bán'), true)) {
        throw new DomainException('Trạng thái bán không hợp lệ.');
    }
    return $value;
}
