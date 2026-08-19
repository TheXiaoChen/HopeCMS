<?php
/**
 * Hope CMS(希望CMS) article
 * @author 林子辰
 * @link http://www.hopecms.cn
 */
 defined('HOPE_ROOT') || exit('access denied!');
$hpPage = new Log_Model();

if (isset($_GET['save']) && !empty($_POST['title'])) {
    $title = Input::postStrVar('title');
    $content = Input::postStrVar('content');
    $alias = Input::postStrVar('alias');
    $pageId = Input::postIntVar('pageid');
    $ishide = Input::postStrVar('ishide');
    $template = Input::postStrVar('template');
    $allow_remark = Input::postStrVar('allow_remark', 'n');
    $home = Input::postStrVar('home_page');
    $cover = Input::postStrVar('cover');
    $act = Input::postStrVar('json', 'save');

    $postTime = time();
    LoginAuth::checkToken();
    if (!empty($alias)) {
        $logalias_cache = $CACHE->readCache('logalias');
        $alias = $hpPage->checkAlias($alias, $logalias_cache, $pageId);
    }

    $logData = array(
        'title'        => $title,
        'content'      => $content,
        'excerpt'      => '',
        'date'         => $postTime,
        'allow_remark' => $allow_remark,
        'hide'         => $ishide,
        'alias'        => $alias,
        'type'         => 'page',
        'template'     => $template,
        'cover'        => $cover,
    );

    $directUrl = '';
    if ($pageId > 0) {
        $hpPage->updateLog($logData, $pageId);
        $directUrl = SELF."?act=page&code=hide_n";
    } else {
        $pageId = $hpPage->addlog($logData);
        $directUrl = SELF."?act=page&code=pub";
    }

    if ($home === 'y') {
        Option::updateOption('home_page_id', $pageId);
        $hpPage->hideSwitch($pageId, 'n');
    } elseif (Option::get('home_page_id') == $pageId) {
        Option::updateOption('home_page_id', 0);
    }

    $CACHE->updateCache(array('options', 'logalias'));
    doAction('save_log', $pageId);
    switch ($act) {
        case 'autosave':
            echo "autosave_gid:{$pageId}_df:0_";
            break;
        case 'save':
            Gohref($directUrl);
            break;
    }
}

if (isset($_POST['operate']) && isset($_POST['id'])) {
    $operate = $_POST['operate'] ?? '';
    $pages = Input::postIntArray('id');
    LoginAuth::checkToken();
    switch ($operate) {
        case 'delete':
            $home_page_id = Option::get('home_page_id');
            foreach ($pages as $value) {
                $hpPage->deleteLog($value);
                doAction('del_log', $value);
                // 如果被删除的页面是首页，需要恢复默认首页
                if ($home_page_id == $value) {
                    Option::updateOption('home_page_id', 0);
                }
            }
            $CACHE->updateCache(array('options', 'sta', 'comment', 'logalias'));
            Gohref(SELF."?act=page&code=del");
            break;
        case 'hide':
        case 'pub':
            $ishide = $operate == 'hide' ? 'y' : 'n';
            foreach ($pages as $value) {
                $hpPage->hideSwitch($value, $ishide);
            }
            $CACHE->updateCache(array('options', 'sta', 'comment'));
            Gohref(SELF."?act=page&code=hide_" . $ishide);
            break;
    }
}

$Theme_Model = new Theme_Model();
$pageThemes = $Theme_Model->getCustomThemes('page');
$pageThemeLabels = [];
if (is_array($pageThemes)) {
    foreach ($pageThemes as $pt) {
        $key = (string)($pt['filename'] ?? '');
        if ($key === '') {
            continue;
        }
        $pageThemeLabels[$key] = (string)($pt['comment'] ?? $key);
    }
}
if (!isset($pageThemeLabels['page'])) {
    $pageThemeLabels['page'] = '默认页面';
}
$page = Input::getIntVar('page', 1);
$keyword = Input::getStrVar('keyword');
$orderParam = Input::getStrVar('order');
$perpage_num = Input::getStrVar('perpage_num');

$filters = [];
if ($keyword) {
    $filters['keyword'] = $keyword;
}

$orderBy = 'date DESC';
switch ($orderParam) {
    case 'view':
        $orderBy = 'views DESC';
        break;
    case 'comm':
        $orderBy = 'comnum DESC';
        break;
}
$filters['order'] = $orderBy;

if ($perpage_num > 0) {
    $perPage = $perpage_num;
    Option::updateOption('admin_lognum', $perpage_num);
    $CACHE->updateCache('options');
} else {
    $admin_lognum = Option::get('admin_lognum');
    $perPage = $admin_lognum ? $admin_lognum : 20;
}
$pages = $hpPage->getArticleList($filters, '', $page, 'page', $perPage);
$pageNum = $hpPage->getLogNum('', $filters, 'page', 1);
$pageurl = pagination($pageNum, $perPage, $page, SELF."?act=page&page=");
