<?php
/**
 * Hope CMS(希望CMS) 配置项
 * @author LinZiChen <271106735@qq.com>
 * @link http://www.hopecms.cn
 * @copyright 2027 Hope CMS All rights reserved.
 * @license http://www.apache.org/licenses/LICENSE-2.0
 */
class Option {
    const HOPE_VERSION = '1.0.2';
    const HOPE_VERSION_TIMESTAMP = 1787184000;
    const UPLOAD_PATH = '../content/upload/';
    const UPLOAD_FULL_PATH = HOPE_ROOT . 'content/upload/';

    /** @var array<string, mixed> 已解码的序列化配置（请求内复用） */
    private static $_decodedOptions = [];
    /** @var string|null 当前解码所依据的 options 缓存指纹 */
    private static $_decodedOptionsFp = null;

    static function get($option) {
        $CACHE = Cache::getInstance();
        $options_cache = $CACHE->readCache('options');
        if (!is_array($options_cache)) {
            $options_cache = [];
        }
        // options 重建后清空解码缓存，避免脏读
        $fp = (string)($options_cache['site_key'] ?? '');
        $fp .= '|' . (isset($options_cache['active_plugins']) ? strlen((string)$options_cache['active_plugins']) : '0');
        $fp .= '|' . (isset($options_cache['widget_title']) ? strlen((string)$options_cache['widget_title']) : '0');
        if (self::$_decodedOptionsFp !== $fp) {
            self::$_decodedOptions = [];
            self::$_decodedOptionsFp = $fp;
        }
        switch ($option) {
            case 'active_plugins':
            case 'widget_title':
                if (array_key_exists($option, self::$_decodedOptions)) {
                    return self::$_decodedOptions[$option];
                }
                if (!empty($options_cache[$option])) {
                    $decoded = @unserialize($options_cache[$option]);
                    self::$_decodedOptions[$option] = is_array($decoded) ? $decoded : [];
                } else {
                    self::$_decodedOptions[$option] = [];
                }
                return self::$_decodedOptions[$option];
            case 'siteurl':
                if (($options_cache['detect_url'] ?? 'n') === 'y') {
                    return realUrl();
                }
                $url = trim((string)($options_cache['siteurl'] ?? ''));
                return $url !== '' ? $url : realUrl();
            case 'posts_name':
                if (empty($options_cache['posts_name'])) {
                    return '文章';
                }
                return $options_cache['posts_name'];
            case 'footer_info':
                return stripslashes((string)($options_cache['footer_info'] ?? ''));
            default:
                return $options_cache[$option] ?? '';
        }
    }

    /** @var int 路由表缓存代数，保存链接设置后递增 */
    private static $_routingCacheGen = 0;

    /**
     * 清除路由表内存缓存（保存链接设置后调用）
     */
    static function resetRoutingTableCache() {
        self::$_routingCacheGen++;
    }

