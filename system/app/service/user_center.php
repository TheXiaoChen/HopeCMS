<?php
/**
 * Hope CMS 个人中心业务逻辑（system/service）
 */
defined('HOPE_ROOT') || exit('access denied!');

/** 个人中心 DB 辅助 */
function hope_uc_uid() {
    return (int)UID;
}

function hope_uc_user_find($uid) {
    return Database::table('user')->where(['uid' => (int)$uid])->find();
}

function hope_uc_blog_owned($gid, $uid) {
    return Database::table('article')->where([
        'gid'    => (int)$gid,
        'author' => (int)$uid,
    ])->find();
}

function hope_uc_escape_like($keyword) {
    $db = Database::getInstance();
    $kw = $db->escape_string($keyword);
    return str_replace(['%', '_'], ['\%', '\_'], $kw);
}

function hope_user_center_gen_password($len = 16) {
    $chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $s = '';
    for ($i = 0; $i < $len; $i++) {
        $s .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $s;
}

function hope_user_center_gen_invite_code() {
    return strtoupper(substr(md5(uniqid('', true)), 0, 8));
}

function hope_user_center_get_invite_reward_amount() {
    $amount = Option::get('invite_reward_amount');
    if ($amount === '' || $amount === null) {
        return 0;
    }
    return max(0, (float)$amount);
}

function hope_user_center_invite_enabled() {
    if (hope_user_center_get_invite_reward_amount() > 0) {
        return true;
    }
    // Shop 主题：仅赠送 VIP 天数时也启用邀请
    if (function_exists('_hope') && (int)_hope('invite_vip_days', 0) > 0) {
        return true;
    }
    return false;
}

/** 支付/充值功能是否开启 */
function hope_user_center_payment_enabled() {
    require_once HOPE_ROOT . 'system/lib/payment/pay_sdk.php';
    return Pay_Sdk::isEnabled();
}

function hope_user_center_init_recharge_table() {
    $name = DB_PREFIX . 'user_recharge';
    if (!Database::fetchOne("SHOW TABLES LIKE '{$name}'")) {
        return;
    }
    $cols = Database::fetchAll("SHOW COLUMNS FROM `{$name}` LIKE 'order_no'");
    if (empty($cols)) {
        Database::execute("ALTER TABLE `{$name}` ADD COLUMN `order_no` varchar(32) NOT NULL DEFAULT '' COMMENT '支付订单号' AFTER `amount`");
        Database::execute("ALTER TABLE `{$name}` ADD KEY `idx_order_no` (`order_no`)");
    }
}

/** 按需补齐 user.api_password（核心安装 SQL 不含此字段） */
function hope_user_center_ensure_api_password_column() {
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    $table = DB_PREFIX . 'user';
    if (!Database::fetchOne("SHOW TABLES LIKE '{$table}'")) {
        return $ready = false;
    }
    foreach (Database::fetchAll("SHOW COLUMNS FROM `{$table}`") as $col) {
        if (($col['Field'] ?? '') === 'api_password') {
            return $ready = true;
        }
    }
    $after = '';
    foreach (Database::fetchAll("SHOW COLUMNS FROM `{$table}`") as $col) {
        if (($col['Field'] ?? '') === 'balance') {
            $after = ' AFTER `balance`';
            break;
        }
    }
    Database::execute(
        "ALTER TABLE `{$table}` ADD COLUMN `api_password` varchar(32) NOT NULL DEFAULT '' COMMENT 'API密钥'{$after}",
        true
    );
    return $ready = true;
}

function hope_user_center_ensure_meta($uid) {
    $uid = (int)$uid;
    $row = hope_uc_user_find($uid);
    if (!$row) {
        return;
    }
    $updates = [];
    if (hope_user_center_ensure_api_password_column() && ($row['api_password'] ?? '') === '') {
        $updates['api_password'] = hope_user_center_gen_password();
    }
    if (($row['invite_code'] ?? '') === '') {
        $code = hope_user_center_gen_invite_code();
        while (Database::table('user')->where(['invite_code' => $code])->find()) {
            $code = hope_user_center_gen_invite_code();
        }
        $updates['invite_code'] = $code;
    }
    if ($updates) {
        Database::table('user')->where(['uid' => $uid])->update($updates);
    }
}

function hope_user_center_photo_url($photo) {
    return hope_avatar_url($photo, '');
}

/** 格式化个人中心附件条目 */
function hope_user_center_format_media_row($row) {
    if (empty($row) || !is_array($row)) {
        return null;
    }
    $filepath = str_replace('\\', '/', (string)($row['filepath'] ?? ''));
    $filepath = str_replace('thum-', '', $filepath);
    $name = html_entity_decode((string)($row['filename'] ?? ''), ENT_QUOTES, 'UTF-8');
    $mime = (string)($row['mimetype'] ?? '');
    $alias = (string)($row['alias'] ?? '');
    $is_image = ($mime !== '' && strpos($mime, 'image/') === 0)
        || preg_match('/\.(jpe?g|png|gif|webp|bmp|svg)$/i', $name);
    $is_zip = function_exists('isZip') ? isZip($name) : (bool)preg_match('/\.(zip|rar|7z|gz)$/i', $name);

    $source_url = '';
    if ($filepath !== '') {
        $is_external = (stripos($filepath, 'http') === 0);
        if (function_exists('getFileUrl')) {
            $raw = getFileUrl($filepath);
            $source_url = ($is_external || !function_exists('rmUrlParams')) ? $raw : rmUrlParams($raw);
        } else {
            $source_url = hope_user_center_photo_url($filepath);
        }
    }

    // 与后台一致：仅压缩包提供公开/登录下载地址（隐藏真实路径）
    $public_url = '';
    $down_url = '';
    if ($is_zip && $alias !== '' && preg_match('/^\w{16}$/', $alias)) {
        $public_url = SITE_URL . '?resource_alias=' . $alias . '&name=' . rawurlencode($alias);
        $down_url = SITE_URL . '?resource_alias=' . $alias;
    }

    return [
        'id'         => (int)($row['aid'] ?? 0),
        'name'       => $name,
        'alias'      => $alias,
        'url'        => $is_zip && $public_url !== '' ? $public_url : $source_url,
        'source_url' => $source_url,
        'public_url' => $public_url,
        'down_url'   => $down_url,
        'size'       => isset($row['filesize']) ? changeFileSize((int)$row['filesize']) : '',
        'mime'       => $mime,
        'is_image'   => $is_image,
        'is_zip'     => $is_zip,
        'date'       => !empty($row['addtime']) ? date('Y-m-d H:i:s', (int)$row['addtime']) : '',
    ];
}

function hope_user_center_get_profile($uid) {
    hope_user_center_ensure_meta($uid);
    $User_Model = new User_Model();
    $user = $User_Model->getOneUser($uid);
    if (!$user) {
        return null;
    }
    $raw = hope_uc_user_find($uid);
    return [
        'uid'          => (int)$uid,
        'username'     => $user['username'] ?? '',
        'nickname'     => $user['nickname'] ?? '',
        'email'        => $user['email'] ?? '',
        'photo'        => hope_user_center_photo_url($raw['photo'] ?? ''),
        'qq'           => $raw['qq'] ?? '',
        'homepage'     => $raw['homepage'] ?? '',
        'bio'          => $raw['description'] ?? '',
        'balance'      => (float)($raw['balance'] ?? 0),
        'api_password' => $raw['api_password'] ?? '',
        'invite_code'  => $raw['invite_code'] ?? '',
        'role'         => $user['role'] ?? '',
    ];
}

function hope_user_center_forum_sorts() {
    if (function_exists('hope_site_forum_sorts')) {
        return hope_site_forum_sorts();
    }
    global $CACHE;
    $sort_cache = $CACHE->readCache('sort');
    if (!is_array($sort_cache)) {
        return [];
    }
    $list = [];
    foreach ($sort_cache as $sid => $row) {
        if (!is_array($row) || !empty($row['pid'])) {
            continue;
        }
        $list[] = $row;
    }
    return $list;
}

function hope_user_center_forum_sort_ids() {
    $ids = [];
    foreach (hope_user_center_forum_sorts() as $row) {
        if (!empty($row['sid'])) {
            $ids[] = (int)$row['sid'];
        }
    }
    return array_values(array_unique($ids));
}

function hope_user_center_forum_tags_string($blog_id) {
    $Tag_Model = new Tag_Model();
    $tags = $Tag_Model->getTag($blog_id);
    if (!$tags) {
        return '';
    }
    $names = [];
    foreach ($tags as $t) {
        $names[] = $t['tagname'];
    }
    return implode(',', $names);
}

function hope_user_center_forum_status($row) {
    if (($row['hide'] ?? 'n') === 'y') {
        return ['key' => 'draft', 'label' => '草稿'];
    }
    if (($row['checked'] ?? 'n') === 'y') {
        return ['key' => 'published', 'label' => '已发布'];
    }
    return ['key' => 'pending', 'label' => '待审核'];
}

function hope_user_center_upload_file($file, $allowed_ext = []) {
    if (empty($file) || !isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'msg' => '上传失败'];
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($allowed_ext && !in_array($ext, $allowed_ext, true)) {
        return ['ok' => false, 'msg' => '不支持的文件类型'];
    }
    $ret = upload($file['name'], $file['tmp_name'], $file['size']);
    if (!is_array($ret) || empty($ret['file_path'])) {
        return ['ok' => false, 'msg' => '文件保存失败'];
    }
    // 始终写入附件库（后台附件 / 个人媒体库可见）
    $aid = 0;
    $uid = (defined('UID') && UID > 0) ? (int)UID : 0;
    if ($uid > 0) {
        if (Option::get('forbid_user_upload') === 'y' && !User::haveEditPermission()) {
            return ['ok' => false, 'msg' => '系统已关闭用户上传'];
        }
        try {
            $Upload_Model = new Upload_Model();
            $aid = (int)$Upload_Model->addMedia($ret, 0, $uid);
        } catch (Throwable $e) {
            $aid = 0;
        }
        if ($aid <= 0) {
            return ['ok' => false, 'msg' => '附件记录写入失败，请检查数据库 upload 表'];
        }
    }
    return [
        'ok'   => true,
        'path' => $ret['file_path'],
        'url'  => hope_user_center_photo_url($ret['file_path']),
        'size' => (int)$file['size'],
        'aid'  => $aid,
    ];
}

function hope_user_center_process_invite($uid, $invite_code) {
    if (!$invite_code) {
        return;
    }
    $uid = (int)$uid;
    $code = strtoupper(trim($invite_code));
    $inviter = Database::table('user')->where(['invite_code' => $code])->find();
    if (!$inviter || (int)$inviter['uid'] === $uid) {
        return;
    }
    $inviter_id = (int)$inviter['uid'];
    $reward = hope_user_center_invite_enabled() ? hope_user_center_get_invite_reward_amount() : 0;
    if ($reward < 0) {
        $reward = 0;
    }
    $now = time();
    $affected = Database::table('user')
        ->where(['uid' => $uid, 'invited_by' => 0])
        ->update(['invited_by' => $inviter_id]);
    if ($affected) {
        if ($reward > 0) {
            Database::execute("UPDATE `" . DB_PREFIX . "user` SET balance=balance+{$reward} WHERE uid={$uid}");
            Database::execute("UPDATE `" . DB_PREFIX . "user` SET balance=balance+{$reward} WHERE uid={$inviter_id}");
            Database::table('user_invite_reward')->insert([
                'user_id'      => $inviter_id,
                'from_user_id' => $uid,
                'amount'       => $reward,
                'type'         => 'invite',
                'create_time'  => $now,
            ]);
            Database::table('user_invite_reward')->insert([
                'user_id'      => $uid,
                'from_user_id' => $inviter_id,
                'amount'       => $reward,
                'type'         => 'register',
                'create_time'  => $now,
            ]);
        }
        doAction('user_invite_rewarded', $uid, $inviter_id, $reward);
    }
}

function hope_user_center_handle_api() {

    if (!defined('UID') || UID <= 0) {
        Output::authError('请先登录');
    }

    $action = trim($_REQUEST['action'] ?? '');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = isset($_REQUEST['token']) ? trim((string)$_REQUEST['token']) : '';
        $sessionToken = LoginAuth::getToken();
        if ($sessionToken !== '' && $token !== $sessionToken) {
            Output::error('安全token校验失败，请刷新页面重试');
        }
    }

    // 插件可接管 apply 等个人中心 action（如 apply 插件）
    $handled = false;
    if ($action !== '') {
        doOnceAction(HopeHooks::USER_CENTER_API, $action, $handled);
    }
    if ($handled) {
        return;
    }

    switch ($action) {
        case 'profile':
            hope_user_center_api_profile();
            break;
        case 'avatar_upload':
            hope_user_center_api_avatar_upload();
            break;
        case 'avatar_qq':
            hope_user_center_api_avatar_qq();
            break;
        case 'recharge':
            hope_user_center_api_recharge();
            break;
        case 'invite_info':
            hope_user_center_api_invite_info();
            break;
        case 'forum_posts':
            hope_user_center_api_forum_posts();
            break;
        case 'forum_save':
            hope_user_center_api_forum_save();
            break;
        case 'forum_delete':
            hope_user_center_api_forum_delete();
            break;
        case 'forum_get':
            hope_user_center_api_forum_get();
            break;
        case 'forum_sorts':
            hope_user_center_api_forum_sorts();
            break;
        case 'recharge_records':
            hope_user_center_api_recharge_records();
            break;
        case 'my_media':
            hope_user_center_api_my_media();
            break;
        case 'media_upload':
            hope_user_center_api_media_upload();
            break;
        case 'media_delete':
            hope_user_center_api_media_delete();
            break;
        case 'my_comments':
            hope_user_center_api_my_comments();
            break;
        case 'comment_reply':
            hope_user_center_api_comment_reply();
            break;
        default:
            Output::error('未知操作');
    }
}

