<?php
/**
 * Hope CMS 公共函数库
 * @link http://www.hopecms.cn
 */

require_once HOPE_ROOT . 'system/lib/core/hope_lang.php';
require_once HOPE_ROOT . 'system/lib/hooks.php';

function hpAutoload($class) {
    $class = strtolower($class);
    $libSubdirs = ['core', 'database', 'http', 'auth', 'mail', 'markdown', 'view', 'payment'];
    $paths = [
        HOPE_ROOT . '/system/app/model/' . $class . '.php',
        HOPE_ROOT . '/system/app/controller/' . $class . '.php',
        HOPE_ROOT . '/system/app/service/' . $class . '.php',
    ];
    foreach ($libSubdirs as $sub) {
        $paths[] = HOPE_ROOT . '/system/lib/' . $sub . '/' . $class . '.php';
    }
    $paths[] = HOPE_ROOT . '/system/lib/' . $class . '.php';
    foreach ($paths as $file) {
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
}

/**
 * Convert HTML Code
 */
function htmlClean($content, $nl2br = true)
{
    $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
    if ($nl2br) {
        $content = nl2br($content);
    }
    $content = str_replace('  ', '&nbsp;&nbsp;', $content);
    $content = str_replace("\t", '&nbsp;&nbsp;&nbsp;&nbsp;', $content);
    return $content;
}

if (!function_exists('getIp')) {
    function getIp()
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        // Check for Cloudflare
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($list[0]);
        }

        // Supports IPv4 and IPv6
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $ip = '';
        }

        return $ip;
    }
}

if (!function_exists('getUA')) {
    function getUA()
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
}

/**
 * 获取站点地址(仅限根目录脚本使用,目前仅用于首页ajax请求)
 */
function getBlogUrl()
{
    $phpself = $_SERVER['SCRIPT_NAME'] ?? '';
    if (preg_match("/^.*\//", $phpself, $matches)) {
        return 'http://' . $_SERVER['HTTP_HOST'] . $matches[0];
    } else {
        return SITE_URL;
    }
}

/**
 * 获取当前访问的base url
 */
function realUrl()
{
    static $real_url = NULL;
    if ($real_url !== NULL) {
        return $real_url;
    }

    $protocol = 'http://';
    if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || (isset($_SERVER['SERVER_PORT']) && (string)$_SERVER['SERVER_PORT'] === '443')) {
        $protocol = 'https://';
    }
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    $script_path = pathinfo($_SERVER['SCRIPT_NAME'] ?? '', PATHINFO_DIRNAME);
    $script_path = str_replace('\\', '/', (string)$script_path);
    $script_path = trim($script_path, '/.');

    $best_match = '';
    if ($script_path !== '') {
        $hope_path = rtrim(str_replace('\\', '/', HOPE_ROOT), '/') . '/';
        $path_element = explode('/', $script_path);
        $this_match = '';
        foreach ($path_element as $segment) {
            if ($segment === '') {
                continue;
            }
            $this_match .= $segment . '/';
            if (substr($hope_path, -strlen($this_match)) === $this_match) {
                $best_match = $this_match;
            }
        }
    }

    $real_url = rtrim($protocol . $host, '/') . '/';
    if ($best_match !== '') {
        $real_url = rtrim($protocol . $host, '/') . '/' . ltrim($best_match, '/');
    }
    $real_url = preg_replace('#(?<!:)//+#', '/', $real_url);
    if (substr($real_url, -1) !== '/') {
        $real_url .= '/';
    }

    return $real_url;
}


/**
 * 检查插件
 * @param string $plugin 格式: plugin_name/plugin_file.php 或 plugin_name
 */
function checkPlugin($plugin)
{
    if (!is_string($plugin)) {
        return false;
    }

    if (strpos($plugin, '/') === false && strpos($plugin, '.php') === false) {
        $plugin = $plugin . '/' . $plugin . '.php';
    }

    if (preg_match("/^[\w\-\/]+\.php$/", $plugin) && file_exists(HOPE_ROOT . '/content/plugin/' . $plugin)) {
        return true;
    }

    return false;
}

/**
 * 验证email地址格式
 */
function checkMail($email)
{
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return true;
    }

    return false;
}

/**
 * 截取编码为utf8的字符串
 *
 * @param string $strings 预处理字符串
 * @param int $start 开始处 eg:0
 * @param int $length 截取长度
 */
function subString($strings, $start, $length, $dot = '...')
{
    if (function_exists('mb_substr') && function_exists('mb_strlen')) {
        $sub_str = mb_substr($strings, $start, $length, 'UTF-8');
        return mb_strlen($sub_str, 'UTF-8') < mb_strlen($strings, 'UTF-8') ? $sub_str . $dot : $sub_str;
    } else {
        $sub_str = substr($strings, $start, $length);
        return strlen($sub_str) < strlen($strings) ? $sub_str . $dot : $sub_str;
    }
}

/**
 * 从可能包含html标记的内容中萃取纯文本摘要
 */
function extractHtmlData($data, $len)
{
    $data = subString(strip_tags($data), 0, $len + 30);
    $search = array(
        "/([\r\n])[\s]+/", // 去掉空白字符
        "/&(quot|#34);/i", // 替换 HTML 实体
        "/&(amp|#38);/i",
        "/&(lt|#60);/i",
        "/&(gt|#62);/i",
        "/&(nbsp|#160);/i",
        "/&(iexcl|#161);/i",
        "/&(cent|#162);/i",
        "/&(pound|#163);/i",
        "/&(copy|#169);/i",
        "/\"/i",
    );
    $replace = array(" ", "\"", "&", " ", " ", "", chr(161), chr(162), chr(163), chr(169), "");
    $data = trim(subString(preg_replace($search, $replace, $data), 0, $len));
    return $data;
}

/**
 * Convert file size units
 *
 * @param int $fileSize File size in bytes
 * @return string Formatted file size with units
 */
function changeFileSize($fileSize)
{
    if ($fileSize >= 1073741824) {
        $fileSize = round($fileSize / 1073741824, 2) . ' GB';
    } elseif ($fileSize >= 1048576) {
        $fileSize = round($fileSize / 1048576, 2) . ' MB';
    } elseif ($fileSize >= 1024) {
        $fileSize = round($fileSize / 1024, 2) . ' KB';
    } elseif ($fileSize == 0) {
        $fileSize = 'N/A';
    } else {
        $fileSize .= ' bytes';
    }
    return $fileSize;
}

/**
 * 获取文件名后缀
 */
function getFileSuffix($fileName)
{
    return strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
}

/**
 * 将相对路径转换为完整URL，eg：../content/uploadfile/xxx.jpeg
 */
function getFileUrl($filePath)
{
    if (defined('UPLOAD_PATH_RELATIVE') && UPLOAD_PATH_RELATIVE === true) {
        return $filePath;
    }

    if (stripos($filePath, 'http') === false) {
        if (strpos($filePath, '../') === 0) {
            $filePath = substr($filePath, 3);
        }
        return SITE_URL . $filePath;
    }
    return $filePath;
}

/**
 * 去除url的参数
 */
function rmUrlParams($url)
{
    $urlInfo = explode("?", $url);
    if (empty($urlInfo[0])) {
        return $url;
    }
    return $urlInfo[0];
}

function isImage($mimetype)
{
    if (strpos($mimetype, "image") !== false) {
        return true;
    }
    return false;
}

function isVideo($mimetypeOrName)
{
    $value = strtolower((string)$mimetypeOrName);
    if ($value === '') {
        return false;
    }
    if (strpos($value, 'video/') === 0) {
        return true;
    }
    // 兼容传入文件名：部分资源 mimetype 为 application/octet-stream
    $suffix = getFileSuffix($value);
    return in_array($suffix, ['mp4', 'webm', 'ogg', 'ogv', 'mov', 'm4v', 'avi', 'mkv', 'flv', 'wmv', '3gp'], true);
}

/**
 * 非图片附件的 Font Awesome 7 图标类名（不含 fa 前缀重复，返回如 fa-file-video）
 */
