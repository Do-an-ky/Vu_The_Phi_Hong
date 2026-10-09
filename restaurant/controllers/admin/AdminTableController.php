<?php

function xuLyAdminTable($model)
{
    $action = adminAction();
    if ($action === 'delete') {
        $model->deleteTable(soNguyenDuong($_POST['id'] ?? null));
        return;
    }
    if ($action !== 'save') {
        throw new DomainException('Thao tác bàn không hợp lệ.');
    }
    $id = ($_POST['id'] ?? '') === '' ? null : soNguyenDuong($_POST['id']);
    $name = adminText($_POST['name'] ?? '', 'Tên bàn', 50);
    // Trạng thái bàn do luồng phục vụ quyết định, không sửa tay ở admin.
    $model->saveTable($id, $name);
}
