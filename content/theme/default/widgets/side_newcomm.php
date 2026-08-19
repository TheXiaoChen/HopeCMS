<?php
defined('HOPE_ROOT') || exit('access denied!');

$defaults = is_array($widget_config['default'] ?? null) ? $widget_config['default'] : [];
$limit = max(1, min(30, (int)hope_widget_field($fields, '显示数量', $defaults['显示数量'] ?? 8)));

$DB = Database::getInstance();
$query = $DB->query(
    "SELECT cid, gid, poster, comment, date FROM " . DB_PREFIX . "comment WHERE hide='n' ORDER BY date DESC LIMIT " . (int)$limit
);
$comments = [];
while ($row = $DB->fetch_array($query)) {
    $comments[] = $row;
}
?>
<div class="widget widget-newcomm">
    <h3 class="widget-title widget-title--line"><?= htmlspecialchars($widget_title ?: '最新评论') ?></h3>
    <?php if ($comments): ?>
        <ul class="widget-posts widget-posts--comment">
            <?php foreach ($comments as $comment):
                $poster = strip_tags($comment['poster'] ?? '');
                $excerpt = extractHtmlData(strip_tags($comment['comment'] ?? ''), 36);
                $date = !empty($comment['date']) ? date('m-d H:i', (int)$comment['date']) : '';
                $letter = mb_substr($poster !== '' ? $poster : '评', 0, 1);
            ?>
                <li>
                    <a class="widget-post" href="<?= htmlspecialchars(Url::log((int)$comment['gid']) . '#comment-' . (int)$comment['cid']) ?>">
                        <span class="widget-post-thumb is-placeholder"><span><?= htmlspecialchars($letter) ?></span></span>
                        <span class="widget-post-body">
                            <span class="widget-post-title"><?= htmlspecialchars($poster !== '' ? $poster : '匿名') ?></span>
                            <span class="widget-post-excerpt"><?= htmlspecialchars($excerpt) ?></span>
                            <?php if ($date !== ''): ?>
                                <span class="widget-post-meta"><?= htmlspecialchars($date) ?></span>
                            <?php endif; ?>
                        </span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="widget-empty">暂无评论</p>
    <?php endif; ?>
</div>
