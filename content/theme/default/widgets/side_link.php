<?php
defined('HOPE_ROOT') || exit('access denied!');

global $CACHE;

$defaults = is_array($widget_config['default'] ?? null) ? $widget_config['default'] : [];
$limit = max(1, min(50, (int)hope_widget_field($fields, '显示数量', $defaults['显示数量'] ?? 10)));

$links = $CACHE->readCache('link');
if (!is_array($links)) {
    $links = [];
}
$links = array_slice($links, 0, $limit);
?>
<div class="widget widget-link">
    <h3 class="widget-title widget-title--line"><?= htmlspecialchars($widget_title ?: '友情链接') ?></h3>
    <?php if ($links): ?>
        <ul class="widget-list widget-list--links">
            <?php foreach ($links as $link):
                $name = $link['link'] ?? '';
                $url = $link['url'] ?? '#';
                $des = trim((string)($link['des'] ?? ''));
            ?>
                <li>
                    <a href="<?= htmlspecialchars($url) ?>" target="_blank" rel="noopener"<?= $des !== '' ? ' title="' . htmlspecialchars($des) . '"' : '' ?>>
                        <?= htmlspecialchars($name) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="widget-empty">暂无链接</p>
    <?php endif; ?>
</div>
