<?php
/**
 * Hope CMS(希望CMS) Codestar Framework
 * @author 林子辰
 * @link http://www.hopecms.cn
 * Plugin Name: Codestar Framework
 * Plugin URI: http://codestarframework.com/
 * Author: Codestar
 * Author URI: http://codestarthemes.com/
 * Version: 2.2.0
 * Description: A Simple and Lightweight WordPress Option Framework for Themes and Plugins
 * Text Domain: csf
 * Domain Path: /languages
 */
defined('HOPE_ROOT') || exit('access denied!');

function new_badge($text='NEW'){
    return '<span class="new-tip">' . $text . '</span>';
}

/**
 * 合并用户定义的参数到默认值数组中 (独立版 hope_parse_args)
 * 此函数用于将字符串、数组或对象的参数与默认数组合并，确保返回规范的数组
 * 
 * @param mixed $args     要合并的值（字符串、数组或对象）
 * @param array $defaults 作为默认值的数组
 * @return array 合并后的数组（用户定义的值覆盖默认值）
 */
function hope_parse_args($args, $defaults = array()) {
    // 初始化变量，用于存储处理后的参数
    $r = array();
    
    // 1. 处理对象类型的参数：将对象属性转换为关联数组
    if (is_object($args)) {
        // 使用 get_object_vars 获取对象的公有属性数组
        $r = get_object_vars($args);
    }
    // 2. 处理数组类型的参数：直接引用，无需转换
    elseif (is_array($args)) {
        $r =& $args;
    }
    // 3. 处理字符串类型的参数（如查询字符串 "key1=value1&key2=value2"）
    else {
        // 使用 parse_str 解析查询字符串到数组
        // 注意：这里使用独立PHP函数替代WordPress的wp_parse_str
        parse_str($args, $r);
    }
    
    // 4. 如果提供了默认值数组，将其与处理后的参数数组合并
    if (is_array($defaults) && !empty($defaults)) {
        // array_merge 的合并顺序确保 $r 中的值覆盖 $defaults 中的同名键
        return array_merge($defaults, $r);
    }
    
    // 5. 如果没有提供默认值，直接返回处理后的参数数组
    return $r;
}

function hope_sql_select($select, $from = '', $where = '', $single = false) {
    $sql = 'SELECT ' . $select . ' FROM ' . DB_PREFIX . $from;
    if ($where !== '') {
        $sql .= ' WHERE ' . $where;
    }
    $db = Database::getInstance();
    if ($single) {
        return $db->once_fetch_array($sql);
    }
    return $db->query($sql);
}
/**
 * 独立版 hope_list_sort - 对对象或数组的列表进行排序
 * 
 * @param array  $list          要排序的数组（包含对象或数组）
 * @param mixed  $orderby       排序字段，可以是字符串或数组
 * @param string $order         排序方向 'ASC' 或 'DESC'
 * @param bool   $preserve_keys 是否保留原始键名
 * @return array 排序后的数组
 */
function hope_list_sort($list, $orderby = array(), $order = 'ASC', $preserve_keys = false) {
    // 如果数组为空或只有一个元素，直接返回
    if (empty($list) || count($list) <= 1) {
        return $list;
    }
    
    // 标准化排序参数
    if (is_string($orderby)) {
        $orderby = array($orderby => $order);
    }
    
    // 统一排序方向格式
    foreach ($orderby as $field => $direction) {
        $orderby[$field] = 'DESC' === strtoupper($direction) ? 'DESC' : 'ASC';
    }
    
    // 根据是否保留键名选择排序函数
    if ($preserve_keys) {
        uasort($list, function($a, $b) use ($orderby) {
            return sort_callback($a, $b, $orderby);
        });
    } else {
        usort($list, function($a, $b) use ($orderby) {
            return sort_callback($a, $b, $orderby);
        });
    }
    
    return $list;
}

/**
 * 排序回调函数
 */
