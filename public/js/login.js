"use strict";
const toggle = document.getElementById('toggle-password');
const password = document.getElementById('password');
if (toggle && password) {
    toggle.hidden = false;
    toggle.addEventListener('click', function () {
        const visible = password.type === 'password';
        password.type = visible ? 'text' : 'password';
        toggle.textContent = visible ? 'Ẩn' : 'Hiện';
        toggle.setAttribute('aria-label', visible ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
        toggle.setAttribute('aria-pressed', String(visible));
    });
}