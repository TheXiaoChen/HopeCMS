<?php
/**
 * Hope CMS(希望CMS) loading plug-in page
 * @author LinZiChen <271106735@qq.com>
 * @link http://www.hopecms.cn
 * @copyright 2027 Hope CMS All rights reserved.
 * @license http://www.apache.org/licenses/LICENSE-2.0
 */

class Plugin_Controller {

    public function loadPluginShow($params) {
        $plugin = '';
        if (is_array($params)) {
            if (isset($params['plugin'])) {
                $plugin = $params['plugin'];
            } elseif (isset($params[1]) && $params[0] === 'plugin') {
                $plugin = $params[1];
            }
        }
        if ($plugin === '' && isset($_GET['plugin'])) {
            $plugin = (string) $_GET['plugin'];
        }
        $plugin = trim((string) $plugin);
        if (!preg_match('/^[\w\-]+$/', $plugin)) {
            show_404_page();
        }
        $showFile = HOPE_ROOT . 'content/plugin/' . $plugin . '/' . $plugin . '_show.php';
        if (!is_file($showFile)) {
            show_404_page();
        }
        // 未启用的插件不允许访问前台页面
        $pluginFile = $plugin . '/' . $plugin . '.php';
        $active_plugins = Option::get('active_plugins');
        if (!is_array($active_plugins) || !in_array($pluginFile, $active_plugins, true)) {
            show_404_page();
        }
        include_once $showFile;
    }
}
