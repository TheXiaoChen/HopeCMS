<?php
/**
 * Hope CMS(希望CMS) article
 * @author 林子辰
 * @link http://www.hopecms.cn
 */
 defined('HOPE_ROOT') || exit('access denied!');

if (isset($_GET['backup'])) {
    require_once HOPE_ROOT . 'system/app/service/backup.php';
    $zipbak = (int)($_POST['zipbak'] ?? Input)::getIntVar('zipbak', 0);
    if (HopeBackup::exportAndDownload($zipbak) === false) {
        Gohref(SELF . '?act=setting&code=error_d');
    }
    exit;
}

if (isset($_GET['import'])) {
    require_once HOPE_ROOT . 'system/app/service/backup.php';
    $result = HopeBackup::importUploadedFile($_FILES['sqlfile'] ?? []);
    if ($result === true) {
        Gohref(SELF . '?act=setting&code=import');
    }
    $errorMap = [
        'error_c' => 'error_a',
        'error_d' => 'error_b',
        'error_e' => 'error_c',
    ];
    Gohref(SELF . '?act=setting&code=' . ($errorMap[$result] ?? 'error_e'));
    exit;
}

if (isset($_GET['Cache']) || (isset($_POST['action']) && $_POST['action'] === 'Cache')) {
    LoginAuth::checkToken();
    global $CACHE;
    $CACHE->updateCache();
    if (class_exists('Option') && method_exists('Option', 'resetRoutingTableCache')) {
        Option::resetRoutingTableCache();
    }
    Gohref(SELF . '?act=setting&code=mc');
    exit;
}

$options_cache = $CACHE->readCache('options');

