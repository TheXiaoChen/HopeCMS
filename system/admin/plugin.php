<?php

$plugin = Input::getStrVar("plugin");


$Plugin_Model = new Plugin_Model();
$plugins = $Plugin_Model->getPlugins();

if (isset($_GET['upzip'])) {
    LoginAuth::checkToken();

    if (defined('APP_UPLOAD_FORBID') && APP_UPLOAD_FORBID === true) {
        hpMsg('系统禁止上传安装应用');
    }
    $zipfile = $_FILES['pluzip'] ?? '';
    if ($zipfile['error'] == 4) {
        Gohref(SELF."?act=plugin&code=error_d");
    }
    if ($zipfile['error'] == 1) {
        Gohref(SELF."?act=plugin&code=error_g");
    }
    if (!$zipfile || $zipfile['error'] >= 1 || empty($zipfile['tmp_name'])) {
        hpMsg('插件上传失败， 错误码：' . $zipfile['error']);
    }
    if (getFileSuffix($zipfile['name']) != 'zip') {
        Gohref(SELF."?act=plugin&code=error_f");
    }

    $ret = hpUnZip($zipfile['tmp_name'], './content/plugin/', 'plugin');
    switch ($ret) {
        case 0:
            Gohref(SELF."?act=plugin&code=install");
            break;
        case -1:
            Gohref(SELF."?act=plugin&code=error_e");
            break;
        case 1:
        case 2:
            Gohref(SELF."?act=plugin&code=error_b");
            break;
        case 3:
            Gohref(SELF."?act=plugin&code=error_c");
            break;
    }


}



if (isset($_GET['active'])) {
    $Plugin_Model = new Plugin_Model();
    if ($Plugin_Model->activePlugin($plugin)) {
        doAction('plugin_active', $plugin);
        $CACHE->updateCache('options');
        Gohref(SELF."?act=plugin");
    } else {
        Gohref(SELF."?act=plugin&error");
    }
}

if (isset($_GET['inactive'])) {
    $Plugin_Model = new Plugin_Model();
    $Plugin_Model->inactivePlugin($plugin);
    doAction('plugin_inactive', $plugin);

    $CACHE->updateCache('options');
    Gohref(SELF."?act=plugin");
}


if (isset($_GET['del'])) {
    LoginAuth::checkToken();
    $Plugin_Model = new Plugin_Model();
    $Plugin_Model->inactivePlugin($plugin);
    doAction('plugin_inactive', $plugin);
    $Plugin_Model->rmCallback($plugin);
    doAction('plugin_deleted', $plugin);
    $path = preg_replace("/^([\w-]+)\/[\w-]+\.php$/i", "$1", $plugin);

    if ($path && true === hopeDelFile(HOPE_ROOT . 'content/plugin/' . $path)) {
        $CACHE->updateCache('options');
        Gohref(SELF."?act=plugin&activate_del=1");
    } else {
        Gohref(SELF."?act=plugin&error_a=1");
    }
}


if ($act === 'check_update') {
    $plugins = $_POST['plugins'] ?? [];
    $Apply_Model = new Apply_Model();
    $updates = $Apply_Model->checkUpgrade('plugin', $plugins);
    Output::ok($updates);
}

if ($act === 'upgrade') {
    LoginAuth::checkToken();
    $alias = trim($_GET['alias'] ?? '');
    if ($alias === '') {
        Output::error('参数错误');
    }

    $Apply_Model = new Apply_Model();
    $temp_file = $Apply_Model->downloadUpgrade('plugin', $alias);
    if (!$temp_file) {
        Gohref(SELF."?act=plugin&code=error_h");
    }
    $unzip_path = './content/plugin/';
    $ret = hpUnZip($temp_file, $unzip_path, 'plugin');
    @unlink($temp_file);
    switch ($ret) {
        case 0:
            $Plugin_Model = new Plugin_Model();
            $Plugin_Model->upCallback($alias);
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_GET['ajax'])) {
                Output::ok(['alias' => $alias]);
            }
            Gohref(SELF."?act=plugin&code=upgrade");
            break;
        case 1:
        case 2:
            Gohref(SELF."?act=plugin&code=error_b");
            break;
        case 3:
            Gohref(SELF."?act=plugin&code=error_d");
            break;
        default:
            Gohref(SELF."?act=plugin&code=error_e");
    }
}
