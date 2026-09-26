<?php

function duLieuAdminDashboard($model)
{
    $date = $_GET['date'] ?? date('Y-m-d');
    if (!is_string($date) || !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D', $date)) {
        throw new DomainException('Ngày xem thống kê không hợp lệ.');
    }
    $day = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    if (!$day || $day->format('Y-m-d') !== $date || (int) $day->format('Y') < 1000) {
        throw new DomainException('Ngày xem thống kê không tồn tại. Hãy chọn lại ngày.');
    }
    return $model->dashboard($date);
}