function hope_user_center_api_profile() {
    $uid = UID;
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        Output::ok(hope_user_center_get_profile($uid));
    }
    $User_Model = new User_Model();
    $data = [
        'nickname'    => Input::postStrVar('nickname'),
        'qq'          => Input::postStrVar('qq'),
        'homepage'    => Input::postStrVar('homepage'),
        'description' => Input::postStrVar('bio'),
    ];
    $User_Model->updateUser($data, $uid);
    $CACHE = Cache::getInstance();
    $CACHE->updateCache('user');
    Output::ok(hope_user_center_get_profile($uid));
}

function hope_user_center_api_avatar_upload() {
    $file = $_FILES['avatar'] ?? null;
    $up = hope_user_center_upload_file($file, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    if (!$up['ok']) {
        Output::error($up['msg']);
    }
    $User_Model = new User_Model();
    $User_Model->updateUser(['photo' => $up['path'], 'update_time' => time()], UID);
    $CACHE = Cache::getInstance();
    $CACHE->updateCache('user');
    Output::ok(['photo' => $up['url']]);
}

function hope_user_center_api_avatar_qq() {
    $qq = Input::postStrVar('qq');
    if (!preg_match('/^\d{5,12}$/', $qq)) {
        Output::error('请先填写正确的 QQ 号');
    }
    $url = 'https://q1.qlogo.cn/g?b=qq&nk=' . $qq . '&s=100';
    $User_Model = new User_Model();
    $User_Model->updateUser(['photo' => $url, 'qq' => $qq, 'update_time' => time()], UID);
    $CACHE = Cache::getInstance();
    $CACHE->updateCache('user');
    Output::ok(['photo' => $url]);
}

function hope_user_center_api_recharge() {
    require_once HOPE_ROOT . 'system/lib/payment/pay_sdk.php';
    if (!Pay_Sdk::isEnabled()) {
        Output::error('支付功能已关闭');
    }
    hope_user_center_init_recharge_table();
    $amount = (float)Input::postStrVar('amount');
    $pay_type = Input::postStrVar('pay_type', 'alipay');
    if ($amount <= 0) {
        Output::error('充值金额无效');
    }
    if (!in_array($pay_type, ['alipay', 'wechat'], true)) {
        $pay_type = 'alipay';
    }
    $uid = hope_uc_uid();
    $Pay_Model = new Pay_Model();
    $order = $Pay_Model->createOrder([
        'user_id'  => $uid,
        'title'    => '账户充值',
        'amount'   => $amount,
        'pay_type' => $pay_type,
        'biz_type' => 'recharge',
        'biz_id'   => 0,
    ]);
    Database::table('user_recharge')->insert([
        'user_id'     => $uid,
        'amount'      => $amount,
        'order_no'    => $order['order_no'],
        'status'      => 0,
        'create_time' => time(),
    ]);
    $pay = Pay_Sdk::createPayment($order);
    if (empty($pay['code'])) {
        Output::error($pay['msg'] ?? '创建支付失败');
    }
    Output::ok(['order' => $order, 'payment' => $pay]);
}

function hope_user_center_complete_recharge($order) {
    if (($order['biz_type'] ?? '') !== 'recharge') {
        return false;
    }
    hope_user_center_init_recharge_table();
    $order_no = $order['order_no'];
    $uid = (int)$order['user_id'];
    $amount = (float)$order['amount'];
    $paid = Database::table('user_recharge')->where(['order_no' => $order_no, 'status' => 1])->find();
    if ($paid) {
        return true;
    }
    $pending = Database::table('user_recharge')->where(['order_no' => $order_no, 'status' => 0])->find();
    if (!$pending) {
        Database::table('user_recharge')->insert([
            'user_id'     => $uid,
            'amount'      => $amount,
            'order_no'    => $order_no,
            'status'      => 0,
            'create_time' => (int)($order['create_time'] ?? time()),
        ]);
    }
    Database::execute("UPDATE `" . DB_PREFIX . "user` SET balance=balance+{$amount} WHERE uid={$uid}");
    Database::table('user_recharge')->where(['order_no' => $order_no])->update(['status' => 1]);
    return true;
}

function hope_user_center_reverse_recharge($order) {
    if (($order['biz_type'] ?? '') !== 'recharge') {
        return false;
    }
    hope_user_center_init_recharge_table();
    $order_no = $order['order_no'];
    $uid = (int)$order['user_id'];
    $amount = (float)$order['amount'];
    $record = Database::table('user_recharge')->where(['order_no' => $order_no, 'status' => 1])->find();
    if (!$record) {
        return false;
    }
    Database::execute("UPDATE `" . DB_PREFIX . "user` SET balance=GREATEST(0, balance-{$amount}) WHERE uid={$uid}");
    Database::table('user_recharge')->where(['order_no' => $order_no])->update(['status' => 2]);
    return true;
}

function hope_user_center_api_invite_info() {
    $uid = hope_uc_uid();
    hope_user_center_ensure_meta($uid);
    $user = hope_uc_user_find($uid);
    $code = $user['invite_code'] ?? '';
    $register_url = hope_site_url('register');
    $sep = (strpos($register_url, '?') !== false) ? '&' : '?';
    $link = $register_url . $sep . 'invite=' . urlencode($code);
    $invited = Database::table('user')->where(['invited_by' => $uid])->count();
    $reward_rows = Database::table('user_invite_reward', 'r')
        ->select('r.*, u.nickname, u.username')
        ->leftJoin('user', 'u', 'u.uid=r.from_user_id')
        ->where(['r.user_id' => $uid])
        ->order('r.create_time DESC')
        ->limit(50)
        ->findAll();
    $total = 0;
    $rewards = [];
    foreach ($reward_rows as $row) {
        $total += (float)$row['amount'];
        $name = $row['nickname'] ?: $row['username'];
        $rewards[] = [
            'from_user' => $name ?: '邀请奖励',
            'amount'    => (float)$row['amount'],
            'date'      => date('Y-m-d', (int)$row['create_time']),
        ];
    }
    Output::ok([
        'code'          => $code,
        'link'          => $link,
        'invited_count' => $invited,
        'total_reward'  => $total,
        'reward_amount' => hope_user_center_get_invite_reward_amount(),
        'enabled'       => hope_user_center_invite_enabled(),
        'rewards'       => $rewards,
    ]);
}

function hope_user_center_recharge_display_status($recharge_row, $pay_order = null) {
    $rc_status = (int)($recharge_row['status'] ?? 0);
    if ($rc_status === 1) {
        return ['key' => 'success', 'label' => '成功'];
    }
    if ($rc_status === 2) {
        return ['key' => 'failed', 'label' => '支付失败'];
    }
    $pay_status = $pay_order ? (int)($pay_order['status'] ?? 0) : -1;
    if ($pay_status === 2 || $pay_status === 3) {
        return ['key' => 'failed', 'label' => '支付失败'];
    }
    $create_time = (int)($recharge_row['create_time'] ?? 0);
    if ($create_time > 0 && (time() - $create_time) > 1800) {
        return ['key' => 'failed', 'label' => '支付失败'];
    }
    return ['key' => 'pending', 'label' => '待支付'];
}

function hope_user_center_api_forum_sorts() {
    $sorts = hope_user_center_forum_sorts();
    $list = [];
    foreach ($sorts as $s) {
        if (empty($s['sid'])) {
            continue;
        }
        $list[] = ['id' => (int)$s['sid'], 'name' => $s['sortname']];
    }
    Output::ok($list);
}

function hope_user_center_api_forum_posts() {
    $uid = hope_uc_uid();
    $sort_ids = hope_user_center_forum_sort_ids();
    $page = max(1, Input::getIntVar('page', 1));
    $per_page = max(1, min(50, Input::getIntVar('per_page', 10)));

    $makeQuery = static function () use ($uid, $sort_ids) {
        $q = Database::table('article')->where(['author' => $uid, 'type' => 'blog']);
        if ($sort_ids) {
            $q->whereIn('sortid', $sort_ids);
        }
        return $q;
    };

    global $CACHE;
    $sort_cache = $CACHE->readCache('sort');
    $formatRows = static function ($db_rows) use ($sort_cache) {
        $rows = [];
        foreach ($db_rows as $row) {
            $sid = (int)$row['sortid'];
            $gid = (int)$row['gid'];
            $st = hope_user_center_forum_status($row);
            $rows[] = [
                'id'           => $gid,
                'title'        => $row['title'],
                'sort_id'      => $sid,
                'sort_name'    => $sort_cache[$sid]['sortname'] ?? '',
                'date'         => date('Y-m-d H:i', (int)$row['date']),
                'views'        => (int)$row['views'],
                'cover'        => !empty($row['cover']) ? getFileUrl($row['cover']) : '',
                'status'       => $st['key'],
                'status_label' => $st['label'],
                'url'          => ($st['key'] === 'published' && $gid > 0) ? Url::log($gid) : '',
            ];
        }
        return $rows;
    };

    $total = (int)$makeQuery()->count();
    $offset = ($page - 1) * $per_page;
    $db_rows = $makeQuery()
        ->select('gid, title, sortid, date, views, checked, hide, cover')
        ->order('date DESC')
        ->limit($per_page, $offset)
        ->findAll();
    Output::ok([
        'list'     => $formatRows($db_rows),
        'total'    => $total,
        'page'     => $page,
        'per_page' => $per_page,
    ]);
}

function hope_user_center_api_forum_save() {
    $Log_Model = new Log_Model();
    $Tag_Model = new Tag_Model();
    $CACHE = Cache::getInstance();
    $uid = hope_uc_uid();
    $id = Input::postIntVar('id');
    $title = Input::postStrVar('title');
    $content = Input::postStrVar('content');
    $sort_id = Input::postIntVar('sort_id');
    $alias = trim(Input::postStrVar('alias'));
    $password = Input::postStrVar('password');
    $allow_remark = Input::postStrVar('allow_remark', 'y') === 'n' ? 'n' : 'y';
    $tags = Input::postStrVar('tags');
    $submit_type = Input::postStrVar('submit_type', 'pending');
    $cover_path = Input::postStrVar('cover_path');

    if ($title === '' || $content === '') {
        Output::error('标题和内容不能为空');
    }
    $sort_ids = hope_user_center_forum_sort_ids();
    if ($sort_id <= 0 && $sort_ids) {
        $sort_id = $sort_ids[0];
    }
    if ($sort_ids && !in_array($sort_id, $sort_ids, true)) {
        $sort_id = $sort_ids[0];
    }
    if ($sort_id <= 0) {
        Output::error('暂无可用分类，请先在后台创建文章分类');
    }

    if ($alias !== '') {
        $logalias_cache = $CACHE->readCache('logalias');
        $alias = $Log_Model->checkAlias($alias, $logalias_cache, $id);
    }

    $cover = $cover_path !== null && $cover_path !== false ? trim((string)$cover_path) : '';
    // 允许 http(s) 封面链接或本地相对路径；拒绝 javascript: 等危险协议
    if ($cover !== '' && preg_match('#^(javascript|data|vbscript):#i', $cover)) {
        Output::error('封面图链接不合法');
    }
    if (!empty($_FILES['cover']['name']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
        $up = hope_user_center_upload_file($_FILES['cover'], ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        if (!$up['ok']) {
            Output::error($up['msg']);
        }
        $cover = $up['path'];
    }

    $hide = $submit_type === 'draft' ? 'y' : 'n';
    // 与后台发文一致：开启审核且无编辑权限时待审，否则直接发布
    $checked = (Option::get('ischkarticle') === 'y' && !User::haveEditPermission()) ? 'n' : 'y';
    if ($hide === 'y') {
        $checked = 'n';
    }
    $now = time();

    $logData = [
        'title'        => $title,
        'content'      => $content,
        'excerpt'      => '',
        'author'       => $uid,
        'sortid'       => $sort_id,
        'type'         => 'blog',
        'hide'         => $hide,
        'checked'      => $checked,
        'allow_remark' => $allow_remark,
        'alias'        => $alias,
        'cover'        => $cover,
        'password'     => $password,
        'date'         => $now,
        'top'          => 'n',
        'sortop'       => 'n',
    ];

    if ($id > 0) {
        if (!hope_uc_blog_owned($id, $uid)) {
            Output::error('文章不存在');
        }
        $Log_Model->updateLog($logData, $id, $uid);
        $Tag_Model->updateTag($tags, $id);
        $CACHE->updateCache(['sta', 'newlog', 'logalias', 'tags']);
        doAction('save_log', $id);
        $st = hope_user_center_forum_status(['hide' => $hide, 'checked' => $checked]);
        Output::ok(['id' => $id, 'status' => $st['key'], 'status_label' => $st['label']]);
    }

    $new_id = (int)$Log_Model->addlog($logData);
    if ($new_id <= 0) {
        Output::error('文章保存失败，请稍后重试');
    }
    $Tag_Model->addTag($tags, $new_id);
    $CACHE->updateCache(['sta', 'newlog', 'logalias', 'tags']);
    doAction('save_log', $new_id);
    $st = hope_user_center_forum_status(['hide' => $hide, 'checked' => $checked]);
    Output::ok(['id' => $new_id, 'status' => $st['key'], 'status_label' => $st['label']]);
}

function hope_user_center_api_forum_delete() {
    $id = Input::postIntVar('id');
    if (!hope_uc_blog_owned($id, hope_uc_uid())) {
        Output::error('文章不存在');
    }
    $Log_Model = new Log_Model();
    $Log_Model->deleteLog($id);
    $CACHE = Cache::getInstance();
    $CACHE->updateCache(['sta', 'newlog']);
    Output::ok();
}

function hope_user_center_api_recharge_records() {
    hope_user_center_init_recharge_table();
    $uid = hope_uc_uid();
    $page = max(1, Input::getIntVar('page', 1));
    $per_page = max(1, min(50, Input::getIntVar('per_page', 10)));
    $offset = ($page - 1) * $per_page;

    $total = (int)Database::table('user_recharge')->where(['user_id' => $uid])->count();
    $db_rows = Database::table('user_recharge')
        ->select('amount, create_time, status, order_no')
        ->where(['user_id' => $uid])
        ->order('create_time DESC')
        ->limit($per_page, $offset)
        ->findAll();

    $order_nos = [];
    foreach ($db_rows as $row) {
        if (!empty($row['order_no'])) {
            $order_nos[] = $row['order_no'];
        }
    }
    $pay_map = [];
    if ($order_nos) {
        $pay_rows = Database::table('pay_order')
            ->select('order_no, status')
            ->whereIn('order_no', $order_nos)
            ->findAll();
        foreach ($pay_rows as $pr) {
            $pay_map[$pr['order_no']] = $pr;
        }
    }

    $rows = [];
    foreach ($db_rows as $row) {
        $pay_order = !empty($row['order_no']) ? ($pay_map[$row['order_no']] ?? null) : null;
        $st = hope_user_center_recharge_display_status($row, $pay_order);
        $rows[] = [
            'amount'       => (float)$row['amount'],
            'date'         => date('Y-m-d H:i', (int)$row['create_time']),
            'status'       => $st['key'],
            'status_label' => $st['label'],
            'order_no'     => $row['order_no'] ?? '',
        ];
    }
    Output::ok([
        'list'     => $rows,
        'total'    => $total,
        'page'     => $page,
        'per_page' => $per_page,
    ]);
}

function hope_user_center_api_forum_get() {
    $id = Input::getIntVar('id');
    $post = hope_user_center_api_forum_get_one(UID, $id);
    if (!$post) {
        Output::error('文章不存在');
    }
    Output::ok($post);
}

function hope_user_center_api_my_media() {
    $uid = (int)UID;
    $page = max(1, Input::getIntVar('page', 1));
    $per_page = max(1, min(48, Input::getIntVar('per_page', 24)));
    $keyword = trim(Input::getStrVar('keyword'));
    $type = trim(Input::getStrVar('media_type', ''));
    if ($type === '') {
        $type = trim(Input::getStrVar('type', 'all'));
    }
    if (!in_array($type, ['all', 'image', 'file'], true)) {
        $type = 'all';
    }
    $offset = ($page - 1) * $per_page;

    $countQuery = Database::table('upload')->where(['author' => $uid]);
    $listQuery = Database::table('upload')->where(['author' => $uid]);
    if ($keyword !== '') {
        $countQuery->whereLike('filename', $keyword);
        $listQuery->whereLike('filename', $keyword);
    }
    // 图片/文件筛选：按扩展名，并兼顾 mimetype 为空的记录
    $imgSql = "(LOWER(`filename`) LIKE '%.jpg' OR LOWER(`filename`) LIKE '%.jpeg' OR LOWER(`filename`) LIKE '%.png'"
        . " OR LOWER(`filename`) LIKE '%.gif' OR LOWER(`filename`) LIKE '%.webp' OR LOWER(`filename`) LIKE '%.bmp'"
        . " OR LOWER(`filename`) LIKE '%.svg' OR `mimetype` LIKE 'image/%')";
    if ($type === 'image') {
        $countQuery->whereRaw($imgSql);
        $listQuery->whereRaw($imgSql);
    } elseif ($type === 'file') {
        $countQuery->whereRaw('NOT ' . $imgSql);
        $listQuery->whereRaw('NOT ' . $imgSql);
    }
    $total = (int)$countQuery->count();
    $rows = $listQuery->order('aid DESC')->limit($per_page, $offset)->findAll();

    $list = [];
    foreach ($rows as $row) {
        $item = hope_user_center_format_media_row($row);
        if ($item) {
            $list[] = $item;
        }
    }
    Output::ok([
        'list'     => $list,
        'total'    => $total,
        'page'     => $page,
        'per_page' => $per_page,
        'type'     => $type,
    ]);
}

function hope_user_center_api_media_upload() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Output::error('请求方式错误');
    }
    $file = $_FILES['file'] ?? null;
    $up = hope_user_center_upload_file($file);
    if (empty($up['ok'])) {
        Output::error($up['msg'] ?? '上传失败');
    }
    $item = [
        'id'       => (int)($up['aid'] ?? 0),
        'name'     => $file['name'] ?? '',
        'url'      => $up['url'] ?? '',
        'size'     => isset($file['size']) ? changeFileSize((int)$file['size']) : '',
        'mime'     => '',
        'is_image' => false,
        'date'     => date('Y-m-d H:i:s'),
    ];
    if (!empty($up['aid'])) {
        $row = Database::table('upload')->where(['aid' => (int)$up['aid'], 'author' => (int)UID])->find();
        $formatted = hope_user_center_format_media_row($row);
        if ($formatted) {
            $item = $formatted;
        }
    }
    Output::ok($item);
}

function hope_user_center_api_media_delete() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Output::error('请求方式错误');
    }
    $id = Input::postIntVar('id');
    if ($id <= 0) {
        Output::error('参数错误');
    }
    $Upload_Model = new Upload_Model();
    if (!$Upload_Model->deleteMedia($id)) {
        Output::error('删除失败或无权操作');
    }
    Output::ok(['id' => $id]);
}