function getMediaFaIcon($filename, $mimetype = '')
{
    $filename = (string)$filename;
    $mimetype = strtolower((string)$mimetype);
    if (isImage($mimetype)) {
        return '';
    }
    if (isZip($filename)) {
        return 'fa-file-zipper';
    }
    if (isVideo($mimetype) || isVideo($filename)) {
        return 'fa-file-video';
    }
    if (isAudio($filename) || strpos($mimetype, 'audio/') === 0) {
        return 'fa-file-audio';
    }
    $suffix = getFileSuffix($filename);
    $map = [
        'pdf'  => 'fa-file-pdf',
        'doc'  => 'fa-file-word',
        'docx' => 'fa-file-word',
        'rtf'  => 'fa-file-word',
        'xls'  => 'fa-file-excel',
        'xlsx' => 'fa-file-excel',
        'csv'  => 'fa-file-csv',
        'ppt'  => 'fa-file-powerpoint',
        'pptx' => 'fa-file-powerpoint',
        'txt'  => 'fa-file-lines',
        'md'   => 'fa-file-lines',
        'log'  => 'fa-file-lines',
        'json' => 'fa-file-code',
        'xml'  => 'fa-file-code',
        'html' => 'fa-file-code',
        'htm'  => 'fa-file-code',
        'css'  => 'fa-file-code',
        'js'   => 'fa-file-code',
        'ts'   => 'fa-file-code',
        'php'  => 'fa-file-code',
        'sql'  => 'fa-file-code',
        'py'   => 'fa-file-code',
        'java' => 'fa-file-code',
        'c'    => 'fa-file-code',
        'cpp'  => 'fa-file-code',
        'h'    => 'fa-file-code',
        'svg'  => 'fa-file-image',
        'psd'  => 'fa-file-image',
        'ai'   => 'fa-file-image',
        'ttf'  => 'fa-font',
        'otf'  => 'fa-font',
        'woff' => 'fa-font',
        'woff2'=> 'fa-font',
    ];
    return $map[$suffix] ?? 'fa-file';
}

function isAudio($fileName)
{
    $suffix = getFileSuffix($fileName);
    return $suffix === 'mp3';
}

function isZip($fileName)
{
    $suffix = getFileSuffix($fileName);
    if (in_array($suffix, ['zip', 'rar', '7z', 'gz'])) {
        return true;
    }
    return false;
}

/**
 * 生成分页导航条
 * 
 * @param int $count 总记录数
 * @param int $perlogs 每页显示记录数
 * @param int $page 当前页码
 * @param string $url 页码的地址
 * @param string $anchor 锚点
 * @param int $showPages 显示页码的数量，默认为7
 * @param string $prevText 上一页按钮文字，默认为 &laquo;
 * @param string $nextText 下一页按钮文字，默认为 &raquo;
 * @return string 返回分页导航HTML代码
 */
/**
 * 分页函数
 *
 * @param int         $count    条目总数
 * @param int         $perlogs  每页条数
 * @param int         $page     当前页码
 * @param string      $url      分页 URL（支持 {%page%} 占位符）
 * @param string      $anchor   锚点
 * @param array|int|null $tpls   数组=模板分页；数字=经典 HTML 显示页数
 * @param string      $prevText 上一页文字（仅经典模式）
 * @param string      $nextText 下一页文字（仅经典模式）
 */
function generatePageUrl($url, $page)
{
    $page = (int)$page;
    if (strpos($url, '{%page%}') !== false) {
        if ($page <= 1 && class_exists('Url', false) && method_exists('Url', 'stripPagePlaceholder')) {
            return Url::stripPagePlaceholder($url);
        }
        return str_replace('{%page%}', (string)$page, $url);
    }
    return $url . $page;
}

/** 分页第 1 页链接（去掉 page 参数 / {%page%} 占位） */
function paginationHomeUrl($url)
{
    if (strpos($url, '{%page%}') !== false && class_exists('Url', false) && method_exists('Url', 'stripPagePlaceholder')) {
        return Url::stripPagePlaceholder($url);
    }
    $home = preg_replace("|[\?&/][^\./\?&=]*page[=/\-]|", "", $url);
    $home = rtrim((string)$home, '&');
    $home = str_replace('?&', '?', $home);
    if (substr($home, -1) === '?') {
        $home = substr($home, 0, -1);
    }
    return $home;
}

function pagination($count, $perlogs, $page, $url, $anchor = '', $tpls = null, $prevText = '&laquo;', $nextText = '&raquo;')
{
    $count = (int)$count;
    $perlogs = (int)$perlogs;
    $page = (int)$page;
    if ($perlogs <= 0) {
        return '';
    }

    if (is_array($tpls)) {
        return pagination_with_tpl($count, $perlogs, $page, $url, $anchor, $tpls);
    }

    $showPages = is_numeric($tpls) ? (int)$tpls : 7;
    if ($showPages < 5) {
        $showPages = 7;
    }

    $pnums = (int)ceil($count / $perlogs);
    if ($pnums <= 1) {
        return '';
    }
    if ($page < 1) {
        $page = 1;
    }
    if ($page > $pnums) {
        $page = $pnums;
    }

    $urlHome = paginationHomeUrl($url);
    $esc = static function ($href) {
        return htmlspecialchars($href, ENT_QUOTES, 'UTF-8');
    };
    $mkLink = static function ($href, $text, $title = '') use ($esc) {
        $titleAttr = $title !== '' ? ' title="' . $esc($title) . '"' : '';
        return '<li class="page-item"><a class="page-link" href="' . $esc($href) . '"' . $titleAttr . '>' . $text . '</a></li>';
    };
    $mkActive = static function ($text) {
        return '<li class="page-item active" aria-current="page"><span class="page-link">' . $text . '</span></li>';
    };
    $mkDot = '<li class="page-item disabled" aria-hidden="true"><span class="page-link page-dot">&hellip;</span></li>';

    $circle_a = 1;
    $circle_b = $pnums;
    $neighborNum = (int)floor(($showPages - 3) / 2);
    if ($neighborNum < 1) {
        $neighborNum = 1;
    }
    $minKey = $showPages;
    $showHead = false;
    $showTail = false;

    if ($pnums > $showPages) {
        $showHead = true;
        $showTail = true;
        if ($pnums >= ($showPages * 2)) {
            $neighborNum = (int)floor($showPages / 2);
        }
        if ($page < $minKey) {
            $circle_b = $minKey;
            $showHead = false;
        } elseif ($page > ($pnums - $minKey + 1)) {
            $circle_a = $pnums - $minKey + 1;
            $showTail = false;
        } else {
            $circle_a = $page - $neighborNum;
            $circle_b = $page + $neighborNum;
            if ($circle_a < 2) {
                $circle_a = 2;
            }
            if ($circle_b > $pnums - 1) {
                $circle_b = $pnums - 1;
            }
        }
    }

    $html = '';
    if ($page > 1) {
        $html .= $mkLink(generatePageUrl($url, $page - 1) . $anchor, $prevText, '上一页');
    }
    if ($showHead) {
        $html .= $mkLink($urlHome . $anchor, '1');
        $html .= $mkDot;
    }
    for ($i = $circle_a; $i <= $circle_b; $i++) {
        if ($i == $page) {
            $html .= $mkActive((string)$i);
        } elseif ($i == 1) {
            $html .= $mkLink($urlHome . $anchor, '1');
        } else {
            $html .= $mkLink(generatePageUrl($url, $i) . $anchor, (string)$i);
        }
    }
    if ($showTail) {
        $html .= $mkDot;
        $html .= $mkLink(generatePageUrl($url, $pnums) . $anchor, (string)$pnums);
    }
    if ($page < $pnums) {
        $html .= $mkLink(generatePageUrl($url, $page + 1) . $anchor, $nextText, '下一页');
    }

    return $html;
}

