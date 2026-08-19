<?php
defined('HOPE_ROOT') || exit('access denied!');

function nova_is_on($key, $default = true) {
    $val = _hope($key, $default);
    if ($val === false || $val === '' || $val === '0' || $val === 0 || $val === 'n' || $val === 'off') {
        return false;
    }
    if ($val === true || $val === '1' || $val === 1 || $val === 'y' || $val === 'on') {
        return true;
    }
    return !empty($val);
}

function nova_brand() {
    return htmlspecialchars(_hope('site_brand', Option::get('sitename')));
}

function nova_tagline() {
    return htmlspecialchars(_hope('site_tagline', Option::get('bloginfo')));
}

function nova_asset($path) {
    $cdn = rtrim(trim(_hope('cdn_base', '')), '/');
    $base = $cdn !== '' ? $cdn . '/' : THEME_URL;
    return $base . ltrim($path, '/');
}

function nova_layout() {
    $layout = _hope('site_layout', 'double');
    return in_array($layout, ['single', 'double', 'triple'], true) ? $layout : 'double';
}

function nova_list_mode() {
    $mode = _hope('list_mode', 'card');
    $allowed = ['standard', 'card', 'grid', 'seamless'];
    return in_array($mode, $allowed, true) ? $mode : 'card';
}

function nova_theme_default() {
    $mode = _hope('color_scheme', 'auto');
    return in_array($mode, ['light', 'dark', 'auto'], true) ? $mode : 'auto';
}

function nova_primary_color() {
    $color = trim(_hope('primary_color', '#3b82f6'));
    return preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? $color : '#3b82f6';
}

function nova_nav_sorts() {
    global $CACHE;
    $limit = (int)_hope('nav_sort_limit', 8);
    $sort_cache = $CACHE->readCache('sort');
    $list = [];
    foreach ($sort_cache as $sid => $sort) {
        if (!empty($sort['pid'])) {
            continue;
        }
        $list[] = [
            'sid'      => $sid,
            'sortname' => $sort['sortname'],
            'taxis'    => (int)($sort['taxis'] ?? 0),
        ];
    }
    usort($list, function ($a, $b) {
        return $a['taxis'] <=> $b['taxis'];
    });
    return array_slice($list, 0, max(1, $limit));
}

function nova_sort_name($sortid) {
    global $CACHE;
    $sortid = (int)$sortid;
    $sort_cache = $CACHE->readCache('sort');
    return isset($sort_cache[$sortid]['sortname']) ? $sort_cache[$sortid]['sortname'] : '';
}

function nova_excerpt($html, $len = 140) {
    return extractHtmlData(strip_tags((string)$html), $len);
}

function nova_author_name($uid) {
    global $CACHE;
    $user_cache = $CACHE->readCache('user');
    if (isset($user_cache[$uid]['nickname']) && $user_cache[$uid]['nickname'] !== '') {
        return $user_cache[$uid]['nickname'];
    }
    return isset($user_cache[$uid]['name']) ? $user_cache[$uid]['name'] : '匿名';
}

function nova_photo_url($photo) {
    if ($photo === '') {
        return '';
    }
    hope_load_user_center();
    if (function_exists('hope_user_center_photo_url')) {
        return hope_user_center_photo_url($photo);
    }
    if (strpos($photo, 'http') === 0) {
        return $photo;
    }
    return SITE_URL . ltrim(str_replace('../', '', $photo), '/');
}

function nova_author_avatar($uid) {
    global $CACHE;
    $uid = (int)$uid;
    $user_cache = $CACHE->readCache('user');
    if (empty($user_cache[$uid]['avatar'])) {
        return '';
    }
    return nova_photo_url($user_cache[$uid]['avatar']);
}