if (isset($_GET['save'])) {
    LoginAuth::checkToken();
    $getData = [
        'sitename'              => Input::postStrVar('sitename'),
        'siteurl'               => Input::postStrVar('siteurl'),
        'detect_url'            => Input::postStrVar('detect_url','n'),
        'siteinfo'              => Input::postStrVar('siteinfo'),
        'footer_info'           => Input::postRawStr('footer_info'),
        'timezone'              => Input::postStrVar('timezone'),
        'language'              => Input::postStrVar('language'),
        'debug'                 => Input::postStrVar('debug', 'n'),
        'close'                 => Input::postStrVar('close', 'n'),
        'close_title'           => Input::postStrVar('close_title'),
        'close_msg'             => Input::postStrVar('close_msg'),

        'site_title'            => Input::postStrVar('site_title', ''),
        'log_title_style'       => Input::postStrVar('log_title_style', '0'),
        'site_key'              => Input::postStrVar('site_key', ''),
        'site_description'      => Input::postStrVar('site_description', ''),

        'index_lognum'          => Input::postIntVar('index_lognum'),
        'search_date'           => Input::postIntVar('search_date'),

        'is_signup'             => Input::postStrVar('is_signup', 'n'),
        'ischkarticle'          => Input::postStrVar('ischkarticle', 'n'),
        'login_code'            => Input::postStrVar('login_code', 'n'),
        'email_code'            => Input::postStrVar('email_code', 'n'),
        'posts_per_day'         => Input::postIntVar('posts_per_day', 0),
        'invite_reward_amount'  => max(0, (float)Input::postStrVar('invite_reward_amount', 0)),
        'article_uneditable'    => Input::postStrVar('article_uneditable', 'n'),
        'forbid_user_upload'    => Input::postStrVar('forbid_user_upload', 'n'),
        'writer_permissions'    => User::normalizeWriterPermissions(Input::postStrArray('writer_permissions')),
        'editor_permissions'    => User::normalizeEditorPermissions(Input::postStrArray('editor_permissions')),

        'iscomment'             => Input::postStrVar('iscomment', 'n'),
        'ischkcomment'          => Input::postStrVar('ischkcomment', 'n'),
        'comment_code'          => Input::postStrVar('comment_code', 'n'),
        'login_comment'         => Input::postStrVar('login_comment', 'n'),
        'comment_needchinese'   => Input::postStrVar('comment_needchinese', 'n'),
        'comment_order'         => Input::postStrVar('comment_order', 'newer'),
        'comment_interval'      => Input::postIntVar('comment_interval', 15),
        'comment_pnum'          => Input::postIntVar('comment_pnum'),
        'comment_paging'        => Input::postStrVar('comment_paging', 'n'),

        'att_type'              => str_replace('php', 'x', strtolower(Input::postStrVar('att_type', ''))),
        'att_maxsize'           => Input::postIntVar('att_maxsize'),

        'smtp_mail'             => Input::postStrVar('smtp_mail'),
        'smtp_pw'               => Input::postStrVar('smtp_pw'),
        'smtp_from_name'        => Input::postStrVar('smtp_from_name'),
        'smtp_server'           => Input::postStrVar('smtp_server'),
        'smtp_port'             => Input::postStrVar('smtp_port'),
        'mail_notice_comment'   => Input::postStrVar('mail_notice_comment', 'n'),
        'mail_notice_post'      => Input::postStrVar('mail_notice_post', 'n'),

        'linkmode'              => Input::postStrVar('linkmode', '0'),
        'rule_index'            => Input::postStrVar('rule_index'),
        'rule_article'          => Input::postStrVar('rule_article'),
        'rule_page'             => Input::postStrVar('rule_page'),
        'rule_tag'              => Input::postStrVar('rule_tag'),
        'rule_category'         => Input::postStrVar('rule_category'),
        'rule_record'           => Input::postStrVar('rule_record'),

        'is_openapi'            => Input::postStrVar('is_openapi', 'n'),
        'is_limitapi'           => Input::postStrVar('is_limitapi', 'n'),
        'apikey'                => Input::postStrVar('apikey'),
        'limit_num'             => Input::postStrVar('limit_num'),

    ];

    if ($getData['login_code'] == 'y' && !checkGDSupport()) {
        Output::error('开启图形验证码失败，服务器PHP不支持GD图形库');
    }
    if ($getData['comment_code'] == 'y' && !checkGDSupport()) {
        Output::error('开启评论验证码失败，服务器PHP不支持GD图形库');
    }

    if ($getData['detect_url'] === 'y' || $getData['siteurl'] === '') {
        $getData['siteurl'] = realUrl();
    }

    if ($getData['siteurl'] && substr($getData['siteurl'], -1) != '/') {
        $getData['siteurl'] .= '/';
    }
    if ($getData['siteurl'] && strncasecmp($getData['siteurl'], 'http', 4)) {
        $scheme = hope_is_https() ? 'https://' : 'http://';
        $getData['siteurl'] = $scheme . $getData['siteurl'];
    }

    $getData['limit_num'] = max(1, min(10000, (int)$getData['limit_num']));
    if ($getData['limit_num'] <= 0) {
        $getData['limit_num'] = 60;
    }
    $getData['apikey'] = trim($getData['apikey']);
    if ($getData['is_openapi'] === 'y' && $getData['apikey'] === '') {
        $getData['apikey'] = md5(getRandStr(32));
    }

    hope_lang_save_settings(['language' => $getData['language']]);

    foreach ($getData as $key => $val) {
        Option::updateOption($key, $val);
    }
    $CACHE->updateCache(array('tags', 'options', 'comment', 'record', 'menu'));
    Option::resetRoutingTableCache();
    doAction('save_setting', $getData);
    Output::ok();
}


