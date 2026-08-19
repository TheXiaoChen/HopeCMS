<?php
defined('HOPE_ROOT') || exit('access denied!');

/**
 * 支付 SDK：读取配置、创建支付、验签回调
 */
class Pay_Sdk {

    public static function getConfig() {
        $raw = Option::get('payment');
        $cfg = $raw ? @unserialize($raw) : [];
        if (!is_array($cfg)) {
            return [];
        }
        // 一次性消化历史单一 hpj_api：拆到微信/支付宝网关后丢弃
        $legacy = trim((string)($cfg['hpj_api'] ?? ''));
        if ($legacy !== '') {
            if (trim((string)($cfg['hpj_wx_api'] ?? '')) === '') {
                $cfg['hpj_wx_api'] = $legacy;
            }
            if (trim((string)($cfg['hpj_alipay_api'] ?? '')) === '') {
                $cfg['hpj_alipay_api'] = $legacy;
            }
            unset($cfg['hpj_api']);
        }
        return $cfg;
    }

    public static function getChannel($type = 'alipay') {
        $cfg = self::getConfig();
        if ($type === 'wechat') {
            $api = (int)($cfg['weixinapi'] ?? 0);
        } else {
            $api = (int)($cfg['alipayapi'] ?? 0);
        }
        $map = [0 => 'official', 1 => 'hpj', 2 => 'epay', 3 => 'codepay'];
        return $map[$api] ?? 'official';
    }

    public static function notifyUrl($channel) {
        return SITE_URL . '?rest-api=pay_notify&channel=' . urlencode($channel);
    }

    public static function returnUrl() {
        return rtrim(SITE_URL, '/') . '/user';
    }

    public static function getChannelLabel($pay_type, $api_index = null) {
        $names = [0 => '官方', 1 => '虎皮椒', 2 => '易支付', 3 => '码支付'];
        if ($api_index === null) {
            $cfg = self::getConfig();
            $api_index = ($pay_type === 'wechat')
                ? (int)($cfg['weixinapi'] ?? 0)
                : (int)($cfg['alipayapi'] ?? 0);
        }
        $prefix = ($pay_type === 'wechat') ? '微信' : '支付宝';
        return $prefix . ' · ' . ($names[$api_index] ?? '未知');
    }

    public static function isChannelReady($pay_type, $api_index = null) {
        $cfg = self::getConfig();
        if ($api_index === null) {
            $api_index = ($pay_type === 'wechat')
                ? (int)($cfg['weixinapi'] ?? 0)
                : (int)($cfg['alipayapi'] ?? 0);
        }
        switch ($api_index) {
            case 1:
                if ($pay_type === 'wechat') {
                    return trim($cfg['hpj_wx_id'] ?? '') !== '' && trim($cfg['hpj_wx_key'] ?? '') !== '';
                }
                return trim($cfg['hpj_alipay_id'] ?? '') !== '' && trim($cfg['hpj_alipay_key'] ?? '') !== '';
            case 2:
                return trim($cfg['epay_api'] ?? '') !== '' && trim($cfg['epay_id'] ?? '') !== '' && trim($cfg['epay_key'] ?? '') !== '';
            case 3:
                return trim($cfg['codepay_api'] ?? '') !== '' && trim($cfg['codepay_id'] ?? '') !== '' && trim($cfg['codepay_key'] ?? '') !== '';
            default:
                if ($pay_type === 'wechat') {
                    return trim($cfg['wx_appid'] ?? '') !== '' && trim($cfg['wx_mchid'] ?? '') !== '' && trim($cfg['wx_key'] ?? '') !== '';
                }
                $f2f = trim($cfg['alipay_f2f_appid'] ?? '') !== '' && trim($cfg['alipay_f2f_private_key'] ?? '') !== '';
                $web = trim($cfg['alipay_web_appid'] ?? '') !== '' && trim($cfg['alipay_web_private_key'] ?? '') !== '';
                return $f2f || $web;
        }
    }

    public static function getConfigStatus() {
        return [
            'wechat' => [
                'channel' => (int)(self::getConfig()['weixinapi'] ?? 0),
                'label'   => self::getChannelLabel('wechat'),
                'ready'   => self::isChannelReady('wechat'),
            ],
            'alipay' => [
                'channel' => (int)(self::getConfig()['alipayapi'] ?? 0),
                'label'   => self::getChannelLabel('alipay'),
                'ready'   => self::isChannelReady('alipay'),
            ],
        ];
    }

    public static function getAdminUrls() {
        return [
            'notify_official' => self::notifyUrl('official'),
            'notify_hpj'      => self::notifyUrl('hpj'),
            'notify_epay'     => self::notifyUrl('epay'),
            'notify_codepay'  => self::notifyUrl('codepay'),
            'cashier'         => self::officialCashierUrl('{order_no}'),
            'return'          => self::returnUrl(),
        ];
    }

    public static function isDirectRedirect() {
        $cfg = self::getConfig();
        return !empty($cfg['pay_direct_redirect']);
    }

    /** 支付功能总开关：未写入 payment_enabled 时默认开启 */
    public static function isEnabled() {
        $cfg = self::getConfig();
        if (!array_key_exists('payment_enabled', $cfg)) {
            return true;
        }
        return !empty($cfg['payment_enabled']);
    }

