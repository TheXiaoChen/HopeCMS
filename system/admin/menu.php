<?php
/**
 * Hope CMS(希望CMS) 菜单管理
 * @author 林子辰
 * @link http://www.hopecms.cn
 */
defined('HOPE_ROOT') || exit('access denied!');

$Menu_Model = new Menu_Model();
$menus = $Menu_Model->getMenu();

$menu_root_ids = [];
$menu_default_id = 0;
foreach ($menus as $id => $menu) {
    if ((int)$menu['pid'] !== 0 || (int)$menu['type'] === 5) {
        continue;
    }
    $menu_root_ids[] = (int)$id;
    if ($menu['home'] === 'n') {
        $menu_default_id = (int)$id;
    }
}
if ($menu_default_id <= 0 && !empty($menu_root_ids)) {
    $menu_default_id = $menu_root_ids[0];
}
usort($menu_root_ids, function ($a, $b) use ($menus) {
    $homeA = ($menus[$a]['home'] ?? 'y') === 'n';
    $homeB = ($menus[$b]['home'] ?? 'y') === 'n';
    if ($homeA !== $homeB) {
        return $homeA ? -1 : 1;
    }
    return ((int)($menus[$a]['taxis'] ?? 0)) <=> ((int)($menus[$b]['taxis'] ?? 0));
});

// 保存菜单项顺序（拖拽后的结果）
if (isset($_GET['menu_taxis']) && isset($_POST['items'])) {
    hope_admin_require_token();
    $itemsJson = $_POST['items'];
    $items = json_decode($itemsJson, true);
    if (!is_array($items) || empty($items)) {
        Output::error('没有可保存的排序');
    }

    foreach ($items as $it) {
        $id = (int)($it['id'] ?? 0);
        $pid = (int)($it['pid'] ?? 0);
        $taxis = (int)($it['taxis'] ?? 0);
        if ($id > 0) {
            $Menu_Model->updateMenu(array('taxis' => $taxis, 'pid' => $pid), $id);
        }
    }
    $CACHE->updateCache('menu');
    Output::ok();
}

if (isset($_GET['get_type_list'])) {
    require_once OPT_PATH . 'codestar-framework.php';
    $type = Input::postIntVar('type', Input::getIntVar('type', 0));
    $options = [];
    switch ($type) {
        case 1:
            $options = CSF_Fields::field_data('blog');
            break;
        case 2:
            $options = CSF_Fields::field_data('page');
            break;
        case 3:
            $Sort_Model = new Sort_Model();
            $sorts = $Sort_Model->getSorts();
            $tree = [];
            foreach ($sorts as $sid => $sort) {
                if ((int)$sort['pid'] !== 0) {
                    continue;
                }
                $children = [];
                if (!empty($sort['children']) && is_array($sort['children'])) {
                    foreach ($sort['children'] as $childSid) {
                        if (!isset($sorts[$childSid])) {
                            continue;
                        }
                        $children[] = [
                            'id'   => (int)$childSid,
                            'name' => $sorts[$childSid]['sortname'],
                        ];
                    }
                }
                $tree[] = [
                    'id'       => (int)$sid,
                    'name'     => $sort['sortname'],
                    'children' => $children,
                ];
            }
            $options = [
                '_format' => 'sort_tree',
                'items'   => $tree,
            ];
            break;
        case 4:
            $options = CSF_Fields::field_data('tag');
            break;
    }
    Output::json($options);
}



