<?php
/**
 * Hope CMS 支付订单 Model（system/app/model 试点迁移）
 */
class Pay_Model {

    private $table;

    public function __construct() {
        $this->table = 'pay_order';
        self::initTable();
    }

    public static function initTable() {
        $name = DB_PREFIX . 'pay_order';
        if (Database::fetchOne("SHOW TABLES LIKE '{$name}'")) {
            return;
        }
        SqlRunner::executeLines([
            "CREATE TABLE IF NOT EXISTS `{$name}` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `order_no` varchar(32) NOT NULL DEFAULT '' COMMENT '订单号',
            `user_id` int(11) unsigned NOT NULL DEFAULT '0',
            `title` varchar(255) NOT NULL DEFAULT '' COMMENT '商品标题',
            `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
            `pay_type` varchar(20) NOT NULL DEFAULT 'alipay' COMMENT 'alipay/wechat',
            `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0待付1已付2关闭',
            `trade_no` varchar(64) NOT NULL DEFAULT '' COMMENT '第三方流水号',
            `biz_type` varchar(32) NOT NULL DEFAULT '' COMMENT '业务类型',
            `biz_id` int(11) unsigned NOT NULL DEFAULT '0',
            `notify_data` text NULL,
            `create_time` int(11) NOT NULL DEFAULT '0',
            `pay_time` int(11) NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_order_no` (`order_no`),
            KEY `idx_user_id` (`user_id`),
            KEY `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='支付订单';",
        ], ['ignore_error' => true]);
    }

    public function generateOrderNo() {
        do {
            $no = date('ymd') . sprintf('%04d', mt_rand(0, 9999));
        } while ($this->getByOrderNo($no));
        return $no;
    }

    public function createOrder($data) {
        $order_no = $this->generateOrderNo();
        $now = time();
        $row = [
            'order_no'    => $order_no,
            'user_id'     => (int)($data['user_id'] ?? 0),
            'title'       => $data['title'] ?? '在线支付',
            'amount'      => (float)($data['amount'] ?? 0),
            'pay_type'    => $data['pay_type'] ?? 'alipay',
            'status'      => 0,
            'biz_type'    => $data['biz_type'] ?? '',
            'biz_id'      => (int)($data['biz_id'] ?? 0),
            'create_time' => $now,
            'pay_time'    => 0,
        ];
        Database::table($this->table)->insert($row);
        return $this->getByOrderNo($order_no);
    }

    public function getByOrderNo($order_no) {
        return Database::table($this->table)->where(['order_no' => $order_no])->find();
    }

    public function markPaid($order_no, $trade_no = '', $notify_data = '') {
        $notify = is_array($notify_data) ? json_encode($notify_data, JSON_UNESCAPED_UNICODE) : $notify_data;
        $now = time();
        return Database::table($this->table)
            ->where(['order_no' => $order_no, 'status' => 0])
            ->update([
                'status'      => 1,
                'trade_no'    => $trade_no,
                'notify_data' => $notify,
                'pay_time'    => $now,
            ]);
    }

    public function markRefunded($order_no, $refund_data = '') {
        $notify = is_array($refund_data) ? json_encode($refund_data, JSON_UNESCAPED_UNICODE) : $refund_data;
        return Database::table($this->table)
            ->where(['order_no' => $order_no, 'status' => 1])
            ->update([
                'status'      => 3,
                'notify_data' => $notify,
            ]);
    }

    public function deleteOrder($order_no) {
        $order = $this->getByOrderNo($order_no);
        if (!$order) {
            return false;
        }
        if ((int)$order['status'] === 1) {
            return false;
        }
        return Database::table($this->table)->where(['order_no' => $order_no])->delete();
    }

    public function batchDeleteOrders(array $order_nos) {
        $deleted = 0;
        foreach ($order_nos as $order_no) {
            if ($this->deleteOrder($order_no)) {
                $deleted++;
            }
        }
        return $deleted;
    }

    public function batchCloseOrders(array $order_nos) {
        $closed = 0;
        foreach ($order_nos as $order_no) {
            if ($this->closeOrder($order_no)) {
                $closed++;
            }
        }
        return $closed;
    }

    public function closeOrder($order_no) {
        return Database::table($this->table)
            ->where(['order_no' => $order_no, 'status' => 0])
            ->update(['status' => 2]);
    }

    public function getOrders($page = 1, $per_page = 20, $status = '', $biz_type = '') {
        $page = max(1, (int)$page);
        $per_page = max(1, min(100, (int)$per_page));
        $offset = ($page - 1) * $per_page;

        $countQuery = Database::table($this->table, 'o');
        if ($status !== '' && $status !== 'all') {
            $countQuery->where(['o.status' => (int)$status]);
        }
        if ($biz_type !== '' && $biz_type !== 'all') {
            $countQuery->where(['o.biz_type' => $biz_type]);
        }
        $total = $countQuery->count();

        $listQuery = Database::table($this->table, 'o')
            ->select('o.*, u.nickname, u.email')
            ->leftJoin('user', 'u', 'u.uid=o.user_id');
        if ($status !== '' && $status !== 'all') {
            $listQuery->where(['o.status' => (int)$status]);
        }
        if ($biz_type !== '' && $biz_type !== 'all') {
            $listQuery->where(['o.biz_type' => $biz_type]);
        }
        $rows = $listQuery->order('o.id DESC')->limit($per_page, $offset)->findAll();

        $formatted = [];
        foreach ($rows as $row) {
            $formatted[] = $this->formatOrder($row);
        }
        return ['list' => $formatted, 'total' => $total, 'page' => $page, 'per_page' => $per_page];
    }

    public function getStats() {
        $pending = (int)Database::table($this->table)->where(['status' => 0])->count();
        $paid = (int)Database::table($this->table)->where(['status' => 1])->count();
        $paid_sum = Database::fetchOne("SELECT COALESCE(SUM(amount),0) AS s FROM `" . DB_PREFIX . "pay_order` WHERE status=1");
        return [
            'pending'    => $pending,
            'paid'       => $paid,
            'paid_amount'=> (float)($paid_sum['s'] ?? 0),
        ];
    }

    public function formatOrder($row) {
        $status_map = [0 => '待支付', 1 => '已支付', 2 => '已关闭', 3 => '已退款'];
        $row['status_label'] = $status_map[(int)$row['status']] ?? '未知';
        $row['create_time_fmt'] = !empty($row['create_time']) ? date('Y-m-d H:i:s', (int)$row['create_time']) : '-';
        $row['pay_time_fmt'] = !empty($row['pay_time']) ? date('Y-m-d H:i:s', (int)$row['pay_time']) : '-';
        $row['user_label'] = $row['nickname'] ?: ($row['email'] ?: ('UID:' . $row['user_id']));
        $biz_map = [
            'recharge' => '账户充值',
            'apply'    => '应用购买',
            'shop_log' => '资源购买',
            'shop_vip' => '会员开通',
            ''         => '-',
        ];
        $row['biz_label'] = $biz_map[$row['biz_type'] ?? ''] ?? ($row['biz_type'] ?: '-');
        $pay_map = ['alipay' => '支付宝', 'wechat' => '微信', 'balance' => '余额', 'qq' => 'QQ', 'coupon' => '优惠券', 'bundle' => '合集赠送'];
        $row['pay_type_label'] = $pay_map[$row['pay_type'] ?? ''] ?? ($row['pay_type'] ?: '-');
        return $row;
    }
}
