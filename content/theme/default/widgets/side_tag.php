<?php
defined('HOPE_ROOT') || exit('access denied!');

$defaults = is_array($widget_config['default'] ?? null) ? $widget_config['default'] : [];
$limit = max(1, min(50, (int)hope_widget_field($fields, '显示数量', $defaults['显示数量'] ?? 18)));
$tags = nova_tags($limit);
?>
<div class="widget widget-tags">
    <h3 class="widget-title">
        <span class="widget-title-ico" aria-hidden="true">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
        </span>
        <?= htmlspecialchars($widget_title ?: '文章标签') ?>
    </h3>
    <?php if ($tags): ?>
        <div class="tag-cloud">
            <?php foreach ($tags as $tag): ?>
                <a href="<?= Url::tag(rawurlencode($tag['tagname'])) ?>" class="tag-item">#<?= htmlspecialchars($tag['tagname']) ?></a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="widget-empty">暂无标签</p>
    <?php endif; ?>
</div>
