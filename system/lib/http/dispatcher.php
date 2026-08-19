<?php
/**
 * Hope CMS(希望CMS) 
 * @author LinZiChen <271106735@qq.com>
 * @link http://www.hopecms.cn
 * @copyright 2027 Hope CMS All rights reserved.
 * @license http://www.apache.org/licenses/LICENSE-2.0
 */

class Dispatcher {

    static $_instance;

    /**
     * Request module
     */
    private $_model = '';

    /**
     * Request module method
     */
    private $_method = '';

    /**
     * Request parameters
     */
    private $_params;

    /**
     * Routing table
     */
    private $_routingTable;

    /**
     * path
     */
    private $_path;

    /**
     * Compiled route patterns for faster matching
     */
    private $_compiledRoutes = [];

    /**
     * Route cache key
     */
    private $_cacheKey;

    /**
     * Regex validation cache to avoid repeated checks
     */
    private $_regexValidationCache = [];

    public static function getInstance() {
        if (!self::$_instance instanceof self) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function __construct() {
        $this->_path = $this->setPath();
        $this->_routingTable = Option::getRoutingTable();
        
        // 执行路由匹配
        $this->performRouteMatching();
    }

    public function dispatch() {
        hope_check_site_closed($this->_model);

        doAction('route_dispatch', $this->_model, $this->_method, $this->_params);

        $resolvedModel = $this->resolveModelClassName($this->_model);
        if ($resolvedModel === '') {
            show_404_page();
        }

        $this->_model = $resolvedModel;
        $module = new $this->_model();
        if (!method_exists($module, $this->_method)) {
            show_404_page();
        }

        $module->{$this->_method}($this->_params);
    }

    /**
     * 预编译路由规则以提高匹配效率
     */
    private function compileRoutes() {
        $this->_compiledRoutes = [];
        foreach ($this->_routingTable as $index => $route) {
            if (!isset($route['pattern'])) {
                error_log("Route missing pattern at index $index");
                continue;
            }
            $pattern = $route['pattern'];
            if (!$this->validatePattern($pattern)) {
                error_log("Invalid pattern skipped: $pattern");
                continue;
            }
            $regex = $this->convertPatternToRegex($pattern);
            if ($regex && $this->isValidRegex($regex)) {
                $this->_compiledRoutes[$index] = [
                    'regex' => $regex,
                    'route' => $route,
                    'priority' => $this->calculateRoutePriority($route),
                    'compiled_at' => time()
                ];
            } else {
                error_log("Failed to compile regex for pattern: $pattern");
            }
        }
        // Sort by priority once compiled
        uasort($this->_compiledRoutes, function($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });
    }

    /**
     * 验证路由模式是否有效
     */
    private function validatePattern($pattern) {
        // Check for balanced braces
        $open = substr_count($pattern, '{');
        $close = substr_count($pattern, '}');
        return $open === $close && !preg_match('/\{\s*\}/', $pattern); // No empty braces
    }

    /**
     * 验证正则表达式是否有效
     */
    private function isValidRegex($regex) {
        if (!is_string($regex) || $regex === '') {
            return false;
        }
        if (array_key_exists($regex, $this->_regexValidationCache)) {
            return $this->_regexValidationCache[$regex];
        }
        // 使用 preg_match 测试，但捕获错误
        $test = @preg_match($regex, '');
        if ($test === false) {
            $error = preg_last_error();
            error_log("Regex compilation error: " . $error . " for " . $regex);
            $this->_regexValidationCache[$regex] = false;
            return false;
        }
        $this->_regexValidationCache[$regex] = true;
        return true;
    }

    /**
     * 将路由pattern转换为正则表达式
     */
    private function convertPatternToRegex($pattern) {
        try {
            $pattern = trim((string)$pattern);
            if ($pattern === '') {
                return '';
            }
            $pattern = str_replace('{%host%}', '', $pattern);
            if (($qPos = strpos($pattern, '?')) !== false) {
                $pattern = substr($pattern, 0, $qPos);
            }
            $pattern = str_replace('index.php/', '', $pattern);
            $pattern = trim($pattern, '/');

            if ($pattern === '') {
                return '/^$/i';
            }

            $idx = 0;
            $tokens = [];
            $prepared = preg_replace_callback('/\{%(.*?)%\}|\{(\w+)(?::(\w+))?\}/', function($m) use (&$idx, &$tokens) {
                $token = "__ROUTE_VAR_" . $idx . "__";
                $idx++;
                $varName = 'arg' . $idx;
                $subPattern = '[^\/]+';

                if (!empty($m[1])) {
                    $expr = trim($m[1]);
                    if (strpos($expr, '&type=') !== false) {
                        list($varName, $type) = explode('&type=', $expr, 2);
                        $varName = trim($varName);
                        switch (strtolower(trim($type))) {
                            case 'int':
                            case 'num':
                                $subPattern = '\d+';
                                break;
                            case 'alpha':
                                $subPattern = '[a-zA-Z]+';
                                break;
                            case 'alnum':
                                $subPattern = '[a-zA-Z0-9]+';
                                break;
                        }
                    } elseif (strpos($expr, '=') !== false) {
                        list($varName, ) = explode('=', $expr, 2);
                        $varName = trim($varName);
                        $subPattern = '[^\/]*';
                    } else {
                        $varName = $expr;
                    }
                } else {
                    $varName = $m[2];
                    $type = strtolower($m[3] ?? '');
                    if ($type === 'num' || $type === 'int') {
                        $subPattern = '\d+';
                    }
                }

                if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $varName)) {
                    $varName = 'arg' . $idx;
                }

                $tokens[$token] = "(?P<{$varName}>{$subPattern})";
                return $token;
            }, $pattern);

            $regex = preg_quote($prepared, '/');
            foreach ($tokens as $token => $replacement) {
                $regex = str_replace(preg_quote($token, '/'), $replacement, $regex);
            }
            return '/^' . $regex . '$/i';
        } catch (Exception $e) {
            error_log("Error converting pattern to regex: $pattern - " . $e->getMessage());
            return null;
        }
    }

    /**
     * 计算单个路由的优先级
     */
    private function calculateRoutePriority($route) {
        $pattern = $route['pattern'] ?? '';
        if (empty($pattern)) return 1;

        $priority = 0;

        // Exact match if no placeholders
        if (!preg_match('/\{[^}]+\}/', $pattern)) {
            $priority += 100;
        }

        // Static segments (higher for more fixed parts)
        $staticSegments = preg_split('/\{[^}]+\}/', $pattern);
        $staticCount = count(array_filter($staticSegments, 'strlen'));
        $priority += $staticCount * 10;

        // Penalize dynamic parameters
        $paramCount = preg_match_all('/\{[^}]+\}/', $pattern, $matches);
        $priority -= $paramCount * 5;

        // Bonus for shorter patterns (more specific)
        $length = strlen($pattern);
        if ($length < 20) $priority += 20;
        elseif ($length < 50) $priority += 10;

        // Z-Blog specifics: Boost for type-enforced params
        if (strpos($pattern, '&type=') !== false) $priority += 5;

        return max(1, $priority); // Minimum priority
    }

    /**
     * 执行路由匹配
     */
    private function performRouteMatching() {
        $path = trim($this->_path, '/');
        $linkmode = (string) Option::get('linkmode');
        $useRewriteRules = ($linkmode === '1' || $linkmode === '2');

        foreach ($this->_routingTable as $route) {
            $routeType = $route['type'] ?? '';
            $pattern = (string)($route['pattern'] ?? '');

            // 首页空 path：跳过 path 型伪静态内容路由；query 型路由（如 ?category=1）仍须参与匹配
            if ($path === '' || $path === 'index.php') {
                if ($routeType === 'rewrite') {
                    continue;
                }
            }

            // 动态模式跳过纯 path 伪静态规则，避免误匹配
            if (!$useRewriteRules && $routeType === 'rewrite') {
                continue;
            }
            // 伪静态模式跳过纯 query 内容路由（活动路由与 default 保留）
            if ($useRewriteRules && $routeType === 'query') {
                continue;
            }

            if (!$useRewriteRules && empty($route['get']) && $routeType === 'active') {
                $route['get'] = $this->getParams($pattern);
            }

            if ($this->tryMatchRoute($route, $path, $linkmode)) {
                return;
            }
        }

        if (empty($this->_model)) {
            show_404_page();
        }
    }

    private function getParams($pattern) {
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
                    // 占位符表达式代表“动态值”而不是固定字面量
                    // 如 {%id%} / {%id&type=num%}，应只要求参数存在（并在其它阶段做类型校验）
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

        // active 规则简写：{%author=%}{%&page=%}
        if (empty($result) && preg_match_all('/\{%\&?([A-Za-z0-9_\-]+)=/', $pattern, $m)) {
            foreach ($m[1] as $param) {
                $result[$param] = null;
            }
        }

        return $result;
    }

    /**
     * 尝试匹配单个路由
     */
    private function tryMatchRoute($route, $path, $urlMode) {
        $matches = [];
        $matched = false;
        $pattern = $route['pattern'] ?? '';


        $matched = $this->matchRouteByPattern($route, $path, $matches);

        $prefix = trim((string)($route['prefix'] ?? ''), '/');
        if ($prefix !== '') {
            if ($path === $prefix || $path === $prefix . '/index.php') {
                if (!$matched) {
                    $matched = true;
                }
                if (!isset($_GET[$prefix])) {
                    $_GET[$prefix] = '';
                }
            }
        }

        if (!$matched && isset($route['pattern']) && $route['pattern'] === '/') {
            if ($path === '' || $path === 'index.php') {
                $matched = true;
            }
        }

        if (!$matched) {
            return false;
        }

        // 验证GET参数条件
        $paramsValid = $this->validateRouteParameters($route);
        if (!$paramsValid) {
            return false;
        }

        // 设置匹配成功的路由信息
        $this->setMatchedRoute($route, $matches);
        return true;
    }

    /**
     * 按 pattern 匹配（支持 path + query 混合规则）
     */
    private function matchRouteByPattern($route, $path, &$matches) {
        $pattern = trim((string)($route['pattern'] ?? ''));
        if ($pattern === '') {
            return false;
        }

        $pattern = str_replace('{%host%}', '', $pattern);
        $pattern = str_replace('index.php/', '', $pattern);
        $queryPos = strpos($pattern, '?');
        $pathPattern = $queryPos === false ? $pattern : substr($pattern, 0, $queryPos);//静态
        $queryPattern = $queryPos === false ? '' : substr($pattern, $queryPos + 1);//动态

        $pathPattern = trim($pathPattern, '/');
        $pathMatched = false;
        $pathPatterns = [$pathPattern];

        // 可选分页占位符：同时尝试带 page / 不带 page 的路径
        $noPagePattern = $this->stripOptionalPageTokens($pathPattern);
        if ($noPagePattern !== $pathPattern) {
            $pathPatterns[] = $noPagePattern;
        }
        $pathPatterns = array_values(array_unique($pathPatterns));
        foreach ($pathPatterns as $candidatePattern) {
            if ($candidatePattern === '' || $this->isQueryOnlyPathPattern($candidatePattern)) {
                $pathMatched = ($path === '' || $path === 'index.php');
                if ($pathMatched) {
                    $matches = [0 => $path];
                    break;
                }
                continue;
            }

            $pathRegex = $this->convertPatternToRegex($candidatePattern);
            $pathMatches = [];
            $pathMatched = !empty($pathRegex) && $this->isValidRegex($pathRegex) && @preg_match($pathRegex, $path, $pathMatches);
         
            if ($pathMatched) {
                if (!empty($pathMatches)) {
                    $matches = $pathMatches;
                }
                // 无分页版本命中时补 page=1
                if ($candidatePattern === $noPagePattern && $candidatePattern !== $pathPattern && !isset($matches['page'])) {
                    $matches['page'] = '1';
                    $_GET['page'] = $_GET['page'] ?? '1';
                }
                break;
            }
        }
        if (!$pathMatched) {
            return false;
        }
        
        if (!$this->matchQueryPattern($queryPattern, $matches)) {
            return false;
        }

        if (!isset($matches[0])) {
            $matches[0] = $path;
        }
        return true;
    }

    /**
     * 纯 query 占位路由（如 {%action=%}）不得占用 /user、/login 等 path
     */
    private function isQueryOnlyPathPattern($pathPattern) {
        $pathPattern = trim((string)$pathPattern);
        if ($pathPattern === '') {
            return true;
        }
        $stripped = preg_replace('/\{%.*?%\}/', '', $pathPattern);
        $stripped = preg_replace('/\{[^}]+\}/', '', $stripped);
        $stripped = str_replace(['&', '?', '='], '', $stripped);
        return trim($stripped) === '';
    }

    /**
     * 去掉规则中的可选分页片段，便于同时匹配带分页与不带分页的 URL
     */
    private function stripOptionalPageTokens($pattern) {
        $pattern = (string)$pattern;
        $pattern = str_replace(
            ['_{%page%}', '/{%page%}', '-{%page%}', '={%page%}', '{%page%}', '{%&page%}', '{%&page=%}'],
            ['', '', '', '', '', '', ''],
            $pattern
        );
        $pattern = str_replace('//', '/', $pattern);
        return trim($pattern);
    }

    /**
     * 校验并提取 pattern 中的 query 规则
     */
    private function matchPatternQueryPart($pattern, &$matches) {
        $pattern = (string)$pattern;
        if (strpos($pattern, '?') === false) {
            return true;
        }
        $queryPattern = substr($pattern, strpos($pattern, '?') + 1);
        return $this->matchQueryPattern($queryPattern, $matches);
    }

    /**
     * 
     * 
     * 
     */
    private function matchQueryPattern($queryPattern, &$matches) {
        $queryPattern = trim((string)$queryPattern, " \t\n\r\0\x0B&?");
        if ($queryPattern === '') {
            return true;
        }

        $pairs = array_values(array_filter(explode('&', $queryPattern), 'strlen'));
        foreach ($pairs as $pair) {
            if ($pair === '') {
                continue;
            }
            $eqPos = strpos($pair, '=');
            if ($eqPos === false) {
                if (!isset($_GET[$pair])) {
                    return false;
                }
                continue;
            }
            $key = substr($pair, 0, $eqPos);
            $valuePattern = substr($pair, $eqPos + 1);
            if ($key === '') {
                return false;
            }
            if (!isset($_GET[$key])) {
                // 复合 query 规则里，分页等占位符可省略；单独 ?page= 规则必须显式传参
                if (preg_match('/^\{%(.+)%\}$/', $valuePattern, $m)) {
                    $hasOtherQueryParam = false;
                    foreach ($pairs as $otherPair) {
                        $otherEqPos = strpos($otherPair, '=');
                        if ($otherEqPos === false) {
                            continue;
                        }
                        $otherKey = substr($otherPair, 0, $otherEqPos);
                        if ($otherKey !== '' && $otherKey !== $key && isset($_GET[$otherKey])) {
                            $hasOtherQueryParam = true;
                            break;
                        }
                    }
                    if (!$hasOtherQueryParam) {
                        return false;
                    }
                    $expr = trim($m[1]);
                    $name = $expr;
                    $default = null;
                    if (strpos($expr, '&type=') !== false) {
                        list($name, ) = explode('&type=', $expr, 2);
                        $name = trim($name);
                    } elseif (strpos($expr, '=') !== false) {
                        list($name, $default) = explode('=', $expr, 2);
                        $name = trim($name);
                        $default = trim($default);
                    }
                    if ($name !== '') {
                        $fallback = $default !== null && $default !== '' ? $default : ($name === 'page' ? '1' : null);
                        if ($fallback !== null) {
                            $matches[$name] = $fallback;
                            $_GET[$name] = $fallback;
                        }
                    }
                    continue;
                }
                return false;
            }
            $actual = (string)$_GET[$key];

            if (preg_match('/^\{%(.+)%\}$/', $valuePattern, $m)) {
                $expr = trim($m[1]);
                $name = $expr;
                $type = '';
                $default = null;

                if (strpos($expr, '&type=') !== false) {
                    list($name, $type) = explode('&type=', $expr, 2);
                } elseif (strpos($expr, '=') !== false) {
                    list($name, $default) = explode('=', $expr, 2);
                }
                $name = trim($name);

                if ($actual === '' && $default !== null) {
                    $actual = (string)$default;
                }
                if (!$this->validatePlaceholderType($actual, $type)) {
                    return false;
                }
                if ($name !== '') {
                    $matches[$name] = $actual;
                    $_GET[$name] = $actual;
                }
                continue;
            }

            if ((string)$valuePattern !== $actual) {
                return false;
            }
        }

        return true;
    }

    private function validatePlaceholderType($value, $type) {
        $type = strtolower(trim((string)$type));
        if ($type === '') {
            return true;
        }
        switch ($type) {
            case 'int':
            case 'num':
                return (bool)preg_match('/^\d+$/', $value);
            case 'alpha':
                return (bool)preg_match('/^[a-zA-Z]+$/', $value);
            case 'alnum':
                return (bool)preg_match('/^[a-zA-Z0-9]+$/', $value);
            default:
                return true;
        }
    }

    /**
     * 验证路由参数条件
     */
    private function validateRouteParameters($route) {
        $getParams = $route['get'] ?? [];
        $notGetParams = $route['not_get'] ?? [];
        $mustGetParams = $route['must_get'] ?? [];
        $requestMethod = $route['request_method'] ?? '';
        $routeType = $route['type'] ?? '';

        // 动态路由未显式配置 get 时，按规则自动提取 query 参数约束
        if ($routeType === 'active' && empty($getParams)) {
            $getParams = $this->getParams($route['pattern'] ?? '');
        }

        // 检查不可存在的参数
        foreach ($notGetParams as $param) {
            if (isset($_GET[$param])) {
                return false;
            }
        }

        // 检查必需的参数
        foreach ($mustGetParams as $param => $value) {
            if (is_numeric($param)) { // 仅检查参数存在性
                if (!isset($_GET[$value])) {
                    return false;
                }
            } else { // 检查参数及值
                if (!isset($_GET[$param]) || $_GET[$param] != $value) {
                    return false;
                }
            }
        }

        // 检查可选参数
        foreach ($getParams as $param => $value) {
            if (is_numeric($param)) {
                if (!isset($_GET[$value])) {
                    return false;
                }
                continue;
            }
            // 占位符未传参时视为可选（如 {%&page=%}、{%&param=%}）
            if ($value === null) {
                continue;
            }
            if (isset($_GET[$param]) && (string)$_GET[$param] !== (string)$value) {
                return false;
            }
        }

        // 检查请求方法
        if (!$this->checkRequestMethod($requestMethod)) {
            return false;
        }

        return true;
    }

    /**
     * 设置匹配成功的路由信息
     */
    private function setMatchedRoute($route, $matches) {
        $this->_model = $this->resolveModelClassName($route['model'] ?? '');
        $this->_method = $route['method'];

        // 处理路由参数
        $params = [];
        
        // Extract named groups from regex matches
        foreach ($matches as $key => $value) {
            if (is_string($key) && ctype_alnum(str_replace('_', '', $key))) { // Valid key
                $params[$key] = $value;
                $_GET[$key] = $value; // Safe set
            }
        }
        $hasNamedMatches = !empty($params);

        // 处理args映射
        if (isset($route['args']) && is_array($route['args'])) {
            foreach ($route['args'] as $matchIndex => $paramName) {
                $value = $this->resolveRouteArgMatchValue($matches, $matchIndex);
                if (strpos($paramName, '&') !== false) {
                    // e.g., 'blog&id' -> set both
                    $parts = explode('&', $paramName);
                    foreach ($parts as $part) {
                        if (ctype_alnum(str_replace('_', '', $part)) && (!$hasNamedMatches || !array_key_exists($part, $params))) {
                            $_GET[$part] = $value;
                            $params[$part] = $value;
                        }
                    }
                } elseif (strpos($paramName, '@') !== false) {
                    // e.g., 'category@alias' -> set 'category'
                    $primary = explode('@', $paramName)[0];
                    if (ctype_alnum(str_replace('_', '', $primary)) && (!$hasNamedMatches || !array_key_exists($primary, $params))) {
                        $_GET[$primary] = $value;
                        $params[$primary] = $value;
                    }
                } elseif (ctype_alnum(str_replace('_', '', $paramName)) && (!$hasNamedMatches || !array_key_exists($paramName, $params))) {
                    $_GET[$paramName] = $value;
                    $params[$paramName] = $value;
                }
            }
        } else {
            // 直接使用匹配结果
            $params = $matches;
        }

        // 处理额外的GET参数
        if (isset($route['args_get']) && is_array($route['args_get'])) {
            foreach ($route['args_get'] as $key => $value) {
                if (isset($_GET[$key])) {
                    $params[$key] = $_GET[$key];
                }
            }
        }

        // 处理with参数
        if (isset($route['args_with']) && is_array($route['args_with'])) {
            foreach ($route['args_with'] as $key => $value) {
                if (is_callable($value)) {
                    $params[$key] = $value($route);
                } else {
                    $params[$key] = $value;
                }
            }
        }

        // 应用路由参数处理器
        if (isset($route['processor']) && is_callable($route['processor'])) {
            $params = $route['processor']($params, $route);
        }
        $params = $this->mergeRouteQueryParams($params, $route);
        $this->_params = $params;
        // 分类别名处理
        $this->handleCategoryAlias();

        // 首页处理
        $this->handleHomePage();
    }

    /**
     * 活动路由简写（如 {%keyword=%}）不含 query 段，需从 $_GET 补全参数
     */
    private function mergeRouteQueryParams(array $params, array $route) {
        $keys = [];
        foreach ((array)($route['get'] ?? []) as $key => $value) {
            $keys[] = is_numeric($key) ? (string)$value : (string)$key;
        }
        foreach ((array)($route['must_get'] ?? []) as $key => $value) {
            $keys[] = is_numeric($key) ? (string)$value : (string)$key;
        }
        foreach (array_unique(array_filter($keys, 'strlen')) as $key) {
            if (isset($_GET[$key]) && !array_key_exists($key, $params)) {
                $params[$key] = $_GET[$key];
            }
        }
        return $params;
    }

    /**
     * 解析控制器类名（calendar / Calendar / xxx_Controller 等写法）
     */
    private function resolveModelClassName($model) {
        $model = trim((string)$model);
        if ($model === '') {
            return '';
        }

        $candidates = [];
        $candidates[] = $model;

        $normalized = str_replace('-', '_', $model);
        $segments = array_filter(explode('_', strtolower($normalized)), 'strlen');
        if (!empty($segments)) {
            $pascal = implode('_', array_map(function($seg) {
                return ucfirst($seg);
            }, $segments));
            $candidates[] = $pascal;

            if (substr(strtolower($pascal), -11) !== '_controller') {
                $candidates[] = $pascal . '_Controller';
            }
        }

        if (substr(strtolower($model), -11) !== '_controller') {
            $candidates[] = ucfirst($model) . '_Controller';
        }

        $candidates[] = ucfirst($model);
        $candidates = array_values(array_unique(array_filter($candidates, 'strlen')));

        foreach ($candidates as $candidate) {
            if (class_exists($candidate)) {
                return $candidate;
            }
        }

        return '';
    }

    /**
     * 将路由 args 映射到 preg_match 捕获组：
     * 数字下标 0/1/2… 对应第 1/2/3… 个捕获组（非完整匹配）；
     * 若存在同名命名组（id/alias/page）则优先取命名组。
     */
    private function resolveRouteArgMatchValue($matches, $matchIndex) {
        if (is_numeric($matchIndex)) {
            $namedByIndex = [0 => 'id', 1 => 'alias', 2 => 'page'];
            $idx = (int)$matchIndex;
            if (isset($namedByIndex[$idx]) && array_key_exists($namedByIndex[$idx], $matches)) {
                return $matches[$namedByIndex[$idx]];
            }
        }

        if (array_key_exists($matchIndex, $matches)) {
            // preg_match 的 0 为整段命中；args[0] 表示第一个占位符 → 捕获组 1
            if ((int)$matchIndex === 0 && array_key_exists(1, $matches)) {
                return $matches[1];
            }
            return $matches[$matchIndex];
        }

        if (is_numeric($matchIndex)) {
            $captureIndex = (int)$matchIndex + 1;
            if (array_key_exists($captureIndex, $matches)) {
                return $matches[$captureIndex];
            }
        }

        return '';
    }

    /**
     * 检查请求方法
     */
    private function checkRequestMethod($expectedMethod) {
        if (empty($expectedMethod)) {
            return true; // 如果未指定，接受任何方法
        }

        $currentMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        return strtoupper($currentMethod) === strtoupper($expectedMethod);
    }

    /**
     * 分类别名处理
     */
    private function handleCategoryAlias() {
        if ($this->_model !== 'Log_Controller' || $this->_method !== 'displayContent') {
            return;
        }

        $alias = $this->_params[1] ?? '';
        if (empty($alias) || $alias === 'post') {
            return;
        }

        $Sort_Model = new Sort_Model();
        $r = $Sort_Model->getSortByAlias($alias);
        if ($r) {
            $this->_model = 'Sort_Controller';
            $this->_method = 'display';
            $this->_params = ['/sort/' . $alias, 'sort', $alias];
            $page = $this->_params[2] ?? 0;
            if ($page) {
                $this->_params[3] = 'page/' . $page;
                $this->_params[4] = 'page';
                $this->_params[5] = $page;
            }
        }
    }

    /**
     * 首页处理
     */
    private function handleHomePage() {
        $homePageID = (int)Option::get('home_page_id');
        if ($this->_model !== 'Log_Controller' ||
            $this->_method !== 'display' ||
            $homePageID <= 0 ||
            strpos($this->_path, 'posts') !== false) {
            return;
        }
        // 带内容查询参数时保留原路由（分类、标签、分页等）
        foreach (['category', 'tags', 'tag', 'article', 'id', 'record', 'post', 'page'] as $param) {
            if (!empty($_GET[$param])) {
                return;
            }
        }
        $this->_method = 'displayContent';
        $this->_params = ['id' => $homePageID];
    }

    public static function setPath() {
        $path = '';
        if (isset($_SERVER['HTTP_X_REWRITE_URL'])) { // for iis
            $path = $_SERVER['HTTP_X_REWRITE_URL'];
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $path = $_SERVER['REQUEST_URI'];
        } elseif (isset($_SERVER['argv'])) {
            $path = $_SERVER['PHP_SELF'] . '?' . $_SERVER['argv'][0];
        } else {
            $path = $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];
        }

        // Decode URL-encoded characters
        $path = urldecode($path);

        //for iis6 path is GBK
        if (isset($_SERVER['SERVER_SOFTWARE']) && stripos($_SERVER['SERVER_SOFTWARE'], 'IIS') !== false) {
            if (function_exists('mb_convert_encoding')) {
                $path = mb_convert_encoding($path, 'UTF-8', 'GBK');
            } else {
                $path = @iconv('GBK', 'UTF-8', @iconv('UTF-8', 'GBK', $path)) == $path ? $path : @iconv('GBK', 'UTF-8', $path);
            }
        }

        // Remove fragment and query string for clean path
        $parsed = parse_url($path);
        $path = $parsed['path'] ?? '/';

        // Normalize slashes and remove trailing slash (except for root)
        $path = '/' . trim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        // Prevent path traversal
        if (strpos($path, '..') !== false || strpos($path, '\\') !== false) {
            error_log("Potential path traversal detected: $path");
            $path = '/';
        }

        //for subdirectory
        $t = parse_url(SITE_URL);
        $path = str_replace($t['path'], '/', $path);

        //for iis6
        $path = str_ireplace('index.php', '', $path);

        return $path;
    }

    /**
     * 清除路由缓存（内存代数 + 可选文件缓存）
     */
    public static function clearRouteCache() {
        Option::resetRoutingTableCache();
        $CACHE = Cache::getInstance();
        $CACHE->deleteCache('enhanced_routing');
    }

    /**
     * 添加自定义路由
     */
    public static function addCustomRoute($routeConfig) {
        // 验证路由配置
        if (!isset($routeConfig['model']) || !isset($routeConfig['method']) || !isset($routeConfig['pattern'])) {
            throw new InvalidArgumentException('Route configuration must include model, method, and pattern');
        }

        // 获取现有路由表
        $routingTable = Option::getRoutingTable();
        
        // 添加新路由
        $routingTable[] = $routeConfig;
        
        // 更新缓存
        $CACHE = Cache::getInstance();
        $CACHE->writeCache('enhanced_routing', $routingTable);
    }

    /**
     * 获取当前匹配的路由信息
     */
    public function getCurrentRouteInfo() {
        return [
            'model' => $this->_model,
            'method' => $this->_method,
            'params' => $this->_params,
            'path' => $this->_path
        ];
    }
}
