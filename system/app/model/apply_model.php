<?php
/**
 * Hope CMS 应用中心远程对接模型
 */
class Apply_Model {

    /** 应用中心 API 地址（后台 Option 配置，默认官方商店） */
    public function getStoreApiUrl() {
        $url = trim((string)Option::get('apply_store_url'));
        if ($url === '') {
            $url = 'http://www.hopecms.cn/plugin/apply';
        }
        return $url;
    }

    /** 官方商店根地址 */
    public function getOfficialSiteUrl() {
        $url = $this->getStoreApiUrl();
        $url = preg_replace('#[?&].*$#', '', $url);
        $url = preg_replace('#/plugin/apply/?$#', '', $url);
        if ($url === '') {
            return 'http://www.hopecms.cn';
        }
        return rtrim($url, '/');
    }

    /** 官方商店购买链接 */
    public function getPurchaseUrl($apply_id) {
        return $this->getOfficialSiteUrl() . '/?rest-api=apply_purchase&apply_id=' . (int)$apply_id;
    }

    /** 官方商店充值链接 */
    public function getRechargeUrl($username = '') {
        $url = $this->getOfficialSiteUrl() . '/user#recharge';
        if ($username !== '') {
            $url .= '?from=bind&user=' . urlencode($username);
        }
        return $url;
    }

    /** 官方商店应用详情/介绍页 */
    public function getDetailUrl($apply_id) {
        return $this->getOfficialSiteUrl() . '/?plugin=apply&id=' . (int)$apply_id;
    }

    /** 组装远程请求鉴权参数（使用后台绑定的 api_password） */
    private function storeAuthParams() {
        return [
            'api_password' => trim((string)Option::get('apply_bind_key')),
            'username'     => trim((string)Option::get('apply_bind_username')),
            'bind_uid'     => Option::get('apply_bind_uid'),
            'site_url'     => SITE_URL,
        ];
    }

    public function getApps($type = 'all', $tag = '', $keyword = '', $page = 1, $author_id = '', $list = '') {
        return $this->reqApplyStore($type, $tag, $keyword, $page, $author_id, $list);
    }

    public function getThemes($tag, $keyword, $page, $author_id, $list) {
        return $this->reqApplyStore('theme', $tag, $keyword, $page, $author_id, $list);
    }

    public function getPlugins($tag, $keyword, $page, $author_id, $list) {
        return $this->reqApplyStore('plugin', $tag, $keyword, $page, $author_id, $list);
    }

    public function getMyAddon() {
        return $this->reqApplyStore('mine');
    }

    public function getTopAddon() {
        return $this->reqApplyStore('top');
    }

    /** 后台首页：幻灯片 + 最新动态（公开接口，无需绑定账号） */
    public function getAdminDashboard() {
        $fallback = $this->localAdminDashboard();

        $hpcurl = new HpCurl();
        $hpcurl->setPost([
            'action'   => 'dashboard',
            'ver'      => Option::HOPE_VERSION,
            'site_url' => SITE_URL,
        ]);
        $hpcurl->request($this->getStoreApiUrl());

        if ($hpcurl->getHttpStatus() !== MSGCODE_SUCCESS) {
            return $fallback;
        }
        $ret = json_decode($hpcurl->getRespone(), true);
        if (!is_array($ret) || empty($ret['code']) || (int)$ret['code'] !== 200) {
            return $fallback;
        }
        $data = isset($ret['data']) && is_array($ret['data']) ? $ret['data'] : [];
        return [
            'carousel' => !empty($data['carousel']) && is_array($data['carousel']) ? $data['carousel'] : $fallback['carousel'],
            'news'     => !empty($data['news']) && is_array($data['news']) ? $data['news'] : $fallback['news'],
        ];
    }

    private static $boundProfileCache;

    /** 远程校验绑定账号 */
    public function verifyRemoteBind($username, $api_password) {
        $profile = $this->fetchBoundProfile($username, $api_password);
        return is_array($profile);
    }