function sort_callback($a, $b, $orderby) {
    // 将对象转换为数组以便统一处理
    $a = (array)$a;
    $b = (array)$b;
    
    foreach ($orderby as $field => $direction) {
        // 跳过不存在的字段
        if (!isset($a[$field]) || !isset($b[$field])) {
            continue;
        }
        
        // 如果值相等，继续比较下一个字段
        if ($a[$field] == $b[$field]) {
            continue;
        }
        
        // 根据排序方向设置比较结果
        $results = 'DESC' === $direction ? [1, -1] : [-1, 1];
        
        // 数值比较
        if (is_numeric($a[$field]) && is_numeric($b[$field])) {
            return ($a[$field] < $b[$field]) ? $results[0] : $results[1];
        }
        
        // 字符串比较
        return (strcmp($a[$field], $b[$field]) < 0) ? $results[0] : $results[1];
    }
    
    return 0; // 所有字段都相等
}



/**
 * 独立版 esc_url() 函数
 * 用于清理和验证URL，确保URL安全可用
 * 
 * @param string $url 要清理的URL
 * @param array|null $protocols 允许的协议数组，默认常见安全协议
 * @param string $_context 使用上下文，'display' 或 'db'
 * @return string 清理后的URL
 */
function esc_url($url, $protocols = null, $_context = 'display') {
    // 1. 基础处理：去除空白字符和无效字符
    if ('' === trim($url)) return '';
    
    // 2. 协议处理：检查并补充默认协议
    if (strpos($url, ':') === false && 
        !in_array($url[0], array('/', '#', '?'), true) &&
        !preg_match('/[a-z0-9-]?.php/i', $url)) {
        $url = 'http://' . $url;
    }
    
    // 3. 定义允许的协议（默认值）
    if (!is_array($protocols)) {
        $protocols = array('http', 'https', 'ftp', 'ftps', 'mailto', 'news', 
                          'irc', 'gopher', 'nntp', 'feed', 'telnet');
    }
    
    // 4. 邮件协议特殊处理
    if (stripos($url, 'mailto:') === 0) {
        $url = str_replace(array('%0d', '%0a', '%0D', '%0A'), '', $url);
    }
    
    // 5. 字符转义（根据上下文）
    if ('display' === $_context) {
        $url = str_replace('&', '&amp;', $url);
        $url = str_replace("'", '&#039;', $url);
    }
    
    // 6. 协议验证：确保使用允许的协议
    $url_check = strtolower($url);
    $allowed = false;
    
    foreach ($protocols as $protocol) {
        if (strpos($url_check, $protocol . ':') === 0) {
            $allowed = true;
            break;
        }
    }
    
    if (!$allowed) return '';
    
    return $url;
}

// 辅助函数：用于数据库存储的URL清理
function sanitize_url($url, $protocols = null) {
    return esc_url($url, $protocols, 'db');
}


/**
 * 将原始字符串转换为URL友好的slug（独立版）
 * 
 * @param string $title        原始字符串（如文章标题）
 * @param string $fallback_title 空值时的备用标题（默认空字符串）
 * @param string $context      处理上下文（'save'用于数据库存储，'display'用于URL查询，默认'save'）
 * @return string 处理后的slug
 */
function sanitize_title($title, $fallback_title = '', $context = 'save') {
    $raw_title = $title;
    
    // 上下文为'save'时，去除重音符号（如é→e）
    if ('save' === $context) {
        $title = remove_accents($title);
    }
    
    // 核心处理：去除特殊字符、转换格式
    $title = sanitize_title_with_dashes($title, $raw_title, $context);
    
    // 过滤后的值为空或false时，使用备用标题
    if (empty($title) || false === $title) {
        $title = $fallback_title;
    }
    
    return $title;
}

/**
 * 去除字符串中的重音符号（如é→e），适配中文等多语言
 * 
 * @param string $string 待处理的字符串
 * @return string 去除重音后的字符串
 */
