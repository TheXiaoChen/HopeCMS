<?php
/**
 * Hope CMS 站点数据：备份 / 导入 / 更新缓存
 */
defined('HOPE_ROOT') || exit('access denied!');

require_once HOPE_ROOT . 'system/app/service/backup.php';

// 优先读 $_GET（setting.php 可能写入），filter_input 读不到运行时改写的 $_GET
$action = isset($_GET['action']) ? trim((string)$_GET['action']) : '';
if ($action === '') {
    $action = Input::getStrVar('action');
}
if ($action === '' && isset($_POST['action'])) {
    $action = trim((string)$_POST['action']);
}

$settingUrl = SELF . '?act=setting';

if ($action === '') {
    Gohref($settingUrl . '#set5');
}

switch ($action) {
    case 'backup':
        $zipbak = (int)($_POST['zipbak'] ?? Input)::getIntVar('zipbak', 0);
        if (HopeBackup::exportAndDownload($zipbak) === false) {
            Gohref($settingUrl . '&code=error_d');
        }
        break;

    case 'import':
        $result = HopeBackup::importUploadedFile($_FILES['sqlfile'] ?? []);
        if ($result === true) {
            Gohref($settingUrl . '&code=import');
        }
        $errorMap = [
            'error_c' => 'error_a',
            'error_d' => 'error_b',
            'error_e' => 'error_c',
        ];
        Gohref($settingUrl . '&code=' . ($errorMap[$result] ?? 'error_e'));
        break;

    case 'Cache':
        LoginAuth::checkToken();
        // 全量刷新（mc_menu 会同步主菜单关联标题后再写缓存）
        $CACHE->updateCache();
        if (class_exists('Option') && method_exists('Option', 'resetRoutingTableCache')) {
            Option::resetRoutingTableCache();
        }
        Gohref($settingUrl . '&code=mc');
        break;

    default:
        Gohref($settingUrl . '#set5');
}
