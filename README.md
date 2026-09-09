# HỒNG RESTAURANT — HỆ THỐNG NHÂN VIÊN

## Cài bản này vào XAMPP

1. Sao lưu thư mục restaurant hiện tại nếu muốn giữ bản cũ.
2. Chép toàn bộ nội dung của file ZIP vào C:\xampp\htdocs\restaurant, chọn thay thế các file trùng tên.
3. Bật Apache và MySQL trong XAMPP.
4. Mở http://localhost/phpmyadmin/, chọn database restaurant, vào Import và chọn file database/upgrade.sql của bản này.
5. Mở http://localhost/restaurant/ rồi đăng nhập bằng tài khoản nhân viên hiện có (username nhanvien) và mật khẩu của bạn.

Chỉ dùng bản ZIP này, không cần chép thêm những bản ZIP trước.
Script upgrade.sql chỉ thêm 4 bảng nghiệp vụ, không xóa món, danh mục, bàn, tài khoản hay đơn hàng hiện có. Script có thể chạy lại. Bản này dành cho CSDL restaurant đã kiểm tra trên máy: 10 bàn, 15 món và chưa có đơn hàng trong MySQL. Order của giao diện cũ trong localStorage không được nhập vào MySQL.

## Luồng sử dụng đúng sơ đồ

1. Khách đến: vào Quản lý bàn, chọn bàn Trống, bấm Xếp khách vào bàn.
2. Gọi món: chọn số lượng, tăng/giảm hoặc xóa về 0. Lọc danh mục và tìm món nếu cần.
3. Bấm Kiểm tra & xác nhận. Màn hình kế tiếp cho kiểm món, số lượng và tổng; có thể quay lại sửa.
4. Xác nhận món tạo một phiếu riêng. Giá và tên món lấy từ MySQL, lưu lại tại thời điểm xác nhận.
5. In phiếu; sau khi in thành công, tích xác nhận và bấm Đã in phiếu · Chuyển bếp.
6. Bếp nhận phiếu, bắt đầu nấu; khi hoàn tất, bấm Nấu xong · Giao món và phiếu.
7. Nhân viên vào Theo dõi phục vụ, đối chiếu tất cả món và tích đủ từng dòng, bấm Đã kiểm đủ tất cả món.
8. Sau khi mang món đến khách, bấm Đã mang món phục vụ khách.
9. Nếu khách gọi thêm: bấm Gọi món / Gọi bổ sung. Tạo phiếu mới, lặp lại quy trình in → bếp → kiểm → phục vụ.
10. Nếu khách thanh toán: khi tất cả phiếu đã phục vụ, bấm Khách yêu cầu thanh toán.
11. Chọn phương thức tiền mặt/chuyển khoản/thẻ, kiểm tra đã nhận đủ tiền rồi xác nhận thanh toán.
12. In hóa đơn. Sau khi in thành công, tích xác nhận và bấm Kết thúc · Trả bàn về trống.

Nếu khách gọi thêm khi bàn đang chờ thanh toán, chọn Tiếp tục gọi món trước. Nếu chưa gọi món nào mà khách đổi ý, có thể trả bàn bằng nút Khách chưa gọi món · Trả bàn.

## Các trạng thái

Phiếu: Chờ in → Chờ bếp → Đang nấu → Chờ kiểm món → Đã kiểm đủ → Đã phục vụ.
Bàn: Trống → Có khách → Chờ thanh toán → Chờ in hóa đơn → Trống.
Order: Đang phục vụ → Chờ thanh toán → Đã thanh toán. Bàn chưa gọi món được đóng với order Đã hủy.

Hủy hộp thoại in không tự đánh dấu đã in. Khi lỗi máy in, phiếu/hóa đơn vẫn còn để in lại. In lại hóa đơn cũ không làm thay đổi lượt phục vụ của khách mới.

## Cách đọc code

- index.php: khởi tạo phiên đăng nhập, nhận yêu cầu, chọn view.
- config/database.php: kết nối MySQLi giống cách tổ chức trong MVC1.
- config/helpers.php: định dạng tiền, bảo vệ HTML, kiểm tra form và chuyển trang.
- models/Restaurant.php: các câu SQL và quy tắc nghiệp vụ; mỗi chức năng là một hàm riêng.
- controllers/auth/AuthController.php: kiểm tra tài khoản của bảng users.
- controllers/staff/OrderController.php: nhận form POST rồi gọi hàm model tương ứng.
- views/auth và views/staff: HTML/PHP từng màn hình, dùng foreach/if.
- public/js/script.js: chỉ hỗ trợ nút tăng/giảm/xóa, lọc món, tổng tạm tính và mở hộp thoại in. Không lưu dữ liệu nghiệp vụ trên trình duyệt.
- public/css/style.css: định dạng giao diện và trang in.
- database/upgrade.sql: bảng lượt phục vụ, phiếu bếp, món theo phiếu, bản lưu hóa đơn.

Dữ liệu được ghi trong transaction: các bước cùng thành công hoặc cùng hủy. Trước khi thay đổi order, chương trình khóa dòng bàn để hai nhân viên không ghi đè nhau. Truy vấn có tham số để không ghép dữ liệu nhập vào SQL. Các phần này được gom trong model, có chú thích.

