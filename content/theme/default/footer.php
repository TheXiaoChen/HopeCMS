<?php
defined('HOPE_ROOT') || exit('access denied!');

$footer_text = trim(_hope('footer_text', ''));
$footer_links = trim(_hope('footer_links', ''));
$footer_dev = trim(_hope('footer_dev_links', ''));

$parse_footer_links = static function ($raw, $fallback) {
    $src = $raw !== '' ? $raw : $fallback;
    $rows = [];
    foreach (preg_split('/\r\n|\r|\n/', $src) as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $parts = array_map('trim', explode('|', $line));
        $label = $parts[0] ?? '';
        $href = $parts[1] ?? '#';
        if ($label === '') {
            continue;
        }
        $rows[] = ['label' => $label, 'href' => $href];
    }
    return $rows;
};

$quick_links = $parse_footer_links(
    $footer_links,
    "首页|" . SITE_URL . "\n最新文章|" . SITE_URL . "\n全站搜索|" . SITE_URL . "index.php?keyword="
);
$dev_links = $parse_footer_links(
    $footer_dev,
    "官方文档|#\n问题反馈|#\nAPI 接口|#"
);

$tag_items = function_exists('nova_tags') ? nova_tags((int)_hope('sidebar_tag_num', 12)) : [];
?>
</main>
<footer class="site-footer">
    <div class="container footer-inner">
        <div class="footer-grid">
            <div class="footer-brand">
                <a href="<?= SITE_URL ?>" class="footer-brand-link">
                    <span class="brand-mark" aria-hidden="true"></span>
                    <span class="footer-logo"><?= nova_brand() ?></span>
                </a>
                <?php if ($footer_text !== ''): ?>
                    <p class="footer-desc"><?= nl2br(htmlspecialchars($footer_text)) ?></p>
                <?php elseif (($footer_info = trim(Option::get('footer_info'))) !== ''): ?>
                    <div class="footer-desc"><?= $footer_info ?></div>
                <?php endif; ?>
                <?php if (!empty($tag_items)): ?>
                <div class="footer-tags" aria-label="热门标签">
                    <?php foreach (array_slice($tag_items, 0, 8) as $tag): ?>
                        <a href="<?= Url::tag(rawurlencode($tag['tagname'])) ?>"><?= htmlspecialchars($tag['tagname']) ?></a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <nav class="footer-col" aria-label="快速链接">
                <h3>快速链接</h3>
                <ul class="footer-link-list">
                    <?php foreach ($quick_links as $row): ?>
                    <li><a href="<?= htmlspecialchars($row['href']) ?>"><?= htmlspecialchars($row['label']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>

            <nav class="footer-col" aria-label="开发者资源">
                <h3>开发者资源</h3>
                <ul class="footer-link-list">
                    <?php foreach ($dev_links as $row): ?>
                    <li><a href="<?= htmlspecialchars($row['href']) ?>"><?= htmlspecialchars($row['label']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        </div>

        <div class="footer-bottom">
            <p class="footer-copy">
                &copy; <?= date('Y') ?> <?= htmlspecialchars($sitename) ?>
                · Powered by <a href="http://www.hopecms.cn" rel="noopener">Hope CMS</a>
                <?php if (($icp = trim(Option::get('icp'))) !== ''): ?>
                    · <?= $icp ?>
                <?php endif; ?>
            </p>
            <div class="footer-legal">
                <a href="<?= SITE_URL ?>">隐私政策</a>
                <a href="<?= SITE_URL ?>">服务条款</a>
                <a href="#main-content">回到顶部</a>
            </div>
        </div>
    </div>
</footer>
<script src="<?= nova_asset('js/theme.js') ?>" defer></script>
<?php doAction('index_body_end'); ?>
<?php doAction('index_footer') ?>
</body>
</html>