function pagination_with_tpl($count, $perlogs, $page, $url, $anchor, $tpls)
{
    $pnums = (int)ceil($count / $perlogs);
    if ($pnums <= 1) {
        return '';
    }

    $defaultTpls = [
        'first'  => '<a href="{url}">{num}</a>',
        'last'   => '<a href="{url}">{num}</a>',
        'prev'   => '<a href="{url}">上一页</a>',
        'next'   => '<a href="{url}">下一页</a>',
        'dot'    => '<em> ... </em>',
        'num'    => '<a href="{url}">{num}</a>',
        'active' => '<span>{num}</span>',
    ];
    $tpls = array_merge($defaultTpls, $tpls);

    $render = function ($type, $vars = []) use ($tpls) {
        $tpl = $tpls[$type] ?? '';
        if ($tpl === '') {
            return '';
        }
        if (isset($vars['url'])) {
            $vars['url'] = htmlspecialchars($vars['url'], ENT_QUOTES, 'UTF-8');
        }
        foreach ($vars as $k => $v) {
            $tpl = str_replace('{' . $k . '}', $v, $tpl);
        }
        return $tpl;
    };

    $urlHome = paginationHomeUrl($url);
    $frontContent = '';
    $paginContent = '';
    $endContent = '';
    $circle_a = 1;
    $circle_b = $pnums;
    $neighborNum = 1;
    $minKey = 4;

    if ($page >= 1 && $pnums >= 7) {
        if ($tpls['first'] !== '') {
            $frontContent .= ' ' . $render('first', ['url' => $urlHome . $anchor, 'num' => 1]);
        }
        $frontContent .= ' ' . $render('dot');
        $endContent .= ' ' . $render('dot');
        if ($tpls['last'] !== '') {
            $endContent .= ' ' . $render('last', ['url' => generatePageUrl($url, $pnums) . $anchor, 'num' => $pnums]);
        }
        if ($pnums >= 12) {
            $minKey = 7;
            $neighborNum = 3;
        }
        if ($page < $minKey) {
            $circle_b = $minKey;
            $frontContent = '';
        }
        if ($page > ($pnums - $minKey + 1)) {
            $circle_a = $pnums - $minKey + 1;
            $endContent = '';
        }
        if ($page > ($minKey - 1) && $page < ($pnums - $minKey + 2)) {
            $circle_a = $page - $neighborNum;
            $circle_b = $page + $neighborNum;
        }
        if ($page != 1 && $tpls['prev'] !== '') {
            $frontContent = ' ' . $render('prev', ['url' => generatePageUrl($url, $page - 1) . $anchor]) . $frontContent;
        }
        if ($page != $pnums && $tpls['next'] !== '') {
            $endContent .= ' ' . $render('next', ['url' => generatePageUrl($url, $page + 1) . $anchor]);
        }
    } elseif ($page > 1 && $tpls['prev'] !== '') {
        $frontContent .= ' ' . $render('prev', ['url' => generatePageUrl($url, $page - 1) . $anchor]);
    }

    for ($i = $circle_a; $i <= $circle_b; $i++) {
        if ($i == $page) {
            $paginContent .= ' ' . $render('active', ['num' => $i]);
        } elseif ($i == 1) {
            $paginContent .= ' ' . $render('num', ['url' => $urlHome . $anchor, 'num' => 1]);
        } else {
            $paginContent .= ' ' . $render('num', ['url' => generatePageUrl($url, $i) . $anchor, 'num' => $i]);
        }
    }

    $re = $frontContent . $paginContent . $endContent;
    if ($page < $pnums && $tpls['next'] !== '' && $pnums < 7) {
        $re .= ' ' . $render('next', ['url' => generatePageUrl($url, $page + 1) . $anchor]);
    }
    return $re;
}

/**
 * 该函数在插件中调用,挂载插件函数到预留的钩子上
 * @see HopeHooks 挂载点常量定义（system/lib/hooks.php）
 */
function addAction($hook, $actionFunc)
{
    global $hopeHooks;
    if (!isset($hopeHooks[$hook])) {
        $hopeHooks[$hook] = [];
    }
    if (!in_array($actionFunc, $hopeHooks[$hook], true)) {
        $hopeHooks[$hook][] = $actionFunc;
    }
    return true;
}

/**
 * 从挂载点移除回调
 */
function removeAction($hook, $actionFunc)
{
    global $hopeHooks;
    if (empty($hopeHooks[$hook])) {
        return false;
    }
    $key = array_search($actionFunc, $hopeHooks[$hook], true);
    if ($key === false) {
        return false;
    }
    unset($hopeHooks[$hook][$key]);
    $hopeHooks[$hook] = array_values($hopeHooks[$hook]);
    return true;
}

/**
 * 挂载点是否已注册回调
 */
function hasAction($hook, $actionFunc = null)
{
    global $hopeHooks;
    if ($actionFunc === null) {
        return !empty($hopeHooks[$hook]);
    }
    return isset($hopeHooks[$hook]) && in_array($actionFunc, $hopeHooks[$hook], true);
}

/**
 * 挂载执行方式1（插入式挂载）：执行挂在钩子上的函数,支持多参数 eg:doAction('post_comment', $author, $email, $url, $comment);
 * eg：在挂载点插入扩展内容
 */
function doAction($hook)
{
    global $hopeHooks;
    $args = array_slice(func_get_args(), 1);
    if (isset($hopeHooks[$hook])) {
        foreach ($hopeHooks[$hook] as $function) {
            call_user_func_array($function, $args);
        }
    }
}

/**
 * 挂载执行方式2（单次接管式挂载）：执行挂在钩子上的第一个函数,仅执行行一次，接收输入input，且会修改传入的变量$ret
 * eg：接管文件上传函数，将上传本地改为上传云端
 */
function doOnceAction($hook, $input, &$ret)
{
    global $hopeHooks;
    $args = [$input, &$ret];
    $func = !empty($hopeHooks[$hook][0]) ? $hopeHooks[$hook][0] : '';
    if ($func) {
        call_user_func_array($func, $args);
    }
}

/**
 * 挂载执行方式3（轮流接管式挂载）：执行挂在钩子上的所有函数，上一个执行结果作为下一个的输入，且会修改传入的变量$ret
 * eg：不同插件对文章内容进行不同的修改替换。
 */
function doMultiAction($hook, $input, &$ret)
{
    global $hopeHooks;
    $args = [$input, &$ret];
    if (isset($hopeHooks[$hook])) {
        foreach ($hopeHooks[$hook] as $function) {
            call_user_func_array($function, $args);
            $args = [&$ret, &$ret];
        }
    }
}

/**
 * 截取文章内容前len个字符
 */
function subContent($content, $len, $clean = 0)
{
    if ($clean) {
        $content = strip_tags($content);
    }
    return subString($content, 0, $len);
}

/**
 * 时间转化函数
 * @param $timestamp int 时间戳(秒)
 * @param $format
 * @return false|string
 */
function smartDate($timestamp, $format = 'Y-m-d H:i')
{
    $sec = time() - $timestamp;
    if ($sec < 60) {
        $op = $sec . ' 秒前';
    } elseif ($sec < 3600) {
        $op = floor($sec / 60) . " 分钟前";
    } elseif ($sec < 3600 * 24) {
        $op = floor($sec / 3600) . " 小时前";
    } elseif ($sec < 3600 * 24 * 30) {
        $days = floor($sec / (3600 * 24));
        $op = $days . " 天前";
    } elseif ($sec < 3600 * 24 * 365) {
        $months = floor($sec / (3600 * 24 * 30));
        $op = $months . " 个月前";
    } elseif ($sec < 3600 * 24 * 365 * 5) {
        $years = floor($sec / (3600 * 24 * 365));
        $op = $years . " 年前";
    } else {
        $op = date($format, $timestamp);
    }
    return $op;
}

/**
 * 应用中心更新时间展示（支持时间戳、毫秒、字符串）
 */
function formatApplyUpdateTime($time, $format = 'Y-m-d H:i')
{
    if ($time === null || $time === '') {
        return '-';
    }
    if (is_numeric($time)) {
        $ts = (int)$time;
        if ($ts > 9999999999) {
            $ts = (int)($ts / 1000);
        }
        return smartDate($ts, $format);
    }
    $ts = strtotime($time);
    if ($ts) {
        return smartDate($ts, $format);
    }
    return htmlspecialchars((string)$time, ENT_QUOTES, 'UTF-8');
}

function getRandStr($length = 12, $special_chars = true, $numeric_only = false)
{
    if ($numeric_only) {
        $chars = '0123456789';
    } else {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        if ($special_chars) {
            $chars .= '!@#$%^&*()';
        }
    }
    $randStr = '';
    $chars_length = strlen($chars);
    for ($i = 0; $i < $length; $i++) {
        $randStr .= substr($chars, hp_rand(0, $chars_length - 1), 1);
    }
    return $randStr;
}

function hp_rand($min = 0, $max = 0)
{
    if (function_exists('random_int')) {
        try {
            return random_int($min, $max);
        } catch (Exception $e) {
            // 失败时继续使用其他方法
        }
    }
    return mt_rand($min, $max);
}

/**
 * 上传文件到当前服务器
 * @param $attach array 文件FILE信息
 * @param $result array 上传结果
 */
function upload2local($attach, &$result)
{
    $fileName = $attach['name'];
    $tmpFile = $attach['tmp_name'];
    $fileSize = $attach['size'];

    $fileName = Database::getInstance()->escape_string($fileName);

    $ret = upload($fileName, $tmpFile, $fileSize);
    $success = 0;
    switch ($ret) {
        case '105':
            $message = '上传失败。文件上传目录不可写 (content/upload)';
            break;
        default:
            $message = '上传成功';
            $success = 1;
            break;
    }

    $result = [
        'success'   => $success,
        'message'   => $message,
        'url'       => $success ? getFileUrl($ret['file_path']) : '',
        'file_info' => $success ? $ret : [],
    ];
}

/**
 * 文件上传
 *
 * 返回的数组索引
 * mime_type 文件类型
 * size      文件大小(单位KB)
 * file_path 文件路径
 * width     宽度
 * height    高度
 * 可选值（仅在上传文件是图片且系统开启缩略图时起作用）
 * thum_file   缩略图的路径
 *
 * @param string $fileName 文件名
 * @param string $tmpFile 上传后的临时文件
 * @param string $fileSize 文件大小 KB
 * @return array | string 文件数据 索引
 *
 */
