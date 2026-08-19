<?php
/**
 * Hope CMS(希望CMS) Service: Upload
 * 上传校验与响应
 */
class Upload {

    private static function blockedMimeTypes() {
        return [
            'application/x-php',
            'application/x-httpd-php',
            'application/php',
            'text/x-php',
            'text/html',
            'application/javascript',
            'text/javascript',
            'application/x-sh',
            'application/x-msdownload',
        ];
    }

    private static function mimeMatchesExtension($extension, $mime) {
        $extension = strtolower((string)$extension);
        $mime = strtolower((string)$mime);
        if ($mime === '' || $mime === 'application/octet-stream') {
            return true;
        }

        $map = [
            'jpg'  => ['image/jpeg', 'image/pjpeg'],
            'jpeg' => ['image/jpeg', 'image/pjpeg'],
            'png'  => ['image/png'],
            'gif'  => ['image/gif'],
            'webp' => ['image/webp'],
            'bmp'  => ['image/bmp', 'image/x-ms-bmp'],
            'svg'  => ['image/svg+xml'],
            'mp4'  => ['video/mp4'],
            'webm' => ['video/webm'],
            'mp3'  => ['audio/mpeg', 'audio/mp3'],
            'wav'  => ['audio/wav', 'audio/x-wav'],
            'pdf'  => ['application/pdf'],
            'zip'  => ['application/zip', 'application/x-zip-compressed'],
            'rar'  => ['application/vnd.rar', 'application/x-rar-compressed'],
            'txt'  => ['text/plain'],
            'doc'  => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xls'  => ['application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        ];

        if (!isset($map[$extension])) {
            return true;
        }
        return in_array($mime, $map[$extension], true);
    }

    static function checkUpload($attach, $mode = '') {
        if (empty($attach) || !is_array($attach)) {
            return '上传失败，未收到文件';
        }

        $fileName = (string)($attach['name'] ?? '');
        $errorNum = (int)($attach['error'] ?? UPLOAD_ERR_NO_FILE);
        $fileSize = (int)($attach['size'] ?? 0);
        $tmpFile = (string)($attach['tmp_name'] ?? '');
        $extension = getFileSuffix($fileName);

        if ($errorNum === UPLOAD_ERR_INI_SIZE || $errorNum === 1) {
            return '文件大小超过PHP' . ini_get('upload_max_filesize') . '限制';
        }
        if ($errorNum !== UPLOAD_ERR_OK) {
            return '上传失败,错误码：' . $errorNum;
        }
        if ($tmpFile === '' || !is_uploaded_file($tmpFile)) {
            return '上传文件无效';
        }
        if ($extension === '') {
            return '文件缺少有效扩展名';
        }

        $attType = User::haveEditPermission() ? Option::getAdminAttType() : Option::getAttType();
        $maxSize = User::haveEditPermission() ? Option::getAdminAttMaxSize() : Option::getAttMaxSize();
        if (!in_array($extension, $attType, true)) {
            return '不能上传该类型文件';
        }
        if ($fileSize <= 0 || $fileSize > $maxSize) {
            return '文件太大了，系统限制上传：' . changeFileSize($maxSize);
        }

        $detectedMime = strtolower((string)get_mimetype($extension, $tmpFile));
        if (in_array($detectedMime, self::blockedMimeTypes(), true)) {
            return '不允许上传该文件类型';
        }
        if (!self::mimeMatchesExtension($extension, $detectedMime)) {
            return '文件内容与扩展名不匹配';
        }

        if ($mode === 'image') {
            if (strpos($detectedMime, 'image/') !== 0) {
                return '请上传有效的图片文件';
            }
            if (!@getimagesize($tmpFile)) {
                return '图片文件无效或已损坏';
            }
        }

        if ($mode === 'video') {
            if (strpos($detectedMime, 'video/') !== 0) {
                return '请上传有效的视频文件';
            }
        }

        return true;
    }

    static function uploadRespond($ret, $isEditor, $isSuccess = false) {
        if (!is_array($ret)) {
            $ret = ['success' => 0, 'message' => (string)$ret];
        }
        if (!isset($ret['success'])) {
            $ret['success'] = $isSuccess ? 1 : 0;
        }
        // Editor.md 要求 success === 1（数字）
        if ($isEditor) {
            $ret['success'] = (int)$ret['success'] ? 1 : 0;
            if (!isset($ret['url'])) {
                $ret['url'] = '';
            }
            if (!isset($ret['message'])) {
                $ret['message'] = $ret['success'] ? '上传成功' : '上传失败';
            }
            header('Content-Type: application/json; charset=UTF-8');
            exit(json_encode($ret, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
        if ($isSuccess) {
            header('HTTP/1.0 200 OK');
            exit($ret['message'] ?? '上传成功');
        }
        header('HTTP/1.0 400 Bad Request');
        exit($ret['message'] ?? '上传失败');
    }

}
