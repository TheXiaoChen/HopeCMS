<?php
/**
 * Hope CMS 统一 SQL 执行器
 * 用于备份恢复、版本升级；插件/主题建表请在 callback_init 内调用 executeLines / Database::execute
 */
class SqlRunner {

    /**
     * 执行 SQL 文件
     *
     * @param string $file          SQL 文件路径
     * @param array  $options       skip_comments, replace_prefix, version_gate, ignore_error, charset_setup
     * @return bool
     */
    public static function executeFile($file, $options = []) {
        if (!is_readable($file)) {
            return false;
        }

        $sql = file($file);
        if (empty($sql)) {
            return false;
        }

        if (isset($sql[0]) && self::hasBom($sql[0])) {
            $sql[0] = substr($sql[0], 3);
        }

        return self::executeLines($sql, $options);
    }

    /**
     * 执行多行 SQL 内容
     */
    public static function executeLines(array $lines, $options = []) {
        $db = Database::getInstance();
        $ignoreError = !empty($options['ignore_error']);
        $replacePrefix = !empty($options['replace_prefix']);
        $versionGate = !empty($options['version_gate']);
        $charsetSetup = !empty($options['charset_setup']);
        $ignoreCharsetErr = !empty($options['ignore_charset_err']);
        $disableFkChecks = !empty($options['disable_fk_checks']);

        if ($disableFkChecks) {
            $db->query('SET FOREIGN_KEY_CHECKS=0', true);
        }

        if ($charsetSetup && $db->getMysqlVersion() > '5.5') {
            $db->query(
                "ALTER DATABASE `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;",
                $ignoreCharsetErr || $ignoreError
            );
        }

        $query = '';
        foreach ($lines as $line) {
            $line = rtrim($line, "\r\n");

            if ($versionGate && $line !== '' && $line[0] === '#') {
                if (preg_match('/#version:(hope|pro)\s+([\.\d]+)/i', $line, $match)) {
                    $ver = trim($match[2]);
                    if ($ver !== '' && version_compare(Option::HOPE_VERSION, $ver, '<')) {
                        break;
                    }
                } elseif (preg_match('/#\s*(hope|pro)\s+([\.\d]+)/i', $line, $match)) {
                    $ver = trim($match[2]);
                    if ($ver !== '' && version_compare(Option::HOPE_VERSION, $ver, '<')) {
                        break;
                    }
                }
                continue;
            }

            $trimmed = trim($line);
            if ($trimmed === '' || $trimmed[0] === '#') {
                continue;
            }

            if ($replacePrefix) {
                $trimmed = str_replace('{db_prefix}', DB_PREFIX, $trimmed);
            }

            $query .= ($query === '' ? '' : ' ') . $trimmed;
            if (preg_match('/;\s*$/i', $trimmed)) {
                if (preg_match('/^CREATE/i', $query)) {
                    $query = preg_replace('/\sDEFAULT CHARSET=([a-z0-9]+)/is', '', $query);
                }
                $db->query($query, $ignoreError);
                $query = '';
            }
        }

        if ($disableFkChecks) {
            $db->query('SET FOREIGN_KEY_CHECKS=1', true);
        }

        return true;
    }

    /**
     * 按表名（不含前缀）批量 DROP，卸载 callback_rm 用
     */
    public static function dropTables(array $tables) {
        foreach ($tables as $name) {
            $name = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$name);
            if ($name === '') {
                continue;
            }
            Database::execute('DROP TABLE IF EXISTS `' . DB_PREFIX . $name . '`', true);
        }
        return true;
    }

    private static function hasBom($line) {
        return strlen($line) >= 3
            && ord($line[0]) === 239
            && ord($line[1]) === 187
            && ord($line[2]) === 191;
    }
}