function remove_accents($string) {
    if (!preg_match('/[\x80-\xff]/', $string)) {
        return $string; // 无重音字符，直接返回
    }
    
    // 常见重音字符映射表（拉丁-1补充、拉丁扩展-A等）
    $chars = array(
        // 拉丁-1补充
        'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'AE', 'Ç'=>'C', 
        'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 
        'Ð'=>'D', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 
        'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'ÿ'=>'y',
        'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'ae', 'ç'=>'c', 
        'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 
        'ð'=>'d', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 
        'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ü'=>'u', 'ý'=>'y',
        // 拉丁扩展-A（可选：适配更多语言）
        'Ā'=>'A', 'ā'=>'a', 'Ă'=>'A', 'ă'=>'a', 'Ą'=>'A', 'ą'=>'a', 'Ć'=>'C', 'ć'=>'c',
        'Č'=>'C', 'č'=>'c', 'Ď'=>'D', 'ď'=>'d', 'Đ'=>'D', 'đ'=>'d', 'Ē'=>'E', 'ē'=>'e',
        'Ė'=>'E', 'ė'=>'e', 'Ę'=>'E', 'ę'=>'e', 'Ě'=>'E', 'ě'=>'e', 'Ĝ'=>'G', 'ĝ'=>'g',
        'Ğ'=>'G', 'ğ'=>'g', 'Ġ'=>'G', 'ġ'=>'g', 'Ģ'=>'G', 'ģ'=>'g', 'Ĥ'=>'H', 'ĥ'=>'h',
        'Ħ'=>'H', 'ħ'=>'h', 'Ĩ'=>'I', 'ĩ'=>'i', 'Ī'=>'I', 'ī'=>'i', 'Ĭ'=>'I', 'ĭ'=>'i',
        'Į'=>'I', 'į'=>'i', 'İ'=>'I', 'ı'=>'i', 'Ĳ'=>'IJ', 'ĳ'=>'ij', 'Ĵ'=>'J', 'ĵ'=>'j',
        'Ķ'=>'K', 'ķ'=>'k', 'ĸ'=>'k', 'Ĺ'=>'L', 'ĺ'=>'l', 'Ļ'=>'L', 'ļ'=>'l', 'Ľ'=>'L',
        'ľ'=>'l', 'Ŀ'=>'L', 'ŀ'=>'l', 'Ł'=>'L', 'ł'=>'l', 'Ń'=>'N', 'ń'=>'n', 'Ņ'=>'N',
        'ņ'=>'n', 'Ň'=>'N', 'ň'=>'n', 'ŉ'=>'n', 'Ŋ'=>'N', 'ŋ'=>'n', 'Ō'=>'O', 'ō'=>'o',
        'Ŏ'=>'O', 'ŏ'=>'o', 'Ő'=>'O', 'ő'=>'o', 'Œ'=>'OE', 'œ'=>'oe', 'Ŕ'=>'R', 'ŕ'=>'r',
        'Ŗ'=>'R', 'ŗ'=>'r', 'Ř'=>'R', 'ř'=>'r', 'Ś'=>'S', 'ś'=>'s', 'Ŝ'=>'S', 'ŝ'=>'s',
        'Ş'=>'S', 'ş'=>'s', 'Š'=>'S', 'š'=>'s', 'Ţ'=>'T', 'ţ'=>'t', 'Ť'=>'T', 'ť'=>'t',
        'Ŧ'=>'T', 'ŧ'=>'t', 'Ũ'=>'U', 'ũ'=>'u', 'Ū'=>'U', 'ū'=>'u', 'Ŭ'=>'U', 'ŭ'=>'u',
        'Ů'=>'U', 'ů'=>'u', 'Ű'=>'U', 'ű'=>'u', 'Ų'=>'U', 'ų'=>'u', 'Ŵ'=>'W', 'ŵ'=>'w',
        'Ŷ'=>'Y', 'ŷ'=>'y', 'Ÿ'=>'Y', 'Ź'=>'Z', 'ź'=>'z', 'Ż'=>'Z', 'ż'=>'z', 'Ž'=>'Z',
        'ž'=>'z', 'ſ'=>'s'
    );
    
    // 替换重音字符
    return strtr($string, $chars);
}

/**
 * 处理字符串中的特殊字符，转换为URL友好的格式（如空格→-、去除非法字符）
 * 
 * @param string $title        待处理的字符串
 * @param string $raw_title    原始字符串（未去除重音前的版本）
 * @param string $context      处理上下文（'save'或'display'，默认'display'）
 * @return string 处理后的字符串
 */
