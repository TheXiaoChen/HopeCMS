<?php
/*@name 默认页面*/
defined('HOPE_ROOT') || exit('access denied!');
$layout = nova_layout();
?>
<section class="club-band">
    <div class="container club-band-inner <?= nova_layout_class() ?> page-static">
        <?php if ($layout === 'triple'): ?>
            <?php include THEME_PATH . 'partials/sidebar-left.php'; ?>
        <?php endif; ?>
        <section class="content-main">
            <article class="article-detail">
                <header class="article-header">
                    <h1><?= htmlspecialchars($log_title) ?></h1>
                </header>
                <div class="article-content"><?= $log_content ?></div>
            </article>
        </section>
        <?php if ($layout !== 'single'): ?>
            <?php include View::getView('sidebar'); ?>
        <?php endif; ?>
    </div>
</section>
<?php include View::getView('footer'); ?>
