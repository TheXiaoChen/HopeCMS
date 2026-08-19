<?php
/**
 * Hope CMS(希望CMS) article and page model
 * @author LinZiChen <271106735@qq.com>
 * @link http://www.hopecms.cn
 * @copyright 2027 Hope CMS All rights reserved.
 * @license http://www.apache.org/licenses/LICENSE-2.0
 */

class Log_Model {

    private $db;
    private $Parsedown;
    private $table;
    private $table_user;
    private $table_sort;

    public function __construct() {
        self::migrateBlogTable();
        $this->db = Database::getInstance();
        $this->table = DB_PREFIX . 'article';
        $this->table_user = DB_PREFIX . 'user';
        $this->table_sort = DB_PREFIX . 'sort';
        $this->Parsedown = new Parsedown();
        $this->Parsedown->setBreaksEnabled(true); //automatic line wrapping
    }

    public static function articleTable() {
        return 'article';
    }

    public static function migrateBlogTable() {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        $old = DB_PREFIX . 'blog';
        $new = DB_PREFIX . 'article';
        if (Database::fetchOne("SHOW TABLES LIKE '{$new}'")) {
            return;
        }
        if (Database::fetchOne("SHOW TABLES LIKE '{$old}'")) {
            Database::execute("RENAME TABLE `{$old}` TO `{$new}`", true);
        }
    }

    /**
     * create article
     */
    public function addlog($logData) {
        return Database::table('article')->insert($logData);
    }

    public function updateLog($logData, $blogId, $uid = UID) {
        $blogId = (int)$blogId;
        $uid = (int)$uid;
        $query = Database::table('article')->where(['gid' => $blogId]);
        if (!User::haveEditPermission()) {
            $query->where(['author' => $uid]);
        }
        $query->update($logData);
    }

    public function getCount($uid = UID) {
        return Database::table('article')->where([
            'author' => (int)$uid,
            'type'   => 'blog',
        ])->count();
    }

    /**
     * Gets the number of articles for the specified condition
     *
     * @param int $spot 0:homepage 1:admin
     * @param string $hide
     * @param string $condition
     * @param string $type
     * @return int
     */
    public function getLogNum($hide = 'n', $condition = '', $type = 'blog', $spot = 0) {
        if (is_array($condition)) {
            return $this->countAdminLogs($condition, $hide, $type, $spot);
        }
        $q = Database::table('article')->where(['type' => $type]);
        if ($hide) {
            $q->where(['hide' => $hide]);
        }
        $this->applySpotScope($q, $spot);
        if ($condition) {
            $parsed = $this->parseLegacyAdminCondition($condition);
            if ($parsed['where'] !== '') {
                $q->whereRaw($parsed['where']);
            }
        }
        return $q->count();
    }

    public function countPublicLogs($filters = []) {
        return $this->buildPublicQuery($filters)->count();
    }

    public function getPostCountByUid($uid, $time = 0) {
        $q = Database::table('article')->where([
            'type'   => 'blog',
            'author' => (int)$uid,
        ]);
        if ($time) {
            $q->whereRaw('date > ' . (int)$time);
        }
        return $q->count();
    }

    public function getOneLogForAdmin($blogId) {
        $blogId = (int)$blogId;
        $q = Database::table('article')->where(['gid' => $blogId]);
        if (!User::haveEditPermission()) {
            $q->where(['author' => (int)UID]);
        }
        $row = $q->find();
        if (!$row) {
            hope_abort('权限不足！', './');
        }
        $row['title'] = htmlspecialchars($row['title']);
        $row['content'] = htmlspecialchars($row['content']);
        $row['excerpt'] = htmlspecialchars($row['excerpt']);
        $row['password'] = htmlspecialchars($row['password']);
        $row['template'] = !empty($row['template']) ? htmlspecialchars(trim($row['template'])) : 'page';
        return $row;
    }

    public function getDetail($blogId) {
        $blogId = (int)$blogId;
        if ($blogId <= 0) {
            return false;
        }
        return Database::table('article', 't1')
            ->select('t1.*, t2.sid, t2.sortname, t2.alias as sort_alias')
            ->leftJoin('sort', 't2', 't1.sortid=t2.sid')
            ->where(['t1.gid' => $blogId])
            ->find();
    }

