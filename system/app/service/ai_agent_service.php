<?php
/**
 * Hope CMS AI 助手 — 数据库读写工具（敏感操作需用户确认）
 */
defined('HOPE_ROOT') || exit('access denied!');

function hope_ai_agent_is_readonly() {
    return Option::get('ai_agent_readonly') === 'y';
}

function hope_ai_agent_system_prompt() {
    return <<<'PROMPT'
你是 Hope CMS 后台 AI 助手，可深度协助内容创作与站点搭建。

可用工具（用 ```hope_action 代码块输出 JSON，每次一个工具）：
- get_stats：获取站点统计 {"tool":"get_stats","params":{}}
- get_option：读取配置 {"tool":"get_option","params":{"key":"sitename"}}
- list_articles：文章列表 {"tool":"list_articles","params":{"limit":5,"keyword":""}}
- get_article：读取文章 {"tool":"get_article","params":{"id":1}}
- list_categories：分类列表 {"tool":"list_categories","params":{}}
- list_pages：页面列表 {"tool":"list_pages","params":{"limit":20}}
- update_option：修改配置 {"tool":"update_option","params":{"key":"sitename","value":"新名称"}}
- create_article_draft：创建草稿 {"tool":"create_article_draft","params":{"title":"标题","content":"正文","excerpt":"摘要","tags":"标签1,标签2","sortid":1,"cover":""}}
- update_article：更新文章 {"tool":"update_article","params":{"id":1,"title":"","content":"","excerpt":"","sortid":1}}
- delete_article：删除文章 {"tool":"delete_article","params":{"id":1}}
- create_category：创建分类 {"tool":"create_category","params":{"name":"分类名","alias":"alias","description":"简介"}}
- create_page：创建页面 {"tool":"create_page","params":{"title":"关于我们","alias":"about","content":"Markdown正文"}}
- organize_drafts：为缺少摘要的草稿补摘要建议（只读分析） {"tool":"organize_drafts","params":{"limit":10}}

规则：
1. 需要查数据或改数据时，先输出 ```hope_action 工具调用，再用自然语言说明将要做什么。
2. 修改/删除类操作需用户确认后才会执行；查询类会自动执行。
3. 不要编造查询结果，等工具执行结果后再回答。
4. 禁止修改 API Key、支付密钥、密码类配置。
5. 用户要求「一键生成分类/页面/改设置」时，优先使用 create_category、create_page、update_option，并分步说明。
PROMPT;
}

function hope_ai_agent_tool_meta($tool) {
    $meta = [
        'get_stats'           => ['type' => 'read',  'label' => '获取站点统计', 'sensitive' => false],
        'get_option'          => ['type' => 'read',  'label' => '读取系统配置', 'sensitive' => false],
        'list_articles'       => ['type' => 'read',  'label' => '查询文章列表', 'sensitive' => false],
        'get_article'         => ['type' => 'read',  'label' => '读取文章内容', 'sensitive' => false],
        'list_categories'     => ['type' => 'read',  'label' => '查询分类列表', 'sensitive' => false],
        'list_pages'          => ['type' => 'read',  'label' => '查询页面列表', 'sensitive' => false],
        'organize_drafts'     => ['type' => 'read',  'label' => '整理草稿分析', 'sensitive' => false],
        'update_option'       => ['type' => 'write', 'label' => '修改系统配置', 'sensitive' => true],
        'create_article_draft'=> ['type' => 'write', 'label' => '创建文章草稿', 'sensitive' => true],
        'update_article'      => ['type' => 'write', 'label' => '更新文章',     'sensitive' => true],
        'delete_article'      => ['type' => 'write', 'label' => '删除文章',     'sensitive' => true, 'danger' => true],
        'create_category'     => ['type' => 'write', 'label' => '创建分类',     'sensitive' => true],
        'create_page'         => ['type' => 'write', 'label' => '创建页面',     'sensitive' => true],
    ];
    return $meta[$tool] ?? null;
}

