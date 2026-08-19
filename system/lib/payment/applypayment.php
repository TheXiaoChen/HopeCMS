<?php
defined('HOPE_ROOT') || exit('access denied!');

/**
 * 应用中心购买 / 订单
 */
class ApplyPayment {

    public static function initTable() {
        $name = DB_PREFIX . 'apply_purchase_record';
        if (Database::fetchOne("SHOW TABLES LIKE '{$name}'")) {
            return;
        }
        SqlRunner::executeLines([
            "CREATE TABLE IF NOT EXISTS `{$name}` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '购买记录ID',
            `user_id` int(11) NOT NULL COMMENT '用户ID',
            `apply_id` int(11) NOT NULL COMMENT '应用ID',
            `order_no` varchar(50) NOT NULL COMMENT '订单号',
            `amount` decimal(10,2) NOT NULL COMMENT '购买金额',
            `payment_method` varchar(20) NULL COMMENT '支付方式',
            `purchase_time` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '购买时间',
            `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态: 1已支付 0未支付',
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_order_no` (`order_no`),
            KEY `idx_user_id` (`user_id`),
            KEY `idx_apply_id` (`apply_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='应用购买记录表';",
        ], ['ignore_error' => true]);
    }

    public static function getApp($apply_id) {
        return Database::table('apply_store')->where(['id' => (int)$apply_id, 'status' => 1])->find();
    }

    public static function resolvePrice(array $app) {
        if (function_exists('apply_sale_price')) {
            return apply_sale_price($app);
        }
        $price = (float)($app['price'] ?? 0);
        $promo = (float)($app['promo_price'] ?? 0);
        if ($promo > 0 && $promo < $price) {
            return $promo;
        }
        return $price;
    }

    public static function createOrder($apply_id, $user_id, $amount = null, $pay_type = 'alipay') {
        self::initTable();
        Pay_Model::initTable();

        $apply_id = (int)$apply_id;
        $user_id = (int)$user_id;
        if ($user_id <= 0) {
            return ['code' => 0, 'msg' => '请先登录'];
        }

        $app = self::getApp($apply_id);
        if (!$app) {
            return ['code' => 0, 'msg' => '应用不存在或已下架'];
        }

        $price = $amount !== null ? (float)$amount : self::resolvePrice($app);
        if ($price <= 0) {
            return ['code' => 0, 'msg' => '免费应用无需购买'];
        }
        if (self::hasPurchased($apply_id, $user_id)) {
            return ['code' => 0, 'msg' => '您已购买该应用'];
        }

        if (!in_array($pay_type, ['alipay', 'wechat', 'balance'], true)) {
            $pay_type = 'alipay';
        }

        if ($pay_type === 'balance') {
            return self::createBalanceOrder($app, $apply_id, $user_id, $price);
        }

        $Pay_Model = new Pay_Model();
        $pay_order = $Pay_Model->createOrder([
            'user_id'  => $user_id,
            'title'    => '购买应用：' . ($app['name'] ?? ''),
            'amount'   => $price,
            'pay_type' => $pay_type,
            'biz_type' => 'apply',
            'biz_id'   => $apply_id,
        ]);

        $order_no = $pay_order['order_no'];
        $ok = Database::insert('apply_purchase_record', [
            'user_id'         => $user_id,
            'apply_id'        => $apply_id,
            'order_no'        => $order_no,
            'amount'          => $price,
            'payment_method'  => $pay_type,
            'status'          => 0,
        ]);
        if (!$ok) {
            $Pay_Model->closeOrder($order_no);
            return ['code' => 0, 'msg' => '创建订单失败'];
        }

        return [
            'code'     => 1,
            'order_no' => $order_no,
            'amount'   => $price,
            'pay_type' => $pay_type,
        ];
    }

    /** 余额支付：当场扣款并完成购买 */
    private static function createBalanceOrder(array $app, $apply_id, $user_id, $price) {
        $bal = self::userBalance($user_id);
        if ($bal < $price) {
            return ['code' => 0, 'msg' => '余额不足，当前 ¥' . number_format($bal, 2)];
        }
        if (!self::deductBalance($user_id, $price)) {
            return ['code' => 0, 'msg' => '余额扣除失败'];
        }

        $Pay_Model = new Pay_Model();
        $pay_order = $Pay_Model->createOrder([
            'user_id'  => $user_id,
            'title'    => '购买应用：' . ($app['name'] ?? ''),
            'amount'   => $price,
            'pay_type' => 'balance',
            'biz_type' => 'apply',
            'biz_id'   => $apply_id,
        ]);
        $order_no = $pay_order['order_no'];
        $ok = Database::insert('apply_purchase_record', [
            'user_id'         => $user_id,
            'apply_id'        => $apply_id,
            'order_no'        => $order_no,
            'amount'          => $price,
            'payment_method'  => 'balance',
            'status'          => 0,
        ]);
        if (!$ok) {
            self::refundBalance($user_id, $price);
            $Pay_Model->closeOrder($order_no);
            return ['code' => 0, 'msg' => '创建订单失败'];
        }

        $Pay_Model->markPaid($order_no, 'BALANCE', ['method' => 'balance']);
        $order = $Pay_Model->getByOrderNo($order_no);
        self::completePurchase($order);
        doAction('pay_order_paid', $order);

        return [
            'code'     => 1,
            'paid'     => true,
            'order_no' => $order_no,
            'amount'   => $price,
            'pay_type' => 'balance',
        ];
    }

    public static function userBalance($uid) {
        $uid = (int)$uid;
        if ($uid <= 0) {
            return 0.0;
        }
        if (function_exists('shop_user_balance')) {
            return (float)shop_user_balance($uid);
        }
        $row = Database::table('user')->where(['uid' => $uid])->find();
        return (float)($row['balance'] ?? 0);
    }

    private static function deductBalance($uid, $amount) {
        if (function_exists('shop_deduct_balance')) {
            return (bool)shop_deduct_balance($uid, $amount);
        }
        $uid = (int)$uid;
        $amount = round((float)$amount, 2);
        if ($uid <= 0 || $amount <= 0) {
            return false;
        }
        $before = Database::table('user')->where(['uid' => $uid])->find();
        if (!$before || (float)($before['balance'] ?? 0) < $amount) {
            return false;
        }
        $sqlAmt = number_format($amount, 2, '.', '');
        Database::execute(
            'UPDATE `' . DB_PREFIX . 'user` SET balance=balance-' . $sqlAmt
            . ' WHERE uid=' . $uid . ' AND balance>=' . $sqlAmt
        );
        $after = Database::table('user')->where(['uid' => $uid])->find();
        $expected = round((float)$before['balance'] - $amount, 2);
        return $after && abs((float)$after['balance'] - $expected) < 0.001;
    }

    private static function refundBalance($uid, $amount) {
        if (function_exists('shop_refund_balance')) {
            return (bool)shop_refund_balance($uid, $amount);
        }
        $uid = (int)$uid;
        $amount = round((float)$amount, 2);
        if ($uid <= 0 || $amount <= 0) {
            return false;
        }
        Database::execute(
            'UPDATE `' . DB_PREFIX . 'user` SET balance=balance+' . number_format($amount, 2, '.', '')
            . ' WHERE uid=' . $uid
        );
        return true;
    }

    public static function createPayment($order_no, $pay_type = null) {
        require_once HOPE_ROOT . 'system/lib/payment/pay_sdk.php';
        $Pay_Model = new Pay_Model();
        $order = $Pay_Model->getByOrderNo($order_no);
        if (!$order) {
            return ['code' => 0, 'msg' => '订单不存在'];
        }
        if ($pay_type && in_array($pay_type, ['alipay', 'wechat'], true) && $pay_type !== $order['pay_type']) {
            Database::table('pay_order')->where(['order_no' => $order_no])->update(['pay_type' => $pay_type]);
            $order['pay_type'] = $pay_type;
            Database::table('apply_purchase_record')->where(['order_no' => $order_no])->update(['payment_method' => $pay_type]);
        }
        $pay = Pay_Sdk::createPayment($order);
        if (empty($pay['code'])) {
            return ['code' => 0, 'msg' => $pay['msg'] ?? '创建支付失败'];
        }
        return array_merge(['code' => 1, 'order_no' => $order_no], $pay);
    }

    public static function getOrder($order_no) {
        self::initTable();
        return Database::table('apply_purchase_record')
            ->where(['order_no' => (string)$order_no])
            ->find();
    }

    public static function updateOrderStatus($order_no, $status) {
        self::initTable();
        return Database::update('apply_purchase_record', ['order_no' => $order_no], [
            'status' => (int)$status,
        ]);
    }

    public static function completePurchase(array $pay_order) {
        if (($pay_order['biz_type'] ?? '') !== 'apply') {
            return false;
        }
        $order_no = $pay_order['order_no'] ?? '';
        if ($order_no === '') {
            return false;
        }
        $record = self::getOrder($order_no);
        if (!$record) {
            return false;
        }
        if ((int)($record['status'] ?? 0) === 1) {
            return true;
        }
        return (bool)self::updateOrderStatus($order_no, 1);
    }

    public static function hasPurchased($apply_id, $user_id) {
        self::initTable();
        if ((int)$user_id <= 0) {
            return false;
        }
        return Database::table('apply_purchase_record')
            ->where([
                'apply_id' => (int)$apply_id,
                'user_id'  => (int)$user_id,
                'status'   => 1,
            ])
            ->find() !== null;
    }

    public static function getUserPurchases($user_id, $page = 1, $per_page = 10) {
        self::initTable();
        $start = max(0, ((int)$page - 1) * (int)$per_page);
        return Database::table('apply_purchase_record', 'pr')
            ->select('pr.*, a.name, a.type, a.version')
            ->leftJoin('apply_store', 'a', 'a.id = pr.apply_id')
            ->where(['pr.user_id' => (int)$user_id, 'pr.status' => 1])
            ->order('pr.purchase_time DESC')
            ->limit((int)$per_page, $start)
            ->findAll();
    }

    public static function countUserPurchases($user_id) {
        self::initTable();
        return (int)Database::table('apply_purchase_record')
            ->where(['user_id' => (int)$user_id, 'status' => 1])
            ->count();
    }

    public static function getAuthorSales($author_id, $page = 1, $per_page = 10) {
        self::initTable();
        $start = max(0, ((int)$page - 1) * (int)$per_page);
        return Database::table('apply_purchase_record', 'pr')
            ->select('pr.*, a.name, a.type, u.nickname, u.username, u.email')
            ->leftJoin('apply_store', 'a', 'a.id = pr.apply_id')
            ->leftJoin('user', 'u', 'u.uid = pr.user_id')
            ->where(['a.author_id' => (int)$author_id, 'pr.status' => 1])
            ->order('pr.purchase_time DESC')
            ->limit((int)$per_page, $start)
            ->findAll();
    }

    public static function countAuthorSales($author_id) {
        self::initTable();
        return (int)Database::table('apply_purchase_record', 'pr')
            ->leftJoin('apply_store', 'a', 'a.id = pr.apply_id')
            ->where(['a.author_id' => (int)$author_id, 'pr.status' => 1])
            ->count();
    }

    public static function getAuthorIncome($author_id) {
        self::initTable();
        $row = Database::fetchOne(
            'SELECT COALESCE(SUM(pr.amount), 0) AS total, COUNT(*) AS cnt FROM `' . DB_PREFIX . 'apply_purchase_record` pr '
            . 'INNER JOIN `' . DB_PREFIX . 'apply_store` a ON a.id = pr.apply_id '
            . 'WHERE a.author_id = ' . (int)$author_id . ' AND pr.status = 1'
        );
        return [
            'total_income' => (float)($row['total'] ?? 0),
            'order_count'  => (int)($row['cnt'] ?? 0),
        ];
    }
}
