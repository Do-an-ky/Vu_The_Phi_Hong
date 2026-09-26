<?php

// Luôn bảo vệ văn bản khi đưa dữ liệu từ CSDL vào HTML.
function e($text)
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

function tien($amount)
{
    $digits = 0;
    if ((float) $amount != floor((float) $amount)) {
        $digits = 2;
    }
    return number_format((float) $amount, $digits, ',', '.') . ' đ';
}

function chuyenTrang($url)
{
    header('Location: ' . $url, true, 303);
    exit;
}

function csrfInput()
{
    echo '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">';
}

function kiemTraCsrf()
{
    if (!isset($_POST['csrf']) || !is_string($_POST['csrf']) ||
        !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        throw new DomainException('Phiên thao tác đã hết hạn. Hãy tải lại trang.');
    }
}

function soNguyenDuong($value)
{
    $number = filter_var($value, FILTER_VALIDATE_INT);
    if ($number === false || $number <= 0) {
        throw new DomainException('Mã hoặc số lượng không hợp lệ.');
    }
    return $number;
}

function ghiChuMon($value)
{
    if (!is_string($value) || mb_strlen($value, 'UTF-8') > 300) {
        throw new DomainException('Ghi chú món tối đa 300 ký tự.');
    }
    return trim($value);
}
