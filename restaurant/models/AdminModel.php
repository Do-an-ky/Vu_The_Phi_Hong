<?php

require_once __DIR__ . '/Restaurant.php';

class AdminModel extends Restaurant
{
    // Các hàm này chạy trong transaction do admin.php mở.
    public function dashboard($date = null)
    {
        $date = $date ?? date('Y-m-d');
        $day = new DateTimeImmutable($date);
        $start = $day->format('Y-m-d') . ' 00:00:00';
        $end = $day->modify('+1 day')->format('Y-m-d') . ' 00:00:00';

        // Doanh thu thuộc ngày tạo order, kể cả khách thanh toán vào ngày hôm sau.
        $invoices = $this->one(
            'SELECT COUNT(*) AS value
             FROM payments p JOIN orders o ON o.id = p.order_id
             WHERE o.created_at >= ? AND o.created_at < ?',
            array($start, $end)
        );
        $revenue = $this->one(
            "SELECT COALESCE(SUM(p.total), 0) AS value,
                    COALESCE(SUM(CASE WHEN p.payment_method = 'Tiền mặt' THEN p.total ELSE 0 END), 0) AS cash,
                    COALESCE(SUM(CASE WHEN p.payment_method = 'Chuyển khoản' THEN p.total ELSE 0 END), 0) AS transfer,
                    COALESCE(SUM(CASE WHEN p.payment_method = 'Thẻ' THEN p.total ELSE 0 END), 0) AS card,
                    COALESCE(SUM(CASE WHEN p.payment_method IS NULL OR p.payment_method NOT IN ('Tiền mặt', 'Chuyển khoản', 'Thẻ') THEN p.total ELSE 0 END), 0) AS other
             FROM payments p JOIN orders o ON o.id = p.order_id
             WHERE o.created_at >= ? AND o.created_at < ?",
            array($start, $end)
        );
        $tables = $this->one('SELECT COUNT(*) AS value FROM service_sessions');
        $dayInvoices = $this->all(
            'SELECT p.id, p.order_id, p.total, p.payment_method,
                    p.created_at AS paid_at, o.created_at AS order_created_at,
                    r.table_name, r.staff_name
             FROM payments p
             JOIN orders o ON o.id = p.order_id
             JOIN payment_receipts r ON r.payment_id = p.id
             WHERE o.created_at >= ? AND o.created_at < ?
             ORDER BY o.created_at DESC, p.id DESC',
            array($start, $end)
        );
        return array(
            'invoices' => $invoices['value'],
            'revenue' => $revenue['value'],
            'cash' => $revenue['cash'],
            'transfer' => $revenue['transfer'],
            'card' => $revenue['card'],
            'other' => $revenue['other'],
            'date' => $date,
            'tables' => $tables['value'],
            'day_invoices' => $dayInvoices
        );
    }

    public function users()
    {
        return $this->all("SELECT id, name, username, role FROM users WHERE role IN ('staff', 'nhanvien') ORDER BY id DESC");
    }

    public function saveUser($id, $name, $username, $password)
    {
        if ($id) {
            $this->staffUser($id);
        }
        $duplicate = $this->one('SELECT id FROM users WHERE username = ? AND id <> ?', array($username, $id ?? 0));
        if ($duplicate) {
            throw new DomainException('Tên đăng nhập đã được sử dụng.');
        }
        if (!$id) {
            $this->query("INSERT INTO users(name, username, password, role) VALUES (?, ?, ?, 'staff')", array($name, $username, password_hash($password, PASSWORD_DEFAULT)));
            return;
        }
        $this->query('UPDATE users SET name = ?, username = ? WHERE id = ?', array($name, $username, $id));
        if ($password !== '') {
            $this->query('UPDATE users SET password = ? WHERE id = ?', array(password_hash($password, PASSWORD_DEFAULT), $id));
        }
    }

    public function staffUser($id)
    {
        $row = $this->one("SELECT id FROM users WHERE id = ? AND role IN ('staff', 'nhanvien') FOR UPDATE", array($id));
        if (!$row) {
            throw new DomainException('Không tìm thấy nhân viên. Không được sửa tài khoản admin ở mục này.');
        }
    }