function sanitize_title_with_dashes($title, $raw_title = '', $context = 'display') {
    // 去除HTML标签
    $title = strip_tags( $title);
    
    // 保留转义后的八位字节（如%E2%80%93），后续处理时会还原
    $title = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '---$1---', $title);
    $title = str_replace('%', '', $title);
    $title = preg_replace('|---([a-fA-F0-9][a-fA-F0-9])---|', '%$1', $title);
    
    // 处理UTF-8编码（适配中文）
    /*if (seems_utf8($title)) {
        if (function_exists('mb_strtolower')) {
            $title = mb_strtolower($title, 'UTF-8');
        }
        $title = utf8_uri_encode($title, 200);
    }*/
    
    // 统一转为小写
    $title = strtolower($title);
    
    // 上下文为'save'时，转换特殊字符为-
    if ('save' === $context) {
        $title = str_replace(array('%c2%a0', '%e2%80%93', '%e2%80%94'), '-', $title); // &nbsp;, –, —
        $title = str_replace(array('&nbsp;', '&#160;', '&ndash;', '&#8211;', '&mdash;', '&#8212;'), '-', $title);
        $title = str_replace('/', '-', $title); // 斜杠→-
    }
    
    // 去除非法字符（HTML实体、特殊符号等）
    $title = preg_replace('/&.+?;/', '', $title); // HTML实体（如&lt;、&gt;）
    $title = str_replace(array('.', ','), '-', $title); // 标点符号→-
    //$title = preg_replace('/[^%a-zA-Z0-9 _-]/', '', $title); // 非法字符（保留字母、数字、下划线、空格、-）
    $title = preg_replace('/\s+/', '-', $title); // 多个空格→单个-
    $title = preg_replace('|-+|', '-', $title); // 多个-→单个-
    $title = trim($title, '-'); // 去除首尾的-
    
    return $title;
}

/**
 * 检测字符串是否为UTF-8编码
 * 
 * @param string $str 待检测的字符串
 * @return bool 是否为UTF-8编码
 */
function seems_utf8($str) {
    $length = strlen($str);
    for ($i = 0; $i < $length; $i++) {
        $c = ord($str[$i]);
        if ($c < 0x80) continue; // 单字节字符（ASCII）
        if (($c & 0xE0) == 0xC0) $n = 1; // 双字节字符（如é）
        elseif (($c & 0xF0) == 0xE0) $n = 2; // 三字节字符（如€）
        elseif (($c & 0xF8) == 0xF0) $n = 3; // 四字节字符（如𠀀）
        else return false; // 非法UTF-8序列
        
        // 检查后续字节是否合法（以10开头）
        for ($j = 0; $j < $n; $j++) {
            if ((++$i >= $length) || ((ord($str[$i]) & 0xC0) != 0x80)) {
                return false;
            }
        }
    }
    return true;
}

/**
 * 将UTF-8字符串编码为URI安全格式（适配中文URL）
 * 
 * @param string $utf8_string UTF-8编码的字符串
 * @param int    $max_length  最大长度（默认200）
 * @return string URI安全的字符串
 */
function utf8_uri_encode($utf8_string, $max_length = 200) {
    $unicode = '';
    $values = array();
    $num_octets = 1;
    $unicode_length = 0;
    
    $string_length = strlen($utf8_string);
    for ($i = 0; $i < $string_length; $i++) {
        $value = ord($utf8_string[$i]);
        
        if ($value < 0x80) {
            $unicode .= chr($value);
            $unicode_length++;
        } elseif ($value < 0x800) {
            $unicode .= chr(0xC0 | ($value >> 6));
            $unicode .= chr(0x80 | ($value & 0x3F));
            $unicode_length += 2;
        } else {
            if (($value & 0xF800) != 0xD800) { // 非代理对
                $unicode .= chr(0xE0 | ($value >> 12));
                $unicode .= chr(0x80 | (($value >> 6) & 0x3F));
                $unicode .= chr(0x80 | ($value & 0x3F));
                $unicode_length += 3;
            } else { // 代理对（处理4字节字符，如𠀀）
                $unicode_length += 4;
                $i++;
                $value = (($value & 0x03FF) << 10) | (ord($utf8_string[$i]) & 0x03FF);
                $unicode .= chr(0xF0 | ($value >> 18));
                $unicode .= chr(0x80 | (($value >> 12) & 0x3F));
                $unicode .= chr(0x80 | (($value >> 6) & 0x3F));
                $unicode .= chr(0x80 | ($value & 0x3F));
            }
        }
        
        if ($unicode_length >= $max_length) {
            break;
        }
    }
    
    return $unicode;
}