function nova_sidebar_sorts() {
    global $CACHE;
    $sort_cache = $CACHE->readCache('sort');
    $list = [];
    foreach ($sort_cache as $sid => $sort) {
        if (!empty($sort['pid'])) {
            continue;
        }
        $list[] = [
            'sid'      => $sid,
            'sortname' => $sort['sortname'],
            'lognum'   => (int)($sort['lognum'] ?? 0),
            'taxis'    => (int)($sort['taxis'] ?? 0),
        ];
    }
    usort($list, function ($a, $b) {
        return $a['taxis'] <=> $b['taxis'];
    });
    return $list;
}

function hope_site_forum_sorts() {
    global $CACHE;
    $sort_cache = $CACHE->readCache('sort');
    $root = (int)_hope('forum_sort_id', 0);
    $list = [];
    if ($root > 0 && isset($sort_cache[$root])) {
        $list[] = $sort_cache[$root];
        if (!empty($sort_cache[$root]['children'])) {
            foreach ($sort_cache[$root]['children'] as $cid) {
                if (isset($sort_cache[$cid])) {
                    $list[] = $sort_cache[$cid];
                }
            }
        }
    } else {
        foreach ($sort_cache as $sid => $row) {
            if (!empty($row['pid'])) {
                continue;
            }
            $list[] = $row;
        }
    }
    return $list;
}

function hope_site_forum_sort_ids() {
    $ids = [];
    foreach (hope_site_forum_sorts() as $row) {
        if (!empty($row['sid'])) {
            $ids[] = (int)$row['sid'];
        }
    }
    return array_values(array_unique($ids));
}

function nova_recent_logs($limit = 5) {
    $Log_Model = new Log_Model();
    return $Log_Model->getPublicLogs(['order' => 'date DESC'], 1, max(1, (int)$limit));
}

function nova_tags($limit = 20) {
    $Tag_Model = new Tag_Model();
    $tags = $Tag_Model->getTags();
    if (!is_array($tags)) {
        return [];
    }
    usort($tags, function ($a, $b) {
        return (int)($b['usenum'] ?? 0) <=> (int)($a['usenum'] ?? 0);
    });
    return array_slice($tags, 0, max(1, (int)$limit));
}

function nova_pagination_tpl() {
    return [
        'first'  => '',
        'last'   => '',
        'dot'    => '<span class="page-dot">…</span>',
        'prev'   => '<a class="page-link page-prev" href="{url}" aria-label="上一页">←</a>',
        'next'   => '<a class="page-link page-next" href="{url}" aria-label="下一页">→</a>',
        'num'    => '<a class="page-link" href="{url}">{num}</a>',
        'active' => '<span class="page-link is-active" aria-current="page">{num}</span>',
    ];
}

function nova_url($key) {
    if (in_array($key, ['login', 'register', 'user', 'auth_api'], true)) {
        return hope_resolve_user_url($key);
    }
    return SITE_URL;
}

function nova_nav_auth() {
    if (defined('UID') && UID > 0) {
        $User_Model = new User_Model();
        $user = $User_Model->getOneUser(UID);
        $name = !empty($user['nickname']) ? $user['nickname'] : $user['name'];
        $raw_photo = !empty($user['photo']) ? html_entity_decode($user['photo'], ENT_QUOTES, 'UTF-8') : '';
        $avatar = nova_photo_url($raw_photo);
        $user_url = nova_url('user');
        echo '<a href="' . htmlspecialchars($user_url) . '" class="nav-user">';
        if ($avatar) {
            echo '<img class="nav-avatar" src="' . htmlspecialchars($avatar) . '" alt="">';
        } else {
            echo '<span class="nav-avatar nav-avatar-text">' . htmlspecialchars(mb_substr($name, 0, 1)) . '</span>';
        }
        echo '<span class="nav-user-name">' . htmlspecialchars($name) . '</span></a>';
    } else {
        echo '<a href="' . htmlspecialchars(nova_url('login')) . '" class="btn btn-ghost btn-sm">登录</a>';
        echo '<a href="' . htmlspecialchars(nova_url('register')) . '" class="btn btn-primary btn-sm">注册</a>';
    }
}