if ($act == 'mail_test') {
    $data = [
        'smtp_mail'      => addslashes($_POST['smtp_mail'] ?? ''),
        'smtp_pw'        => addslashes($_POST['smtp_pw'] ?? ''),
        'smtp_from_name' => addslashes($_POST['smtp_from_name'] ?? ''),
        'smtp_server'    => addslashes($_POST['smtp_server'] ?? ''),
        'smtp_port'      => (int)($_POST['smtp_port'] ?? ''),
        'testTo'         => $_POST['testTo'] ?? '',
    ];

    if (!checkMail($data['testTo'])) {
        exit("<small class='text-info'>请正确填写邮箱</small>");
    }

    $mail = new PHPMailer(true);
    $mail->IsSMTP();
    $mail->CharSet = 'UTF-8';
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = $data['smtp_port'] == '587' ? 'STARTTLS' : 'ssl';
    $mail->Port = $data['smtp_port'];
    $mail->Host = $data['smtp_server'];
    $mail->Username = $data['smtp_mail'];
    $mail->Password = $data['smtp_pw'];
    $mail->From = $data['smtp_mail'];
    $mail->FromName = $data['smtp_from_name'];
    $mail->AddAddress($data['testTo']);
    $mail->Subject = '测试邮件';
    $mail->isHTML();
    $mail->Body = Notice::getMailTemplate('这是一封测试邮件');

    try {
        return $mail->Send();
    } catch (Exception $exc) {
        exit("<small class='text-danger'>发送失败</small>");
    }
}


if (isset($_GET['api_reset'])) {
    LoginAuth::checkToken();
    $apikey = md5(getRandStr(32));
    Option::updateOption('apikey', $apikey);
    $CACHE->updateCache('options');
    Gohref(SELF."?act=setting&code=ok_reset");
}


extract($options_cache);
$siteurl = Option::get('siteurl');
$conf_detect_url = ($detect_url ?? 'n') == 'y' ? 'checked="checked"' : '';
$conf_is_debug = $debug == 'y' ? 'checked="checked"' : '';
$conf_is_close = $close == 'y' ? 'checked="checked"' : '';
$close_title = $close_title ?? '';
$close_msg = $close_msg ?? '';
$limit_num = isset($limit_num) && $limit_num !== '' ? (int)$limit_num : 60;
$apikey = $apikey ?? '';
$hope_api_base_url = hope_api_base_url();

$hope_lang_settings = hope_lang_settings_for_admin();
$hope_lang_registry = $hope_lang_settings['registry'];
$language = $hope_lang_settings['language'];

$conf_is_signup = $is_signup == 'y' ? 'checked="checked"' : '';

$conf_posts_per_day = empty($conf_posts_per_day) ? '0' : $posts_per_day;
$conf_comment_needchinese = $comment_needchinese == 'y' ? 'checked="checked"' : '';
$conf_comment_order = $comment_order != 'newer' ? 'checked="checked"' : '';
$conf_linkmode_0 = $linkmode == '0' ? 'checked="checked"' : '';
$conf_linkmode_1 = $linkmode == '1' ? 'checked="checked"' : '';
$conf_linkmode_2 = $linkmode == '2' ? 'checked="checked"' : '';
$conf_is_openapi = $is_openapi == 'y' ? 'checked="checked"' : '';
$conf_is_limitapi = $is_limitapi == 'y' ? 'checked="checked"' : '';


