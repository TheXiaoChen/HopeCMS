<?php
/**
 * Hope CMS 后台头部快捷入口
 */
defined('HOPE_ROOT') || exit('access denied!');

function hope_header_shortcuts_catalog() {
    return [
        ['id' => 'article_edit', 'group' => '内容', 'label' => '写文章',     'icon' => 'fa-pen text-success',           'href' => '?act=article_edit', 'target' => '_blank'],
        ['id' => 'article',      'group' => '内容', 'label' => '文章管理',   'icon' => 'fa-file-lines text-secondary',  'href' => '?act=article', 'target' => ''],
        ['id' => 'page',         'group' => '内容', 'label' => '页面管理',   'icon' => 'fa-file text-secondary',        'href' => '?act=page', 'target' => ''],
        ['id' => 'comment',      'group' => '内容', 'label' => '评论管理',   'icon' => 'fa-comment text-info',          'href' => '?act=comment', 'target' => ''],
        ['id' => 'link',         'group' => '内容', 'label' => '链接管理',   'icon' => 'fa-link text-info',             'href' => '?act=link', 'target' => ''],
        ['id' => 'twitter',      'group' => '内容', 'label' => '微语管理',   'icon' => 'fa-feather text-info',          'href' => '?act=twitter', 'target' => ''],
        ['id' => 'theme',        'group' => '外观', 'label' => '主题管理',   'icon' => 'fa-palette text-warning',       'href' => '?act=theme', 'target' => ''],
        ['id' => 'menu',         'group' => '外观', 'label' => '导航菜单',   'icon' => 'fa-bars text-warning',          'href' => '?act=menu', 'target' => ''],
        ['id' => 'setting',      'group' => '系统', 'label' => '系统设置',   'icon' => 'fa-gear text-dark',             'href' => '?act=setting', 'target' => ''],
        ['id' => 'ai_studio',    'group' => '系统', 'label' => 'AI 工作台',  'icon' => 'fa-comments text-primary',      'href' => '?act=ai_studio', 'target' => ''],
        ['id' => 'ai_config',    'group' => '系统', 'label' => 'AI 配置',    'icon' => 'fa-sliders text-info',          'href' => '?act=ai_studio#ai-tab-config', 'target' => ''],
        ['id' => 'pay',          'group' => '系统', 'label' => '支付设置',   'icon' => 'fa-credit-card text-success',   'href' => '?act=pay', 'target' => ''],
        ['id' => 'upload',       'group' => '系统', 'label' => '媒体库',     'icon' => 'fa-image text-warning',         'href' => '?act=upload', 'target' => ''],
        ['id' => 'user',         'group' => '系统', 'label' => '用户管理',   'icon' => 'fa-users text-primary',         'href' => '?act=user', 'target' => ''],
        ['id' => 'apply',        'group' => '扩展', 'label' => '应用中心',   'icon' => 'fa-store text-danger',          'href' => '?act=apply', 'target' => ''],
        ['id' => 'plugin',       'group' => '扩展', 'label' => '插件管理',   'icon' => 'fa-puzzle-piece text-danger',   'href' => '?act=plugin', 'target' => ''],
    ];
}

function hope_header_shortcuts_default_ids() {
    return ['article_edit', 'article', 'upload', 'setting'];
}

function hope_get_header_shortcuts_raw_option() {
    $raw = Option::get('header_shortcuts');
    if ($raw === '' || $raw === null) {
        $raw = Option::get('ai_header_shortcuts');
    }
    return $raw;
}

function hope_get_header_shortcuts_enabled() {
    $raw = hope_get_header_shortcuts_raw_option();
    if ($raw === '' || $raw === null) {
        return hope_header_shortcuts_default_ids();
    }
    $arr = json_decode($raw, true);
    if (!is_array($arr)) {
        return hope_header_shortcuts_default_ids();
    }
    $valid = array_column(hope_header_shortcuts_catalog(), 'id');
    return array_values(array_intersect($arr, $valid));
}

function hope_get_header_shortcuts_links() {
    $enabled = array_flip(hope_get_header_shortcuts_enabled());
    $items = [];
    foreach (hope_header_shortcuts_catalog() as $item) {
        if (isset($enabled[$item['id']])) {
            $items[] = $item;
        }
    }
    return $items;
}

function hope_sanitize_header_shortcuts($raw) {
    if (is_array($raw)) {
        $arr = $raw;
    } else {
        $arr = json_decode(stripslashes((string)$raw), true);
        if (!is_array($arr)) {
            $arr = json_decode((string)$raw, true);
        }
    }
    if (!is_array($arr)) {
        return hope_header_shortcuts_default_ids();
    }
    $valid = array_column(hope_header_shortcuts_catalog(), 'id');
    $out = [];
    foreach ($arr as $id) {
        $id = trim((string)$id);
        if ($id !== '' && in_array($id, $valid, true) && !in_array($id, $out, true)) {
            $out[] = $id;
        }
    }
    return $out ?: hope_header_shortcuts_default_ids();
}

function hope_save_header_shortcuts_from_request() {
    if (!User::isAdmin()) {
        Output::error('权限不足');
    }
    if (!isset($_SESSION)) {
        session_start();
    }
    $token = (string)($_REQUEST['token'] ?? '');
    $sessionToken = LoginAuth::getToken();
    if ($sessionToken === '' || $token === '' || !hash_equals($sessionToken, $token)) {
        Output::authError('安全token校验失败，请刷新页面后重试');
    }

    $raw = '[]';
    if (isset($_POST['header_shortcuts'])) {
        $raw = (string)$_POST['header_shortcuts'];
    } elseif (isset($_POST['ai_header_shortcuts'])) {
        $raw = (string)$_POST['ai_header_shortcuts'];
    }

    $ids = hope_sanitize_header_shortcuts($raw);
    Option::updateOption('header_shortcuts', json_encode($ids, JSON_UNESCAPED_UNICODE));
    $CACHE = Cache::getInstance();
    $CACHE->updateCache('options');
    Output::ok();
}
