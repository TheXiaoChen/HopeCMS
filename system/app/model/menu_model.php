<?php
/**
 * Hope CMS(希望CMS) menu model
 * @author LinZiChen <271106735@qq.com>
 * @link http://www.hopecms.cn
 * @copyright 2027 Hope CMS All rights reserved.
 * @license http://www.apache.org/licenses/LICENSE-2.0
*/

class Menu_Model {

    private $db;
    private $table;

    const menutype_custom = 0;  //自定义链接
    const menutype_article = 1; //文章
    const menutype_page = 2;    //页面
    const menutype_category = 3;//分类
    const menutype_tag = 4;     //标签
    const menutype_sidebar = 5; //侧边栏
    public static $menutype_name = [
        self::menutype_custom => '自定义',
        self::menutype_article => '文章',
        self::menutype_page => '页面',
        self::menutype_category => '分类',
        self::menutype_tag => '标签',
        self::menutype_sidebar => '侧边栏'
    ];
    function __construct() {
        $this->db = Database::getInstance();
        $this->table = DB_PREFIX . 'menu';
    }

    /**
     * 将文章 / 页面 / 分类 / 标签菜单项的标题同步为来源最新名称
     * （更新缓存时调用，保证前台主菜单标题随之刷新）
     */
    function syncLinkedMenuTitles() {
        $typeList = implode(',', [
            self::menutype_article,
            self::menutype_page,
            self::menutype_category,
            self::menutype_tag,
        ]);
        $query = $this->db->query("SELECT id, type, type_id, menuname FROM $this->table WHERE type IN ($typeList)");
        if (!$query) {
            return;
        }

        $items = [];
        $articleIds = [];
        $sortIds = [];
        $tagIds = [];
        while ($row = $this->db->fetch_array($query)) {
            $id = (int)$row['id'];
            $type = (int)$row['type'];
            $typeId = (int)$row['type_id'];
            if ($id <= 0 || $typeId <= 0) {
                continue;
            }
            $items[] = [
                'id'       => $id,
                'type'     => $type,
                'type_id'  => $typeId,
                'menuname' => (string)$row['menuname'],
            ];
            if ($type === self::menutype_article || $type === self::menutype_page) {
                $articleIds[$typeId] = $typeId;
            } elseif ($type === self::menutype_category) {
                $sortIds[$typeId] = $typeId;
            } elseif ($type === self::menutype_tag) {
                $tagIds[$typeId] = $typeId;
            }
        }
        if (!$items) {
            return;
        }

        $articleTitles = [];
        if ($articleIds) {
            $ids = implode(',', $articleIds);
            $res = $this->db->query('SELECT gid, title FROM ' . DB_PREFIX . "article WHERE gid IN ($ids)");
            while ($row = $this->db->fetch_array($res)) {
                $articleTitles[(int)$row['gid']] = trim((string)$row['title']);
            }
        }

        $sortNames = [];
        if ($sortIds) {
            $ids = implode(',', $sortIds);
            $res = $this->db->query('SELECT sid, sortname FROM ' . DB_PREFIX . "sort WHERE sid IN ($ids)");
            while ($row = $this->db->fetch_array($res)) {
                $sortNames[(int)$row['sid']] = trim((string)$row['sortname']);
            }
        }

        $tagNames = [];
        if ($tagIds) {
            $ids = implode(',', $tagIds);
            $res = $this->db->query('SELECT tid, tagname FROM ' . DB_PREFIX . "tag WHERE tid IN ($ids)");
            while ($row = $this->db->fetch_array($res)) {
                $tagNames[(int)$row['tid']] = trim((string)$row['tagname']);
            }
        }

        foreach ($items as $item) {
            $name = '';
            if ($item['type'] === self::menutype_article || $item['type'] === self::menutype_page) {
                $name = $articleTitles[$item['type_id']] ?? '';
            } elseif ($item['type'] === self::menutype_category) {
                $name = $sortNames[$item['type_id']] ?? '';
            } elseif ($item['type'] === self::menutype_tag) {
                $name = $tagNames[$item['type_id']] ?? '';
            }
            if ($name === '' || $name === $item['menuname']) {
                continue;
            }
            $safeName = $this->db->escape_string($name);
            $this->db->query("UPDATE $this->table SET menuname='$safeName' WHERE id=" . (int)$item['id']);
        }
    }

