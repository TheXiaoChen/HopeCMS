<?php
defined('HOPE_ROOT') || exit('access denied!');
?>

<?php if ($signup_closed): ?>
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-brand" aria-hidden="true"><span class="brand-mark"></span></div>
        <h1>注册已关闭</h1>
        <p class="auth-subtitle">系统暂未开放注册</p>
        <a href="<?= htmlspecialchars($login_url) ?>" class="btn btn-primary btn-block">返回登录</a>
    </div>
</div>
<?php else: ?>
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-brand" aria-hidden="true"><span class="brand-mark"></span></div>
        <h1>创建账号</h1>
        <p class="auth-subtitle">注册后即可使用个人中心</p>
        <?php if ($msg): ?>
        <p class="form-error show"><?= htmlspecialchars($msg) ?></p>
        <?php endif; ?>
        <form method="post" action="<?= SITE_URL ?>admin/?act=reg" id="register-form"
              data-auth-api="<?= htmlspecialchars($auth_api) ?>"
              data-token="<?= htmlspecialchars($auth_token) ?>">
            <input type="hidden" name="do" value="1">
            <div class="form-group">
                <label for="mail">邮箱</label>
                <input type="email" id="mail" name="mail" class="form-input" placeholder="用于登录与找回" required>
            </div>
            <div class="form-group">
                <label for="passwd">密码</label>
                <input type="password" id="passwd" name="passwd" class="form-input" placeholder="至少 6 位" required minlength="6">
            </div>
            <?php if (!empty($invite_enabled)): ?>
            <div class="form-group">
                <label for="invite">邀请码（选填）</label>
                <input type="text" id="invite" name="invite" class="form-input" placeholder="填写邀请码双方各得 ¥<?= number_format($invite_reward_amount ?? 5, 2) ?>" value="<?= htmlspecialchars($invite_code) ?>">
            </div>
            <?php endif; ?>
            <div class="form-group">
                <label for="repasswd">确认密码</label>
                <input type="password" id="repasswd" name="repasswd" class="form-input" placeholder="再次输入密码" required>
            </div>
            <?php if ($login_code): ?>
            <div class="form-group">
                <label>图形验证码</label>
                <div class="input-with-btn">
                    <input type="text" name="login_code" class="form-input" placeholder="验证码" required>
                    <img src="<?= SITE_URL ?>system/checkcode.php" alt="验证码" class="auth-captcha" onclick="this.src='<?= SITE_URL ?>system/checkcode.php?'+Date.now()">
                </div>
            </div>
            <?php endif; ?>
            <?php if ($email_code): ?>
            <div class="form-group">
                <label>邮箱验证码</label>
                <input type="text" name="mail_code" class="form-input" placeholder="邮箱验证码" required>
            </div>
            <?php endif; ?>
            <button type="submit" class="auth-submit">注 册</button>
        </form>
        <p class="auth-footer">已有账号？<a href="<?= htmlspecialchars($login_url) ?>">立即登录</a></p>
    </div>
</div>
<?php endif; ?>

<script src="<?= THEME_URL ?>js/auth_api.js"></script>
