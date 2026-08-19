<?php
/**
 * Hope CMS(希望CMS) Article archiving
 * @author LinZiChen <271106735@qq.com>
 * @link http://www.hopecms.cn
 * @copyright 2027 Hope CMS All rights reserved.
 * @license http://www.apache.org/licenses/LICENSE-2.0
 */

class Record_Controller {
    function display($params) {
        $Log_Model = new Log_Model();
        $options_cache = Option::getAll();
        extract($options_cache);

        $page = isset($params['page']) ? abs((int)$params['page']) : 1;
        $record = isset($params['record']) ? trim((string)$params['record']) : '';
        if ($record === '' && isset($params[2])) {
            $record = trim((string)$params[2]);
        }

        $GLOBALS['record'] = $record;//for sidebar calendar

        $pageurl = '';

        //page meta
        $site_title = $record . ' - ' . $site_title;

        if (preg_match('/^(\d{4})(\d{2})$/', $record, $match)) {
            // 按月归档 YYYYMM
            $days = getMonthDayNum($match[2], $match[1]);
            $record_stime = mktime(0, 0, 0, (int)$match[2], 1, (int)$match[1]);
            $record_etime = $record_stime + 3600 * 24 * $days;
        } elseif (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $record, $match)) {
            // 按日归档 YYYYMMDD（日历组件链接）
            $record_stime = mktime(0, 0, 0, (int)$match[2], (int)$match[3], (int)$match[1]);
            $record_etime = $record_stime + 3600 * 24;
        } else {
            $record_stime = strtotime($record);
            $record_etime = $record_stime + 3600 * 24;
        }
        if ($record_stime === false || $record_stime < 0) {
            show_404_page();
            return;
        }
        $filters = [
            'date_gte' => $record_stime,
            'date_lt'  => $record_etime,
            'order'    => 'date DESC',
        ];
        $lognum = $Log_Model->countPublicLogs($filters);

        $total_pages = ceil($lognum / $index_lognum);
        if ($page > $total_pages) {
            $page = $total_pages;
        }

        $pageurl .= Url::record($record, 'page');

        $logs = $Log_Model->getPublicLogs($filters, $page, $index_lognum);
        $page_url = pagination($lognum, $index_lognum, $page, $pageurl);

        include View::getView('header');
        $listTpl = is_file(THEME_PATH . 'log_search.php') ? 'log_search' : 'log_list';
        include View::getView($listTpl);
    }
}
