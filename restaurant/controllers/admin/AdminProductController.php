<?php

function xuLyAdminProduct($model)
{
    $action = adminAction();
    if ($action === 'delete') {
        $model->deleteProduct(soNguyenDuong($_POST['id'] ?? null));
        return;
    }
    if ($action === 'status') {
        $model->productStatus(
            soNguyenDuong($_POST['id'] ?? null),
            adminSaleStatus($_POST['status'] ?? '')
        );
        return;
    }
    $id = ($_POST['id'] ?? '') === '' ? null : soNguyenDuong($_POST['id']);
    $categoryId = soNguyenDuong($_POST['category_id'] ?? null);
    $name = adminText($_POST['name'] ?? '', 'Tên sản phẩm', 100);
    $price = adminPrice($_POST['price'] ?? '');
    $status = adminSaleStatus($_POST['status'] ?? '');
    $model->saveProduct($id, $categoryId, $name, $price, $status);
}
