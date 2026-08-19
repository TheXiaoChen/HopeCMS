<?php
defined('HOPE_ROOT') || exit('access denied!');
?>
<aside class="sidebar-left" aria-label="快捷导航">
    <div class="widget">
        <h3 class="widget-title">快捷入口</h3>
        <ul class="widget-list">
            <li><a href="<?= SITE_URL ?>"><span>首页</span></a></li>
            <?php foreach (nova_nav_sorts() as $nav): ?>
            <li>
                <a href="<?= Url::sort($nav['sid']) ?>">
                    <span><?= htmlspecialchars($nav['sortname']) ?></span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="widget">
        <h3 class="widget-title">用户</h3>
        <ul class="widget-list">
            <li><a href="<?= htmlspecialchars(nova_url('login')) ?>"><span>登录</span></a></li>
            <li><a href="<?= htmlspecialchars(nova_url('register')) ?>"><span>注册</span></a></li>
            <li><a href="<?= htmlspecialchars(nova_url('user')) ?>"><span>个人中心</span></a></li>
        </ul>
    </div>
</aside>
