"use strict";

// JavaScript chỉ hỗ trợ soạn phiếu. PHP vẫn kiểm tra và lưu dữ liệu vào MySQL.

function dinhDangTien(value) {
    return new Intl.NumberFormat("vi-VN").format(value) + " đ";
}

function taoInputAn(name, value) {
    const input = document.createElement("input");
    input.type = "hidden";
    input.name = name;
    input.value = value;
    return input;
}

function docPhieuBanDau() {
    const data = document.getElementById("draft-items-data");
    if (!data) {
        return [];
    }
    try {
        const items = JSON.parse(data.textContent);
        return Array.isArray(items) ? items : [];
    } catch (error) {
        return [];
    }
}

const menuForm = document.getElementById("menu-form");
const directorySearch = document.getElementById("table-search");
if (directorySearch) {
    directorySearch.addEventListener("input", function () {
        const search = chuanHoaTenBan(directorySearch.value);
        const cards = document.querySelectorAll("[data-directory-table]");
        let count = 0;
        for (const card of cards) {
            card.hidden = !chuanHoaTenBan(card.dataset.directoryTable).includes(search);
            if (!card.hidden) count++;
        }
        document.getElementById("table-search-empty").hidden = count > 0;
    });
}
// Giữ nguyên các ô đã tích khi lưu từng món, không tải lại trang.
function capNhatNutHoanThanh(card) {
    const button = card.querySelector('button[value="ready-table"]');
    if (!button || card.dataset.saving === "yes") {
        return;
    }
    const checks = card.querySelectorAll('.item-progress-form input[name="done"]');
    button.disabled = Array.from(checks).some(function (check) { return !check.checked; });
}

document.addEventListener("change", function (event) {
    if (event.target.matches('.kitchen-card .item-progress-form input[name="done"]')) {
        capNhatNutHoanThanh(event.target.closest(".kitchen-card"));
    }
});

document.addEventListener("submit", async function (event) {
    const form = event.target;
    const card = form.closest(".kitchen-card");
    const submitter = event.submitter;
    if (!card || !submitter || !["item-ready", "ready-table"].includes(submitter.value)) {
        return;
    }
    event.preventDefault();
    if (card.dataset.saving === "yes") {
        return;
    }
    const data = new FormData(form);
    data.set("action", submitter.value);
    if (submitter.value === "ready-table") {
        const pending = card.querySelectorAll(".item-progress-form");
        for (const itemForm of pending) {
            if (itemForm.querySelector('input[name="done"]').checked) {
                data.append("detail_ids[]", itemForm.querySelector('input[name="detail_id"]').value);
            }
        }
    }
    card.dataset.saving = "yes";
    submitter.disabled = true;
    let message = card.querySelector(".kitchen-save-message");
    if (!message) {
        message = document.createElement("p");
        message.className = "kitchen-save-message";
        message.setAttribute("role", "status");
        card.appendChild(message);
    }
    message.textContent = "Đang lưu…";
    try {
        const response = await fetch(window.location.href, {
            method: "POST",
            body: data,
            headers: { "X-Requested-With": "KitchenFetch", "Accept": "application/json" }
        });
        const result = await response.json();
        if (!response.ok || !result.success) {
            throw new Error(result.message || "Không thể lưu xác nhận.");
        }
        const itemForms = submitter.value === "ready-table"
            ? Array.from(card.querySelectorAll(".item-progress-form")) : [form];
        for (const itemForm of itemForms) {
            itemForm.closest(".item-progress-row").querySelector(".item-progress-status").textContent = "✓ Đã mang ra bàn";
            itemForm.remove();
        }
        if (submitter.value === "ready-table") {
            form.remove();
            const columns = document.querySelectorAll(".kitchen-column");
            const completedColumn = columns[columns.length - 1];
            for (const empty of completedColumn.querySelectorAll(":scope > .empty")) {
                empty.remove();
            }
            card.hidden = false;
            const existing = Array.from(completedColumn.querySelectorAll('.kitchen-card')).find(function (other) {
                return other.dataset.orderId === card.dataset.orderId;
            });
            if (existing) {
                // Nối món vừa xong vào cùng bàn, không tạo thêm thẻ trùng tên.
                existing.querySelector('.item-progress-list').append(...card.querySelector('.item-progress-list').children);
                existing.appendChild(message);
                card.remove();
            } else {
                completedColumn.appendChild(card);
            }
            completedColumn.querySelector('[data-kitchen-table-search]')?.dispatchEvent(new Event('input'));
            message.textContent = "Đã hoàn thành · Chờ hoàn tất";
        } else {
            message.textContent = "Đã lưu xác nhận.";
        }
        taiThongBaoSidebar();
    } catch (error) {
        message.textContent = error.message;
        submitter.disabled = false;
    } finally {
        delete card.dataset.saving;
        capNhatNutHoanThanh(card);
    }
});

