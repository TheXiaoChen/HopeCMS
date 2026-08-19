<?php
defined('HOPE_ROOT') || exit('access denied!');

/**
 * 用户认证 API：配置 / 登录 / 注册 / 退出 / 状态
 */

function hope_user_auth_url($key) {
    if (function_exists('hope_resolve_user_url') && in_array($key, ['login', 'register', 'user', 'auth_api'], true)) {
        return hope_resolve_user_url($key);
    }
    if (function_exists('hope_blog_url')) {
        return hope_blog_url($key);
    }
    if (function_exists('hope_site_url')) {
        return hope_site_url($key);
    }
    if (function_exists('hope_resource_url')) {
        return hope_resource_url($key);
    }
    switch ($key) {
        case 'login':
            return Url::login();
        case 'register':
            return Url::register();
        case 'user':
            return Url::userCenter();
        case 'auth_api':
            return Url::authApi(['api' => 1]);
        default:
            return SITE_URL;
    }
}

function hope_user_auth_safe_redirect($url, $default) {
    $url = trim($url);
    if ($url === '') {
        return $default;
    }
    if ($url[0] === '/') {
        return $url;
    }
    $site_host = parse_url(SITE_URL, PHP_URL_HOST);
    $target = parse_url($url);
    if (!empty($target['host']) && $target['host'] === $site_host) {
        return $url;
    }
    return $default;
}

function hope_user_auth_handle_api() {
    hope_load_user_center();
    if (is_file(THEME_PATH . 'module.php')) {
        require_once THEME_PATH . 'module.php';
    }

    $action = isset($_REQUEST['action']) ? trim($_REQUEST['action']) : '';
    $post_actions = ['login', 'register', 'logout'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, $post_actions, true)) {
        LoginAuth::checkToken();
    }

    switch ($action) {
        case 'config':
            hope_user_auth_api_config();
            break;
        case 'login':
            hope_user_auth_api_login();
            break;
        case 'register':
            hope_user_auth_api_register();
            break;
        case 'logout':
            hope_user_auth_api_logout();
            break;
        case 'auth_status':
            hope_user_auth_api_status();
            break;
        default:
            Output::error('未知操作');
    }
}

function hope_user_auth_api_config() {
    Output::ok([
        'token'         => LoginAuth::genToken(),
        'login_code'    => Option::get('login_code') === 'y',
        'email_code'    => Option::get('email_code') === 'y',
        'is_signup'     => Option::get('is_signup') === 'y',
        'captcha_url'   => SITE_URL . 'system/checkcode.php',
        'login_url'     => hope_user_auth_url('login'),
        'register_url'  => hope_user_auth_url('register'),
        'user_url'      => hope_user_auth_url('user'),
        'is_logged_in'  => defined('UID') && UID > 0,
    ]);
}

function hope_user_auth_api_login() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Output::error('请使用 POST 请求');
    }
    $username = Input::postStrVar('user');
    $password = Input::postStrVar('password');
    $rememberMe = Input::postIntVar('rememberMe');
    $redirect = Input::postStrVar('redirect');
    $login_code = Option::get('login_code') === 'y' && isset($_POST['login_code'])
        ? addslashes(strtoupper(trim($_POST['login_code'])))
        : '';

    if (!User::checkLoginCode($login_code)) {
        Output::error('验证码错误');
    }

    $uid = LoginAuth::checkUser($username, $password);
    if ($uid <= 0) {
        Output::error('用户名或密码错误');
    }

    Register::isValid();
    $User_Model = new User_Model();
    $User_Model->updateUser(['ip' => getIp()], $uid);
    $userRow = LoginAuth::getUserDataByLogin($username);
    $cookieLogin = (!empty($userRow['username']) ? html_entity_decode($userRow['username'], ENT_QUOTES, 'UTF-8') : $username);
    LoginAuth::setAuthCookie($cookieLogin, $rememberMe > 0);

    if (function_exists('hope_user_center_ensure_meta')) {
        hope_user_center_ensure_meta($uid);
    }
    $profile = function_exists('hope_user_center_get_profile')
        ? hope_user_center_get_profile($uid)
        : ['uid' => $uid];

    $redirect = hope_user_auth_safe_redirect($redirect, hope_user_auth_url('user'));

    Output::ok([
        'profile'  => $profile,
        'redirect' => $redirect,
        'token'    => LoginAuth::genToken(),
    ]);
}

function hope_user_auth_api_register() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Output::error('请使用 POST 请求');
    }
    if (Option::get('is_signup') !== 'y') {
        Output::error('注册已关闭');
    }

    $mail = Input::postStrVar('mail');
    $passwd = Input::postStrVar('passwd');
    $repasswd = Input::postStrVar('repasswd');
    $invite = Input::postStrVar('invite');
    $login_code = strtoupper(Input::postStrVar('login_code'));
    $mail_code = Input::postStrVar('mail_code');

    if (!checkMail($mail)) {
        Output::error('邮箱格式不正确');
    }
    if (!User::checkLoginCode($login_code)) {
        Output::error('验证码错误');
    }
    if (Option::get('email_code') === 'y' && !User::checkMailCode($mail_code)) {
        Output::error('邮箱验证码错误');
    }

    $User_Model = new User_Model();
    if ($User_Model->isMailExist($mail)) {
        Output::error('该邮箱已被注册');
    }
    if (strlen($passwd) < 6) {
        Output::error('密码长度不能少于 6 位');
    }
    if ($passwd !== $repasswd) {
        Output::error('两次密码不一致');
    }

    $PHPASS = new PasswordHash(8, true);
    $hash = $PHPASS->HashPassword($passwd);
    $User_Model->addUser('', $mail, $hash, User::ROLE_WRITER);
    $uid = Database::getInstance()->insert_id();

    if ($uid > 0) {
        if (function_exists('hope_user_center_ensure_meta')) {
            hope_user_center_ensure_meta($uid);
        }
        if (function_exists('hope_user_center_process_invite')) {
            hope_user_center_process_invite($uid, $invite);
        }
    }

    $CACHE = Cache::getInstance();
    $CACHE->updateCache(['sta', 'user']);

    Output::ok([
        'uid'      => (int)$uid,
        'redirect' => hope_user_auth_url('login'),
        'msg'      => '注册成功，请登录',
    ]);
}

function hope_user_auth_api_logout() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Output::error('请使用 POST 请求');
    }
    setcookie(AUTH_COOKIE_NAME, ' ', time() - 31536000, '/');
    Output::ok([
        'redirect' => hope_user_auth_url('login'),
        'msg'      => '已退出登录',
    ]);
}

function hope_user_auth_api_status() {
    if (!defined('UID') || UID <= 0) {
        Output::authError('未登录');
    }
    $profile = function_exists('hope_user_center_get_profile')
        ? hope_user_center_get_profile(UID)
        : null;
    if (!$profile) {
        Output::authError('用户不存在');
    }
    Output::ok($profile);
}
