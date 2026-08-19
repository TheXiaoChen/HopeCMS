<?php
defined('HOPE_ROOT') || exit('access denied!');
$site_title = '未找到页面 - ' . $site_title;
include View::getView('header');
?>
<div class="container">
    <div class="empty-state page-404">
        <div class="empty-icon">404</div>
        <h1>页面不存在</h1>
        <p>您访问的页面可能已被删除或地址有误。</p>
        <a href="<?= SITE_URL ?>" class="btn btn-primary">返回首页</a>
    </div>
</div>
<?php include View::getView('footer'); ?>
