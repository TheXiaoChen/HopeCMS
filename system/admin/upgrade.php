<?php
/**
 * Hope CMS 在线升级（应用中心 API）
 */
defined('HOPE_ROOT') || exit('access denied!');

require_once HOPE_ROOT . 'system/lib/upgrade_helper.php';

if (!User::isAdmin()) {
    header('HTTP/1.1 403 Forbidden');
    exit('error_auth');
}

$action = Input::requestStrVar('action');

if ($action === 'check_update') {
    header('Content-Type: application/json; charset=UTF-8');

    $Apply_Model = new Apply_Model();
    $hpcurl = new HpCurl();
    $hpcurl->setPost(array_merge([
        'action'       => 'upgrade_check',
        'username'     => Option::get('apply_bind_username'),
        'api_password' => Option::get('apply_bind_key'),
        'version'      => Option::HOPE_VERSION,
        'timestamp'    => Option::HOPE_VERSION_TIMESTAMP,
        'site_url'     => SITE_URL,
        'apps'         => json_encode([['name' => 'hopecms', 'version' => Option::HOPE_VERSION]]),
    ]));
    $hpcurl->request($Apply_Model->getStoreApiUrl());
    $retStatus = $hpcurl->getHttpStatus();
    $response = $hpcurl->getRespone();

    $r = ($retStatus === 200) ? json_decode($response, true) : null;

    if (is_array($r) && isset($r['code']) && $r['code'] === MSGCODE_API_AUTH_INVALID) {
        Register::clear();
        exit(json_encode([
            'code' => MSGCODE_API_AUTH_INVALID,
            'msg'  => $r['msg'] ?? '应用中心授权无效',
            'data' => [
                'has_update' => false,
                'current'    => Option::HOPE_VERSION,
            ],
        ], JSON_UNESCAPED_UNICODE));
    }

    // 同步推送密钥（便于主站帮子站更新）
    if (is_array($r) && !empty($r['push_secret'])) {
        Option::updateOption('apply_push_secret', (string)$r['push_secret']);
        Cache::getInstance()->updateCache('options');
    }

    $item = null;
    $list = [];
    if (is_array($r)) {
        if (isset($r['data']) && is_array($r['data'])) {
            $list = $r['data'];
        } elseif (isset($r[0]) && is_array($r[0])) {
            $list = $r;
        }
    }
    foreach ($list as $row) {
        if (!is_array($row)) {
            continue;
        }
        $name = $row['name'] ?? ($row['alias'] ?? '');
        if ($name === 'hopecms' || $name === '') {
            $item = $row;
            break;
        }
    }
    if ($item === null && count($list) === 1 && is_array($list[0])) {
        $item = $list[0];
    }

    $source = '';
    $upsql = '';
    $newVersion = '';
    $changelog = '';
    $fromCache = false;

    if (is_array($item)) {
        $source = trim((string)($item['source'] ?? ($item['download'] ?? ($item['download_url'] ?? ''))));
        $upsql = trim((string)($item['upsql'] ?? ($item['sql_url'] ?? '')));
        $newVersion = trim((string)($item['version'] ?? ''));
        $changelog = trim((string)($item['changelog'] ?? ($item['summary'] ?? '')));
    }

    // 主站不可用时，回退本地缓存的更新包（直链下载，不依赖主站 API）
    if (($retStatus !== 200 || !is_array($r)) && ($source === '' || $newVersion === '')) {
        $cached = hope_update_cache_get();
        if (is_array($cached)
            && !empty($cached['source'])
            && !empty($cached['version'])
            && version_compare((string)$cached['version'], Option::HOPE_VERSION, '>')
        ) {
            $source = (string)$cached['source'];
            $upsql = (string)($cached['upsql'] ?? '');
            $newVersion = (string)$cached['version'];
            $changelog = (string)($cached['changelog'] ?? '');
            $fromCache = true;
        } elseif ($retStatus !== 200) {
            exit(json_encode([
                'code' => 0,
                'msg'  => '无法连接更新服务器，且无可用的本地更新缓存',
                'data' => [
                    'has_update' => false,
                    'current'    => Option::HOPE_VERSION,
                ],
            ], JSON_UNESCAPED_UNICODE));
        }
    }

    $hasUpdate = $source !== '' && $newVersion !== ''
        && version_compare($newVersion, Option::HOPE_VERSION, '>');

    if ($hasUpdate) {
        hope_update_cache_save([
            'version'   => $newVersion,
            'source'    => $source,
            'upsql'     => $upsql,
            'changelog' => $changelog,
        ]);
    }

    exit(json_encode([
        'code' => 200,
        'msg'  => $hasUpdate ? ($fromCache ? '发现新版本（来自本地缓存）' : '发现新版本') : '已是最新版本',
        'data' => [
            'has_update' => $hasUpdate,
            'current'    => Option::HOPE_VERSION,
            'version'    => $hasUpdate ? $newVersion : Option::HOPE_VERSION,
            'source'     => $hasUpdate ? $source : '',
            'upsql'      => $hasUpdate ? $upsql : '',
            'changelog'  => $hasUpdate ? $changelog : '',
            'from_cache' => $fromCache,
        ],
    ], JSON_UNESCAPED_UNICODE));
}

if ($action === 'update') {
    hope_admin_require_token();
    $source = Input::requestStrVar('source');
    $upsql = Input::requestStrVar('upsql');
    $version = Input::requestStrVar('version');
    $sha256 = Input::requestStrVar('sha256');
    $cached = hope_update_cache_get();
    if ($version === '') {
        $version = is_array($cached) ? (string)($cached['version'] ?? '') : '';
    }
    if ($sha256 === '' && is_array($cached)) {
        $sha256 = (string)($cached['sha256'] ?? '');
    }
    if ($source === '' && is_array($cached)) {
        $source = (string)($cached['source'] ?? '');
    }
    if ($upsql === '' && is_array($cached)) {
        $upsql = (string)($cached['upsql'] ?? '');
    }

    $r = hope_install_update_package($source, $upsql, $sha256);
    if (!$r['ok']) {
        exit($r['code']);
    }
    hope_update_cache_clear();
    if ($version !== '') {
        hope_report_core_update($version);
    }
    exit('succ');
}

header('HTTP/1.1 404 Not Found');
exit('error');
