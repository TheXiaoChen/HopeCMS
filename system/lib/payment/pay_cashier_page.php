<?php
defined('HOPE_ROOT') || exit('access denied!');

require_once HOPE_ROOT . 'system/lib/payment/pay_sdk.php';
require_once HOPE_ROOT . 'system/lib/payment/pay_channel.php';
require_once HOPE_ROOT . 'system/lib/payment/official_pay.php';

/**
 * 统一收银台页面（样式参考 payment.php）
 */
class Pay_Cashier {

    /** 是否请求 JSON（format=json / Accept / XHR） */
    public static function wantsJson() {
        $format = '';
        if (class_exists('Input')) {
            $format = Input::getStrVar('format', Input::getStrVar('json', ''));
        } else {
            $format = (string)($_GET['format'] ?? (isset($_GET['json']) ? (string)$_GET['json'] : ''));
        }
        if ($format === 'json' || $format === '1' || $format === 'true') {
            return true;
        }
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (stripos($accept, 'application/json') !== false) {
            return true;
        }
        $xhr = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
        return $xhr === 'xmlhttprequest';
    }

    /**
     * 解析订单支付结果（不输出 HTML）
     */
    public static function resolve(array $order) {
        $status = (int)($order['status'] ?? 0);
        if ($status === 1) {
            return ['ok' => false, 'msg' => '订单已支付', 'status' => $status];
        }
        if ($status === 2) {
            return ['ok' => false, 'msg' => '订单已关闭', 'status' => $status];
        }
        if ($status === 3) {
            return ['ok' => false, 'msg' => '订单已退款', 'status' => $status];
        }

        $channel = Pay_Sdk::getChannel($order['pay_type'] ?? 'alipay');
        if ($channel === 'official') {
            $pay = Pay_Official::createPayResult($order);
        } else {
            $pay = Pay_Channel::createPayResult($order);
        }
        if (empty($pay['code'])) {
            return ['ok' => false, 'msg' => $pay['msg'] ?? '创建支付失败', 'status' => $status];
        }

        $method = $pay['method'] ?? 'redirect';
        $qr = (string)($pay['qrcode'] ?? '');
        $url = (string)($pay['payment_url'] ?? '');
        $formHtml = (string)($pay['html'] ?? '');
        $meta = [
            'order_no'   => $order['order_no'],
            'amount'     => number_format((float)$order['amount'], 2, '.', ''),
            'pay_type'   => $order['pay_type'] ?? 'alipay',
            'title'      => $order['title'] ?? '',
            'status'     => $status,
            'status_api' => SITE_URL . '?rest-api=pay_status&order_no=' . urlencode($order['order_no']),
            'trade'      => $order['order_no'],
        ];

        if ($method === 'form') {
            $payurl = $url !== '' ? $url : Pay_Sdk::officialCashierUrl($order['order_no']);
            return array_merge($meta, [
                'ok'          => true,
                'method'      => 'form',
                'form_html'   => $formHtml,
                'payment_url' => $payurl,
                'type'        => 2,
                'payurl'      => $payurl,
            ]);
        }

        if ($method === 'qrcode' || $qr !== '') {
            if ($qr === '' && $url !== '' && strpos($url, 'rest-api=pay_cashier') === false) {
                $qr = $url;
            }
            if ($qr === '') {
                return ['ok' => false, 'msg' => '未获取到支付二维码', 'status' => $status];
            }
            $isImage = array_key_exists('qrcode_is_image', $pay)
                ? (bool)$pay['qrcode_is_image']
                : self::isQrImageUrl($qr);
            return array_merge($meta, [
                'ok'              => true,
                'method'          => 'qrcode',
                'qrcode'          => $qr,
                'qrcode_is_image' => $isImage,
                'payment_url'     => $url !== '' ? $url : $qr,
                'html'            => self::buildShopPopupHtml($order, $qr, $isImage),
                'type'            => 1,
                'payurl'          => $url !== '' ? $url : $qr,
            ]);
        }

        if ($url === '') {
            return ['ok' => false, 'msg' => '未获取到支付链接', 'status' => $status];
        }
        return array_merge($meta, [
            'ok'          => true,
            'method'      => 'redirect',
            'payment_url' => $url,
            'type'        => 2,
            'payurl'      => $url,
        ]);
    }

