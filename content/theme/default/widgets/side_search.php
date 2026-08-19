<?php
defined('HOPE_ROOT') || exit('access denied!');

$placeholder = hope_widget_field($fields, '输入提示', $widget_config['default']['输入提示'] ?? '输入关键词…');
?>
<div class="widget widget-search">
    <h3 class="widget-title"><?= htmlspecialchars($widget_title ?: '搜索') ?></h3>
    <form class="widget-search-form" method="get" action="<?= SITE_URL ?>index.php" role="search">
        <input type="search" name="keyword" placeholder="<?= htmlspecialchars($placeholder) ?>" value="<?= htmlspecialchars(isset($_GET['keyword']) ? trim((string)$_GET['keyword']) : '') ?>" aria-label="搜索">
        <button type="submit" aria-label="搜索">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        </button>
    </form>
</div>
