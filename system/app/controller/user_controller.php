<?php
/**
 * Hope CMS(希望CMS) user center
 * @author LinZiChen <271106735@qq.com>
 * @link http://www.hopecms.cn
 * @copyright 2027 Hope CMS All rights reserved.
 * @license http://www.apache.org/licenses/LICENSE-2.0
 */

class User_Controller {

    /** 登录页错误码 */
    private const LOGIN_FLASH_CODES = [
        'login'  => '用户名或密码错误',
        'ckcode' => '验证码错误',
    ];

    /** 注册页错误码 */
    private const REGISTER_FLASH_CODES = [
        'login'     => '邮箱格式不正确',
        'ckcode'    => '验证码错误',
        'mail_code' => '邮箱验证码错误',
        'exist'     => '该邮箱已被注册',
        'pwd_len'   => '密码长度不能少于 6 位',
        'pwd2'      => '两次密码不一致',
    ];

    public function index() {
        $this->bootstrap(true, false);

        if ($this->isApiRequest()) {
            hope_user_center_handle_api();
            exit;
        }

        // 兼容主题旧链接：?user&login / &new_reg / &resetpassword
        if (!$this->isLoggedIn()) {
            if (isset($_GET['new_reg'])) {
                $this->register();
                return;
            }
            if (isset($_GET['resetpassword'])) {
                $this->bootstrap(false, true);
                $this->renderUserPage('resetpassword', '找回密码', $this->authViewVars());
                return;
            }
            if (isset($_GET['login'])) {
                $this->login();
                return;
            }
            $this->redirectToLogin();
        }

        $profile = hope_user_center_get_profile(UID);
        if (!$profile) {
            show_404_page();
        }

        $this->renderUserPage('index', '个人中心', array_merge(
            $this->inviteViewVars(),
            [
                'profile'       => $profile,
                'name'          => $profile['nickname'] ?: ($profile['username'] ?: '用户'),
                'avatar'        => $profile['photo'] ?? '',
                'api_url'       => $this->themeUrl('user', ['api' => 1]),
                'token'         => LoginAuth::genToken(),
                'auth_api'      => $this->themeUrl('auth_api', ['api' => 1]),
                'apply_enabled' => function_exists('hope_uc_apply_enabled') && hope_uc_apply_enabled(),
                'logout_url'    => $this->themeUrl('auth_api', ['action' => 'logout']),
            ]
        ));
    }

    public function login() {
        $this->bootstrap(false, true);

        if ($this->isApiRequest()) {
            hope_user_auth_handle_api();
            exit;
        }

        $this->redirectIfLoggedIn();

        $user_url = $this->themeUrl('user');
        $redirect = trim($_GET['redirect'] ?? $user_url);
        if (function_exists('hope_user_auth_safe_redirect')) {
            $redirect = hope_user_auth_safe_redirect($redirect, $user_url);
        }

        $this->renderUserPage('login', '用户登录', array_merge(
            $this->authViewVars(),
            [
                'login_code'   => Option::get('login_code') === 'y',
                'is_signup'    => Option::get('is_signup') === 'y',
                'user_url'     => $user_url,
                'register_url' => $this->themeUrl('register'),
                'redirect'     => $redirect,
                'msg'          => $this->flashMessage(self::LOGIN_FLASH_CODES),
            ]
        ));
    }

    public function register() {
        $this->bootstrap(true, true);

        if ($this->isApiRequest()) {
            hope_user_auth_handle_api();
            exit;
        }

        $this->redirectIfLoggedIn();

        $this->renderUserPage('register', '用户注册', array_merge(
            $this->authViewVars(),
            $this->inviteViewVars(),
            [
                'signup_closed' => Option::get('is_signup') !== 'y',
                'login_code'    => Option::get('login_code') === 'y',
                'email_code'    => Option::get('email_code') === 'y',
                'login_url'     => $this->themeUrl('login'),
                'invite_code'   => trim($_GET['invite'] ?? ''),
                'msg'           => $this->flashMessage(self::REGISTER_FLASH_CODES),
            ]
        ));
    }

