<?php
defined('HOPE_ROOT') || exit('access denied!');

/**
 * 官方支付宝 / 微信收银台
 */
class Pay_Official {

    public static function notifyUrl() {
        return Pay_Sdk::notifyUrl('official');
    }

    public static function returnUrl() {
        return Pay_Sdk::returnUrl();
    }

    public static function renderCashier(array $order) {
        if ((int)($order['status'] ?? 0) === 1) {
            self::renderMessage('订单已支付', self::returnUrl());
            return;
        }
        if ((int)($order['status'] ?? 0) === 2) {
            self::renderMessage('订单已关闭', self::returnUrl());
            return;
        }
        $pay = self::createPayResult($order);
        if (empty($pay['code'])) {
            self::renderMessage($pay['msg'] ?? '创建支付失败');
            return;
        }
        if (($pay['method'] ?? '') === 'form' && !empty($pay['html'])) {
            echo $pay['html'];
            return;
        }
        if (($pay['method'] ?? '') === 'redirect' && !empty($pay['payment_url'])) {
            header('Location: ' . $pay['payment_url']);
            exit;
        }
        $qr = $pay['qrcode'] ?? ($pay['payment_url'] ?? '');
        if ($qr === '') {
            self::renderMessage('未获取到支付二维码');
            return;
        }
        self::renderQrPage($order, $qr);
    }

    /**
     * 官方通道创建支付（供收银台 HTML / JSON 复用）
     * @return array{code:int,method?:string,qrcode?:string,payment_url?:string,html?:string,msg?:string}
     */
    public static function createPayResult(array $order) {
        if (($order['pay_type'] ?? '') === 'wechat') {
            return self::createWechatNative($order);
        }
        return self::createAlipay($order);
    }

    private static function createAlipay(array $order) {
        $cfg = Pay_Sdk::getConfig();
        $web_appid = trim($cfg['alipay_web_appid'] ?? '');
        $web_key = trim($cfg['alipay_web_private_key'] ?? '');
        if ($web_appid !== '' && $web_key !== '') {
            $form = self::buildAlipayPagePayForm($order, $web_appid, $web_key);
            if ($form !== '') {
                return ['code' => 1, 'method' => 'form', 'html' => $form, 'payment_url' => Pay_Sdk::officialCashierUrl($order['order_no'])];
            }
        }
        $appid = trim($cfg['alipay_f2f_appid'] ?? '');
        $prikey = trim($cfg['alipay_f2f_private_key'] ?? '');
        if ($appid === '' || $prikey === '') {
            return ['code' => 0, 'msg' => '支付宝官方支付未配置，请在后台填写当面付或网站应用参数'];
        }
        $result = self::alipayRequest($appid, $prikey, 'alipay.trade.precreate', [
            'out_trade_no'   => $order['order_no'],
            'total_amount'   => number_format((float)$order['amount'], 2, '.', ''),
            'subject'        => mb_substr($order['title'] ?: '在线支付', 0, 64),
            'timeout_express'=> '30m',
        ]);
        $resp = $result['alipay_trade_precreate_response'] ?? [];
        if (($resp['code'] ?? '') !== '10000' || empty($resp['qr_code'])) {
            $msg = $resp['sub_msg'] ?? ($resp['msg'] ?? '创建支付宝订单失败');
            return ['code' => 0, 'msg' => $msg];
        }
        $qr = $resp['qr_code'];
        return ['code' => 1, 'method' => 'qrcode', 'qrcode' => $qr, 'payment_url' => $qr];
    }