function nova_slides() {
    $raw = trim(_hope('hero_slides', ''));
    if ($raw === '') {
        return [];
    }
    $slides = [];
    foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $parts = array_map('trim', explode('|', $line));
        $image = $parts[0] ?? '';
        if ($image === '') {
            continue;
        }
        $slides[] = [
            'image' => $image,
            'title' => $parts[1] ?? '',
            'link'  => $parts[2] ?? '',
            'desc'  => $parts[3] ?? '',
        ];
    }
    return array_slice($slides, 0, 6);
}

function nova_show_hero() {
    return nova_is_on('hero_enable', true);
}

function nova_layout_class() {
    $layout = nova_layout();
    return 'layout-' . $layout;
}

function nova_reading_time($content) {
    $text = strip_tags((string)$content);
    $words = mb_strlen(preg_replace('/\s+/u', '', $text));
    return max(1, (int)ceil($words / 400));
}

function nova_seo_meta() {
    $extra = trim(_hope('seo_extra_meta', ''));
    if ($extra !== '') {
        echo $extra . "\n";
    }
}

function nova_user_badge($uid) {
    global $CACHE;
    $uid = (int)$uid;
    $user_cache = $CACHE->readCache('user');
    $role = isset($user_cache[$uid]['role']) ? $user_cache[$uid]['role'] : '';
    if ($role === 'admin') {
        return '<span class="user-badge badge-admin">管理员</span>';
    }
    if ($role === 'writer') {
        return '<span class="user-badge badge-writer">创作者</span>';
    }
    return '';
}

/**
 * 递归渲染评论树（含回复）
 */
function nova_render_comment_tree($comments_data, $logid, $args = []) {
    if (empty($comments_data['commentStacks'])) {
        return;
    }
    $comments = $comments_data['comments'];
    echo '<div class="comments-list">';
    foreach ($comments_data['commentStacks'] as $cid) {
        nova_render_comment_item($comments, (int)$cid, (int)$logid, $args, 0);
    }
    echo '</div>';
}

function nova_render_comment_item($comments, $cid, $logid, $args, $depth = 0) {
    if (empty($comments[$cid])) {
        return;
    }
    $c = $comments[$cid];
    $verifyCode = $args['verifyCode'] ?? '';
    $allowReply = $depth < 4;
    $avatar = mb_substr(strip_tags($c['poster']), 0, 1);
    ?>
    <article class="comment-item" id="comment-<?= (int)$cid ?>" data-cid="<?= (int)$cid ?>">
        <div class="comment-avatar" aria-hidden="true"><?= htmlspecialchars($avatar) ?></div>
        <div class="comment-main">
            <div class="comment-head">
                <strong class="comment-author"><?= $c['poster'] ?></strong>
                <?php if (!empty($c['url'])): ?>
                    <a href="<?= htmlspecialchars($c['url']) ?>" class="comment-url" rel="nofollow noopener" target="_blank">网站</a>
                <?php endif; ?>
                <time datetime="<?= htmlspecialchars($c['date']) ?>"><?= $c['date'] ?></time>
            </div>
            <div class="comment-body"><?= $c['content'] ?></div>
            <?php if ($allowReply): ?>
            <div class="comment-actions">
                <button type="button" class="comment-reply-btn" data-cid="<?= (int)$cid ?>" data-poster="<?= htmlspecialchars(strip_tags($c['poster']), ENT_QUOTES) ?>">回复</button>
            </div>
            <div class="comment-reply-box" id="reply-box-<?= (int)$cid ?>" hidden>
                <form method="post" action="<?= SITE_URL ?>index.php?action=addcom" class="comment-reply-form">
                    <textarea name="comment" rows="3" placeholder="回复 @<?= htmlspecialchars(strip_tags($c['poster'])) ?>…" required></textarea>
                    <input type="hidden" name="gid" value="<?= (int)$logid ?>">
                    <input type="hidden" name="pid" value="<?= (int)$cid ?>">
                    <?php if (!defined('UID') || !UID): ?>
                    <div class="comment-fields comment-fields--inline">
                        <input type="text" name="comname" placeholder="昵称" required>
                        <input type="email" name="commail" placeholder="邮箱（可选）">
                    </div>
                    <?php endif; ?>
                    <?php if ($verifyCode !== ''): ?>
                    <div class="comment-captcha"><?= $verifyCode ?></div>
                    <?php endif; ?>
                    <div class="comment-reply-actions">
                        <button type="button" class="btn btn-ghost btn-sm comment-cancel-reply">取消</button>
                        <button type="submit" class="btn btn-primary btn-sm">发表回复</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
            <?php if (!empty($c['children'])): ?>
            <div class="comment-children">
                <?php foreach ($c['children'] as $childId): ?>
                    <?php nova_render_comment_item($comments, (int)$childId, $logid, $args, $depth + 1); ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </article>
    <?php
}