$conf_comment_code = $comment_code == 'y' ? 'checked="checked"' : '';
$conf_iscomment = $iscomment == 'y' ? 'checked="checked"' : '';
$conf_login_comment = $login_comment == 'y' ? 'checked="checked"' : '';
$conf_ischkcomment = $ischkcomment == 'y' ? 'checked="checked"' : '';
$conf_comment_paging = $comment_paging == 'y' ? 'checked="checked"' : '';
/*
$ex1 = $ex2 = $ex3 = $ex4 = '';
if ($rss_output_fulltext == 'y') {
    $ex1 = 'selected="selected"';
} else {
    $ex2 = 'selected="selected"';
}
if ($comment_order == 'newer') {
    $ex3 = 'selected="selected"';
} else {
    $ex4 = 'selected="selected"';
}
*/
$tzlist = [
    'Etc/GMT'              => '(UTC)协调世界时',
    'Africa/Casablanca'    => '(UTC)卡萨布兰卡',
    'Atlantic/Reykjavik'   => '(UTC)蒙罗维亚，雷克雅未克',
    'Europe/London'        => '(UTC)都柏林，爱丁堡，里斯本，伦敦',
    'Africa/Lagos'         => '(UTC+01:00)中非西部',
    'Europe/Paris'         => '(UTC+01:00)布鲁塞尔，哥本哈根，马德里，巴黎',
    'Africa/Windhoek'      => '(UTC+01:00)温得和克',
    'Europe/Warsaw'        => '(UTC+01:00)萨拉热窝，斯科普里，华沙，萨格勒布',
    'Europe/Budapest'      => '(UTC+01:00)贝尔格莱德，布拉迪斯拉发，布达佩斯，卢布尔雅那，布拉格',
    'Europe/Berlin'        => '(UTC+01:00)阿姆斯特丹，柏林，伯尔尼，罗马，斯德哥尔摩，维也纳',
    'Europe/Istanbul'      => '(UTC+02:00)伊斯坦布尔',
    'Europe/Kaliningrad'   => '(UTC+02:00)加里宁格勒(RTZ 1)',
    'Africa/Johannesburg'  => '(UTC+02:00)哈拉雷，比勒陀利亚',
    'Asia/Damascus'        => '(UTC+02:00)大马士革',
    'Asia/Amman'           => '(UTC+02:00)安曼',
    'Africa/Cairo'         => '(UTC+02:00)开罗',
    'Africa/Tripoli'       => '(UTC+02:00)的黎波里',
    'Asia/Jerusalem'       => '(UTC+02:00)耶路撒冷',
    'Asia/Beirut'          => '(UTC+02:00)贝鲁特',
    'Europe/Kiev'          => '(UTC+02:00)赫尔辛基，基辅，里加，索非亚，塔林，维尔纽斯',
    'Europe/Bucharest'     => '(UTC+02:00)雅典，布加勒斯特',
    'Africa/Nairobi'       => '(UTC+03:00)内罗毕',
    'Asia/Baghdad'         => '(UTC+03:00)巴格达',
    'Europe/Minsk'         => '(UTC+03:00)明斯克',
    'Asia/Riyadh'          => '(UTC+03:00)科威特，利雅得',
    'Europe/Moscow'        => '(UTC+03:00)莫斯科，圣彼得堡，伏尔加格勒(RTZ 2)',
    'Asia/Tehran'          => '(UTC+03:30)德黑兰',
    'Europe/Samara'        => '(UTC+04:00)伊热夫斯克，萨马拉(RTZ 3)',
    'Asia/Yerevan'         => '(UTC+04:00)埃里温',
    'Asia/Bak'             => '(UTC+04:00)巴库',
    'Asia/Tbilisi'         => '(UTC+04:00)第比利斯',
    'Indian/Mauritius'     => '(UTC+04:00)路易港',
    'Asia/Dubai'           => '(UTC+04:00)阿布扎比，马斯喀特',
    'Asia/Kabu'            => '(UTC+04:30)喀布尔',
    'Asia/Karachi'         => '(UTC+05:00)伊斯兰堡，卡拉奇',
    'Asia/Yekaterinburg'   => '(UTC+05:00)叶卡捷琳堡(RTZ 4)',
    'Asia/Tashkent'        => '(UTC+05:00)阿什哈巴德，塔什干',
    'Asia/Colombo'         => '(UTC+05:30)斯里加亚渥登普拉',
    'Asia/Calcutta'        => '(UTC+05:30)钦奈，加尔各答，孟买，新德里',
    'Asia/Katmandu'        => '(UTC+05:45)加德满都',
    'Asia/Novosibirsk'     => '(UTC+06:00)新西伯利亚(RTZ 5)',
    'Asia/Dhaka'           => '(UTC+06:00)达卡',
    'Asia/Almaty'          => '(UTC+06:00)阿斯塔纳',
    'Asia/Rangoon'         => '(UTC+06:30)仰光',
    'Asia/Krasnoyarsk'     => '(UTC+07:00)克拉斯诺亚尔斯克(RTZ 6)',
    'Asia/Bangkok'         => '(UTC+07:00)曼谷，河内，雅加达',
    'Asia/Ulaanbaatar'     => '(UTC+08:00)乌兰巴托',
    'Asia/Irkutsk'         => '(UTC+08:00)伊尔库茨克(RTZ 7)',
    'Asia/Shanghai'        => '(UTC+08:00)北京，重庆，香港特别行政区，乌鲁木齐',
    'Asia/Taipei'          => '(UTC+08:00)台北',
    'Asia/Singapore'       => '(UTC+08:00)吉隆坡，新加坡',
    'Australia/Perth'      => '(UTC+08:00)珀斯',
    'Asia/Tokyo'           => '(UTC+09:00)大阪，札幌，东京',
    'Asia/Yakutsk'         => '(UTC+09:00)雅库茨克(RTZ 8)',
    'Asia/Seoul'           => '(UTC+09:00)首尔',
    'Australia/Darwin'     => '(UTC+09:30)达尔文',
    'Australia/Adelaide'   => '(UTC+09:30)阿德莱德',
    'Pacific/Port_Moresby' => '(UTC+10:00)关岛，莫尔兹比港',
    'Australia/Sydney'     => '(UTC+10:00)堪培拉，墨尔本，悉尼',
    'Australia/Brisbane'   => '(UTC+10:00)布里斯班',
    'Asia/Vladivostok'     => '(UTC+10:00)符拉迪沃斯托克，马加丹(RTZ 9)',
    'Australia/Hobart'     => '(UTC+10:00)霍巴特',
    'Asia/Magadan'         => '(UTC+10:00)马加丹',
    'Asia/Srednekolymsk'   => '(UTC+11:00)乔库尔达赫(RTZ 10)',
    'Pacific/Guadalcanal'  => '(UTC+11:00)所罗门群岛，新喀里多尼亚',
    'Etc/GMT-12'           => '(UTC+12:00)协调世界时+12',
    'Pacific/Auckland'     => '(UTC+12:00)奥克兰，惠灵顿',
    'Pacific/Fiji'         => '(UTC+12:00)斐济',
    'Asia/Kamchatka'       => '(UTC+12:00)阿纳德尔，彼得罗巴甫洛夫斯克-堪察加(RTZ 11)',
    'Pacific/Tongatapu'    => '(UTC+13:00)努库阿洛法',
    'Pacific/Apia'         => '(UTC+13:00)萨摩亚群岛',
    'Pacific/Kiritimati'   => '(UTC+14:00)圣诞岛',
    'Atlantic/Azores'      => '(UTC-01:00)亚速尔群岛',
    'Atlantic/Cape_Verde'  => '(UTC-01:00)佛得角群岛',
    'Etc/GMT+2'            => '(UTC-02:00)协调世界时-02',
    'America/Cayenne'      => '(UTC-03:00)卡宴，福塔雷萨',
    'America/Sao_Paulo'    => '(UTC-03:00)巴西利亚',
    'America/Buenos_Aires' => '(UTC-03:00)布宜诺斯艾利斯',
    'America/Godthab'      => '(UTC-03:00)格陵兰',
    'America/Bahia'        => '(UTC-03:00)萨尔瓦多',
    'America/Montevideo'   => '(UTC-03:00)蒙得维的亚',
    'America/St_Johns'     => '(UTC-03:30)纽芬兰',
    'America/La_Paz'       => '(UTC-04:00)乔治敦，拉巴斯，马瑙斯，圣胡安',
    'America/Asuncion'     => '(UTC-04:00)亚松森',
    'America/Halifax'      => '(UTC-04:00)大西洋时间(加拿大)',
    'America/Cuiaba'       => '(UTC-04:00)库亚巴',
    'America/Caracas'      => '(UTC-04:30)加拉加斯',
    'America/New_York'     => '(UTC-05:00)东部时间(美国和加拿大)',
    'America/Indianapolis' => '(UTC-05:00)印地安那州(东部)',
    'America/Bogota'       => '(UTC-05:00)波哥大，利马，基多，里奥布朗库',
    'America/Guatemala'    => '(UTC-06:00)中美洲',
    'America/Chicago'      => '(UTC-06:00)中部时间(美国和加拿大)',
    'America/Mexico_City'  => '(UTC-06:00)瓜达拉哈拉，墨西哥城，蒙特雷',
    'America/Regina'       => '(UTC-06:00)萨斯喀彻温',
    'America/Phoenix'      => '(UTC-07:00)亚利桑那',
    'America/Chihuahua'    => '(UTC-07:00)奇瓦瓦，拉巴斯，马萨特兰',
    'America/Denver'       => '(UTC-07:00)山地时间(美国和加拿大)',
    'America/Santa_Isabel' => '(UTC-08:00)下加利福尼亚州',
    'America/Los_Angeles'  => '(UTC-08:00)太平洋时间(美国和加拿大)',
    'America/Anchorage'    => '(UTC-09:00)阿拉斯加',
    'Pacific/Honolulu'     => '(UTC-10:00)夏威夷',
    'Etc/GMT+11'           => '(UTC-11:00)协调世界时-11',
    'Etc/GMT+12'           => '(UTC-12:00)国际日期变更线西',
];

