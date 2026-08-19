<?php
defined('HOPE_ROOT') || exit('access denied!');

global $CACHE;

$defaults = is_array($widget_config['default'] ?? null) ? $widget_config['default'] : [];
$qq = trim((string)hope_widget_field($fields, 'QQ号码', $defaults['QQ号码'] ?? ''));
$wechat = trim((string)hope_widget_field($fields, '微信二维码', $defaults['微信二维码'] ?? ''));
$bili = trim((string)hope_widget_field($fields, 'B站链接', $defaults['B站链接'] ?? ''));
if ($bili === '') {
    $bili = trim((string)hope_widget_field($fields, 'bili', $defaults['bili'] ?? ''));
}
$douyin = trim((string)hope_widget_field($fields, '抖音二维码', $defaults['抖音二维码'] ?? ''));
$kuaishou = trim((string)hope_widget_field($fields, '快手二维码', $defaults['快手二维码'] ?? ''));

if ($qq === '') {
    $qq = trim((string)_hope('qq', ''));
}

$user_cache = $CACHE->readCache('user');
$sta_cache = $CACHE->readCache('sta');
$uid = 1;
$user = isset($user_cache[$uid]) && is_array($user_cache[$uid]) ? $user_cache[$uid] : [];

$name = !empty($user['name_orig'])
    ? $user['name_orig']
    : (!empty($user['name']) ? html_entity_decode($user['name'], ENT_QUOTES, 'UTF-8') : Option::get('sitename'));
$desRaw = !empty($user['des']) ? $user['des'] : '';
$des = trim(strip_tags(html_entity_decode((string)$desRaw, ENT_QUOTES, 'UTF-8')));
if ($des === '') {
    $des = 'Hope CMS — 轻量优雅的内容管理系统。';
}

$avatar = '';
if (!empty($user['avatar'])) {
    $avatar = function_exists('hope_avatar_url') ? hope_avatar_url($user['avatar'], '') : nova_photo_url($user['avatar']);
} elseif (!empty($user['photo']['src'])) {
    $src = html_entity_decode($user['photo']['src'], ENT_QUOTES, 'UTF-8');
    $avatar = function_exists('hope_avatar_url') ? hope_avatar_url($src, '') : nova_photo_url($src);
}
$letter = mb_substr((string)$name, 0, 1);

$lognum = (int)($sta_cache['lognum'] ?? 0);
$comnum = (int)($sta_cache['comnum'] ?? 0);

$qrList = array_filter([
    '微信' => $wechat,
    '抖音' => $douyin,
    '快手' => $kuaishou,
], static function ($url) {
    return $url !== '';
});

$hasContact = ($qq !== '' || $bili !== '');
?>
<div class="widget widget-blogger">
    <h3 class="widget-title widget-title--line"><?= htmlspecialchars($widget_title ?: '博主信息') ?></h3>

    <div class="blogger-card">
        <div class="blogger-profile">
            <?php if ($avatar !== ''): ?>
                <img class="blogger-avatar" src="<?= htmlspecialchars($avatar) ?>" alt="<?= htmlspecialchars($name) ?>" loading="lazy" width="72" height="72">
            <?php else: ?>
                <span class="blogger-avatar blogger-avatar--text" aria-hidden="true"><?= htmlspecialchars($letter) ?></span>
            <?php endif; ?>
            <div class="blogger-meta">
                <h4 class="blogger-name"><?= htmlspecialchars($name) ?></h4>
                <p class="blogger-des"><?= htmlspecialchars($des) ?></p>
            </div>
        </div>

        <?php if ($lognum > 0 || $comnum > 0): ?>
        <div class="blogger-stats" aria-label="站点数据">
            <div class="blogger-stat">
                <strong><?= $lognum ?></strong>
                <span>文章</span>
            </div>
            <div class="blogger-stat">
                <strong><?= $comnum ?></strong>
                <span>评论</span>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($qrList): ?>
        <div class="blogger-qrs">
            <?php foreach ($qrList as $label => $img): ?>
                <figure class="blogger-qr">
                    <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($label) ?>二维码" loading="lazy">
                    <figcaption><?= htmlspecialchars($label) ?></figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($hasContact): ?>
        <div class="blogger-actions">
            <?php if ($qq !== ''): ?>
                <a class="blogger-btn blogger-btn--primary" href="tencent://message/?uin=<?= htmlspecialchars($qq) ?>&Site=&Menu=yes">联系博主</a>
            <?php endif; ?>
            <?php if ($bili !== ''): ?>
                <a class="blogger-btn" href="<?= htmlspecialchars($bili) ?>" target="_blank" rel="noopener">B站主页</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