    private static function createWechatNative(array $order) {
        $cfg = Pay_Sdk::getConfig();
        $appid = trim($cfg['wx_appid'] ?? '');
        $mchid = trim($cfg['wx_mchid'] ?? '');
        $key = trim($cfg['wx_key'] ?? '');
        if ($appid === '' || $mchid === '' || $key === '') {
            return ['code' => 0, 'msg' => '微信官方支付未配置，请在后台填写微信 APPID、商户号与 KEY'];
        }
        $total = (int)round((float)$order['amount'] * 100);
        if ($total <= 0) {
            return ['code' => 0, 'msg' => '订单金额无效'];
        }
        $params = [
            'appid'            => $appid,
            'mch_id'           => $mchid,
            'nonce_str'        => self::nonceStr(),
            'body'             => mb_substr($order['title'] ?: '在线支付', 0, 64),
            'out_trade_no'     => $order['order_no'],
            'total_fee'        => (string)$total,
            'spbill_create_ip' => self::clientIp(),
            'notify_url'       => self::notifyUrl(),
            'trade_type'       => 'NATIVE',
        ];
        $params['sign'] = self::wechatSign($params, $key);
        $xml = self::arrayToXml($params);
        $raw = self::curlPost('https://api.mch.weixin.qq.com/pay/unifiedorder', $xml, ['Content-Type: text/xml']);
        $data = self::xmlToArray($raw);
        if (($data['return_code'] ?? '') !== 'SUCCESS' || ($data['result_code'] ?? '') !== 'SUCCESS' || empty($data['code_url'])) {
            $msg = $data['err_code_des'] ?? ($data['return_msg'] ?? '创建微信订单失败');
            return ['code' => 0, 'msg' => $msg];
        }
        $qr = $data['code_url'];
        return ['code' => 1, 'method' => 'qrcode', 'qrcode' => $qr, 'payment_url' => $qr];
    }