$opt0 = $opt1 = $opt2 = '';
$t = 'opt' . $log_title_style;
$$t = 'selected="selected"';




$is_signup = $options_cache['is_signup'] ?? '';
$login_code = $options_cache['login_code'] ?? '';
$ischkarticle = $options_cache['ischkarticle'] ?? '';
$article_uneditable = $options_cache['article_uneditable'] ?? '';
$forbid_user_upload = $options_cache['forbid_user_upload'] ?? '';
$posts_per_day = $options_cache['posts_per_day'] ?? '';
$posts_name = $options_cache['posts_name'] ?? '';
$email_code = $options_cache['email_code'] ?? '';
$att_maxsize = $options_cache['att_maxsize'] ?? '';
$att_type = $options_cache['att_type'] ?? '';

$conf_login_code = $login_code == 'y' ? 'checked="checked"' : '';
$conf_email_code = $email_code == 'y' ? 'checked="checked"' : '';
$conf_ischkarticle = $ischkarticle == 'y' ? 'checked="checked"' : '';
$conf_forbid_user_upload = $forbid_user_upload == 'y' ? 'checked="checked"' : '';
$conf_article_uneditable = $article_uneditable == 'y' ? 'checked="checked"' : '';
$invite_reward_amount = (float)($options_cache['invite_reward_amount'] ?? 0);

$role_permission_registry = User::rolePermissionRegistry();
$writer_permissions_selected = User::getWriterPermissions();
$editor_permissions_selected = User::getEditorPermissions();

$smtp_mail = $options_cache['smtp_mail'] ?? '';
$smtp_pw = $options_cache['smtp_pw'] ?? '';
$smtp_from_name = $options_cache['smtp_from_name'] ?? '';
$smtp_server = $options_cache['smtp_server'] ?? '';
$smtp_port = $options_cache['smtp_port'] ?? '';
$mail_notice_comment = $options_cache['mail_notice_comment'] ?? '';
$mail_notice_post = $options_cache['mail_notice_post'] ?? '';
$mail_template = $options_cache['mail_template'] ?? '';

$conf_mail_notice_comment = $mail_notice_comment == 'y' ? 'checked="checked"' : '';
$conf_mail_notice_post = $mail_notice_post == 'y' ? 'checked="checked"' : '';