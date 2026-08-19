<?php
/**
 * Hope CMS 安装程序
 * @link http://www.hopecms.cn
 */

const HOPE_ROOT = __DIR__ . DIRECTORY_SEPARATOR;

require_once HOPE_ROOT . 'system/lib/common.php';
header('Content-Type: text/html; charset=UTF-8');
spl_autoload_register('hpAutoload');

if (version_compare(PHP_VERSION, '7.0.0', '<')) {
    hpMsg('PHP 版本过低，请使用 PHP 7.0 及以上版本（推荐 8.0+）');
}

$act = trim((string)($_GET['action'] ?? ''));

$bt_db_host = 'localhost';
$bt_db_username = 'BT_DB_USERNAME';
$bt_db_password = 'BT_DB_PASSWORD';
$bt_db_name = 'BT_DB_NAME';

$env_db_host = getenv('HOPE_DB_HOST') ?: 'localhost';
$env_db_name = getenv('HOPE_DB_NAME');
$env_db_user = getenv('HOPE_DB_USER');
$env_db_password = getenv('HOPE_DB_PASSWORD') ?: '';

$skipDbStep = (bool)$env_db_user || strpos($bt_db_username, 'BT_DB_') === false;

/**
 * 环境检测项
 */
function hope_install_requirements() {
    $configFile = HOPE_ROOT . 'system/config.php';
    $cacheDir = HOPE_ROOT . 'content/cache';
    $uploadDir = HOPE_ROOT . 'content/upload';
    $logsDir = HOPE_ROOT . 'logs';

    return [
        [
            'label' => 'PHP 版本 ≥ 7.0（当前 ' . PHP_VERSION . '，推荐 8.0+）',
            'ok'    => version_compare(PHP_VERSION, '7.0.0', '>='),
        ],
        [
            'label' => 'MySQL 驱动（mysqli 或 PDO）',
            'ok'    => class_exists('mysqli') || class_exists('pdo'),
        ],
        [
            'label' => is_file($configFile)
                ? '配置文件可写 system/config.php'
                : 'system 目录可写（用于生成 config.php）',
            'ok'    => is_writable($configFile) || (is_writable(dirname($configFile)) && !file_exists($configFile)),
        ],
        [
            'label' => '缓存目录可写 content/cache',
            'ok'    => is_dir($cacheDir) ? is_writable($cacheDir) : is_writable(dirname($cacheDir)),
        ],
        [
            'label' => '上传目录可写 content/upload',
            'ok'    => is_dir($uploadDir) ? is_writable($uploadDir) : is_writable(dirname($uploadDir)),
        ],
        [
            'label' => '日志目录可写 logs',
            'ok'    => is_dir($logsDir) ? is_writable($logsDir) : is_writable(dirname($logsDir)),
        ],
    ];
}

function hope_install_env_ready() {
    foreach (hope_install_requirements() as $item) {
        if (!$item['ok']) {
            return false;
        }
    }
    return true;
}

function hope_install_build_db_host($host, $port) {
    $host = trim((string)$host);
    $port = trim((string)$port);
    if ($host === '') {
        $host = 'localhost';
    }
    if ($port !== '' && ctype_digit($port) && strpos($host, ':') === false) {
        return $host . ':' . $port;
    }
    return $host;
}

function hope_install_connect($host, $user, $pass, $name) {
    if (!class_exists('mysqli')) {
        return [false, '服务器 PHP 未启用 mysqli 扩展'];
    }
    mysqli_report(MYSQLI_REPORT_OFF);
    $mysqli = @new mysqli($host, $user, $pass, $name);
    if ($mysqli->connect_errno) {
        switch ($mysqli->connect_errno) {
            case 1044:
            case 1045:
                return [false, '数据库用户名或密码错误'];
            case 1049:
                return [false, '未找到填写的数据库，请先创建数据库'];
            case 2003:
            case 2005:
            case 2006:
                return [false, '无法连接数据库服务器，请检查地址与端口'];
            default:
                return [false, '连接数据库失败：' . $mysqli->connect_error];
        }
    }
    $mysqli->set_charset('utf8mb4');
    return [$mysqli, ''];
}

