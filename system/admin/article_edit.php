<?php
/**
 * Hope CMS(希望CMS) article_edit
 * @author 林子辰
 * @link http://www.hopecms.cn
 */
 defined('HOPE_ROOT') || exit('access denied!');

 $Log_Model = new Log_Model();
 $Tag_Model = new Tag_Model();

// 返回当前保存的预设字段
if (isset($_GET['get_blog_fields'])) {
    $val = Option::get('blog_field');
    $arr = @unserialize($val);
    if (!is_array($arr)) {
        $arr = [];
    }
    $arr = array_map('hope_normalize_blog_field_preset', $arr);
    Output::ok(array_values($arr));
}

// 保存文章预设字段（AJAX）
if (isset($_GET['save_blog_fields'])) {
    $data_raw = $_POST['data'] ?? '';
    $arr = @json_decode($data_raw, true);
    if (!is_array($arr)) {
        Output::error('invalid_data');
    }
    $normalized = [];
    foreach ($arr as $item) {
        $item = hope_normalize_blog_field_preset($item);
        if ($item['key'] === '') {
            continue;
        }
        $normalized[] = $item;
    }
    Option::updateOption('blog_field', serialize($normalized));
    $CACHE->updateCache('options');
    Output::ok($normalized);
}

 if($_POST && isset($_POST['gid'])){
    $title = Input::postStrVar('title');
    $sort = Input::postIntVar('sort', -1);
    $tagstring = isset($_POST['tag']) ? strip_tags(addslashes(trim($_POST['tag']))) : '';
    $content = Input::postStrVar('logcontent');
    $excerpt = Input::postStrVar('logexcerpt');
    $cover = Input::postStrVar('cover');
    $alias = Input::postStrVar('alias');
    $password = Input::postStrVar('password');
    $author = Input::postIntVar('author');
    $date = isset($_POST['date']) ? strtotime(trim($_POST['date'])) : time();
    $hide = Input::postStrVar('hide', 'y');
    $remark = Input::postStrVar('remark', 'n');
    $top = Input::postStrVar('top', 'n');
    $sortop = Input::postStrVar('sortop', 'n');
    $blogid = Input::postIntVar('gid', -1); //自动保存为草稿的文章id
    $field_keys = isset($_POST['field_key']) && is_array($_POST['field_key']) ? $_POST['field_key'] : [];
    $field_values = isset($_POST['field_value']) && is_array($_POST['field_value']) ? $_POST['field_value'] : [];
    $fields = hope_collect_article_fields($field_keys, $field_values);
    LoginAuth::checkToken();
    
    if (!empty($alias)) {
        $logalias_cache = $CACHE->readCache('logalias');
        $alias = $Log_Model->checkAlias($alias, $logalias_cache, $blogid);
    }
    
    //管理员发文不审核,注册用户受开关控制
    //$checked = Option::get('ischkarticle') == 'y' && !User::haveEditPermission() ? 'n' : 'y';
    $checked = 'y';
    $logData = [
        'title'        => $title,
        'alias'        => $alias,
        'content'      => $content,
        'excerpt'      => $excerpt,
        'cover'        => $cover,
        'author'       => $author,
        'sortid'       => $sort,
        'date'         => $date,
        'top'          => $top,
        'sortop'       => $sortop,
        'allow_remark' => $remark,
        'hide'         => $hide,
        'checked'      => $checked,
        'password'     => $password,
        'fields'       => serialize($fields)
    ];
    if (User::isWriter()) {
        $count = $Log_Model->getPostCountByUid(UID, time() - 3600 * 24);
        $post_per_day = Option::get('posts_per_day');
        if ($count >= $post_per_day) {
            Gohref(SELF."?act=article&code=error_post_per_day");
        }
    }
    
    if ($blogid > 0) {
        $Log_Model->updateLog($logData, $blogid);
        $Tag_Model->updateTag($tagstring, $blogid);
    } else {
        $blogid = $Log_Model->addlog($logData);
        $Tag_Model->addTag($tagstring, $blogid);
    }
    
    $CACHE->updateArticleCache();
    
    doAction('save_log', $blogid);
    
    if (isset($_GET['save'])) {
        Output::ok([
            'id'  => (int)$blogid,
            'url' => Url::log($blogid),
        ]);
    }
    
    if (isset($_POST['pubPost'])) {
        if ($checked === 'n') {
            notice::sendNewPostMail($title, $blogid);
        }
        Gohref(SELF."?act=article&code=post");
    }

    Gohref(SELF ."?act=article&code=savelog");
}else{
    $logid = Input::getIntVar('gid',-1);
    $UploadSort_Model = new UploadSort_Model();
    $mediaSorts = $UploadSort_Model->getSorts();
    if($logid > 0){
        $Log_Model->checkEditable($logid);
        $blogData = $Log_Model->getOneLogForAdmin($logid);
        extract($blogData);
        $pagetitle = '编辑文章';
        $date = date('Y-m-d\TH:i', $date);
        $sorts = $CACHE->readCache('sort');
        
        $tags = [];
        foreach ($Tag_Model->getTag($logid) as $val) {
            $tags[] = $val['tagname'];
        }
        $tagStr = implode(',', $tags);
    
        //media
        $Upload_Model = new Upload_Model();
        $medias = $Upload_Model->getMedias();
    
        $is_hide = $hide == 'n' ? 'checked' : '';
        $is_remark = $allow_remark == 'y' ? 'checked' : '';
        $is_top = $top == 'y' ? 'checked' : '';
        $is_sortop = $sortop == 'y' ? 'checked' : '';
    }else{
        $blogData = [
            'logid'    => -1,
            'title'    => '',
            'content'  => '',
            'excerpt'  => '',
            'alias'    => '',
            'sortid'   => -1,
            'type'     => 'blog',
            'password' => '',
            'hide'     => 'y',
            'author'   => UID,
            'cover'    => '',
            'link'     => '',
        ];
        
        extract($blogData);
        
        $isdraft = false;
        $pagetitle = '写文章';
        $orig_date = '';
        $sorts = $CACHE->readCache('sort');
        $tagStr = '';
        $tags = $Tag_Model->getTags();
        $is_hide = 'checked';
        $is_remark = 'checked';
        $is_top = '';
        $is_sortop = '';
        $date = date('Y-m-d\TH:i');
    }

    require_once HOPE_ROOT . 'system/app/service/ai_service.php';
    $conf_ai_enabled = hope_ai_is_ready();
    $ai_chat_models = hope_ai_get_chat_models();
    $ai_default_chat_model = hope_ai_get_default_chat_model();

}