    /** 绑定账号资料：昵称、余额（同请求内缓存） */
    public function fetchBoundProfile($username = '', $api_password = '') {
        if (self::$boundProfileCache !== null) {
            return self::$boundProfileCache;
        }
        $username = trim((string)$username);
        $api_password = trim((string)$api_password);
        if ($username === '' || $api_password === '') {
            $auth = $this->storeAuthParams();
            $username = $auth['username'];
            $api_password = $auth['api_password'];
        }
        if ($username === '' || $api_password === '') {
            self::$boundProfileCache = false;
            return false;
        }
        $hpcurl = new HpCurl();
        $hpcurl->setPost([
            'action'       => 'verify',
            'username'     => $username,
            'api_password' => $api_password,
            'site_url'     => SITE_URL,
        ]);
        $hpcurl->request($this->getStoreApiUrl());
        if ($hpcurl->getHttpStatus() !== MSGCODE_SUCCESS) {
            self::$boundProfileCache = false;
            return false;
        }
        $response = json_decode($hpcurl->getRespone(), true);
        if (!is_array($response) || empty($response['code']) || (int)$response['code'] !== 200) {
            self::$boundProfileCache = false;
            return false;
        }
        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        $nickname = trim((string)($data['nickname'] ?? ''));
        if ($nickname === '') {
            $nickname = trim((string)($data['username'] ?? $username));
        }
        self::$boundProfileCache = [
            'nickname'       => $nickname,
            'username'       => (string)($data['username'] ?? ''),
            'email'          => (string)($data['email'] ?? $username),
            'user_id'        => (int)($data['user_id'] ?? 0),
            'balance'        => round((float)($data['balance'] ?? 0), 2),
            'profile_image'  => trim((string)($data['profile_image'] ?? ($data['photo'] ?? ''))),
        ];
        return self::$boundProfileCache;
    }

    public function bindAccount($username, $api_password, $uid = UID) {
        $uid = (int)$uid;
        $username = trim((string)$username);
        $api_password = trim((string)$api_password);
        if ($username === '') {
            return ['ok' => false, 'msg' => '请输入商店用户邮箱'];
        }
        if ($api_password === '') {
            return ['ok' => false, 'msg' => '请输入 API 密钥'];
        }
        if (!filter_var($username, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'msg' => '请输入有效的商店用户邮箱'];
        }

        // 绑定仍有效时不可更换；失效后允许重新绑定
        if (Register::isBound() && Register::isValid()) {
            $bound_user = trim((string)Option::get('apply_bind_username'));
            $bound_key = trim((string)Option::get('apply_bind_key'));
            if ($bound_user !== '' && ($bound_user !== $username || $bound_key !== $api_password)) {
                return ['ok' => false, 'msg' => '已绑定商店账号「' . $bound_user . '」，不可更换。如需解绑请联系管理员'];
            }
        }

        $user = Database::getOne('user', ['uid' => $uid]);
        if (!$user) {
            return ['ok' => false, 'msg' => '用户不存在'];
        }

        $hpcurl = new HpCurl();
        $post_data = array_merge($this->storeAuthParams(), [
            'action'       => 'bind',
            'username'     => $username,
            'email'        => $username,
            'api_password' => $api_password,
            'nickname'     => $user['nickname'] ?? '',
            'uid'          => $uid,
        ]);
        $hpcurl->setPost($post_data);
        $hpcurl->request($this->getStoreApiUrl());
        $response = json_decode($hpcurl->getRespone(), true);

        if (is_array($response) && !empty($response['code']) && (int)$response['code'] === 200) {
            $store_uid = $response['data']['user_id'] ?? $uid;
            $store_email = trim((string)($response['data']['email'] ?? ''));
            if ($store_email === '') {
                $store_email = $username;
            }
            $store_username = $response['data']['username'] ?? $store_email;
            $store_nickname = trim((string)($response['data']['nickname'] ?? ''));
            if ($store_nickname === '') {
                $store_nickname = (string)$store_username;
            }
            Option::updateOption('apply_bind_key', $api_password);
            Option::updateOption('apply_bind_uid', $store_uid);
            // 本地凭证标识统一存邮箱，供后续 API 鉴权
            Option::updateOption('apply_bind_username', $store_email);
            Option::updateOption('apply_bind_nickname', $store_nickname);
            if (isset($response['data']['type'])) {
                Option::updateOption('apply_bind_type', (int)$response['data']['type']);
            }
            Option::updateOption('apply_store_url', $this->getStoreApiUrl());
            Option::updateOption('apply_bind_time', time());
            if (!empty($response['data']['push_secret'])) {
                Option::updateOption('apply_push_secret', (string)$response['data']['push_secret']);
            }
            $CACHE = Cache::getInstance();
            $CACHE->updateCache('options');
            return [
                'ok'             => true,
                'msg'            => '绑定成功',
                'user_id'        => $store_uid,
                'username'       => $store_username,
                'nickname'       => $store_nickname,
                'balance'        => round((float)($response['data']['balance'] ?? 0), 2),
                'profile_image'  => trim((string)($response['data']['profile_image'] ?? '')),
                'email'          => $store_email,
                'recharge_url'   => $response['data']['recharge_url'] ?? $this->getRechargeUrl($store_email),
                'store_url'      => $response['data']['store_url'] ?? $this->getOfficialSiteUrl(),
            ];
        }

        $msg = (is_array($response) && !empty($response['msg']))
            ? (string)$response['msg']
            : '远程商店连接失败，请检查应用中心地址与账号密钥';
        return ['ok' => false, 'msg' => $msg];
    }

