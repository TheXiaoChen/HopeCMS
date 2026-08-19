<?php
/**
 * Hope CMS 支付回调 Controller（rest-api=pay_notify）
 */
class Pay_Controller {

    public function starter($params) {
        $_func = addslashes($_GET['rest-api'] ?? '');
        if ($_func === 'pay_notify') {
            $this->pay_notify();
            return;
        }
        if ($_func === 'pay_create') {
            $this->pay_create();
            return;
        }
        if ($_func === 'pay_cashier') {
            $this->pay_cashier();
            return;
        }
        if ($_func === 'pay_status') {
            $this->pay_status();
            return;
        }
        if ($_func === 'apply_purchase') {
            $this->apply_purchase();
            return;
        }
        Output::error('pay api not found');
    }

    public function pay_notify() {
        require_once HOPE_ROOT . 'system/lib/payment/pay_sdk.php';
        require_once HOPE_ROOT . 'system/lib/payment/official_pay.php';
        $Pay_Model = new Pay_Model();
        $channel = Input::getStrVar('channel', 'epay');
        $raw = file_get_contents('php://input');
        $data = array_merge($_GET, $_POST);
        if (empty($data) && $raw !== '') {
            $json = json_decode($raw, true);
            if (is_array($json)) {
                $data = $json;
            }
        }
        $is_wechat_xml = ($channel === 'official' && $raw !== '' && strpos(trim($raw), '<') === 0);
        if (!Pay_Sdk::verifyNotify($channel, $data, $raw)) {
            die($is_wechat_xml ? Pay_Official::wechatNotifyResponse(false) : 'fail');
        }
        if ($is_wechat_xml) {
            $data = Pay_Official::parseWechatNotify($raw);
        }
        $order_no = Pay_Sdk::extractOrderNo($channel, $data);
        if ($order_no === '') {
            die($is_wechat_xml ? Pay_Official::wechatNotifyResponse(false) : 'fail');
        }
        $order = $Pay_Model->getByOrderNo($order_no);
        if (!$order || (int)$order['status'] !== 0) {
            die($is_wechat_xml ? Pay_Official::wechatNotifyResponse(true) : 'success');
        }
        $trade_no = Pay_Sdk::extractTradeNo($channel, $data);
        $affected = $Pay_Model->markPaid($order_no, $trade_no, $data);
        if ($affected) {
            if (($order['biz_type'] ?? '') === 'recharge') {
                hope_load_user_center();
                hope_user_center_complete_recharge($order);
            } elseif (($order['biz_type'] ?? '') === 'apply') {
                ApplyPayment::completePurchase($order);
            }
            doAction('pay_order_paid', $order);
        }
        die($is_wechat_xml ? Pay_Official::wechatNotifyResponse(true) : 'success');
    }

    public function pay_create() {
        if (!ISLOGIN) {
            Output::authError('请先登录');
        }
        LoginAuth::checkToken();
        require_once HOPE_ROOT . 'system/lib/payment/pay_sdk.php';
        if (!Pay_Sdk::isEnabled()) {
            Output::error('支付功能已关闭');
        }
        $Pay_Model = new Pay_Model();
        $title = Input::postStrVar('title', '账户充值');
        $amount = (float)Input::postStrVar('amount');
        $pay_type = Input::postStrVar('pay_type', 'alipay');
        if ($amount <= 0) {
            Output::error('金额无效');
        }
        if (!in_array($pay_type, ['alipay', 'wechat'], true)) {
            $pay_type = 'alipay';
        }
        $order = $Pay_Model->createOrder([
            'user_id'  => UID,
            'title'    => $title,
            'amount'   => $amount,
            'pay_type' => $pay_type,
            'biz_type' => Input::postStrVar('biz_type'),
            'biz_id'   => Input::postIntVar('biz_id'),
        ]);
        $pay = Pay_Sdk::createPayment($order);
        if (empty($pay['code'])) {
            Output::error($pay['msg'] ?? '创建支付失败');
        }
        Output::ok(['order' => $order, 'payment' => $pay]);
    }