if (isset($_GET['upmenu']) && isset($_POST['name'])) {//主菜单
    hope_admin_require_token();
    $id = Input::postIntVar('id');
    $name = Input::postStrVar('name');
    $type_id = Input::postStrVar('type_id', 0);
    $fields = Input::postStrArray('fields','');

    if ($name == '') Gohref(SELF."?act=menu&code=empty");
    if ($id == 0) {
        // SideModal 提交含 type_id；MenuModal 创建主菜单分组
        $type = isset($_POST['type_id']) ? Menu_Model::menutype_sidebar : 0;
        $url = '';
        if ($type === Menu_Model::menutype_sidebar) {
            $slot = hope_theme_side_normalize_slot(Input::postStrVar('side_slot', 'sidebar'));
            $allowed = hope_theme_side_slots();
            if (empty($allowed)) {
                hpMsg('当前主题未提供侧边栏', SELF . '?act=menu#side');
            }
            if (!in_array($slot, $allowed, true)) {
                $slot = $allowed[0];
            }
            $url = $slot;
        }
        $Menu_Model->addMenu($name, $url, 0, 'n', $type, $type_id, serialize($fields));
    } else {
        $update = ['menuname' => $name, 'type_id' => $type_id, 'fields' => serialize($fields)];
        if (isset($_POST['type_id'])) {
            $slot = hope_theme_side_normalize_slot(Input::postStrVar('side_slot', 'sidebar'));
            $allowed = hope_theme_side_slots();
            if (in_array($slot, $allowed, true)) {
                $update['url'] = $slot;
            }
        }
        $Menu_Model->updateMenu($update, $id);
    }

    $CACHE->updateCache('menu');
    $slotHash = '';
    if (isset($_POST['type_id'])) {
        $slotHash = '#side-' . hope_theme_side_normalize_slot(Input::postStrVar('side_slot', 'sidebar'));
    }
    Gohref(SELF . "?act=menu&code=add" . $slotHash);
}
if (isset($_GET['addmenuitem']) && isset($_POST['name'])) {//添加菜单项
    hope_admin_require_token();
    $pid = Input::postIntVar('pid');
    if($pid == 0){
        $pid = Input::postIntVar('id');
    }
    $type = Input::postIntVar('type');
    $name = Input::postStrVar('name');
    $url = Input::postStrVar('url');
    $target = Input::postStrVar('target','n');
    $typeId = Input::postStrVar('typeId',0);
    $field_key = Input::postStrArray('field_key');
    $field_values = Input::postStrArray('field_value');

    $fields = array();
    if (!empty($field_key) && !empty($field_values)) {
        foreach ($field_key as $key => $field_name) {
            $field_name = addslashes(trim($field_name));
            $field_value = addslashes(trim($field_values[$key] ?? ''));
            $fields [$field_name] = $field_value;
        }
    }

    $newMenuId = $Menu_Model->addMenu($name, $url,$pid, $target, $type, $typeId, serialize($fields));

    // 分类：勾选「同时添加子分类」时，把一级分类下的子分类挂到刚添加的菜单项下
    $withChildren = Input::postIntVar('with_children', 0);
    if ($withChildren && (int)$type === Menu_Model::menutype_category && $typeId && $newMenuId) {
        $Sort_Model = new Sort_Model();
        $sorts = $Sort_Model->getSorts();
        $parentSid = (int)$typeId;
        if (isset($sorts[$parentSid]) && !empty($sorts[$parentSid]['children'])) {
            foreach ($sorts[$parentSid]['children'] as $childSid) {
                if (!isset($sorts[$childSid])) {
                    continue;
                }
                $Menu_Model->addMenu(
                    $sorts[$childSid]['sortname'],
                    '',
                    $newMenuId,
                    $target,
                    Menu_Model::menutype_category,
                    $childSid,
                    serialize($fields)
                );
            }
        }
    }

    $CACHE->updateCache('menu');
    Gohref(SELF."?act=menu&code=add#menu".Input::postIntVar('id'));
}
// 更新菜单项
if (isset($_GET['upmenuitem']) && isset($_POST['name'])) {
    hope_admin_require_token();
    $itemId = Input::postIntVar('item_id');
    $pid = Input::postIntVar('pid',Input::postIntVar('id'));
    $type = Input::postIntVar('type');
    $name = Input::postStrVar('name');
    $url = Input::postStrVar('url');
    $target = Input::postStrVar('target','n');
    $typeId = Input::postStrVar('typeId',0);
    $field_key = Input::postStrArray('field_key');
    $field_values = Input::postStrArray('field_value');

    $fields = array();
    if (!empty($field_key) && !empty($field_values)) {
        foreach ($field_key as $key => $field_name) {
            $field_name = addslashes(trim($field_name));
            $field_value = addslashes(trim($field_values[$key] ?? ''));
            $fields [$field_name] = $field_value;
        }
    }
    if ($itemId && $name != '') {
        $updateData = array(
            'menuname' => $name,
            'url' => $url,
            'pid' => $pid,
            'target' => $target,
            'type' => $type,
            'type_id' => $typeId,
            'fields' => serialize($fields)
        );
        $Menu_Model->updateMenu($updateData, $itemId);
        $CACHE->updateCache('menu');
    }
    Gohref(SELF."?act=menu&code=edit#".$pid);
}

if (isset($_GET['home'])) {
    hope_admin_require_token();
    $id = Input::getIntVar('home');
    $Menu_Model->setHomeMenu($id);
    $CACHE->updateCache('menu');
    Gohref(SELF."?act=menu&code=home");
}

if (isset($_GET['del'])) {
    hope_admin_require_token();
    $menuid = (int)($_GET['id'] ?? '');
    $Menu_Model->deleteMenu($menuid);
    $CACHE->updateCache('menu');
    Gohref(SELF."?act=menu&code=del");
}

if (isset($_GET['hide'])) {
    hope_admin_require_token();
    $itemId = (int)($_GET['id'] ?? '');
    $Menu_Model->updateMenu(array('hide' => 'y'), $itemId);
    $CACHE->updateCache('menu');
    Gohref(SELF."?act=menu");
}

if (isset($_GET['show'])) {
    hope_admin_require_token();
    $itemId = (int)($_GET['id'] ?? '');
    $Menu_Model->updateMenu(array('hide' => 'n'), $itemId);
    $CACHE->updateCache('menu');
    Gohref(SELF."?act=menu");
}
