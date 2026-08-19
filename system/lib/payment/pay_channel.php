<?php
defined('HOPE_ROOT') || exit('access denied!');

/**
 * 各支付通道：发起支付、查询、退款
 */
class Pay_Channel {

    public static function createPayResult(array $order) {
        $channel = Pay_Sdk::getChannel($order['pay_type']);
        switch ($channel) {
            case 'hpj':
                return self::hpjCreate($order);
            case 'epay':
                return self::epayCreate($order);
            case 'codepay':
                return self::codepayCreate($order);
            default:
                return self::officialCreate($order);
        }
    }

    public static function queryRemote(array $order) {
        $channel = Pay_Sdk::getChannel($order['pay_type']);
        switch ($channel) {
            case 'hpj':
                return self::hpjQuery($order);
            case 'epay':
                return self::epayQuery($order);
            case 'codepay':
                return self::codepayQuery($order);
            default:
                require_once HOPE_ROOT . 'system/lib/payment/official_pay.php';
                return Pay_Official::queryOrder($order);
        }
    }

    public static function refundRemote(array $order) {
        $channel = Pay_Sdk::getChannel($order['pay_type']);
        switch ($channel) {
            case 'epay':
                return self::epayRefund($order);
            case 'official':
                require_once HOPE_ROOT . 'system/lib/payment/official_pay.php';
                return Pay_Official::refundOrder($order);
            default:
                return ['ok' => false, 'msg' => '当前支付通道暂不支持在线退款，请至支付平台后台手动退款'];
        }
    }

    private static function hpjApi(array $cfg, $is_wx) {
        $default = 'https://api.xunhupay.com/payment/do.html';
        if ($is_wx) {
            $api = trim($cfg['hpj_wx_api'] ?? '');
        } else {
            $api = trim($cfg['hpj_alipay_api'] ?? '');
        }
        if ($api === '') {
            $api = $default;
        }
        return rtrim($api, '/');
    }

    private static function hpjCreate(array $order) {
        $cfg = Pay_Sdk::getConfig();
        $is_wx = ($order['pay_type'] === 'wechat');
        $api = self::hpjApi($cfg, $is_wx);
        $appid = $is_wx ? ($cfg['hpj_wx_id'] ?? '') : ($cfg['hpj_alipay_id'] ?? '');
        $appsecret = $is_wx ? ($cfg['hpj_wx_key'] ?? '') : ($cfg['hpj_alipay_key'] ?? '');
        if ($appid === '' || $appsecret === '') {
            return ['code' => 0, 'msg' => '虎皮椒支付未配置完整'];
        }
        $data = [
            'version'        => '1.1',
            'appid'          => $appid,
            'plugins'        => $order['pay_type'],
            'trade_order_id' => $order['order_no'],
            'total_fee'      => number_format((float)$order['amount'], 2, '.', ''),
            'title'          => mb_substr($order['title'] ?: '在线支付', 0, 42),
            'time'           => time(),
            'notify_url'     => Pay_Sdk::notifyUrl('hpj'),
            'return_url'     => Pay_Sdk::returnUrl(),
            'nonce_str'      => md5(uniqid('', true)),
        ];
        if ($is_wx && self::isMobile()) {
            $data['type'] = 'WAP';
            $data['wap_url'] = rtrim(SITE_URL, '/');
            $data['wap_name'] = Option::get('blogname') ?: 'Hope CMS';
        }
        $data['hash'] = self::hpjHash($data, $appsecret);
        $raw = self::curlPost($api, json_encode($data));
        $result = $raw ? json_decode($raw, true) : null;
        if (empty($result) || ($result['errcode'] ?? 1) != 0) {
            return ['code' => 0, 'msg' => $result['errmsg'] ?? ($raw ?: '虎皮椒创建订单失败')];
        }
        if (self::isMobile() && !empty($result['url'])) {
            return ['code' => 1, 'method' => 'redirect', 'payment_url' => $result['url']];
        }
        $qr = $result['url_qrcode'] ?? ($result['url'] ?? '');
        if ($qr === '') {
            return ['code' => 0, 'msg' => '未获取到支付链接'];
        }
        $isImage = !empty($result['url_qrcode']);
        return ['code' => 1, 'method' => 'qrcode', 'payment_url' => $result['url'] ?? $qr, 'qrcode' => $qr, 'qrcode_is_image' => $isImage];
    }