    public function pay_cashier() {
        require_once HOPE_ROOT . 'system/lib/payment/pay_cashier_page.php';
        $order_no = Input::getStrVar('order_no');
        if ($order_no === '') {
            if (Pay_Cashier::wantsJson()) {
                Output::error('缺少订单号');
            }
            Pay_Cashier::renderMessage('缺少订单号');
            exit;
        }
        $Pay_Model = new Pay_Model();
        $order = $Pay_Model->getByOrderNo($order_no);
        if (!$order) {
            if (Pay_Cashier::wantsJson()) {
                Output::error('订单不存在');
            }
            Pay_Cashier::renderMessage('订单不存在');
            exit;
        }

        // JSON 收银台（标准 Output::ok / Output::error）
        if (Pay_Cashier::wantsJson()) {
            $resolved = Pay_Cashier::resolve($order);
            if (empty($resolved['ok'])) {
                Output::error($resolved['msg'] ?? '支付失败');
            }
            $shopData = [
                'type'  => (int)($resolved['type'] ?? 2),
                'trade' => $resolved['trade'] ?? $order_no,
                'guest' => 0,
            ];
            if ((int)($resolved['type'] ?? 0) === 1) {
                $shopData['html'] = $resolved['html'] ?? '';
            } else {
                $shopData['payurl'] = $resolved['payurl'] ?? ($resolved['payment_url'] ?? '');
            }
            unset($resolved['ok'], $resolved['form_html']);
            Output::ok(array_merge($resolved, ['shop' => $shopData]));
        }

        Pay_Cashier::render($order);
        // render 内 redirect 会 exit；HTML 收银台在此结束，避免继续走主题输出
        exit;
    }

    public function pay_status() {
        $Pay_Model = new Pay_Model();
        $order_no = Input::getStrVar('order_no');
        if ($order_no === '') {
            Output::error('缺少订单号');
        }
        $order = $Pay_Model->getByOrderNo($order_no);
        if (!$order) {
            Output::error('订单不存在');
        }
        Output::ok([
            'order_no' => $order_no,
            'status'   => (int)$order['status'],
            'paid'     => (int)$order['status'] === 1,
        ]);
    }

    public function apply_purchase() {
        if (!ISLOGIN) {
            $back = urlencode($_SERVER['REQUEST_URI'] ?? '');
            $loginUrl = Url::login($back ? ['redirect' => $back] : []);
            header('Location: ' . $loginUrl);
            exit;
        }
        require_once HOPE_ROOT . 'system/lib/payment/pay_sdk.php';
        $apply_id = Input::getIntVar('apply_id');
        $pay_type = Input::getStrVar('pay_type', 'alipay');
        if (!Pay_Sdk::isEnabled() && $pay_type !== 'balance') {
            $this->applyPurchaseRedirect($apply_id, '支付功能已关闭');
        }
        $result = ApplyPayment::createOrder($apply_id, UID, null, $pay_type);
        if (empty($result['code'])) {
            $this->applyPurchaseRedirect($apply_id, $result['msg'] ?? '创建订单失败');
        }
        if (!empty($result['paid'])) {
            $this->applyPurchaseRedirect($apply_id);
        }
        header('Location: ' . Pay_Sdk::officialCashierUrl($result['order_no']));
        exit;
    }

    /** 购买结果回到应用详情：成功显示开通，失败显示错误（如余额不足） */
    private function applyPurchaseRedirect($apply_id, $error = '') {
        $apply_lib = HOPE_ROOT . 'content/plugin/apply/apply_lib.php';
        if (is_file($apply_lib)) {
            require_once $apply_lib;
        }
        $back = function_exists('apply_get_detail_url')
            ? apply_get_detail_url($apply_id)
            : (SITE_URL . '?plugin=apply&id=' . (int)$apply_id);
        $sep = (strpos($back, '?') !== false) ? '&' : '?';
        if ($error !== '') {
            $back .= $sep . 'msg=pay_error&pay_err=' . rawurlencode($error);
        } else {
            $back .= $sep . 'msg=purchase_ok';
        }
        header('Location: ' . $back);
        exit;
    }
}