function hope_ai_agent_blocked_option_keys() {
    return [
        'ai_api_key', 'api_key', 'api_password', 'mail_pass', 'smtp_pass',
        'payment', 'wxpay', 'alipay', 'official_pay', 'backend_theme',
    ];
}

function hope_ai_agent_allowed_option_keys() {
    return [
        'sitename', 'siteinfo', 'site_title', 'site_description', 'site_key',
        'footer_info', 'comment_need_check', 'index_lognum', 'posts_name',
    ];
}

function hope_ai_agent_extract_actions($content) {
    $pattern = '/```hope_action\s*\n?(.*?)\n?```/s';
    preg_match_all($pattern, (string)$content, $matches);
    $actions = [];
    foreach ($matches[1] as $json) {
        $item = json_decode(trim($json), true);
        if (!is_array($item) || empty($item['tool'])) {
            continue;
        }
        $actions[] = [
            'tool'   => trim((string)$item['tool']),
            'params' => is_array($item['params'] ?? null) ? $item['params'] : [],
        ];
    }
    $display = trim(preg_replace($pattern, '', (string)$content));
    return ['actions' => $actions, 'content' => $display];
}

function hope_ai_agent_describe_action($action) {
    $tool = $action['tool'] ?? '';
    $params = $action['params'] ?? [];
    $meta = hope_ai_agent_tool_meta($tool);
    if (!$meta) {
        return '未知操作：' . $tool;
    }
    switch ($tool) {
        case 'update_option':
            return $meta['label'] . '：' . ($params['key'] ?? '') . ' → ' . mb_substr((string)($params['value'] ?? ''), 0, 80);
        case 'create_article_draft':
            return $meta['label'] . '：《' . ($params['title'] ?? '无标题') . '》';
        case 'update_article':
            return $meta['label'] . '：ID ' . ($params['id'] ?? '') . (isset($params['title']) ? ' 《' . $params['title'] . '》' : '');
        case 'delete_article':
            return $meta['label'] . '：ID ' . ($params['id'] ?? '');
        case 'create_category':
            return $meta['label'] . '：' . ($params['name'] ?? '');
        case 'create_page':
            return $meta['label'] . '：《' . ($params['title'] ?? '无标题') . '》';
        default:
            return $meta['label'];
    }
}

function hope_ai_agent_run($action) {
    $tool = $action['tool'] ?? '';
    $params = $action['params'] ?? [];
    $meta = hope_ai_agent_tool_meta($tool);
    if (!$meta) {
        return ['ok' => false, 'msg' => '不支持的工具：' . $tool];
    }
    if ($meta['type'] === 'write' && hope_ai_agent_is_readonly()) {
        return ['ok' => false, 'msg' => 'AI 助手已开启只读模式，写操作已禁用'];
    }

    switch ($tool) {
        case 'get_stats':
            $result = hope_ai_agent_tool_get_stats();
            break;
        case 'get_option':
            $result = hope_ai_agent_tool_get_option($params);
            break;
        case 'list_articles':
            $result = hope_ai_agent_tool_list_articles($params);
            break;
        case 'get_article':
            $result = hope_ai_agent_tool_get_article($params);
            break;
        case 'update_option':
            $result = hope_ai_agent_tool_update_option($params);
            break;
        case 'create_article_draft':
            $result = hope_ai_agent_tool_create_article($params, true);
            break;
        case 'update_article':
            $result = hope_ai_agent_tool_update_article($params);
            break;
        case 'delete_article':
            $result = hope_ai_agent_tool_delete_article($params);
            break;
        case 'list_categories':
            $result = hope_ai_agent_tool_list_categories();
            break;
        case 'list_pages':
            $result = hope_ai_agent_tool_list_pages($params);
            break;
        case 'organize_drafts':
            $result = hope_ai_agent_tool_organize_drafts($params);
            break;
        case 'create_category':
            $result = hope_ai_agent_tool_create_category($params);
            break;
        case 'create_page':
            $result = hope_ai_agent_tool_create_page($params);
            break;
        default:
            $result = ['ok' => false, 'msg' => '未知工具'];
    }

    if ($meta['type'] === 'write') {
        hope_audit_log(
            'ai_agent',
            $tool,
            $params,
            !empty($result['ok']) ? 'success' : 'fail',
            $result['msg'] ?? ''
        );
    }
    return $result;
}