    private static function epayCreate(array $order) {
        $cfg = Pay_Sdk::getConfig();
        $api = rtrim($cfg['epay_api'] ?? '', '/');
        $pid = $cfg['epay_id'] ?? '';
        $key = $cfg['epay_key'] ?? '';
        if ($api === '' || $pid === '' || $key === '') {
            return ['code' => 0, 'msg' => '易支付未配置完整'];
        }
        $type = $order['pay_type'] === 'wechat' ? 'wxpay' : 'alipay';
        $params = [
            'pid'          => $pid,
            'type'         => $type,
            'out_trade_no' => $order['order_no'],
            'notify_url'   => Pay_Sdk::notifyUrl('epay'),
            'return_url'   => Pay_Sdk::returnUrl(),
            'name'         => mb_substr($order['title'] ?: '在线支付', 0, 64),
            'money'        => number_format((float)$order['amount'], 2, '.', ''),
        ];
        ksort($params);
        $params['sign'] = md5(urldecode(http_build_query($params)) . $key);
        $params['sign_type'] = 'MD5';
        return ['code' => 1, 'method' => 'redirect', 'payment_url' => $api . '/submit.php?' . http_build_query($params)];
    }

    private static function codepayCreate(array $order) {
        $cfg = Pay_Sdk::getConfig();
        $api = rtrim($cfg['codepay_api'] ?? '', '/');
        $id = $cfg['codepay_id'] ?? '';
        $key = $cfg['codepay_key'] ?? '';
        if ($api === '' || $id === '' || $key === '') {
            return ['code' => 0, 'msg' => '码支付未配置完整'];
        }
        $type = $order['pay_type'] === 'wechat' ? 3 : 1;
        $params = [
            'id'         => $id,
            'type'       => $type,
            'price'      => number_format((float)$order['amount'], 2, '.', ''),
            'pay_id'     => $order['order_no'],
            'notify_url' => Pay_Sdk::notifyUrl('codepay'),
            'return_url' => Pay_Sdk::returnUrl(),
        ];
        ksort($params);
        $params['sign'] = md5(http_build_query($params) . $key);
        return ['code' => 1, 'method' => 'redirect', 'payment_url' => $api . '?' . http_build_query($params)];
    }

    private static function officialCreate(array $order) {
        if ($order['pay_type'] === 'alipay') {
            $cfg = Pay_Sdk::getConfig();
            $web_appid = trim($cfg['alipay_web_appid'] ?? '');
            $web_key = trim($cfg['alipay_web_private_key'] ?? '');
            if ($web_appid !== '' && $web_key !== '') {
                return ['code' => 1, 'method' => 'redirect', 'payment_url' => Pay_Sdk::officialCashierUrl($order['order_no'])];
            }
        }
        return ['code' => 1, 'method' => 'qrcode', 'payment_url' => Pay_Sdk::officialCashierUrl($order['order_no'])];
    }