    /** Shop 主题弹窗二维码 HTML（?user=pay） */
    public static function buildShopPopupHtml(array $order, $qr, $isImage = false) {
        $payType = ($order['pay_type'] ?? '') === 'wechat' ? 'wechat' : 'alipay';
        $pic = $payType === 'wechat' ? 'weixin' : 'alipay';
        $name = $payType === 'wechat' ? '微信支付' : '支付宝';
        $money = number_format((float)$order['amount'], 2, '.', '');
        $icon = SITE_URL . 'content/theme/Shop/style/img/' . $pic . '.png';
        $shopIcon = HOPE_ROOT . 'content/theme/Shop/style/img/' . $pic . '.png';
        if (!is_file($shopIcon)) {
            $icon = SITE_URL . 'content/admin/img/' . $payType .'pay.png';
        }
        $islink = $isImage ? '1' : '0';
        $qrEsc = htmlspecialchars((string)$qr, ENT_QUOTES, 'UTF-8');
        $iconEsc = htmlspecialchars($icon, ENT_QUOTES, 'UTF-8');
        return '<div class="qrcon"><h5><img src="' . $iconEsc . '" class="qr-pay"></h5>'
            . '<div class="title">' . $name . '扫码支付 <span class=price>' . $money . '</span> 元</div>'
            . '<div align="center" class="qrcode" id="pay-box-qrcode" data-islink="' . $islink . '" data-qrdata="' . $qrEsc . '"></div>'
            . '<div class="bottom ' . $pic . '">请使用' . $name . '扫一扫<br>扫描二维码支付</br></div></div>';
    }

    public static function isQrImageUrl($qr) {
        $qr = (string)$qr;
        if ($qr === '') {
            return false;
        }
        if (preg_match('#\.(png|jpe?g|gif|webp)(\?|$)#i', $qr)) {
            return true;
        }
        if (stripos($qr, 'data:image/') === 0) {
            return true;
        }
        return false;
    }

    public static function render(array $order) {
        $resolved = self::resolve($order);
        if (empty($resolved['ok'])) {
            self::renderMessage($resolved['msg'] ?? '支付失败', ($resolved['status'] ?? 0) === 1 ? Pay_Sdk::returnUrl() : '');
            return;
        }
        if (($resolved['method'] ?? '') === 'form' && !empty($resolved['form_html'])) {
            echo $resolved['form_html'];
            return;
        }
        if (($resolved['method'] ?? '') === 'redirect' && !empty($resolved['payment_url'])) {
            header('Location: ' . $resolved['payment_url']);
            exit;
        }
        $qr = $resolved['qrcode'] ?? ($resolved['payment_url'] ?? '');
        if ($qr === '') {
            self::renderMessage('未获取到支付二维码');
            return;
        }
        self::renderQrPage($order, $qr);
    }