function upload($fileName, $tmpFile, $fileSize)
{
    $extension = getFileSuffix($fileName);
    $file_info = [];
    $file_info['file_name'] = $fileName;
    $file_info['mime_type'] = get_mimetype($extension, $tmpFile);
    $file_info['size'] = $fileSize;
    $file_info['width'] = 0;
    $file_info['height'] = 0;

    $fileName = substr(md5($fileName), 0, 4) . time() . '.' . $extension;

    // 读取、写入文件使用绝对路径，兼容API文件上传
    $uploadFullPath = Option::UPLOAD_FULL_PATH . gmdate('Ym') . '/';
    $uploadFullFile = $uploadFullPath . $fileName;
    $thumFullFile = $uploadFullPath . 'thum-' . $fileName;

    // 输出文件信息使用相对路径，兼容头像上传等业务场景
    $uploadPath = Option::UPLOAD_PATH . gmdate('Ym') . '/';
    $uploadFile = $uploadPath . $fileName;
    $thumFile = $uploadPath . 'thum-' . $fileName;

    $file_info['file_path'] = $uploadFile;
    $file_info['file_url'] = rmUrlParams(getFileUrl($uploadFile));

    if (!createDirectoryIfNeeded($uploadFullPath)) {
        return '105'; // 创建上传目录失败
    }

    doAction('attach_upload', $tmpFile);

    // 生成缩略图
    $is_thumbnail = Option::get('isthumbnail') === 'y';
    if ($is_thumbnail && resizeImage($tmpFile, $thumFullFile, Option::get('att_imgmaxw'), Option::get('att_imgmaxh'))) {
        $file_info['thum_file'] = $thumFile;
    }

    // 处理HTTP上传的文件
    if (@is_uploaded_file($tmpFile)) {
        if (@!move_uploaded_file($tmpFile, $uploadFullFile)) {
            @unlink($tmpFile);
            return '105'; //上传失败。上传目录不可写
        }
    } else {
        // 处理非HTTP上传的文件（如AI生成的图像）
        if (!@copy($tmpFile, $uploadFullFile)) {
            @unlink($tmpFile);
            return '105'; //上传失败。上传目录不可写
        }
    }

    // 提取图片宽高
    if (in_array($file_info['mime_type'], array('image/jpeg', 'image/png', 'image/gif', 'image/bmp'))) {
        $size = getimagesize($uploadFullFile);
        if ($size) {
            $file_info['width'] = $size[0];
            $file_info['height'] = $size[1];
        }
    }
    return $file_info;
}

function createDirectoryIfNeeded($path)
{
    if (!is_dir($path)) {
        if (!mkdir($path, 0777, true) && !is_dir($path)) {
            return false;
        }
    }
    return true;
}

/**
 * 图片生成缩略图
 *
 * @param string $img 预缩略的图片
 * @param string $thum_path 生成缩略图路径
 * @param int $max_w 缩略图最大宽度 px
 * @param int $max_h 缩略图最大高度 px
 * @return bool
 */
function resizeImage($img, $thum_path, $max_w, $max_h)
{
    if (!in_array(getFileSuffix($thum_path), array('jpg', 'png', 'jpeg', 'gif'))) {
        return false;
    }
    if (!function_exists('ImageCreate')) {
        return false;
    }

    $size = chImageSize($img, $max_w, $max_h);
    $newwidth = $size['w'];
    $newheight = $size['h'];
    $w = $size['rc_w'];
    $h = $size['rc_h'];
    if ($w <= $max_w && $h <= $max_h) {
        return false;
    }
    return imageCropAndResize($img, $thum_path, 0, 0, 0, 0, $newwidth, $newheight, $w, $h);
}

/**
 * 裁剪、缩放图片
 *
 * @param string $src_image 原始图
 * @param string $dst_path 裁剪后的图片保存路径
 * @param int $dst_x 新图坐标x
 * @param int $dst_y 新图坐标y
 * @param int $src_x 原图坐标x
 * @param int $src_y 原图坐标y
 * @param int $dst_w 新图宽度
 * @param int $dst_h 新图高度
 * @param int $src_w 原图宽度
 * @param int $src_h 原图高度
 */
