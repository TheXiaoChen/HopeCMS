<?php
/**
 * Hope CMS(希望CMS) article
 * @author 林子辰
 * @link http://www.hopecms.cn
 */
defined('HOPE_ROOT') || exit('access denied!');

$Log_Model = new Log_Model();
$Tag_Model = new Tag_Model();
$UploadSort_Model = new UploadSort_Model();
$Theme_Model = new Theme_Model();
if (isset($_POST['operate']) && isset($_POST['category'])) {
    $category = Input::postIntVar('category');
    $operate = Input::postStrVar('operate');
    $author = Input::postIntVar('author');
    $logs = Input::postIntArray('id');
    LoginAuth::checkToken();
    switch ($operate) {
        case 'delete':
            foreach ($logs as $val) {
                $Log_Model->deleteLog( $val);
                doAction('del_log', $val);
            }
            $CACHE->updateArticleCache();
            Gohref(SELF."?act=article&code=del");
            break;
        case 'draft':
            foreach ($logs as $val) {
                $Log_Model->hideSwitch($val, 'y');
            }
            $CACHE->updateArticleCache();
            Gohref(SELF."?act=article&code=draft");
            break;
        case 'publish':
            foreach ($logs as $val) {
                $Log_Model->hideSwitch($val, 'n');
            }
            $CACHE->updateArticleCache();
            Gohref(SELF."?act=article&code=post");
            break;
        case 'top':
            foreach ($logs as $val) {
                $Log_Model->updateLog(array('top' => 'y'), $val);
            }
            Gohref(SELF."?act=article&code=top&".json_encode($logs));
            break;
        case 'untop':
            foreach ($logs as $val) {
                $Log_Model->updateLog(array('top' => 'n'), $val);
            }
            Gohref(SELF."?act=article&code=untop");
            break;
        case 'move':
            foreach ($logs as $val) {
                $Log_Model->checkEditable($val);
                $Log_Model->updateLog(array('sortid' => $category), $val);
            }
            $CACHE->updateCache(array('sort', 'logsort'));
            Gohref(SELF."?act=article&code=move");
            break;
    }
}

if (isset($_GET['del'])) {
    $id = Input::getIntVar('id');
    LoginAuth::checkToken();
    if (isset($_GET['draft'])) {
        $Log_Model->hideSwitch($id, 'y');
    } else {
        $Log_Model->deleteLog($id);
        doAction('del_log', $id);
    }
    $CACHE->updateArticleCache();
    Gohref(SELF."?act=article&code=del&draft=$draft");
}

$tagId = Input::getIntVar('tagid');
$sid = Input::getIntVar('sid');
$uid = Input::getIntVar('uid');
$page = Input::getIntVar('page', 1);
$keyword = Input::getStrVar('keyword');
$hide = Input::getStrVar('hide');
$perpage_num = Input::getStrVar('perpage_num');

$sortView = (isset($_GET['sortView']) && $_GET['sortView'] == 'ASC') ? 'DESC' : 'ASC';
$sortComm = (isset($_GET['sortComm']) && $_GET['sortComm'] == 'ASC') ? 'DESC' : 'ASC';
$sortDate = (isset($_GET['sortDate']) && $_GET['sortDate'] == 'DESC') ? 'ASC' : 'DESC';

$filters = [];
if ($tagId) {
    $blogIdStr = $Tag_Model->getTagById($tagId) ?: '0';
    $filters['gid_in'] = array_filter(array_map('intval', explode(',', $blogIdStr)));
} elseif ($sid) {
    $filters['sortid'] = (int)$sid;
} elseif ($uid) {
    $filters['author'] = (int)$uid;
} elseif ($keyword) {
    $filters['keyword'] = $keyword;
}

$order = 'top DESC, sortop DESC, date DESC';
if (isset($_GET['sortView'])) {
    $order = "views $sortView";
} elseif (isset($_GET['sortComm'])) {
    $order = "comnum $sortComm";
} elseif (isset($_GET['sortDate'])) {
    $order = "date $sortDate";
}
$filters['order'] = $order;

if ($perpage_num > 0) {
    $perPage = $perpage_num;
    Option::updateOption('admin_lognum', $perpage_num);
    $CACHE->updateCache('options');
} else {
    $admin_lognum = Option::get('admin_lognum');
    $perPage = $admin_lognum ? $admin_lognum : 20;
}
$hide_state = $hide ? $hide : 'n';
$logNum = $Log_Model->getLogNum($hide_state, $filters, 'blog', 1);
$logs = $Log_Model->getArticleList($filters, $hide_state, $page, 'blog', $perPage);
$sorts = $CACHE->readCache('sort');
$tags = $Tag_Model->getTags();

$subPage = '';
foreach ($_GET as $key => $val) {
    $subPage .= $key != 'page' ? "&$key=$val" : '';
}
$pageurl = pagination($logNum,$perPage, $page, SELF."?act=article&{$subPage}&page=");