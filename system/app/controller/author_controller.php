<?php
/**
 * Hope CMS(希望CMS) author's articles
 * @author LinZiChen <271106735@qq.com>
 * @link http://www.hopecms.cn
 * @copyright 2027 Hope CMS All rights reserved.
 * @license http://www.apache.org/licenses/LICENSE-2.0
 */

class Author_Controller {
    function display($params) {
        $Log_Model = new Log_Model();
        $User_Model = new User_Model();

        $CACHE = Cache::getInstance();
        $options_cache = Option::getAll();
        extract($options_cache);

        $page = isset($params['page']) ? abs((int)$params['page']) : 1;
        $author = (int)($params['author'] ?? 0);

        $pageurl = '';

        $user_info = $User_Model->getOneUser($author);
        if (empty($user_info)) {
            show_404_page();
        }
        $author_name = $user_info['nickname'];

        //page meta
        $site_title = $author_name . ' - ' . $site_title;

        $filters = [
            'author' => (int)$author,
            'order'  => 'date DESC',
        ];
        $sta_cache = $CACHE->readCache('sta');
        $lognum = $sta_cache[$author]['lognum'];

        $total_pages = ceil($lognum / $index_lognum);
        if ($page > $total_pages) {
            $page = $total_pages;
        }
        $pageurl .= Url::author($author, 'page');

        $logs = $Log_Model->getPublicLogs($filters, $page, $index_lognum);
        $page_url = pagination($lognum, $index_lognum, $page, $pageurl);

        include View::getView('header');
        $listTpl = is_file(THEME_PATH . 'log_search.php') ? 'log_search' : 'log_list';
        include View::getView($listTpl);
    }
}