    function getMenu() {
        $menu = [];
        $orphanItems = [];
        $query = $this->db->query("SELECT * FROM $this->table ORDER BY pid ASC, taxis ASC");
        
        while ($row = $this->db->fetch_array($query)) {
            $menuData = array(
                'id'        => (int)$row['id'],
                'home'      => $row['home'],
                'name'      => htmlspecialchars(trim($row['menuname'])),
                'url'       => htmlspecialchars(trim($row['url'])),
                'target'    => $row['target'],
                'type'      => (int)$row['type'],
                'typeId'    => $row['type_id'],
                'taxis'     => (int)$row['taxis'],
                'hide'      => $row['hide'],
                'pid'       => (int)$row['pid'],
                'fields'    => $row['fields'],
            );

            if ($menuData['pid'] == 0) {
                $menu[$row['id']] = $menuData;
            } else {
                $orphanItems[] = $menuData;
            }
        }
        
        // 第二次遍历：处理子菜单项
        foreach ($orphanItems as $item) {
            $this->addToParent($menu, $item);
        }
        
        return $menu;
    }

    private function addToParent(&$menu, $item) {
        // 检查是否已经存在相同的子项，避免重复添加
        $found = false;
        
        // 遍历菜单树查找父节点
        foreach ($menu as &$menuItem) {
            if ($menuItem['id'] == $item['pid']) {
                // 找到直接父节点
                if (!isset($menuItem['child'])) {
                    $menuItem['child'] = [];
                }
                
                // 检查是否已存在相同ID的子项
                $exists = false;
                foreach ($menuItem['child'] as $child) {
                    if ($child['id'] == $item['id']) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    $menuItem['child'][] = $item;
                }
                $found = true;
                break;
            } elseif (isset($menuItem['child'])) {
                // 在子菜单中递归查找
                $result = $this->addToParent($menuItem['child'], $item);
                if ($result !== false) {
                    $found = true;
                    break;
                }
            }
        }
        
        return $found ? true : false;
    }
    function getMenus($menuData, $menu = 'lord') {
        $childMenus = [];
        if (!is_array($menuData)) {
            return [];
        }
        if (is_numeric($menu)) {
            if (isset($menuData[$menu])) {
                $childMenus = $menuData[$menu]['child'] ?? [];
            }
        } elseif ($menu === 'sidebar') {
            foreach ($menuData as $menuItem) {
                if ((int)($menuItem['type'] ?? 0) === self::menutype_sidebar && ($menuItem['hide'] ?? 'n') !== 'y') {
                    $childMenus[] = $menuItem;
                }
            }
            usort($childMenus, function ($a, $b) {
                return ((int)($a['taxis'] ?? 0)) <=> ((int)($b['taxis'] ?? 0));
            });
        } else {
            foreach ($menuData as $menuItem) {
                if (($menuItem['home'] ?? 'y') === 'n') {
                    $childMenus = $menuItem['child'] ?? [];
                    break;
                }
            }
        }
        return $this->filterVisibleMenuItems($childMenus);
    }

    private function filterVisibleMenuItems($items) {
        $visible = [];
        if (!is_array($items)) {
            return [];
        }
        foreach ($items as $item) {
            if (($item['hide'] ?? 'n') === 'y') {
                continue;
            }
            if (!empty($item['child']) && is_array($item['child'])) {
                $item['child'] = $this->filterVisibleMenuItems($item['child']);
            }
            $visible[] = $item;
        }
        return $visible;
    }
    function updateMenu($menuData, $menuId) {
        switch ($menuData['type'] ?? null) {
            case self::menutype_article:
            case self::menutype_page:
                $Log_Model = new Log_Model();
                $log = $Log_Model->getDetail($menuData['type_id']);
                if ($log && !empty($log['title'])){
                    $menuData['menuname'] = htmlspecialchars(trim($log['title']));
                    $menuData['url'] = '';
                } 
                break;
            case self::menutype_category:
                $Sort_Model = new Sort_Model();
                $sortName = $Sort_Model->getSortName($menuData['type_id']);
                if (!empty($sortName)){
                    $menuData['menuname'] = htmlspecialchars(trim($sortName));
                    $menuData['url'] = '';  
                }
                break;
            case self::menutype_tag:
                $Tag_Model = new Tag_Model();
                $tag = $Tag_Model->getOneTag($menuData['type_id']);
                if ($tag && !empty($tag['tagname'])){
                    $menuData['menuname'] = htmlspecialchars(trim($tag['tagname']));
                    $menuData['url'] = '';  
                }
                break;
            default:
                break;
        }
        $Item = [];
        foreach ($menuData as $key => $data) {
            $Item[] = "$key='$data'";
        }
        $upStr = implode(',', $Item);
        $this->db->query("update $this->table set $upStr where id=$menuId");
    }

