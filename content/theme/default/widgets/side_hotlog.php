<?php
defined('HOPE_ROOT') || exit('access denied!');

$defaults = is_array($widget_config['default'] ?? null) ? $widget_config['default'] : [];
$limit = max(1, min(30, (int)hope_widget_field($fields, '显示数量', $defaults['显示数量'] ?? 5)));
$Log_Model = new Log_Model();
$logs = $Log_Model->getHotLog($limit);
?>
<div class="widget widget-hotlog">
    <h3 class="widget-title widget-title--line"><?= htmlspecialchars($widget_title ?: '热门文章') ?></h3>
    <?php if ($logs): ?>
        <ul class="widget-posts widget-posts--thumb">
            <?php foreach ($logs as $post):
                $cover = !empty($post['log_cover']) ? $post['log_cover'] : ($post['cover'] ?? '');
                $title = $post['title'] ?? ($post['log_title'] ?? '');
                $url = $post['log_url'] ?? Url::log($post['gid'] ?? 0);
                $letter = mb_substr(strip_tags($title), 0, 1);
            ?>
                <li>
                    <a class="widget-post" href="<?= htmlspecialchars($url) ?>">
                        <span class="widget-post-thumb<?= $cover ? '' : ' is-placeholder' ?>">
                            <?php if ($cover): ?>
                                <img src="<?= htmlspecialchars($cover) ?>" alt="" loading="lazy">
                            <?php else: ?>
                                <span><?= htmlspecialchars($letter) ?></span>
                            <?php endif; ?>
                        </span>
                        <span class="widget-post-body">
                            <span class="widget-post-title"><?= htmlspecialchars(strip_tags($title)) ?></span>
                        </span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="widget-empty">暂无文章</p>
    <?php endif; ?>
</div>
