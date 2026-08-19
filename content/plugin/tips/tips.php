<?php
/*
Plugin Name: 小贴士
Version: 1.0.0
Plugin URL: http://www.hopecms.cn
Description: 插件启用后，在后台首页随机展示一句内置使用提示。
Author: Hope CMS
Author URL: http://www.hopecms.cn
*/
!defined('HOPE_ROOT') && exit('error');

require_once HOPE_ROOT . 'content/plugin/tips/tips_lib.php';

addAction('adm_main_top', 'tips_render_admin_banner');
addAction('adm_head', 'tips_enqueue_admin_css');
