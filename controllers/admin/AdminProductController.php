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
    // Không nhận đường dẫn ảnh từ form; giữ ảnh cũ nếu không chọn ảnh mới.
    $current = $id ? $model->one('SELECT image FROM products WHERE id = ? FOR UPDATE', array($id)) : null;
    $image = $current['image'] ?? null;
    $uploadedImage = luuAnhSanPham($_FILES['image'] ?? null);
    if ($uploadedImage !== null) {
        $image = $uploadedImage;
    }
    try {
        $model->saveProduct($id, $categoryId, $name, $price, $status, $image);
    } catch (Throwable $error) {
        if ($uploadedImage !== null) {
            unlink(__DIR__ . '/../../' . $uploadedImage);
        }
        throw $error;
    }
}

// Chỉ cho phép ảnh JPG, PNG, WebP tối đa 5 MB; tên file do server tạo.
function luuAnhSanPham($file)
{
    if ($file === null) {
        return null;
    }
    if (!is_array($file) || !isset($file['error']) || !is_int($file['error'])) {
        throw new DomainException('Ảnh tải lên không hợp lệ.');
    }
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new DomainException('Không tải được ảnh. Hãy chọn ảnh nhỏ hơn 5 MB.');
    }
    $temp = $file['tmp_name'] ?? '';
    if (!is_string($temp) || !is_uploaded_file($temp) || filesize($temp) > 5 * 1024 * 1024) {
        throw new DomainException('Ảnh phải nhỏ hơn hoặc bằng 5 MB.');
    }
    $info = @getimagesize($temp);
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temp);
    $types = array('image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp');
    if (!$info || !isset($types[$mime]) || $info['mime'] !== $mime ||
        $info[0] > 8000 || $info[1] > 8000) {
        throw new DomainException('Chỉ nhận ảnh JPG, PNG hoặc WebP, tối đa 8000 × 8000 pixel.');
    }
    $directory = __DIR__ . '/../../image/uploads';
    if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
        throw new DomainException('Không tạo được thư mục lưu ảnh.');
    }
    $path = 'image/uploads/' . bin2hex(random_bytes(16)) . '.' . $types[$mime];
    if (!move_uploaded_file($temp, __DIR__ . '/../../' . $path)) {
        throw new DomainException('Không lưu được ảnh. Vui lòng thử lại.');
    }
    return $path;
}