function nova_comment_form($logid, $verifyCode = '', $opts = []) {
    $is_login = defined('UID') && UID > 0;
    $ckname = $opts['ckname'] ?? '';
    $ckmail = $opts['ckmail'] ?? '';
    $ckurl = $opts['ckurl'] ?? '';
    ?>
    <form method="post" action="<?= SITE_URL ?>index.php?action=addcom" class="comment-form" id="comment-form-main">
        <div class="comment-form-head">
            <strong>发表评论</strong>
            <span class="comment-reply-hint" id="comment-reply-hint" hidden></span>
            <button type="button" class="comment-cancel-main-reply" id="comment-cancel-main-reply" hidden>取消回复</button>
        </div>
        <textarea name="comment" id="comment-main-text" rows="4" placeholder="写下你的想法…" required></textarea>
        <input type="hidden" name="gid" value="<?= (int)$logid ?>">
        <input type="hidden" name="pid" value="0" id="comment-main-pid">
        <?php if (!$is_login): ?>
        <div class="comment-fields">
            <input type="text" name="comname" placeholder="昵称" required value="<?= htmlspecialchars($ckname) ?>">
            <input type="email" name="commail" placeholder="邮箱（可选）" value="<?= htmlspecialchars($ckmail) ?>">
            <input type="url" name="comurl" placeholder="网址（可选）" value="<?= htmlspecialchars($ckurl) ?>">
        </div>
        <?php endif; ?>
        <?php if ($verifyCode !== ''): ?>
        <div class="comment-captcha"><?= $verifyCode ?></div>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary">发表评论</button>
    </form>
    <?php
}

function nova_blog_url($key) {
    return nova_url($key);
}

function hope_blog_brand() {
    return nova_brand();
}

function hope_blog_nav_sorts() {
    return nova_nav_sorts();
}

function hope_blog_nav_auth() {
    nova_nav_auth();
}

function hope_blog_url($key) {
    return nova_url($key);
}

function hope_blog_pagination_tpl() {
    return nova_pagination_tpl();
}

function hope_blog_sort_name($sortid) {
    return nova_sort_name($sortid);
}

function hope_blog_excerpt($html, $len = 120) {
    return nova_excerpt($html, $len);
}

function hope_blog_author_name($uid) {
    return nova_author_name($uid);
}

function hope_blog_sidebar_sorts() {
    return nova_sidebar_sorts();
}

function hope_blog_recent_logs($limit = 5) {
    return nova_recent_logs($limit);
}

function hope_blog_tags($limit = 15) {
    return nova_tags($limit);
}

function hope_nav_current_path() {
    $path = '';
    if (class_exists('Dispatcher') && method_exists('Dispatcher', 'setPath')) {
        $path = (string)Dispatcher::setPath();
    }
    return rtrim(SITE_URL . trim($path, '/'), '/');
}