    public function deleteUser($id)
    {
        $this->staffUser($id);
        if ($this->one('SELECT id FROM orders WHERE user_id = ? LIMIT 1', array($id))) {
            throw new DomainException('Nhân viên đã có đơn hàng nên không thể xóa để giữ lịch sử.');
        }
        $this->query('DELETE FROM users WHERE id = ?', array($id));
    }

    public function adminTables()
    {
        return $this->all('SELECT * FROM tables ORDER BY id');
    }

    public function freeTable($id)
    {
        $table = $this->lockTable($id);
        if ($table['status'] !== 'Trống' || $this->one('SELECT table_id FROM service_sessions WHERE table_id = ?', array($id))) {
            throw new DomainException('Bàn đang được sử dụng. Chỉ sửa hoặc xóa khi bàn trống.');
        }
    }

    public function saveTable($id, $name)
    {
        if ($id) {
            $this->freeTable($id);
        }
        if ($this->one('SELECT id FROM tables WHERE name = ? AND id <> ?', array($name, $id ?? 0))) {
            throw new DomainException('Tên bàn đã tồn tại.');
        }
        if ($id) {
            $this->query('UPDATE tables SET name = ? WHERE id = ?', array($name, $id));
        } else {
            $this->query("INSERT INTO tables(name, status) VALUES (?, 'Trống')", array($name));
        }
    }

    public function deleteTable($id)
    {
        $this->freeTable($id);
        if ($this->one('SELECT id FROM orders WHERE table_id = ? LIMIT 1', array($id))) {
            throw new DomainException('Bàn đã có lịch sử order nên không thể xóa.');
        }
        $this->query('DELETE FROM tables WHERE id = ?', array($id));
    }

    public function categories()
    {
        return $this->all('SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count FROM categories c ORDER BY c.id');
    }

    public function lockCategory($id)
    {
        if (!$this->one('SELECT id FROM categories WHERE id = ? FOR UPDATE', array($id))) {
            throw new DomainException('Danh mục không tồn tại.');
        }
    }

    public function saveCategory($id, $name)
    {
        if ($id) {
            $this->lockCategory($id);
        }
        if ($this->one('SELECT id FROM categories WHERE name = ? AND id <> ?', array($name, $id ?? 0))) {
            throw new DomainException('Tên danh mục đã tồn tại.');
        }
        if ($id) {
            $this->query('UPDATE categories SET name = ? WHERE id = ?', array($name, $id));
        } else {
            $this->query('INSERT INTO categories(name) VALUES (?)', array($name));
        }
    }

    public function deleteCategory($id)
    {
        $this->lockCategory($id);
        if ($this->one('SELECT id FROM products WHERE category_id = ? LIMIT 1', array($id))) {
            throw new DomainException('Danh mục còn sản phẩm. Hãy chuyển hoặc xóa sản phẩm trước.');
        }
        $this->query('DELETE FROM categories WHERE id = ?', array($id));
    }

    public function adminProducts()
    {
        return $this->all('SELECT p.*, c.name AS category FROM products p LEFT JOIN categories c ON c.id = p.category_id ORDER BY p.id DESC');
    }

    public function lockProduct($id)
    {
        if (!$this->one('SELECT id FROM products WHERE id = ? FOR UPDATE', array($id))) {
            throw new DomainException('Sản phẩm không tồn tại.');
        }
    }

    public function saveProduct($id, $categoryId, $name, $price, $status)
    {
        $this->lockCategory($categoryId);
        if ($id) {
            $this->lockProduct($id);
            $this->query('UPDATE products SET category_id = ?, name = ?, price = ?, status = ? WHERE id = ?', array($categoryId, $name, $price, $status, $id));
        } else {
            $this->query('INSERT INTO products(category_id, name, price, status) VALUES (?, ?, ?, ?)', array($categoryId, $name, $price, $status));
        }
    }

    public function productStatus($id, $status)
    {
        $this->lockProduct($id);
        $this->query('UPDATE products SET status = ? WHERE id = ?', array($status, $id));
    }

    public function deleteProduct($id)
    {
        $this->lockProduct($id);
        if ($this->one('SELECT id FROM order_details WHERE product_id = ? LIMIT 1', array($id))) {
            throw new DomainException('Sản phẩm đã có trong order. Hãy chuyển sang Ngừng bán để giữ lịch sử.');
        }
        $this->query('DELETE FROM products WHERE id = ?', array($id));
    }
}
