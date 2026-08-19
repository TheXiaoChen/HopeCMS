<?php
/**
 * Hope CMS(希望CMS)
 * @author LinZiChen <271106735@qq.com>
 * @link http://www.hopecms.cn
 * @copyright 2027 Hope CMS All rights reserved.
 * @license http://www.apache.org/licenses/LICENSE-2.0
 */
require_once 'system/base.php';
$hpDispatcher = Dispatcher::getInstance();
$hpDispatcher->dispatch();
View::output();