    public function checkUpgrade($type, $apps) {
        $hpcurl = new HpCurl();
        $post_data = array_merge($this->storeAuthParams(), [
            'action' => 'upgrade_check',
            'type'   => $type,
            'apps'   => json_encode($apps),
            'ver'    => Option::HOPE_VERSION,
        ]);
        $hpcurl->setPost($post_data);
        $hpcurl->request($this->getStoreApiUrl());
        $retStatus = $hpcurl->getHttpStatus();
        $response = $hpcurl->getRespone();
        $ret = json_decode($response, true);
        if ($retStatus === MSGCODE_SUCCESS && is_array($ret) && !empty($ret['data']) && is_array($ret['data'])) {
            return $ret['data'];
        }
        return [];
    }

    /** 通过商店 API 余额购买应用 */
    public function purchaseApp($apply_id, $pay_type = 'balance') {
        $apply_id = (int)$apply_id;
        $pay_type = trim((string)$pay_type);
        if ($apply_id <= 0) {
            return ['ok' => false, 'msg' => '应用无效'];
        }
        if (!in_array($pay_type, ['balance', 'alipay', 'wechat'], true)) {
            $pay_type = 'balance';
        }
        if (!Register::isBound() || !Register::isValid()) {
            return ['ok' => false, 'msg' => '请先绑定商店账号'];
        }

        $hpcurl = new HpCurl();
        $post_data = array_merge($this->storeAuthParams(), [
            'action'   => 'purchase',
            'apply_id' => $apply_id,
            'pay_type' => $pay_type,
        ]);
        $hpcurl->setPost($post_data);
        $hpcurl->request($this->getStoreApiUrl());

        $retStatus = $hpcurl->getHttpStatus();
        $ret = json_decode($hpcurl->getRespone(), true);
        if ($retStatus !== MSGCODE_SUCCESS || !is_array($ret) || empty($ret['code'])) {
            $msg = is_array($ret) && !empty($ret['msg']) ? (string)$ret['msg'] : '商店购买请求失败';
            return ['ok' => false, 'msg' => $msg];
        }
        if ((int)$ret['code'] === MSGCODE_API_AUTH_INVALID) {
            Register::clear();
            return ['ok' => false, 'msg' => '商店账号已失效，请重新绑定'];
        }
        if ((int)$ret['code'] !== 200) {
            return ['ok' => false, 'msg' => (string)($ret['msg'] ?? '购买失败')];
        }
        $data = is_array($ret['data'] ?? null) ? $ret['data'] : [];
        if (!empty($data['paid'])) {
            return [
                'ok'       => true,
                'msg'      => (string)($ret['msg'] ?? '购买成功'),
                'paid'     => true,
                'apply_id' => $apply_id,
                'order_no' => $data['order_no'] ?? '',
                'nickname' => (string)($data['nickname'] ?? ''),
                'balance'  => round((float)($data['balance'] ?? 0), 2),
            ];
        }
        return [
            'ok'       => true,
            'msg'      => (string)($ret['msg'] ?? '请完成支付'),
            'paid'     => false,
            'pay_url'  => $data['pay_url'] ?? '',
            'order_no' => $data['order_no'] ?? '',
        ];
    }

    /** 查询绑定账号余额（远程商店 API） */
    public function getBoundUserBalance() {
        if (!Register::isBound() || !Register::isValid()) {
            return null;
        }
        $hpcurl = new HpCurl();
        $hpcurl->setPost(array_merge($this->storeAuthParams(), ['action' => 'balance']));
        $hpcurl->request($this->getStoreApiUrl());
        if ($hpcurl->getHttpStatus() !== MSGCODE_SUCCESS) {
            return null;
        }
        $ret = json_decode($hpcurl->getRespone(), true);
        if (!is_array($ret) || (int)($ret['code'] ?? 0) !== 200) {
            return null;
        }
        return isset($ret['data']['balance']) ? (float)$ret['data']['balance'] : null;
    }

    public function downloadUpgrade($type, $alias) {
        $auth = $this->storeAuthParams();
        $url = $this->getStoreApiUrl()
            . (strpos($this->getStoreApiUrl(), '?') !== false ? '&' : '?')
            . 'action=download&type=' . urlencode($type) . '&alias=' . urlencode($alias)
            . '&site_url=' . urlencode(SITE_URL);
        if ($auth['api_password'] !== '') {
            $url .= '&api_password=' . urlencode($auth['api_password']);
        }
        if ($auth['username'] !== '') {
            $url .= '&username=' . urlencode($auth['username']);
        }
        return hopeFetchFile($url);
    }

