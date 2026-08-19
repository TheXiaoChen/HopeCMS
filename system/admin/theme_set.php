<?php
/**
 * Hope CMS(希望CMS) category
 * @author 林子辰
 * @link http://www.hopecms.cn
 */
defined('HOPE_ROOT') || exit('access denied!');
require_once OPT_PATH . 'codestar-framework.php'; // 框架文件
$themes = Input::getStrVar('tpl');

// 程序版本发布 AJAX（已迁至应用中心 apply 插件）
if (isset($_GET['core_release_api'])) {
    $applyCore = HOPE_ROOT . 'content/plugin/apply/apply_core_release.php';
    if (is_file($applyCore)) {
        require_once $applyCore;
        if (function_exists('hope_core_release_handle_api')) {
            hope_core_release_handle_api();
        }
    }
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['code' => 0, 'msg' => '请启用应用中心（apply）插件以管理程序版本'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (isset($_GET['hope-get-icons'])) {
    // 仅输出真实字形图标；已去除无效的「系统内置 SVG」分组
    $icon_lists = array_values(array_filter([
        icons('Font Awesome 7', 'fontawesome7', 'fa', 'fa ', 'glyph'),
        icons('Remixicon V4', 'remixicon.min', 'ri', '', 'glyph'),
    ], static function ($list) {
        return !empty($list['icons']);
    }));

    $html = '';
    if (!empty($icon_lists)) {
        $multi = count($icon_lists) >= 2;
        foreach ($icon_lists as $list) {
            if ($multi) {
                $html .= '<div class="hope-icon-title">' . htmlspecialchars($list['title']) . '</div>';
            }
            foreach ($list['icons'] as $icon) {
                $html .= '<i title="' . htmlspecialchars($icon) . '" class="' . htmlspecialchars($icon) . '"></i>';
            }
        }
    } else {
        $html .= '<div class="hope-error-text">无相关数据。</div>';
    }
    header('Content-Type: text/html; charset=UTF-8');
    echo $html;
    exit;
}
if(isset($_GET['hope-chosen'])){
    $term = Input::postStrVar('term');
    $type = Input::postStrVar('type');
    
    $options      = array();
    $db = Database::getInstance();

    switch ($type) {
        case 'blog':
        case 'page':
            $query = hope_sql_select('gid,title', 'article', "hide='n' AND checked='y' AND type='$type' AND (title like '%$term%' OR content like '%$term%') ORDER BY gid DESC");
            if (!empty($query)) {
                while ($data = $db->fetch_array($query)) {
                    $options[] = ['value' =>$data['gid'],'text'=>$data['title']] ;
                }
            }


            break;
        case 'category':
        case 'sort':
            $query = hope_sql_select('sid,sortname', 'sort', "sortname like '%$term%'");
            if (!empty($query)) {
                while ($data = $db->fetch_array($query)) {
                    $options[$data['sid']] = $data['sortname'];
                }
            }
            break;
        case 'tag':
        case 'tags':
            $query = hope_sql_select('tid,tagname', 'tag', "tagname like '%$term%'");
            if (!empty($query)) {
                while ($data = $db->fetch_array($query)) {
                    $options[$data['tid']] = $data['tagname'];
                }
            }
            break;
        case 'user':
            $query = hope_sql_select('uid,username', 'user', "nickname like '%$term%' OR username like '%$term%' OR email like '%$term%'");
            if (!empty($query)) {
                while ($data = $db->fetch_array($query)) {
                    $options[$data['uid']] = $data['username'];
                }
            }
            break;
        default:

            if (is_callable($type . '_title')) {
                $options[$type] = call_user_func($type . '_title', $type);
            } else {
                $options[$type] = ucfirst($type);
            }

            break;
    }
    
    Output::ok($options);
}
if(isset($_GET['import_options']) && isset($_POST['theme'])){
    $theme = Input::postStrVar('theme');
    $data = $_POST['data'] ?:'';
    if(empty($data))Output::error('导入数据格式错误！');
    if (is_string($data)) {
        $jsonData = json_decode($data, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $data = $jsonData;
        }else{
            Output::error('数据格式错误，不是有效的JSON格式');
        }
    }
    Option::updateOption($theme, serialize($data));
    $CACHE->updateCache('options');

    Output::ok('导入成功，5秒后页面将自动刷新');

}
if(isset($_GET['export_options']) && isset($_GET['theme'])){
    // 获取主题选项数据
    $theme = Input::getStrVar('theme');
    if(!$themes = Option::get($theme)){
        Output::error('导出失败，请先保存主题设置后再导出！');
    }
    // $themes 可能是 serialize 字符串，也可能是 JSON 或数组，尝试解析为数组/对象
    $data = null;
    // 如果是空，返回错误
    if(empty($themes)){
        Output::error('导出失败：数据为空');
    }

    // 尝试 unserialize
    $maybe = @unserialize($themes);
    if($maybe !== false || $themes === 'b:0;'){
        $data = $maybe;
    } else {
        // 尝试 json_decode
        $maybeJson = json_decode($themes, true);
        if(json_last_error() === JSON_ERROR_NONE){
            $data = $maybeJson;
        } else {
            // 可能 Option::get 已经返回了数组/对象
            if(is_array($themes) || is_object($themes)){
                $data = $themes;
            } else {
                // 最后退回为原始字符串
                $data = $themes;
            }
        }
    }

    // 强制转为 JSON 字符串用于下载
    $json = json_encode($data);
    if($json === false){
        Output::error('导出失败：数据异常');
    }

    // 清理所有之前可能的输出
    if (ob_get_level()) {
        ob_clean();
    }
    // 发送下载头，文件名 backup-日期.json
    header('Content-Type: application/json');
    header('Content-disposition: attachment; filename=backup-'. gmdate('d-m-Y') .'.json');
    header('Content-Transfer-Encoding: binary');
    header('Pragma: no-cache');
    header('Expires: 0');
    exit($json);
}

if(isset($_GET['hope_ajax_save'])){
    // Export
    header('Content-Type: application/json');
    header('Content-disposition: attachment; filename=backup-'. gmdate( 'd-m-Y' ) .'.json');
    header('Content-Transfer-Encoding: binary');
    header('Pragma: no-cache');
    header('Expires: 0');
    if(empty($_POST[$themes])){
        exit(json_encode(array('errors' => 1 )));
    }
    Option::updateOption($themes, serialize($_POST[$themes]));
    $CACHE->updateCache('options');
    exit(json_encode(['data' => ['errors' => 0,'notice' => '设置已保存。'],'success' => true]));
}

if (!$optionFile = is_tpl_options_exists($themes)) {
    hpMsg('当前主题不支持选项配置，请联系主题作者');
}
require_once $optionFile; // 加载配置options.php文件

//加载插件底部资源
addAction('adm_footer', 'opt_footer_content');
function opt_footer_content()
{
    CSF::add_admin_enqueue_scripts();
}