function imageCropAndResize($src_image, $dst_path, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h)
{
    if (!function_exists('imagecreatefromstring')) {
        return false;
    }

    $src_img = imagecreatefromstring(file_get_contents($src_image));
    if (!$src_img) {
        return false;
    }

    if (function_exists('imagecopyresampled')) {
        $new_img = imagecreatetruecolor($dst_w, $dst_h);
        imagecopyresampled($new_img, $src_img, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
    } elseif (function_exists('imagecopyresized')) {
        $new_img = imagecreate($dst_w, $dst_h);
        imagecopyresized($new_img, $src_img, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
    } else {
        return false;
    }

    switch (getFileSuffix($dst_path)) {
        case 'png':
            if (function_exists('imagepng') && imagepng($new_img, $dst_path)) {
                ImageDestroy($new_img);
                return true;
            }
            return false;
        case 'jpg':
        default:
            if (function_exists('imagejpeg') && imagejpeg($new_img, $dst_path)) {
                ImageDestroy($new_img);
                return true;
            }
            return false;
        case 'gif':
            if (function_exists('imagegif') && imagegif($new_img, $dst_path)) {
                ImageDestroy($new_img);
                return true;
            }
            return false;
    }
}

/**
 * 按比例计算图片缩放尺寸
 *
 * @param string $img 图片路径
 * @param int $max_w 最大缩放宽
 * @param int $max_h 最大缩放高
 * @return array
 */
function chImageSize($img, $max_w, $max_h)
{
    $size = @getimagesize($img);
    if (!$size) {
        return [];
    }
    $w = $size[0];
    $h = $size[1];
    //计算缩放比例
    @$w_ratio = $max_w / $w;
    @$h_ratio = $max_h / $h;
    //决定处理后的图片宽和高
    if (($w <= $max_w) && ($h <= $max_h)) {
        $tn['w'] = $w;
        $tn['h'] = $h;
    } else if (($w_ratio * $h) < $max_h) {
        $tn['h'] = ceil($w_ratio * $h);
        $tn['w'] = $max_w;
    } else {
        $tn['w'] = ceil($h_ratio * $w);
        $tn['h'] = $max_h;
    }
    $tn['rc_w'] = $w;
    $tn['rc_h'] = $h;
    return $tn;
}

/**
 * 解析用户头像地址（支持本地上传路径与 QQ 等外链）
 */
if (!function_exists('hope_avatar_url')) {
    function hope_avatar_url($avatar, $default = '') {
        if ($avatar === '' || $avatar === null) {
            return $default;
        }
        $avatar = str_replace('\\', '/', (string)$avatar);
        if (strpos($avatar, 'http') === 0) {
            return $avatar;
        }
        return SITE_URL . ltrim(str_replace('../', '', $avatar), '/');
    }
}

/**
 * 获取Gravatar头像
 */
if (!function_exists('getGravatar')) {
    function getGravatar($email, $s = 120)
    {
        $hash = md5($email);
        $gravatar_url = "//cravatar.cn/avatar/$hash?s=$s";
        doOnceAction('get_Gravatar', $email, $gravatar_url);

        return $gravatar_url;
    }
}

/**
 * 获取指定月份的天数
 * @param $month string 月份 01-12
 * @param $year string 年份 0000
 * @return false|string
 */
function getMonthDayNum($month, $year)
{
    return date("t", strtotime($year . $month . '01'));
}

/**
 * 解压zip
 * @param string $zipfile 要解压的文件
 * @param string $path 解压到该目录
 * @param string $type
 * @return int
 */
function hpUnZip($zipfile, $path, $type = 'tpl')
{
    if (!class_exists('ZipArchive', FALSE)) {
        return 3; //zip模块问题
    }
    $zip = new ZipArchive();
    if (@$zip->open($zipfile) !== TRUE) {
        return 2; //文件权限问题
    }
    $r = explode('/', $zip->getNameIndex(0), 2);
    $dir = isset($r[0]) ? $r[0] . '/' : '';
    switch ($type) {
        case 'tpl':
            $re = $zip->getFromName($dir . 'header.php');
            if (false === $re) {
                return -2;
            }
            break;
        case 'plugin':
            $plugin_name = substr($dir, 0, -1);
            $re = $zip->getFromName($dir . $plugin_name . '.php');
            if (false === $re) {
                return -1;
            }
            break;
        case 'backup':
            $sql_name = substr($dir, 0, -1);
            if (getFileSuffix($sql_name) != 'sql') {
                return -3;
            }
            break;
        case 'update':
            break;
    }
    if (true === @$zip->extractTo($path)) {
        $zip->close();
        return 0;
    }

    return 1; //文件权限问题
}

/**
 * Zip compression
 */
function hpZip($orig_fname, $content)
{
    if (!class_exists('ZipArchive', FALSE)) {
        return false;
    }
    $zip = new ZipArchive();
    $tempzip = HOPE_ROOT . '/content/cache/emtemp.zip';
    $res = $zip->open($tempzip, ZipArchive::CREATE);
    if ($res === TRUE) {
        $zip->addFromString($orig_fname, $content);
        $zip->close();
        $zip_content = file_get_contents($tempzip);
        unlink($tempzip);
        return $zip_content;
    }

    return false;
}

/**
 * Download remote files
 * @param string $source file url
 * @return string Temporary file path
 */
function hopeFetchFile($source)
{
    $temp_file = tempnam(HOPE_ROOT . '/content/cache/', 'tmp_');
    $wh = fopen($temp_file, 'w+b');

    $ctx_opt = set_ctx_option();
    $ctx = stream_context_create($ctx_opt);
    $rh = @fopen($source, 'rb', false, $ctx);

    if (!$rh || !$wh) {
        return FALSE;
    }

    while (!feof($rh)) {
        if (fwrite($wh, fread($rh, 4096)) === FALSE) {
            return FALSE;
        }
    }
    fclose($rh);
    fclose($wh);
    return $temp_file;
}

/**
 * Download remote files
 * @param string $source file url
 * @return string Temporary file path
 */
function hpDownFile($source)
{
    $ctx_opt = set_ctx_option();
    $context = stream_context_create($ctx_opt);
    $content = file_get_contents($source, false, $context);
    if ($content === false) {
        return false;
    }

    $temp_file = tempnam(HOPE_ROOT . '/content/cache/', 'tmp_');
    if ($temp_file === false) {
        hpMsg('hpDownFile：Failed to create temporary file.');
    }
    $ret = file_put_contents($temp_file, $content);
    if ($ret === false) {
        hpMsg('hpDownFile：Failed to write temporary file.');
    }

    return $temp_file;
}

function set_ctx_option()
{
    $api_password = trim((string)Option::get('apply_bind_key'));
    return [
        'http' => [
            'timeout' => 120,
            'method'  => 'GET',
            'header'  => "Referer: " . SITE_URL . "\r\n"
                . "Api-Password: " . $api_password . "\r\n"
                . "User-Agent: HopeCMS/" . Option::HOPE_VERSION . "\r\n",
        ],
        "ssl"  => [
            "verify_peer"      => false,
            "verify_peer_name" => false,
        ]
    ];
}

/**
 * 删除文件或目录
 */
function hopeDelFile($file)
{
    if (empty($file)) {
        return false;
    }
    if (@is_file($file)) {
        return @unlink($file);
    }
    $ret = true;
    if ($handle = @opendir($file)) {
        while ($filename = @readdir($handle)) {
            if ($filename == '.' || $filename == '..') {
                continue;
            }
            if (!hopeDelFile($file . '/' . $filename)) {
                $ret = false;
            }
        }
    } else {
        $ret = false;
    }
    @closedir($handle);
    if (file_exists($file) && !rmdir($file)) {
        $ret = false;
    }
    return $ret;
}

/**
 * Hope CMS 后台页面跳转
 */
function Gohref($url)
{
    $url = trim((string)$url);
    if ($url === '') {
        $url = defined('SELF') ? SELF : './';
    }
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Location: ' . $url);
    exit;
}

/**
 * 显示系统信息
 *
 * @param string $msg 信息
 * @param string $url 返回地址
 * @param boolean $isAutoGo 是否自动返回 true false
 */
function hpMsg($msg, $url = 'javascript:history.back(-1);', $isAutoGo = false)
{
    if ($msg == '404') {
        header("HTTP/1.1 404 Not Found");
        $msg = '抱歉，你所请求的页面不存在！';
    }
    echo <<<EOT
<!doctype html>
<html lang="zh-cn">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge, chrome=1">
    <meta name="renderer" content="webkit">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="applicable-device" content="pc,mobile">
EOT;
    if ($isAutoGo) {
        echo "<meta http-equiv=\"refresh\" content=\"2;url=$url\" />";
    }
    echo <<<EOT
<title>提示信息</title>
<style>
body {
    background-color:#f8f9fc;
    font-family: Arial;
    font-size: 12px;
    line-height:150%;
}
.main {
    background-color: #ffffff;
    font-size: 14px;
    color: #333333;
    width: 650px;
    margin: 80px auto 0;
    border-radius: 8px;
    padding: 20px;
    list-style: none;
    border: 1px solid #e8e8e8;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    line-height: 1.5;
    transition: all 0.3s ease;
}

.main p {
    margin: 16px 0;
    color: #444444;
}

.main a {
    color: #1890ff;
    text-decoration: none;
    transition: color 0.3s;
}

.main a:hover {
    color: #40a9ff;
    text-decoration: underline;
}
.main p {
    line-height: 18px;
    margin: 10px 20px;
}
a {
    color: #333333;
}
@media (max-width: 768px) {
    .main{
        width: unset;   
    }
}
</style>
</head>
<body>
<div class="main">
<p>$msg</p>
EOT;
    if ($url != 'none') {
        echo '<p><a href="' . $url . '">&larr; 点击返回</a></p>';
    }
    echo <<<EOT
</div>
</body>
</html>
EOT;
    exit;
}

function show_404_page($show_404_only = false)
{
    doAction('page_not_found');
    if ($show_404_only) {
        header("HTTP/1.1 404 Not Found");
        exit;
    }

    if (is_file(THEME_PATH . '404.php')) {
        header("HTTP/1.1 404 Not Found");
        $options_cache = Option::getAll();
        extract($options_cache);
        include View::getView('404');
        exit;
    }

    hpMsg('404', SITE_URL);
}

/**
 * 检测主题是否包含 CSF 选项配置文件 options.php
 *
 * @param string $tpl 主题目录名，空则使用当前主题
 * @return string|false 存在时返回 options.php 绝对路径
 */
function is_tpl_options_exists($tpl = '')
{
    if ($tpl === '') {
        $tpl = Option::get('nonce_theme');
    }
    $tpl = preg_replace('/[^\w\-]/', '', $tpl);
    if ($tpl === '') {
        return false;
    }
    $tplsPath = defined('TPLS_PATH') ? TPLS_PATH : HOPE_ROOT . 'content/theme/';
    $file = $tplsPath . $tpl . '/options.php';
    return is_file($file) ? $file : false;
}

/**
 * hmac 加密
 *
 * @param unknown_type $algo hash算法 md5
 * @param unknown_type $data 用户名和到期时间
 * @param unknown_type $key
 * @return unknown
 */
if (!function_exists('hash_hmac')) {
    function hash_hmac($algo, $data, $key)
    {
        $packs = array('md5' => 'H32', 'sha1' => 'H40');

        if (!isset($packs[$algo])) {
            return false;
        }

        $pack = $packs[$algo];

        if (strlen($key) > 64) {
            $key = pack($pack, $algo($key));
        } elseif (strlen($key) < 64) {
            $key = str_pad($key, 64, chr(0));
        }

        $ipad = (substr($key, 0, 64) ^ str_repeat(chr(0x36), 64));
        $opad = (substr($key, 0, 64) ^ str_repeat(chr(0x5C), 64));

        return $algo($opad . pack($pack, $algo($ipad . $data)));
    }
}

/**
 * 根据文件后缀获取其MIME类型，并尝试获取文件真实类型
 * @param string $extension 文件后缀
 * @param string $filePath 文件路径（可选），如果提供则尝试读取真实MIME类型
 */
function get_mimetype($extension, $filePath = '')
{
    // 优先尝试获取真实 MIME 类型
    if ($filePath && file_exists($filePath)) {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $real_mime = finfo_file($finfo, $filePath);
                finfo_close($finfo);
                if ($real_mime) {
                    return $real_mime;
                }
            }
        } elseif (function_exists('mime_content_type')) {
            $real_mime = mime_content_type($filePath);
            if ($real_mime) {
                return $real_mime;
            }
        }
    }

    $ct['txt'] = 'text/plain';
    $ct['asc'] = 'text/plain';
    $ct['css'] = 'text/css';
    $ct['htm'] = 'text/html';
    $ct['html'] = 'text/html';
    $ct['js'] = 'application/x-javascript';
    $ct['xhtml'] = 'application/xhtml+xml';
    $ct['xml'] = 'text/xml';
    $ct['xsl'] = 'text/xml';
    $ct['wml'] = 'text/vnd.wap.wml';
    $ct['wmls'] = 'text/vnd.wap.wmlscript';
    $ct['rtf'] = 'text/rtf';
    $ct['bmp'] = 'image/bmp';
    $ct['gif'] = 'image/gif';
    $ct['jpeg'] = 'image/jpeg';
    $ct['jpg'] = 'image/jpeg';
    $ct['jpe'] = 'image/jpeg';
    $ct['png'] = 'image/png';
    $ct['webp'] = 'image/webp';
    $ct['svg'] = 'image/svg+xml';
    $ct['avif'] = 'image/avif';
    $ct['tiff'] = 'image/tiff';
    $ct['tif'] = 'image/tiff';
    $ct['ico'] = 'image/vnd.microsoft.icon';
    $ct['midi'] = 'audio/midi';
    $ct['mid'] = 'audio/midi';
    $ct['mp3'] = 'audio/mpeg';
    $ct['wav'] = 'audio/x-wav';
    $ct['mpg'] = 'video/mpeg';
    $ct['mpeg'] = 'video/mpeg';
    $ct['mpe'] = 'video/mpeg';
    $ct['qt'] = 'video/quicktime';
    $ct['webm'] = 'video/webm';
    $ct['mov'] = 'video/quicktime';
    $ct['avi'] = 'video/x-msvideo';
    $ct['wmv'] = 'video/x-ms-wmv';
    $ct['mp4'] = 'video/mp4';
    $ct['mkv'] = 'video/x-matroska';
    $ct['bin'] = 'application/octet-stream';
    $ct['class'] = 'application/octet-stream';
    $ct['dll'] = 'application/octet-stream';
    $ct['dvi'] = 'application/x-dvi';
    $ct['exe'] = 'application/octet-stream';
    $ct['gtar'] = 'application/x-gtar';
    $ct['gzip'] = 'application/x-gzip';
    $ct['pdf'] = 'application/pdf';
    $ct['doc'] = 'application/msword';
    $ct['ppt'] = 'application/vnd.ms-powerpoint';
    $ct['wbxml'] = 'application/vnd.wap.wbxml';
    $ct['wmlc'] = 'application/vnd.wap.wmlc';
    $ct['wmlsc'] = 'application/vnd.wap.wmlscriptc';
    $ct['xls'] = 'application/vnd.ms-excel';
    $ct['zip'] = 'application/zip';

    return $ct[strtolower($extension)] ?? 'text/html';
}

