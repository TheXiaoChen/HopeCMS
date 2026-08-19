<?php
/**
 * Hope CMS(希望CMS) Login authentication
 * @author LinZiChen <271106735@qq.com>
 * @link http://www.hopecms.cn
 * @copyright 2027 Hope CMS All rights reserved.
 * @license http://www.apache.org/licenses/LICENSE-2.0
*/

class LoginAuth {

    const LOGIN_ERROR_USER = -1;
    const LOGIN_ERROR_PASSWD = -2;


    public static function isLogin() {
        global $userData;

        if (isset($_COOKIE[AUTH_COOKIE_NAME])) {
            $auth_cookie = $_COOKIE[AUTH_COOKIE_NAME];
        } elseif (isset($_POST[AUTH_COOKIE_NAME])) {
            $auth_cookie = $_POST[AUTH_COOKIE_NAME];
        } else {
            return false;
        }

        if (($userData = self::validateAuthCookie($auth_cookie)) === false) {
            return false;
        }

        return true;
    }

    public static function checkLogin($error_code = NULL) {
        if (self::isLogin() === true) {
            return;
        }

        // 后台：跳转后台登录页（依赖 admin.php 定义的 SELF）
        if (self::isAdminContext()) {
            $url = SITE_URL . SELF . '?act=login';
            if ($error_code) {
                $url .= '&code=' . urlencode((string)$error_code);
            }
            Gohref($url);
        }

        // 前台：跳转前台登录，并带回跳地址（如下载页）
        $query = [];
        if ($error_code) {
            $query['code'] = $error_code;
        }
        $redirect = self::currentRedirectTarget();
        if ($redirect !== '') {
            $query['redirect'] = $redirect;
        }
        Gohref(Url::login($query));
    }

    public static function checkLogged() {
        if (self::isLogin() === false) {
            return;
        }
        if (self::isAdminContext()) {
            Gohref(SITE_URL . SELF);
        }
        Gohref(Url::userCenter());
    }

    /**
     * 是否处于后台入口上下文
     */
    private static function isAdminContext() {
        return defined('HOPE_ADMIN') && HOPE_ADMIN && defined('SELF') && SELF !== '';
    }

    /**
     * 当前请求地址（用于登录后回跳，优先相对路径）
     */
    private static function currentRedirectTarget() {
        $uri = isset($_SERVER['REQUEST_URI']) ? trim((string)$_SERVER['REQUEST_URI']) : '';
        if ($uri === '' || $uri[0] !== '/') {
            return '';
        }
        return $uri;
    }

    public static function checkUser($username, $password) {
        if (empty($username) || empty($password)) {
            return self::LOGIN_ERROR_USER;
        }
        $userData = self::getUserDataByLogin($username);
        if (false === $userData) {
            return self::LOGIN_ERROR_USER;
        }
        $hash = $userData['password'];
        if (true === self::checkPassword($password, $hash)) {
            return $userData['uid'];
        }
        return self::LOGIN_ERROR_PASSWD;
    }

    public static function getUserDataByLogin($account) {
        $DB = Database::getInstance();
        if (empty($account)) {
            return false;
        }
        $account = $DB->escape_string($account);
        $ret = $DB->once_fetch_array("SELECT * FROM " . DB_PREFIX . "user WHERE username = '$account' AND state = 0");
        if (!$ret) {
            $ret = $DB->once_fetch_array("SELECT * FROM " . DB_PREFIX . "user WHERE email = '$account' AND state = 0");
            if (!$ret) {
                return false;
            }
        }
        $userData['nickname'] = htmlspecialchars($ret['nickname']);
        $userData['username'] = htmlspecialchars($ret['username']);
        $userData['password'] = $ret['password'];
        $userData['uid'] = $ret['uid'];
        $userData['role'] = $ret['role'];
        $userData['photo'] = $ret['photo'];
        $userData['email'] = $ret['email'];
        $userData['mail'] = $ret['email']; // 主题兼容旧字段名
        $userData['description'] = $ret['description'];
        $userData['ip'] = $ret['ip'];
        $userData['create_time'] = $ret['create_time'];
        $userData['update_time'] = $ret['update_time'];
        // 主题扩展字段（ResHub 等）
        $userData['vip'] = isset($ret['vip']) ? (string)$ret['vip'] : '0';
        $userData['qq'] = isset($ret['qq']) ? (string)$ret['qq'] : '';
        $userData['sex'] = isset($ret['sex']) ? $ret['sex'] : 0;
        $userData['balance'] = isset($ret['balance']) ? (float)$ret['balance'] : 0;
        return $userData;
    }

    public static function checkPassword($password, $hash) {
        global $em_hasher;
        if (empty($em_hasher)) {
            $em_hasher = new PasswordHash(8, true);
        }
        return $em_hasher->CheckPassword($password, $hash);
    }

