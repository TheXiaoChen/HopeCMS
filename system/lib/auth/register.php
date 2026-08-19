<?php
/**
 * Hope CMS 应用中心商店账号绑定
 */
class Register {

    /** 本地绑定凭证：邮箱 + API 密钥 */
    public static function getCredentials() {
        return [
            'email'        => trim((string)Option::get('apply_bind_username')),
            'api_password' => trim((string)Option::get('apply_bind_key')),
            'uid'          => (int)Option::get('apply_bind_uid'),
            'type'         => (int)Option::get('apply_bind_type'),
        ];
    }

    /** 是否已在本地保存商店绑定 */
    public static function isBound() {
        $cred = self::getCredentials();
        return $cred['email'] !== '' && $cred['api_password'] !== '';
    }

    /** 绑定账号类型：0 普通 / 1 开发者 / 2 VIP 等 */
    public static function getType() {
        return (int)Option::get('apply_bind_type');
    }

    /** 远程校验绑定是否仍有效 */
    public static function isValid() {
        $cred = self::getCredentials();
        if ($cred['email'] === '' || $cred['api_password'] === '') {
            return false;
        }
        $Apply_Model = new Apply_Model();
        return $Apply_Model->verifyRemoteBind($cred['email'], $cred['api_password']);
    }

    /** 校验是否有权从应用中心下载指定资源 */
    public static function verifyDownload($source) {
        $cred = self::getCredentials();
        $source = trim((string)$source);
        if ($cred['api_password'] === '' || $source === '') {
            return false;
        }

        $Apply_Model = new Apply_Model();
        $hpcurl = new HpCurl();
        $hpcurl->setPost([
            'action'       => 'download_verify',
            'username'     => $cred['email'],
            'email'        => $cred['email'],
            'api_password' => $cred['api_password'],
            'source'       => $source,
            'site_url'     => SITE_URL,
            'ver'          => Option::HOPE_VERSION,
        ]);
        $hpcurl->request($Apply_Model->getStoreApiUrl());

        $status = $hpcurl->getHttpStatus();
        if ($status === 403) {
            self::clear();
            return false;
        }
        if ($status !== 200) {
            return false;
        }

        $response = json_decode($hpcurl->getRespone(), true);
        if (!is_array($response) || (int)($response['code'] ?? 0) !== 200) {
            return false;
        }
        return !empty($response['data']['allowed']);
    }

    /** 清除本地绑定信息 */
    public static function clear() {
        Option::updateOption('apply_bind_key', '');
        Option::updateOption('apply_bind_username', '');
        Option::updateOption('apply_bind_nickname', '');
        Option::updateOption('apply_bind_uid', '');
        Option::updateOption('apply_bind_type', '');
        Option::updateOption('apply_bind_time', '');
        Option::updateOption('apply_push_secret', '');
        Cache::getInstance()->updateCache('options');
    }
}