function hope_ai_agent_tool_get_stats() {
    $Log_Model = new Log_Model();
    $Comment_Model = new Comment_Model();
    return [
        'ok'   => true,
        'data' => [
            'sitename'       => Option::get('sitename'),
            'siteurl'        => Option::get('siteurl'),
            'articles_total' => (int)$Log_Model->getLogNum('n', '', 'blog'),
            'articles_draft' => (int)$Log_Model->getLogNum('y', '', 'blog'),
            'comments'       => method_exists($Comment_Model, 'getCommentNum') ? (int)$Comment_Model->getCommentNum() : 0,
        ],
    ];
}

function hope_ai_agent_tool_get_option($params) {
    $key = trim((string)($params['key'] ?? ''));
    if ($key === '') {
        return ['ok' => false, 'msg' => '缺少配置项 key'];
    }
    foreach (hope_ai_agent_blocked_option_keys() as $blocked) {
        if (stripos($key, $blocked) !== false) {
            return ['ok' => false, 'msg' => '不允许读取敏感配置：' . $key];
        }
    }
    if (!in_array($key, hope_ai_agent_allowed_option_keys(), true)) {
        return ['ok' => false, 'msg' => '配置项不在允许列表：' . $key];
    }
    return ['ok' => true, 'data' => ['key' => $key, 'value' => Option::get($key)]];
}

function hope_ai_agent_tool_list_articles($params) {
    $Log_Model = new Log_Model();
    $limit = max(1, min(20, (int)($params['limit'] ?? 5)));
    $keyword = trim((string)($params['keyword'] ?? ''));
    $condition = '';
    if ($keyword !== '') {
        $DB = Database::getInstance();
        $kw = $DB->escape_string($keyword);
        $condition = " AND (title LIKE '%{$kw}%' OR content LIKE '%{$kw}%')";
    }
    $list = $Log_Model->getArticleList($condition, '', 1, 'blog', $limit);
    $rows = [];
    if (is_array($list)) {
        foreach ($list as $row) {
            $rows[] = [
                'id'    => (int)($row['gid'] ?? 0),
                'title' => $row['title'] ?? '',
                'date'  => isset($row['date']) ? date('Y-m-d H:i', (int)$row['date']) : '',
                'hide'  => ($row['hide'] ?? 'n') === 'y' ? 'draft' : 'published',
            ];
        }
    }
    return ['ok' => true, 'data' => ['articles' => $rows]];
}

function hope_ai_agent_tool_get_article($params) {
    $id = (int)($params['id'] ?? 0);
    if ($id <= 0) {
        return ['ok' => false, 'msg' => '无效文章 ID'];
    }
    $Log_Model = new Log_Model();
    $row = $Log_Model->getOneLogForAdmin($id);
    if (empty($row)) {
        return ['ok' => false, 'msg' => '文章不存在'];
    }
    return [
        'ok'   => true,
        'data' => [
            'id'      => $id,
            'title'   => $row['title'] ?? '',
            'excerpt' => $row['excerpt'] ?? '',
            'content' => hope_ai_truncate(strip_tags($row['content'] ?? ''), 3000),
            'hide'    => ($row['hide'] ?? 'n') === 'y' ? 'draft' : 'published',
        ],
    ];
}