const kitchenTableSearches = document.querySelectorAll("[data-kitchen-table-search]");
for (const tableSearch of kitchenTableSearches) {
    tableSearch.addEventListener("input", function () {
        const search = chuanHoaTenBan(tableSearch.value);
        const column = tableSearch.closest(".kitchen-column");
        const cards = column.querySelectorAll("[data-table-name]");
        let visibleCount = 0;
        for (let index = 0; index < cards.length; index++) {
            cards[index].hidden = !chuanHoaTenBan(cards[index].dataset.tableName).includes(search);
            if (!cards[index].hidden) {
                visibleCount++;
            }
        }
        const empty = column.querySelector(".kitchen-search-empty");
        if (empty) {
            empty.hidden = visibleCount > 0;
        }
    });
}

// Cho phép tìm tên bàn bằng cả chữ có dấu và không dấu.
function chuanHoaTenBan(value) {
    return value.toLocaleLowerCase("vi").normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "").replace(/đ/g, "d").trim();
}

let draftItems = docPhieuBanDau();
let monDangSua = null;
let dongDangSua = null;

function timTheMon(productId) {
    return document.querySelector('.menu-item[data-id="' + productId + '"]');
}

function capNhatPhieu() {
    const summary = document.getElementById("cart-summary");
    const fields = document.getElementById("draft-fields");
    if (!summary || !fields) {
        return;
    }

    summary.replaceChildren();
    fields.replaceChildren();
    let total = 0;
    const quantityByProduct = {};

    for (let index = 0; index < draftItems.length; index++) {
        const item = draftItems[index];
        const card = timTheMon(item.product_id);
        if (!card) {
            continue;
        }

        item.quantity = Number(item.quantity);
        item.note = String(item.note || "").trim();
        quantityByProduct[item.product_id] = (quantityByProduct[item.product_id] || 0) + item.quantity;

        const row = document.createElement("div");
        row.className = "cart-row";
        const head = document.createElement("div");
        head.className = "cart-item-head";
        const name = document.createElement("p");
        name.className = "cart-item-name";
        name.textContent = item.quantity + " × " + card.dataset.name;
        const amount = document.createElement("strong");
        amount.className = "cart-item-amount";
        amount.textContent = dinhDangTien(item.quantity * Number(card.dataset.price));
        head.appendChild(name);
        head.appendChild(amount);
        row.appendChild(head);

        if (item.note !== "") {
            const detail = document.createElement("p");
            detail.className = "food-note";
            detail.textContent = "Ghi chú: " + item.note;
            row.appendChild(detail);
        }

        const actions = document.createElement("div");
        actions.className = "cart-item-actions";
        const minus = document.createElement("button");
        minus.type = "button";
        minus.className = "button";
        minus.textContent = "−";
        minus.setAttribute("aria-label", "Giảm số lượng " + card.dataset.name);
        minus.addEventListener("click", function () {
            if (draftItems[index].quantity <= 1) {
                draftItems.splice(index, 1);
            } else {
                draftItems[index].quantity--;
            }
            capNhatPhieu();
        });
        const plus = document.createElement("button");
        plus.type = "button";
        plus.className = "button";
        plus.textContent = "+";
        plus.setAttribute("aria-label", "Tăng số lượng " + card.dataset.name);
        plus.disabled = item.quantity >= 99;
        plus.addEventListener("click", function () {
            draftItems[index].quantity++;
            capNhatPhieu();
        });
        const edit = document.createElement("button");
        edit.type = "button";
        edit.className = "button";
        edit.textContent = "Sửa";
        edit.addEventListener("click", function () {
            moMon(card, index);
        });
        actions.appendChild(minus);
        actions.appendChild(plus);
        actions.appendChild(edit);
        row.appendChild(actions);
        summary.appendChild(row);

        fields.appendChild(taoInputAn("product_id[]", item.product_id));
        fields.appendChild(taoInputAn("quantity[]", item.quantity));
        fields.appendChild(taoInputAn("note[]", item.note));
        total += item.quantity * Number(card.dataset.price);
    }

    const cards = document.querySelectorAll(".menu-item");
    for (let index = 0; index < cards.length; index++) {
        const quantity = quantityByProduct[cards[index].dataset.id] || 0;
        cards[index].querySelector(".dish-selection").textContent =
            quantity > 0 ? "Đang có trong phiếu: " + quantity : "";
    }

    if (draftItems.length === 0) {
        summary.textContent = "Chưa chọn món nào.";
    }
    document.getElementById("draft-total").textContent = dinhDangTien(total);
    const clearButton = document.getElementById("clear-draft");
    if (clearButton) {
        clearButton.disabled = draftItems.length === 0;
    }
}