    private static function hpjQuery(array $order) {
        $cfg = Pay_Sdk::getConfig();
        $is_wx = ($order['pay_type'] === 'wechat');
        $appid = $is_wx ? ($cfg['hpj_wx_id'] ?? '') : ($cfg['hpj_alipay_id'] ?? '');
        $appsecret = $is_wx ? ($cfg['hpj_wx_key'] ?? '') : ($cfg['hpj_alipay_key'] ?? '');
        if ($appid === '' || $appsecret === '') {
            return ['paid' => false, 'msg' => '虎皮椒未配置'];
        }
        $request = [
            'appid'           => $appid,
            'out_trade_order' => $order['order_no'],
            'time'            => time(),
            'nonce_str'       => md5(uniqid('', true)),
        ];
        $request['hash'] = self::hpjHash($request, $appsecret);
        $api = self::hpjApi($cfg, $is_wx);
        $query_url = preg_replace('#/payment/do\.html$#', '/payment/query.html', $api);
        if ($query_url === $api) {
            $query_url = 'https://api.xunhupay.com/payment/query.html';
        }
        $raw = self::curlPost($query_url, http_build_query($request));
        $result = $raw ? json_decode($raw, true) : null;
        $status = $result['data']['status'] ?? '';
        return [
            'paid'      => $status === 'OD',
            'trade_no'  => $result['data']['open_order_id'] ?? '',
            'raw'       => $result,
            'msg'       => $status === 'OD' ? '已支付' : '未支付',
        ];
    }

    private static function epayQuery(array $order) {
        $cfg = Pay_Sdk::getConfig();
        $api = rtrim($cfg['epay_api'] ?? '', '/');
        $pid = $cfg['epay_id'] ?? '';
        $key = $cfg['epay_key'] ?? '';
        if ($api === '' || $pid === '' || $key === '') {
            return ['paid' => false, 'msg' => '易支付未配置'];
        }
        $trade_no = $order['trade_no'] ?: $order['order_no'];
        $url = $api . '/api.php?act=order&pid=' . urlencode($pid) . '&key=' . urlencode($key) . '&out_trade_no=' . urlencode($order['order_no']);
        $raw = self::curlGet($url);
        $result = $raw ? json_decode($raw, true) : null;
        $paid = isset($result['status']) && (int)$result['status'] === 1;
        return [
            'paid'     => $paid,
            'trade_no' => $result['trade_no'] ?? ($order['trade_no'] ?? ''),
            'raw'      => $result,
            'msg'      => $paid ? '已支付' : '未支付',
        ];
    }

    private static function codepayQuery(array $order) {
        return ['paid' => false, 'msg' => '码支付暂不支持主动查询，请等待异步通知'];
    }

    private static function epayRefund(array $order) {
        $cfg = Pay_Sdk::getConfig();
        $api = rtrim($cfg['epay_api'] ?? '', '/');
        $pid = $cfg['epay_id'] ?? '';
        $key = $cfg['epay_key'] ?? '';
        if ($api === '' || $pid === '' || $key === '') {
            return ['ok' => false, 'msg' => '易支付未配置'];
        }
        $trade_no = $order['trade_no'] ?: $order['order_no'];
        $money = number_format((float)$order['amount'], 2, '.', '');
        $url = $api . '/api.php?act=refund';
        $post = 'pid=' . urlencode($pid) . '&key=' . urlencode($key) . '&trade_no=' . urlencode($trade_no) . '&money=' . urlencode($money);
        $raw = self::curlPost($url, $post);
        $result = $raw ? json_decode($raw, true) : null;
        if (isset($result['code']) && (int)$result['code'] === 1) {
            return ['ok' => true, 'msg' => '退款成功', 'raw' => $result];
        }
        return ['ok' => false, 'msg' => $result['msg'] ?? ($raw ?: '退款失败'), 'raw' => $result];
    }

    private static function hpjHash(array $data, $key) {
        ksort($data);
        $parts = [];
        foreach ($data as $k => $v) {
            if ($k === 'hash' || $v === '' || $v === null) {
                continue;
            }
            $parts[] = "$k=$v";
        }
        return md5(implode('&', $parts) . $key);
    }

    private static function isMobile() {
        if (empty($_SERVER['HTTP_USER_AGENT'])) {
            return false;
        }
        return (bool)preg_match('/(android|iphone|ipad|mobile)/i', $_SERVER['HTTP_USER_AGENT']);
    }

    private static function curlPost($url, $body, $headers = []) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    private static function curlGet($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
}