function hope_ai_agent_tool_update_option($params) {
    global $CACHE;
    $key = trim((string)($params['key'] ?? ''));
    $value = (string)($params['value'] ?? '');
    if ($key === '') {
        return ['ok' => false, 'msg' => '缺少配置项 key'];
    }
    foreach (hope_ai_agent_blocked_option_keys() as $blocked) {
        if (stripos($key, $blocked) !== false) {
            return ['ok' => false, 'msg' => '禁止修改敏感配置：' . $key];
        }
    }
    if (!in_array($key, hope_ai_agent_allowed_option_keys(), true)) {
        return ['ok' => false, 'msg' => '配置项不在允许修改列表：' . $key];
    }
    Option::updateOption($key, $value);
    if (isset($CACHE)) {
        $CACHE->updateCache('options');
    }
    return ['ok' => true, 'data' => ['key' => $key, 'value' => $value], 'msg' => '配置已更新'];
}

function hope_ai_agent_tool_create_article($params, $draft = true) {
    global $CACHE;
    $title = trim((string)($params['title'] ?? ''));
    $content = (string)($params['content'] ?? '');
    if ($title === '') {
        return ['ok' => false, 'msg' => '标题不能为空'];
    }
    $sortid = (int)($params['sortid'] ?? -1);
    $Log_Model = new Log_Model();
    $logData = [
        'title'        => $title,
        'content'      => $content,
        'excerpt'      => trim((string)($params['excerpt'] ?? '')),
        'author'       => UID,
        'sortid'       => $sortid > 0 ? $sortid : -1,
        'date'         => time(),
        'hide'         => $draft ? 'y' : 'n',
        'allow_remark' => 'y',
        'checked'      => 'y',
        'type'         => 'blog',
        'cover'        => trim((string)($params['cover'] ?? '')),
    ];
    $id = (int)$Log_Model->addlog($logData);
    $tags = trim(str_replace('，', ',', (string)($params['tags'] ?? '')));
    if ($id > 0 && $tags !== '') {
        $Tag_Model = new Tag_Model();
        $Tag_Model->addTag($tags, $id);
    }
    if (isset($CACHE)) {
        $CACHE->updateArticleCache();
    }
    return ['ok' => true, 'data' => ['id' => $id, 'title' => $title], 'msg' => '草稿已创建，ID ' . $id];
}

function hope_ai_agent_tool_list_categories() {
    $Sort_Model = new Sort_Model();
    $sorts = $Sort_Model->getSorts();
    $rows = [];
    foreach ($sorts as $sid => $row) {
        $rows[] = [
            'id'          => (int)$sid,
            'name'        => html_entity_decode($row['sortname'] ?? '', ENT_QUOTES, 'UTF-8'),
            'alias'       => $row['alias'] ?? '',
            'description' => html_entity_decode($row['description'] ?? '', ENT_QUOTES, 'UTF-8'),
            'lognum'      => (int)($row['lognum'] ?? 0),
        ];
    }
    return ['ok' => true, 'data' => ['categories' => $rows]];
}

function hope_ai_agent_tool_list_pages($params) {
    $Log_Model = new Log_Model();
    $limit = max(1, min(50, (int)($params['limit'] ?? 20)));
    $list = $Log_Model->getArticleList('', '', 1, 'page', $limit);
    $rows = [];
    if (is_array($list)) {
        foreach ($list as $row) {
            $rows[] = [
                'id'    => (int)($row['gid'] ?? 0),
                'title' => $row['title'] ?? '',
                'alias' => $row['alias'] ?? '',
                'hide'  => ($row['hide'] ?? 'n') === 'y' ? 'draft' : 'published',
            ];
        }
    }
    return ['ok' => true, 'data' => ['pages' => $rows]];
}