    private static function buildAlipayPagePayForm(array $order, $appid, $prikey) {
        $biz = [
            'out_trade_no' => $order['order_no'],
            'product_code' => 'FAST_INSTANT_TRADE_PAY',
            'total_amount' => number_format((float)$order['amount'], 2, '.', ''),
            'subject'      => mb_substr($order['title'] ?: '在线支付', 0, 64),
        ];
        $params = [
            'app_id'     => $appid,
            'method'     => 'alipay.trade.page.pay',
            'format'     => 'JSON',
            'charset'    => 'utf-8',
            'sign_type'  => 'RSA2',
            'timestamp'  => date('Y-m-d H:i:s'),
            'version'    => '1.0',
            'notify_url' => self::notifyUrl(),
            'return_url' => self::returnUrl(),
            'biz_content'=> json_encode($biz, JSON_UNESCAPED_UNICODE),
        ];
        $params['sign'] = self::alipaySign($params, $prikey);
        $action = 'https://openapi.alipay.com/gateway.do?charset=utf-8';
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>跳转支付宝</title></head><body>';
        $html .= '<form id="alipay" method="post" action="' . htmlspecialchars($action, ENT_QUOTES, 'UTF-8') . '">';
        foreach ($params as $k => $v) {
            $html .= '<input type="hidden" name="' . htmlspecialchars($k, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars($v, ENT_QUOTES, 'UTF-8') . '">';
        }
        $html .= '</form><p>正在跳转支付宝…</p><script>document.getElementById("alipay").submit();</script></body></html>';
        return $html;
    }

    private static function alipayRequest($appid, $prikey, $method, array $biz) {
        $params = [
            'app_id'     => $appid,
            'method'     => $method,
            'format'     => 'JSON',
            'charset'    => 'utf-8',
            'sign_type'  => 'RSA2',
            'timestamp'  => date('Y-m-d H:i:s'),
            'version'    => '1.0',
            'notify_url' => self::notifyUrl(),
            'biz_content'=> json_encode($biz, JSON_UNESCAPED_UNICODE),
        ];
        $params['sign'] = self::alipaySign($params, $prikey);
        $raw = self::curlPost('https://openapi.alipay.com/gateway.do?charset=utf-8', http_build_query($params));
        $json = json_decode($raw, true);
        return is_array($json) ? $json : [];
    }

    public static function verifyAlipayNotify(array $data) {
        $sign = $data['sign'] ?? '';
        $sign_type = $data['sign_type'] ?? 'RSA2';
        if ($sign === '') {
            return false;
        }
        unset($data['sign'], $data['sign_type']);
        ksort($data);
        $parts = [];
        foreach ($data as $k => $v) {
            if ($v !== '' && $v !== null && substr((string)$v, 0, 1) !== '@') {
                $parts[] = $k . '=' . $v;
            }
        }
        $content = implode('&', $parts);
        $pub = trim(Pay_Sdk::getConfig()['alipay_public_key'] ?? '');
        if ($pub === '') {
            return false;
        }
        $res = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($pub, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
        $algo = ($sign_type === 'RSA2') ? OPENSSL_ALGO_SHA256 : OPENSSL_ALGO_SHA1;
        return openssl_verify($content, base64_decode($sign), $res, $algo) === 1
            && in_array($data['trade_status'] ?? '', ['TRADE_SUCCESS', 'TRADE_FINISHED'], true);
    }

    public static function verifyWechatNotify($raw) {
        $data = self::parseWechatNotify($raw);
        if (($data['return_code'] ?? '') !== 'SUCCESS' || ($data['result_code'] ?? '') !== 'SUCCESS') {
            return false;
        }
        $sign = $data['sign'] ?? '';
        unset($data['sign']);
        $key = trim(Pay_Sdk::getConfig()['wx_key'] ?? '');
        if ($key === '' || $sign === '') {
            return false;
        }
        return $sign === self::wechatSign($data, $key);
    }

    public static function parseWechatNotify($raw) {
        return self::xmlToArray($raw);
    }

    public static function wechatNotifyResponse($ok = true) {
        $code = $ok ? 'SUCCESS' : 'FAIL';
        return '<xml><return_code><![CDATA[' . $code . ']]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
    }

    private static function alipaySign(array $params, $prikey) {
        ksort($params);
        $parts = [];
        foreach ($params as $k => $v) {
            if ($v !== '' && $v !== null && substr((string)$v, 0, 1) !== '@') {
                $parts[] = $k . '=' . $v;
            }
        }
        $content = implode('&', $parts);
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" . wordwrap($prikey, 64, "\n", true) . "\n-----END RSA PRIVATE KEY-----";
        openssl_sign($content, $sign, $res, OPENSSL_ALGO_SHA256);
        return base64_encode($sign);
    }

    private static function wechatSign(array $params, $key) {
        ksort($params);
        $parts = [];
        foreach ($params as $k => $v) {
            if ($v !== '' && $v !== null && $k !== 'sign') {
                $parts[] = $k . '=' . $v;
            }
        }
        return strtoupper(md5(implode('&', $parts) . '&key=' . $key));
    }

    private static function renderQrPage(array $order, $qr_url) {
        require_once HOPE_ROOT . 'system/lib/payment/pay_cashier_page.php';
        Pay_Cashier::renderQrPage($order, $qr_url);
    }

    public static function renderMessage($msg, $back_url = '') {
        $text = htmlspecialchars($msg, ENT_QUOTES, 'UTF-8');
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>支付提示</title></head><body style="font-family:sans-serif;text-align:center;padding:40px">';
        echo '<p>' . $text . '</p>';
        if ($back_url !== '') {
            echo '<p><a href="' . htmlspecialchars($back_url, ENT_QUOTES, 'UTF-8') . '">返回个人中心</a></p>';
        }
        echo '</body></html>';
    }

    public static function queryOrder(array $order) {
        if (($order['pay_type'] ?? '') === 'wechat') {
            return self::queryWechat($order);
        }
        return self::queryAlipay($order);
    }

    public static function refundOrder(array $order) {
        if (($order['pay_type'] ?? '') === 'wechat') {
            return ['ok' => false, 'msg' => '微信官方退款需配置 API 证书，请至微信商户平台手动退款'];
        }
        return self::refundAlipay($order);
    }

    private static function queryWechat(array $order) {
        $cfg = Pay_Sdk::getConfig();
        $appid = trim($cfg['wx_appid'] ?? '');
        $mchid = trim($cfg['wx_mchid'] ?? '');
        $key = trim($cfg['wx_key'] ?? '');
        if ($appid === '' || $mchid === '' || $key === '') {
            return ['paid' => false, 'msg' => '微信官方未配置'];
        }
        $params = [
            'appid'        => $appid,
            'mch_id'       => $mchid,
            'out_trade_no' => $order['order_no'],
            'nonce_str'    => self::nonceStr(),
        ];
        $params['sign'] = self::wechatSign($params, $key);
        $xml = self::arrayToXml($params);
        $raw = self::curlPost('https://api.mch.weixin.qq.com/pay/orderquery', $xml, ['Content-Type: text/xml']);
        $data = self::xmlToArray($raw);
        $paid = ($data['return_code'] ?? '') === 'SUCCESS'
            && ($data['result_code'] ?? '') === 'SUCCESS'
            && ($data['trade_state'] ?? '') === 'SUCCESS';
        return [
            'paid'     => $paid,
            'trade_no' => $data['transaction_id'] ?? ($order['trade_no'] ?? ''),
            'raw'      => $data,
            'msg'      => $paid ? '已支付' : ($data['trade_state_desc'] ?? '未支付'),
        ];
    }

    private static function queryAlipay(array $order) {
        $cfg = Pay_Sdk::getConfig();
        $appid = trim($cfg['alipay_web_appid'] ?? '') ?: trim($cfg['alipay_f2f_appid'] ?? '');
        $prikey = trim($cfg['alipay_web_private_key'] ?? '') ?: trim($cfg['alipay_f2f_private_key'] ?? '');
        if ($appid === '' || $prikey === '') {
            return ['paid' => false, 'msg' => '支付宝官方未配置'];
        }
        $result = self::alipayRequest($appid, $prikey, 'alipay.trade.query', [
            'out_trade_no' => $order['order_no'],
        ]);
        $resp = $result['alipay_trade_query_response'] ?? [];
        $paid = ($resp['code'] ?? '') === '10000'
            && in_array($resp['trade_status'] ?? '', ['TRADE_SUCCESS', 'TRADE_FINISHED'], true);
        return [
            'paid'     => $paid,
            'trade_no' => $resp['trade_no'] ?? ($order['trade_no'] ?? ''),
            'raw'      => $resp,
            'msg'      => $paid ? '已支付' : ($resp['sub_msg'] ?? ($resp['msg'] ?? '未支付')),
        ];
    }

    private static function refundAlipay(array $order) {
        $cfg = Pay_Sdk::getConfig();
        $appid = trim($cfg['alipay_web_appid'] ?? '') ?: trim($cfg['alipay_f2f_appid'] ?? '');
        $prikey = trim($cfg['alipay_web_private_key'] ?? '') ?: trim($cfg['alipay_f2f_private_key'] ?? '');
        if ($appid === '' || $prikey === '') {
            return ['ok' => false, 'msg' => '支付宝官方未配置'];
        }
        $result = self::alipayRequest($appid, $prikey, 'alipay.trade.refund', [
            'out_trade_no'   => $order['order_no'],
            'refund_amount'  => number_format((float)$order['amount'], 2, '.', ''),
            'out_request_no' => $order['order_no'] . 'R' . time(),
        ]);
        $resp = $result['alipay_trade_refund_response'] ?? [];
        if (($resp['code'] ?? '') === '10000') {
            return ['ok' => true, 'msg' => '退款成功', 'raw' => $resp];
        }
        return ['ok' => false, 'msg' => $resp['sub_msg'] ?? ($resp['msg'] ?? '退款失败'), 'raw' => $resp];
    }

    private static function nonceStr($len = 32) {
        return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $len);
    }

    private static function clientIp() {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    private static function arrayToXml(array $data) {
        $xml = '<xml>';
        foreach ($data as $k => $v) {
            if (is_numeric($v)) {
                $xml .= '<' . $k . '>' . $v . '</' . $k . '>';
            } else {
                $xml .= '<' . $k . '><![CDATA[' . $v . ']]></' . $k . '>';
            }
        }
        return $xml . '</xml>';
    }

    private static function xmlToArray($xml) {
        if ($xml === '' || $xml === false) {
            return [];
        }
        libxml_disable_entity_loader(true);
        $obj = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        return $obj ? json_decode(json_encode($obj), true) : [];
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
}
