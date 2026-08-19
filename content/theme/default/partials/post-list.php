<?php
defined('HOPE_ROOT') || exit('access denied!');

$mode = isset($list_mode) ? $list_mode : nova_list_mode();
$list_class = 'post-list post-list--' . $mode;
?>
<div class="<?= $list_class ?>">
    <?php foreach ($logs as $value):
        $sort_name = nova_sort_name($value['sortid']);
        $author_name = nova_author_name($value['author']);
        $has_cover = !empty($value['log_cover']);
    ?>
    <article class="post-item">
        <a href="<?= $value['log_url'] ?>" class="post-thumb<?= $has_cover ? '' : ' post-thumb--placeholder' ?>">
            <?php if ($has_cover): ?>
                <img src="<?= htmlspecialchars($value['log_cover']) ?>" alt="" loading="lazy">
            <?php else: ?>
                <span><?= htmlspecialchars(mb_substr(strip_tags($value['log_title']), 0, 1)) ?></span>
            <?php endif; ?>
        </a>
        <div class="post-body">
            <div class="post-meta">
                <?php if ($sort_name): ?>
                    <a href="<?= Url::sort($value['sortid']) ?>" class="post-cat"><?= htmlspecialchars($sort_name) ?></a>
                <?php endif; ?>
                <time datetime="<?= date('c', $value['date']) ?>"><?= date('Y-m-d', $value['date']) ?></time>
                <?php if ((int)($value['views'] ?? 0) > 0): ?>
                    <span class="post-stat"><?= (int)$value['views'] ?> 阅读</span>
                <?php endif; ?>
            </div>
            <h3 class="post-title"><a href="<?= $value['log_url'] ?>"><?= $value['log_title'] ?></a></h3>
            <p class="post-excerpt"><?= nova_excerpt($value['log_description'], 110) ?></p>
            <div class="post-footer">
                <span class="post-author"><?= htmlspecialchars($author_name) ?></span>
                <a href="<?= $value['log_url'] ?>" class="read-more">阅读全文</a>
            </div>
        </div>
    </article>
    <?php endforeach; ?>
</div>
