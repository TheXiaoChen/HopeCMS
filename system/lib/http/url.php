<?php
/**
 * Hope CMS(希望CMS) URL
 * @author LinZiChen <271106735@qq.com>
 * @link http://www.hopecms.cn
 * @copyright 2027 Hope CMS All rights reserved.
 * @license http://www.apache.org/licenses/LICENSE-2.0
 */
class Url {
    /**
     * 从 URL 规则去掉分页占位（首页 / 第 1 页链接）
     */
    public static function stripPagePlaceholder($rule) {
        $rule = (string)$rule;
        if (strpos($rule, '{%page%}') === false) {
            return $rule;
        }
        if (stripos($rule, '_{%page%}') !== false) {
            $rule = str_replace('_{%page%}', '', $rule);
        } elseif (stripos($rule, '/{%page%}') !== false) {
            $rule = str_replace('/{%page%}', '', $rule);
        } elseif (stripos($rule, '-{%page%}') !== false) {
            $rule = str_replace('-{%page%}', '', $rule);
        } elseif (stripos($rule, '={%page%}') !== false) {
            $rule = preg_replace('/&?[^&]*={%page%}/', '', $rule);
        } else {
            $rule = preg_replace('/(?<=\})[^\}]+(?=\{%page%\})/i', '', $rule, 1);
            $rule = str_replace('{%page%}', '', $rule);
        }
        $rule = rtrim($rule, '&');
        $rule = str_replace('?&', '?', $rule);
        if (substr($rule, -1) === '?') {
            $rule = substr($rule, 0, -1);
        }
        // 清理路径中多余斜杠（保留 https://）
        $rule = preg_replace('#(?<!:)/{2,}#', '/', $rule);
        return $rule;
    }

    // 简单规则替换，支持常见占位符
    // $Pagination === 'page'|true：保留 {%page%} 供 pagination() 填充；null：生成无分页 URL；其它：按页码替换
    private static function buildFromRule($rule, $vars, $Pagination = null) {
        if (!$rule) return '';

        $keepPagePlaceholder = ($Pagination === 'page' || $Pagination === true);

        // 无分页链接：去掉 {%page%} 片段
        if (!$keepPagePlaceholder && $Pagination === null && strpos($rule, '{%page%}') !== false) {
            $rule = self::stripPagePlaceholder($rule);
        }

        $replacements = [
            '{%host%}' => SITE_URL,
        ];
        foreach ($vars as $k => $v) {
            // 分页基址：不要把 {%page%} 替换成字面量 "page"
            if ($keepPagePlaceholder && $k === 'page') {
                continue;
            }
            $replacements['{%' . $k . '%}'] = $v;
        }

        return strtr($rule, $replacements);
    }

    /**
     * Get article links
     */
    static function log($blogId) {
        // 先读文章基本信息（用于规则替换和别名）
        $Log_Model = new Log_Model();
        $logInfo = $Log_Model->getDetail($blogId);
        $sortName = $logInfo['sortname'] ?? '';
        $sortAlias = $logInfo['sort_alias'] ?? '';
        $logAlias = $logInfo['alias'] ?? '';
        $date = $logInfo['date'] ?? '';
        $type = $logInfo['type'] ?? '';
        $year = $month = $day = '';
        if ($date) {
            $year = date('Y', $date);
            $month = date('m', $date);
            $day = date('d', $date);
        }
        if ($type == 'blog') { 
            $rule = Option::get('rule_article');
        }else if($type == 'page')   {
            $rule = Option::get('rule_page');
        }else{
            $rule = Option::get('rule_'.$type);
        }
        $vars = [
            'id' => $blogId,
            'alias' => !empty($logAlias) ? $logAlias : $blogId,
            'year' => $year,
            'month' => $month,
            'day' => $day,
            'category' => $sortAlias ?: $sortName,
            'sortname' => $sortName,
            'sort_alias' => $sortAlias,
            'type' => $type,
        ];
        return self::buildFromRule($rule, $vars);
    }
    
    static function record($record, $page = null) {
        $rule = Option::get('rule_record');
        return self::buildFromRule($rule, ['record' => $record, 'page' => $page]);
    }