function hope_nav_url_is_active($url, $currentPath = null) {
    if ($url === '') {
        return false;
    }
    if ($currentPath === null) {
        $currentPath = hope_nav_current_path();
    }
    $target = rtrim($url, '/');
    if ($target === '' || $currentPath === '') {
        return false;
    }
    if ($currentPath === $target) {
        return true;
    }
    // 首页单独判断，避免所有子路径都高亮首页
    $home = rtrim(SITE_URL, '/');
    if ($target === $home) {
        return $currentPath === $home;
    }
    return strpos($currentPath . '/', $target . '/') === 0;
}

function hope_render_nav_items($items, $link_class = 'nav-link', $depth = 0) {
    $currentPath = hope_nav_current_path();
    foreach ($items as $item) {
        $url = Url::menu((int)$item['type'], $item['typeId'] ?? 0, $item['url'] ?? '');
        $target = ($item['target'] ?? 'n') === 'y' ? '_blank' : '_self';
        $name = $item['name'] ?? '';
        if ($name === '' || $url === '') {
            continue;
        }
        $children = !empty($item['child']) && is_array($item['child']) ? $item['child'] : [];
        $hasChildren = !empty($children);
        $isActive = hope_nav_url_is_active($url, $currentPath);
        if (!$isActive && $hasChildren) {
            foreach ($children as $child) {
                $childUrl = Url::menu((int)($child['type'] ?? 0), $child['typeId'] ?? 0, $child['url'] ?? '');
                if (hope_nav_url_is_active($childUrl, $currentPath)) {
                    $isActive = true;
                    break;
                }
            }
        }

        $itemClass = 'nav-item' . ($hasChildren ? ' has-children' : '') . ($isActive ? ' is-active' : '');
        $linkCls = $link_class . ($isActive ? ' is-active' : '');
        echo '<div class="' . htmlspecialchars($itemClass) . '">';
        echo '<a href="' . htmlspecialchars($url) . '" class="' . htmlspecialchars($linkCls) . '" target="' . $target . '"'
            . ($hasChildren ? ' aria-haspopup="true" aria-expanded="false"' : '')
            . ($isActive ? ' aria-current="page"' : '') . '>';
        echo htmlspecialchars($name);
        if ($hasChildren) {
            echo '<span class="nav-caret" aria-hidden="true"></span>';
        }
        echo '</a>';
        if ($hasChildren) {
            echo '<div class="nav-dropdown" role="menu">';
            foreach ($children as $child) {
                $childUrl = Url::menu((int)($child['type'] ?? 0), $child['typeId'] ?? 0, $child['url'] ?? '');
                $childName = $child['name'] ?? '';
                if ($childName === '' || $childUrl === '') {
                    continue;
                }
                $childTarget = ($child['target'] ?? 'n') === 'y' ? '_blank' : '_self';
                $childActive = hope_nav_url_is_active($childUrl, $currentPath);
                $childClass = 'nav-dropdown-link' . ($childActive ? ' is-active' : '');
                echo '<a href="' . htmlspecialchars($childUrl) . '" class="' . htmlspecialchars($childClass) . '" target="' . $childTarget . '" role="menuitem"'
                    . ($childActive ? ' aria-current="page"' : '') . '>';
                echo htmlspecialchars($childName);
                echo '</a>';
            }
            echo '</div>';
        }
        echo '</div>';
    }
}

function hope_render_main_nav($link_class = 'nav-link') {
    $items = hope_menu_main_nav_items();
    if (empty($items)) {
        $currentPath = hope_nav_current_path();
        foreach (nova_nav_sorts() as $nav) {
            $url = Url::sort($nav['sid']);
            $active = hope_nav_url_is_active($url, $currentPath);
            echo '<div class="nav-item' . ($active ? ' is-active' : '') . '">';
            echo '<a href="' . htmlspecialchars($url) . '" class="' . htmlspecialchars($link_class . ($active ? ' is-active' : '')) . '"'
                . ($active ? ' aria-current="page"' : '') . '>';
            echo htmlspecialchars($nav['sortname']);
            echo '</a></div>';
        }
        return;
    }
    hope_render_nav_items($items, $link_class);
}
