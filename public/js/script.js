"use strict";

// JavaScript chỉ giúp nhập món nhanh. PHP/MySQL xử lý và lưu mọi nghiệp vụ.
// Không dùng localStorage để lưu bàn, món, order hoặc thanh toán.

function dinhDangTien(value) {
    return new Intl.NumberFormat("vi-VN").format(value) + " đ";
}

function capNhatPhieu() {
    const cards = document.querySelectorAll(".menu-item");
    const summary = document.getElementById("cart-summary");
    let total = 0;
    let count = 0;

    if (!summary) {
        return;
    }
    summary.replaceChildren();

    for (let i = 0; i < cards.length; i++) {
        const card = cards[i];
        const input = card.querySelector(".quantity-input");
        const quantity = Number(input.value);
        const note = card.querySelector('.dish-note').value.trim();
        const selection = card.querySelector('.dish-selection');
        selection.textContent = quantity > 0 ? 'Đã chọn: ' + quantity + (note ? ' · ' + note : '') : '';
        if (quantity > 0) {
            const row = document.createElement("div");
            row.className = 'cart-row';

            const head = document.createElement('div');
            head.className = 'cart-item-head';

            const name = document.createElement('p');
            name.className = 'cart-item-name';
            name.textContent = quantity + " × " + card.dataset.name;

            const amount = document.createElement('strong');
            amount.className = 'cart-item-amount';
            amount.textContent = dinhDangTien(quantity * Number(card.dataset.price));

            head.appendChild(name);
            head.appendChild(amount);
            row.appendChild(head);

            if (note !== '') {
                const detail = document.createElement('p');
                detail.className = 'food-note';
                detail.textContent = 'Ghi chú: ' + note;
                row.appendChild(detail);
            }

            const actions = document.createElement('div');
            actions.className = 'cart-item-actions';

            const edit = document.createElement('button');
            edit.type = 'button';
            edit.className = 'button';
            edit.textContent = 'Sửa';
            edit.addEventListener('click', function () { moMon(card); });
            actions.appendChild(edit);

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'button';
            remove.textContent = 'Xóa';
            remove.addEventListener('click', function () {
                input.value = 0;
                card.querySelector('.dish-note').value = '';
                capNhatPhieu();
            });
            actions.appendChild(remove);
            row.appendChild(actions);
            summary.appendChild(row);
            total += quantity * Number(card.dataset.price);
            count++;
        }
    }
    if (count === 0) {
        summary.textContent = "Chưa chọn món nào.";
    }
    document.getElementById("draft-total").textContent = dinhDangTien(total);
    const clearButton = document.getElementById('clear-draft');
    if (clearButton) {
        clearButton.disabled = count === 0;
    }
}

// Chỉ xóa món trong phiếu đang soạn, không xóa các phiếu đã xác nhận.
function lamTrongPhieu() {
    const cards = document.querySelectorAll('#menu-form .menu-item');
    for (let i = 0; i < cards.length; i++) {
        cards[i].querySelector('.quantity-input').value = 0;
        cards[i].querySelector('.dish-note').value = '';
    }
    capNhatPhieu();
}

function locMon() {
    const search = document.getElementById("search").value.toLocaleLowerCase("vi").trim();
    const activeButton = document.querySelector(".category-button.active");
    const category = activeButton ? activeButton.dataset.category : "";
    const cards = document.querySelectorAll(".menu-item");
    let count = 0;

    for (let i = 0; i < cards.length; i++) {
        const card = cards[i];
        const matchName = card.dataset.name.toLocaleLowerCase("vi").includes(search);
        const matchCategory = category === "" || card.dataset.category === category;
        card.hidden = !(matchName && matchCategory);
        if (!card.hidden) {
            count++;
        }
    }
    document.getElementById("no-results").hidden = count > 0;
}

document.addEventListener("click", function (event) {
    const button = event.target.closest("button");
    if (!button) {
        return;
    }
    if (button.hasAttribute("data-print")) {
        window.print();
    }
    if (button.id === 'clear-draft' && !button.disabled) {
        lamTrongPhieu();
    }
    if (button.hasAttribute('data-edit-dish')) {
        moMon(button.closest('.menu-item'));
    }
    if (button.hasAttribute('data-close-dish')) {
        document.getElementById('dish-modal').close();
    }
    if (button.classList.contains('category-button')) {
        const categoryButtons = document.querySelectorAll('.category-button');
        for (let i = 0; i < categoryButtons.length; i++) {
            categoryButtons[i].classList.remove('active');
        }
        button.classList.add('active');
        locMon();
    }
    if (button.hasAttribute("data-step") || button.hasAttribute("data-clear")) {
        const input = button.closest(".menu-item").querySelector(".quantity-input");
        let quantity = Number(input.value);
        if (button.hasAttribute("data-clear")) {
            quantity = 0;
        } else {
            quantity += Number(button.dataset.step);
        }
        input.value = Math.min(99, Math.max(0, quantity));
        capNhatPhieu();
    }
});

const menuForm = document.getElementById("menu-form");
let monDangSua = null;

// Chỉ ghi vào form order sau khi bấm Lưu. Đóng hoặc nhấn Esc sẽ bỏ sửa đổi.
function moMon(card) {
    monDangSua = card;
    document.getElementById('dish-modal-title').textContent = card.dataset.name;
    document.getElementById('dish-modal-quantity').value = Math.max(1, Number(card.querySelector('.quantity-input').value));
    capNhatGiaModal();
    document.getElementById('dish-modal-note').value = card.querySelector('.dish-note').value;
    document.getElementById('dish-modal').showModal();
    document.getElementById('dish-modal-quantity').focus();
}

// Giá lấy từ món trong CSDL, thành tiền thay đổi theo số lượng đang nhập.
function capNhatGiaModal() {
    if (!monDangSua) {
        return;
    }

    const input = document.getElementById('dish-modal-quantity');
    const price = Number(monDangSua.dataset.price);
    const quantity = Number(input.value);
    const priceBox = document.getElementById('dish-modal-price');

    priceBox.setAttribute('aria-live', 'polite');
    if (input.value === '' || !input.validity.valid) {
        priceBox.textContent = 'Đơn giá: ' + dinhDangTien(price)
            + '\nNhập số lượng từ 1 đến 99 để tính thành tiền.';
        return;
    }

    priceBox.textContent =

        dinhDangTien(price * quantity);
}

if (menuForm) {
    document.getElementById('dish-modal-quantity').addEventListener('input', capNhatGiaModal);
    document.getElementById('dish-modal-quantity').addEventListener('change', capNhatGiaModal);
    document.body.classList.add('has-dish-modal');
    document.getElementById('dish-modal-form').addEventListener('submit', function (event) {
        event.preventDefault();
        monDangSua.querySelector('.quantity-input').value = document.getElementById('dish-modal-quantity').value;
        monDangSua.querySelector('.dish-note').value = document.getElementById('dish-modal-note').value.trim();
        document.getElementById('dish-modal').close();
        capNhatPhieu();
    });
    menuForm.addEventListener("input", capNhatPhieu);
    document.getElementById("search").addEventListener("input", locMon);
    capNhatPhieu();
}

document.addEventListener("submit", function (event) {
    const message = event.target.dataset.confirm;
    if (message && !window.confirm(message)) {
        event.preventDefault();
    }
});
