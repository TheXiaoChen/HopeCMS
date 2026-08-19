<?php
/**
 * Hope CMS(希望CMS) Theme model
 * @author LinZiChen <271106735@qq.com>
 * @link http://www.hopecms.cn
 * @copyright 2027 Hope CMS All rights reserved.
 * @license http://www.apache.org/licenses/LICENSE-2.0
*/

class Theme_Model {

    function getThemes() {
        $nonce_theme = Option::get('nonce_theme');

        $Themes = [];
        $handle = @opendir(TPLS_PATH) or die('Hopecms theme path error!');
        $i = 1;
        while ($file = @readdir($handle)) {
            if (!file_exists(TPLS_PATH . $file . '/header.php')) {
                continue;
            }
            $tplData = hope_strip_utf8_bom(implode('', @file(TPLS_PATH . $file . '/header.php')));
            preg_match("/Theme Name:(.*)/i", $tplData, $tplName);
            preg_match("/Theme Type:(.*)/i", $tplData, $tplType);
            preg_match("/Theme Url:(.*)/i", $tplData, $tplUrl);
            preg_match("/Author:(.*)/i", $tplData, $author);
            preg_match("/Author Url:(.*)/i", $tplData, $authorUrl);
            preg_match("/Version:(.*)/i", $tplData, $tplVersion);
            preg_match("/Description:(.*)/i", $tplData, $tplDes);

            $text = !empty($tplType[1]) ? strip_tags(trim($tplType[1])) : '';
            $mapping = [
                'cms' => '博客',
                'music' => '音乐',
                'video' => '视频',
                'forum' => '论坛'
            ];

            $found = [];
            foreach (array_keys($mapping) as $keyword) {
                if (preg_match('/' . preg_quote($keyword, '/') . '/i', $text)) {
                    $found[] = $mapping[$keyword];
                }
            }

            $tpltype = implode('.', $found);

            $tplInfo = [
                'tplfile'    => $file,
                'tplname'    => !empty($tplName[1]) ? subString(strip_tags(trim($tplName[1])), 0, 16) : $file,
                'tpltype'    => $tpltype,
                'tplurl'     => !empty($tplUrl[1]) ? subString(strip_tags(trim($tplUrl[1])), 0, 75) : '',
                'author'     => !empty($author[1]) ? subString(strip_tags(trim($author[1])), 0, 16) : '',
                'author_url' => !empty($authorUrl[1]) ? subString(strip_tags(trim($authorUrl[1])), 0, 75) : '',
                'version'    => !empty($tplVersion[1]) ? subString(strip_tags(trim($tplVersion[1])), 0, 16) : '',
                'tpldes'     => !empty($tplDes[1]) ? subString(strip_tags(trim($tplDes[1])), 0, 40) : '',
            ];

            $previewPath = TPLS_PATH . $file . '/preview.png';
            $tplInfo['preview'] = file_exists($previewPath) ? (TPLS_URL . $file . '/preview.png') : './content/admin/img/theme-icon.png';

            if ($nonce_theme === $file) {
                $Themes[0] = $tplInfo;
            } else {
                $Themes[$i] = $tplInfo;
            }
            $i++;
        }
        ksort($Themes);
        closedir($handle);
        return $Themes;
    }

    function getCustomThemes($type) {
        $nonce_theme = Option::get('nonce_theme') . '/';
        if (!is_dir(TPLS_PATH . $nonce_theme)) {
            return false;
        }
        $files = scandir(TPLS_PATH . $nonce_theme . '/');
        $php_files = [];
        if(in_array($type, $files, true)){
            $list_file = scandir(TPLS_PATH . $nonce_theme . '/'.$type.'/');
            if (is_array($list_file)) {
                foreach ($list_file as $file) {
                    if (strpos($file, '.php') !== false) {
                        $php_files[] = [
                            'filename' => str_replace('.php', '', $file),
                            'comment'  => $this->getThemeComment($type.'/'.$file),
                        ];
                    }
                }
            }
        }else{
            foreach ($files as $file) {
                switch ($type) {
                    case 'sort':
                        if (strpos($file, 'log_list_') === 0 && strpos($file, '.php') !== false) {
                            $php_files[] = [
                                'filename' => str_replace('.php', '', $file),
                                'comment'  => $this->getThemeComment($file),
                            ];
                        }
                        break;
                    case 'page':
                        if (strpos($file, 'page_') === 0 && strpos($file, '.php') !== false) {
                            $php_files[] = [
                                'filename' => str_replace('.php', '', $file),
                                'comment'  => $this->getThemeComment($file),
                            ];
                        }
                        break;
                    case 'log':
                        if (strpos($file, 'echo_log_') === 0 && strpos($file, '.php') !== false) {
                            $php_files[] = [
                                'filename' => str_replace('.php', '', $file),
                                'comment'  => $this->getThemeComment($file),
                            ];
                        }
                        break;
                }
            }
        }
        return $php_files;
    }

    function getThemeComment($filename) {
        $nonce_theme = Option::get('nonce_theme') . '/';
        $path = TPLS_PATH . $nonce_theme . $filename;
        $fallback = str_replace('.php', '', basename((string)$filename));
        if (!is_file($path)) {
            return $fallback;
        }

        $content = @file_get_contents($path);
        if ($content === false || $content === '') {
            return $fallback;
        }
        // 去掉 UTF-8 BOM，避免首行匹配失败
        if (strncmp($content, "\xEF\xBB\xBF", 3) === 0) {
            $content = substr($content, 3);
        }

        // 兼容 /*@name 名称*/ 、/** @name 名称 */ 、单独一行 * @name 名称
        if (preg_match('/@name\s+([^\r\n*]+)/i', $content, $m)) {
            $comment = trim($m[1]);
            $comment = trim($comment, " \t*/");
            if ($comment !== '') {
                return $comment;
            }
        }

        return $fallback;
    }

    // init callback
    public function initCallback($tplName) {
        $callback_file = TPLS_PATH . $tplName . '/callback.php';
        if (is_file($callback_file)) {
            require_once $callback_file;
            if (function_exists('callback_init')) {
                callback_init();
            }
        }
    }

    // delete callback
    public function rmCallback($tplName) {
        $callback_file = TPLS_PATH . $tplName . '/callback.php';
        if (is_file($callback_file)) {
            require_once $callback_file;
            if (function_exists('callback_rm')) {
                callback_rm();
            }
        }
    }

    // upgrade callback
    public function upCallback($tplName) {
        $callback_file = TPLS_PATH . $tplName . '/callback.php';
        if (is_file($callback_file)) {
            require_once $callback_file;
            if (function_exists('callback_up')) {
                callback_up();
            }
        }
    }

}
