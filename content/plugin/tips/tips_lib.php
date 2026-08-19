<?php
defined('HOPE_ROOT') || exit('access denied!');

function tips_default_list() {
    return [
        '若站点根目录仍存在 install.php，安装完成后请立即删除',
        '写文章时系统会自动保存草稿，防止意外丢失内容',
        '未发布的文章可以先保存到草稿箱，稍后再继续编辑',
        '大尺寸图片上传时会自动生成缩略图，加快页面加载速度',
        '可以在后台「插件管理」安装扩展，丰富站点功能',
        '发布文章时可设置访问密码，仅授权读者可见',
        'Hope CMS 支持主题切换与个人中心，可在主题设置中调整外观',
        '建议定期备份数据库与 content/upload 目录',
        '推荐使用 Chrome 或 Edge 浏览器，获得更流畅的后台体验',
        '开发插件可参考本插件源码与前台开发文档「插件开发指南」章节',
    ];
}

function tips_get_list() {
    return tips_default_list();
}

function tips_random() {
    $list = tips_get_list();
    if (!$list) {
        return '';
    }
    return $list[array_rand($list)];
}

function tips_asset_url($path) {
    return PLUGIN_URL . 'tips/assets/' . ltrim($path, '/');
}

function tips_page_url($query = []) {
    return Url::plugin('tips', $query);
}

function tips_enqueue_admin_css() {
    echo '<link rel="stylesheet" href="' . htmlspecialchars(tips_asset_url('tips.css?v=4.2')) . '">' . "\n";
}

/** 是否仍使用默认后台入口 admin.php */
function tips_is_default_admin_entry() {
    return defined('SELF') && strtolower((string)SELF) === 'admin.php';
}

/** 默认后台入口安全警告（仅后台首页） */
function tips_render_admin_security_alert() {
    if (!tips_is_default_admin_entry()) {
        return;
    }
    echo '<div class="tips-banner tips-banner--danger" role="alert">'
        . '<span class="tips-banner-icon" aria-hidden="true">⚠️</span>'
        . '<div class="tips-banner-text">'
        . '<strong>安全警告：</strong>检测到当前仍使用默认后台入口 <code>admin.php</code>，极易被恶意扫描与暴力破解。'
        . '请<strong>立即</strong>将网站根目录下的 <code>admin.php</code> 重命名为不易猜测的文件名'
        . '（例如 <code>manage_x8k2.php</code>），并同步更新浏览器收藏地址。'
        . '</div></div>';
}

function tips_render_admin_banner() {
    tips_render_admin_security_alert();
    $text = tips_random();
    if ($text === '') {
        return;
    }
    echo '<div class="tips-banner" role="note">'
        . '<span class="tips-banner-icon" aria-hidden="true">💡</span>'
        . '<span class="tips-banner-text">' . htmlspecialchars($text) . '</span>'
        . '</div>';
}