    public function getDetails($blogIds) {
        if (empty($blogIds) || !is_array($blogIds)) {
            return false;
        }
        $ids = array_values(array_filter(array_map('intval', $blogIds)));
        if (empty($ids)) {
            return [];
        }
        return Database::table('article', 't1')
            ->select('t1.*, t2.sid, t2.sortname, t2.alias as sort_alias')
            ->leftJoin('sort', 't2', 't1.sortid=t2.sid')
            ->whereIn('t1.gid', $ids)
            ->findAll();
    }

    /**
     * get single article
     * @param $blogId
     * @param bool $ignoreHide 忽略隐藏状态
     * @param bool $ignoreChecked 忽略审核状态
     * @return array|false
     */
    public function getOneLogForHome($blogId, $ignoreHide = false, $ignoreChecked = false) {
        $blogId = (int)$blogId;
        $q = Database::table('article')->where(['gid' => $blogId]);
        if (!$ignoreHide) {
            $q->where(['hide' => 'n']);
        }
        if (!$ignoreChecked) {
            $q->where(['checked' => 'y']);
        }
        $row = $q->find();
        if (!$row) {
            return false;
        }
        return $this->formatOneLogForHome($row);
    }
    
    public function getArticleList($condition = '', $hide_state = '', $page = 1, $type = 'blog', $perpage_num = 20) {
        if (is_array($condition)) {
            return $this->getAdminArticleList($condition, $hide_state, $page, $type, $perpage_num);
        }
        $page = max(1, (int)$page);
        $perpage_num = max(1, (int)$perpage_num);
        $offset = ($page - 1) * $perpage_num;
        $q = Database::table('article')->where(['type' => $type]);
        if (!User::haveEditPermission()) {
            $q->where(['author' => (int)UID]);
        }
        if ($hide_state) {
            $q->where(['hide' => $hide_state]);
        }
        if ($condition) {
            $parsed = $this->parseLegacyAdminCondition($condition);
            if ($parsed['where'] !== '') {
                $q->whereRaw($parsed['where']);
            }
            if ($parsed['order'] !== '') {
                $q->order($parsed['order']);
            }
        }
        $rows = $q->limit($perpage_num, $offset)->findAll();
        $logs = [];
        foreach ($rows as $row) {
            $row['timestamp'] = $row['date'];
            $row['date'] = date('Y-m-d H:i', $row['date']);
            $row['title'] = !empty($row['title']) ? htmlspecialchars($row['title']) : '无标题';
            $logs[] = $row;
        }
        return $logs;
    }