function hope_install_tables_exist($mysqli, $prefix) {
    $like = $mysqli->real_escape_string($prefix . 'article');
    $result = $mysqli->query("SHOW TABLES LIKE '{$like}'");
    return $result && $result->num_rows > 0;
}

function hope_install_verify_core_tables($mysqli, $prefix) {
    $required = ['article', 'options', 'user'];
    foreach ($required as $table) {
        $like = $mysqli->real_escape_string($prefix . $table);
        $result = $mysqli->query("SHOW TABLES LIKE '{$like}'");
        if (!$result || $result->num_rows === 0) {
            return [false, '数据库安装不完整：缺少数据表 ' . $prefix . $table . '，请检查 MySQL 用户是否有 CREATE 权限后重试'];
        }
    }
    return [true, ''];
}

function hope_install_boot_app_db() {
    if (defined('DB_HOST')) {
        return;
    }
    $configFile = HOPE_ROOT . 'system/config.php';
    if (!is_readable($configFile)) {
        hpMsg('无法读取配置文件 system/config.php');
    }
    require_once $configFile;
    if (!defined('DB_HOST')) {
        hpMsg('配置文件格式错误，缺少数据库配置项 DB_HOST');
    }
}

/**
 * 从 config.php 读取数据库配置（不 require，避免占位文件中的重定向等副作用）
 */
function hope_install_parse_config($configFile) {
    if (!is_readable($configFile)) {
        return null;
    }
    $content = file_get_contents($configFile);
    if ($content === false || $content === '') {
        return null;
    }
    $keys = ['DB_HOST', 'DB_USER', 'DB_PASSWD', 'DB_NAME', 'DB_PREFIX'];
    $config = [];
    foreach ($keys as $key) {
        if (!preg_match('/^\s*const\s+' . preg_quote($key, '/') . '\s*=\s*(.+?);/m', $content, $match)) {
            return null;
        }
        $config[$key] = hope_install_parse_config_value($match[1]);
    }
    return $config;
}

function hope_install_parse_config_value($raw) {
    $raw = trim($raw);
    if ($raw === "''" || $raw === '""') {
        return '';
    }
    if ($raw !== '' && $raw[0] === "'" && substr($raw, -1) === "'") {
        return stripcslashes(substr($raw, 1, -1));
    }
    if ($raw !== '' && $raw[0] === '"' && substr($raw, -1) === '"') {
        return stripcslashes(substr($raw, 1, -1));
    }
    return $raw;
}

function hope_install_is_finished() {
    $configFile = HOPE_ROOT . 'system/config.php';
    $config = hope_install_parse_config($configFile);
    if ($config === null) {
        return false;
    }
    list($mysqli) = hope_install_connect(
        $config['DB_HOST'],
        $config['DB_USER'],
        $config['DB_PASSWD'],
        $config['DB_NAME']
    );
    if (!$mysqli) {
        return false;
    }
    $exists = hope_install_tables_exist($mysqli, $config['DB_PREFIX']);
    $mysqli->close();
    return $exists;
}

function hope_install_write_config(array $data) {
    $configFile = HOPE_ROOT . 'system/config.php';
    $content = "<?php\ndefined('HOPE_ROOT') || exit('access denied!');\n";
    foreach ($data as $name => $value) {
        $content .= 'const ' . $name . ' = ' . var_export($value, true) . ";\n";
    }
    $content .= "\nini_set('error_log', dirname(__DIR__) . '/content/logs/php_error.log');\n";
    $content .= "ini_set('log_errors', 'On');\n";
    $content .= "error_reporting(E_ALL & ~E_DEPRECATED);\n";
    if (@file_put_contents($configFile, $content, LOCK_EX) === false) {
        hpMsg('无法写入配置文件 system/config.php，请检查目录权限');
    }
}

function hope_install_prepare_dirs() {
    $dirs = [
        HOPE_ROOT . 'content/cache',
        HOPE_ROOT . 'content/upload',
        HOPE_ROOT . 'logs',
    ];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }
}

