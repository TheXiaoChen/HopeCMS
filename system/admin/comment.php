<?php
/**
 * Hope CMS(希望CMS) category
 * @author 林子辰
 * @link http://www.hopecms.cn
 */
defined('HOPE_ROOT') || exit('access denied!');

$Comment_Model = new Comment_Model();

if (isset($_GET['del'])) {
    $id = Input::getIntVar('id');
    LoginAuth::checkToken();
    $Comment_Model->delComment($id);
    $CACHE->updateCache(array('sta', 'comment'));
    Gohref(SELF."?act=comment&code=del");
}

if (isset($_GET['delbyip'])) {
    LoginAuth::checkToken();
    if (!User::haveEditPermission()) {
        hpMsg('权限不足！', './');
    }
    $ip = $_GET['ip'] ? addslashes($_GET['ip']) : '';
    $Comment_Model->delCommentByIp($ip);
    $CACHE->updateCache(array('sta', 'comment'));
    Gohref(SELF."?act=comment&code=del");
}

if (isset($_POST['operate']) && isset($_POST['id'])) {
    $operate = Input::postStrVar('operate');
    $comments = Input::postIntArray('id');

    switch ($operate) {
        case 'delete' :
            $Comment_Model->batchComment('delcom', $comments);
            $CACHE->updateCache(array('sta', 'comment'));
            Gohref(SELF."?act=comment&code=del");
            break;
        case 'hide':
            $Comment_Model->batchComment('hidecom', $comments);
            $CACHE->updateCache(array('sta', 'comment'));
            Gohref(SELF."?act=comment&code=hide");
            break;
        case 'pub':
            $Comment_Model->batchComment('showcom', $comments);
            $CACHE->updateCache(array('sta', 'comment'));
            Gohref(SELF."?act=comment&code=show");
            break;
        case 'top':
            $Comment_Model->batchComment('top', $comments);
            Gohref(SELF."?act=comment&code=top");
            break;
        case 'untop':
            $Comment_Model->batchComment('untop', $comments);
            Gohref(SELF."?act=comment&code=untop");
            break;
        default:
            Gohref(SELF."?act=comment&code=b");
    }
}

if (isset($_GET['pub'])) {
    LoginAuth::checkToken();
    if (!User::haveEditPermission()) {
        hpMsg('权限不足！', './');
    }

    $id = Input::getIntVar('id');

    $Comment_Model->showComment($id);
    $CACHE->updateCache(array('sta', 'comment'));
    Gohref(SELF."?act=comment&code=show");
}

if (isset($_GET['reply'])) {
    $reply = Input::postStrVar('reply');
    $commentId = Input::postIntVar('cid');
    $hide = Input::postStrVar('hide', 'n');

    if (empty($reply)) {
         (SELF."?act=comment&code=error_c");
    }

    //回复一条待审核的评论，视为要将其公开（包括回复内容）
    if ($hide == 'y') {
        $Comment_Model->showComment($commentId);
        $hide = 'n';
    }

    $comment = $Comment_Model->getOneComment($commentId);
    $blogId = (int)($comment['gid'] ?? null);
    $content = '@' . addslashes($comment['poster']) . '：' . $reply;

    $Comment_Model->replyComment($blogId, $commentId, $content, $hide);
    notice::sendNewCommentMail($reply, $blogId, $commentId);

    $CACHE->updateCache('comment');
    $CACHE->updateCache('sta');
    doAction('comment_reply', $commentId, $reply);
    Gohref(SELF."?act=comment&active_rep");
}


$blogId = Input::getIntVar('gid');
$uid = Input::getIntVar('uid');
$hide = Input::getStrVar('hide');
$page = Input::getIntVar('page', 1);
$perpage_num = Input::getStrVar('perpage_num');

$addUrl_0 = $uid ? "uid=$uid&" : '';
$addUrl_1 = $blogId ? "gid=$blogId&" : '';
$addUrl_2 = $hide ? "hide=$hide&" : '';
$addUrl = $addUrl_0 . $addUrl_1 . $addUrl_2;
if ($perpage_num > 0) {
    $perPage = $perpage_num;
    Option::updateOption('admin_perpage_num', $perpage_num);
    $CACHE->updateCache('options');
} else {
    $admin_lognum = Option::get('admin_perpage_num');
    $perPage = $admin_lognum ? $admin_lognum : 20;
}
$comment = $Comment_Model->getCommentsForAdmin($blogId, $uid, $hide, $page, $perPage);
$cmnum = $Comment_Model->getCommentNum($blogId, $uid, $hide);
$hideCommNum = $Comment_Model->getCommentNum($blogId, $uid, 'y');
$pageurl = pagination($cmnum, $perPage, $page, SELF."?act=comment&{$addUrl}page=");