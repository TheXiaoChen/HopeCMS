<?php
/**
 * Hope CMS(希望CMS) 应用中心
 */
defined('HOPE_ROOT') || exit('access denied!');

require_once HOPE_ROOT . 'system/lib/apply_store_view.php';

$Apply_Model = new Apply_Model();
$all_categories = [
    '全部' => 'all',
    '博客' => 'theme',
    '资源' => 'theme',
    '社区' => 'theme',
    '企业' => 'theme',
    'SEO优化' => 'plugin',
    '内容创作' => 'plugin',
    '装饰特效' => 'plugin',
    '官方生态' => 'plugin'
];

$theme_categories = [
    '全部' => 'theme',
    '博客' => 'theme',
    '资源' => 'theme',
    '社区' => 'theme',
    '导航' => 'theme',
    '企业' => 'theme',
    '文档' => 'theme',
    '通用' => 'theme',
];

$plugin_categories = [
    '全部' => 'plugin',
    '编辑器' => 'plugin',
    'SEO优化' => 'plugin',
    '数据采集' => 'plugin',
    '内容创作' => 'plugin',
    '装饰特效' => 'plugin',
    '官方生态' => 'plugin',
    '功能扩展' => 'plugin'
];

if (isset($_GET['install'])) {
    header('Content-Type: application/json; charset=utf-8');
    $source_type = trim($_POST['type'] ?? '');
    $alias = trim($_POST['alias'] ?? '');
    if ($alias === '' || !in_array($source_type, ['theme', 'plugin'], true)) {
        Output::error('安装失败：缺少应用标识，请刷新应用中心后重试');
    }

    $hp_type = ($source_type === 'theme') ? 'tpl' : 'plugin';

    // 仅走官方鉴权下载（alias），不使用前端 CDN 真链
    if (!Register::isBound()) {
        Output::error('未绑定应用中心，请先绑定商店账号');
    }
    $temp_file = $Apply_Model->downloadUpgrade($source_type, $alias);
    if (!$temp_file) {
        Output::error('安装失败：下载超时或没有权限（需登录且具备下载权）');
    }

    if ($hp_type === 'tpl') {
        $unzip_path = './content/theme/';
        $suc_url = SELF . '?act=theme';
    } else {
        $unzip_path = './content/plugin/';
        $suc_url = SELF . '?act=plugin';
    }

    $ret = hpUnZip($temp_file, $unzip_path, $hp_type);
    @unlink($temp_file);
    switch ($ret) {
        case 0:
            Output::ok(['msg' => '安装成功', 'redirect' => $suc_url]);
        case 1:
        case 2:
            Output::error('安装失败，请检查 content 下目录是否可写');
        case 3:
            Output::error('安装失败，请安装 PHP 的 Zip 扩展');
        default:
            Output::error('安装失败，不是有效的安装包');
    }
}

