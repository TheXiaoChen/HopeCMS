<?php
/**
 * Hope CMS(希望CMS) article
 * @author 林子辰
 * @link http://www.hopecms.cn
 */
defined('HOPE_ROOT') || exit('access denied!');

$User_Model = new User_Model();

if (isset($_GET['save']) && $_POST) {
    $uid = Input::postIntVar('uid');
    $email = Input::postStrVar('email');
    $role = Input::postStrVar('role',User::ROLE_WRITER);
    $username = Input::postStrVar('username');
    $nickname = Input::postStrVar('nickname');
    $description = Input::postStrVar('description');
    $password = Input::postStrVar('password');
    $password2 = Input::postStrVar('password2');
    if($uid == 0){
        if ($email == '') Gohref(SELF."?act=user&code=email");
        if ($User_Model->isMailExist($email)) Gohref(SELF."?act=user&code=exist_email");
        if (strlen($password) < 6) Gohref(SELF."?act=user&code=pwd_len");
        if ($password != $password2) Gohref(SELF."?act=user&code=pwd2");

        $PHPASS = new PasswordHash(8, true);
        $password = $PHPASS->HashPassword($password);

        $newUid = $User_Model->addUser('', $email, $password, $role);
        $CACHE->updateCache(array('sta', 'user'));
        doAction(HopeHooks::ADD_USER, $newUid, [
            'email'    => $email,
            'role'     => $role,
            'username' => '',
        ]);
        Gohref(SELF."?act=user&code=add");

    }else{
        //创始人账户不能被他人编辑
        if (!User::isFounder() && $uid === 1) Gohref(SELF."?act=user&code=del_b");

        if (empty($nickname)) Gohref(SELF."?act=user&code=nickname");
        if (empty($email) && empty($username)) Gohref(SELF."?act=user&code=email");
        if ($User_Model->isMailExist($email, $uid)) Gohref(SELF."?act=user&code=exist_email");
        if ($User_Model->isUserExist($username, $uid)) Gohref(SELF."?act=user&code=exist");
        if (strlen($password) > 0 && strlen($password) < 6) Gohref(SELF."?act=user&code=pwd_len");
        if ($password != $password2) Gohref(SELF."?act=user&code=pwd2");

        $balance = round((float)Input::postStrVar('balance', '0'), 2);
        if ($balance < 0) {
            $balance = 0;
        }

        $userData = [
            'username'    => $username,
            'nickname'    => $nickname,
            'email'       => $email,
            'description' => $description,
            'balance'     => $balance,
        ];
        // 创始人账户（UID=1）禁止修改用户角色
        if ($uid !== 1) {
            $userData['role'] = $role;
        }

        if (!empty($password)) {
            $PHPASS = new PasswordHash(8, true);
            $password = $PHPASS->HashPassword($password);
            $userData['password'] = $password;
        }

        $User_Model->updateUser($userData, $uid);
        $CACHE->updateCache('user');
        doAction(HopeHooks::UPDATE_USER, $uid, $userData);
        Gohref(SELF."?act=user&code=update");

    }
}


if (isset($_GET['del'])) {
    LoginAuth::checkToken();
    $uid = Input::getIntVar('uid');
    if (UID == $uid) Gohref(SELF."?act=user");

    //创始人账户不能被删除
    if ($uid == 1) Gohref(SELF."?act=user&code=del_a");

    $User_Model->deleteUser($uid);
    $CACHE->updateCache(array('sta', 'user'));
    doAction(HopeHooks::DEL_USER, $uid);
    Gohref(SELF."?act=user&code=del");
}

if (isset($_GET['forbid'])) {
    LoginAuth::checkToken();
    $uid = Input::getIntVar('uid');

    if (UID == $uid) Gohref(SELF."?act=user");

    //创始人账户不能被禁用
    if ($uid == 1) Gohref(SELF."?act=user");

    $User_Model->forbidUser($uid);
    doAction(HopeHooks::FORBID_USER, $uid);
    Gohref(SELF."?act=user&code=fb");
}

if (isset($_GET['unforbid'])) {
    LoginAuth::checkToken();
    $uid = Input::getIntVar('uid');

    $User_Model->unforbidUser($uid);
    doAction(HopeHooks::UNFORBID_USER, $uid);
    Gohref(SELF."?act=user&code=unfb");
}


if(isset($_POST['operate']) && !empty($_POST['id'])){
    $operate = Input::postStrVar('operate');
    $user_id = Input::postIntArray('id');
    
    if (!$operate || empty($user_id)) {
        Gohref(SELF."?act=user");
    }

    if ($operate == 'delete') {
        foreach ($user_id as $id) {
            if ($id == 1) {
                continue;
            }
            $User_Model->deleteUser($id);
            doAction(HopeHooks::DEL_USER, $id);
        }
        $CACHE->updateCache(array('sta', 'user'));
        Gohref(SELF."?act=user");
    }
    if ($operate == 'forbid') {
        foreach ($user_id as $id) {
            if ($id == 1) {
                continue;
            }
            $User_Model->forbidUser($id);
            doAction(HopeHooks::FORBID_USER, $id);
        }
        Gohref(SELF."?act=user");
    }

    if ($operate == 'unforbid') {
        foreach ($user_id as $id) {
            if ($id == 1) {
                continue;
            }
            $User_Model->unforbidUser($id);
            doAction(HopeHooks::UNFORBID_USER, $id);
        }
        Gohref(SELF."?act=user");
    }
}


$page = Input::getIntVar('page', 1);
$perpage_num = Input::getStrVar('perpage_num');
$keyword = Input::getStrVar('keyword');
$order = Input::getStrVar('order');

$email = $nickname = '';
if (filter_var($keyword, FILTER_VALIDATE_EMAIL)) {
    $email = $keyword;
} else {
    $nickname = $keyword;
}

if ($perpage_num > 0) {
    $perPage = $perpage_num;
    Option::updateOption('admin_perpage_num', $perpage_num);
    $CACHE->updateCache('options');
} else {
    $admin_perpage_num = Option::get('admin_perpage_num');
    $perPage = $admin_perpage_num ? $admin_perpage_num : 20;
}

$users = $User_Model->getUsers($email, $nickname, $order, $page, $perPage);
$userCount = $User_Model->getUserCount($email, $nickname);
$subPage = '';
foreach ($_GET as $key => $val) {
    if (in_array($key, ['keyword', 'order', 'perpage_num'])) {
        $subPage .= "&$key=$val";
    }
}
$pageurl = pagination($userCount, $perPage, $page, SELF."?act=user&{$subPage}page=");