    function addMenu($name, $url='', $pid=0, $target='n', $type=0, $typeId=0 ,$field = '') {
        switch ($type) {
            case self::menutype_article:
            case self::menutype_page:
                $Log_Model = new Log_Model();
                $log = $Log_Model->getDetail($typeId);
                if ($log && !empty($log['title'])) $name = htmlspecialchars(trim($log['title']));
                break;
            case self::menutype_category:
                $Sort_Model = new Sort_Model();
                $sortName = $Sort_Model->getSortName($typeId);
                if (!empty($sortName)) $name = htmlspecialchars(trim($sortName));
                break;
            case self::menutype_tag:
                $Tag_Model = new Tag_Model();
                $tag = $Tag_Model->getOneTag($typeId);
                if ($tag && !empty($tag['tagname']))$name = htmlspecialchars(trim($tag['tagname']));
                break;
            default:
                break;
        }
        $sql = "insert into $this->table (menuname,url,pid,target,type,type_id,fields) values('$name','$url', $pid, '$target', $type, '$typeId', '$field')";
        $this->db->query($sql);
        return (int)$this->db->insert_id();
    }
    function setHomeMenu($id) {
        $this->db->query("update $this->table set home = 'y'");
        $this->db->query("update $this->table set home = 'n' WHERE id = '$id'");
    }

    function getOneMenu($menuId) {
        $sql = "select * from $this->table where id=$menuId ";
        $res = $this->db->query($sql);
        $row = $this->db->fetch_array($res);
        $menuData = [];
        if ($row) {
            $menuData = array(
                'name'   => htmlspecialchars(trim($row['menuname'])),
                'url'    => htmlspecialchars(trim($row['url'])),
                'target' => $row['target'],
                'home'   => $row['home'],
                'type'   => (int)$row['type'],
                'typeId' => $row['type_id'],
                'pid'    => (int)$row['pid'],
            );
        }
        return $menuData;
    }

    function getMenuNameByUrl($url) {
        $CACHE = Cache::getInstance();
        $menu_cache = $CACHE->readCache('menu');
        if (!is_array($menu_cache)) {
            return '';
        }
        return $this->findMenuName($menu_cache, function ($item) use ($url) {
            return isset($item['url']) && $item['url'] == $url;
        });
    }

    function getMenuNameByType($type) {
        $CACHE = Cache::getInstance();
        $menu_cache = $CACHE->readCache('menu');
        if (!is_array($menu_cache)) {
            return '';
        }
        return $this->findMenuName($menu_cache, function ($item) use ($type) {
            return isset($item['type']) && (int)$item['type'] === (int)$type;
        });
    }

    private function findMenuName($items, $matcher) {
        foreach ($items as $item) {
            if ($matcher($item)) {
                return $item['name'] ?? '';
            }
            if (!empty($item['child']) && is_array($item['child'])) {
                $found = $this->findMenuName($item['child'], $matcher);
                if ($found !== '') {
                    return $found;
                }
            }
        }
        return '';
    }

    function deleteMenu($menuId) {
        $allIds = [$menuId];
        $this->collectChildMenuIds($menuId, $allIds);
        $idsStr = implode(',', $allIds);
        $this->db->query("DELETE FROM $this->table WHERE pid IN ($idsStr) OR id IN ($idsStr)");
        
    }

    private function collectChildMenuIds($parentId, &$ids) {
        $query = $this->db->query("SELECT id FROM $this->table WHERE pid = $parentId");
        while ($row = $this->db->fetch_array($query)) {
            $ids[] = $row['id'];
            $this->collectChildMenuIds($row['id'], $ids);
        }
    }





}