## Tài khoản và dữ liệu

Dùng tài khoản đang có trong bảng users, vai trò nhanvien, staff hoặc admin. Không tạo tài khoản thật mới. Với mật khẩu cũ đang lưu dạng thường, sau một lần đăng nhập đúng chương trình tự chuyển sang password_hash; mật khẩu bạn nhập vẫn giữ nguyên.

Bếp và phục vụ thuộc phạm vi tài khoản nhân viên theo sơ đồ đã yêu cầu. Không xây thêm chức năng quản trị nhân sự hoặc thực đơn. Chuyển khoản/thẻ là ghi nhận phương thức sau khi nhân viên kiểm tra nhận tiền; chưa kết nối ngân hàng hoặc máy POS.

Tên và giá món trên hóa đơn được lưu riêng, nên sửa tên/giá thực đơn sau này không làm đổi hóa đơn cũ. Các màn hình đọc lại MySQL khi mở hoặc bấm Làm mới, không tự làm mới form đang nhập dở.

## Kiểm tra đã thực hiện

61 trường hợp đã đạt trên CSDL thử riêng:
- 32 kiểm tra model: dữ liệu thật, số lượng sai, món ngừng bán, chuyển trạng thái, order bổ sung, giá/tổng, in hóa đơn, trả bàn và hóa đơn cũ.
- 27 kiểm tra qua HTTP/form: đăng nhập, CSRF, xếp bàn, kiểm tra/sửa món, tạo/in phiếu, bếp, kiểm món, phục vụ, thanh toán và kết thúc.
- 2 kiểm tra với hai tiến trình đồng thời: mở cùng bàn và thanh toán cùng order.

Đã kiểm tra cú pháp PHP 8.0.30 của XAMPP và JavaScript. Chưa kiểm tra máy in vật lý; nút In mở hộp thoại in của trình duyệt. Không tạo đơn thử hay đổi tài khoản trong CSDL restaurant thật.

## Cập nhật modal và ghi chú món

Chép nội dung bản ZIP này thay cho code cũ và chạy lại database/upgrade.sql trong CSDL restaurant. Script thêm cột order_details.note (tối đa 300 ký tự), giữ nguyên dữ liệu đang có.

Bấm Chọn món / Sửa yêu cầu để mở modal, nhập số lượng và ghi chú rồi bấm Lưu món vào phiếu. Bấm Hủy hoặc Esc không lưu thay đổi. Nút Sửa trong phiếu đang chọn mở lại modal với dữ liệu đã nhập; nút Xóa bỏ món cùng ghi chú. Ghi chú áp dụng cho toàn bộ số lượng của dòng món trong lần gọi này.

Ghi chú được giữ khi kiểm tra/quay lại sửa, lưu theo chi tiết order và hiện trên màn hình bếp, phiếu in, phần kiểm món. Lần gọi bổ sung có thể có ghi chú khác và không làm đổi phiếu trước. Nội dung HTML trong ghi chú được hiển thị như văn bản để bảo vệ giao diện.

Bản cập nhật đã kiểm tra lưu/đọc ghi chú tiếng Việt, dòng mới, giới hạn độ dài, nội dung HTML và phiếu in; chạy lại 29 kiểm tra HTTP/form cùng kiểm thử logic modal (mở/hủy/lưu/sửa/xóa). Chưa thử in trên máy in vật lý.

## Cập nhật thanh danh mục món

Danh mục món được đặt thành sidebar bên trái thực đơn. Mỗi mục hiển thị tên danh mục và số món tương ứng. Danh mục đang chọn có nền xanh. Ô tìm kiếm chỉ tìm trong danh mục đang chọn.

Trên màn hình điện thoại, thanh danh mục chuyển thành một hàng ngang có thể vuốt để không làm phần món ăn bị quá hẹp. Phiếu order vẫn nằm bên phải trên máy tính và chuyển xuống dưới thực đơn trên màn hình nhỏ.

## Cập nhật bố cục chuyên nghiệp

Tăng khoảng cách giữa các cột và padding bên trong toàn bộ thẻ, panel, bảng, khu bếp, thanh toán, hóa đơn và modal. Trang gọi món có khoảng cách lớn hơn giữa danh mục, danh sách món và phiếu order. Các màn hình nhỏ tự giảm padding và chuyển các cột thành một cột để không xuất hiện cuộn ngang.

## Sửa cột món đang chọn

Bố cục gọi món được tách sang public/css/order-layout.css và nạp sau style.css để các media query cũ không ghi đè kích thước mới. Trên màn hình lớn, thanh danh mục rộng 240px, phiếu món đang chọn rộng 520px và thực đơn hiển thị 2 cột. Phiếu có chiều cao tối thiểu 650px; riêng danh sách món đã chọn cuộn dọc nên phần tổng tiền và nút xác nhận luôn nhìn thấy.

## Cập nhật sidebar và phiếu món đang chọn

Sidebar điều hướng chính rộng tối đa 292px; sidebar danh mục rộng 230px trên màn hình lớn. Danh sách món hiển thị hai cột để tên món và nút thao tác không bị ép. Phiếu order rộng 440px và danh sách món đã chọn có vùng cuộn riêng. Mỗi món trong phiếu tách rõ tên, thành tiền, ghi chú và nút sửa/xóa.
