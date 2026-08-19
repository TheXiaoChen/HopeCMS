<?php
defined('HOPE_ROOT') || exit('access denied!');

require_once HOPE_ROOT . 'content/plugin/tips/tips_lib.php';

$tip = tips_random();
$list = tips_get_list();
$page_url = tips_page_url();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>小贴士 - Hope CMS 插件示例</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(tips_asset_url('tips.css?v=4.1')) ?>">
    <style>
        body { font-family: system-ui, sans-serif; max-width: 720px; margin: 48px auto; padding: 0 20px; color: #334155; }
        h1 { font-size: 1.5rem; margin-bottom: 8px; }
        .tips-page-desc { color: #64748b; font-size: 14px; margin-bottom: 24px; }
        .tips-page-list { margin: 0; padding-left: 20px; line-height: 1.8; font-size: 14px; }
    </style>
</head>
<body>
    <h1>Hope CMS 小贴士插件</h1>
    <p class="tips-page-desc">前台访问地址：<code><?= htmlspecialchars(str_replace(SITE_URL, '', $page_url)) ?></code>。后台首页会随机展示下方提示之一。</p>
    <div class="tips-banner" role="note">
        <span class="tips-banner-icon" aria-hidden="true">💡</span>
        <span class="tips-banner-text"><?= htmlspecialchars($tip) ?></span>
    </div>
    <h2 style="font-size:1rem;margin:28px 0 12px;">内置提示库（<?= count($list) ?> 条）</h2>
    <ol class="tips-page-list">
        <?php foreach ($list as $item): ?>
        <li><?= htmlspecialchars($item) ?></li>
        <?php endforeach; ?>
    </ol>
</body>
</html>