function esc_attr($text) {
    if (is_array($text) || is_object($text)) {
        $text = '';
    }
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

function esc_textarea($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}



function checked( $checked, $current = true, $display = true ) {
	return __checked_selected_helper( $checked, $current, $display, 'checked' );
}
function __checked_selected_helper( $helper, $current, $display, $type ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionDoubleUnderscore,PHPCompatibility.FunctionNameRestrictions.ReservedFunctionNames.FunctionDoubleUnderscore
	if ( (string) $helper === (string) $current ) {
		$result = " $type='$type'";
	} else {
		$result = '';
	}

	if ( $display ) {
		echo $result;
	}

	return $result;
}

/**
 * 从图标 CSS 中提取可用 class（只收字形，排除尺寸/动画等工具类）
 *
 * @param string $title 分组标题
 * @param string $css   content/admin/css/{name}.css 文件名（不含 .css）
 * @param string $front 类名前缀，如 fa / ri
 * @param string $add   写入 HTML 时附加前缀，如 "fa " / "fa-solid "
 * @param string $mode  auto|glyph|all — glyph 仅匹配定义字形的选择器（FA7 的 --fa / :before content）
 */
function icons($title, $css, $front, $add = '', $mode = 'auto') {
    $cssFile = HOPE_ROOT . 'content/admin/css/' . $css . '.css';
    if ($css === '' || !is_file($cssFile)) {
        return array('title' => $title, 'icons' => array(), 'error' => 'CSS file not found: ' . $cssFile);
    }

    $cssContent = file_get_contents($cssFile);
    if ($cssContent === false || $cssContent === '') {
        return array('title' => $title, 'icons' => array());
    }

    $front = preg_quote($front, '/');
    $icons = array();

    // Font Awesome 7+：仅收集设置了 --fa 的选择器
    if ($mode === 'glyph' || ($mode === 'auto' && strpos($cssContent, '--fa:') !== false)) {
        if (preg_match_all('/([^{}]+)\{[^{}]*--fa\s*:/i', $cssContent, $blocks)) {
            foreach ($blocks[1] as $selectorGroup) {
                if (preg_match_all('/\.(' . $front . '[-a-z0-9]+)/i', $selectorGroup, $m)) {
                    foreach ($m[1] as $cls) {
                        $icons[] = $add . $cls;
                    }
                }
            }
        }
    }

    // Remixicon / 经典 FA：匹配 .xxx:before { content: "\...." }
    if (!$icons && ($mode === 'glyph' || $mode === 'auto')) {
        if (preg_match_all('/\.(' . $front . '[-a-z0-9]+)\s*(?::before|:after)?\s*\{[^}]*content\s*:\s*["\']\\\\[a-f0-9]+/i', $cssContent, $m)) {
            foreach ($m[1] as $cls) {
                $icons[] = $add . $cls;
            }
        }
    }

    // 回退：扫描全部 class，再过滤工具类（glyph 模式不回退，避免空白工具图标）
    if ($mode === 'all' || ($mode === 'auto' && !$icons)) {
        preg_match_all('/\.(' . $front . '[-a-z0-9]+)/i', $cssContent, $matches);
        $deny = '/^' . $front . '-(?:solid|regular|brands|classic|sharp|fw|sm|lg|xl|2xl|2xs|3xs|xs|1x|2x|3x|4x|5x|6x|7x|8x|9x|10x|li|pull(?:-left|-right)?|border(?:-\w+)?|spin|pulse|rotate(?:-\d+)?|flip(?:-\w+)?|stack(?:-\w+)?|ul|sr-only|beat(?:-fade)?|fade|bounce|shake|inverse|layers(?:-\w+)?|counter|pull)$/i';
        if (!empty($matches[1])) {
            foreach ($matches[1] as $cls) {
                if (preg_match($deny, $cls)) {
                    continue;
                }
                $icons[] = $add . $cls;
            }
        }
    }

    $icons = array_values(array_unique($icons));
    sort($icons, SORT_STRING);

    return array('title' => $title, 'icons' => $icons);
}



require_once OPT_PATH . 'classes/setup.class.php'; // 初始化类