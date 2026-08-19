<?php
/**
 * Hope CMS(希望CMS) Input class
 * @author LinZiChen <271106735@qq.com>
 * @link http://www.hopecms.cn
 * @copyright 2027 Hope CMS All rights reserved.
 * @license http://www.apache.org/licenses/LICENSE-2.0
*/

class Input {
    public static function postIntVar($var_name, $var_default = 0, $var_min = 0, $var_max = 0) {
        $options = self::setIntOption($var_default, $var_min, $var_max);
        return filter_input(INPUT_POST, $var_name, FILTER_VALIDATE_INT, $options);
    }

    public static function getIntVar($var_name, $var_default = 0, $var_min = 0, $var_max = 0) {
        $options = self::setIntOption($var_default, $var_min, $var_max);
        return filter_input(INPUT_GET, $var_name, FILTER_VALIDATE_INT, $options);
    }

    public static function postStrVar($var_name, $var_default = '') {
        $str = filter_input(INPUT_POST, $var_name);
        if ($str === null || $str === false) {
            $str = $_POST[$var_name] ?? null;
        }
        if ($str === null || $str === false) {
            return $var_default;
        }
        $str = trim((string)$str);
        return $str === '' ? $var_default : addslashes($str);
    }

    /** 读取 POST 字符串，不做 addslashes（用于密码等原始值） */
    public static function postRawStr($var_name, $var_default = '') {
        $str = filter_input(INPUT_POST, $var_name);
        if ($str === null || $str === false) {
            $str = $_POST[$var_name] ?? null;
        }
        if ($str === null || $str === false) {
            return $var_default;
        }
        $str = trim((string)$str);
        return $str === '' ? $var_default : $str;
    }

    public static function postIntArray($var_name, $var_default = []) {
        $value = filter_input(INPUT_POST, $var_name, FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY);
        return $value ?: $var_default;
    }

    public static function postStrArray($var_name, $var_default = []) {
        $value = filter_input(INPUT_POST, $var_name, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
        return $value ?: $var_default;
    }

    public static function getStrVar($var_name, $var_default = '') {
        $str = filter_input(INPUT_GET, $var_name);
        return $str ? addslashes(trim($str)) : $var_default;
    }

    public static function requestStrVar($var_name, $var_default = '') {
        return isset($_REQUEST[$var_name]) ? addslashes(trim($_REQUEST[$var_name])) : $var_default;
    }

    public static function requestNumVar($var_name, $var_default = 0) {
        return (float)($_REQUEST[$var_name] ?? $var_default);
    }

    private static function setIntOption($var_default = 0, $var_min = 0, $var_max = 0) {
        $options['options']['default'] = $var_default;
        if ($var_max) {
            $options['options']['max_range'] = $var_max;
        }
        if ($var_min) {
            $options['options']['min_range'] = $var_min;
        }
        return $options;
    }
}
