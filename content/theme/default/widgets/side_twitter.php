<?php
defined('HOPE_ROOT') || exit('access denied!');

$limit = max(1, (int)hope_widget_field($fields, '显示数量', $widget_config['default']['显示数量'] ?? 5));
$Twitter_Model = new Twitter_Model();
$notes = $Twitter_Model->getTwitters(0, 1, $limit, false);
?>
<div class="widget widget-twitter">
    <h3 class="widget-title"><?= htmlspecialchars($widget_title ?: '碎言碎语') ?></h3>
    <?php if ($notes): ?>
        <ul class="widget-posts">
            <?php foreach ($notes as $note): ?>
                <li>
                    <span class="widget-post-title"><?= htmlspecialchars($note['date'] ?? '') ?></span>
                    <span class="widget-post-excerpt"><?= $note['t'] ?? nl2br(htmlspecialchars($note['content'] ?? '')) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="widget-empty">暂无微语</p>
    <?php endif; ?>
</div>
