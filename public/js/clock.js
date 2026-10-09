"use strict";
// Chỉ cập nhật đồng hồ; không tải lại trang hoặc ảnh hưởng form đang nhập.
(function () {
    const clocks = document.querySelectorAll('[data-live-clock]');
    if (!clocks.length) return;
    const formatter = new Intl.DateTimeFormat('en-GB', {
        timeZone: 'Asia/Ho_Chi_Minh',
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit', hourCycle: 'h23'
    });
    function updateClock() {
        const now = new Date();
        const parts = {};
        for (const part of formatter.formatToParts(now)) {
            parts[part.type] = part.value;
        }
        for (const clock of clocks) {
            clock.textContent = parts.hour + ':' + parts.minute + ' · ' + parts.day + '/' + parts.month + '/' + parts.year;
            clock.dateTime = now.toISOString();
        }
    }
    updateClock();
    setInterval(updateClock, 1000);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) updateClock();
    });
})();