if (isset($_GET['purchase_app']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    LoginAuth::checkToken();
    $apply_id = Input::postIntVar('apply_id');
    $r = $Apply_Model->purchaseApp($apply_id, 'balance');
    if (empty($r['ok'])) {
        Output::error($r['msg'] ?? '购买失败');
    }
    Output::ok($r);
}

if (isset($_GET['ajax_mine'])) {
    $mine = $Apply_Model->getMyAddon();
    if (!is_array($mine)) $mine = [];
    Output::json(['code' => 200, 'data' => ['apps' => $mine, 'count' => count($mine)]]);
}

if (isset($_GET['bind_account']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    LoginAuth::checkToken();
    $username = Input::postStrVar('email');
    if ($username === '') {
        $username = Input::postStrVar('username');
    }
    $api_password = Input::postStrVar('api_password');
    $r = $Apply_Model->bindAccount($username, $api_password, UID);
    if (!$r['ok']) {
        Output::error($r['msg']);
    }
    Output::ok($r);
}

if (isset($_GET['verify_bind'])) {
    $valid = Register::isBound() && Register::isValid();
    Output::ok([
        'bound'  => Register::isBound(),
        'valid'  => $valid,
        'username' => Option::get('apply_bind_username'),
    ]);
}

if (isset($_GET['check_update'])) {
    $apps = $_POST['apps'] ?? [];
    if (!is_array($apps)) {
        $apps = [];
    }
    $updates = $Apply_Model->checkUpgrade('', $apps);
    Output::ok($updates);
}

if (isset($_GET['ajax_load'])) {
    $type = Input::getStrVar('type', 'all');
    $tag = Input::getStrVar('tag');
    $page = Input::getIntVar('page', 1);
    $keyword = Input::getStrVar('keyword');
    $author_id = Input::getStrVar('author_id');
    $list = Input::getStrVar('list');

    $Apply_Model = new Apply_Model();

    switch ($type) {
        case 'theme':
            $r = $Apply_Model->getApps('theme', $tag, $keyword, $page, $author_id, $list);
            $apps = $r['theme'];
            break;
        case 'plugin':
            $r = $Apply_Model->getApps('plugin', $tag, $keyword, $page, $author_id, $list);
            $apps = $r['plugin'];
            break;
        default:
            $r = $Apply_Model->getApps($type, $tag, $keyword, $page, $author_id, $list);
            $apps = $r['apps'];
            break;
    }

    $count = $r['count'];
    $page_count = $r['page_count'];
    $has_more = $r['has_more'] ?? true;
    $next_page = $has_more==false ? $page + 1 : null;

    // 为每个应用添加状态信息
    foreach ($apps as &$app) {
        if ($app['app_type'] === 'theme') {
            //$app['is_active'] = Theme::isActive($app['alias']);
        } else {
            //$app['is_active'] = Plugin::isActive($app['alias']);
        }
        $app['user_is_svip'] = (Register::getType() === 2);
        if (!isset($app['update_Date']) && isset($app['update_time'])) {
            $app['update_Date'] = $app['update_time'];
        }
    }
    unset($app);

    $response = [
        'code' => 200,
        'data' => [
            'apps' => $apps,
            'count' => $count,
            'page_count' => $page_count
        ],
        'has_more' => $has_more,
        'current_page' => $page,
        'next_page' => $next_page
    ];
    Output::json($response);
}


$apps = $Apply_Model->getApps();
$theme = $Apply_Model->getApps('theme');
$plugin = $Apply_Model->getApps('plugin');

$app['apps'] = $apps['apps'];
$app['theme'] = $theme['theme'];
$app['plugin'] = $plugin['plugin'];

$apply_bind_uid = Option::get('apply_bind_uid');
$apply_bind_key = Option::get('apply_bind_key');
$apply_bind_username = Option::get('apply_bind_username');
$apply_is_bound = Register::isBound();
// 绑定商店账号 = 对接官方 API：凭证写入 apply_bind_*，后续商店请求均带上
$apply_default_email = $apply_bind_username ?: '';
$apply_recharge_url = $Apply_Model->getRechargeUrl($apply_bind_username);
$apply_purchase_base = $Apply_Model->getOfficialSiteUrl();
$apply_bind_valid = $apply_is_bound ? Register::isValid() : false;
$apply_profile = $apply_bind_valid ? $Apply_Model->fetchBoundProfile() : false;
$apply_display_name = '';
$apply_display_balance = null;
$apply_display_avatar = '';
if (is_array($apply_profile)) {
    $apply_display_name = trim((string)($apply_profile['nickname'] ?? ''));
    $apply_display_balance = isset($apply_profile['balance']) ? (float)$apply_profile['balance'] : null;
    $apply_display_avatar = trim((string)($apply_profile['profile_image'] ?? ''));
    if ($apply_display_name !== '' && trim((string)Option::get('apply_bind_nickname')) === '') {
        Option::updateOption('apply_bind_nickname', $apply_display_name);
        Cache::getInstance()->updateCache('options');
    }
}
if ($apply_display_name === '') {
    $apply_display_name = trim((string)Option::get('apply_bind_nickname'))
        ?: ($apply_bind_username ?: ($userData['nickname'] ?? ($userData['username'] ?? '用户')));
}
$apply_display_id = $apply_bind_uid ?: '';
// 仅绑定有效时隐藏表单；未绑定或账号失效时显示
$apply_show_bind_form = !$apply_bind_valid;

// 本地已安装应用版本（用于检测商店更新）
$apply_local_versions = [];
$Theme_Model = new Theme_Model();
foreach ($Theme_Model->getThemes() as $t) {
    if (!empty($t['tplfile'])) {
        $apply_local_versions[$t['tplfile']] = $t['version'] ?? '';
    }
}
if (class_exists('Plugin')) {
    foreach (Plugin::get() as $p) {
        $name = $p['Name'] ?? '';
        if ($name !== '') {
            $apply_local_versions[$name] = $p['Version'] ?? '';
        }
    }
}

$is_page['apps'] = $apps['has_more'];
$is_page['theme'] = $theme['has_more'];
$is_page['plugin'] = $plugin['has_more'];

