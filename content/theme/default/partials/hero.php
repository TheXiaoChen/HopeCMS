<?php
defined('HOPE_ROOT') || exit('access denied!');

if (!nova_show_hero()) {
    return;
}

$title = trim(_hope('hero_title', '轻量 优雅 强大'));
$desc = trim(_hope('hero_desc', 'Hope CMS 助你快速搭建博客与内容站点，专注写作、发布与运营。'));
$cta1 = trim(_hope('hero_cta1_text', '开始阅读'));
$cta1Url = trim(_hope('hero_cta1_url', '#latest'));
$cta2 = trim(_hope('hero_cta2_text', '立即体验'));
$cta2Url = trim(_hope('hero_cta2_url', nova_url('register')));
$bg = trim(_hope('hero_bg', ''));
?>
<section class="club-hero<?= $bg !== '' ? ' has-bg' : '' ?>"<?= $bg !== '' ? ' style="--hero-bg:url(\'' . htmlspecialchars($bg, ENT_QUOTES) . '\')"' : '' ?>>
    <div class="club-hero-glow" aria-hidden="true"></div>
    <div class="club-hero-grid" aria-hidden="true"></div>
    <div class="container club-hero-inner">
        <h1 class="club-hero-title">
            <?php
            // 中间词主色 — 「轻量 [优雅] 强大」
            $parts = preg_split('/\s+/u', $title);
            $parts = array_values(array_filter($parts, function ($p) { return $p !== ''; }));
            $n = count($parts);
            if ($n >= 3):
                echo htmlspecialchars($parts[0]) . ' ';
                echo '<em>' . htmlspecialchars($parts[1]) . '</em> ';
                echo htmlspecialchars(implode(' ', array_slice($parts, 2)));
            elseif ($n === 2):
                echo htmlspecialchars($parts[0]) . ' <em>' . htmlspecialchars($parts[1]) . '</em>';
            else:
                echo htmlspecialchars($title);
            endif;
            ?>
        </h1>
        <?php if ($desc !== ''): ?>
            <p class="club-hero-desc"><?= htmlspecialchars($desc) ?></p>
        <?php endif; ?>
        <div class="club-hero-actions">
            <?php if ($cta1 !== ''): ?>
                <a class="btn btn-primary" href="<?= htmlspecialchars($cta1Url !== '' ? $cta1Url : '#latest') ?>"><?= htmlspecialchars($cta1) ?></a>
            <?php endif; ?>
            <?php if ($cta2 !== ''): ?>
                <a class="btn btn-ghost" href="<?= htmlspecialchars($cta2Url !== '' ? $cta2Url : nova_url('register')) ?>"><?= htmlspecialchars($cta2) ?></a>
            <?php endif; ?>
        </div>
    </div>
</section>
