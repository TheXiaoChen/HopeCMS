<?php
/**
 * Hope CMS 在线升级公共方法（后台 / 远程推送共用）
 */
defined('HOPE_ROOT') || exit('access denied!');

/**
 * 升级包允许下载的主机名（小写）
 * 含：官方站、当前站点、应用中心配置地址；可用 Option upgrade_url_hosts（逗号分隔）扩展
 */
function hope_upgrade_allowed_hosts() {
    $hosts = [
        'hopecms.cn',
        'www.hopecms.cn',
        'localhost',
        '127.0.0.1',
    ];
    if (defined('SITE_URL')) {
        $siteHost = parse_url(SITE_URL, PHP_URL_HOST);
        if (is_string($siteHost) && $siteHost !== '') {
            $hosts[] = strtolower($siteHost);
        }
    }
    try {
        if (class_exists('Apply_Model')) {
            $m = new Apply_Model();
            foreach ([$m->getStoreApiUrl(), $m->getOfficialSiteUrl()] as $u) {
                $h = parse_url((string)$u, PHP_URL_HOST);
                if (is_string($h) && $h !== '') {
                    $hosts[] = strtolower($h);
                }
            }
        }
        $extra = trim((string)Option::get('upgrade_url_hosts'));
        if ($extra !== '') {
            foreach (preg_split('/[\s,;]+/', $extra) as $h) {
                $h = strtolower(trim($h));
                if ($h !== '') {
                    $hosts[] = $h;
                }
            }
        }
    } catch (Throwable $e) {
        // ignore
    }
    return array_values(array_unique($hosts));
}

/**
 * 校验升级资源 URL：仅 http(s)，主机在白名单内
 * @return array{ok:bool,msg:string}
 */
function hope_upgrade_assert_url($url, $label = '更新包') {
    $url = trim((string)$url);
    if ($url === '') {
        return ['ok' => false, 'msg' => '缺少' . $label . '地址'];
    }
    $parts = parse_url($url);
    if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
        return ['ok' => false, 'msg' => $label . '地址无效'];
    }
    $scheme = strtolower((string)$parts['scheme']);
    if (!in_array($scheme, ['http', 'https'], true)) {
        return ['ok' => false, 'msg' => $label . '仅允许 http/https'];
    }
    $host = strtolower((string)$parts['host']);
    $allowed = hope_upgrade_allowed_hosts();
    $ok = in_array($host, $allowed, true);
    if (!$ok) {
        foreach ($allowed as $a) {
            if ($a !== '' && substr($host, -strlen('.' . $a)) === '.' . $a) {
                $ok = true;
                break;
            }
        }
    }
    if (!$ok) {
        return ['ok' => false, 'msg' => $label . '域名不在白名单：' . $host];
    }
    return ['ok' => true, 'msg' => ''];
}

/**
 * 可选：校验本地文件 SHA256（期望值为 64 位十六进制）
 */
function hope_upgrade_assert_sha256($file, $expected) {
    $expected = strtolower(trim((string)$expected));
    if ($expected === '') {
        return ['ok' => true, 'msg' => ''];
    }
    if (!preg_match('/^[a-f0-9]{64}$/', $expected)) {
        return ['ok' => false, 'msg' => '校验和格式无效'];
    }
    if (!is_readable($file)) {
        return ['ok' => false, 'msg' => '无法读取下载文件以校验'];
    }
    $hash = hash_file('sha256', $file);
    if ($hash === false || !hash_equals($expected, strtolower($hash))) {
        return ['ok' => false, 'msg' => '文件校验和不匹配，已中止升级'];
    }
    return ['ok' => true, 'msg' => ''];
}