    public function authApi() {
        $this->bootstrap(false, true);
        // auth_api 路由专用于认证 API，不依赖 ?api=1 参数
        hope_user_auth_handle_api();
        exit;
    }

    /** 加载主题 module、个人中心服务、认证库 */
    private function bootstrap($loadUserCenter, $loadAuthLib) {
        $this->loadThemeModule();
        if ($loadUserCenter) {
            $this->loadUserCenterLib();
        }
        if ($loadAuthLib) {
            $this->loadUserAuthLib();
        }
    }

    private function isApiRequest() {
        return !empty($_GET['api']);
    }

    private function isLoggedIn() {
        return defined('UID') && UID > 0;
    }

    private function redirectIfLoggedIn() {
        if ($this->isLoggedIn()) {
            header('Location: ' . $this->themeUrl('user'));
            exit;
        }
    }

    private function flashMessage(array $codes) {
        if (!isset($_GET['code'])) {
            return '';
        }
        return $codes[$_GET['code']] ?? '';
    }

    /** 登录/注册页共用的认证 API 变量 */
    private function authViewVars() {
        return [
            'auth_api'   => $this->themeUrl('auth_api', ['api' => 1]),
            'auth_token' => LoginAuth::genToken(),
        ];
    }

    /** 邀请奖励 / 支付开关相关变量 */
    private function inviteViewVars() {
        return [
            'invite_reward_amount' => hope_user_center_get_invite_reward_amount(),
            'invite_enabled'       => hope_user_center_invite_enabled(),
            'payment_enabled'      => hope_user_center_payment_enabled(),
        ];
    }

    /**
     * 解析主题用户中心 URL（委托 hope_resolve_user_url）
     * @param string $key login|register|user|auth_api
     */
    private function themeUrl($key, $query = []) {
        $url = hope_resolve_user_url($key);
        if ($key === 'auth_api' && strpos($url, 'api=') === false) {
            $query = array_merge(['api' => 1], $query);
        }
        if (!empty($query)) {
            $sep = (strpos($url, '?') !== false) ? '&' : '?';
            $url .= $sep . http_build_query($query);
        }
        return $url;
    }

    private function loadThemeModule() {
        $module = THEME_PATH . 'module.php';
        if (is_file($module)) {
            require_once $module;
        }
    }

    private function loadUserAuthLib() {
        $path = THEME_PATH . 'user/user_auth_lib.php';
        if (is_file($path)) {
            require_once $path;
        }
    }

    private function loadUserCenterLib() {
        hope_load_user_center();
        $path = THEME_PATH . 'user/user_center_lib.php';
        if (is_file($path)) {
            require_once $path;
        }
    }

    /**
     * @param string $template    user/ 下模板名
     * @param string $titleSuffix 页面标题前缀
     * @param array  $viewVars    传入模板的变量
     */
    private function renderUserPage($template, $titleSuffix, $viewVars = []) {
        extract(Option::getAll(), EXTR_SKIP);
        extract($viewVars, EXTR_OVERWRITE);
        $site_title = $titleSuffix . ' - ' . ($site_title ?: $sitename);
        $body_class = 'user-center-page' . ($template !== 'index' ? ' auth-layout' : '');

        if (View::isUserTplExist('header')) {
            include View::getUserView('header');
        } else {
            include View::getView('header');
        }

        include View::getUserView($template);

        if (View::isUserTplExist('footer')) {
            include View::getUserView('footer');
        } else {
            include View::getView('footer');
        }
    }

    private function redirectToLogin() {
        $login_url = $this->themeUrl('login');
        $sep = (strpos($login_url, '?') !== false) ? '&' : '?';
        header('Location: ' . $login_url . $sep . 'redirect=' . urlencode($this->themeUrl('user')));
        exit;
    }
}
