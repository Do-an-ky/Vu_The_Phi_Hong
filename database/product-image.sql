-- Đường dẫn ảnh tương đối, file ảnh nằm trong thư mục image.
ALTER TABLE products ADD COLUMN IF NOT EXISTS image VARCHAR(255) NULL;