function hope_user_center_truncate_comment_text($text, $max = 120) {
    $text = strip_tags($text ?? '');
    if ($text === '') {
        return '';
    }
    if (function_exists('mb_strlen') && mb_strlen($text) > $max) {
        return mb_substr($text, 0, $max) . '…';
    }
    if (strlen($text) > $max) {
        return substr($text, 0, $max) . '…';
    }
    return $text;
}

function hope_user_center_api_my_comments() {
    $uid = UID;
    $page = max(1, Input::getIntVar('page', 1));
    $per_page = max(1, min(50, Input::getIntVar('per_page', 15)));
    $offset = ($page - 1) * $per_page;
    $prefix = DB_PREFIX;
    $count_row = Database::fetchOne("SELECT COUNT(*) AS cnt FROM {$prefix}comment WHERE uid = {$uid}");
    $total = (int)($count_row['cnt'] ?? 0);
    $rows = Database::fetchAll(
        "SELECT c.cid, c.gid, c.comment, c.date, c.hide, c.pid, b.title "
        . "FROM {$prefix}comment c "
        . "LEFT JOIN {$prefix}article b ON b.gid = c.gid "
        . "WHERE c.uid = {$uid} ORDER BY c.date DESC LIMIT {$offset}, {$per_page}"
    );
    $parent_ids = [];
    foreach ($rows as $row) {
        $parent_id = (int)($row['pid'] ?? 0);
        if ($parent_id > 0) {
            $parent_ids[$parent_id] = $parent_id;
        }
    }
    $parents = [];
    if ($parent_ids) {
        $pid_list = implode(',', array_map('intval', array_values($parent_ids)));
        $parent_rows = Database::fetchAll(
            "SELECT cid, gid, poster, comment FROM {$prefix}comment WHERE cid IN ({$pid_list})"
        );
        foreach ($parent_rows as $parent_row) {
            $parents[(int)$parent_row['cid']] = $parent_row;
        }
    }
    $list = [];
    foreach ($rows as $row) {
        $gid = (int)$row['gid'];
        $pid = (int)($row['pid'] ?? 0);
        $content = hope_user_center_truncate_comment_text($row['comment'] ?? '');
        $item = [
            'id'           => (int)$row['cid'],
            'gid'          => $gid,
            'pid'          => $pid,
            'article'      => $row['title'] ?: '文章已删除',
            'article_url'  => $gid > 0 ? Url::log($gid) : '',
            'comment_url'  => $gid > 0 ? Url::log($gid) . '#comment-' . (int)$row['cid'] : '',
            'content'      => $content,
            'date'         => date('Y-m-d H:i', (int)$row['date']),
            'status'       => ($row['hide'] ?? 'n') === 'y' ? 'hidden' : 'visible',
            'status_label' => ($row['hide'] ?? 'n') === 'y' ? '已隐藏' : '已显示',
            'is_reply'     => $pid > 0,
            'parent'       => null,
        ];
        if ($pid > 0 && isset($parents[$pid])) {
            $parent = $parents[$pid];
            $parent_gid = (int)$parent['gid'];
            $item['parent'] = [
                'id'      => (int)$parent['cid'],
                'author'  => strip_tags($parent['poster'] ?? ''),
                'content' => hope_user_center_truncate_comment_text($parent['comment'] ?? '', 80),
                'url'     => $parent_gid > 0 ? Url::log($parent_gid) . '#comment-' . (int)$parent['cid'] : '',
            ];
        }
        $list[] = $item;
    }
    Output::ok([
        'list'     => $list,
        'total'    => $total,
        'page'     => $page,
        'per_page' => $per_page,
    ]);
}