function lamTrongPhieu() {
    draftItems = [];
    capNhatPhieu();
}

function locMon() {
    const search = document.getElementById("search").value.toLocaleLowerCase("vi").trim();
    const activeButton = document.querySelector(".category-button.active");
    const category = activeButton ? activeButton.dataset.category : "";
    const cards = document.querySelectorAll(".menu-item");
    let count = 0;

    for (let index = 0; index < cards.length; index++) {
        const card = cards[index];
        const matchName = card.dataset.name.toLocaleLowerCase("vi").includes(search);
        const matchCategory = category === "" || card.dataset.category === category;
        card.hidden = !(matchName && matchCategory);
        if (!card.hidden) {
            count++;
        }
    }
    document.getElementById("no-results").hidden = count > 0;
}

function moMon(card, itemIndex) {
    monDangSua = card;
    dongDangSua = Number.isInteger(itemIndex) ? itemIndex : null;
    const item = dongDangSua === null ? null : draftItems[dongDangSua];
    document.getElementById("dish-modal-title").textContent =
        item ? "Sửa " + card.dataset.name : "Thêm " + card.dataset.name;
    document.getElementById("dish-modal-quantity").value = item ? item.quantity : 1;
    document.getElementById("dish-modal-note").value = item ? item.note : "";
    capNhatGiaModal();
    document.getElementById("dish-modal").showModal();
    document.getElementById("dish-modal-quantity").focus();
}

// Giá lấy từ món trong CSDL và thay đổi ngay khi nhập số lượng.
function capNhatGiaModal() {
    if (!monDangSua) {
        return;
    }
    const input = document.getElementById("dish-modal-quantity");
    const price = Number(monDangSua.dataset.price);
    const priceBox = document.getElementById("dish-modal-price");
    priceBox.setAttribute("aria-live", "polite");
    if (input.value === "" || !input.validity.valid) {
        priceBox.textContent = dinhDangTien(price);
        return;
    }
    priceBox.textContent = dinhDangTien(price * Number(input.value));
}

function luuMonTuModal() {
    const quantity = Number(document.getElementById("dish-modal-quantity").value);
    const note = document.getElementById("dish-modal-note").value.trim();
    const productId = Number(monDangSua.dataset.id);

    if (dongDangSua !== null) {
        draftItems[dongDangSua] = {
            product_id: productId,
            quantity: quantity,
            note: note
        };
    } else {
        const sameIndex = draftItems.findIndex(function (item) {
            return Number(item.product_id) === productId && String(item.note || "").trim() === note;
        });
        if (sameIndex >= 0) {
            if (Number(draftItems[sameIndex].quantity) + quantity > 99) {
                window.alert("Tổng số lượng của dòng món này không được vượt quá 99.");
                return false;
            }
            draftItems[sameIndex].quantity = Number(draftItems[sameIndex].quantity) + quantity;
        } else {
            draftItems.push({
                product_id: productId,
                quantity: quantity,
                note: note
            });
        }
    }
    return true;
}

