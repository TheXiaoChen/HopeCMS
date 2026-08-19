<?php
defined('HOPE_ROOT') || exit('access denied!');

$sidebar_about = trim(_hope('sidebar_about', ''));
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
?>
<aside class="sidebar" aria-label="侧边栏">
    <?php doAction('index_sidebar'); ?>
    <?php if (!hope_render_sidebar_widgets('sidebar')): ?>

        <div class="widget widget-search">
            <h3 class="widget-title">搜索</h3>
            <form class="widget-search-form" method="get" action="<?= SITE_URL ?>index.php" role="search">
                <input type="search" name="keyword" placeholder="输入关键词…" value="<?= htmlspecialchars($keyword) ?>" aria-label="搜索">
                <button type="submit" aria-label="搜索">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </button>
            </form>
        </div>

        <?php if ($sidebar_about !== ''): ?>
        <div class="widget widget-about">
            <h3 class="widget-title">关于站点</h3>
            <p class="widget-text"><?= nl2br(htmlspecialchars($sidebar_about)) ?></p>
        </div>
        <?php endif; ?>

        <?php $tags = nova_tags((int)_hope('sidebar_tag_num', 18)); if ($tags): ?>
        <div class="widget widget-tags">
            <h3 class="widget-title">
                <span class="widget-title-ico" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                </span>
                热门标签
            </h3>
            <div class="tag-cloud">
                <?php foreach ($tags as $tag): ?>
                <a href="<?= Url::tag(rawurlencode($tag['tagname'])) ?>" class="tag-item">#<?= htmlspecialchars($tag['tagname']) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="widget widget-recommend">
            <h3 class="widget-title widget-title--line">推荐阅读</h3>
            <ul class="widget-posts widget-posts--thumb">
                <?php
                $recent = nova_recent_logs((int)_hope('sidebar_recent_num', 6));
                if ($recent):
                    foreach ($recent as $post):
                        $has_cover = !empty($post['log_cover']);
                        $letter = mb_substr(strip_tags($post['log_title']), 0, 1);
                ?>
                <li>
                    <a class="widget-post" href="<?= $post['log_url'] ?>">
                        <span class="widget-post-thumb<?= $has_cover ? '' : ' is-placeholder' ?>">
                            <?php if ($has_cover): ?>
                                <img src="<?= htmlspecialchars($post['log_cover']) ?>" alt="" loading="lazy">
                            <?php else: ?>
                                <span><?= htmlspecialchars($letter) ?></span>
                            <?php endif; ?>
                        </span>
                        <span class="widget-post-body">
                            <span class="widget-post-title"><?= $post['log_title'] ?></span>
                        </span>
                    </a>
                </li>
                <?php
                    endforeach;
                else:
                ?>
                <li class="widget-empty">暂无文章</li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="widget widget-sorts">
            <h3 class="widget-title widget-title--line">分类导航</h3>
            <ul class="widget-list">
                <?php foreach (nova_sidebar_sorts() as $sort): ?>
                <li>
                    <a href="<?= Url::sort($sort['sid']) ?>">
                        <span><?= htmlspecialchars($sort['sortname']) ?></span>
                        <em><?= (int)$sort['lognum'] ?></em>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</aside>
