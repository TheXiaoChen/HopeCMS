<?php
defined('HOPE_ROOT') || exit('access denied!');

// 支持归档页跟随 record，或 ?cal=YYYYMM 翻月
$calRaw = '';
if (!empty($_GET['cal'])) {
    $calRaw = preg_replace('/\D+/', '', (string)$_GET['cal']);
} elseif (!empty($GLOBALS['record'])) {
    $calRaw = preg_replace('/\D+/', '', (string)$GLOBALS['record']);
}

$year = (int)date('Y');
$month = (int)date('n');
if (preg_match('/^(\d{4})(\d{2})/', $calRaw, $m)) {
    $y = (int)$m[1];
    $mo = (int)$m[2];
    if ($y >= 1970 && $y <= 2100 && $mo >= 1 && $mo <= 12) {
        $year = $y;
        $month = $mo;
    }
}

$firstWeekday = (int)date('N', mktime(0, 0, 0, $month, 1, $year));
$daysInMonth = (int)date('t', mktime(0, 0, 0, $month, 1, $year));
$isCurrentMonth = ($year === (int)date('Y') && $month === (int)date('n'));
$today = $isCurrentMonth ? (int)date('j') : 0;

$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}
$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}
$prevCal = sprintf('%04d%02d', $prevYear, $prevMonth);
$nextCal = sprintf('%04d%02d', $nextYear, $nextMonth);

// 翻月保留在当前页，仅改 cal 参数
$reqUri = isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : SITE_URL;
$basePath = strtok($reqUri, '?');
if ($basePath === false || $basePath === '') {
    $basePath = '/';
}
$query = [];
if (!empty($_GET) && is_array($_GET)) {
    foreach ($_GET as $k => $v) {
        if ($k === 'cal' || !is_scalar($v)) {
            continue;
        }
        $query[$k] = $v;
    }
}
$query['cal'] = $prevCal;
$prevUrl = $basePath . '?' . http_build_query($query);
$query['cal'] = $nextCal;
$nextUrl = $basePath . '?' . http_build_query($query);

$DB = Database::getInstance();
$monthStart = mktime(0, 0, 0, $month, 1, $year);
$monthEnd = mktime(23, 59, 59, $month, $daysInMonth, $year);
$now = time();
$querySql = $DB->query(
    "SELECT date FROM " . DB_PREFIX . "article
     WHERE hide='n' AND checked='y' AND type='blog'
       AND date>={$monthStart} AND date<={$monthEnd} AND date<={$now}"
);
$activeDays = [];
while ($row = $DB->fetch_array($querySql)) {
    $activeDays[(int)date('j', (int)$row['date'])] = true;
}

$weekLabels = ['一', '二', '三', '四', '五', '六', '日'];
?>
<div class="widget widget-calendar">
    <h3 class="widget-title widget-title--line"><?= htmlspecialchars($widget_title ?: '日历') ?></h3>
    <div class="widget-calendar-nav">
        <a class="widget-calendar-nav-btn" href="<?= htmlspecialchars($prevUrl) ?>" aria-label="上个月">‹</a>
        <div class="widget-calendar-head"><?= $year ?>年<?= $month ?>月</div>
        <a class="widget-calendar-nav-btn" href="<?= htmlspecialchars($nextUrl) ?>" aria-label="下个月">›</a>
    </div>
    <table class="widget-calendar-table">
        <thead>
            <tr>
                <?php foreach ($weekLabels as $weekLabel): ?>
                    <th><?= $weekLabel ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <tr>
                <?php
                for ($i = 1; $i < $firstWeekday; $i++) {
                    echo '<td></td>';
                }
                $cell = $firstWeekday;
                for ($day = 1; $day <= $daysInMonth; $day++, $cell++) {
                    if ($cell > 7) {
                        echo '</tr><tr>';
                        $cell = 1;
                    }
                    $classes = [];
                    if ($day === $today) {
                        $classes[] = 'is-today';
                    }
                    $hasPost = !empty($activeDays[$day]);
                    if ($hasPost) {
                        $classes[] = 'has-post';
                    }
                    $classAttr = $classes ? ' class="' . implode(' ', $classes) . '"' : '';
                    if ($hasPost) {
                        // Url::record 需要 YYYYMMDD，不能传时间戳
                        $record = sprintf('%04d%02d%02d', $year, $month, $day);
                        echo '<td' . $classAttr . '><a href="' . htmlspecialchars(Url::record($record)) . '">' . $day . '</a></td>';
                    } else {
                        echo '<td' . $classAttr . '><span>' . $day . '</span></td>';
                    }
                }
                while ($cell <= 7) {
                    echo '<td></td>';
                    $cell++;
                }
                ?>
            </tr>
        </tbody>
    </table>
</div>
