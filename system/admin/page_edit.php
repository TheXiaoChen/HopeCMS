<?php
/**
 * Hope CMS(希望CMS) 
 * @author 林子辰
 * @link http://www.hopecms.cn
 */
 defined('HOPE_ROOT') || exit('access denied!');

 $Log_Model = new Log_Model();

 if($_POST && isset($_POST['gid'])){ //保存
    $title = Input::postStrVar('title');
    $content = Input::postStrVar('logcontent');
    $cover = Input::postStrVar('cover');
    $template = Input::postStrVar('template');
    $alias = Input::postStrVar('alias');
    $remark = Input::postStrVar('remark', 'n');
    $home = Input::postStrVar('home_page', 'n');
    $blogid = Input::postIntVar('gid', -1); //自动保存为草稿的文章id
    LoginAuth::checkToken();

    if (!empty($alias)) {
        $logalias_cache = $CACHE->readCache('logalias');
        $alias = $Log_Model->checkAlias($alias, $logalias_cache, $blogid);
    }

    $checked = 'y';
    $logData = [
        'title'        => $title,
        'alias'        => $alias,
        'content'      => $content,
        'cover'        => $cover,
        'template'     => $template,
        'date'         => time(),
        'allow_remark' => $remark,
        'checked'      => $checked,
    ];
    if ($home === 'y') {
        $logData['hide'] = 'n';
    }
    
    if ($blogid > 0) {
        $Log_Model->updateLog($logData, $blogid);
    } else {
        $logData['type'] = 'page';
        $logData['hide'] = isset($_GET['save']) ? 'y' : 'n';
        $blogid = $Log_Model->addlog($logData);
    }
    
    if ($home === 'y') {
        Option::updateOption('home_page_id', $blogid);
        $updateHomeOption = true;
    } elseif (Option::get('home_page_id') == $blogid) {
        Option::updateOption('home_page_id', 0);
        $updateHomeOption = true;
    }

    $CACHE->updateArticleCache();
    if (!empty($updateHomeOption)) {
        $CACHE->updateCache('options');
    }
    
    doAction('save_log', $blogid);
    
    // 异步保存
    if (isset($_GET['save'])) {
        Output::ok([
            'id'  => (int)$blogid,
            'url' => Url::log($blogid),
        ]);
    }
    
    
    // 文章（草稿）公开发布
    if (isset($_POST['pubPost'])) {
        if ($checked === 'n') {
            notice::sendNewPostMail($title, $blogid);
        }
        Gohref(SELF . "?act=page&code=post");
    }
    
    // 编辑文章（保存并返回）
    Gohref(SELF ."?act=page&code=savelog");
    
    
    
}else{//读取


    $pageModel = new Log_Model();

    $Theme_Model = new Theme_Model();
    $customThemes = $Theme_Model->getCustomThemes('page');
    $pageId = Input::getIntVar('sid', -1);
    if ($pageId > 0) {
        $pageData = $pageModel->getOneLogForAdmin($pageId);
        extract($pageData);
        $pagetitle = '编辑页面';
        $is_comment = $allow_remark == 'y' ? 'checked="checked"' : '';
        $is_home = Option::get('home_page_id') == $pageId ? 'checked="checked"' : '';
    } else {
        $pagetitle = '新建页面';
        $title = '';
        $content = '';
        $alias = '';
        $cover = '';
        $template = 'page';
        $is_comment = 'checked="checked"';
        $is_home = '';
    }

    //media
    $Upload_Model = new Upload_Model();
    $medias = $Upload_Model->getMedias();

    $UploadSort_Model = new UploadSort_Model();
    $mediaSorts = $UploadSort_Model->getSorts();
}
