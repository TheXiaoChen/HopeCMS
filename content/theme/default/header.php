<?php
/*
Theme Name: 默认主题
Theme Type: cms
Theme Url: http://www.hopecms.cn
Author: Hope CMS
Author Url: http://www.hopecms.cn
Version: 1.0.0
Description: Hope CMS 默认主题：大标题首页、双栏列表、标签云侧栏与个人中心。
*/
defined('HOPE_ROOT') || exit('access denied!');
require_once View::getView('module');

$nova_scheme = nova_theme_default();
$nova_primary = nova_primary_color();
$is_front = empty($_GET['sort']) && empty($_GET['tag']) && empty($_GET['keyword']) && empty($_GET['record']) && empty($_GET['author']) && empty($logid ?? null);
?>
<!DOCTYPE html>
<html lang="zh-CN" data-theme="<?= htmlspecialchars($nova_scheme) ?>" data-primary="<?= htmlspecialchars($nova_primary) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="generator" content="Hope CMS">
    <title><?= htmlspecialchars($site_title) ?></title>
    <?php if (!empty($site_key)): ?>
        <meta name="keywords" content="<?= htmlspecialchars($site_key) ?>">
    <?php endif; ?>
    <?php if (!empty($site_description)): ?>
        <meta name="description" content="<?= htmlspecialchars($site_description) ?>">
    <?php endif; ?>
    <meta property="og:site_name" content="<?= nova_brand() ?>">
    <meta property="og:title" content="<?= htmlspecialchars($site_title) ?>">
    <?php if (!empty($site_description)): ?>
        <meta property="og:description" content="<?= htmlspecialchars($site_description) ?>">
    <?php endif; ?>
    <meta name="twitter:card" content="summary_large_image">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Noto+Sans+SC:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= nova_asset('css/style.css') ?>">
    <link rel="stylesheet" href="<?= nova_asset('css/user.css') ?>">
    <?php nova_seo_meta(); ?>
    <script>
    (function () {
        var root = document.documentElement;
        var stored = localStorage.getItem('nova-theme');
        var mode = stored || root.getAttribute('data-theme') || 'auto';
        var dark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        var resolved = mode === 'auto' ? (dark ? 'dark' : 'light') : mode;
        root.setAttribute('data-theme-resolved', resolved);
        root.style.colorScheme = resolved;
        var primary = root.getAttribute('data-primary');
        if (primary) {
            root.style.setProperty('--nova-primary', primary);
        }
    })();
    </script>
    <?php doAction('index_head') ?>
</head>
<body class="<?= $is_front ? 'is-home' : 'is-inner' ?>">
<?php doAction('index_body_start'); ?>
<a class="skip-link" href="#main-content">跳到主要内容</a>
<header class="site-header" id="site-header">
    <div class="container header-inner">
        <a href="<?= SITE_URL ?>" class="site-brand">
            <span class="brand-mark" aria-hidden="true"></span>
            <span class="brand-name"><?= nova_brand() ?></span>
        </a>
        <button type="button" class="nav-toggle" id="nav-toggle" aria-label="打开菜单" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
        <nav class="site-nav" id="site-nav" aria-label="主导航">
            <?php hope_render_main_nav('nav-link'); ?>
        </nav>
        <div class="header-actions">
            <button type="button" class="search-toggle" id="search-toggle" aria-label="搜索" aria-expanded="false" aria-controls="header-search-panel">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </button>
            <button type="button" class="theme-toggle" id="theme-toggle" aria-label="切换深浅色">
                <svg class="icon-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
                <svg class="icon-moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
            </button>
            <div class="header-auth" id="nav-auth"
                 data-user-url="<?= htmlspecialchars(nova_url('user')) ?>"
                 data-login-url="<?= htmlspecialchars(nova_url('login')) ?>"
                 data-register-url="<?= htmlspecialchars(nova_url('register')) ?>"><?php nova_nav_auth(); ?></div>
        </div>
    </div>
    <div class="nav-backdrop" id="nav-backdrop" hidden></div>
    <div class="header-search-panel" id="header-search-panel" hidden>
        <form class="site-search" method="get" action="<?= SITE_URL ?>index.php" role="search">
            <input type="search" name="keyword" placeholder="搜索文章、标签或关键词…" value="<?= htmlspecialchars(isset($_GET['keyword']) ? trim($_GET['keyword']) : '') ?>" aria-label="搜索" autofocus>
            <button type="submit">搜索</button>
        </form>
    </div>
</header>
<main class="site-main" id="main-content">