    public function getPublicLogs($filters = [], $page = 1, $perPage = 10) {
        $order = $filters['order'] ?? 'top DESC, sortop DESC, date DESC';
        $page = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);
        $offset = ($page - 1) * $perPage;
        $rows = $this->buildPublicQuery($filters)
            ->order($order)
            ->limit($perPage, $offset)
            ->findAll();
        return $this->formatHomeLogs($rows);
    }

    public function getLogsForHome($condition = '', $page = 1, $perPageNum = 10) {
        if (!is_array($condition)) {
            $condition = [];
        }
        return $this->getPublicLogs($condition, $page, $perPageNum);
    }

    /**
     * get rss article list
     */
    public function getLogsForRss($perPageNum = 10) {
        if ($perPageNum <= 0) {
            return [];
        }
        $rows = Database::table('article', 't1')
            ->select('t1.*, t1.password as pwd')
            ->leftJoin('user', 't2', 't1.author=t2.uid')
            ->where(['t1.hide' => 'n', 't1.checked' => 'y', 't1.type' => 'blog'])
            ->whereRaw('t1.date <= ' . time())
            ->order('t1.date DESC')
            ->limit((int)$perPageNum)
            ->findAll();
        $d = [];
        foreach ($rows as $re) {
            $re['id'] = $re['gid'];
            $re['title'] = htmlspecialchars($re['title']);
            $re['content'] = $this->Parsedown->text($re['content']);
            if (!empty($re['pwd'])) {
                $re['content'] = '<p>[该文章已设置加密]</p>';
            } elseif (Option::get('rss_output_fulltext') == 'n') {
                if (!empty($re['excerpt'])) {
                    $re['content'] = $re['excerpt'];
                } else {
                    $re['content'] = extractHtmlData($re['content'], 330);
                }
                $re['content'] .= ' <a href="' . Url::log($re['id']) . '">阅读全文&gt;&gt;</a>';
            }
            $d[] = $re;
        }
        return $d;
    }

    public function getPageOffset($date, $pageSize = 20, $type = 'blog') {
        $date = (int)$date;
        $pageSize = max(1, (int)$pageSize);
        $count = Database::table('article')
            ->where(['type' => $type, 'hide' => 'n'])
            ->whereRaw("(date >= {$date} OR top = 'y' OR sortop = 'y')")
            ->count();
        return ceil($count / $pageSize);
    }

    public function getAllPageList() {
        $rows = Database::table('article')->where(['type' => 'page'])->findAll();
        $pages = [];
        foreach ($rows as $row) {
            $row['date'] = date('Y-m-d H:i', $row['date']);
            $row['title'] = !empty($row['title']) ? htmlspecialchars($row['title']) : '无标题';
            $pages[] = $row;
        }
        return $pages;
    }

    public function deleteLog($blogId) {
        $blogId = (int)$blogId;
        $this->checkEditable($blogId);
        $detail = $this->getDetail($blogId);
        $q = Database::table('article')->where(['gid' => $blogId]);
        if (!User::haveEditPermission()) {
            $q->where(['author' => (int)UID]);
        }
        $affected = $q->delete();
        if ($affected < 1) {
            hope_abort('权限不足！', './');
        }
        Database::table('comment')->where(['gid' => $blogId])->delete();
        if (!empty($detail['tags'])) {
            $TagModel = new Tag_Model();
            $tags = explode(',', $detail['tags']);
            foreach ($tags as $tag) {
                $TagModel->removeBlogIdFromTag($tag, $blogId);
            }
        }
    }

    public function hideSwitch($blogId, $state) {
        $blogId = (int)$blogId;
        $state = $state === 'y' ? 'y' : 'n';
        $q = Database::table('article')->where(['gid' => $blogId]);
        if (!User::haveEditPermission()) {
            $q->where(['author' => (int)UID]);
        }
        $q->update(['hide' => $state]);
        Database::table('comment')->where(['gid' => $blogId])->update(['hide' => $state]);
        $Comment_Model = new Comment_Model();
        $Comment_Model->updateCommentNum($blogId);
    }

    public function checkSwitch($blogId, $state) {
        $blogId = (int)$blogId;
        $state = $state === 'y' ? 'y' : 'n';
        Database::table('article')->where(['gid' => $blogId])->update(['checked' => $state]);
        $commentHide = $state === 'y' ? 'n' : 'y';
        Database::table('comment')->where(['gid' => $blogId])->update(['hide' => $commentHide]);
        $Comment_Model = new Comment_Model();
        $Comment_Model->updateCommentNum($blogId);
    }

    public function unCheck($blogId, $feedback) {
        $blogId = (int)$blogId;
        Database::table('article')->where(['gid' => $blogId])->update([
            'checked'  => 'n',
            'feedback' => $feedback,
        ]);
        Database::table('comment')->where(['gid' => $blogId])->update(['hide' => 'y']);
        $Comment_Model = new Comment_Model();
        $Comment_Model->updateCommentNum($blogId);
    }

    public function updateViewCount($blogId) {
        $blogId = (int)$blogId;
        Database::execute("UPDATE `{$this->table}` SET views=views+1 WHERE gid={$blogId}");
    }

    public function isRepeatPost($title, $time) {
        $row = Database::table('article')->where([
            'title' => $title,
            'date'  => (int)$time,
        ])->find();
        return (int)($row['gid'] ?? false);
    }

    public function neighborLog($date) {
        $date = (int)$date;
        $neighborlog = [];
        $next = $this->buildPublicQuery(['date_lt' => $date])
            ->select('title,gid,cover')
            ->order('date DESC')
            ->limit(1)
            ->find();
        $prev = $this->buildPublicQuery(['date_gt' => $date])
            ->select('title,gid,cover')
            ->order('date ASC')
            ->limit(1)
            ->find();
        $neighborlog['nextLog'] = $next;
        $neighborlog['prevLog'] = $prev;
        if ($neighborlog['nextLog']) {
            $neighborlog['nextLog']['title'] = htmlspecialchars($neighborlog['nextLog']['title']);
            $neighborlog['nextLog']['cover'] = !empty($neighborlog['nextLog']['cover']) ? getFileUrl($neighborlog['nextLog']['cover']) : '';
        }
        if ($neighborlog['prevLog']) {
            $neighborlog['prevLog']['title'] = htmlspecialchars($neighborlog['prevLog']['title']);
            $neighborlog['prevLog']['cover'] = !empty($neighborlog['prevLog']['cover']) ? getFileUrl($neighborlog['prevLog']['cover']) : '';
        }
        return $neighborlog;
    }

    public function getRandLog($num) {
        global $CACHE;
        $num = max(1, (int)$num);
        $sta_cache = $CACHE->readCache('sta');
        $lognum = (int)($sta_cache['lognum'] ?? 0);
        $start = $lognum > $num ? mt_rand(0, $lognum - $num) : 0;
        $rows = $this->buildPublicQuery()
            ->select('gid,title')
            ->limit($num, $start)
            ->findAll();
        $logs = [];
        foreach ($rows as $row) {
            $row['gid'] = (int)$row['gid'];
            $row['title'] = htmlspecialchars($row['title']);
            $logs[] = $row;
        }
        return $logs;
    }

    public function getHotLog($num) {
        $num = max(1, (int)$num);
        $rows = $this->buildPublicQuery()
            ->select(['gid', 'title', 'cover', 'views', 'comnum', 'alias'])
            ->order('views DESC, comnum DESC')
            ->limit($num)
            ->findAll();
        $logs = [];
        foreach ($rows as $row) {
            $row['gid'] = (int)$row['gid'];
            $row['title'] = htmlspecialchars($row['title']);
            $row['cover'] = $row['cover'] ? getFileUrl($row['cover']) : '';
            $row['log_url'] = Url::log($row['gid']);
            $logs[] = $row;
        }
        return $logs;
    }

    // 检查文章别名，别名重复则重命名为 xxx-1 格式
    public function checkAlias($alias, $logalias_cache, $logid) {
        if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $alias)) {
            return '';
        }
        static $i = 2;
        $key = array_search($alias, $logalias_cache);
        if (false !== $key && $key != $logid) {
            if ($i == 2) {
                $alias .= '-' . $i;
            } else {
                $alias = preg_replace("|(.*)-([\d]+)|", "$1-{$i}", $alias);
            }
            $i++;
            return $this->checkAlias($alias, $logalias_cache, $logid);
        }
        return $alias;
    }

    public function authPassword($postPwd, $cookiePwd, $logPwd, $logid) {
        $url = SITE_URL;
        $pwd = $cookiePwd ?: $postPwd;
        if ($pwd !== addslashes($logPwd)) {
            if (view::isTplExist('pw')) {
                include view::getView('pw');
            } else {
                $home = htmlspecialchars(rtrim($url, '/') . '/', ENT_QUOTES, 'UTF-8');
                $site = htmlspecialchars((string)(Option::get('blogname') ?: Option::get('sitename') ?: 'Hope CMS'), ENT_QUOTES, 'UTF-8');
                $wrong = ($postPwd !== '' && $postPwd !== null);
                $errorHtml = $wrong
                    ? '<p class="pw-error" role="alert">密码不正确，请重试</p>'
                    : '';
                echo <<<EOT
<!doctype html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="renderer" content="webkit">
<title>访问密码 · {$site}</title>
<style>
:root {
  --ink: #101820;
  --muted: #5a6a78;
  --line: rgba(16, 24, 32, .12);
  --panel: rgba(255, 255, 255, .82);
  --accent: #0e7490;
  --glow: rgba(14, 116, 144, .28);
  --danger: #b42318;
  --danger-bg: rgba(180, 35, 24, .08);
}
* { box-sizing: border-box; }
html, body { height: 100%; }
body {
  margin: 0;
  color: var(--ink);
  font-family: "Outfit", "PingFang SC", "Microsoft YaHei", sans-serif;
  background:
    radial-gradient(1100px 640px at 8% -8%, rgba(14, 116, 144, .2), transparent 55%),
    radial-gradient(900px 560px at 100% 8%, rgba(15, 118, 110, .14), transparent 48%),
    linear-gradient(165deg, #dbe7ee 0%, #eef3f6 42%, #d9e6e4 100%);
  min-height: 100%;
  display: grid;
  place-items: center;
  padding: 24px 16px;
}
.pw-shell {
  width: min(420px, 100%);
  animation: pw-rise .55s cubic-bezier(.22, 1, .36, 1) both;
}
@keyframes pw-rise {
  from { opacity: 0; transform: translateY(18px) scale(.98); }
  to { opacity: 1; transform: none; }
}
.pw-brand {
  text-align: center;
  margin-bottom: 18px;
  letter-spacing: .04em;
  font-size: .85rem;
  font-weight: 600;
  color: var(--muted);
}
.pw-panel {
  background: var(--panel);
  backdrop-filter: blur(14px);
  border: 1px solid var(--line);
  border-radius: 22px;
  padding: 36px 28px 28px;
  box-shadow:
    0 1px 0 rgba(255,255,255,.7) inset,
    0 24px 60px rgba(20, 33, 43, .12);
}
.pw-lock {
  width: 56px;
  height: 56px;
  margin: 0 auto 18px;
  border-radius: 16px;
  display: grid;
  place-items: center;
  background: linear-gradient(145deg, #0e7490, #155e75);
  color: #fff;
  box-shadow: 0 12px 28px var(--glow);
  animation: pw-pulse 2.8s ease-in-out infinite;
}
@keyframes pw-pulse {
  0%, 100% { transform: translateY(0); box-shadow: 0 12px 28px var(--glow); }
  50% { transform: translateY(-3px); box-shadow: 0 18px 34px var(--glow); }
}
.pw-lock svg { width: 26px; height: 26px; }
.pw-title {
  margin: 0 0 8px;
  text-align: center;
  font-family: "Source Serif 4", "Songti SC", serif;
  font-size: 1.65rem;
  font-weight: 700;
  line-height: 1.25;
}
.pw-desc {
  margin: 0 0 22px;
  text-align: center;
  color: var(--muted);
  font-size: .95rem;
  line-height: 1.5;
}
.pw-error {
  margin: -6px 0 16px;
  padding: 10px 12px;
  border-radius: 12px;
  background: var(--danger-bg);
  color: var(--danger);
  font-size: .9rem;
  text-align: center;
  animation: pw-shake .4s ease;
}
@keyframes pw-shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-5px); }
  75% { transform: translateX(5px); }
}
.pw-field {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.pw-field label {
  font-size: .8rem;
  font-weight: 600;
  color: var(--muted);
  letter-spacing: .02em;
}
.pw-field input {
  width: 100%;
  height: 48px;
  border: 1px solid var(--line);
  border-radius: 14px;
  padding: 0 16px;
  font: inherit;
  font-size: 1rem;
  background: #fff;
  color: var(--ink);
  outline: none;
  transition: border-color .2s, box-shadow .2s;
}
.pw-field input:focus {
  border-color: rgba(14, 116, 144, .55);
  box-shadow: 0 0 0 4px rgba(14, 116, 144, .15);
}
.pw-submit {
  margin-top: 6px;
  width: 100%;
  height: 48px;
  border: 0;
  border-radius: 14px;
  cursor: pointer;
  font: inherit;
  font-weight: 700;
  letter-spacing: .02em;
  color: #fff;
  background: linear-gradient(135deg, #0e7490, #0f766e 55%, #155e75);
  box-shadow: 0 10px 24px var(--glow);
  transition: transform .15s ease, filter .15s ease;
}
.pw-submit:hover { filter: brightness(1.05); transform: translateY(-1px); }
.pw-submit:active { transform: translateY(0); }
.pw-back {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  margin-top: 18px;
  width: 100%;
  color: var(--muted);
  text-decoration: none;
  font-size: .9rem;
  transition: color .15s;
}
.pw-back:hover { color: var(--accent); }
@media (prefers-reduced-motion: reduce) {
  .pw-shell, .pw-lock, .pw-error { animation: none; }
}
</style>
</head>
<body>
  <main class="pw-shell">
    <div class="pw-brand">{$site}</div>
    <form class="pw-panel" action="" method="post" autocomplete="current-password">
      <div class="pw-lock" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <rect x="4.5" y="10.5" width="15" height="10" rx="2.5"></rect>
          <path d="M8 10.5V7.8a4 4 0 0 1 8 0v2.7"></path>
        </svg>
      </div>
      <h1 class="pw-title">受保护的内容</h1>
      <p class="pw-desc">请输入访问密码后继续阅读</p>
      {$errorHtml}
      <div class="pw-field">
        <label for="logpwd">文章访问密码</label>
        <input type="password" id="logpwd" name="logpwd" placeholder="输入密码" required autofocus>
        <button class="pw-submit" type="submit">确认访问</button>
      </div>
      <a class="pw-back" href="{$home}">← 返回首页</a>
    </form>
  </main>
</body>
</html>
EOT;
            }
            if ($cookiePwd) {
                setcookie('hp_logpwd_' . $logid, ' ', time() - 31536000);
            }
            exit;
        }

        setcookie('hp_logpwd_' . $logid, $logPwd);
    }

    public function checkEditable($gid) {
        if (User::haveEditPermission()) {
            return;
        }
        $r = $this->getOneLogForAdmin($gid);
        if (!$r || !isset($r['checked'])) {
            return;
        }
        if ($r['checked'] === 'y' && Option::get('article_uneditable') === 'y') {
            hope_abort('审核通过的文章不可编辑和删除');
        }
    }

    private function buildPublicQuery($filters = []) {
        $q = Database::table('article');
        $q->where(['type' => 'blog', 'hide' => 'n', 'checked' => 'y']);
        $q->whereRaw('date <= ' . time());
        if (isset($filters['sortid'])) {
            $q->where(['sortid' => (int)$filters['sortid']]);
        }
        if (isset($filters['sortids'])) {
            $q->whereIn('sortid', $filters['sortids']);
        }
        if (!empty($filters['keyword'])) {
            $q->whereLike('title', $filters['keyword']);
        }
        if (!empty($filters['author'])) {
            $q->where(['author' => (int)$filters['author']]);
        }
        if (isset($filters['gid_in'])) {
            $q->whereIn('gid', $filters['gid_in']);
        }
        if (isset($filters['date_gte'])) {
            $q->whereRaw('date >= ' . (int)$filters['date_gte']);
        }
        if (isset($filters['date_lt'])) {
            $q->whereRaw('date < ' . (int)$filters['date_lt']);
        }
        if (isset($filters['date_gt'])) {
            $q->whereRaw('date > ' . (int)$filters['date_gt']);
        }
        return $q;
    }

    private function applySpotScope($query, $spot) {
        if ($spot == 0) {
            $query->whereRaw('date <= ' . time());
        } elseif (!User::haveEditPermission()) {
            $query->where(['author' => (int)UID]);
        }
    }

    private function countAdminLogs($filters, $hide, $type, $spot) {
        $q = Database::table('article')->where(['type' => $type]);
        if ($hide) {
            $q->where(['hide' => $hide]);
        }
        $this->applySpotScope($q, $spot);
        $this->applyAdminFilters($q, $filters);
        return $q->count();
    }

    private function getAdminArticleList($filters, $hide_state, $page, $type, $perpage_num) {
        $page = max(1, (int)$page);
        $perpage_num = max(1, (int)$perpage_num);
        $offset = ($page - 1) * $perpage_num;
        $q = Database::table('article')->where(['type' => $type]);
        if (!User::haveEditPermission()) {
            $q->where(['author' => (int)UID]);
        }
        if ($hide_state) {
            $q->where(['hide' => $hide_state]);
        }
        $this->applyAdminFilters($q, $filters);
        if (!empty($filters['order'])) {
            $q->order($filters['order']);
        }
        $rows = $q->limit($perpage_num, $offset)->findAll();
        $logs = [];
        foreach ($rows as $row) {
            $row['timestamp'] = $row['date'];
            $row['date'] = date('Y-m-d H:i', $row['date']);
            $row['title'] = !empty($row['title']) ? htmlspecialchars($row['title']) : '无标题';
            $logs[] = $row;
        }
        return $logs;
    }

    private function applyAdminFilters($query, $filters) {
        if (!is_array($filters)) {
            return;
        }
        if (isset($filters['sortid'])) {
            $query->where(['sortid' => (int)$filters['sortid']]);
        }
        if (isset($filters['sortids'])) {
            $query->whereIn('sortid', $filters['sortids']);
        }
        if (!empty($filters['keyword'])) {
            $query->whereLike('title', $filters['keyword']);
        }
        if (!empty($filters['author'])) {
            $query->where(['author' => (int)$filters['author']]);
        }
        if (isset($filters['gid_in'])) {
            $query->whereIn('gid', $filters['gid_in']);
        }
    }

    /**
     * 拆分旧版 condition 字符串中的 WHERE 与 ORDER BY
     */
    private function parseLegacyAdminCondition($condition) {
        $condition = trim($condition);
        $order = '';
        $where = $condition;
        if ($condition !== '' && preg_match('/\s+ORDER\s+BY\s+(.*)$/is', $condition, $m)) {
            $order = trim($m[1]);
            $where = trim(substr($condition, 0, -strlen($m[0])));
        }
        $where = preg_replace('/^\s*and\s+/i', '', $where);
        return ['where' => $where, 'order' => $order];
    }

    private function formatHomeLogs($rows) {
        $logs = [];
        foreach ($rows as $row) {
            $row['log_title'] = htmlspecialchars(trim($row['title']));
            $row['log_cover'] = $row['cover'] ? getFileUrl($row['cover']) : '';
            $row['log_url'] = Url::log($row['gid']);
            $row['logid'] = $row['gid'];
            $cookiePassword = trim($_COOKIE['hp_logpwd_' . $row['gid']] ?? '');
            if (!empty($row['password']) && $cookiePassword !== $row['password']) {
                $row['excerpt'] = '<p>[该文章已加密，请点击标题输入密码访问]</p>';
            }
            $row['log_description'] = $this->Parsedown->text(empty($row['excerpt']) ? $row['content'] : $row['excerpt']);
            $row['attachment'] = '';
            $row['tag'] = '';
            $row['tbcount'] = 0;
            $logs[] = $row;
        }
        return $logs;
    }

    private function formatOneLogForHome($row) {
        return [
            'log_title'    => htmlspecialchars($row['title']),
            'timestamp'    => $row['date'],
            'date'         => $row['date'],
            'logid'        => (int)$row['gid'],
            'sortid'       => (int)$row['sortid'],
            'type'         => $row['type'],
            'author'       => $row['author'],
            'log_cover'    => $row['cover'] ? getFileUrl($row['cover']) : '',
            'excerpt'      => $this->Parsedown->text($row['excerpt']),
            'log_content'  => $this->Parsedown->text($row['content']),
            'views'        => (int)$row['views'],
            'comnum'       => (int)$row['comnum'],
            'top'          => $row['top'],
            'sortop'       => $row['sortop'],
            'hide'         => $row['hide'],
            'checked'      => $row['checked'],
            'allow_remark' => Option::get('iscomment') == 'y' ? $row['allow_remark'] : 'n',
            'password'     => $row['password'],
            'template'     => $row['template'],
            'link'         => $row['link'],
            'tags'         => $row['tags'],
        ];
    }
}