/** 用户中心：回复评论 */
function hope_user_center_api_comment_reply() {
    $pid = Input::postIntVar('pid');
    $content = trim(Input::postStrVar('content'));
    if ($pid <= 0) {
        Output::error('请选择要回复的评论');
    }
    if ($content === '') {
        Output::error('回复内容不能为空');
    }
    if (mb_strlen($content) > 60000) {
        Output::error('回复内容过长');
    }
    if (Option::get('iscomment') === 'n') {
        Output::error('站点已关闭评论功能');
    }

    $Comment_Model = new Comment_Model();
    $parent = Database::getOne('comment', ['cid' => $pid]);
    if (!$parent) {
        Output::error('评论不存在');
    }
    $blogId = (int)$parent['gid'];
    $Log_Model = new Log_Model();
    $log = $Log_Model->getDetail($blogId);
    if (!$log) {
        Output::error('文章不存在');
    }
    if (($log['allow_remark'] ?? 'n') !== 'y') {
        Output::error('该文章未开启评论');
    }

    $User_Model = new User_Model();
    $user_info = $User_Model->getOneUser(UID);
    if (!$user_info) {
        Output::error('用户不存在');
    }
    $name = addslashes($user_info['name_orig']);
    $mail = addslashes($user_info['email'] ?? '');
    $url = addslashes(SITE_URL);

    $r = $Comment_Model->addComment(UID, $name, $content, $mail, $url, $blogId, $pid);
    $hide = $r['hide'] ?? 'n';
    notice::sendNewCommentMail($content, $blogId, $pid);
    doAction('post_comment', $name, $mail, $url, $content, $blogId, (int)($r['cid'] ?? 0), $pid);

    Output::ok([
        'cid'          => (int)($r['cid'] ?? 0),
        'status'       => $hide === 'y' ? 'hidden' : 'visible',
        'status_label' => $hide === 'y' ? '待审核' : '已显示',
        'article_url'  => Url::log($blogId),
    ]);
}

function hope_user_center_api_forum_get_one($uid, $id) {
    $row = Database::table('article')
        ->select('gid, title, content, sortid, checked, hide, alias, cover, password, allow_remark')
        ->where(['gid' => (int)$id, 'author' => (int)$uid])
        ->find();
    if (!$row) {
        return null;
    }
    $st = hope_user_center_forum_status($row);
    $cover = $row['cover'] ?? '';
    if ($cover && strpos($cover, 'http') !== 0) {
        $cover = hope_user_center_photo_url($cover);
    }
    $submit_type = ($row['hide'] ?? 'n') === 'y' ? 'draft' : 'pending';
    return [
        'id'           => (int)$row['gid'],
        'title'        => $row['title'],
        'content'      => $row['content'],
        'sort_id'      => (int)$row['sortid'],
        'alias'        => $row['alias'] ?? '',
        'cover'        => $cover,
        'cover_path'   => $row['cover'] ?? '',
        'password'     => $row['password'] ?? '',
        'allow_remark' => ($row['allow_remark'] ?? 'y') === 'n' ? 'n' : 'y',
        'tags'         => hope_user_center_forum_tags_string($id),
        'submit_type'  => $submit_type,
        'status'       => $st['key'],
        'status_label' => $st['label'],
    ];
}
