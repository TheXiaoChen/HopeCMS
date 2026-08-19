<?php
defined('HOPE_ROOT') || exit('access denied!');

$author_name = nova_author_name($author);
$sort_name = nova_sort_name($sortid);
$layout = nova_layout();
$read_min = nova_reading_time($log_content);
$author_avatar = nova_author_avatar($author);
$article_tags = [];
if (!empty($tags)) {
    $Tag_Model = new Tag_Model();
    $article_tags = $Tag_Model->getNamesFromIds(explode(',', $tags));
}
$prevLog = !empty($neighborLog['prevLog']) ? $neighborLog['prevLog'] : null;
$nextLog = !empty($neighborLog['nextLog']) ? $neighborLog['nextLog'] : null;
$hasNeighbor = !empty($prevLog) || !empty($nextLog);
?>

<div class="reading-progress" id="reading-progress" aria-hidden="true"></div>

<section class="club-band club-band--article">
    <div class="container club-band-inner <?= nova_layout_class() ?> page-article">
        <?php if ($layout === 'triple'): ?>
            <?php include THEME_PATH . 'partials/sidebar-left.php'; ?>
        <?php endif; ?>

        <div class="content-main">
            <nav class="article-crumb" aria-label="面包屑">
                <a href="<?= SITE_URL ?>">首页</a>
                <span class="crumb-sep" aria-hidden="true">/</span>
                <?php if ($sort_name): ?>
                    <a href="<?= Url::sort($sortid) ?>"><?= htmlspecialchars($sort_name) ?></a>
                    <span class="crumb-sep" aria-hidden="true">/</span>
                <?php endif; ?>
                <span class="crumb-current">正文</span>
            </nav>

            <article class="article-detail<?= !empty($log_cover) ? ' has-cover' : '' ?>" itemscope itemtype="https://schema.org/Article">
                <div class="article-body-wrap">
                    <header class="article-header">
                        <?php if ($sort_name): ?>
                            <a href="<?= Url::sort($sortid) ?>" class="post-cat"><?= htmlspecialchars($sort_name) ?></a>
                        <?php endif; ?>
                        <h1 itemprop="headline"><?= $log_title ?></h1>
                        <div class="article-meta">
                            <span class="meta-item meta-author" itemprop="author">
                                <?php if ($author_avatar): ?>
                                    <img class="meta-avatar" src="<?= htmlspecialchars($author_avatar) ?>" alt="" width="22" height="22">
                                <?php endif; ?>
                                <?= htmlspecialchars($author_name) ?>
                                <?= nova_user_badge($author) ?>
                            </span>
                            <time class="meta-item" datetime="<?= date('c', $date) ?>" itemprop="datePublished"><?= date('Y-m-d', $date) ?></time>
                            <span class="meta-item"><?= (int)$views ?> 阅读</span>
                            <span class="meta-item">约 <?= $read_min ?> 分钟</span>
                            <?php if ((int)$comnum > 0): ?>
                                <a class="meta-item" href="#comments"><?= (int)$comnum ?> 评论</a>
                            <?php endif; ?>
                        </div>
                    </header>

                    <?php if (!empty($log_cover)): ?>
                    <figure class="article-cover">
                        <img src="<?= htmlspecialchars($log_cover) ?>" alt="" itemprop="image">
                    </figure>
                    <?php endif; ?>

                    <div class="article-content" itemprop="articleBody">
                        <?= $log_content ?>
                    </div>

                    <?php if (!empty($article_tags)): ?>
                    <footer class="article-tags">
                        <span class="article-tags-label">标签</span>
                        <?php foreach ($article_tags as $t): ?>
                        <a href="<?= Url::tag(rawurlencode($t)) ?>" class="tag-item">#<?= htmlspecialchars($t) ?></a>
                        <?php endforeach; ?>
                    </footer>
                    <?php endif; ?>

                    <aside class="article-author-card">
                        <?php if ($author_avatar): ?>
                            <img class="article-author-avatar" src="<?= htmlspecialchars($author_avatar) ?>" alt="">
                        <?php else: ?>
                            <span class="article-author-avatar is-placeholder"><?= htmlspecialchars(mb_substr($author_name, 0, 1)) ?></span>
                        <?php endif; ?>
                        <div class="article-author-body">
                            <div class="article-author-name">
                                <?= htmlspecialchars($author_name) ?>
                                <?= nova_user_badge($author) ?>
                            </div>
                            <p class="article-author-des">感谢阅读，欢迎在下方留言交流。</p>
                        </div>
                        <?php if ($allow_remark === 'y'): ?>
                            <a class="btn btn-ghost btn-sm article-author-cta" href="#comments">写评论</a>
                        <?php endif; ?>
                    </aside>
                </div>
            </article>

            <?php if ($hasNeighbor): ?>
            <nav class="article-neighbors" aria-label="上下篇">
                <?php if ($prevLog): ?>
                <a class="neighbor-card neighbor-prev" href="<?= Url::log($prevLog['gid']) ?>" rel="prev">
                    <span class="neighbor-label">上一篇</span>
                    <span class="neighbor-title"><?= $prevLog['title'] ?></span>
                </a>
                <?php else: ?>
                <span class="neighbor-card is-empty"></span>
                <?php endif; ?>
                <?php if ($nextLog): ?>
                <a class="neighbor-card neighbor-next" href="<?= Url::log($nextLog['gid']) ?>" rel="next">
                    <span class="neighbor-label">下一篇</span>
                    <span class="neighbor-title"><?= $nextLog['title'] ?></span>
                </a>
                <?php else: ?>
                <span class="neighbor-card is-empty"></span>
                <?php endif; ?>
            </nav>
            <?php endif; ?>

            <div class="log-related">
                <?php doAction('log_related', (int)$logid); ?>
            </div>

            <?php if ($allow_remark === 'y'): ?>
                <?php include THEME_PATH . 'partials/comments.php'; ?>
            <?php endif; ?>
        </div>

        <?php if ($layout !== 'single'): ?>
            <?php include View::getView('sidebar'); ?>
        <?php endif; ?>
    </div>
</section>

<?php include View::getView('footer'); ?>
