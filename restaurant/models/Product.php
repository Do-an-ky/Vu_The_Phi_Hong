<?php

require_once __DIR__ . '/../config/database.php';

class Product
{
    public function layDanhSachMon()
    {
        $conn = ketNoiCSDL();
        $sql = "SELECT products.id, products.name, products.price,
                       categories.name AS category
                FROM products
                LEFT JOIN categories ON products.category_id = categories.id
                WHERE products.status = 'Đang bán'
                  AND products.price IS NOT NULL
                  AND products.price >= 0
                ORDER BY products.category_id, products.id";

        $query = mysqli_query($conn, $sql);
        $danhSachMon = array();

        while ($mon = mysqli_fetch_assoc($query)) {
            $mon['id'] = (int) $mon['id'];
            $mon['price'] = (float) $mon['price'];

            if ($mon['category'] === null) {
                $mon['category'] = 'Chưa phân loại';
            }

            $danhSachMon[] = $mon;
        }

        mysqli_close($conn);
        return $danhSachMon;
    }
}
