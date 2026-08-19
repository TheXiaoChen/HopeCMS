<?php
/**
 * Hope CMS(希望CMS) sort
 * @author LinZiChen <271106735@qq.com>
 * @link http://www.hopecms.cn
 * @copyright 2027 Hope CMS All rights reserved.
 * @license http://www.apache.org/licenses/LICENSE-2.0
 */

class Sort_Controller {
    function display($params) {
        $Log_Model = new Log_Model();
        $CACHE = Cache::getInstance();
        $options_cache = Option::getAll();
        extract($options_cache);

        $page = isset($params['page']) ? abs((int)$params['page']) : 1;

        $sortid = '';
        if (isset($params['id']) && is_numeric($params['id'])) {
            $sortid = (int)$params['id'];
        } elseif (isset($params['category'])) {
            if (is_numeric($params['category'])) {
                $sortid = (int)$params['category'];
            } else {
                $sort_cache = $CACHE->readCache('sort');
                $alias = addslashes(urldecode(trim($params['category'])));
                foreach ($sort_cache as $key => $value) {
                    if (isset($value['alias']) && $value['alias'] === $alias) {
                        $sortid = (int)$key;
                        break;
                    }
                }
            }
        }
        

        $pageurl = '';

        $sort_cache = $CACHE->readCache('sort');
        if (!isset($sort_cache[$sortid])) {
            show_404_page();
        }
        $sort = $sort_cache[$sortid];
        $sortName = $sort['sortname'];
        //page meta
        $site_title = $sortName . ' - ' . $site_title;
        if (!empty($sort['description'])) {
            $site_description = $sort['description'];
        }
        if (!empty($sort['keywords'])) {
            $site_key = $sort['keywords'];
        }
        if ($sort['pid'] != 0 || empty($sort['children'])) {
            $filters = ['sortid' => (int)$sortid];
        } else {
            $filters = ['sortids' => array_merge([(int)$sortid], $sort['children'])];
        }
        $filters['order'] = 'sortop DESC, date DESC';
        $lognum = $Log_Model->countPublicLogs($filters);
        $total_pages = (int)ceil($lognum / max(1, (int)$index_lognum));
        if ($total_pages < 1) {
            $total_pages = 1;
        }
        if ($page < 1) {
            $page = 1;
        }
        if ($page > $total_pages) {
            $page = $total_pages;
        }
        // 第二参数 'page'：保留 {%page%} 占位，供 pagination() 填充页码
        $pageurl .= Url::sort($sortid, 'page');

        $logs = $Log_Model->getPublicLogs($filters, $page, $index_lognum);
        $page_url = pagination($lognum, $index_lognum, $page, $pageurl);

        $template = !empty($sort['style']) && file_exists(THEME_PATH . $sort['style'] . '.php') ? $sort['style'] : 'log_list';

        include View::getView('header');
        include View::getView($template);
    }
}
