"use strict";

// Chỉ hỏi khi rời phiếu chưa gửi bếp qua liên kết ở sidebar.
if (document.getElementById("unsent-ticket-warning")) {
    document.addEventListener("click", function (event) {
        const link = event.target.closest(".sidebar a[href]");
        if (!link || event.defaultPrevented || event.button !== 0 ||
            event.ctrlKey || event.metaKey || event.shiftKey || event.altKey ||
            link.target === "_blank" || link.hasAttribute("download")) {
            return;
        }

        const url = new URL(link.href, window.location.href);
        if (url.pathname === location.pathname && url.search === location.search) {
            return;
        }

        const message = "Phiếu chưa được chuyển xuống bếp nên các món này chưa được tính là đã gọi. Bạn có muốn rời trang không?";
        if (!window.confirm(message)) {
            event.preventDefault();
        }
    });
}