/**
 * 将字符串转换为时区无关的UNIX时间戳
 */
function hpStrtotime($timeStr)
{
    if (!$timeStr) {
        return false;
    }

    $timezone = Option::get('timezone');

    $unixPostDate = strtotime($timeStr);
    if (!$unixPostDate) {
        return false;
    }

    $serverTimeZone = date_default_timezone_get();
    if (empty($serverTimeZone) || $serverTimeZone == 'UTC') {
        $unixPostDate -= (int)$timezone * 3600;
    } elseif ($serverTimeZone) {
        /*
         * 如果服务器配置默认了时区，那么PHP将会把传入的时间识别为时区当地时间
         * 但是我们传入的时间实际是blog配置的时区的当地时间，并不是服务器时区的当地时间
         * 因此，我们需要将strtotime得到的时间去掉/加上两个时区的时差，得到utc时间
         */
        $offset = getTimeZoneOffset($serverTimeZone);
        // 首先减去/加上本地时区配置的时差
        $unixPostDate -= (int)$timezone * 3600;
        // 再减去/加上服务器时区与utc的时差，得到utc时间
        $unixPostDate -= $offset;
    }
    return $unixPostDate;
}

/**
 * 加载jQuery
 */
function hpLoadJQuery()
{
    static $isJQueryLoaded = false;
    if (!$isJQueryLoaded) {
        global $hopeHooks;
        if (!isset($hopeHooks['index_head'])) {
            $hopeHooks['index_head'] = [];
        }
        array_unshift($hopeHooks['index_head'], 'loadJQuery');
        $isJQueryLoaded = true;

        function loadJQuery()
        {
            echo '<script src="https://code.jquery.com/jquery-1.12.4.min.js" integrity="sha256-ZosEbRLbNQzLpnKIkEdrPv7lOy9C27hHQ+Xp8a4MxAQ=" crossorigin="anonymous"></script>';
        }
    }
}

/**
 * 计算时区的时差
 * @param string $remote_tz 远程时区
 * @param string $origin_tz 标准时区
 *
 * @throws Exception
 */
function getTimeZoneOffset($remote_tz, $origin_tz = 'UTC')
{
    if (($origin_tz === null) && !is_string($origin_tz = date_default_timezone_get())) {
        return false; // A UTC timestamp was returned -- bail out!
    }
    $origin_dtz = new DateTimeZone($origin_tz);
    $remote_dtz = new DateTimeZone($remote_tz);
    $origin_dt = new DateTime('now', $origin_dtz);
    $remote_dt = new DateTime('now', $remote_dtz);
    return $origin_dtz->getOffset($origin_dt) - $remote_dtz->getOffset($remote_dt);
}

/**
 * Upload the cut pictures (cover and avatar)
 */
function uploadCropImg()
{
    $attach = $_FILES['image'] ?? '';

    // 检查上传文件，并增加对是否为有效图片的校验
    $uploadCheckResult = Upload::checkUpload($attach, 'image');
    if ($uploadCheckResult !== true) {
        Output::error($uploadCheckResult);
    }

    $ret = '';
    upload2local($attach, $ret);
    if (empty($ret['success'])) {
        Output::error($ret['message']);
    }

    return $ret;
}

if (!function_exists('split')) {
    function split($delimiter, $str)
    {
        return explode($delimiter, $str);
    }
}

if (!function_exists('get_os')) {
    function get_os($user_agent)
    {
        if (false !== stripos($user_agent, "win")) {
            $os = 'Windows';
        } else if (false !== stripos($user_agent, "mac")) {
            $os = 'MAC';
        } else if (false !== stripos($user_agent, "linux")) {
            $os = 'Linux';
        } else if (false !== stripos($user_agent, "unix")) {
            $os = 'Unix';
        } else if (false !== stripos($user_agent, "bsd")) {
            $os = 'BSD';
        } else {
            $os = 'unknown';
        }
        return $os;
    }
}

if (!function_exists('get_browse')) {
    function get_browse($user_agent)
    {
        if (false !== stripos($user_agent, "MSIE")) {
            $br = 'MSIE';
        } else if (false !== stripos($user_agent, "Edg")) {
            $br = 'Edge';
        } else if (false !== stripos($user_agent, "Firefox")) {
            $br = 'Firefox';
        } else if (false !== stripos($user_agent, "Chrome")) {
            $br = 'Chrome';
        } else if (false !== stripos($user_agent, "Safari")) {
            $br = 'Safari';
        } else if (false !== stripos($user_agent, "Opera")) {
            $br = 'Opera';
        } else {
            $br = 'unknown';
        }
        return $br;
    }
}

// 获取内容中的第一张图片
if (!function_exists('getFirstImage')) {
    function getFirstImage($content)
    {
        // 匹配 Markdown 中的图片
        preg_match('/!\[.*?\]\((.*?)\)/', $content, $matches);

        if (!empty($matches[1])) {
            return $matches[1];
        }

        // 匹配 HTML 中的图片
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($content);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $imgNode = $xpath->query('//img')->item(0);

        if ($imgNode) {
            $src = $imgNode->getAttribute('src');
            return trim($src, '\\"'); // 清理路径中的多余字符（引号、反斜杠等）
        }

        return null;
    }
}

// 检查PHP是否支持GD图形库
function checkGDSupport()
{
    if (function_exists("gd_info") && function_exists('imagepng')) {
        return true;
    } else {
        return false;
    }
}

/**
 * UBB代码解析
 * 
 * @param string $content 包含UBB代码的内容
 * @return string 转换后的HTML内容
 */
function parseUBB($content)
{
    if (empty($content)) {
        return $content;
    }

    $ubbPatterns = array(
        // 粗体 [b]text[/b]
        '/\[b\](.*?)\[\/b\]/is' => '<b>$1</b>',
        // 颜色 [color=red]text[/color]
        '/\[color=([#\w]+)\](.*?)\[\/color\]/is' => '<span style="color:$1">$2</span>',
        // 链接 [url]http://example.com[/url]，仅允许 http/https
        '/\[url\]((?:https?:\/\/)[^\s<>"\']+)\[\/url\]/is' => '<a href="$1" target="_blank" rel="nofollow noopener noreferrer">$1</a>',
        // 图片 [img]url[/img]，仅允许 http/https
        '/\[img\]((?:https?:\/\/)[^\s<>"\']+)\[\/img\]/is' => '<img src="$1" alt="UBB图片" style="max-width:100%;height:auto;" />',
    );

    foreach ($ubbPatterns as $pattern => $replacement) {
        $content = preg_replace($pattern, $replacement, $content);
    }

    return $content;
}

