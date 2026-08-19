<?php
/**
 * Hope CMS(希望CMS) 初始化
 * @author LinZiChen <271106735@qq.com>
 * @link http://www.hopecms.cn
 * @copyright 2027 Hope CMS All rights reserved.
 * @license http://www.apache.org/licenses/LICENSE-2.0
 */

/** 输出缓冲：移除页面开头 UTF-8 BOM，避免主题文件带 BOM 时出现 &#xFEFF; */
function hope_ob_strip_utf8_bom($buffer) {
    if ($buffer !== '' && strncmp($buffer, "\xEF\xBB\xBF", 3) === 0) {
        return substr($buffer, 3);
    }
    return $buffer;
}

ob_start('hope_ob_strip_utf8_bom');
header('Content-Type: text/html; charset=UTF-8');
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
define('HOPE_ROOT', str_ireplace('system' ,'' , dirname(__FILE__)));
require_once HOPE_ROOT . 'system/config.php';
require_once HOPE_ROOT . 'system/lib/common.php';
require_once HOPE_ROOT . 'system/bootstrap/paths.php';
if (extension_loaded('mbstring')) {
    mb_internal_encoding('UTF-8');
}
spl_autoload_register("hpAutoload");
if (Option::get('debug') === 'y' || (defined('ENVIRONMENT') && ENVIRONMENT === 'develop')) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL & ~E_DEPRECATED);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', HOPE_ROOT . 'content/cache/error.log');
}

$CACHE = Cache::getInstance();

define('ISLOGIN', LoginAuth::isLogin());
date_default_timezone_set(Option::get('timezone'));
hope_lang_init();

const ROLE_ADMIN = 'admin';
const ROLE_EDITOR = 'editor';
const ROLE_WRITER = 'writer';
const ROLE_VISITOR = 'visitor';

global $userData;
define('ROLE', ISLOGIN === true ? $userData['role'] : User::ROLE_VISITOR);
define('UID', ISLOGIN === true ? (int)$userData['uid'] : 0);

define('SITE_URL', Option::get('siteurl'));

const TPLS_URL = SITE_URL . 'content/theme/';
const TPLS_PATH = HOPE_ROOT . 'content/theme/';
const PLUGIN_URL = SITE_URL . 'content/plugin/';
const PLUGIN_PATH = HOPE_ROOT . 'content/plugin/';
const OPT_URL = HOPE_ROOT . 'system/options/';
const OPT_PATH = HOPE_ROOT . 'system/options/';

//站点URL
define('DYNAMIC_BLOGURL', Option::get('siteurl'));
// 当前主题的 URL
define('THEME_URL', TPLS_URL . Option::get('nonce_theme') . '/');
// 后台视图目录
define('ADMIN_TEMPLATE_PATH', HOPE_ROOT . 'content/admin/');
// 当前主题目录
define('THEME_PATH', TPLS_PATH . Option::get('nonce_theme') . '/');

require_once HOPE_ROOT . 'system/lib/sidebar.php';

const MSGCODE_API_AUTH_INVALID = 1001;
const MSGCODE_NO_UPUPDATE = 1002;
const MSGCODE_SUCCESS = 200;

$active_plugins = Option::get('active_plugins');
$hopeHooks = [];
if ($active_plugins && is_array($active_plugins)) {
    foreach ($active_plugins as $plugin) {
        if (true === checkPlugin($plugin)) {
            include_once(HOPE_ROOT . 'content/plugin/' . $plugin);
        }
    }
}

// 加载主题的系统调用文件
define('THEME_HOOK_PATH', TPLS_PATH . Option::get('nonce_theme') . '/plugins.php');
if (file_exists(THEME_HOOK_PATH)) {
    include_once(THEME_HOOK_PATH);
}

doAction('init');

// 侧栏日历 AJAX：仅输出日历 HTML 片段（?action=cal&record=YYYYMM）
if (isset($_GET['action']) && (string)$_GET['action'] === 'cal') {
	while (ob_get_level() > 0) {
		ob_end_clean();
	}
	header('Content-Type: text/html; charset=UTF-8');
	header('X-Robots-Tag: noindex');
	if (class_exists('Calendar')) {
		Calendar::generate();
	}
	exit;
}