function hope_ai_agent_tool_organize_drafts($params) {
    $Log_Model = new Log_Model();
    $limit = max(1, min(20, (int)($params['limit'] ?? 10)));
    $list = $Log_Model->getArticleList('', 'y', 1, 'blog', $limit);
    $suggestions = [];
    if (is_array($list)) {
        foreach ($list as $row) {
            $excerpt = trim(strip_tags($row['excerpt'] ?? ''));
            $title = $row['title'] ?? '';
            $need = [];
            if ($excerpt === '') {
                $need[] = '缺少摘要';
            }
            if (trim(strip_tags($row['content'] ?? '')) === '') {
                $need[] = '正文为空';
            }
            if ((int)($row['sortid'] ?? -1) <= 0) {
                $need[] = '未分类';
            }
            if (!empty($need)) {
                $suggestions[] = [
                    'id'     => (int)($row['gid'] ?? 0),
                    'title'  => $title,
                    'issues' => $need,
                    'hint'   => '可用 AI 写作助手生成摘要/归类后更新文章',
                ];
            }
        }
    }
    return ['ok' => true, 'data' => ['suggestions' => $suggestions, 'count' => count($suggestions)]];
}

function hope_ai_agent_tool_create_category($params) {
    global $CACHE;
    $name = trim((string)($params['name'] ?? ''));
    if ($name === '') {
        return ['ok' => false, 'msg' => '分类名不能为空'];
    }
    $alias = hope_ai_slugify($params['alias'] ?? $name);
    $DB = Database::getInstance();
    $Sort_Model = new Sort_Model();
    $sid = (int)$Sort_Model->addSort([
        'sortname'    => $DB->escape_string($name),
        'pid'         => 0,
        'style'       => '',
        'keywords'    => '',
        'description' => $DB->escape_string(trim((string)($params['description'] ?? ''))),
        'logstyle'    => '',
        'alias'       => $DB->escape_string($alias),
    ]);
    if (isset($CACHE)) {
        $CACHE->updateCache(['sort', 'logsort', 'menu']);
    }
    return ['ok' => true, 'data' => ['id' => $sid, 'name' => $name, 'alias' => $alias], 'msg' => '分类已创建，ID ' . $sid];
}

function hope_ai_agent_tool_create_page($params) {
    global $CACHE;
    $title = trim((string)($params['title'] ?? ''));
    if ($title === '') {
        return ['ok' => false, 'msg' => '页面标题不能为空'];
    }
    $alias = hope_ai_slugify($params['alias'] ?? $title);
    $Log_Model = new Log_Model();
    $id = (int)$Log_Model->addlog([
        'title'        => $title,
        'content'      => (string)($params['content'] ?? ''),
        'excerpt'      => '',
        'date'         => time(),
        'allow_remark' => 'n',
        'hide'         => 'n',
        'alias'        => $alias,
        'type'         => 'page',
        'template'     => 'page',
        'author'       => UID,
        'checked'      => 'y',
    ]);
    if (isset($CACHE)) {
        $CACHE->updateCache(['options', 'logalias']);
    }
    return ['ok' => true, 'data' => ['id' => $id, 'title' => $title, 'alias' => $alias], 'msg' => '页面已创建，ID ' . $id];
}

function hope_ai_agent_tool_update_article($params) {
    global $CACHE;
    $id = (int)($params['id'] ?? 0);
    if ($id <= 0) {
        return ['ok' => false, 'msg' => '无效文章 ID'];
    }
    $Log_Model = new Log_Model();
    $Log_Model->checkEditable($id);
    $logData = [];
    if (isset($params['title'])) {
        $logData['title'] = trim((string)$params['title']);
    }
    if (isset($params['content'])) {
        $logData['content'] = (string)$params['content'];
    }
    if (isset($params['excerpt'])) {
        $logData['excerpt'] = trim((string)$params['excerpt']);
    }
    if (isset($params['sortid'])) {
        $logData['sortid'] = (int)$params['sortid'];
    }
    if (isset($params['cover'])) {
        $logData['cover'] = trim((string)$params['cover']);
    }
    if (empty($logData)) {
        return ['ok' => false, 'msg' => '没有可更新的字段'];
    }
    $Log_Model->updateLog($logData, $id);
    if (isset($CACHE)) {
        $CACHE->updateArticleCache();
    }
    return ['ok' => true, 'data' => ['id' => $id], 'msg' => '文章已更新'];
}