/** 缓存待更新包（主站 API 失效时仍可用直链更新） */
function hope_update_cache_save(array $data) {
    $payload = [
        'version'    => trim((string)($data['version'] ?? '')),
        'source'     => trim((string)($data['source'] ?? '')),
        'upsql'      => trim((string)($data['upsql'] ?? '')),
        'sha256'     => trim((string)($data['sha256'] ?? '')),
        'changelog'  => (string)($data['changelog'] ?? ''),
        'cached_at'  => time(),
        'from_cache' => !empty($data['from_cache']),
    ];
    if ($payload['version'] === '' || $payload['source'] === '') {
        return;
    }
    Option::updateOption('hope_pending_update', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $CACHE = Cache::getInstance();
    $CACHE->updateCache('options');
}

function hope_update_cache_get() {
    $raw = Option::get('hope_pending_update');
    if (is_array($raw)) {
        return $raw;
    }
    $raw = trim((string)$raw);
    if ($raw === '') {
        return null;
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

function hope_update_cache_clear() {
    Option::updateOption('hope_pending_update', '');
    $CACHE = Cache::getInstance();
    $CACHE->updateCache('options');
}

/** 执行更新包安装 */
function hope_install_update_package($source, $upsql = '', $sha256 = '') {
    $source = trim((string)$source);
    $upsql = trim((string)$upsql);
    $sha256 = trim((string)$sha256);
    if ($source === '') {
        return ['ok' => false, 'code' => 'error', 'msg' => '缺少更新包地址'];
    }
    $check = hope_upgrade_assert_url($source, '更新包');
    if (!$check['ok']) {
        return ['ok' => false, 'code' => 'error_host', 'msg' => $check['msg']];
    }
    if ($upsql !== '') {
        $checkSql = hope_upgrade_assert_url($upsql, '升级 SQL');
        if (!$checkSql['ok']) {
            return ['ok' => false, 'code' => 'error_host', 'msg' => $checkSql['msg']];
        }
    }
    $temp_zip_file = hopeFetchFile($source);
    if (!$temp_zip_file) {
        return ['ok' => false, 'code' => 'error_down', 'msg' => '下载更新包失败'];
    }
    $hashCheck = hope_upgrade_assert_sha256($temp_zip_file, $sha256);
    if (!$hashCheck['ok']) {
        @unlink($temp_zip_file);
        return ['ok' => false, 'code' => 'error_hash', 'msg' => $hashCheck['msg']];
    }
    $ret = hpUnZip($temp_zip_file, HOPE_ROOT, 'update');
    @unlink($temp_zip_file);
    switch ($ret) {
        case 1:
        case 2:
            return ['ok' => false, 'code' => 'error_dir', 'msg' => '解压失败，请检查目录权限'];
        case 3:
            return ['ok' => false, 'code' => 'error_zip', 'msg' => '服务器未启用 ZipArchive'];
    }
    if ($upsql !== '') {
        $temp_sql_file = hopeFetchFile($upsql);
        if (!$temp_sql_file) {
            return ['ok' => false, 'code' => 'error_down', 'msg' => '下载升级 SQL 失败'];
        }
        SqlRunner::executeFile($temp_sql_file, [
            'charset_setup'      => true,
            'ignore_charset_err' => true,
            'replace_prefix'     => true,
            'version_gate'       => true,
            'ignore_error'       => true,
        ]);
        @unlink($temp_sql_file);
        $CACHE = Cache::getInstance();
        $CACHE->updateCache();
    }
    return ['ok' => true, 'code' => 'succ', 'msg' => '更新成功'];
}

/** 向应用中心上报已更新 */
function hope_report_core_update($version) {
    $username = trim((string)Option::get('apply_bind_username'));
    $key = trim((string)Option::get('apply_bind_key'));
    if ($username === '' || $key === '') {
        return;
    }
    try {
        $Apply_Model = new Apply_Model();
        $hpcurl = new HpCurl();
        $hpcurl->setPost([
            'action'       => 'upgrade_report',
            'username'     => $username,
            'api_password' => $key,
            'site_url'     => SITE_URL,
            'version'      => (string)$version,
        ]);
        $hpcurl->request($Apply_Model->getStoreApiUrl());
    } catch (Throwable $e) {
        // ignore
    }
}

/**
 * 远程推送更新入口（rest-api=hope_remote_upgrade）
 * 校验 apply_push_secret 后安装，并缓存包地址
 */
function hope_handle_remote_upgrade() {
    header('Content-Type: application/json; charset=UTF-8');
    $secret = isset($_POST['push_secret']) ? trim((string)$_POST['push_secret']) : '';
    $local = trim((string)Option::get('apply_push_secret'));
    if ($local === '' || $secret === '' || !hash_equals($local, $secret)) {
        echo json_encode(['code' => 403, 'msg' => '推送密钥无效'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $source = isset($_POST['source']) ? trim((string)$_POST['source']) : '';
    $upsql = isset($_POST['upsql']) ? trim((string)$_POST['upsql']) : '';
    $version = isset($_POST['version']) ? trim((string)$_POST['version']) : '';
    $changelog = (string)($_POST['changelog'] ?? '');
    $sha256 = isset($_POST['sha256']) ? trim((string)$_POST['sha256']) : '';
    $apply_only = isset($_POST['apply_only']) && (string)$_POST['apply_only'] === '1';

    if ($source === '') {
        echo json_encode(['code' => 0, 'msg' => '缺少更新包'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $check = hope_upgrade_assert_url($source, '更新包');
    if (!$check['ok']) {
        echo json_encode(['code' => 0, 'msg' => $check['msg']], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($upsql !== '') {
        $checkSql = hope_upgrade_assert_url($upsql, '升级 SQL');
        if (!$checkSql['ok']) {
            echo json_encode(['code' => 0, 'msg' => $checkSql['msg']], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    hope_update_cache_save([
        'version'   => $version !== '' ? $version : 'pending',
        'source'    => $source,
        'upsql'     => $upsql,
        'sha256'    => $sha256,
        'changelog' => $changelog,
    ]);

    if ($apply_only) {
        echo json_encode(['code' => 200, 'msg' => '已缓存更新包，请在子站后台执行更新', 'data' => [
            'cached'  => true,
            'version' => $version,
        ]], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($version !== '' && !version_compare($version, Option::HOPE_VERSION, '>')) {
        echo json_encode(['code' => 200, 'msg' => '已是最新版本', 'data' => [
            'current' => Option::HOPE_VERSION,
        ]], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $r = hope_install_update_package($source, $upsql, $sha256);
    if (!$r['ok']) {
        echo json_encode(['code' => 0, 'msg' => $r['msg'], 'data' => ['err' => $r['code']]], JSON_UNESCAPED_UNICODE);
        exit;
    }
    hope_update_cache_clear();
    if ($version !== '') {
        hope_report_core_update($version);
    }
    echo json_encode(['code' => 200, 'msg' => '远程更新成功', 'data' => [
        'version' => $version,
    ]], JSON_UNESCAPED_UNICODE);
    exit;
}