/**
 * 获取目录占用大小（字节）
 * @param string $dir
 * @param array $skipNames 跳过的文件/目录名（默认跳过 .htaccess 等配置文件）
 */
function getDirSize($dir, $skipNames = null)
{
    if (!is_dir($dir)) {
        return 0;
    }
    if ($skipNames === null) {
        $skipNames = ['.htaccess', '.user.ini', 'web.config', 'Thumbs.db', '.DS_Store'];
    }
    $skipLookup = [];
    foreach ($skipNames as $name) {
        $skipLookup[strtolower((string)$name)] = true;
    }
    $handle = @opendir($dir);
    if (!$handle) {
        return 0;
    }
    $sizeResult = 0;
    while (($entry = readdir($handle)) !== false) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        if (isset($skipLookup[strtolower($entry)])) {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $entry;
        if (is_dir($path)) {
            $sizeResult += getDirSize($path, $skipNames);
        } else {
            $size = @filesize($path);
            if ($size !== false) {
                $sizeResult += (int)$size;
            }
        }
    }
    closedir($handle);
    return $sizeResult;
}

/**
 * 统一错误出口：API 返回 JSON，页面走 hpMsg
 */
function hope_strip_utf8_bom($text) {
    if (!is_string($text) || $text === '') {
        return $text;
    }
    if (strncmp($text, "\xEF\xBB\xBF", 3) === 0) {
        return substr($text, 3);
    }
    return $text;
}

function hope_abort($msg, $url = '', $httpCode = 400) {
    if (hope_is_json_request()) {
        Response::fail($msg, Response::CODE_ERROR, $httpCode);
    }
    hpMsg($msg, $url ?: 'javascript:history.back(-1);');
}

function hope_is_json_request() {
    if (isset($_GET['rest-api']) || isset($_GET['api']) || (isset($_GET['action']) && $_GET['action'] === 'api')) {
        return true;
    }
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    return strpos($accept, 'application/json') !== false;
}

function hope_client_ip() {
    $keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (empty($_SERVER[$key])) {
            continue;
        }
        $raw = trim((string)$_SERVER[$key]);
        if ($raw === '') {
            continue;
        }
        if ($key === 'HTTP_X_FORWARDED_FOR') {
            $parts = explode(',', $raw);
            $raw = trim($parts[0]);
        }
        if (filter_var($raw, FILTER_VALIDATE_IP)) {
            return $raw;
        }
    }
    return '0.0.0.0';
}

function hope_site_is_closed() {
    return Option::get('close') === 'y';
}

function hope_site_close_bypass() {
    return defined('UID') && UID > 0 && defined('ROLE') && ROLE === ROLE_ADMIN;
}

function hope_site_close_allowed_models() {
    return ['Api_Controller'];
}

function hope_check_site_closed($model = '') {
    if (!hope_site_is_closed() || hope_site_close_bypass()) {
        return;
    }
    $model = (string)$model;
    if ($model !== '' && in_array($model, hope_site_close_allowed_models(), true)) {
        return;
    }
    hope_render_site_closed_page();
}

function hope_render_site_closed_page() {
    if (ob_get_level() > 0) {
        ob_end_clean();
    }
    $title = trim((string)Option::get('close_title'));
    if ($title === '') {
        $title = Option::get('sitename') ?: '网站维护中';
    }
    $message = trim((string)Option::get('close_msg'));
    if ($message === '') {
        $message = '网站暂时关闭维护中，请稍后再访。';
    }
    http_response_code(503);
    header('Content-Type: text/html; charset=UTF-8');
    header('Retry-After: 3600');
    $titleEsc = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $msgEsc = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
    echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>' . $titleEsc . '</title>'
        . '<style>body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f8fafc;color:#334155;font-family:system-ui,sans-serif;padding:24px}'
        . '.box{max-width:520px;width:100%;background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:32px 28px;box-shadow:0 10px 30px rgba(15,23,42,.08);text-align:center}'
        . 'h1{margin:0 0 12px;font-size:1.5rem;color:#0f172a}p{margin:0;line-height:1.7;font-size:15px;color:#64748b}</style></head><body>'
        . '<div class="box"><h1>' . $titleEsc . '</h1><p>' . $msgEsc . '</p></div></body></html>';
    exit;
}

function hope_admin_token_param() {
    return 'token=' . rawurlencode(LoginAuth::genToken());
}

function hope_admin_require_token() {
    LoginAuth::checkToken();
}

function hope_is_https() {
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        return true;
    }
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
        return true;
    }
    return false;
}

function hope_api_verify_signature($reqTime, $reqSign, $reqNonce = '') {
    $apikey = Option::get('apikey');
    if ($apikey === '') {
        Output::authError('api key not configured');
    }
    if ($reqTime === '' || $reqSign === '' || !ctype_digit((string)$reqTime)) {
        Output::authError('auth param error');
    }
    $reqTime = (int)$reqTime;
    if (abs(time() - $reqTime) > 300) {
        Output::authError('request expired');
    }

    $reqNonce = trim((string)$reqNonce);
    if ($reqNonce === '' || !preg_match('/^[a-f0-9]{16,64}$/i', $reqNonce)) {
        Output::authError('nonce required');
    }
    $expected = md5($reqTime . $reqNonce . $apikey);
    if (!hash_equals($expected, (string)$reqSign)) {
        Output::authError('sign error');
    }
    hope_api_nonce_consume($reqNonce);
    return true;
}

function hope_api_nonce_consume($nonce) {
    $dir = HOPE_ROOT . 'content/cache/api_nonce';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $file = $dir . '/' . md5($nonce) . '.used';
    if (is_file($file)) {
        Output::authError('replay detected');
    }
    file_put_contents($file, (string)time(), LOCK_EX);
    if (mt_rand(1, 50) === 1) {
        foreach (glob($dir . '/*.used') ?: [] as $old) {
            if (is_file($old) && filemtime($old) < time() - 600) {
                @unlink($old);
            }
        }
    }
}