    /**
     * 获取增强版路由表（按 linkmode 自动切换静态/动态规则）
     */
    static function getEnhancedRoutingTable() {
        $linkmode = (string) self::get('linkmode');
        $rulesSignature = md5(implode('|', [
            $linkmode,
            self::get('rule_index'),
            self::get('rule_page'),
            self::get('rule_article'),
            self::get('rule_category'),
            self::get('rule_tag'),
            self::get('rule_record'),
        ]));

        static $cachedTable = null;
        static $cachedSignature = null;
        static $cacheVersion = -1;

        if ($cachedTable !== null
            && $cachedSignature === $rulesSignature
            && $cacheVersion === self::$_routingCacheGen) {
            return $cachedTable;
        }

        $isRewrite = ($linkmode === '1' || $linkmode === '2');

        $normalizeRule = function ($rule, $defaultRule = '') {
            $rule = trim((string)$rule);
            if ($rule === '') {
                $rule = $defaultRule;
            }
            $rule = str_replace('{%host%}', '', $rule);
            $rule = str_replace('index.php/', '', $rule);
            $rule = trim($rule);
            if ($rule !== '' && $rule[0] !== '/' && $rule[0] !== '?') {
                $rule = '/' . $rule;
            }
            return $rule;
        };

        $getParams = function ($pattern) {
            $result = [];
            $pattern = (string)$pattern;
            if ($pattern === '') {
                return $result;
            }

            $query = parse_url($pattern, PHP_URL_QUERY);
            if (!is_string($query)) {
                $query = '';
            }

            if ($query !== '') {
                $pairs = [];
                $buffer = '';
                $inPlaceholder = false;
                $query = trim($query, '&');
                $length = strlen($query);
                for ($i = 0; $i < $length; $i++) {
                    $ch = $query[$i];
                    $next = ($i + 1 < $length) ? $query[$i + 1] : '';
                    if (!$inPlaceholder && $ch === '{' && $next === '%') {
                        $inPlaceholder = true;
                        $buffer .= $ch;
                        continue;
                    }
                    if ($inPlaceholder && $ch === '%' && $next === '}') {
                        $inPlaceholder = false;
                        $buffer .= $ch;
                        continue;
                    }
                    if ($ch === '&' && !$inPlaceholder) {
                        if ($buffer !== '') {
                            $pairs[] = $buffer;
                            $buffer = '';
                        }
                        continue;
                    }
                    $buffer .= $ch;
                }
                if ($buffer !== '') {
                    $pairs[] = $buffer;
                }
                foreach ($pairs as $pair) {
                    if ($pair === '') {
                        continue;
                    }
                    $eqPos = strpos($pair, '=');
                    if ($eqPos === false) {
                        $result[$pair] = null;
                        continue;
                    }

                    $key = trim(substr($pair, 0, $eqPos));
                    $valuePattern = trim(substr($pair, $eqPos + 1));
                    if ($key === '') {
                        continue;
                    }

                    if (preg_match('/^\{%(.+)%\}$/', $valuePattern, $m)) {
                        $expr = trim($m[1]);
                        if (strpos($expr, '&type=') !== false) {
                            $expr = trim(explode('&type=', $expr, 2)[0]);
                        }
                        if (strpos($expr, '=') !== false) {
                            list(, $default) = explode('=', $expr, 2);
                            $default = trim($default);
                            $result[$key] = $default === '' ? null : $default;
                        } else {
                            $result[$key] = null;
                        }
                    } else {
                        $result[$key] = $valuePattern;
                    }
                }
            }

            if (empty($result) && preg_match_all('/\{%\&?([A-Za-z0-9_\-]+)=/', $pattern, $m)) {
                foreach ($m[1] as $param) {
                    $result[$param] = null;
                }
            }

            return $result;
        };

        $resolveRule = function ($optionKey, $rewriteDefault, $dynamicDefault) use ($isRewrite, $normalizeRule) {
            $stored = trim((string) self::get($optionKey));
            if ($isRewrite) {
                $rule = $stored !== '' ? $stored : $rewriteDefault;
                return $normalizeRule($rule, $rewriteDefault);
            }
            if ($stored !== '' && strpos($stored, '?') !== false) {
                return $normalizeRule($stored, $dynamicDefault);
            }
            return $normalizeRule($dynamicDefault, $dynamicDefault);
        };

        $routeRules = [
            'rule_index'    => $resolveRule('rule_index', '/page/{%page%}/', '?page={%page%}'),
            'rule_page'     => $resolveRule('rule_page', '/{%alias%}.html', '?id={%id%}'),
            'rule_article'  => $resolveRule('rule_article', '/article/{%id%}.html', '?article={%id%}'),
            'rule_category' => $resolveRule('rule_category', '/category/{%alias%}/{%page%}/', '?category={%id%}&page={%page%}'),
            'rule_tag'      => $resolveRule('rule_tag', '/tags-{%tag%}_{%page%}.html', '?tags={%tag%}&page={%page%}'),
            'rule_record'   => $resolveRule('rule_record', '/date/{%record%}/{%page%}/', '?record={%record%}&page={%page%}'),
        ];

        $makeRoute = function ($config) use ($getParams) {
            $pattern = $config['pattern'] ?? '';
            $defaults = [
                'not_get'        => [],
                'must_get'       => [],
                'request_method' => '',
            ];
            if (!isset($config['get'])) {
                $config['get'] = $getParams($pattern);
            }
            return array_merge($defaults, $config);
        };

        // —— 活动路由（动态 query，全 linkmode 生效）——
        $activeRoutes = [
            $makeRoute([
                'type'     => 'active',
                'prefix'   => '',
                'model'    => 'Author_Controller',
                'method'   => 'display',
                'pattern'  => '{%author=%}{%&page=%}',
                'must_get' => ['author'],
                'args'     => ['author&id', 'author@alias', 'page'],
            ]),
            $makeRoute([
                'type'     => 'active',
                'model'    => 'Search_Controller',
                'method'   => 'display',
                'pattern'  => '{%keyword=%}{%&page=%}',
                'must_get' => ['keyword'],
            ]),
            // 用户中心：伪静态 path + query（?user）双兼容，避免主题旧链接落到首页
            $makeRoute([
                'type'     => 'active',
                'prefix'   => 'user',
                'model'    => 'User_Controller',
                'method'   => 'index',
                'pattern'  => '/user',
                'must_get' => [],
                'request_method' => '',
            ]),
            $makeRoute([
                'type'     => 'active',
                'prefix'   => 'user',
                'model'    => 'User_Controller',
                'method'   => 'index',
                'pattern'  => '{%user=%}{%&api=%}',
                'must_get' => ['user'],
                'request_method' => '',
            ]),
            $makeRoute([
                'type'     => 'active',
                'prefix'   => 'login',
                'model'    => 'User_Controller',
                'method'   => 'login',
                'pattern'  => '/login',
                'must_get' => [],
            ]),
            $makeRoute([
                'type'     => 'active',
                'prefix'   => 'login',
                'model'    => 'User_Controller',
                'method'   => 'login',
                'pattern'  => '{%login=%}{%&api=%}',
                'must_get' => ['login'],
            ]),
            $makeRoute([
                'type'     => 'active',
                'prefix'   => 'register',
                'model'    => 'User_Controller',
                'method'   => 'register',
                'pattern'  => '/register',
                'must_get' => [],
            ]),
            $makeRoute([
                'type'     => 'active',
                'prefix'   => 'register',
                'model'    => 'User_Controller',
                'method'   => 'register',
                'pattern'  => '{%register=%}{%&api=%}',
                'must_get' => ['register'],
            ]),
            $makeRoute([
                'type'     => 'active',
                'prefix'   => 'auth_api',
                'model'    => 'User_Controller',
                'method'   => 'authApi',
                'pattern'  => '/auth_api',
                'must_get' => [],
            ]),
            $makeRoute([
                'type'     => 'active',
                'prefix'   => 'auth_api',
                'model'    => 'User_Controller',
                'method'   => 'authApi',
                'pattern'  => '{%auth_api=%}{%&api=%}',
                'must_get' => ['auth_api'],
            ]),
            $makeRoute([
                'type'           => 'active',
                'model'          => 'Comment_Controller',
                'method'         => 'addComment',
                'pattern'        => '{%action=addcom}',
                'must_get'       => ['action' => 'addcom'],
                'request_method' => 'POST',
            ]),
            $makeRoute([
                'type'           => 'active',
                'model'          => 'Like_Controller',
                'method'         => 'index',
                'pattern'        => '{%action=%}',
                'must_get'       => ['action'],
                'request_method' => 'POST',
            ]),
            $makeRoute([
                'type'           => 'active',
                'prefix'         => 'plugin',
                'model'          => 'Plugin_Controller',
                'method'         => 'loadPluginShow',
                'pattern'        => $isRewrite ? '/plugin/{%plugin%}' : '?plugin={%plugin%}',
                'must_get'       => $isRewrite ? [] : ['plugin'],
                'request_method' => '',
            ]),
            $makeRoute([
                'type'     => 'active',
                'model'    => 'Api_Controller',
                'method'   => 'starter',
                'pattern'  => '{%rest-api=%}',
                'must_get' => ['rest-api'],
            ]),
            $makeRoute([
                'type'     => 'active',
                'model'    => 'Download_Controller',
                'method'   => 'index',
                'pattern'  => '{%resource_alias=%}',
                'must_get' => ['resource_alias'],
            ]),
        ];

        // 动态模式下，文章 query 规则需提升为活动路由（空 path 时 query 内容路由会被跳过）
        if (!$isRewrite && strpos($routeRules['rule_article'], '?') !== false) {
            $articleGet = $getParams($routeRules['rule_article']);
            $articleMustGet = [];
            foreach ($articleGet as $key => $value) {
                if ($value === null) {
                    $articleMustGet[] = $key;
                }
            }
            if ($articleMustGet === []) {
                $articleMustGet = array_keys($articleGet);
            }
            array_unshift($activeRoutes, $makeRoute([
                'type'     => 'active',
                'model'    => 'Log_Controller',
                'method'   => 'displayContent',
                'pattern'  => $routeRules['rule_article'],
                'must_get' => $articleMustGet,
                'args'     => ['id', 'alias', 'page'],
            ]));
        }

        // —— 内容路由（随 linkmode 切换 path / query 规则）——
        $contentRoutes = [
            $makeRoute([
                'type'    => $isRewrite ? 'rewrite' : 'query',
                'model'   => 'Tag_Controller',
                'method'  => 'display',
                'pattern' => $routeRules['rule_tag'],
                'args'    => ['tag&id', 'tag@alias', 'page'],
            ]),
            $makeRoute([
                'type'    => $isRewrite ? 'rewrite' : 'query',
                'model'   => 'Log_Controller',
                'method'  => 'displayContent',
                'pattern' => $routeRules['rule_article'],
                'args'    => ['id', 'alias', 'page'],
            ]),
            $makeRoute([
                'type'    => $isRewrite ? 'rewrite' : 'query',
                'model'   => 'Sort_Controller',
                'method'  => 'display',
                'pattern' => $routeRules['rule_category'],
                'args'    => ['category&id', 'category@alias', 'page'],
            ]),
            $makeRoute([
                'type'     => $isRewrite ? 'rewrite' : 'query',
                'model'    => 'Log_Controller',
                'method'   => 'display',
                'pattern'  => $routeRules['rule_index'],
                'args_get' => ['page' => 1],
                'args'     => ['page'],
            ]),
            // 归档须在页面规则前：{%alias%}.html 会误匹配 date-20260705.html
            $makeRoute([
                'type'    => $isRewrite ? 'rewrite' : 'query',
                'model'   => 'Record_Controller',
                'method'  => 'display',
                'pattern' => $routeRules['rule_record'],
                'args'    => ['record', 'page'],
            ]),
            $makeRoute([
                'type'    => $isRewrite ? 'rewrite' : 'query',
                'model'   => 'Log_Controller',
                'method'  => 'displayContent',
                'pattern' => $routeRules['rule_page'],
                'args'    => ['page&id', 'page@alias', 'comment-page'],
                'request_method' => '',
            ]),
        ];

        $defaultRoute = $makeRoute([
            'type'    => 'default',
            'model'   => 'Log_Controller',
            'method'  => 'display',
            'pattern' => '/',
        ]);

        // 活动路由（含 rest-api）须在 default 之前；内容路由也须在 default 之前，否则 ?category=1 等会被首页路由抢走
        $cachedTable = array_merge($activeRoutes, $contentRoutes, [$defaultRoute]);
        doMultiAction('routing_register', $cachedTable, $cachedTable);
        $cachedSignature = $rulesSignature;
        $cacheVersion = self::$_routingCacheGen;

        return $cachedTable;
    }

