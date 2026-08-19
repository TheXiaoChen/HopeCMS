<?php
/**
 * Hope CMS(希望CMS) tags
 * @author LinZiChen <271106735@qq.com>
 * @link http://www.hopecms.cn
 * @copyright 2027 Hope CMS All rights reserved.
 * @license http://www.apache.org/licenses/LICENSE-2.0
 */

class Tag_Controller {
    function display($params) {
        $Log_Model = new Log_Model();
        $options_cache = Option::getAll();
        extract($options_cache);

        $page = isset($params['page']) ? abs((int)$params['page']) : 1;
        $tag = isset($params['tag']) ? urldecode(trim($params['tag'])) : '';

        $pageurl = '';

        //page meta
        $site_title = htmlspecialchars(stripslashes($tag), ENT_QUOTES, 'UTF-8') . ' - ' . $site_title;

        $Tag_Model = new Tag_Model();

        if (!$Tag_Model->getIdFromName($tag)) {
            show_404_page();
        }

        $lognum = 0;
        $logs = [];
        $blogIdStr = $Tag_Model->getTagByName($tag);
        if ($blogIdStr) {
            $filters = [
                'gid_in' => array_filter(array_map('intval', explode(',', $blogIdStr))),
                'order'  => 'date DESC',
            ];
            $lognum = $Log_Model->countPublicLogs($filters);
            $logs = $Log_Model->getPublicLogs($filters, $page, $index_lognum);
        }

        $total_pages = ceil($lognum / $index_lognum);
        if ($page > $total_pages) {
            $page = $total_pages;
        }
        $pageurl .= Url::tag(urlencode($tag), 'page');
        $page_url = pagination($lognum, $index_lognum, $page, $pageurl);

        include View::getView('header');
        $listTpl = is_file(THEME_PATH . 'log_search.php') ? 'log_search' : 'log_list';
        include View::getView($listTpl);
    }
}