    public static function createPayment($order) {
        if (!self::isEnabled()) {
            return ['code' => 0, 'msg' => '支付功能已关闭'];
        }
        require_once HOPE_ROOT . 'system/lib/payment/pay_channel.php';
        if (!self::isDirectRedirect()) {
            $channel = self::getChannel($order['pay_type']);
            $method = ($channel === 'official' && ($order['pay_type'] ?? '') === 'alipay') ? 'redirect' : 'qrcode';
            return ['code' => 1, 'method' => $method, 'payment_url' => self::officialCashierUrl($order['order_no'])];
        }
        return Pay_Channel::createPayResult($order);
    }

    public static function queryAndSync(array $order) {
        require_once HOPE_ROOT . 'system/lib/payment/pay_channel.php';
        $Pay_Model = new Pay_Model();
        if ((int)($order['status'] ?? 0) === 1) {
            return ['synced' => false, 'paid' => true, 'msg' => '订单已是已支付状态'];
        }
        if ((int)($order['status'] ?? 0) !== 0) {
            return ['synced' => false, 'paid' => false, 'msg' => '仅待支付订单可查询同步'];
        }
        $remote = Pay_Channel::queryRemote($order);
        if (empty($remote['paid'])) {
            return ['synced' => false, 'paid' => false, 'msg' => $remote['msg'] ?? '未支付'];
        }
        $trade_no = $remote['trade_no'] ?? '';
        $affected = $Pay_Model->markPaid($order['order_no'], $trade_no, $remote['raw'] ?? $remote);
        if ($affected && ($order['biz_type'] ?? '') === 'recharge') {
            hope_load_user_center();
            hope_user_center_complete_recharge($order);
        }
        if ($affected && ($order['biz_type'] ?? '') === 'apply') {
            ApplyPayment::completePurchase($order);
        }
        if ($affected) {
            doAction('pay_order_paid', $order);
        }
        return ['synced' => (bool)$affected, 'paid' => true, 'msg' => '已同步为已支付'];
    }

    public static function refundOrder(array $order) {
        require_once HOPE_ROOT . 'system/lib/payment/pay_channel.php';
        if ((int)($order['status'] ?? 0) !== 1) {
            return ['ok' => false, 'msg' => '仅已支付订单可退款'];
        }
        $payType = (string)($order['pay_type'] ?? '');
        // 余额支付：站内退款，不走第三方
        if ($payType === 'balance') {
            $Pay_Model = new Pay_Model();
            $Pay_Model->markRefunded($order['order_no'], ['method' => 'balance']);
            doAction('pay_order_refunded', $order);
            return ['ok' => true, 'msg' => '余额已退回'];
        }
        $remote = Pay_Channel::refundRemote($order);
        if (empty($remote['ok'])) {
            return $remote;
        }
        $Pay_Model = new Pay_Model();
        $Pay_Model->markRefunded($order['order_no'], $remote['raw'] ?? $remote);
        if (($order['biz_type'] ?? '') === 'recharge') {
            hope_load_user_center();
            hope_user_center_reverse_recharge($order);
        }
        doAction('pay_order_refunded', $order);
        return ['ok' => true, 'msg' => $remote['msg'] ?? '退款成功'];
    }

    public static function officialCashierUrl($order_no) {
        return SITE_URL . '?rest-api=pay_cashier&order_no=' . urlencode($order_no);
    }

    public static function verifyNotify($channel, $data, $raw = '') {
        $cfg = self::getConfig();
        switch ($channel) {
            case 'epay':
                $key = $cfg['epay_key'] ?? '';
                $sign = $data['sign'] ?? '';
                unset($data['sign'], $data['sign_type']);
                ksort($data);
                return $sign === md5(urldecode(http_build_query($data)) . $key);
            case 'codepay':
                $key = $cfg['codepay_key'] ?? '';
                $sign = $data['sign'] ?? '';
                unset($data['sign']);
                ksort($data);
                return $sign === md5(http_build_query($data) . $key);
            case 'hpj':
                $key = $cfg['hpj_wx_key'] ?? ($cfg['hpj_alipay_key'] ?? '');
                $hash = $data['hash'] ?? '';
                unset($data['hash']);
                ksort($data);
                $parts = [];
                foreach ($data as $k => $v) {
                    if ($v !== '') $parts[] = "$k=$v";
                }
                return $hash === md5(implode('&', $parts) . $key);
            case 'official':
                require_once HOPE_ROOT . 'system/lib/payment/official_pay.php';
                if (!empty($data['trade_status'])) {
                    return Pay_Official::verifyAlipayNotify($data);
                }
                if ($raw === '') {
                    $raw = file_get_contents('php://input');
                }
                if ($raw !== '' && strpos(trim($raw), '<') === 0) {
                    return Pay_Official::verifyWechatNotify($raw);
                }
                return false;
            default:
                return false;
        }
    }

    public static function extractOrderNo($channel, $data) {
        if (!empty($data['out_trade_no'])) return $data['out_trade_no'];
        if (!empty($data['trade_order_id'])) return $data['trade_order_id'];
        if (!empty($data['pay_id'])) return $data['pay_id'];
        if (!empty($data['order_no'])) return $data['order_no'];
        return '';
    }

    public static function extractTradeNo($channel, $data) {
        if (!empty($data['trade_no'])) return $data['trade_no'];
        if (!empty($data['transaction_id'])) return $data['transaction_id'];
        if (!empty($data['open_order_id'])) return $data['open_order_id'];
        return '';
    }
}
