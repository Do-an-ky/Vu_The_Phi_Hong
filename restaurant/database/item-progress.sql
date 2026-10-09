-- Chạy trong CSDL restaurant trước khi cập nhật các file PHP.
-- Theo dõi từng dòng món trên phiếu, không thay đổi giá hoặc số lượng.
ALTER TABLE kitchen_ticket_items
    ADD COLUMN IF NOT EXISTS item_ready_at DATETIME NULL COMMENT 'Bếp xác nhận đã nấu xong dòng món',
    ADD COLUMN IF NOT EXISTS item_served_at DATETIME NULL COMMENT 'Nhân viên xác nhận đã mang đủ dòng món ra bàn';

-- Giữ đúng trạng thái các phiếu cũ đã hoàn thành trước khi nâng cấp.
UPDATE kitchen_ticket_items i
JOIN kitchen_tickets t ON t.id = i.ticket_id
SET i.item_ready_at = COALESCE(t.ready_at, t.created_at)
WHERE i.item_ready_at IS NULL
  AND t.status IN ('Chờ kiểm món', 'Đã kiểm đủ', 'Đã phục vụ');

UPDATE kitchen_ticket_items i
JOIN kitchen_tickets t ON t.id = i.ticket_id
SET i.item_served_at = COALESCE(t.served_at, t.created_at)
WHERE i.item_served_at IS NULL AND t.status = 'Đã phục vụ';
