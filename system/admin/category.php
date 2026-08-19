<?php
/**
 * Hope CMS(希望CMS) category
 * @author 林子辰
 * @link http://www.hopecms.cn
 */
defined('HOPE_ROOT') || exit('access denied!');

$Sort_Model = new Sort_Model();

$sorts = $Sort_Model->getSorts();
$Theme_Model = new Theme_Model();
$sort_style = $Theme_Model->getCustomThemes('sort');
$log_style = $Theme_Model->getCustomThemes('log');

if (isset($_GET['save']) && isset($_POST)) {
    $sid = Input::postIntVar('sid');
    $sortname = Input::postStrVar('sortname');
    $alias = Input::postStrVar('alias');
    $pid = Input::postIntVar('pid');
    $style = Input::postStrVar('style','log_list');
    $logstyle = Input::postStrVar('logstyle','echo_log');
    $keywords = Input::postStrVar('keywords');
    $description = Input::postStrVar('description');

    if (empty($sortname)) {
        Gohref(SELF."?act=category&code=empty");
    }
    if ($sid && $sid == $pid) {
        Gohref(SELF."?act=category&code=oneself");
    }

    if (!empty($alias)) {
        if (!preg_match("|^[\w-]+$|", $alias)) {
            Gohref(SELF."?act=category&code=format");
        } elseif (preg_match("|^[0-9]+$|", $alias)) {
            Gohref(SELF."?act=category&code=format");
        } elseif (in_array($alias, array('post', 'record', 'sort', 'tag', 'author', 'page', 'posts'))) {
            Gohref(SELF."?act=category&code=system");
        } else {
            $sort_cache = $CACHE->readCache('sort');
            if ($sid) {
                unset($sort_cache[$sid]);
            }
            foreach ($sort_cache as $key => $value) {
                if ($alias == $value['alias']) {
                    Gohref(SELF."?act=category&code=repeat");
                }
            }
        }
    }

    $sort_data = [
        'sortname'    => $sortname,
        'pid'         => $pid,
        'style'       => $style,
        'keywords'    => $keywords,
        'description' => $description,
        'logstyle'    => $logstyle,
        'alias'       => $alias
    ];

    if ($sid) {
        $Sort_Model->updateSort($sort_data, $sid);
    } else {
        $Sort_Model->addSort($sort_data);
    }

    doAction('save_sort', $sid, $sort_data);

    $CACHE->updateCache(['sort', 'logsort', 'menu']);
    Gohref(SELF."?act=category&code=save");
}



if (isset($_GET['del']) && isset($_GET['sid'])) {
    $sid = Input::getIntVar('sid');
    LoginAuth::checkToken();
    $Sort_Model->deleteSort($sid);
    doAction('del_sort', $sid);
    $CACHE->updateCache(['sort', 'logsort', 'menu']);
    Gohref(SELF."?act=category&code=del");
}



if (isset($_GET['taxis'])) {
    $sort = $_POST['order'] ?? '';

    if (empty($sort)) {
        Output::error('没有可排序的分类');
    }

    foreach ($sort as $key => $value) {
        $value = (int)$value;
        $key = (int)$key;
        $Sort_Model->updateSort(array('taxis' => $key), $value);
    }

    $CACHE->updateCache(['sort', 'menu']);
    Output::ok('排序已保存');
}
