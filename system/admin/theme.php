<?php
/**
 * Hope CMS(希望CMS) category
 */
defined('HOPE_ROOT') || exit('access denied!');

$Theme_Model = new Theme_Model();
$Apply_Model = new Apply_Model();

$nonce_theme = Option::get('nonce_theme');
$nonce_theme_data = @file(TPLS_PATH . $nonce_theme . '/header.php');

$themes = $Theme_Model->getThemes();

if (isset($_GET['use'])) {
    LoginAuth::checkToken();
    $tplName = addslashes($_GET['tpl'] ?? '');

    Option::updateOption('nonce_theme', $tplName);
    $CACHE->updateCache('options');
    $Theme_Model->initCallback($tplName);

    Gohref(SELF."?act=theme&code=activated");
}

if (isset($_GET['del'])) {
    LoginAuth::checkToken();
    $tplName = addslashes($_GET['tpl'] ?? '');

    $nonce_theme = Option::get('nonce_theme');
    if ($tplName === $nonce_theme) {
        hpMsg('不能删除正在使用的主题');
    }

    $Theme_Model->rmCallback($tplName);

    $path = preg_replace("/^([\w-]+)$/i", "$1", $tplName);
    if ($path && true === hopeDelFile(TPLS_PATH . $path)) {
        Gohref(SELF."?act=theme&code=del#tpllib");
    } else {
        Gohref(SELF."?act=theme&code=error_f#tpllib");
    }
}

if (isset($_GET['upzip'])) {
    if (defined('APP_UPLOAD_FORBID') && APP_UPLOAD_FORBID === true) {
        hpMsg('系统禁止上传安装应用');
    }
    $zipfile = $_FILES['tplzip'] ?? '';

    if ($zipfile['error'] == 4) {
        Gohref(SELF."?act=theme&code=error_d");
    }
    if ($zipfile['error'] == 1) {
        Gohref(SELF."?act=theme&code=error_f");
    }
    if (!$zipfile || $zipfile['error'] > 0 || empty($zipfile['tmp_name'])) {
        hpMsg('主题上传失败， 错误码：' . $zipfile['error']);
    }
    if (getFileSuffix($zipfile['name']) != 'zip') {
        Gohref(SELF."?act=theme&code=error_a");
    }

    $ret = hpUnZip($zipfile['tmp_name'], './content/theme/', 'tpl');
    switch ($ret) {
        case 0:
            Gohref(SELF."?act=theme&code=install");
            break;
        case -2:
            Gohref(SELF."?act=theme&code=error_e");
            break;
        case 1:
        case 2:
            Gohref(SELF."?act=theme&code=error_b");
            break;
        case 3:
            Gohref(SELF."?act=theme&code=error_c");
            break;
    }
}

if (isset($_GET['check_update'])) {
    $theme = $_POST['theme'] ?? [];
    $updates = $Apply_Model->checkUpgrade('theme', $theme);
    Output::ok($updates);
}

if ($act === 'upgrade') {
    LoginAuth::checkToken();
    $alias = trim($_GET['alias'] ?? '');
    if ($alias === '') {
        Output::error('参数错误');
    }

    $temp_file = $Apply_Model->downloadUpgrade('theme', $alias);
    if (!$temp_file) {
        Gohref(SELF . '?act=theme&code=error_h');
    }
    $unzip_path = './content/theme/';
    $ret = hpUnZip($temp_file, $unzip_path, 'tpl');
    @unlink($temp_file);
    switch ($ret) {
        case 0:
            $Theme_Model->upCallback($alias);
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_GET['ajax'])) {
                Output::ok(['alias' => $alias]);
            }
            Gohref(SELF."?act=theme&code=upgrade");
            break;
        case 1:
        case 2:
            Gohref(SELF."?act=theme&code=error_b");
            break;
        case 3:
            Gohref(SELF."?act=theme&code=error_d");
            break;
        default:
            Gohref(SELF."?act=theme&code=error_e");
    }
}
