<?php
/**
 * Hope CMS(希望CMS) 后台入口文件
 * @author LinZiChen <271106735@qq.com>
 * @link http://www.hopecms.cn
 * @copyright 2027 Hope CMS All rights reserved.
 * @license http://www.apache.org/licenses/LICENSE-2.0
 */
define('HOPE_ADMIN', true);
require_once 'system/base.php';
define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME)); // 后台文件名
require_once HOPE_ROOT.'/system/admin/globals.php';