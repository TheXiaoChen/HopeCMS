<?php
/**
 * Hope CMS 目录与常量引导
 */
defined('HOPE_ROOT') || define('HOPE_ROOT', str_ireplace('system', '', dirname(__DIR__)) . DIRECTORY_SEPARATOR);

define('HOPE_PATH_APP', HOPE_ROOT . 'system/app/');
define('HOPE_PATH_MODEL', HOPE_PATH_APP . 'model/');
define('HOPE_PATH_CONTROLLER', HOPE_PATH_APP . 'controller/');
define('HOPE_PATH_SERVICE', HOPE_PATH_APP . 'service/');
define('HOPE_PATH_ADMIN', HOPE_ROOT . 'system/admin/');
define('HOPE_PATH_LIBRARY', HOPE_ROOT . 'system/lib/');
define('HOPE_PATH_OPTIONS', HOPE_ROOT . 'system/options/');
