<?php
defined('HOPE_ROOT') || exit('access denied!');
?>

<div class="auth-page">
    <div class="auth-card">
        <div class="auth-brand" aria-hidden="true"><span class="brand-mark"></span></div>
        <h1>欢迎回来</h1>
        <p class="auth-subtitle">登录你的账号，继续探索</p>
        <?php if ($msg): ?>
        <p class="form-error show"><?= htmlspecialchars($msg) ?></p>
        <?php endif; ?>
        <form method="post" action="<?= SITE_URL ?>admin/?act=login" id="login-form"
              data-auth-api="<?= htmlspecialchars($auth_api) ?>"
              data-token="<?= htmlspecialchars($auth_token) ?>"
              data-redirect="<?= htmlspecialchars($redirect) ?>">
            <input type="hidden" name="do" value="1">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
            <div class="form-group">
                <label for="account">用户名 / 邮箱</label>
                <input type="text" id="account" name="user" class="form-input" placeholder="请输入用户名或邮箱" required>
            </div>
            <div class="form-group">
                <label for="password">密码</label>
                <input type="password" id="password" name="password" class="form-input" placeholder="请输入密码" required>
            </div>
            <?php if ($login_code): ?>
            <div class="form-group">
                <label for="login_code">验证码</label>
                <div class="input-with-btn">
                    <input type="text" id="login_code" name="login_code" class="form-input" placeholder="验证码" required>
                    <img src="<?= SITE_URL ?>system/checkcode.php" alt="验证码" class="auth-captcha" onclick="this.src='<?= SITE_URL ?>system/checkcode.php?'+Date.now()">
                </div>
            </div>
            <?php endif; ?>
            <div class="form-check-row">
                <label><input type="checkbox" name="rememberMe" value="1"> 记住我</label>
            </div>
            <button type="submit" class="auth-submit">登 录</button>
        </form>
        <?php doAction('login_ext'); ?>
        <?php if ($is_signup): ?>
        <p class="auth-footer">还没有账号？<a href="<?= htmlspecialchars($register_url) ?>">立即注册</a></p>
        <?php endif; ?>
    </div>
</div>

<script src="<?= THEME_URL ?>js/auth_api.js"></script>
