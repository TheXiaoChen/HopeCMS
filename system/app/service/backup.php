<?php
/**
 * Hope CMS 数据库备份/恢复
 */
defined('HOPE_ROOT') || exit('access denied!');

class HopeBackup {

    public static function exportAndDownload($zipbak = 0) {
        LoginAuth::checkToken();
        if (!User::isAdmin()) {
            hpMsg('权限不足');
        }

        $DB = Database::getInstance();
        $sqldump = '';
        foreach ($DB->listTables() as $table) {
            $sqldump .= self::exportTable($table);
        }

        $dumpfile = '#version:hope ' . Option::HOPE_VERSION . "\n";
        $dumpfile .= '#date:' . date('Y-m-d H:i') . "\n";
        $dumpfile .= '#tableprefix:' . DB_PREFIX . "\n";
        $dumpfile .= $sqldump;
        $dumpfile .= "\n#the end of backup";

        $filename = 'hope_' . Option::HOPE_VERSION . '_' . date('Ymd_His');
        $useZip = ((int)$zipbak === 1) || ($zipbak === 'y');

        if ($useZip) {
            $zipped = hpZip($filename . '.sql', $dumpfile);
            if ($zipped === false) {
                return false;
            }
            self::sendFileDownload($filename . '.zip', $zipped, 'application/zip');
        }

        self::sendFileDownload($filename . '.sql', $dumpfile, 'application/octet-stream');
    }

    /**
     * 清空输出缓冲后发送附件（避免 base.php 的 text/html 头与缓冲内容污染下载）
     */
    public static function sendFileDownload($filename, $content, $contentType) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        $filename = str_replace(['"', "\r", "\n"], '', (string)$filename);
        header('Content-Type: ' . $contentType . '; charset=UTF-8', true);
        header('Content-Disposition: attachment; filename="' . $filename . '"', true);
        header('Content-Transfer-Encoding: binary', true);
        header('Content-Length: ' . strlen($content), true);
        header('Cache-Control: no-store, no-cache, must-revalidate', true);
        header('Pragma: no-cache', true);
        header('Expires: 0', true);
        echo $content;
        exit;
    }

    public static function importUploadedFile(array $sqlfile) {
        LoginAuth::checkToken();
        if (!User::isAdmin()) {
            hpMsg('权限不足');
        }
        if (empty($sqlfile)) {
            hpMsg('非法提交的信息');
        }
        if ($sqlfile['error'] == 1) {
            hpMsg('文件大小超过系统' . ini_get('upload_max_filesize') . '限制');
        }
        if ($sqlfile['error'] > 1) {
            hpMsg('上传文件失败,错误码：' . $sqlfile['error']);
        }

        $resolved = self::resolveUploadedSqlPath($sqlfile);
        if (is_string($resolved)) {
            return $resolved;
        }

        self::checkSqlFileInfo($resolved['path']);
        if (!SqlRunner::executeFile($resolved['path'], [
            'charset_setup'      => true,
            'ignore_charset_err' => true,
            'disable_fk_checks'  => true,
        ])) {
            hpMsg('备份 SQL 执行失败，请检查文件是否完整或联系管理员查看数据库错误日志');
        }

        $CACHE = Cache::getInstance();
        $CACHE->updateCache();
        return true;
    }

    /**
     * @return array{path:string}|string error code
     */
    public static function resolveUploadedSqlPath(array $sqlfile) {
        $suffix = getFileSuffix($sqlfile['name']);
        if ($suffix === 'zip') {
            $tmpdir = dirname($sqlfile['tmp_name']);
            $ret = hpUnZip($sqlfile['tmp_name'], $tmpdir, 'backup');
            switch ($ret) {
                case -3:
                    return 'error_e';
                case 1:
                case 2:
                    return 'error_d';
                case 3:
                    return 'error_c';
            }
            $sqlPath = self::findSqlFile($tmpdir, $sqlfile['name']);
            if ($sqlPath === '') {
                hpMsg('只能导入 Hope CMS 兼容备份的压缩包');
            }
            return ['path' => $sqlPath];
        }
        if ($suffix !== 'sql') {
            hpMsg('只能导入 Hope CMS 兼容备份的 SQL 文件');
        }
        return ['path' => $sqlfile['tmp_name']];
    }

    public static function findSqlFile($dir, $uploadName) {
        $dir = rtrim(str_replace('\\', '/', $dir), '/');
        $files = glob($dir . '/*.sql');
        if (!is_array($files)) {
            $files = [];
        }
        if (count($files) === 1) {
            return $files[0];
        }
        $guess = $dir . '/' . preg_replace('/\.zip$/i', '.sql', $uploadName);
        if (is_file($guess)) {
            return $guess;
        }
        return !empty($files[0]) ? $files[0] : '';
    }

    public static function checkSqlFileInfo($sqlfile) {
        $fp = @fopen($sqlfile, 'r');
        if (!$fp) {
            hpMsg('读取备份文件失败，检查文件权限');
        }
        $dumpinfo = [];
        $line = 0;
        while (!feof($fp)) {
            $a = fgets($fp, 4096);
            if ($a === false) {
                break;
            }
            if (empty(trim($a, "\t\n\r\0\x0B"))) {
                continue;
            }
            $dumpinfo[] = $a;
            $line++;
            if ($line === 3) {
                break;
            }
        }
        fclose($fp);

        if (empty($dumpinfo)) {
            hpMsg('该文件不是 Hope CMS 数据备份文件');
        }

        if (isset($dumpinfo[0]) && strncmp($dumpinfo[0], "\xEF\xBB\xBF", 3) === 0) {
            $dumpinfo[0] = substr($dumpinfo[0], 3);
        }

        $version = self::parseVersionLine($dumpinfo[0]);
        if ($version === '') {
            hpMsg('该文件不是有效的 Hope CMS 数据备份文件');
        }

        $prefixLine = trim($dumpinfo[2] ?? '');
        if ($prefixLine === '' || preg_match('/#tableprefix:\s*' . preg_quote(DB_PREFIX, '/') . '\s*$/', $prefixLine) !== 1) {
            hpMsg('备份文件中的数据库表前缀与当前系统数据库表前缀不一致');
        }
    }

    public static function parseVersionLine($line) {
        $line = trim((string)$line);
        if (preg_match('/#version:(hope|pro)\s+(\d+\.\d+\.\d+)/i', $line, $matches)) {
            return $matches[2];
        }
        if (preg_match('/(?:hope|pro)\s+(\d+\.\d+\.\d+)/i', $line, $matches)) {
            return $matches[1];
        }
        return '';
    }

    public static function exportTable($table) {
        $DB = Database::getInstance();
        $sql = "DROP TABLE IF EXISTS $table;\n";
        $createtable = $DB->query("SHOW CREATE TABLE $table");
        $create = $DB->fetch_row($createtable);
        $sql .= $create[1] . ";\n\n";

        $rows = $DB->query("SELECT * FROM $table");
        $numfields = $DB->num_fields($rows);
        while ($row = $DB->fetch_row($rows)) {
            $comma = '';
            $sql .= "INSERT INTO $table VALUES(";
            for ($i = 0; $i < $numfields; $i++) {
                $fieldValue = $row[$i];
                if (is_null($fieldValue)) {
                    $sql .= $comma . 'NULL';
                } else {
                    $sql .= $comma . "'" . $DB->escape_string($fieldValue) . "'";
                }
                $comma = ',';
            }
            $sql .= ");\n";
        }
        $sql .= "\n";
        return $sql;
    }
}
