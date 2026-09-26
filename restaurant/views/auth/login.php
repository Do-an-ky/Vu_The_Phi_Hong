<section class="panel login-panel">
    <p class="eyebrow">RESTAURANT</p>
    <h1>Đăng nhập nhân viên</h1>
    <p>Dùng tài khoản nhân viên trong hệ thống nhà hàng.</p>
    <form method="post" action="?page=login">
        <?php csrfInput(); ?>
        <label>Tên đăng nhập
            <input name="username" autocomplete="username" required maxlength="50">
        </label>
        <label>Mật khẩu
            <input name="password" type="password" autocomplete="current-password" required>
        </label>
        <button class="button primary full" name="action" value="login">Đăng nhập</button>
    </form>
</section>
