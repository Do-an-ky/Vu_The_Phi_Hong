-- Chạy một lần trong database restaurant bằng phpMyAdmin.
-- Không xóa hay sửa các món, bàn, tài khoản hoặc đơn hàng hiện có.

CREATE TABLE IF NOT EXISTS service_sessions (
    table_id INT PRIMARY KEY,
    order_id INT NOT NULL UNIQUE,
    FOREIGN KEY (table_id) REFERENCES tables(id),
    FOREIGN KEY (order_id) REFERENCES orders(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ghi chú riêng cho mỗi dòng món, không sửa ghi chú trên thực đơn.
ALTER TABLE order_details
    ADD COLUMN IF NOT EXISTS note VARCHAR(300) NOT NULL DEFAULT ''
    COMMENT 'Yêu cầu của khách cho món ở lần gọi này';

CREATE TABLE IF NOT EXISTS kitchen_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    request_key VARCHAR(64) NOT NULL UNIQUE,
    status VARCHAR(30) NOT NULL DEFAULT 'Chờ in',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    printed_at DATETIME NULL,
    accepted_at DATETIME NULL,
    ready_at DATETIME NULL,
    checked_at DATETIME NULL,
    served_at DATETIME NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    INDEX (order_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS kitchen_ticket_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    detail_id INT NOT NULL UNIQUE,
    product_name VARCHAR(100) NOT NULL,
    FOREIGN KEY (ticket_id) REFERENCES kitchen_tickets(id),
    FOREIGN KEY (detail_id) REFERENCES order_details(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payment_receipts (
    payment_id INT PRIMARY KEY,
    order_id INT NOT NULL UNIQUE,
    table_name VARCHAR(50) NOT NULL,
    staff_name VARCHAR(100) NOT NULL,
    items_json LONGTEXT NOT NULL,
    FOREIGN KEY (payment_id) REFERENCES payments(id),
    FOREIGN KEY (order_id) REFERENCES orders(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