    private function normalizeApps($apps) {
        if (!is_array($apps)) {
            return [];
        }
        foreach ($apps as &$app) {
            $raw = $app['update_time'] ?? ($app['update_Date'] ?? '');
            $app['update_time_raw'] = $raw;
            $app['update_time'] = formatApplyUpdateTime($raw);
            $app['update_Date'] = $app['update_time'];
            if (empty($app['info'])) {
                $summary = trim(strip_tags((string)($app['summary'] ?? ($app['description'] ?? ''))));
                $app['info'] = $summary !== '' ? $summary : (string)($app['summary'] ?? '');
            }
            if (!isset($app['downloads'])) {
                $app['downloads'] = (int)($app['download_count'] ?? 0);
            }
            if (!isset($app['ver']) && isset($app['version'])) {
                $app['ver'] = $app['version'];
            }
            if (!isset($app['app_type']) && isset($app['type'])) {
                $app['app_type'] = $app['type'];
            }
        }
        unset($app);
        return $apps;
    }

    public function reqApplyStore($type, $tag = '', $keyword = '', $page = 1, $author_id = 0, $list = '') {
        $hpcurl = new HpCurl();
        $post_data = array_merge($this->storeAuthParams(), [
            'action'    => $type === 'mine' ? 'mine' : ($type === 'top' ? 'top' : 'list'),
            'ver'       => Option::HOPE_VERSION,
            'type'      => $type,
            'tag'       => $tag,
            'keyword'   => $keyword,
            'page'      => $page,
            'author_id' => $author_id,
            'list'      => $list,
        ]);

        $hpcurl->setPost($post_data);
        $hpcurl->request($this->getStoreApiUrl());

        $retStatus = $hpcurl->getHttpStatus();
        $response = $hpcurl->getRespone();
        $ret = json_decode($response, true);

        if ($retStatus !== MSGCODE_SUCCESS || empty($ret) || !isset($ret['code'])) {
            return $this->emptyStoreData($type);
        }
        if ($ret['code'] === MSGCODE_API_AUTH_INVALID) {
            Register::clear();
            return $this->emptyStoreData($type);
        }

        $data = [];
        switch ($type) {
            case 'all':
                $data['apps'] = $this->normalizeApps($ret['data']['apps'] ?? []);
                $data['count'] = $ret['data']['count'] ?? 0;
                $data['page_count'] = $ret['data']['page_count'] ?? 0;
                $data['has_more'] = $ret['has_more'] ?? false;
                break;
            case 'theme':
                $data['theme'] = $this->normalizeApps($ret['data']['apps'] ?? []);
                $data['count'] = $ret['data']['count'] ?? 0;
                $data['page_count'] = $ret['data']['page_count'] ?? 0;
                $data['has_more'] = $ret['has_more'] ?? false;
                break;
            case 'plugin':
                $data['plugin'] = $this->normalizeApps($ret['data']['apps'] ?? []);
                $data['count'] = $ret['data']['count'] ?? 0;
                $data['page_count'] = $ret['data']['page_count'] ?? 0;
                $data['has_more'] = $ret['has_more'] ?? false;
                break;
            case 'svip':
            case 'mine':
            case 'top':
                $items = $ret['data']['apps'] ?? (isset($ret['data']) ? $ret['data'] : []);
                $data = is_array($items) ? $this->normalizeApps($items) : [];
                break;
        }
        return $data;
    }

    /** 远程不可用时的后台首页兜底数据 */
    private function localAdminDashboard() {
        return [
            'carousel' => [
                [
                    'image' => SITE_URL . 'content/admin/img/carousel-1.jpg',
                    'title' => 'Hope CMS 应用中心',
                    'link'  => SELF . '?act=apply',
                    'desc'  => '浏览主题与插件，一键安装扩展',
                    'icon'  => 'fa fa-store',
                ],
                [
                    'image' => SITE_URL . 'content/admin/img/carousel-2.jpg',
                    'title' => '持续更新',
                    'link'  => SELF . '?act=apply',
                    'desc'  => '获取官方推荐与版本动态',
                    'icon'  => 'fa fa-cloud-arrow-up',
                ],
            ],
            'news' => [
                [
                    'type'  => 'notice',
                    'title' => '欢迎使用 Hope CMS',
                    'text'  => '可在应用中心浏览并安装主题、插件',
                    'time'  => date('Y-m-d'),
                ],
                [
                    'type'  => 'update',
                    'title' => '版本 ' . Option::HOPE_VERSION,
                    'text'  => '保持系统与应用为最新版本，获得更好体验',
                    'time'  => date('Y-m-d'),
                ],
            ],
        ];
    }

    private function emptyStoreData($type) {
        $empty = ['count' => 0, 'page_count' => 0, 'has_more' => false];
        switch ($type) {
            case 'theme':
                return array_merge($empty, ['theme' => []]);
            case 'plugin':
                return array_merge($empty, ['plugin' => []]);
            case 'svip':
            case 'mine':
            case 'top':
                return [];
            default:
                return array_merge($empty, ['apps' => []]);
        }
    }
}
