<?php
/**
 * Hope CMS 统一 API 响应
 * 成功 code=200，失败 code=0，认证失败 code=401
 */
class Response {
    const CODE_SUCCESS = 200;
    const CODE_ERROR = 0;
    const CODE_AUTH = 401;

    public static function isSuccessCode($code) {
        return (int)$code === self::CODE_SUCCESS;
    }

    public static function success($data = null, $msg = 'ok') {
        self::send(self::CODE_SUCCESS, $msg, $data, 200);
    }

    public static function fail($msg, $code = self::CODE_ERROR, $httpCode = 400) {
        self::send($code, $msg, null, $httpCode);
    }

    public static function authFail($msg) {
        self::send(self::CODE_AUTH, $msg, null, 401);
    }

    public static function send($code, $msg, $data = null, $httpCode = 200) {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=UTF-8');
        if ($httpCode !== 200) {
            header('HTTP/1.1 ' . $httpCode);
        }
        die(json_encode([
            'code' => $code,
            'msg'  => $msg,
            'data' => $data ?? '',
        ], JSON_UNESCAPED_UNICODE));
    }

    public static function raw($payload) {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=UTF-8');
        die(json_encode($payload, JSON_UNESCAPED_UNICODE));
    }
}