    /**
     * 路由表（getEnhancedRoutingTable 别名）
     */
    static function getRoutingTable() {
        return self::getEnhancedRoutingTable();
    }

    static function getAll() {
        $CACHE = Cache::getInstance();
        $options_cache = $CACHE->readCache('options');
        $options_cache['site_title'] = $options_cache['site_title'] ?? $options_cache['sitename'] ?? '';
        $options_cache['site_description'] = $options_cache['site_description'] ?? $options_cache['siteinfo'] ?? '';
        if (isset($options_cache['footer_info'])) {
            $options_cache['footer_info'] = stripslashes((string)$options_cache['footer_info']);
        }
        return $options_cache;
    }

    static function getAttType() {
        return explode(',', self::get('att_type'));
    }

    static function getAttMaxSize() {
        return self::get('att_maxsize') * 1024;
    }

    static function getAdminAttType() {
        return [
            'rar', 'zip', '7z', 'gz',
            'gif', 'jpg', 'jpeg', 'png', 'webp',
            'txt', 'pdf', 'docx', 'doc', 'xls', 'xlsx', 'key', 'ppt', 'pptx',
            'mp4', 'mp3', 'mkv', 'webm', 'avi',
        ];
    }

    static function getAdminAttMaxSize() {
        return 2097152 * 1024;
    }

    static function getDefPlugin() {
        return ['tips/tips.php'];
    }

    static function updateOption($name, $value, $isSyntax = false) {
        $DB = Database::getInstance();
        if (!$isSyntax) {
            $value = $DB->escape_string($value);
        }
        $sql = 'INSERT INTO ' . DB_PREFIX . "options (option_name, option_value) VALUES ('".$DB->escape_string($name)."', '$value') ON DUPLICATE KEY UPDATE option_value='$value', option_name='".$DB->escape_string($name)."'";
        $DB->query($sql);
    }
}