document.addEventListener("click", function (event) {
    const button = event.target.closest("button");
    if (!button) {
        return;
    }
    if (button.hasAttribute("data-print")) {
        window.print();
    }
    if (button.id === "clear-draft" && !button.disabled) {
        lamTrongPhieu();
    }
    if (button.hasAttribute("data-edit-dish")) {
        moMon(button.closest(".menu-item"), null);
    }
    if (button.hasAttribute("data-close-dish")) {
        document.getElementById("dish-modal").close();
    }
    if (button.classList.contains("category-button")) {
        const buttons = document.querySelectorAll(".category-button");
        for (let index = 0; index < buttons.length; index++) {
            buttons[index].classList.remove("active");
        }
        button.classList.add("active");
        locMon();
    }
});

if (menuForm) {
    document.getElementById("dish-modal-quantity").addEventListener("input", capNhatGiaModal);
    document.getElementById("dish-modal-quantity").addEventListener("change", capNhatGiaModal);
    document.body.classList.add("has-dish-modal");
    document.getElementById("dish-modal-form").addEventListener("submit", function (event) {
        event.preventDefault();
        if (luuMonTuModal()) {
            document.getElementById("dish-modal").close();
            capNhatPhieu();
        }
    });
    document.getElementById("search").addEventListener("input", locMon);
    capNhatPhieu();
}

document.addEventListener("submit", function (event) {
    const message = event.target.dataset.confirm;
    if (message && !window.confirm(message)) {
        event.preventDefault();
    }
});

const notificationStorageKey = "restaurant_seen_notifications";

function docThongBaoDaXem() {
    try {
        const saved = JSON.parse(sessionStorage.getItem(notificationStorageKey));
        return saved && typeof saved === "object" ? saved : {};
    } catch (error) {
        return {};
    }
}

function luuThongBaoDaXem(seen) {
    sessionStorage.setItem(notificationStorageKey, JSON.stringify(seen));
}

function capNhatThongBaoSidebar(counts) {
    const badges = document.querySelectorAll("[data-notification]");
    const seen = docThongBaoDaXem();
    for (let index = 0; index < badges.length; index++) {
        const badge = badges[index];
        const key = badge.dataset.notification;
        const total = Number(counts[key] || 0);
        if (total < Number(seen[key] || 0)) {
            seen[key] = total;
        }
        const unread = Math.max(0, total - Number(seen[key] || 0));
        badge.dataset.total = total;
        badge.textContent = unread;
        badge.hidden = unread === 0;
    }
    luuThongBaoDaXem(seen);
}

async function taiThongBaoSidebar() {
    try {
        const response = await fetch("staff.php?api=sidebar-counts", {
            cache: "no-store",
            headers: { "Accept": "application/json" }
        });
        if (response.ok) {
            capNhatThongBaoSidebar(await response.json());
        }
    } catch (error) {
        // Giữ số cũ nếu mạng hoặc MySQL tạm thời gián đoạn.
    }
}

if (document.querySelector("[data-notification]")) {
    const initialCounts = {};
    const initialBadges = document.querySelectorAll("[data-notification]");
    for (let index = 0; index < initialBadges.length; index++) {
        initialCounts[initialBadges[index].dataset.notification] = Number(initialBadges[index].textContent || 0);
    }
    capNhatThongBaoSidebar(initialCounts);
    taiThongBaoSidebar();
    window.setInterval(taiThongBaoSidebar, 5000);
}

document.addEventListener("click", function (event) {
    const link = event.target.closest("[data-notification-link]");
    if (!link) {
        return;
    }
    const key = link.dataset.notificationLink;
    const badge = document.querySelector('[data-notification="' + key + '"]');
    const seen = docThongBaoDaXem();
    seen[key] = Number(badge ? badge.dataset.total || badge.textContent : 0);
    luuThongBaoDaXem(seen);
    if (badge) {
        badge.hidden = true;
    }
});
