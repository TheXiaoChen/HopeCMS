<?php
/**
 * Hope CMS(希望CMS) pay
 */
defined('HOPE_ROOT') || exit('access denied!');

require_once HOPE_ROOT . 'system/lib/payment/pay_sdk.php';
$Pay_Model = new Pay_Model();

$payment = Pay_Sdk::getConfig();
if (empty($payment)) {
    $raw = $CACHE->readCache('options')['payment'] ?? '';
    $payment = $raw ? @unserialize($raw) : [];
}
if (!is_array($payment)) {
    $payment = [];
}

$wx0 = $wx1 = $wx2 = $wx3 = '';
$t = 'wx' . (int)($payment['weixinapi'] ?? 0);
$$t = 'selected="selected"';

$zfb0 = $zfb1 = $zfb2 = $zfb3 = '';
$t = 'zfb' . (int)($payment['alipayapi'] ?? 0);
$$t = 'selected="selected"';

if (isset($_GET['save']) && $_POST) {
    LoginAuth::checkToken();
    $getData = [
        'weixinapi'              => Input::postStrVar('weixinapi', 0),
        'alipayapi'              => Input::postStrVar('alipayapi', 0),
        'wx_appid'               => Input::postStrVar('wx_appid'),
        'wx_mchid'               => Input::postStrVar('wx_mchid'),
        'wx_key'                 => Input::postStrVar('wx_key'),
        'alipay_public_key'      => Input::postStrVar('alipay_public_key'),
        'alipay_f2f_appid'       => Input::postStrVar('alipay_f2f_appid'),
        'alipay_f2f_private_key' => Input::postStrVar('alipay_f2f_private_key'),
        'alipay_web_appid'       => Input::postStrVar('alipay_web_appid'),
        'alipay_web_private_key' => Input::postStrVar('alipay_web_private_key'),
        'hpj_wx_api'             => Input::postStrVar('hpj_wx_api'),
        'hpj_alipay_api'         => Input::postStrVar('hpj_alipay_api'),
        'hpj_wx_id'              => Input::postStrVar('hpj_wx_id'),
        'hpj_wx_key'             => Input::postStrVar('hpj_wx_key'),
        'hpj_alipay_id'          => Input::postStrVar('hpj_alipay_id'),
        'hpj_alipay_key'         => Input::postStrVar('hpj_alipay_key'),
        'epay_api'               => Input::postStrVar('epay_api'),
        'epay_id'                => Input::postStrVar('epay_id'),
        'epay_key'               => Input::postStrVar('epay_key'),
        'codepay_api'            => Input::postStrVar('codepay_api'),
        'codepay_id'             => Input::postStrVar('codepay_id'),
        'codepay_key'            => Input::postStrVar('codepay_key'),
        'payment_enabled'        => Input::postIntVar('payment_enabled', 0),
        'pay_direct_redirect'    => Input::postIntVar('pay_direct_redirect', 0),
    ];
    Option::updateOption('payment', serialize($getData));
    $CACHE->updateCache('options');
    Output::ok();
}

if (isset($_GET['del'])) {
    LoginAuth::checkToken();
    $order_no = Input::getStrVar('order_no');
    if ($order_no === '') {
        Output::error('缺少订单号');
    }
    if (!$Pay_Model->deleteOrder($order_no)) {
        Output::error('删除失败，已支付订单请先退款');
    }
    Output::ok(['msg' => '删除成功']);
}

if (isset($_GET['refund'])) {
    LoginAuth::checkToken();
    require_once HOPE_ROOT . 'system/lib/payment/pay_sdk.php';
    $order_no = Input::getStrVar('order_no');
    if ($order_no === '') {
        Output::error('缺少订单号');
    }
    $order = $Pay_Model->getByOrderNo($order_no);
    if (!$order) {
        Output::error('订单不存在');
    }
    $result = Pay_Sdk::refundOrder($order);
    if (empty($result['ok'])) {
        Output::error($result['msg'] ?? '退款失败');
    }
    Output::ok(['msg' => $result['msg'] ?? '退款成功']);
}

if (isset($_GET['sync'])) {
    LoginAuth::checkToken();
    require_once HOPE_ROOT . 'system/lib/payment/pay_sdk.php';
    $order_no = Input::getStrVar('order_no');
    if ($order_no === '') {
        Output::error('缺少订单号');
    }
    $order = $Pay_Model->getByOrderNo($order_no);
    if (!$order) {
        Output::error('订单不存在');
    }
    $result = Pay_Sdk::queryAndSync($order);
    if (!empty($result['paid']) && !empty($result['synced'])) {
        Output::ok(['msg' => $result['msg'], 'status' => 1]);
    }
    if (!empty($result['paid'])) {
        Output::ok(['msg' => $result['msg'], 'status' => 1]);
    }
    Output::ok(['msg' => $result['msg'] ?? '未支付', 'status' => (int)$order['status'], 'synced' => false]);
}

if (isset($_GET['orders'])) {
    $page = Input::getIntVar('page', 1);
    $status = Input::getStrVar('status', 'all');
    $biz_type = Input::getStrVar('biz_type', 'all');
    Output::ok($Pay_Model->getOrders($page, 20, $status, $biz_type));
}

if (isset($_POST['order_operate']) && isset($_POST['order_no'])) {
    LoginAuth::checkToken();
    require_once HOPE_ROOT . 'system/lib/payment/pay_sdk.php';
    $operate = Input::postStrVar('order_operate');
    $order_nos = Input::postStrArray('order_no');
    $order_nos = array_values(array_filter(array_map('trim', $order_nos)));
    if (!$order_nos) {
        Output::error('请选择订单');
    }
    switch ($operate) {
        case 'delete':
            $n = $Pay_Model->batchDeleteOrders($order_nos);
            Output::ok(['msg' => "已删除 {$n} 条订单", 'count' => $n]);
            break;
        case 'close':
            $n = $Pay_Model->batchCloseOrders($order_nos);
            Output::ok(['msg' => "已关闭 {$n} 条订单", 'count' => $n]);
            break;
        case 'sync':
            $synced = 0;
            $paid = 0;
            foreach ($order_nos as $order_no) {
                $order = $Pay_Model->getByOrderNo($order_no);
                if (!$order || (int)$order['status'] !== 0) {
                    continue;
                }
                $result = Pay_Sdk::queryAndSync($order);
                $synced++;
                if (!empty($result['paid'])) {
                    $paid++;
                }
            }
            Output::ok(['msg' => "已查单 {$synced} 条，同步成功 {$paid} 条", 'synced' => $synced, 'paid' => $paid]);
            break;
        default:
            Output::error('未知操作');
    }
}

$order_page = max(1, Input::getIntVar('order_page', 1));
$order_status = Input::getStrVar('order_status', 'all');
$order_biz = Input::getStrVar('order_biz', 'all');
$order_per_page = 20;
$pay_orders = $Pay_Model->getOrders($order_page, $order_per_page, $order_status, $order_biz);
$pay_order_total = $pay_orders['total'];
$pay_order_pages = (int)ceil(max(1, $pay_order_total) / $order_per_page);
$pay_order_page_url = SELF . '?act=pay&order_status=' . urlencode($order_status) . '&order_biz=' . urlencode($order_biz) . '&order_page=';
$pay_stats = $Pay_Model->getStats();
$pay_status = Pay_Sdk::getConfigStatus();
$pay_urls = Pay_Sdk::getAdminUrls();
