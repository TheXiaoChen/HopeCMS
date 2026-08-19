<?php
/**
 * Hope CMS(希望CMS) View control
 * @author LinZiChen <271106735@qq.com>
 * @link http://www.hopecms.cn
 * @copyright 2027 Hope CMS All rights reserved.
 * @license http://www.apache.org/licenses/LICENSE-2.0
*/

class View {
    public static function getView($template, $ext = '.php') {
        if (!is_dir(THEME_PATH)) {
            hpMsg('当前使用的主题已被删除或损坏，请登录后台更换其他主题。', SITE_URL . 'admin.php?act=theme');
        }
        // 侧边栏容器优先 widgets/（sidebar / sidebar1）
        if (preg_match('/^(sidebar|sidebar\d+)$/', (string)$template)) {
            $widgetPath = THEME_PATH . 'widgets/' . $template . $ext;
            if (is_file($widgetPath)) {
                return $widgetPath;
            }
        }
        return THEME_PATH . $template . $ext;
    }

    public static function getAdmView($template, $ext = '.php') {
        if (!is_dir(ADMIN_TEMPLATE_PATH)) {
            hpMsg('后台视图已损坏'.ADMIN_TEMPLATE_PATH, SITE_URL);
        }
        return ADMIN_TEMPLATE_PATH . $template . $ext;
    }

    public static function isTplExist($template, $ext = '.php') {
        if (preg_match('/^(sidebar|sidebar\d+)$/', (string)$template)) {
            if (is_file(THEME_PATH . 'widgets/' . $template . $ext)) {
                return true;
            }
        }
        if (file_exists(THEME_PATH . $template . $ext)) {
            return true;
        }
        return false;
    }

    /** 个人中心视图：优先 theme/user/ 目录 */
    public static function getUserView($template, $ext = '.php') {
        if (!is_dir(THEME_PATH)) {
            hpMsg('当前使用的主题已被删除或损坏，请登录后台更换其他主题。', SITE_URL . 'admin.php?act=theme');
        }
        $path = THEME_PATH . 'user/' . $template . $ext;
        if (!file_exists($path)) {
            hpMsg('个人中心页面不存在：user/' . $template . $ext, SITE_URL);
        }
        return $path;
    }

    public static function isUserTplExist($template, $ext = '.php') {
        return file_exists(THEME_PATH . 'user/' . $template . $ext);
    }

    public static function output() {
        $content = ob_get_clean();
        ob_start();
        echo $content;
        ob_end_flush();
        exit;
    }

}
