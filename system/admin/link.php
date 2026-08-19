<?php
/**
 * Hope CMS(希望CMS) 
 * @author 林子辰
 * @link http://www.hopecms.cn
 */
 defined('HOPE_ROOT') || exit('access denied!');

$Link_Model = new Link_Model();
$links = $Link_Model->getLinks();

if (isset($_GET['taxis'])) {
    LoginAuth::checkToken();
    $link = Input::postStrArray('link');
    
    if (empty($link) || !is_array($link)) {
        Output::error('没有可排序的链接');
    }

    foreach ($link as $key => $value) {
        $value = (int)$value;
        $key = (int)$key;
        $Link_Model->updateLink(array('taxis' => $key), $value);
    }
    $CACHE->updateCache('link');
    Output::ok();
}

if (isset($_GET['save'])) {
    $siteName = Input::postStrVar('sitename');
    $siteUrl = Input::postStrVar('siteurl');
    $icon = Input::postStrVar('icon');
    $description = Input::postStrVar('description');
    $linkId = Input::postIntVar('linkid');

    if ($siteName == '' || $siteUrl == '') {
        Gohref(SELF ."?act=link&code=empty");
    }

    if (!preg_match("/^http|ftp.+$/i", $siteUrl)) {
        $siteUrl = 'https://' . $siteUrl;
    }

    $data = [
        'sitename'    => $siteName,
        'siteurl'     => $siteUrl,
        'icon'        => $icon,
        'description' => $description
    ];

    if ($linkId) {
        $Link_Model->updateLink($data, $linkId);
    } else {
        $Link_Model->addLink($data);
    }

    $CACHE->updateCache('link');
    Gohref(SELF ."?act=link&code=save");
}

if (isset($_GET['del'])) {
    LoginAuth::checkToken();
    $linkId = Input::getIntVar('linkid');

    $Link_Model->deleteLink($linkId);
    $CACHE->updateCache('link');
    Gohref(SELF ."?act=link&code=del");
}

if (isset($_GET['hide'])) {
    $linkId = Input::getIntVar('linkid');
    $Link_Model->updateLink(['hide' => 'y'], $linkId);
    $CACHE->updateCache('link');
    Gohref(SELF ."?act=link");
}

if (isset($_GET['show'])) {
    $linkId = Input::getIntVar('linkid');
    $Link_Model->updateLink(['hide' => 'n'], $linkId);
    $CACHE->updateCache('link');
    Gohref(SELF ."?act=link");
}