function hope_logs_dir($create = false) {
    $dir = HOPE_ROOT . 'content/logs';
    if ($create && !is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir;
}

function hope_audit_log($category, $action, $detail = [], $status = 'success', $message = '') {
    $dir = hope_logs_dir(true);
    global $user_cache;
    $username = '';
    if (defined('UID') && UID > 0 && !empty($user_cache[UID]['name'])) {
        $username = (string)$user_cache[UID]['name'];
    }
    $entry = [
        'time'     => date('Y-m-d H:i:s'),
        'ip'       => hope_client_ip(),
        'uid'      => defined('UID') ? (int)UID : 0,
        'user'     => $username,
        'category' => (string)$category,
        'action'   => (string)$action,
        'status'   => (string)$status,
        'message'  => (string)$message,
        'detail'   => is_array($detail) ? $detail : ['value' => (string)$detail],
    ];
    @file_put_contents(
        $dir . '/audit.log',
        json_encode($entry, JSON_UNESCAPED_UNICODE) . "\n",
        FILE_APPEND | LOCK_EX
    );
}

function hope_login_rate_limit_check() {
    $ip = hope_client_ip();
    if ($ip === '') {
        return;
    }
    $dir = HOPE_ROOT . 'content/cache/login_guard';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $file = $dir . '/' . md5($ip) . '.json';
    if (!is_file($file)) {
        return;
    }
    $data = json_decode((string)file_get_contents($file), true);
    if (!is_array($data)) {
        return;
    }
    $lockedUntil = (int)($data['locked_until'] ?? 0);
    if ($lockedUntil > time()) {
        $minutes = max(1, (int)ceil(($lockedUntil - time()) / 60));
        hpMsg('登录尝试过于频繁，请 ' . $minutes . ' 分钟后再试');
    }
}

function hope_login_rate_limit_fail($username = '') {
    $ip = hope_client_ip();
    if ($ip === '') {
        return;
    }
    $dir = HOPE_ROOT . 'content/cache/login_guard';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $file = $dir . '/' . md5($ip) . '.json';
    $data = is_file($file) ? json_decode((string)file_get_contents($file), true) : [];
    if (!is_array($data)) {
        $data = [];
    }
    $failures = (int)($data['failures'] ?? 0) + 1;
    $data['failures'] = $failures;
    $data['last_fail'] = time();
    if ($failures >= 5) {
        $data['locked_until'] = time() + 900;
    }
    file_put_contents($file, json_encode($data), LOCK_EX);
    hope_audit_log('login', 'fail', ['username' => (string)$username], 'fail', '密码错误或用户不存在');
}

function hope_login_rate_limit_success() {
    $ip = hope_client_ip();
    if ($ip === '') {
        return;
    }
    $file = HOPE_ROOT . 'content/cache/login_guard/' . md5($ip) . '.json';
    if (is_file($file)) {
        @unlink($file);
    }
}

function hope_api_rate_limit_check() {
    if (Option::get('is_limitapi') !== 'y') {
        return;
    }
    $limit = max(1, (int)Option::get('limit_num', 60));
    $ip = hope_client_ip();
    $dir = HOPE_ROOT . 'content/cache/ratelimit';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $file = $dir . '/' . md5($ip . date('YmdHi')) . '.cnt';
    $count = is_file($file) ? (int)file_get_contents($file) : 0;
    if ($count >= $limit) {
        Output::error('api rate limit exceeded', 429);
    }
    file_put_contents($file, (string)($count + 1), LOCK_EX);
}

function hope_api_base_url() {
    return rtrim(SITE_URL, '/') . '/?rest-api=';
}

function hope_load_user_center() {
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $file = HOPE_ROOT . 'system/app/service/user_center.php';
    if (is_file($file)) {
        require_once $file;
        $loaded = true;
    }
}

function hope_theme_options_load_saved() {
    static $saved = null;
    static $loadedTheme = null;
    $theme = Option::get('nonce_theme');
    if ($saved !== null && $loadedTheme === $theme) {
        return $saved;
    }
    $loadedTheme = $theme;
    $saved = [];
    if ($theme) {
        $raw = Option::get($theme . '_options');
        if ($raw) {
            $data = @unserialize($raw);
            if (is_array($data)) {
                $saved = $data;
            }
        }
    }
    return $saved;
}

function hope_csf_collect_field_defaults($section) {
    $defaults = [];
    if (empty($section['fields'])) {
        return $defaults;
    }
    foreach ($section['fields'] as $field) {
        if (!empty($field['id']) && array_key_exists('default', $field)) {
            $defaults[$field['id']] = $field['default'];
        }
        if (!empty($field['fields'])) {
            $defaults = array_merge($defaults, hope_csf_collect_field_defaults(['fields' => $field['fields']]));
        }
        foreach (['tabs', 'accordions'] as $nested) {
            if (!empty($field[$nested])) {
                foreach ($field[$nested] as $item) {
                    $defaults = array_merge($defaults, hope_csf_collect_field_defaults($item));
                }
            }
        }
    }
    return $defaults;
}

function hope_csf_collect_defaults($sections) {
    $defaults = [];
    foreach ($sections as $section) {
        $defaults = array_merge($defaults, hope_csf_collect_field_defaults($section));
    }
    return $defaults;
}

function hope_theme_options_schema_defaults() {
    static $defaults = null;
    static $loadedTheme = null;
    $theme = Option::get('nonce_theme');
    if ($defaults !== null && $loadedTheme === $theme) {
        return $defaults;
    }
    $loadedTheme = $theme;
    $defaults = [];
    if (!$theme) {
        return $defaults;
    }
    $file = TPLS_PATH . $theme . '/options.php';
    if (!is_file($file)) {
        return $defaults;
    }
    if (!class_exists('CSF')) {
        $optPath = defined('OPT_PATH') ? OPT_PATH : HOPE_ROOT . 'system/options/';
        require_once $optPath . 'codestar-framework.php';
    }
    include $file;
    $prefix = $theme . '_options';
    if (!empty(CSF::$args['sections'][$prefix])) {
        $defaults = hope_csf_collect_defaults(CSF::$args['sections'][$prefix]);
    }
    return $defaults;
}

function hope_option($key, $default = '') {
    static $merged = null;
    if ($merged === null) {
        $merged = array_merge(hope_theme_options_schema_defaults(), hope_theme_options_load_saved());
    }
    if (array_key_exists($key, $merged)) {
        return $merged[$key];
    }
    return $default;
}

function _hope($key, $default = '') {
    return hope_option($key, $default);
}

function hope_option_default($key, $default = '') {
    $defs = hope_theme_options_schema_defaults();
    if (array_key_exists($key, $defs)) {
        return $defs[$key];
    }
    return $default;
}

/**
 * 主题 URL 设置：空值或未配置时使用系统默认；废弃后台地址自动回退
 */
function hope_theme_url_option($key, $default = '') {
    $val = trim((string) hope_option($key, ''));
    if ($val === '') {
        return $default;
    }
    $userKeys = ['page_url_login', 'page_url_register', 'page_url_user', 'page_url_auth_api'];
    if (in_array($key, $userKeys, true) && preg_match('#/admin(/|$|\?)#i', $val)) {
        return $default;
    }
    return $val;
}

/** 用户中心相关系统路由 URL */
function hope_user_system_urls() {
    return [
        'login'    => Url::login(),
        'register' => Url::register(),
        'user'     => Url::userCenter(),
        'auth_api' => Url::authApi(['api' => 1]),
    ];
}

/** 解析登录/注册/个人中心/认证 API 链接 */
function hope_resolve_user_url($key) {
    $system = hope_user_system_urls();
    $map = [
        'login'    => 'page_url_login',
        'register' => 'page_url_register',
        'user'     => 'page_url_user',
        'auth_api' => 'page_url_auth_api',
    ];
    if (!isset($map[$key])) {
        return SITE_URL;
    }
    return hope_theme_url_option($map[$key], $system[$key] ?? SITE_URL);
}

/** 规范化文章预设字段定义 */
function hope_normalize_blog_field_preset($item) {
    if (!is_array($item)) {
        return [
            'key'         => '',
            'label'       => '',
            'type'        => 'text',
            'placeholder' => '',
            'default'     => '',
            'options'     => '',
            'description' => '',
            'group'       => '',
        ];
    }
    $allowed = ['text', 'textarea', 'number', 'url', 'select', 'radio', 'checkbox', 'switch'];
    $type = strtolower(trim((string)($item['type'] ?? 'text')));
    if (!in_array($type, $allowed, true)) {
        $type = 'text';
    }
    $key = trim((string)($item['key'] ?? $item['name'] ?? ''));
    $label = trim((string)($item['label'] ?? $item['title'] ?? $key));
    $default = $item['default'] ?? '';
    if (is_array($default)) {
        $default = implode(',', array_map('strval', $default));
    }
    $options = $item['options'] ?? ($item['values'] ?? '');
    if (is_array($options)) {
        $lines = [];
        foreach ($options as $ok => $ov) {
            if (is_int($ok) || ctype_digit((string)$ok)) {
                $lines[] = (string)$ov;
            } else {
                $lines[] = $ok . '|' . $ov;
            }
        }
        $options = implode("\n", $lines);
    }
    return [
        'key'         => $key,
        'label'       => $label !== '' ? $label : $key,
        'type'        => $type,
        'placeholder' => trim((string)($item['placeholder'] ?? '')),
        'default'     => (string)$default,
        'options'     => trim((string)$options),
        'description' => trim((string)($item['description'] ?? $item['desc'] ?? '')),
        'group'       => trim((string)($item['group'] ?? $item['group_name'] ?? '')),
    ];
}

/** 解析预设 options 文本为 [['value'=>,'label'=>], ...] */
function hope_parse_blog_field_options($optionsText) {
    $rows = [];
    $text = trim((string)$optionsText);
    if ($text === '') {
        return $rows;
    }
    foreach (preg_split('/\r\n|\r|\n|,/', $text) as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        if (strpos($line, '|') !== false) {
            list($value, $label) = array_map('trim', explode('|', $line, 2));
        } else {
            $value = $line;
            $label = $line;
        }
        if ($value === '') {
            continue;
        }
        $rows[] = ['value' => $value, 'label' => ($label !== '' ? $label : $value)];
    }
    return $rows;
}

/** 从 POST 的 field_key / field_value 收集文章自定义字段 */
function hope_collect_article_fields($field_keys, $field_values) {
    $fields = [];
    if (!is_array($field_keys)) {
        return $fields;
    }
    if (!is_array($field_values)) {
        $field_values = [];
    }
    foreach ($field_keys as $i => $field_name) {
        $field_name = trim((string)$field_name);
        if ($field_name === '') {
            continue;
        }
        $raw = array_key_exists($i, $field_values) ? $field_values[$i] : '';
        if (is_array($raw)) {
            $vals = [];
            foreach ($raw as $v) {
                $v = trim((string)$v);
                if ($v !== '') {
                    $vals[] = $v;
                }
            }
            $fields[$field_name] = $vals;
        } else {
            $fields[$field_name] = trim((string)$raw);
        }
    }
    return $fields;
}
