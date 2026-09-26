"use strict";

// Đổi ngày thì gửi form GET để PHP tính lại thống kê từ CSDL.
const dashboardDate = document.getElementById('dashboard-date');
if (dashboardDate) {
    dashboardDate.addEventListener('change', function () {
        if (dashboardDate.value !== '' && dashboardDate.validity.valid) {
            dashboardDate.form.requestSubmit();
        }
    });
}

// Hỏi lại trước khi xóa. Quyền và điều kiện xóa vẫn do PHP kiểm tra.
document.addEventListener('submit', function (event) {
    const message = event.target.dataset.confirm;
    if (message && !window.confirm(message)) {
        event.preventDefault();
    }
});