    public static function renderQrPage(array $order, $qr_url) {
        $type = ($order['pay_type'] ?? '') === 'wechat' ? 'wechat' : 'alipay';
        $pay_name = $type === 'wechat' ? '微信' : '支付宝';
        $amount = number_format((float)$order['amount'], 2, '.', '');
        $order_title = htmlspecialchars($order['title'] ?: '在线支付', ENT_QUOTES, 'UTF-8');
        $order_no = htmlspecialchars($order['order_no'], ENT_QUOTES, 'UTF-8');
        $pay_name_esc = htmlspecialchars($pay_name, ENT_QUOTES, 'UTF-8');
        $back_url = Pay_Sdk::returnUrl();
        $back = htmlspecialchars($back_url, ENT_QUOTES, 'UTF-8');
        $status_api = json_encode(SITE_URL . '?rest-api=pay_status&order_no=' . urlencode($order['order_no']), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $qr_src = htmlspecialchars(self::qrImageSrc($qr_url), ENT_QUOTES, 'UTF-8');
        $img_base = SITE_URL . 'content/admin/img/';
        $type_icon = $img_base . $type .'pay.png';
        $logo_src = self::assetUrl($img_base . $type . '/logo.png', $type_icon);
        $sys_src = self::assetUrl($img_base . $type . '/' . $type . '-sys.png', '');
        ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= $pay_name_esc ?>支付收银台</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f2f2f4; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        .clearfix:after { content: "."; display: block; height: 0; clear: both; visibility: hidden; }
        .clearfix { display: block; }
        .xh-title {
            height: 75px; line-height: 75px; text-align: center; font-size: 30px; font-weight: 300;
            border-bottom: 2px solid #eee; background: #fff;
        }
        .xh-title img { height: 36px; vertical-align: middle; margin-right: 8px; }
        .qrbox { max-width: 900px; margin: 0 auto; padding: 85px 20px 20px 50px; }
        .qrbox .left { width: 40%; float: left; display: block; margin: 0 auto; }
        .qrbox .left .qrcon {
            border-radius: 10px; background: #fff; overflow: visible; text-align: center;
            padding-top: 25px; color: #555;
            box-shadow: 0 3px 3px 0 rgba(0, 0, 0, .05);
            transition: all .2s linear;
        }
        .qrbox .left .qrcon .logo { width: 100%; }
        .qrbox .left .qrcon h5 img { height: 30px; }
        .qrbox .left .qrcon .title { font-size: 16px; margin: 10px auto; width: 100%; padding: 0 16px; }
        .qrbox .left .qrcon .price { font-size: 22px; margin: 0 auto; width: 100%; color: #e74c3c; font-weight: 600; }
        .qrbox .left .qrcon .meta { font-size: 12px; color: #999; margin: 8px 0 4px; }
        .qrbox .left .qrcon .bottom {
            border-radius: 0 0 10px 10px; width: 100%; background: #32343d; color: #f2f2f2;
            padding: 15px 0; text-align: center; font-size: 14px;
        }
        .qrbox .sys { width: 60%; float: right; text-align: center; padding-top: 20px; font-size: 12px; color: #ccc; }
        .qrbox img { max-width: 100%; }
        #wechat_qrcode { width: 250px; height: 250px; margin: 0 auto; }
        #wechat_qrcode img { width: 250px; height: 250px; display: block; }
        #wechat_qrcode.expired img { opacity: .25; filter: grayscale(1); }
        .pay-hint { font-size: 13px; color: #888; margin: 10px 0; min-height: 20px; }
        .back-link { display: inline-block; margin: 12px 0 20px; color: #3498db; text-decoration: none; font-size: 14px; }
        @media (max-width: 767px) {
            .qrbox { padding: 20px; }
            .qrbox .left { width: 90%; float: none; }
            .qrbox .sys { display: none; }
        }
    </style>
</head>
<body>
    <div class="xh-title">
        <img src="<?= htmlspecialchars($type_icon, ENT_QUOTES, 'UTF-8') ?>" alt="">
        <?= $pay_name_esc ?>支付收银台
    </div>
    <div class="qrbox clearfix">
        <div class="left">
            <div class="qrcon">
                <h5><img src="<?= htmlspecialchars($logo_src, ENT_QUOTES, 'UTF-8') ?>" alt=""></h5>
                <div class="title"><?= $order_title ?></div>
                <div class="price"><?= $amount ?> 元</div>
                <div class="meta">订单号：<?= $order_no ?></div>
                <div align="center">
                    <div id="wechat_qrcode"><img id="qrcode" src="<?= $qr_src ?>" alt="支付二维码"></div>
                </div>
                <div class="pay-hint" id="pay-status-hint">等待支付中…</div>
                <div class="bottom">
                    请使用<?= $pay_name_esc ?>扫一扫<br>
                    扫描二维码支付
                </div>
                <a class="back-link" href="<?= $back ?>">返回</a>
            </div>
        </div>
        <?php if ($sys_src !== '') { ?>
        <div class="sys"><img src="<?= htmlspecialchars($sys_src, ENT_QUOTES, 'UTF-8') ?>" alt=""></div>
        <?php } ?>
    </div>
<script>
(function(){
    var timer, n = 0, expired = false;
    var qrBox = document.getElementById('wechat_qrcode');
    var hint = document.getElementById('pay-status-hint');
    function markExpired() {
        if (expired) return;
        expired = true;
        clearInterval(timer);
        qrBox.className = 'expired';
        hint.textContent = '二维码已过期，请重新发起支付';
    }
    function query() {
        fetch(<?= $status_api ?>)
            .then(function(r){ return r.json(); })
            .then(function(d){
                if (d && d.data && d.data.paid) {
                    hint.textContent = '支付成功，正在跳转…';
                    clearInterval(timer);
                    setTimeout(function(){ location.href = <?= json_encode($back_url, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>; }, 1500);
                }
            })
            .catch(function(){});
    }
    timer = setInterval(function(){
        if (++n > 60) {
            markExpired();
            return;
        }
        query();
    }, 5000);
    setTimeout(markExpired, 300 * 1000);
    query();
})();
</script>
</body>
</html>
        <?php
    }

    private static function assetUrl($path, $fallback = '') {
        $file = str_replace(SITE_URL, HOPE_ROOT, $path);
        if (is_file($file)) {
            return $path;
        }
        return $fallback;
    }

    private static function qrImageSrc($qr_url) {
        if (strpos($qr_url, 'http') === 0 && (strpos($qr_url, '.png') !== false || strpos($qr_url, '.jpg') !== false)) {
            return $qr_url;
        }
        return 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . urlencode($qr_url);
    }

    public static function renderMessage($msg, $back_url = '') {
        Pay_Official::renderMessage($msg, $back_url);
    }
}
