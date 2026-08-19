<?php
/**
 * Hope CMS(希望CMS) category
 * @author 林子辰
 * @link http://www.hopecms.cn
 */
defined('HOPE_ROOT') || exit('access denied!');

$Tag_Model = new Tag_Model();

if (isset($_GET['update'])) {
    $tagName = Input::postStrVar('tagname');
    $tagId = Input::postIntVar('tid');
    $title = Input::postStrVar('title');
    $keywords = Input::postStrVar('keywords');
    $description = Input::postStrVar('description');
    if (empty($tagName)) {
        Gohref(SELF."?act=tag&code=empty");
    }
    if ($tagId > 0) {
        $Tag_Model->updateTagName($tagId, $tagName, $title, $keywords, $description);
    }else{
        $existingTagId = $Tag_Model->getIdFromName($tagName);
        if ($existingTagId) {
            Gohref(SELF."?act=tag&code=exist");
        }
        $tagId = $Tag_Model->createTag($tagName);
        if ($tagId) {
            $Tag_Model->updateTagName($tagId, $tagName, $title, $keywords, $description);
        }
    }
    // 同步刷新菜单：关联了该标签的菜单项标题随 tagname 更新
    $CACHE->updateCache(['tags', 'menu']);
    Gohref(SELF."?act=tag&code=edit");
}

if (isset($_GET['del'])) {
    $tid = Input::getStrVar('tid');
    $tids = array_filter(array_map('intval', explode(',', $tid)));
    LoginAuth::checkToken();
    if (empty($tids)) {
        Gohref(SELF."?act=tag&code=empty");
    }
    foreach ($tids as $id) {
        $Tag_Model->deleteTag($id);
    }
    $CACHE->updateCache(['tags', 'menu']);
    Gohref(SELF."?act=tag&code=del");
}

$page_count = 100;
$page = Input::getIntVar('page', 1);
$keyword = Input::getStrVar('keyword');

$tags = $Tag_Model->getTags($keyword, $page_count, $page);
$tags_count = $Tag_Model->getTagsCount($keyword);
$pageQuery = SELF . '?act=tag' . ($keyword !== '' ? '&keyword=' . rawurlencode($keyword) : '') . '&page=';
$pageurl = pagination($tags_count, $page_count, $page, $pageQuery);