function hope_install_load_schema_sql($mysqli, array $vars) {
    $schemaFile = HOPE_ROOT . 'system/install/schema.sql';
    if (!is_readable($schemaFile)) {
        hpMsg('缺少安装 SQL 文件：system/install/schema.sql');
    }
    $sql = file_get_contents($schemaFile);
    foreach ($vars as $key => $value) {
        if ($key === 'db_prefix') {
            $sql = str_replace('{db_prefix}', $value, $sql);
            continue;
        }
        if ($key === 'demo_time' || $key === 'user_time') {
            $sql = str_replace('{' . $key . '}', (string)(int)$value, $sql);
            continue;
        }
        $sql = str_replace('{' . $key . '}', $mysqli->real_escape_string((string)$value), $sql);
    }
    return $sql;
}

function hope_install_execute_sql($mysqli, $sql) {
    $statements = preg_split('/;\s*\R/', $sql);
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if ($statement === '') {
            continue;
        }
        if ($mysqli->query($statement . ';') === false) {
            hpMsg('数据库安装失败：' . $mysqli->error);
        }
    }
}

function hope_install_post($key, $default = '') {
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : $default;
}

function hope_install_json($ok, $msg = '', $data = []) {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'code' => $ok ? 200 : 400,
        'msg'  => $msg,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($act === 'test_db') {
    $db_host = hope_install_build_db_host(hope_install_post('host', 'localhost'), hope_install_post('port'));
    $db_user = hope_install_post('user');
    $db_pw = hope_install_post('passwd');
    $db_name = hope_install_post('name');
    if ($db_user === '' || $db_name === '') {
        hope_install_json(false, '请填写数据库用户名和数据库名');
    }
    list($mysqli, $error) = hope_install_connect($db_host, $db_user, $db_pw, $db_name);
    if (!$mysqli) {
        hope_install_json(false, $error);
    }
    if ($mysqli->server_info && version_compare($mysqli->server_info, '5.5.3', '<')) {
        $mysqli->close();
        hope_install_json(false, 'MySQL 版本过低，请使用 5.5.3 及以上版本');
    }
    $mysqli->close();
    hope_install_json(true, '数据库连接成功');
}

if ($act === 'install' || $act === 'reinstall' || $act === 'keep') {
    $db_host = hope_install_build_db_host(hope_install_post('host', 'localhost'), hope_install_post('port'));
    $db_user = hope_install_post('user');
    $db_pw = hope_install_post('passwd');
    $db_name = hope_install_post('name');
    $db_prefix = hope_install_post('prefix', 'hope_');
    $username = hope_install_post('username', 'admin');
    $password = hope_install_post('password');
    $email = hope_install_post('email');
    $site_name = hope_install_post('site_name', 'Hope CMS');
    $site_title = hope_install_post('site_title', 'Hope CMS - 开源轻量建站系统');
    $site_describe = hope_install_post('site_describe', 'Hope CMS（希望CMS）是面向中文站长的开源轻量 CMS，支持博客、主题插件扩展与应用分发，小巧强大、开箱即用。');
    $keepData = ($act === 'keep');

    if ($db_user === '' || $db_name === '') {
        hpMsg($env_db_user && $db_name === ''
            ? '环境变量 HOPE_DB_NAME 未配置，无法自动安装'
            : '数据库用户名和数据库名不能为空');
    }
    if (!preg_match('/^[\w\-]+$/', $db_name)) {
        hpMsg('数据库名格式不正确');
    }
    if ($db_prefix === '') {
        hpMsg('数据库表前缀不能为空');
    }
    if (!preg_match('/^[\w]+_$/', $db_prefix)) {
        hpMsg('数据库表前缀格式错误，仅允许字母、数字、下划线，且必须以 _ 结尾');
    }
    if (!$keepData) {
        if ($username === '' || $password === '') {
            hpMsg('管理员登录名和密码不能为空');
        }
        if (strlen($password) < 6) {
            hpMsg('管理员密码不能少于 6 位');
        }
        if (!preg_match('/^[a-zA-Z0-9_\-@\.]{3,32}$/', $username)) {
            hpMsg('管理员登录名格式不正确');
        }
        if ($email !== '' && !checkMail($email)) {
            hpMsg('管理员邮箱格式不正确');
        }
        if ($site_name === '') {
            hpMsg('站点名称不能为空');
        }
    }

    hope_install_prepare_dirs();
    if (!hope_install_env_ready()) {
        hpMsg('安装环境检测未通过，请根据安装页提示修正目录权限或 PHP 扩展');
    }

    list($mysqli, $error) = hope_install_connect($db_host, $db_user, $db_pw, $db_name);
    if (!$mysqli) {
        hpMsg($error);
    }

    if ($act === 'install' && hope_install_tables_exist($mysqli, $db_prefix)) {
        $fields = [
            'host', 'port', 'user', 'passwd', 'name', 'prefix',
            'username', 'password', 'email', 'site_name', 'site_title', 'site_describe',
        ];
        $hidden = '';
        foreach ($fields as $field) {
            $value = htmlspecialchars((string)($_POST[$field] ?? ''), ENT_QUOTES, 'UTF-8');
            $hidden .= '<input type="hidden" name="' . $field . '" value="' . $value . '">';
        }
        echo <<<HTML
<!DOCTYPE html>
<html lang="zh-cn">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Hope CMS 安装提示</title>
<link href="./content/admin/css/argon-dashboard.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
<div class="container py-5">
  <div class="card shadow border-0 mx-auto" style="max-width:640px;">
    <div class="card-body p-4">
      <h5 class="mb-3">检测到已有安装数据</h5>
      <p class="text-sm text-muted mb-2">数据库中已存在 Hope CMS 数据表，请选择安装方式：</p>
      <ul class="text-sm text-muted ps-3 mb-4">
        <li><strong>保留数据继续安装</strong>：沿用现有库表与内容，仅重新生成配置文件。</li>
        <li><strong>覆盖并重新安装</strong>：清空并重建全部数据表，此操作不可恢复。</li>
      </ul>
      <div class="d-flex flex-wrap gap-2">
        <form method="post" action="install.php?action=keep" class="d-inline">
          {$hidden}
          <button type="submit" class="btn btn-success mb-0">保留数据继续安装</button>
        </form>
        <form method="post" action="install.php?action=reinstall" class="d-inline">
          {$hidden}
          <button type="submit" class="btn btn-danger mb-0">确认覆盖并重新安装</button>
        </form>
        <a href="javascript:history.back();" class="btn btn-light mb-0">返回修改</a>
      </div>
    </div>
  </div>
</div>
</body>
</html>
HTML;
        exit;
    }

    if ($keepData) {
        list($schemaOk, $schemaError) = hope_install_verify_core_tables($mysqli, $db_prefix);
        if (!$schemaOk) {
            $mysqli->close();
            hpMsg($schemaError);
        }
        $mysqli->close();
    } else {
        $PHPASS = new PasswordHash(8, true);
        $password_hash = $PHPASS->HashPassword($password);
        $now = time();
        $site_url = realUrl();
        $apikey = md5(getRandStr(32));
        $def_plugin = serialize(Option::getDefPlugin());

        $safeDbName = str_replace('`', '``', $db_name);
        $mysqli->query("ALTER DATABASE `{$safeDbName}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        $schemaSql = hope_install_load_schema_sql($mysqli, [
            'db_prefix'       => $db_prefix,
            'site_url'        => $site_url,
            'sitename'        => $site_name,
            'site_title'      => $site_title,
            'site_description'=> $site_describe,
            'siteinfo'        => $site_describe,
            'apikey'          => $apikey,
            'def_plugin'      => $def_plugin,
            'demo_time'       => $now,
            'username'        => $username,
            'email'           => $email,
            'password_hash'   => $password_hash,
            'nickname'        => $username,
            'user_time'       => $now,
        ]);
        hope_install_execute_sql($mysqli, $schemaSql);

        list($schemaOk, $schemaError) = hope_install_verify_core_tables($mysqli, $db_prefix);
        if (!$schemaOk) {
            $mysqli->close();
            hpMsg($schemaError);
        }
        $mysqli->close();
    }

    hope_install_write_config([
        'DB_HOST'          => $db_host,
        'DB_USER'          => $db_user,
        'DB_PASSWD'        => $db_pw,
        'DB_NAME'          => $db_name,
        'DB_PREFIX'        => $db_prefix,
        'AUTH_KEY'         => getRandStr(32) . md5(getUA()),
        'AUTH_COOKIE_NAME' => 'HOPE_AUTHCOOKIE_' . getRandStr(32, false),
    ]);

    hope_install_boot_app_db();

    $CACHE = Cache::getInstance();
    $CACHE->updateCache();

    if ($keepData) {
        $result = '
        <p style="font-size:24px;border-bottom:1px solid #E6E6E6;padding:10px 0;">恭喜，安装成功</p>
        <p>已保留原有数据并完成安装，配置文件已重新生成。</p>
        <p><b>后台地址</b>：<a href="./admin.php">./admin.php</a></p>
        <p><b>登录账号</b>：请使用数据库中原有的管理员账号密码登录。</p>';
    } else {
        $result = '
        <p style="font-size:24px;border-bottom:1px solid #E6E6E6;padding:10px 0;">恭喜，安装成功</p>
        <p>Hope CMS 已安装完成，现在可以开始创作了。</p>
        <p><b>后台地址</b>：<a href="./admin.php">./admin.php</a></p>
        <p><b>用户名</b>：' . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . '</p>
        <p><b>密 码</b>：安装时设定的密码</p>';
    }
    $installRemoved = @unlink(HOPE_ROOT . 'install.php');
    if (!$installRemoved && is_file(HOPE_ROOT . 'install.php')) {
        $result .= '<p style="color:#c0392b;">安全提示：请手动删除根目录下的 install.php</p>';
    }
    $result .= '<p style="text-align:right;"><a href="./">访问首页</a> | <a href="./admin.php">登录后台</a></p>';
    hpMsg($result, 'none');
}

