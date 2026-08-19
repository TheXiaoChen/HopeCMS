<?php
/**
 * Hope CMS(希望CMS) Output class — 委托 Response 统一格式
 */
class Output {
    public static function ok($data = '') {
        Response::success($data);
    }

    public static function error($msg, $httpCode = 400) {
        Response::fail($msg, Response::CODE_ERROR, $httpCode === 200 ? 200 : $httpCode);
    }

    public static function authError($msg) {
        Response::authFail($msg);
    }

    public static function json($data = '') {
        Response::raw($data);
    }
}
