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

// Mỗi bảng tổng quan hiển thị 5 dòng, chuyển trang riêng không tải lại dữ liệu.
for (const table of document.querySelectorAll('.admin-dashboard .dashboard-orders-table')) {
    const rows = Array.from(table.querySelectorAll('tbody > tr'));
    if (rows.length <= 5) continue;
    let page = 0;
    const pageCount = Math.ceil(rows.length / 5);
    const controls = document.createElement('nav');
    controls.className = 'dashboard-pagination';
    const heading = table.closest('section').querySelector('h2').textContent;
    controls.setAttribute('aria-label', 'Chuyển trang ' + heading);
    const status = document.createElement('span');
    status.setAttribute('aria-live', 'polite');
    const previous = document.createElement('button');
    previous.type = 'button';
    previous.className = 'pagination-up';
    previous.title = 'Xem 5 dòng trước';
    previous.setAttribute('aria-label', previous.title);

    const next = document.createElement('button');
    next.type = 'button';
    next.className = 'pagination-down';
    next.title = 'Xem 5 dòng tiếp theo';
    next.setAttribute('aria-label', next.title);
    controls.append(status, previous, next);
    table.closest('.table-scroll').after(controls);
    function showPage() {
        rows.forEach(function (row, index) {
            row.hidden = index < page * 5 || index >= (page + 1) * 5;
        });
        status.textContent = (page * 5 + 1) + '–' + Math.min((page + 1) * 5, rows.length)
            + ' / ' + rows.length + ' · Trang ' + (page + 1) + '/' + pageCount;
        previous.disabled = page === 0;
        next.disabled = page === pageCount - 1;
    }
    // Chuyển trang bảng nào thì đưa phần đầu của bảng đó vào màn hình.
    function scrollToSection() {
        const section = table.closest('section');


        section.scrollIntoView({
            behavior: 'instant',
            block: 'start'
        });
    }

    previous.addEventListener('click', function () {
        if (page > 0) {
            page--;
            showPage();
            scrollToSection();
        }
    });

    next.addEventListener('click', function () {
        if (page < pageCount - 1) {
            page++;
            showPage();
            scrollToSection();
        }
    });
    showPage();
}