if (!$act && hope_install_is_finished()) {
    hpMsg(
        'Hope CMS 已安装。如需重新安装，请先备份数据并删除 system/config.php，或清空数据库后再次访问本页。',
        './admin.php'
    );
}

hope_install_prepare_dirs();

$requirements = hope_install_requirements();
$envReady = hope_install_env_ready();
$dbPanelClass = $skipDbStep ? '' : 'js-active';
$adminPanelClass = $skipDbStep ? 'js-active' : '';
$dbProgressClass = $skipDbStep ? '' : 'js-active';
$adminProgressClass = $skipDbStep ? 'js-active' : '';
?>
<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="icon" type="image/png" href="./content/admin/img/favicon.png">
    <title>Hope CMS 安装向导</title>
    <link href="./content/admin/css/argon-dashboard.min.css" rel="stylesheet">
    <link href="./content/admin/css/fontawesome7.css" rel="stylesheet">
    <style>
        .install-check-list li { font-size: .875rem; margin-bottom: .35rem; }
        .install-check-list .ok { color: #2dce89; }
        .install-check-list .fail { color: #f5365c; }
        .footer { color: rgba(255,255,255,.8); margin-top: 2rem; font-size: .875rem; }
    </style>
</head>
<body class="g-sidenav-show bg-gray-100">
<div class="min-height-200 bg-primary position-absolute w-100"></div>
<main class="main-content position-relative border-radius-lg">
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12 text-center">
                <h3 class="text-white">Hope CMS <?= Option::HOPE_VERSION ?></h3>
                <h5 class="text-white font-weight-normal">安装向导</h5>

                <div class="multisteps-form mb-4">
                    <div class="row mt-4">
                        <div class="col-12 col-lg-8 mx-auto">
                            <div class="multisteps-form__progress">
                                <?php if (!$skipDbStep): ?>
                                <button class="multisteps-form__progress-btn <?= $dbProgressClass ?>" type="button"><span>数据库</span></button>
                                <?php endif; ?>
                                <button class="multisteps-form__progress-btn <?= $adminProgressClass ?>" type="button"><span>管理员</span></button>
                                <button class="multisteps-form__progress-btn" type="button"><span>站点配置</span></button>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 col-lg-8 m-auto">
                            <?php if (!$envReady): ?>
                            <div class="alert alert-warning text-start text-sm mb-3" role="alert">
                                <strong>环境检测未通过</strong>，请先修正以下项后再继续安装。
                            </div>
                            <?php endif; ?>
                            <div class="card mb-3">
                                <div class="card-body py-3 text-start">
                                    <h6 class="mb-2">环境检测</h6>
                                    <ul class="install-check-list list-unstyled mb-0">
                                        <?php foreach ($requirements as $item): ?>
                                        <li class="<?= $item['ok'] ? 'ok' : 'fail' ?>">
                                            <i class="fa fa-<?= $item['ok'] ? 'check-circle' : 'times-circle' ?> me-1"></i>
                                            <?= htmlspecialchars($item['label']) ?>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>

                            <form class="multisteps-form__form" method="post" action="install.php?action=install" id="hope-install-form">
                                <?php if ($env_db_user): ?>
                                    <input name="host" type="hidden" value="<?= htmlspecialchars($env_db_host) ?>">
                                    <input name="user" type="hidden" value="<?= htmlspecialchars($env_db_user) ?>">
                                    <input name="passwd" type="hidden" value="<?= htmlspecialchars($env_db_password) ?>">
                                    <input name="name" type="hidden" value="<?= htmlspecialchars($env_db_name) ?>">
                                    <input name="prefix" type="hidden" value="hope_">
                                <?php elseif (strpos($bt_db_username, 'BT_DB_') === false): ?>
                                    <input name="host" type="hidden" value="<?= htmlspecialchars($bt_db_host) ?>">
                                    <input name="user" type="hidden" value="<?= htmlspecialchars($bt_db_username) ?>">
                                    <input name="passwd" type="hidden" value="<?= htmlspecialchars($bt_db_password) ?>">
                                    <input name="name" type="hidden" value="<?= htmlspecialchars($bt_db_name) ?>">
                                    <input name="prefix" type="hidden" value="hope_">
                                <?php else: ?>
                                <div class="card multisteps-form__panel p-3 border-radius-xl bg-white <?= $dbPanelClass ?>" data-animation="FadeIn">
                                    <div class="multisteps-form__content">
                                        <div class="row mt-2">
                                            <div class="col-12 col-sm-6">
                                                <label class="form-label">数据库地址</label>
                                                <input class="form-control" name="host" type="text" value="localhost" required>
                                            </div>
                                            <div class="col-12 col-sm-6 mt-3 mt-sm-0">
                                                <label class="form-label">端口（可选）</label>
                                                <input class="form-control" name="port" type="text" value="" placeholder="默认 3306">
                                            </div>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-12 col-sm-6">
                                                <label class="form-label">数据库用户名</label>
                                                <input class="form-control" name="user" type="text" required>
                                            </div>
                                            <div class="col-12 col-sm-6 mt-3 mt-sm-0">
                                                <label class="form-label">数据库密码</label>
                                                <input class="form-control" name="passwd" type="password" autocomplete="new-password">
                                            </div>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-12 col-sm-6">
                                                <label class="form-label">数据库名</label>
                                                <input class="form-control" name="name" type="text" required>
                                            </div>
                                            <div class="col-12 col-sm-6 mt-3 mt-sm-0">
                                                <label class="form-label">表前缀</label>
                                                <input class="form-control" name="prefix" type="text" value="hope_" required>
                                            </div>
                                        </div>
                                        <div class="d-flex flex-wrap gap-2 mt-4">
                                            <button class="btn btn-outline-primary mb-0" type="button" id="hope-install-test-db">测试连接</button>
                                            <span class="text-sm align-self-center text-muted" id="hope-install-test-result"></span>
                                            <button class="btn bg-gradient-dark ms-auto mb-0 js-btn-next" type="button">下一步</button>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <div class="card multisteps-form__panel p-3 border-radius-xl bg-white <?= $adminPanelClass ?>" data-animation="FadeIn">
                                    <div class="multisteps-form__content">
                                        <div class="row mt-2">
                                            <div class="col-12 col-sm-4 text-center">
                                                <img src="./content/admin/img/Face.png" class="border-radius-md w-100" alt="admin" style="max-width:120px;">
                                            </div>
                                            <div class="col-12 col-sm-8 mt-3 mt-sm-0 text-start">
                                                <label class="form-label">登录名</label>
                                                <input class="form-control mb-3" name="username" type="text" value="admin" required>
                                                <label class="form-label">密码</label>
                                                <input class="form-control mb-3" name="password" type="password" placeholder="不少于 6 位" required minlength="6" autocomplete="new-password">
                                                <label class="form-label">邮箱（可选）</label>
                                                <input class="form-control" name="email" type="email" placeholder="可用于找回密码">
                                            </div>
                                        </div>
                                        <div class="button-row d-flex mt-4 col-12">
                                            <?php if (!$skipDbStep): ?>
                                            <button class="btn bg-gradient-light mb-0 js-btn-prev" type="button">上一步</button>
                                            <?php endif; ?>
                                            <button class="btn bg-gradient-dark ms-auto mb-0 js-btn-next" type="button">下一步</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="card multisteps-form__panel p-3 border-radius-xl bg-white" data-animation="FadeIn">
                                    <div class="multisteps-form__content">
                                        <div class="row text-start">
                                            <div class="col-12">
                                                <label class="form-label">站点名称</label>
                                                <input class="form-control" type="text" name="site_name" value="Hope CMS" required>
                                            </div>
                                            <div class="col-12 mt-3">
                                                <label class="form-label">站点标题</label>
                                                <input class="form-control" type="text" name="site_title" value="Hope CMS - 开源轻量建站系统" maxlength="120" placeholder="用于浏览器标题与 SEO">
                                            </div>
                                            <div class="col-12 mt-3">
                                                <label class="form-label">站点描述</label>
                                                <textarea class="form-control" name="site_describe" rows="3" maxlength="250" placeholder="用于站点简介与 SEO 描述">Hope CMS（希望CMS）是面向中文站长的开源轻量 CMS，支持博客、主题插件扩展与应用分发，小巧强大、开箱即用。</textarea>
                                            </div>
                                        </div>
                                        <div class="button-row d-flex mt-4">
                                            <button class="btn bg-gradient-light mb-0 js-btn-prev" type="button">上一步</button>
                                            <button class="btn bg-gradient-dark ms-auto mb-0" type="submit" id="hope-install-submit" <?= $envReady ? '' : 'disabled' ?>>开始安装</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="footer">Powered by <a class="text-white" href="http://www.hopecms.cn/">Hope CMS</a></div>
            </div>
        </div>
    </div>
</main>
<script src="./content/admin/js/core/popper.min.js"></script>
<script src="./content/admin/js/core/bootstrap.min.js"></script>
<script src="./content/admin/js/plugins/multistep-form.js"></script>
<script src="./content/admin/js/argon-dashboard.min.js"></script>
<script>
(function () {
    var testBtn = document.getElementById('hope-install-test-db');
    var testResult = document.getElementById('hope-install-test-result');
    if (testBtn) {
        testBtn.addEventListener('click', function () {
            var form = document.getElementById('hope-install-form');
            var fd = new FormData(form);
            testBtn.disabled = true;
            testResult.textContent = '测试中…';
            testResult.className = 'text-sm align-self-center text-muted';
            fetch('install.php?action=test_db', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    testBtn.disabled = false;
                    testResult.textContent = res.msg || (res.code === 200 ? '连接成功' : '连接失败');
                    testResult.className = 'text-sm align-self-center ' + (res.code === 200 ? 'text-success' : 'text-danger');
                })
                .catch(function () {
                    testBtn.disabled = false;
                    testResult.textContent = '请求失败';
                    testResult.className = 'text-sm align-self-center text-danger';
                });
        });
    }
})();
</script>
</body>
</html>
