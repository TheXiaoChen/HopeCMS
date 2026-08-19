<?php
/**
 * Hope CMS(希望CMS) login
 * @author 林子辰
 * @link http://www.hopecms.cn
 */
defined('HOPE_ROOT') || exit('access denied!');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)Input::postStrVar('do') === '1') {
    hope_login_rate_limit_check();
    $username = Input::postRawStr('user');
    $password = Input::postRawStr('password');
    // 兼容 checkbox / 部分环境下 filter_input 读不到的情况
    $rememberMe = !empty($_POST['rememberMe']) && (string)$_POST['rememberMe'] !== '0';
    $login_code = Option::get('login_code') === 'y' && isset($_POST['login_code'])
        ? strtoupper(trim((string)$_POST['login_code']))
        : '';

    if (!User::checkLoginCode($login_code)) {
        Gohref(SELF . '?act=login&code=ckcode');
    }

    $uid = LoginAuth::checkUser($username, $password);
    if ($uid > 0) {
        hope_login_rate_limit_success();
        hope_audit_log('login', 'success', ['username' => $username]);
        $User_Model->updateUser(['ip' => getIp()], $uid);
        $userRow = LoginAuth::getUserDataByLogin($username);
        $cookieLogin = $username;
        if (!empty($userRow['username'])) {
            $cookieLogin = html_entity_decode((string)$userRow['username'], ENT_QUOTES, 'UTF-8');
        }
        LoginAuth::setAuthCookie($cookieLogin, $rememberMe);
        doAction('login_success', $uid, $username);
        // 绝对地址跳转，避免相对路径导致 Cookie 作用域异常
        Gohref(SITE_URL . SELF);
    }

    hope_login_rate_limit_fail($username);
    Gohref(SELF . '?act=login&code=login');
}

$login_code = Option::get('login_code') === 'y';
$is_signup = Option::get('is_signup') === 'y';
$rememberUser = '';
if (!empty($_COOKIE['HOPE_REMEMBER_USER'])) {
    $rememberUser = rawurldecode((string)$_COOKIE['HOPE_REMEMBER_USER']);
}
require_once View::getAdmView('login');
View::output();
