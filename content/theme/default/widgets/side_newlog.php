<?php
defined('HOPE_ROOT') || exit('access denied!');

global $CACHE;

$defaults = is_array($widget_config['default'] ?? null) ? $widget_config['default'] : [];
$limit = max(1, min(30, (int)hope_widget_field($fields, '显示数量', $defaults['显示数量'] ?? 6)));

$logs = $CACHE->readCache('newlog');
if (!is_array($logs)) {
    $logs = [];
}
$logs = array_slice($logs, 0, $limit);
?>
<div class="widget widget-newlog">
    <h3 class="widget-title widget-title--line"><?= htmlspecialchars($widget_title ?: '最新文章') ?></h3>
    <?php if ($logs): ?>
        <ul class="widget-posts widget-posts--thumb">
            <?php foreach ($logs as $post):
                $title = $post['title'] ?? '';
                $cover = !empty($post['cover']) ? $post['cover'] : '';
                $letter = mb_substr(strip_tags(html_entity_decode($title, ENT_QUOTES, 'UTF-8')), 0, 1);
                $date = !empty($post['date']) ? date('Y-m-d', (int)$post['date']) : '';
            ?>
                <li>
                    <a class="widget-post" href="<?= htmlspecialchars(Url::log($post['gid'])) ?>">
                        <span class="widget-post-thumb<?= $cover ? '' : ' is-placeholder' ?>">
                            <?php if ($cover): ?>
                                <img src="<?= htmlspecialchars($cover) ?>" alt="" loading="lazy">
                            <?php else: ?>
                                <span><?= htmlspecialchars($letter) ?></span>
                            <?php endif; ?>
                        </span>
                        <span class="widget-post-body">
                            <span class="widget-post-title"><?= $title ?></span>
                            <?php if ($date !== ''): ?>
                                <span class="widget-post-meta"><?= htmlspecialchars($date) ?></span>
                            <?php endif; ?>
                        </span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="widget-empty">暂无文章</p>
    <?php endif; ?>
</div>
