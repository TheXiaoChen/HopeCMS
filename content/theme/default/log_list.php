<?php
/**
 * 文章列表（首页 / 分类 / 搜索 / 归档）
 * 层次：Hero(H1) → 内容带(灰底) → 主栏(H2+列表) | 侧栏(独立卡片)
 */
defined('HOPE_ROOT') || exit('access denied!');

$list_title = isset($sortName) ? $sortName : (isset($tag) ? '标签：' . $tag : (isset($keyword) ? '搜索：' . $keyword : (isset($record) ? $record . ' 归档' : '最新发布')));
$page_url = pagination($lognum, $index_lognum, $page, $pageurl, '', nova_pagination_tpl());
$layout = nova_layout();
$is_front = !isset($sortName) && !isset($tag) && !isset($keyword) && !isset($record);
$list_mode = isset($nova_list_mode_override) ? $nova_list_mode_override : nova_list_mode();
?>

<?php if ($is_front && nova_show_hero()): ?>
    <?php include THEME_PATH . 'partials/hero.php'; ?>
<?php endif; ?>

<section class="club-band<?= $is_front ? ' club-band--home' : '' ?>" id="latest">
    <div class="container club-band-inner <?= nova_layout_class() ?>">
        <?php if ($layout === 'triple'): ?>
            <?php include THEME_PATH . 'partials/sidebar-left.php'; ?>
        <?php endif; ?>

        <div class="content-main">
            <header class="list-header">
                <div class="list-header-top">
                    <?php if ($is_front): ?>
                        <h2 class="list-title"><?= htmlspecialchars($list_title) ?></h2>
                        <a class="list-more" href="<?= SITE_URL ?>">查看更多 <span class="list-more-ico" aria-hidden="true">→</span></a>
                    <?php else: ?>
                        <h1 class="list-title"><?= htmlspecialchars($list_title) ?></h1>
                    <?php endif; ?>
                </div>
                <?php if (!$is_front && isset($sort['description']) && $sort['description'] !== ''): ?>
                    <p class="list-desc"><?= htmlspecialchars($sort['description']) ?></p>
                <?php endif; ?>
            </header>

            <?php if (!empty($logs)): ?>
                <?php include THEME_PATH . 'partials/post-list.php'; ?>
                <?php if ($page_url): ?>
                <nav class="pagination" aria-label="分页"><?= $page_url ?></nav>
                <?php endif; ?>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon" aria-hidden="true">◇</div>
                <p>暂无文章</p>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($layout !== 'single'): ?>
            <?php include View::getView('sidebar'); ?>
        <?php endif; ?>
    </div>
</section>

<?php include View::getView('footer'); ?>