function hope_ai_agent_tool_delete_article($params) {
    global $CACHE;
    $id = (int)($params['id'] ?? 0);
    if ($id <= 0) {
        return ['ok' => false, 'msg' => '无效文章 ID'];
    }
    $Log_Model = new Log_Model();
    $Log_Model->checkEditable($id);
    $Log_Model->deleteLog($id);
    if (isset($CACHE)) {
        $CACHE->updateArticleCache();
    }
    return ['ok' => true, 'data' => ['id' => $id], 'msg' => '文章已删除'];
}

function hope_ai_agent_process_response($content, $executeWrites = false) {
    $parsed = hope_ai_agent_extract_actions($content);
    $readResults = [];
    $pending = [];
    $messages = [];

    foreach ($parsed['actions'] as $action) {
        $meta = hope_ai_agent_tool_meta($action['tool']);
        if (!$meta) {
            $messages[] = '跳过未知工具：' . $action['tool'];
            continue;
        }
        if ($meta['type'] === 'read') {
            $result = hope_ai_agent_run($action);
            $readResults[] = [
                'tool'   => $action['tool'],
                'result' => $result,
            ];
            continue;
        }
        if (hope_ai_agent_is_readonly()) {
            $messages[] = '只读模式：已跳过「' . hope_ai_agent_describe_action($action) . '」';
            continue;
        }
        if ($executeWrites) {
            $result = hope_ai_agent_run($action);
            $messages[] = $result['ok'] ? ($result['msg'] ?? '操作成功') : ($result['msg'] ?? '操作失败');
            continue;
        }
        $pending[] = [
            'tool'        => $action['tool'],
            'params'      => $action['params'],
            'label'       => hope_ai_agent_describe_action($action),
            'sensitive'   => !empty($meta['sensitive']),
            'danger'      => !empty($meta['danger']),
            'action_json' => json_encode($action, JSON_UNESCAPED_UNICODE),
        ];
    }

    return [
        'content'      => $parsed['content'],
        'read_results' => $readResults,
        'pending'      => $pending,
        'messages'     => $messages,
    ];
}

function hope_ai_agent_format_read_results($readResults) {
    if (empty($readResults)) {
        return '';
    }
    $lines = ["\n\n---\n**工具执行结果：**"];
    foreach ($readResults as $item) {
        $json = json_encode($item['result'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $lines[] = '- `' . $item['tool'] . '`：' . $json;
    }
    return implode("\n", $lines);
}

function hope_ai_agent_chat($messages, $override = []) {
    $normalized = [];
    foreach ($messages as $msg) {
        if (!is_array($msg)) {
            continue;
        }
        $role = $msg['role'] ?? '';
        $content = trim($msg['content'] ?? '');
        if (!in_array($role, ['user', 'assistant'], true) || $content === '') {
            continue;
        }
        $normalized[] = ['role' => $role, 'content' => $content];
    }
    if (empty($normalized)) {
        return ['ok' => false, 'msg' => '请输入对话内容'];
    }

    array_unshift($normalized, ['role' => 'system', 'content' => hope_ai_agent_system_prompt()]);

    $result = hope_ai_chat($normalized, $override);
    if (!$result['ok']) {
        return $result;
    }

    $processed = hope_ai_agent_process_response($result['content'], false);
    $content = $processed['content'];
    if (!empty($processed['read_results'])) {
        $content .= hope_ai_agent_format_read_results($processed['read_results']);
    }

    return [
        'ok'             => true,
        'content'        => trim($content),
        'pending_actions'=> $processed['pending'],
    ];
}

function hope_ai_agent_execute_confirmed($actionRaw) {
    $action = is_array($actionRaw) ? $actionRaw : json_decode((string)$actionRaw, true);
    if (!is_array($action) || empty($action['tool'])) {
        return ['ok' => false, 'msg' => '操作数据无效'];
    }
    $meta = hope_ai_agent_tool_meta($action['tool']);
    if (!$meta || $meta['type'] !== 'write') {
        return ['ok' => false, 'msg' => '不允许执行该操作'];
    }
    return hope_ai_agent_run($action);
}

