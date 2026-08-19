<?php
/**
 * Hope CMS(希望CMS) search
 * @author LinZiChen <271106735@qq.com>
 * @link http://www.hopecms.cn
 * @copyright 2027 Hope CMS All rights reserved.
 * @license http://www.apache.org/licenses/LICENSE-2.0
 */

class Search_Controller {
    function display($params) {
        $Log_Model = new Log_Model();
        $options_cache = Option::getAll();
        extract($options_cache);

        $page = isset($params['page']) ? abs((int)$params['page']) : 1;
        $keyword = trim((string)($params['keyword'] ?? ''));
        if ($keyword !== '') {
            $keyword = htmlspecialchars(urldecode($keyword), ENT_QUOTES, 'UTF-8');
        }

        $pageurl = '';

        $filters = [
            'keyword' => $keyword,
            'order'   => 'date DESC',
        ];
        $lognum = $Log_Model->countPublicLogs($filters);
        $total_pages = (int)ceil($lognum / max(1, (int)$index_lognum));
        if ($total_pages < 1) {
            $total_pages = 1;
        }
        if ($page > $total_pages) {
            $page = $total_pages;
        }
        if ($page < 1) {
            $page = 1;
        }

        $pageurl .= SITE_URL . '?keyword=' . urlencode($keyword) . '&page=';

        $logs = $Log_Model->getPublicLogs($filters, $page, $index_lognum);
        $page_url = pagination($lognum, $index_lognum, $page, $pageurl);

        include View::getView('header');
        $listTpl = is_file(THEME_PATH . 'log_search.php') ? 'log_search' : 'log_list';
        include View::getView($listTpl);
    }
}
