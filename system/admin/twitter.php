<?php
/**
 * Hope CMS 微语管理
 */
defined('HOPE_ROOT') || exit('access denied!');

const TW_PAGE_COUNT = 20;

$Twitter_Model = new Twitter_Model();
$user_cache = $CACHE->readCache('user');

if (isset($_GET['post'])) {
    $content = Input::postStrVar('content');
    $private = Input::postStrVar('private', 'n') === 'y' ? 'y' : 'n';

    if ($content === '') {
        Gohref(SELF . '?act=twitter&code=empty');
    }

    $data = [
        'content' => $content,
        'private' => $private,
        'author'  => UID,
        'date'    => time(),
    ];
    $id = $Twitter_Model->addTwitter($data);
    $CACHE->updateCache('sta');
    doAction('post_note', $data, $id);
    Gohref(SELF . '?act=twitter&code=post');
}

if (isset($_GET['update'])) {
    $content = Input::postStrVar('content');
    $id = Input::postIntVar('id');
    $private = Input::postStrVar('private', 'n') === 'y' ? 'y' : 'n';

    if ($id <= 0 || $content === '') {
        Gohref(SELF . '?act=twitter&code=empty');
    }

    $row = $Twitter_Model->getTwitter($id);
    if (!$row) {
        hpMsg('微语不存在或无权操作', SELF . '?act=twitter');
    }
    if (!User::haveEditPermission() && (int)$row['author'] !== (int)UID) {
        hpMsg('权限不足', SELF . '?act=twitter');
    }

    $Twitter_Model->update([
        'content' => $content,
        'private' => $private,
    ], $id);
    $CACHE->updateCache('sta');
    Gohref(SELF . '?act=twitter&code=save');
}

if (isset($_GET['del'])) {
    LoginAuth::checkToken();
    $id = Input::getIntVar('id');
    if ($id > 0) {
        $Twitter_Model->delTwitter($id);
        $CACHE->updateCache('sta');
    }
    Gohref(SELF . '?act=twitter&code=del');
}

$page = Input::getIntVar('page', 1);
$viewAll = Input::getStrVar('all') === 'y' && User::isAdmin();
$uid = $viewAll ? 0 : UID;

$tws = $Twitter_Model->getTwitters($uid, $page, TW_PAGE_COUNT, true);
$twnum = $Twitter_Model->getCount($uid);

$subPage = $viewAll ? 'all=y&' : '';
$pageurl = pagination($twnum, TW_PAGE_COUNT, $page, SELF . "?act=twitter&{$subPage}page=");
