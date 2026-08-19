<?php
/**
 * Hope CMS 业务异常，由统一错误处理器转换为页面或 JSON
 */
class HopeException extends Exception {
    public $responseCode;
    public $httpCode;
    public $redirectUrl;

    public function __construct($message, $responseCode = 0, $httpCode = 400, $redirectUrl = '') {
        parent::__construct($message);
        $this->responseCode = $responseCode;
        $this->httpCode = $httpCode;
        $this->redirectUrl = $redirectUrl;
    }
}
