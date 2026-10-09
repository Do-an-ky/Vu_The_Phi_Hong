"use strict";
const paymentWorkspace = document.getElementById('payment-workspace');
const invoicePreview = document.getElementById('invoice-preview');
const invoiceFrame = document.getElementById('invoice-preview-frame');
const invoiceStatus = document.getElementById('invoice-preview-status');
const invoicePrint = document.getElementById('print-invoice-preview');
let invoiceTrigger = null;
document.addEventListener('click', function (event) {
    const link = event.target.closest('a[href]');
    if (!link || !paymentWorkspace.contains(link) || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
    const url = new URL(link.href, window.location.href);
    if (url.origin !== location.origin || url.searchParams.get('page') !== 'invoice') return;
    event.preventDefault();
    invoiceTrigger?.removeAttribute('aria-current');
    invoiceTrigger = link;
    link.setAttribute('aria-current', 'true');
    url.searchParams.set('embed', '1');
    document.getElementById('invoice-preview-title').textContent = 'Hóa đơn #' + url.searchParams.get('id');
    invoicePreview.hidden = false;
    paymentWorkspace.classList.add('has-preview');

    invoiceStatus.hidden = false;
    invoiceStatus.textContent = 'Đang tải hóa đơn…';
    invoiceFrame.hidden = true;
    invoicePrint.disabled = true;
    invoiceFrame.src = url.href;
    document.getElementById('close-invoice-preview').focus({preventScroll: true});
    if (window.innerWidth <= 1100) invoicePreview.scrollIntoView({behavior: 'smooth', block: 'start'});
});
invoiceFrame.addEventListener('load', function () {
    if (!invoiceTrigger) return;
    const invoice = invoiceFrame.contentDocument?.querySelector('.invoice');
    if (!invoice) {
        invoiceStatus.textContent = 'Không tải được hóa đơn. Hãy kiểm tra đăng nhập và chọn lại hóa đơn.';
        return;
    }
    invoiceStatus.hidden = true;
    invoiceFrame.hidden = false;
    invoicePrint.disabled = false;
});
document.getElementById('close-invoice-preview').addEventListener('click', function () {
    invoicePreview.hidden = true;
    paymentWorkspace.classList.remove('has-preview');
    invoiceFrame.hidden = true;
    invoicePrint.disabled = true;
    invoiceStatus.hidden = false;
    invoiceStatus.textContent = 'Chọn “Xem hóa đơn” trong danh sách bên trái để xem chi tiết tại đây.';
    document.getElementById('invoice-preview-title').textContent = 'Hóa đơn';
    invoiceTrigger?.removeAttribute('aria-current');
    invoiceTrigger?.focus({preventScroll: true});
    invoiceTrigger = null;
});
invoicePrint.addEventListener('click', function () {
    if (!invoicePrint.disabled) {
        invoiceFrame.contentWindow.focus();
        invoiceFrame.contentWindow.print();
    }
});

// Lịch sử thanh toán hiển thị 5 hóa đơn mỗi trang.
const receiptHistory = document.querySelector('.payment-history');
const receiptList = receiptHistory?.querySelector('.receipt-list');
const receiptRows = Array.from(receiptList?.querySelectorAll('.receipt-link') || []);

if (receiptRows.length > 5) {
    let receiptPage = 0;
    const pageSize = 5;
    const pageCount = Math.ceil(receiptRows.length / pageSize);
    const controls = document.createElement('nav');
    controls.className = 'receipt-pagination';
    controls.setAttribute('aria-label', 'Chuyển trang lịch sử thanh toán');

    const status = document.createElement('span');
    status.setAttribute('aria-live', 'polite');

    const previous = document.createElement('button');
    previous.type = 'button';
    previous.className = 'pagination-up';
    previous.title = 'Xem 5 hóa đơn trước';
    previous.setAttribute('aria-label', previous.title);

    const next = document.createElement('button');
    next.type = 'button';
    next.className = 'pagination-down';
    next.title = 'Xem 5 hóa đơn tiếp theo';
    next.setAttribute('aria-label', next.title);

    controls.append(status, previous, next);
    receiptList.after(controls);

    function showReceipts() {
        receiptRows.forEach(function (row, index) {
            row.hidden = index < receiptPage * pageSize || index >= (receiptPage + 1) * pageSize;
        });

        status.textContent = (receiptPage * pageSize + 1) + '–'
            + Math.min((receiptPage + 1) * pageSize, receiptRows.length)
            + ' / ' + receiptRows.length + ' · Trang ' + (receiptPage + 1) + '/' + pageCount;
        previous.disabled = receiptPage === 0;
        next.disabled = receiptPage === pageCount - 1;
    }

    function scrollToReceipts() {

        receiptHistory.scrollIntoView({
            behavior: 'instant',
            block: 'start'
        });
    }

    previous.addEventListener('click', function () {
        if (receiptPage > 0) {
            receiptPage--;
            showReceipts();
            scrollToReceipts();
        }
    });

    next.addEventListener('click', function () {
        if (receiptPage < pageCount - 1) {
            receiptPage++;
            showReceipts();
            scrollToReceipts();
        }
    });

    showReceipts();
}