<?php
/**
 * Hope CMS(希望CMS) reg
 * @author 林子辰
 * @link http://www.hopecms.cn
 */
defined('HOPE_ROOT') || exit('access denied!');

if(Option::get('is_signup') !== 'y'){
    hpMsg('系统已关闭注册！');
}
if($do==1 && $_SERVER['REQUEST_METHOD'] == 'POST'){
    $mail = Input::postStrVar('mail');
    $passwd = Input::postStrVar('passwd');
    $repasswd = Input::postStrVar('repasswd');
    $login_code = strtoupper(Input::postStrVar('login_code'));
    $mail_code = Input::postStrVar('mail_code');

    if(!checkMail($mail)) Gohref(SELF.'?act=reg&code=login');
    if(!User::checkLoginCode($login_code)) Gohref(SELF.'?act=reg&code=ckcode');
    if(Option::get('email_code') === 'y' && !User::checkMailCode($mail_code)) Gohref(SELF.'?act=reg&code=mail_code');
    if($User_Model->isMailExist($mail)) Gohref(SELF.'?act=reg&code=exist');
    if(strlen($passwd) < 6) Gohref(SELF.'?act=reg&code=pwd_len');
    if($passwd !== $repasswd) Gohref(SELF.'?act=reg&code=pwd2');

    $PHPASS = new PasswordHash(8, true);
    $passwd = $PHPASS->HashPassword($passwd);

    $User_Model->addUser('', $mail, $passwd, User::ROLE_WRITER);
    $uid = Database::getInstance()->insert_id();
    if ($uid > 0) {
        $lib = HOPE_ROOT . '/system/app/service/user_center.php';
        if (is_file($lib)) {
            require_once $lib;
            hope_user_center_ensure_meta($uid);
            $invite_code = Input::postStrVar('invite');
            hope_user_center_process_invite($uid, $invite_code);
        }
    }
    $CACHE->updateCache(['sta', 'user']);
    Gohref(SELF."?act=signin&code=reg");
}else{
    $login_code = Option::get('login_code') === 'y';
    $email_code = Option::get('email_code') === 'y';
    require_once View::getAdmView('reg');
    View::output();
}