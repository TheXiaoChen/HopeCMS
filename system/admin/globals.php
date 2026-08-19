<?php
/**
 * Hope CMS(希望CMS) global
 * @author LinZiChen <271106735@qq.com>
 * @link http://www.hopecms.cn
 * @copyright 2024-2025 Hope CMS All rights reserved.
 * @license http://www.apache.org/licenses/LICENSE-2.0
 */

$sta_cache = $CACHE->readCache('sta');
$user_cache = $CACHE->readCache('user');
$act = Input::getStrVar('act');
if(in_array($act, ['login', 'reg'])){
    LoginAuth::checkLogged();//判断是否已登录
    $do = Input::postStrVar('do');
    $User_Model = new User_Model();
    require_once HOPE_ROOT . 'system/admin/' . $act . '.php';
}
if ($act == 'logout') {
    LoginAuth::clearAuthCookie();
    if (PHP_VERSION_ID >= 70300) {
        setcookie('HOPE_REMEMBER_USER', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => function_exists('hope_is_https') && hope_is_https(),
            'httponly' => false,
            'samesite' => 'Lax',
        ]);
    } else {
        setcookie('HOPE_REMEMBER_USER', '', time() - 3600, '/');
    }
    Gohref(SITE_URL);
}
LoginAuth::checkLogin();//判断登录状态

if (($act === 'header_shortcuts' && isset($_GET['save']))
    || ($act === 'ai_studio' && isset($_GET['save_shortcuts']))) {
    require_once HOPE_ROOT . 'system/app/service/header_shortcuts.php';
    hope_save_header_shortcuts_from_request();
}

User::checkRolePermission();//判断权限

if (in_array($act, ['data', 'upgrade'], true)) {
    require_once HOPE_ROOT . 'system/admin/' . $act . '.php';
    exit;
}

// 备份/导入/更新缓存：只跑业务脚本，避免进入后台 HTML 视图污染下载内容
if ($act === 'setting' && (
    isset($_GET['backup'])
    || isset($_GET['import'])
    || isset($_GET['Cache'])
    || (isset($_POST['action']) && $_POST['action'] === 'Cache')
)) {
    require_once HOPE_ROOT . 'system/admin/setting.php';
    exit;
}

if ($act == 'save_admin_theme' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    header('Content-Type: application/json; charset=UTF-8');
    LoginAuth::checkTokenJson();
    if (!User::isAdmin()) {
        echo json_encode(['success' => false, 'message' => '权限不足'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        echo json_encode(['success' => false, 'message' => '无效的数据格式'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $allowedColors = ['primary', 'dark', 'info', 'success', 'warning', 'danger'];
    $allowedSidebarTypes = ['bg-white', 'bg-default'];
    $allowedMinimize = ['none', 'pinned', 'hidden'];

    $data = [
        'sidebar_color'   => in_array($input['sidebar_color'] ?? '', $allowedColors, true) ? $input['sidebar_color'] : 'primary',
        'sidebar_type'    => in_array($input['sidebar_type'] ?? '', $allowedSidebarTypes, true) ? $input['sidebar_type'] : 'bg-white',
        'navbar_fixed'    => ($input['navbar_fixed'] ?? 'n') === 'y' ? 'y' : 'n',
        'navbar_minimize' => in_array($input['navbar_minimize'] ?? '', $allowedMinimize, true) ? $input['navbar_minimize'] : 'none',
        'dark_mode'       => ($input['dark_mode'] ?? 'n') === 'y' ? 'y' : 'n',
    ];

    Option::updateOption('backend_theme', serialize($data));
    $CACHE->updateCache('options');

    echo json_encode([
        'success' => true,
        'message' => '主题设置已保存',
        'data'    => $data,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}


if ($act === 'ai') {
    require_once HOPE_ROOT . 'system/admin/ai.php';
    exit;
}

if ($act === 'menu' && (isset($_GET['get_type_list']) || isset($_GET['menu_taxis']))) {
    require_once HOPE_ROOT . 'system/admin/menu.php';
    exit;
}

if(in_array($act, ['article_edit', 'article', 'category', 'category_edit', 'tag', 'page', 'page_edit', 'upload', 'user', 'user_edit', 'theme', 'theme_set', 'link', 'link_edit', 'setting', 'apply', 'plugin', 'comment', 'menu', 'pay', 'ai_studio', 'twitter'])){
	require_once HOPE_ROOT . 'system/admin/' . $act . '.php';
    require_once View::getAdmView('header');
    require_once View::getAdmView($act);
    require_once View::getAdmView('footer');
	View::output();
}elseif($act=='plugin_set'){
    $plugin = Input::getStrVar('plugin');
    if(empty($plugin) || !file_exists(HOPE_ROOT . 'content/plugin/'.$plugin.'/'.$plugin.'_setting.php')){
        show_404_page();
    }
    require_once View::getAdmView('header');
    require_once HOPE_ROOT . 'content/plugin/'.$plugin.'/'.$plugin.'_setting.php';
    plugin_setting_view();
    require_once View::getAdmView('footer');
	View::output();
}elseif($act==''){
    $max_execution_time = ini_get('max_execution_time') ?: '';
    $max_upload_size = ini_get('upload_max_filesize') ?: '';
    $php_ver = PHP_VERSION . ',' . $max_execution_time . 's,' . $max_upload_size;
    if (function_exists('curl_init')) {
        $c = curl_version();
        $php_ver .= ',curl' . $c['version'];
    }
    if (class_exists('ZipArchive', false)) {
        $php_ver .= ',zip';
    }
    $DB = Database::getInstance();
    $mysql_ver = $DB->getMysqlVersion();
    $User_Model = new User_Model();
    $users = $User_Model->getUsers('', '', 'update', 1, 5);
    $apply_dashboard = ['carousel' => [], 'news' => []];
    $Apply_Model = new Apply_Model();
    $apply_dashboard = $Apply_Model->getAdminDashboard();
    require_once View::getAdmView('header');
    require_once View::getAdmView('index');
    require_once View::getAdmView('footer');
	View::output();
}else{
    show_404_page();
}