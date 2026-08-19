<?php
/**
 * Hope CMS(希望CMS) Cache
 * @author LinZiChen <271106735@qq.com>
 * @link http://www.hopecms.cn
 * @copyright 2027 Hope CMS All rights reserved.
 * @license http://www.apache.org/licenses/LICENSE-2.0
 */

class Cache {

    private $db;
    private static $instance;

    /** @var array<string, mixed> 请求内缓存池（按名记忆，避免重复反序列化） */
    private $pool = [];

    protected function __construct() {
        $this->db = Database::getInstance();
    }

    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Cache();
        }
        return self::$instance;
    }

    /**
     * @param mixed $cacheMethodName cache name：'options', multi use array：['options', 'user'], Leave blank for update all
     */
    public function updateCache($cacheMethodName = null) {
        if (is_string($cacheMethodName)) {
            $method = 'mc_' . $cacheMethodName;
            if (method_exists($this, $method)) {
                $this->$method();
            }
            return;
        }
        if (is_array($cacheMethodName)) {
            foreach ($cacheMethodName as $name) {
                $method = 'mc_' . $name;
                if (method_exists($this, $method)) {
                    $this->$method();
                }
            }
            return;
        }
        if (!$cacheMethodName) {
            $cacheMethodNames = get_class_methods($this);
            foreach ($cacheMethodNames as $method) {
                if (0 === strpos($method, 'mc_')) {
                    $this->$method();
                }
            }
        }
    }

    public function updateArticleCache() {
        $this->updateCache(['sta', 'tags', 'sort', 'newlog', 'record', 'logsort', 'logalias', 'menu']);
    }

    /**
     * 写入缓存文件（PHP return 数组，便于 OPcache）
     */
    public function cacheWrite($cacheData, $cacheName) {
        $cacheName = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$cacheName);
        if ($cacheName === '') {
            return;
        }
        $data = $cacheData;

        $cacheFile = HOPE_ROOT . 'content/cache/' . $cacheName . '.php';
        $export = var_export($data, true);
        $payload = "<?php\n//Hope-cache\nreturn " . $export . ";\n";

        if (file_put_contents($cacheFile, $payload, LOCK_EX) === false) {
            hpMsg('写入缓存失败，缓存目录(content/cache)不可写');
        }

        // 避免 OPcache 继续命中旧的 menu/options 等 PHP 缓存文件
        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($cacheFile, true);
        }

        unset($this->pool[$cacheName]);
    }

    /**
     * Dispatcher 等调用：writeCache($name, $data)
     */
    public function writeCache($cacheName, $cacheData = null) {
        if ($cacheData === null && !is_string($cacheName)) {
            return;
        }
        $this->cacheWrite($cacheData, $cacheName);
    }

    /**
     * 删除指定缓存文件并清空请求内记忆
     */
    public function deleteCache($cacheName) {
        $cacheName = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$cacheName);
        if ($cacheName === '') {
            return;
        }
        $cacheFile = HOPE_ROOT . 'content/cache/' . $cacheName . '.php';
        $metaFile = HOPE_ROOT . 'content/cache/' . $cacheName . '.meta';
        if (is_file($cacheFile)) {
            @unlink($cacheFile);
        }
        if (is_file($metaFile)) {
            @unlink($metaFile);
        }
        unset($this->pool[$cacheName]);
    }

    public function readCache($cacheName) {
        $cacheName = (string)$cacheName;
        if (array_key_exists($cacheName, $this->pool)) {
            return $this->pool[$cacheName];
        }

        $cacheFile = HOPE_ROOT . 'content/cache/' . $cacheName . '.php';

        if (!is_file($cacheFile) || filesize($cacheFile) <= 0) {
            if (method_exists($this, 'mc_' . $cacheName)) {
                $this->{'mc_' . $cacheName}();
            }
        }

        if (!is_file($cacheFile) || filesize($cacheFile) <= 0) {
            $this->pool[$cacheName] = [];
            return $this->pool[$cacheName];
        }

        $data = $this->loadCacheFile($cacheFile);
        $this->pool[$cacheName] = ($data === null) ? [] : $data;
        return $this->pool[$cacheName];
    }

    /**
     * 加载缓存文件：优先 include（Hope-cache / 旧 HopeCMS-cache），其次历史 serialize 格式
     */
    private function loadCacheFile($cacheFile) {
        $head = (string)@file_get_contents($cacheFile, false, null, 0, 64);
        if ($head !== '' && (
            strpos($head, '//Hope-cache') !== false
            || strpos($head, '//HopeCMS-cache') !== false
            || preg_match('/^<\?php\s*(?:\/\/[^\n]*\n)?\s*return\b/s', $head)
        )) {
            $data = include $cacheFile;
            return $data;
        }

        $cacheData = (string)file_get_contents($cacheFile);
        $prefix = '<?php exit;//';
        if (strpos($cacheData, $prefix) !== 0) {
            return null;
        }
        $raw = substr($cacheData, strlen($prefix));
        if (strpos($raw, 'b64:') === 0) {
            $decoded = base64_decode(substr($raw, 4), true);
            $raw = ($decoded !== false) ? $decoded : '';
        } elseif (strpos($raw, 'ser:') === 0) {
            $raw = substr($raw, 4);
        }
        $data = @unserialize($raw);
        return ($data === false && $raw !== serialize(false)) ? null : $data;
    }

    /**
     * 带 TTL 的缓存：过期后通过回调重建
     */
    public function remember($cacheName, $ttl, callable $callback) {
        $metaFile = HOPE_ROOT . 'content/cache/' . $cacheName . '.meta';
        $fresh = false;
        if (is_file($metaFile)) {
            $meta = json_decode((string)file_get_contents($metaFile), true);
            if (is_array($meta) && isset($meta['expires']) && (int)$meta['expires'] > time()) {
                $fresh = true;
            }
        }
        if (!$fresh) {
            $data = $callback();
            $this->cacheWrite($data, $cacheName);
            file_put_contents($metaFile, json_encode(['expires' => time() + (int)$ttl]), LOCK_EX);
            $this->pool[$cacheName] = $data;
            return $data;
        }
        return $this->readCache($cacheName);
    }

    private function mc_options() {
        $options_cache = [];
        $res = $this->db->query("SELECT option_name, option_value FROM " . DB_PREFIX . "options");
        while ($row = $this->db->fetch_array($res)) {
            if (in_array($row['option_name'], ['site_key', 'sitename', 'siteinfo', 'siteurl', 'icp'], true)) {
                $row['option_value'] = htmlspecialchars($row['option_value']);
            }
            if ($row['option_name'] === 'footer_info') {
                $row['option_value'] = stripslashes((string)$row['option_value']);
            }
            $options_cache[$row['option_name']] = $row['option_value'];
        }
        $this->cacheWrite($options_cache, 'options');
    }

    private function mc_user() {
        $user_cache = [];
        $query = $this->db->query("SELECT uid, username, nickname, email, description, photo, ischeck, role FROM " . DB_PREFIX . "user ORDER BY uid ASC");
        while ($row = $this->db->fetch_array($query)) {
            $photo = [];
            $photoSrc = '';
            if (!empty($row['photo'])) {
                $photoSrc = str_replace('../', '', $row['photo']);
                $photo['src'] = htmlspecialchars($photoSrc);
                $photo['width'] = 500;
                $photo['height'] = 300;
            }
            $row['nickname'] = empty($row['nickname']) ? $row['username'] : $row['nickname'];
            $user_cache[$row['uid']] = [
                'uid'       => $row['uid'],
                'photo'     => $photo,
                'avatar'    => $photoSrc,
                'name_orig' => $row['nickname'],
                'name'      => htmlspecialchars($row['nickname']),
                'mail'      => htmlspecialchars($row['email']),
                'des'       => htmlClean($row['description']),
                'ischeck'   => htmlspecialchars($row['ischeck']),
                'role'      => $row['role'],
            ];
        }
        $this->cacheWrite($user_cache, 'user');
    }

    private function mc_sta() {
        $prefix = DB_PREFIX;
        $blog = $this->db->once_fetch_array(
            "SELECT
                SUM(CASE WHEN hide='n' AND checked='y' THEN 1 ELSE 0 END) AS log_num,
                SUM(CASE WHEN hide='n' AND checked='n' THEN 1 ELSE 0 END) AS check_num
             FROM {$prefix}article WHERE type='blog'"
        );
        $log_num = (int)($blog['log_num'] ?? 0);
        $check_num = (int)($blog['check_num'] ?? 0);

        $note = $this->db->once_fetch_array("SELECT COUNT(*) AS total FROM {$prefix}twitter");
        $com = $this->db->once_fetch_array(
            "SELECT
                SUM(CASE WHEN hide='n' THEN 1 ELSE 0 END) AS com_num,
                SUM(CASE WHEN hide='y' THEN 1 ELSE 0 END) AS hide_com_num
             FROM {$prefix}comment"
        );
        $att = $this->db->once_fetch_array("SELECT COUNT(*) AS total FROM {$prefix}upload");
        $user = $this->db->once_fetch_array("SELECT COUNT(*) AS total FROM {$prefix}user WHERE state='0'");

        $com_num = (int)($com['com_num'] ?? 0);
        $hide_com_num = (int)($com['hide_com_num'] ?? 0);

        $sta_cache = [
            'lognum'     => $log_num,
            'checknum'   => $check_num,
            'comnum'     => $com_num,
            'note_num'   => (int)($note['total'] ?? 0),
            'comnum_all' => $com_num + $hide_com_num,
            'hidecomnum' => $hide_com_num,
            'uploadnum'  => (int)($att['total'] ?? 0),
            'usernum'    => (int)($user['total'] ?? 0),
        ];

        // 按作者聚合，避免 N+1
        $logByAuthor = [];
        $q = $this->db->query("SELECT author, COUNT(*) AS total FROM {$prefix}article WHERE hide='n' AND type='blog' GROUP BY author");
        while ($row = $this->db->fetch_array($q)) {
            $logByAuthor[(int)$row['author']] = (int)$row['total'];
        }

        $comByAuthor = [];
        $q = $this->db->query(
            "SELECT b.author,
                    COUNT(*) AS commentnum,
                    SUM(CASE WHEN a.hide='y' THEN 1 ELSE 0 END) AS hidecommentnum
             FROM {$prefix}comment AS a
             INNER JOIN {$prefix}article AS b ON a.gid = b.gid
             GROUP BY b.author"
        );
        while ($row = $this->db->fetch_array($q)) {
            $uid = (int)$row['author'];
            $comByAuthor[$uid] = [
                'commentnum'     => (int)$row['commentnum'],
                'hidecommentnum' => (int)$row['hidecommentnum'],
            ];
        }

        $q = $this->db->query("SELECT uid FROM {$prefix}user ORDER BY uid DESC LIMIT 1000");
        while ($row = $this->db->fetch_array($q)) {
            $uid = (int)$row['uid'];
            $sta_cache[$uid] = [
                'lognum'         => $logByAuthor[$uid] ?? 0,
                'draftnum'       => 0,
                'commentnum'     => isset($comByAuthor[$uid]) ? $comByAuthor[$uid]['commentnum'] : 0,
                'hidecommentnum' => isset($comByAuthor[$uid]) ? $comByAuthor[$uid]['hidecommentnum'] : 0,
            ];
        }

        $this->cacheWrite($sta_cache, 'sta');
    }

    private function mc_comment() {
        $comment_paging = Option::get('comment_paging');
        $comment_pnum = (int)Option::get('comment_pnum');
        $comment_order = Option::get('comment_order');
        if ($comment_pnum <= 0) {
            $comment_pnum = 10;
        }

        $query = $this->db->query("SELECT cid,gid,pid,poster,date,mail,uid,comment FROM " . DB_PREFIX . "comment WHERE hide='n' ORDER BY date DESC LIMIT 0, 10");
        $com_cache = [];
        $com_cids = [];
        $rows = [];
        while ($show_com = $this->db->fetch_array($query)) {
            $rows[] = $show_com;
        }

        // 批量解析顶级父评论，减少逐条向上查询
        $needParents = [];
        if ($comment_paging == 'y') {
            foreach ($rows as $show_com) {
                if ((int)$show_com['pid'] !== 0) {
                    $needParents[(int)$show_com['cid']] = (int)$show_com['pid'];
                }
            }
        }
        $rootCidMap = [];
        if ($needParents) {
            $parentIds = array_unique(array_values($needParents));
            $parentMap = [];
            $guard = 0;
            while ($parentIds && $guard < 20) {
                $idList = implode(',', array_map('intval', $parentIds));
                $parentIds = [];
                $pq = $this->db->query("SELECT cid,pid FROM " . DB_PREFIX . "comment WHERE cid IN ($idList)");
                while ($pr = $this->db->fetch_array($pq)) {
                    $parentMap[(int)$pr['cid']] = (int)$pr['pid'];
                    if ((int)$pr['pid'] !== 0) {
                        $parentIds[] = (int)$pr['pid'];
                    }
                }
                $guard++;
            }
            foreach ($needParents as $cid => $pid) {
                $walk = $pid;
                $root = $cid;
                $depth = 0;
                while ($walk !== 0 && $depth < 20) {
                    $root = $walk;
                    $walk = $parentMap[$walk] ?? 0;
                    $depth++;
                }
                $rootCidMap[$cid] = $root;
            }
        }

        foreach ($rows as $show_com) {
            $com_page = '';
            if ($comment_paging == 'y') {
                $cid = (int)$show_com['cid'];
                if ((int)$show_com['pid'] !== 0 && isset($rootCidMap[$cid])) {
                    $cid = $rootCidMap[$cid];
                }
                $gid = (int)$show_com['gid'];
                if (!isset($com_cids[$gid])) {
                    $com_cids[$gid] = [];
                    $order = $comment_order == 'newer' ? 'DESC' : '';
                    $query2 = $this->db->query("SELECT cid FROM " . DB_PREFIX . "comment WHERE gid=" . $gid . " AND pid=0 AND hide='n' ORDER BY date $order");
                    while ($show_cid = $this->db->fetch_array($query2)) {
                        $com_cids[$gid][] = (int)$show_cid['cid'];
                    }
                }
                $idx = array_search($cid, $com_cids[$gid], true);
                $com_page = (int)floor(($idx === false ? 0 : $idx) / $comment_pnum) + 1;
            }
            $com_cache[] = [
                'cid'     => $show_com['cid'],
                'gid'     => $show_com['gid'],
                'name'    => htmlspecialchars($show_com['poster']),
                'date'    => $show_com['date'],
                'page'    => $com_page,
                'mail'    => $show_com['mail'],
                'uid'     => $show_com['uid'],
                'content' => htmlClean(subString($show_com['comment'], 0, 48), false),
            ];
        }
        $this->cacheWrite($com_cache, 'comment');
    }

    private function mc_tags() {
        $tag_cache = [];
        $tagnum = 50;
        $maxuse = 20;
        $minuse = 0;
        $spread = min($tagnum, 12);
        $rank = $maxuse - $minuse;
        $rank = ($rank == 0 ? 1 : $rank);
        $rank = $spread / $rank;
        $query = $this->db->query("SELECT tagname,gid FROM " . DB_PREFIX . "tag order by tid desc limit $tagnum");
        while ($row = $this->db->fetch_array($query)) {
            if ($row['gid'] == ',') {
                continue;
            }
            $usenum = empty($row['gid']) ? 0 : substr_count($row['gid'], ',') + 1;
            $fontsize = 10 + round(($usenum - $minuse) * $rank);
            $tag_cache[] = [
                'tagurl'   => urlencode($row['tagname']),
                'tagname'  => htmlspecialchars($row['tagname']),
                'fontsize' => min($fontsize, 22),
                'usenum'   => $usenum,
            ];
        }
        $this->cacheWrite($tag_cache, 'tags');
    }

    private function mc_sort() {
        $Sort_Model = new Sort_Model();
        $sort_cache = $Sort_Model->getSorts();
        $this->cacheWrite($sort_cache, 'sort');
    }

    private function mc_link() {
        $link_cache = [];
        $query = $this->db->query("SELECT sitename,siteurl,icon,description FROM " . DB_PREFIX . "link WHERE hide='n' ORDER BY taxis ASC");
        while ($show_link = $this->db->fetch_array($query)) {
            $link_cache[] = [
                'link' => htmlspecialchars($show_link['sitename']),
                'url'  => htmlspecialchars($show_link['siteurl']),
                'icon' => htmlspecialchars($show_link['icon']),
                'des'  => htmlspecialchars($show_link['description']),
            ];
        }
        $this->cacheWrite($link_cache, 'link');
    }

    private function mc_menu() {
        $Menu_Model = new Menu_Model();
        // 先同步关联内容标题，再写缓存，避免文章/分类/标签改名后导航仍显示旧名
        $Menu_Model->syncLinkedMenuTitles();
        $menu_cache = $Menu_Model->getMenu();
        $this->cacheWrite($menu_cache, 'menu');
    }

    private function mc_newlog() {
        $index_newlognum = (int) Option::get('index_lognum');
        if ($index_newlognum <= 0) {
            $index_newlognum = 10;
        }
        $now = time();
        $date_state = "and date<=$now";
        $sql = "SELECT gid,title,cover,views,comnum,date FROM " . DB_PREFIX . "article WHERE hide='n' and checked='y' and type='blog' $date_state ORDER BY date DESC LIMIT 0, $index_newlognum";
        $res = $this->db->query($sql);
        $logs = [];
        while ($row = $this->db->fetch_array($res)) {
            $row['gid'] = (int)$row['gid'];
            $row['title'] = htmlspecialchars($row['title']);
            $row['cover'] = $row['cover'] ? getFileUrl($row['cover']) : '';
            $logs[] = $row;
        }
        $this->cacheWrite($logs, 'newlog');
    }

    private function mc_record() {
        // 按 UTC 年月聚合，与原 gmdate 行为一致，避免拉取全部文章日期
        $sql = "SELECT
                    DATE_FORMAT(DATE_ADD('1970-01-01', INTERVAL `date` SECOND), '%Y_%c') AS f_record,
                    DATE_FORMAT(DATE_ADD('1970-01-01', INTERVAL `date` SECOND), '%Y年%c月') AS record_label,
                    DATE_FORMAT(DATE_ADD('1970-01-01', INTERVAL `date` SECOND), '%Y%m') AS record_date,
                    COUNT(*) AS lognum
                FROM " . DB_PREFIX . "article
                WHERE hide='n' AND checked='y' AND type='blog'
                GROUP BY 1, 2, 3
                ORDER BY MAX(`date`) DESC";
        $query = $this->db->query($sql);
        $record_cache = [];
        while ($row = $this->db->fetch_array($query)) {
            $record_cache[] = [
                'record' => $row['record_label'],
                'date'   => $row['record_date'],
                'lognum' => (int)$row['lognum'],
            ];
        }
        $this->cacheWrite($record_cache, 'record');
    }

    private function mc_logalias() {
        $sql = "SELECT gid,alias FROM " . DB_PREFIX . "article where alias!=''";
        $query = $this->db->query($sql);
        $log_cache_alias = [];
        while ($row = $this->db->fetch_array($query)) {
            $log_cache_alias[$row['gid']] = $row['alias'];
        }
        $this->cacheWrite($log_cache_alias, 'logalias');
    }

    private function mc_logtags() {
        $this->cacheWrite([], 'logtags');
    }

    private function mc_logsort() {
        $sql = "SELECT gid,sortid FROM " . DB_PREFIX . "article where type='blog' order by top DESC, sortop DESC, date DESC LIMIT 200";
        $query = $this->db->query($sql);
        $log_cache_sort = [];
        $logs = [];
        $sorts = [];
        while ($row = $this->db->fetch_array($query)) {
            if ($row['sortid'] > 0) {
                $logs[$row['gid']] = $row['sortid'];
            }
        }

        if ($logs) {
            $query = $this->db->query("SELECT sid,sortname,alias FROM " . DB_PREFIX . "sort");
            while ($srow = $this->db->fetch_array($query)) {
                $sorts[$srow['sid']] = [
                    'name'  => htmlspecialchars($srow['sortname']),
                    'id'    => htmlspecialchars($srow['sid']),
                    'alias' => htmlspecialchars($srow['alias']),
                ];
            }
            foreach ($logs as $gid => $sortid) {
                $log_cache_sort[$gid] = $sorts[$sortid] ?? [];
            }
        }
        $this->cacheWrite($log_cache_sort, 'logsort');
    }
}
