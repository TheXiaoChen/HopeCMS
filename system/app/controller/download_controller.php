<?php
/**
 * Hope CMS(希望CMS) Download Controller
 * @author LinZiChen <271106735@qq.com>
 * @link http://www.hopecms.cn
 * @copyright 2027 Hope CMS All rights reserved.
 * @license http://www.apache.org/licenses/LICENSE-2.0
 */

class Download_Controller {
    /**
     * @var User_Model
     */
    public $User_Model;
    /**
     * @var Upload_Model
     */
    public $Upload_Model;

    public $Cache;

    function index() {
        $this->Upload_Model = new Upload_Model();
        $resource_alias = Input::getStrVar('resource_alias');
        $name = stripslashes(Input::getStrVar('name'));

        if (empty($resource_alias) || !preg_match('/^\w{16}$/', $resource_alias)) {
            show_404_page();
        }

        // 公开下载：带 name 参数，免登录，隐藏真实源地址
        // 登录下载：仅 resource_alias，需登录
        $is_public = ($name !== '');
        if (!$is_public) {
            LoginAuth::checkLogin();
        }

        $r = $this->Upload_Model->getDetailByAlias($resource_alias);
        if (!$this->isValidResource($r)) {
            show_404_page();
        }

        $file_name = html_entity_decode((string)$r['filename'], ENT_QUOTES, 'UTF-8');
        $alias = (string)$r['alias'];

        // 公开下载：name 对接 alias，必须一致才放行
        if ($is_public && !$this->isNameMatched($name, $alias)) {
            show_404_page();
        }

        doAction('download_resource', $r);

        $this->Upload_Model->incrDownloadCount($r['aid']);

        $this->download($r['filepath'], $file_name, SITE_URL, getUA());
    }

    private function download($file_path, $file_name, $referer = '', $user_agent = '') {
        $file_path = (string)$file_path;

        // 外部资源：服务端跳转，对外只暴露 resource_alias 链接
        if (filter_var($file_path, FILTER_VALIDATE_URL)) {
            Gohref($file_path);
        }

        $local_path = $this->resolveLocalPath($file_path);
        if ($local_path === '' || !is_file($local_path) || !is_readable($local_path)) {
            // 回退：尝试通过站点 URL 读取
            $file_url = getFileUrl($file_path);
            $options = [
                'http' => [
                    'header' => [
                        'Referer: ' . $referer,
                        'User-Agent: ' . $user_agent
                    ]
                ]
            ];
            $context = stream_context_create($options);
            $file_content = @file_get_contents($file_url, false, $context);
            if ($file_content === false) {
                show_404_page();
            }
        } else {
            $file_content = @file_get_contents($local_path);
            if ($file_content === false) {
                show_404_page();
            }
        }

        if (ob_get_length()) {
            ob_end_clean();
        }

        $safe_name = str_replace(['"', "\r", "\n"], '', $file_name);
        $encoded_name = rawurlencode($safe_name);

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $safe_name . '"; filename*=UTF-8\'\'' . $encoded_name);
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . strlen($file_content));

        echo $file_content;
        exit;
    }

    private function resolveLocalPath($file_path) {
        $path = str_replace('\\', '/', (string)$file_path);
        if ($path === '') {
            return '';
        }
        if (strpos($path, '../') === 0) {
            $path = substr($path, 3);
        }
        $path = ltrim($path, '/');
        $full = HOPE_ROOT . $path;
        if (is_file($full)) {
            return $full;
        }
        // 兼容以 content/upload 相对 HOPE_ROOT 的路径
        $alt = HOPE_ROOT . ltrim(str_replace('..', '', $file_path), '/.\\');
        return is_file($alt) ? $alt : $full;
    }

    private function isValidResource($resource) {
        if (!$resource || empty($resource['filepath'])) {
            return false;
        }
        $filename = html_entity_decode((string)$resource['filename'], ENT_QUOTES, 'UTF-8');
        if (isZip($filename)) {
            return true;
        }
        $mime = strtolower((string)$resource['mimetype']);
        return $mime === 'application/zip'
            || $mime === 'application/x-zip-compressed'
            || $mime === 'application/x-rar-compressed'
            || $mime === 'application/x-7z-compressed'
            || strpos($mime, 'zip') !== false;
    }

    /**
     * 公开下载 name 与 alias 比对（兼容 URL 编码）
     */
    private function isNameMatched($request_name, $alias) {
        $a = $this->normalizeDownloadName($request_name);
        $b = $this->normalizeDownloadName($alias);
        if ($a === '' || $b === '' || !preg_match('/^\w{16}$/', $b)) {
            return false;
        }
        return hash_equals($b, $a);
    }

    private function normalizeDownloadName($name) {
        $name = html_entity_decode((string)$name, ENT_QUOTES, 'UTF-8');
        $name = rawurldecode($name);
        $name = urldecode($name);
        $name = trim(str_replace(["\0", "\r", "\n"], '', $name));
        return $name;
    }
}