    static function sort($sortId, $page = null) {
        $CACHE = Cache::getInstance();
        $sort_cache = $CACHE->readCache('sort');
        $sortInfo = $sort_cache[$sortId] ?? [];
        $sort_index = !empty($sortInfo['alias']) ? $sortInfo['alias'] : $sortId;

        $pid = $sortInfo && !empty($sortInfo['pid']) ? $sortInfo['pid'] : 0; //   父分类ID
        $pAlias = $pid && !empty($sort_cache[$pid]['alias']) ? $sort_cache[$pid]['alias'] : ''; // 父分类别名

        $rule = Option::get('rule_category');
        $vars = [
            'id' => $sortId,
            'alias' => $sort_index,
            'category' => $sort_index,
            'sortname' => $sortInfo['sortname'] ?? '',
            'sort_alias' => !empty($sortInfo['alias']) ? $sortInfo['alias'] : '',
            'parent' => $pAlias,
            'page' => $page,
        ];
        return self::buildFromRule($rule, $vars, $page);
    }

    /**
     * 用户模块前台链接（?key / /key / index.php/key）
     */
    private static function buildUserRoute($segment, $query = []) {
        $linkmode = (string) Option::get('linkmode');
        if ($linkmode === '1') {
            $url = SITE_URL . $segment;
        } elseif ($linkmode === '2') {
            $url = SITE_URL . 'index.php/' . $segment;
        } else {
            $url = SITE_URL . '?' . $segment;
        }
        if (!empty($query)) {
            $sep = (strpos($url, '?') !== false) ? '&' : '?';
            $url .= $sep . http_build_query($query);
        }
        return $url;
    }

    static function userCenter($query = []) {
        return self::buildUserRoute('user', $query);
    }

    static function login($query = []) {
        return self::buildUserRoute('login', $query);
    }

    static function register($query = []) {
        return self::buildUserRoute('register', $query);
    }

    static function authApi($query = []) {
        return self::buildUserRoute('auth_api', $query);
    }

    /**
     * 前台插件页（?plugin=name / /plugin/name）
     */
    static function plugin($name, $query = []) {
        $name = trim((string) $name);
        $linkmode = (string) Option::get('linkmode');
        if ($linkmode === '1') {
            $url = SITE_URL . 'plugin/' . rawurlencode($name);
        } elseif ($linkmode === '2') {
            $url = SITE_URL . 'index.php/plugin/' . rawurlencode($name);
        } else {
            $url = SITE_URL . '?plugin=' . rawurlencode($name);
        }
        if (!empty($query)) {
            $sep = (strpos($url, '?') !== false) ? '&' : '?';
            $url .= $sep . http_build_query($query);
        }
        return $url;
    }

    static function author($authorId, $page = null) {
        $linkmode = (string)Option::get('linkmode');
        if ($linkmode == '1') {
            $authorUrl = SITE_URL . 'author/' . $authorId;
            if ($page) {
                $authorUrl .= '/page/' . $page;
            }
            return $authorUrl;
        }elseif ($linkmode == '2') {
            $authorUrl = str_replace(SITE_URL, SITE_URL . 'index.php/', $authorUrl);
            if ($page) {
                $authorUrl .= '/page/' . $page;
            }
        }else {
            $authorUrl = SITE_URL . '?author=' . $authorId;
            if ($page) {
                $authorUrl .= '&page=';
            }
        }
        return $authorUrl;
    }

    static function tag($tag, $page = null) {
        $rule = Option::get('rule_tag');
        return self::buildFromRule($rule, [ 'tag' => $tag ,'page' => $page ], $page);
    }

    static function logPage() {
        $posts = Option::get('home_page_id') > 0 ? 'posts/' : '';
        $rule = Option::get('rule_index');
        return self::buildFromRule($rule. $posts, [], 1);
    }

    static function comment($blogId, $pageId, $cid) {
        $commentUrl = Url::log($blogId);
        if ($pageId > 1) {
            if ((string)Option::get('linkmode') == '0' && strpos($commentUrl, '=') !== false) {
                $commentUrl .= '&comment-page=';
            } else {
                $commentUrl .= '/comment-page-';
            }
            $commentUrl .= $pageId;
        }
        $commentUrl .= '#' . $cid;
        return $commentUrl;
    }

    /**
     * 获取菜单链接
     */
    static function menu($type, $typeId, $url) {
        switch ($type) {
            case Menu_Model::menutype_article:
            case Menu_Model::menutype_page:
                $url = self::log($typeId);
                break;
            case Menu_Model::menutype_category:
                $url = self::sort($typeId);
                break;
            case Menu_Model::menutype_tag:
                $Tag_Model = new Tag_Model();
                $tag = $Tag_Model->getOneTag($typeId);
                if ($tag && !empty($tag['tagname'])){
                    $url = self::tag($tag['tagname']);
                }
                break;
            case Menu_Model::menutype_sidebar:
                // sidebar 类型的 url 保持传入的 $url 不变
                return $url;
            default:
                $url = (strpos($url, 'http') === 0 ? '' : SITE_URL) . $url;
                break;
        }
        return $url;
    }

}
