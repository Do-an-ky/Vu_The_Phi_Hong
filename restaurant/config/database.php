<?php

function ketNoiCSDL()
{
    $servername = '127.0.0.1';
    $username = 'root';
    $password = '';
    $dbname = 'restaurant';

    // Biến môi trường chỉ dùng khi chạy kiểm thử với database riêng.
    if (getenv('RESTAURANT_DB')) {
        $dbname = getenv('RESTAURANT_DB');
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn = mysqli_connect($servername, $username, $password, $dbname);
    mysqli_set_charset($conn, 'utf8mb4');

    return $conn;
}