    public static function setAuthCookie($user_login, $persist = false) {
        $persist = (bool)$persist;
        // 持久登录约 1 个月；未勾选记住我则为浏览器会话 Cookie
        $expiration = $persist ? (time() + 3600 * 24 * 30) : 0;
        $auth_cookie_name = AUTH_COOKIE_NAME;
        $auth_cookie = self::generateAuthCookie($user_login, $expiration);
        $secure = function_exists('hope_is_https') ? hope_is_https() : false;

        // 先按相同属性清除旧 Cookie，避免 Secure/SameSite 不一致导致覆盖失败
        self::clearAuthCookie();

        if (PHP_VERSION_ID >= 70300) {
            $opts = [
                'expires'  => $expiration,
                'path'     => '/',
                'secure'   => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ];
            if ($persist) {
                $opts['expires'] = $expiration;
            } else {
                // 会话 Cookie：expires 必须为 0（不能省略成过去时间）
                $opts['expires'] = 0;
            }
            setcookie($auth_cookie_name, $auth_cookie, $opts);
        } else {
            setcookie($auth_cookie_name, $auth_cookie, $expiration, '/', '', $secure, true);
        }

        // 当前请求内立即可见
        $_COOKIE[$auth_cookie_name] = $auth_cookie;

        // 记住账号（仅用户名，非 HttpOnly，便于登录框回填）
        $accountCookie = 'HOPE_REMEMBER_USER';
        if ($persist) {
            if (PHP_VERSION_ID >= 70300) {
                setcookie($accountCookie, rawurlencode($user_login), [
                    'expires'  => $expiration,
                    'path'     => '/',
                    'secure'   => $secure,
                    'httponly' => false,
                    'samesite' => 'Lax',
                ]);
            } else {
                setcookie($accountCookie, rawurlencode($user_login), $expiration, '/', '', $secure, false);
            }
            $_COOKIE[$accountCookie] = rawurlencode($user_login);
        } else {
            if (PHP_VERSION_ID >= 70300) {
                setcookie($accountCookie, '', [
                    'expires'  => time() - 3600,
                    'path'     => '/',
                    'secure'   => $secure,
                    'httponly' => false,
                    'samesite' => 'Lax',
                ]);
            } else {
                setcookie($accountCookie, '', time() - 3600, '/', '', $secure, false);
            }
            unset($_COOKIE[$accountCookie]);
        }

        return $auth_cookie_name . '=' . $auth_cookie;
    }

    /** 清除登录 Cookie（属性需与 setAuthCookie 一致才能删干净） */
    public static function clearAuthCookie() {
        $secure = function_exists('hope_is_https') ? hope_is_https() : false;
        $name = AUTH_COOKIE_NAME;
        if (PHP_VERSION_ID >= 70300) {
            setcookie($name, '', [
                'expires'  => time() - 3600,
                'path'     => '/',
                'secure'   => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            // 兼容历史未带 SameSite/Secure 的旧 Cookie
            setcookie($name, '', [
                'expires'  => time() - 3600,
                'path'     => '/',
                'secure'   => false,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        } else {
            setcookie($name, '', time() - 3600, '/', '', $secure, true);
            setcookie($name, '', time() - 3600, '/', '', false, true);
        }
        unset($_COOKIE[$name]);
    }

    private static function generateAuthCookie($user_login, $expiration) {
        $key = self::hpHash($user_login . '|' . $expiration);
        $hash = hash_hmac('md5', $user_login . '|' . $expiration, $key);

        return $user_login . '|' . $expiration . '|' . $hash;
    }

    private static function hpHash($data) {
        return hash_hmac('md5', $data, AUTH_KEY);
    }

    public static function validateAuthCookie($cookie = '') {
        if (empty($cookie)) {
            return false;
        }

        $cookie_elements = explode('|', $cookie);
        if (count($cookie_elements) !== 3) {
            return false;
        }

        list($username, $expiration, $hmac) = $cookie_elements;

        if (!empty($expiration) && $expiration < time()) {
            return false;
        }

        $key = self::hpHash($username . '|' . $expiration);
        $hash = hash_hmac('md5', $username . '|' . $expiration, $key);

        if ($hmac !== $hash) {
            return false;
        }

        $user = self::getUserDataByLogin($username);
        if (!$user) {
            return false;
        }
        return $user;
    }

    public static function genToken() {
        if (!isset($_SESSION)) {
            session_start();
        }
        if (!empty($_SESSION['hope_csrf_token'])) {
            return $_SESSION['hope_csrf_token'];
        }
        // 一次性迁移旧 session 键
        if (!empty($_SESSION['em_csrf_token'])) {
            $_SESSION['hope_csrf_token'] = $_SESSION['em_csrf_token'];
            unset($_SESSION['em_csrf_token']);
            return $_SESSION['hope_csrf_token'];
        }
        $token = sha1(getRandStr(32));
        $_SESSION['hope_csrf_token'] = $token;
        return $token;
    }

    public static function getToken() {
        if (!isset($_SESSION)) {
            session_start();
        }
        $token = '';
        if (!empty($_SESSION['hope_csrf_token'])) {
            $token = $_SESSION['hope_csrf_token'];
        } elseif (!empty($_SESSION['em_csrf_token'])) {
            $token = $_SESSION['em_csrf_token'];
            $_SESSION['hope_csrf_token'] = $token;
            unset($_SESSION['em_csrf_token']);
        }
        return $token;
    }

    /** 读取请求中的 CSRF token（字段名 token） */
    public static function requestToken() {
        if (isset($_REQUEST['token']) && (string)$_REQUEST['token'] !== '') {
            return (string)$_REQUEST['token'];
        }
        return '';
    }

    public static function checkToken() {
        if (!isset($_SESSION)) {
            session_start();
        }
        $token = self::requestToken();
        $sessionToken = self::getToken();
        if ($sessionToken === '' || $token === '' || !hash_equals($sessionToken, $token)) {
            hpMsg('安全token校验失败，请尝试刷新页面或者更换浏览器重试');
        }
    }

    /**
     * AJAX 场景下的 token 校验，失败时输出 JSON 并终止
     */
    public static function checkTokenJson() {
        if (!isset($_SESSION)) {
            session_start();
        }
        $token = self::requestToken();
        $sessionToken = self::getToken();
        if ($sessionToken === '' || $token === '' || !hash_equals($sessionToken, $token)) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'success' => 0,
                'message' => '安全token校验失败，请刷新页面后重试',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}
