<?php

function xuLyAdminCategory($model)
{
    $action = adminAction();
    if ($action === 'delete') {
        $model->deleteCategory(soNguyenDuong($_POST['id'] ?? null));
        return;
    }
    if ($action !== 'save') {
        throw new DomainException('Thao tác danh mục không hợp lệ.');
    }
    $id = ($_POST['id'] ?? '') === '' ? null : soNguyenDuong($_POST['id']);
    $name = adminText($_POST['name'] ?? '', 'Tên danh mục', 100);
    $model->saveCategory($id, $name);
}
