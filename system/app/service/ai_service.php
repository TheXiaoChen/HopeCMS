<?php
/**
 * Hope CMS AI 服务 — OpenAI 兼容 Chat / Images
 */
defined('HOPE_ROOT') || exit('access denied!');

function hope_ai_decode_models($raw) {
    if ($raw === '' || $raw === null) {
        return [];
    }
    if (is_array($raw)) {
        $arr = $raw;
    } else {
        $str = (string)$raw;
        $arr = json_decode($str, true);
        if (!is_array($arr)) {
            $arr = json_decode(stripslashes($str), true);
        }
        if (!is_array($arr)) {
            return hope_ai_parse_models_text($str);
        }
    }
    $models = [];
    foreach ($arr as $item) {
        if (is_string($item)) {
            $id = trim($item);
            if ($id !== '') {
                $models[] = ['id' => $id, 'label' => $id];
            }
            continue;
        }
        if (!is_array($item)) {
            continue;
        }
        $id = trim($item['id'] ?? $item['model'] ?? '');
        if ($id === '') {
            continue;
        }
        $label = trim($item['label'] ?? $item['name'] ?? $id);
        $entry = ['id' => $id, 'label' => $label ?: $id];
        $apiBase = trim($item['api_base'] ?? '');
        if ($apiBase !== '') {
            $entry['api_base'] = hope_ai_normalize_api_base($apiBase);
        }
        $apiKey = trim(stripslashes((string)($item['api_key'] ?? '')));
        if ($apiKey !== '') {
            $entry['api_key'] = $apiKey;
        }
        $models[] = $entry;
    }
    return $models;
}

function hope_ai_mask_api_key($key) {
    $key = (string)$key;
    if ($key === '') {
        return '';
    }
    return str_repeat('*', max(8, strlen($key) - 4)) . substr($key, -4);
}

function hope_ai_models_for_view($models) {
    $out = [];
    foreach ($models as $item) {
        $key = $item['api_key'] ?? '';
        $row = [
            'id'            => $item['id'],
            'label'         => $item['label'],
            'api_base'      => $item['api_base'] ?? '',
            'has_api_key'   => $key !== '',
            'api_key_masked'=> hope_ai_mask_api_key($key),
        ];
        $out[] = $row;
    }
    return $out;
}

function hope_ai_resolve_api_key($modelId, $type = 'chat') {
    $models = $type === 'image' ? hope_ai_get_image_models() : hope_ai_get_chat_models();
    if ($modelId !== '') {
        foreach ($models as $m) {
            if (($m['id'] ?? '') === $modelId && !empty($m['api_key'])) {
                return $m['api_key'];
            }
        }
    }
    foreach ($models as $m) {
        if (!empty($m['api_key'])) {
            return $m['api_key'];
        }
    }
    return '';
}

function hope_ai_resolve_model_override($modelId, $type = 'chat') {
    $modelId = trim($modelId);
    if ($modelId === '') {
        return [];
    }
    $models = $type === 'image' ? hope_ai_get_image_models() : hope_ai_get_chat_models();
    $override = ['model' => $modelId];
    foreach ($models as $m) {
        if (($m['id'] ?? '') !== $modelId) {
            continue;
        }
        if (!empty($m['api_base'])) {
            $base = hope_ai_normalize_api_base($m['api_base']);
            $override['api_base'] = $base;
            if ($type === 'image') {
                $override['image_api_base'] = $base;
            }
        }
        if (!empty($m['api_key'])) {
            $override['api_key'] = $m['api_key'];
        }
        break;
    }
    return $override;
}

function hope_ai_parse_models_text($text) {
    $models = [];
    foreach (preg_split('/\r\n|\r|\n/', (string)$text) as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        if (strpos($line, '|') !== false) {
            list($id, $label) = array_map('trim', explode('|', $line, 2));
        } else {
            $id = $line;
            $label = $line;
        }
        if ($id !== '') {
            $models[] = ['id' => $id, 'label' => $label ?: $id];
        }
    }
    return $models;
}

function hope_ai_models_to_json($text) {
    $models = hope_ai_parse_models_text($text);
    return json_encode($models, JSON_UNESCAPED_UNICODE);
}

function hope_ai_models_to_text($models) {
    if (!is_array($models) || empty($models)) {
        return '';
    }
    $lines = [];
    foreach ($models as $item) {
        $id = trim($item['id'] ?? '');
        if ($id === '') {
            continue;
        }
        $label = trim($item['label'] ?? $id);
        $lines[] = $label === $id ? $id : ($id . '|' . $label);
    }
    return implode("\n", $lines);
}

function hope_ai_get_chat_models() {
    $models = hope_ai_decode_models(Option::get('ai_chat_models'));
    if (empty($models)) {
        $models = [['id' => 'gpt-4o-mini', 'label' => 'gpt-4o-mini']];
    }
    return $models;
}

function hope_ai_get_image_models() {
    $models = hope_ai_decode_models(Option::get('ai_image_models'));
    if (empty($models)) {
        $models = [
            ['id' => 'dall-e-3', 'label' => 'DALL-E 3'],
            ['id' => 'dall-e-2', 'label' => 'DALL-E 2'],
        ];
    }
    return $models;
}

function hope_ai_get_default_chat_model() {
    $def = trim(Option::get('ai_default_chat_model') ?: '');
    if ($def !== '') {
        return $def;
    }
    $models = hope_ai_get_chat_models();
    return $models[0]['id'] ?? 'gpt-4o-mini';
}

function hope_ai_get_default_image_model() {
    $def = trim(Option::get('ai_default_image_model') ?: '');
    if ($def !== '') {
        return $def;
    }
    $models = hope_ai_get_image_models();
    return $models[0]['id'] ?? 'dall-e-3';
}

function hope_ai_normalize_api_base($url) {
    $url = rtrim(trim((string)$url), '/');
    return preg_replace('#/(chat/completions|images/generations)$#', '', $url);
}

function hope_ai_resolve_api_base($modelId, $type = 'chat') {
    $models = $type === 'image' ? hope_ai_get_image_models() : hope_ai_get_chat_models();
    if ($modelId !== '') {
        foreach ($models as $m) {
            if (($m['id'] ?? '') === $modelId && !empty($m['api_base'])) {
                return hope_ai_normalize_api_base($m['api_base']);
            }
        }
    }
    foreach ($models as $m) {
        if (!empty($m['api_base'])) {
            return hope_ai_normalize_api_base($m['api_base']);
        }
    }
    return 'https://api.openai.com/v1';
}

function hope_ai_get_config($override = []) {
    $defaultChatModel = hope_ai_get_default_chat_model();
    $defaultImageModel = hope_ai_get_default_image_model();
    $config = [
        'enabled'       => Option::get('ai_enabled') === 'y',
        'api_base'      => hope_ai_resolve_api_base($defaultChatModel, 'chat'),
        'image_api_base'=> hope_ai_resolve_api_base($defaultImageModel, 'image'),
        'api_key'       => hope_ai_resolve_api_key($defaultChatModel, 'chat'),
        'model'         => $defaultChatModel,
        'temperature'   => (float)(Option::get('ai_temperature') !== '' ? Option::get('ai_temperature') : 0.7),
        'max_tokens'    => (int)(Option::get('ai_max_tokens') ?: 4096),
        'system_prompt' => Option::get('ai_system_prompt') ?: '你是 Hope CMS 专业内容创作助手，擅长中文博客写作、摘要提炼、多语言翻译与站点内容规划。输出简洁准确，严格遵循用户要求的格式，不要添加多余说明。',
    ];
    if (!empty($override)) {
        $config = array_merge($config, $override);
    }
    $config['temperature'] = max(0, min(2, (float)$config['temperature']));
    $config['max_tokens'] = max(256, min(32000, (int)$config['max_tokens']));
    if (!empty($config['api_base'])) {
        $config['api_base'] = rtrim($config['api_base'], '/');
    }
    if (!empty($config['image_api_base'])) {
        $config['image_api_base'] = rtrim($config['image_api_base'], '/');
    } elseif (!empty($config['api_base'])) {
        $config['image_api_base'] = $config['api_base'];
    }
    return $config;
}

function hope_ai_is_ready() {
    if (Option::get('ai_enabled') !== 'y') {
        return false;
    }
    foreach (hope_ai_get_chat_models() as $m) {
        if (!empty($m['api_key'])) {
            return true;
        }
    }
    return false;
}

function hope_ai_friendly_error($msg) {
    $msg = trim((string)$msg);
    if ($msg === '') {
        return '未知错误';
    }
    $map = [
        'Insufficient Balance'       => '账户余额不足，请前往服务商控制台充值后再试（连接本身已成功）',
        'insufficient balance'       => '账户余额不足，请前往服务商控制台充值后再试（连接本身已成功）',
        'Invalid API Key'            => 'API Key 无效，请检查是否填写正确或已失效',
        'invalid api key'            => 'API Key 无效，请检查是否填写正确或已失效',
        'Incorrect API key provided' => 'API Key 无效，请检查是否填写正确或已失效',
        'Model not found'            => '模型不存在，请检查 Model 名称是否正确',
        'model not found'            => '模型不存在，请检查 Model 名称是否正确',
        'Rate limit'                 => '请求过于频繁，请稍后再试',
        'rate limit'                 => '请求过于频繁，请稍后再试',
    ];
    foreach ($map as $needle => $friendly) {
        if (stripos($msg, $needle) !== false) {
            return $friendly;
        }
    }
    return $msg;
}

function hope_ai_request_json($url, $payload, $config, $timeout = 120) {
    if (!extension_loaded('curl')) {
        return ['ok' => false, 'msg' => '服务器未安装 PHP Curl 扩展'];
    }
    if ($config['api_key'] === '') {
        return ['ok' => false, 'msg' => '请先为该模型配置 API Key'];
    }

    $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($body === false) {
        return ['ok' => false, 'msg' => '请求参数编码失败'];
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $config['api_key'],
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);

    $response = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errno) {
        return ['ok' => false, 'msg' => '请求失败：' . $error];
    }

    $data = json_decode($response, true);
    if ($status < 200 || $status >= 300) {
        $msg = $data['error']['message'] ?? ('HTTP ' . $status);
        return ['ok' => false, 'msg' => hope_ai_friendly_error($msg), 'raw' => $data];
    }

    return ['ok' => true, 'data' => $data];
}

function hope_ai_chat($messages, $override = []) {
    $config = hope_ai_get_config($override);
    $url = $config['api_base'] . '/chat/completions';
    $payload = [
        'model'       => $config['model'],
        'messages'    => $messages,
        'temperature' => $config['temperature'],
        'max_tokens'  => $config['max_tokens'],
    ];

    $result = hope_ai_request_json($url, $payload, $config);
    if (!$result['ok']) {
        return $result;
    }

    $data = $result['data'];
    $content = $data['choices'][0]['message']['content'] ?? '';
    if ($content === '') {
        return ['ok' => false, 'msg' => 'AI 返回内容为空'];
    }

    return ['ok' => true, 'content' => trim($content), 'raw' => $data];
}

function hope_ai_conversation($messages, $override = []) {
    $config = hope_ai_get_config($override);
    $system = $config['system_prompt'];
    $normalized = [];
    $hasSystem = false;

    foreach ($messages as $msg) {
        if (!is_array($msg)) {
            continue;
        }
        $role = $msg['role'] ?? '';
        $content = trim($msg['content'] ?? '');
        if (!in_array($role, ['system', 'user', 'assistant'], true) || $content === '') {
            continue;
        }
        if ($role === 'system') {
            $hasSystem = true;
        }
        $normalized[] = ['role' => $role, 'content' => $content];
    }

    if (empty($normalized)) {
        return ['ok' => false, 'msg' => '请输入对话内容'];
    }

    if (!$hasSystem) {
        array_unshift($normalized, ['role' => 'system', 'content' => $system]);
    }

    return hope_ai_chat($normalized, $override);
}

function hope_ai_generate_image($prompt, $options = []) {
    $config = hope_ai_get_config($options);
    $model = trim($options['model'] ?? hope_ai_get_default_image_model());
    $size = trim($options['size'] ?? '1024x1024');
    $prompt = trim($prompt);

    if ($prompt === '') {
        return ['ok' => false, 'msg' => '请输入图像描述'];
    }

    $url = $config['image_api_base'] . '/images/generations';
    $payload = [
        'model'  => $model,
        'prompt' => $prompt,
        'n'      => 1,
        'size'   => $size,
    ];

    $result = hope_ai_request_json($url, $payload, $config, 180);
    if (!$result['ok']) {
        return $result;
    }

    $item = $result['data']['data'][0] ?? null;
    if (!$item) {
        return ['ok' => false, 'msg' => '未返回图像数据'];
    }

    $saved = hope_ai_save_image_result($item);
    if (!$saved['ok']) {
        if (!empty($item['url'])) {
            return ['ok' => true, 'url' => $item['url'], 'remote' => true];
        }
        return $saved;
    }

    $out = ['ok' => true, 'url' => $saved['url'], 'remote' => false];
    if (!empty($options['save_media'])) {
        $Upload_Model = new Upload_Model();
        $aid = (int)$Upload_Model->addMedia($saved['file_info'], 0, UID);
        $out['aid'] = $aid;
    }
    return $out;
}

function hope_ai_save_image_result($imageData) {
    $uploadFullPath = Option::UPLOAD_FULL_PATH . gmdate('Ym') . '/';
    if (!createDirectoryIfNeeded($uploadFullPath)) {
        return ['ok' => false, 'msg' => '无法创建上传目录'];
    }

    $ext = 'png';
    $fileName = 'ai_' . time() . '_' . getRandStr(6) . '.' . $ext;
    $uploadFullFile = $uploadFullPath . $fileName;
    $uploadFile = Option::UPLOAD_PATH . gmdate('Ym') . '/' . $fileName;

    if (!empty($imageData['b64_json'])) {
        $bin = base64_decode($imageData['b64_json']);
        if ($bin === false) {
            return ['ok' => false, 'msg' => '图片解码失败'];
        }
        file_put_contents($uploadFullFile, $bin);
    } elseif (!empty($imageData['url'])) {
        $ch = curl_init($imageData['url']);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);
        $bin = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);
        if ($errno || !$bin) {
            return ['ok' => false, 'msg' => '下载图片失败'];
        }
        file_put_contents($uploadFullFile, $bin);
    } else {
        return ['ok' => false, 'msg' => '无图片数据'];
    }

    $size = filesize($uploadFullFile);
    $wh = @getimagesize($uploadFullFile);
    $file_info = [
        'file_name' => $fileName,
        'mime_type' => 'image/png',
        'size'      => round($size / 1024, 2),
        'file_path' => $uploadFile,
        'file_url'  => rmUrlParams(getFileUrl($uploadFile)),
        'width'     => $wh ? $wh[0] : 0,
        'height'    => $wh ? $wh[1] : 0,
    ];

    return ['ok' => true, 'url' => $file_info['file_url'], 'file_info' => $file_info];
}

function hope_ai_build_messages($task, $context = []) {
    $config = hope_ai_get_config();
    $system = $config['system_prompt'];

    $title = trim($context['title'] ?? '');
    $content = trim($context['content'] ?? '');
    $prompt = trim($context['prompt'] ?? '');
    $excerpt = trim($context['excerpt'] ?? '');

    switch ($task) {
        case 'test':
            return [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => '请回复：连接成功'],
            ];

        case 'title':
            $user = "请根据以下内容生成一个简洁、有吸引力的中文博客标题，只输出标题本身，不要引号或多余说明。\n\n";
            if ($prompt !== '') {
                $user .= "主题/要求：{$prompt}\n\n";
            }
            if ($content !== '') {
                $user .= "正文参考：\n" . hope_ai_truncate($content, 6000);
            } elseif ($title !== '') {
                $user .= "当前标题：{$title}\n请优化或重写标题。";
            } else {
                return null;
            }
            break;

        case 'excerpt':
            $user = "请为以下文章生成一段 80~150 字的中文摘要，只输出摘要正文。\n\n";
            if ($title !== '') {
                $user .= "标题：{$title}\n\n";
            }
            $user .= "正文：\n" . hope_ai_truncate($content ?: $prompt, 8000);
            if ($content === '' && $prompt === '') {
                return null;
            }
            break;

        case 'content':
            $user = "请撰写一篇 Markdown 格式的中文博客正文。\n\n";
            if ($title !== '') {
                $user .= "标题：{$title}\n\n";
            }
            if ($prompt !== '') {
                $user .= "写作要求：{$prompt}\n\n";
            }
            if ($content !== '') {
                $user .= "已有内容（可续写或在此基础上扩展）：\n" . hope_ai_truncate($content, 6000) . "\n\n";
            }
            $user .= "直接输出 Markdown 正文，不要输出标题行。";
            if ($title === '' && $prompt === '' && $content === '') {
                return null;
            }
            break;

        case 'polish':
            $target = $content !== '' ? $content : $excerpt;
            if ($target === '') {
                return null;
            }
            $user = "请润色以下文本，保持原意，提升表达流畅度与可读性，使用 Markdown 格式，只输出润色后的正文。\n\n" . hope_ai_truncate($target, 8000);
            break;

        case 'tags':
            $user = "请根据以下文章生成 3~8 个中文标签，用英文逗号分隔，只输出标签列表。\n\n";
            if ($title !== '') {
                $user .= "标题：{$title}\n\n";
            }
            $user .= "正文：\n" . hope_ai_truncate($content ?: $prompt, 6000);
            if ($content === '' && $prompt === '' && $title === '') {
                return null;
            }
            break;

        case 'translate':
            $lang = trim($context['lang'] ?? 'en');
            $langLabel = hope_ai_lang_label($lang);
            $target = $content !== '' ? $content : ($excerpt !== '' ? $excerpt : $prompt);
            if ($target === '') {
                return null;
            }
            $user = "请将以下内容翻译为{$langLabel}（{$lang}）。\n";
            $user .= "要求：保持 Markdown 结构与标题层级；专有名词可保留原文；只输出译文，不要解释。\n\n";
            if ($title !== '') {
                $user .= "原标题：{$title}\n\n";
            }
            $user .= "原文：\n" . hope_ai_truncate($target, 10000);
            break;

        case 'cover_prompt':
            $user = "请根据以下文章信息，生成一段适合 AI 绘图的英文图像提示词（prompt），";
            $user .= "风格专业、适合博客封面，只输出英文 prompt，不要引号或其他说明。\n\n";
            if ($title !== '') {
                $user .= "标题：{$title}\n";
            }
            if ($excerpt !== '') {
                $user .= "摘要：{$excerpt}\n";
            }
            if ($prompt !== '') {
                $user .= "补充要求：{$prompt}\n";
            }
            if ($content !== '') {
                $user .= "正文参考：\n" . hope_ai_truncate($content, 3000);
            }
            if ($title === '' && $content === '' && $prompt === '' && $excerpt === '') {
                return null;
            }
            break;

        case 'article_bundle':
            $user = "请根据写作要求生成一篇完整博客文章，并严格输出如下 JSON（不要 Markdown 代码围栏，不要多余说明）：\n";
            $user .= '{"title":"标题","excerpt":"80-150字摘要","content":"Markdown正文","tags":"标签1,标签2","cover_prompt":"英文封面绘图提示词"}\n\n';
            if ($title !== '') {
                $user .= "指定标题（可微调）：{$title}\n";
            }
            if ($prompt !== '') {
                $user .= "写作要求：{$prompt}\n";
            }
            if ($content !== '') {
                $user .= "参考素材：\n" . hope_ai_truncate($content, 5000) . "\n";
            }
            $user .= "\n正文要求：结构清晰，含小标题，适合中文博客，800~2000字。";
            if ($title === '' && $prompt === '' && $content === '') {
                return null;
            }
            break;

        case 'site_plan':
            $user = "你是网站信息架构顾问。请根据站点主题，输出严格 JSON（不要代码围栏，不要多余说明）：\n";
            $user .= '{"sitename":"站名建议","siteinfo":"一句话副标题","site_description":"SEO描述","categories":[{"name":"分类名","alias":"英文别名","description":"简介"}],"pages":[{"title":"页面标题","alias":"英文别名","content":"Markdown正文"}],"settings":{"index_lognum":10}}\n\n';
            $user .= "规则：categories 3~8 个；pages 建议包含 关于我们、联系我们（可按主题调整）；alias 仅用小写字母数字和连字符。\n\n";
            if ($prompt !== '') {
                $user .= "站点主题/要求：{$prompt}\n";
            } elseif ($title !== '') {
                $user .= "站点主题/要求：{$title}\n";
            } else {
                return null;
            }
            break;

        case 'organize':
            $user = "请整理并优化以下站点内容清单。输出严格 JSON（不要代码围栏）：\n";
            $user .= '{"suggestions":[{"type":"article|category|setting","id":0,"action":"update_title|update_excerpt|move_category|update_option","payload":{},"reason":"原因"}]}\n\n';
            if ($prompt !== '') {
                $user .= "整理目标：{$prompt}\n\n";
            }
            $user .= "当前数据：\n" . hope_ai_truncate($content ?: $excerpt, 8000);
            if (($content === '' && $excerpt === '') || ($prompt === '' && $content === '')) {
                // allow content-only organize
            }
            if ($content === '' && $excerpt === '' && $prompt === '') {
                return null;
            }
            break;

        default:
            if ($prompt === '') {
                return null;
            }
            $user = $prompt;
            if ($title !== '') {
                $user = "标题：{$title}\n\n" . $user;
            }
            if ($content !== '') {
                $user .= "\n\n参考内容：\n" . hope_ai_truncate($content, 6000);
            }
            break;
    }

    return [
        ['role' => 'system', 'content' => $system],
        ['role' => 'user', 'content' => $user],
    ];
}

function hope_ai_generate($task, $context = [], $override = []) {
    $messages = hope_ai_build_messages($task, $context);
    if ($messages === null) {
        return ['ok' => false, 'msg' => '缺少必要的输入内容'];
    }
    if (!empty($context['model'])) {
        $override['model'] = $context['model'];
    }
    return hope_ai_chat($messages, $override);
}

function hope_ai_truncate($text, $maxLen) {
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($text, 'UTF-8') <= $maxLen) {
            return $text;
        }
        return mb_substr($text, 0, $maxLen, 'UTF-8') . '…';
    }
    if (strlen($text) <= $maxLen) {
        return $text;
    }
    return substr($text, 0, $maxLen) . '…';
}

function hope_ai_lang_label($code) {
    $map = [
        'zh-cn' => '简体中文',
        'zh-tw' => '繁体中文',
        'en'    => '英语',
        'ja'    => '日语',
        'ko'    => '韩语',
        'fr'    => '法语',
        'de'    => '德语',
        'es'    => '西班牙语',
        'ru'    => '俄语',
        'pt'    => '葡萄牙语',
        'vi'    => '越南语',
        'th'    => '泰语',
    ];
    $code = strtolower(trim((string)$code));
    return $map[$code] ?? $code;
}

function hope_ai_extract_json($text) {
    $text = trim((string)$text);
    if ($text === '') {
        return null;
    }
    $decoded = json_decode($text, true);
    if (is_array($decoded)) {
        return $decoded;
    }
    if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/i', $text, $m)) {
        $decoded = json_decode(trim($m[1]), true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }
    $start = strpos($text, '{');
    $end = strrpos($text, '}');
    if ($start !== false && $end !== false && $end > $start) {
        $decoded = json_decode(substr($text, $start, $end - $start + 1), true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }
    return null;
}

/**
 * 生成完整文章包：标题 / 摘要 / 正文 / 标签 / 封面 prompt
 */
function hope_ai_write_article_bundle($context = [], $override = []) {
    if (!empty($context['model'])) {
        $override = array_merge($override, hope_ai_resolve_model_override($context['model'], 'chat'));
    }
    $result = hope_ai_generate('article_bundle', $context, $override);
    if (!$result['ok']) {
        return $result;
    }
    $bundle = hope_ai_extract_json($result['content']);
    if (!is_array($bundle)) {
        return ['ok' => false, 'msg' => 'AI 返回的文章结构无法解析，请重试'];
    }
    $out = [
        'title'        => trim((string)($bundle['title'] ?? '')),
        'excerpt'      => trim((string)($bundle['excerpt'] ?? '')),
        'content'      => trim((string)($bundle['content'] ?? '')),
        'tags'         => trim(str_replace('，', ',', (string)($bundle['tags'] ?? ''))),
        'cover_prompt' => trim((string)($bundle['cover_prompt'] ?? '')),
    ];
    if ($out['title'] === '' || $out['content'] === '') {
        return ['ok' => false, 'msg' => 'AI 未生成有效标题或正文'];
    }
    return ['ok' => true, 'data' => $out, 'raw' => $result['content']];
}

/**
 * 翻译文本
 */
function hope_ai_translate_text($context = [], $override = []) {
    $lang = trim($context['lang'] ?? 'en');
    if ($lang === '') {
        $lang = 'en';
    }
    $context['lang'] = $lang;
    if (!empty($context['model'])) {
        $override = array_merge($override, hope_ai_resolve_model_override($context['model'], 'chat'));
    }
    $result = hope_ai_generate('translate', $context, $override);
    if (!$result['ok']) {
        return $result;
    }
    return [
        'ok'   => true,
        'data' => [
            'lang'    => $lang,
            'content' => $result['content'],
            'title'   => trim((string)($context['title'] ?? '')),
        ],
    ];
}

/**
 * 生成站点规划（分类 / 页面 / 设置建议）
 */
function hope_ai_generate_site_plan($brief, $override = []) {
    $brief = trim((string)$brief);
    if ($brief === '') {
        return ['ok' => false, 'msg' => '请输入站点主题或建站需求'];
    }
    $result = hope_ai_generate('site_plan', ['prompt' => $brief], $override);
    if (!$result['ok']) {
        return $result;
    }
    $plan = hope_ai_extract_json($result['content']);
    if (!is_array($plan)) {
        return ['ok' => false, 'msg' => 'AI 返回的建站方案无法解析，请重试'];
    }
    $categories = [];
    foreach ((array)($plan['categories'] ?? []) as $item) {
        if (!is_array($item)) {
            continue;
        }
        $name = trim((string)($item['name'] ?? ''));
        if ($name === '') {
            continue;
        }
        $categories[] = [
            'name'        => $name,
            'alias'       => hope_ai_slugify($item['alias'] ?? $name),
            'description' => trim((string)($item['description'] ?? '')),
        ];
    }
    $pages = [];
    foreach ((array)($plan['pages'] ?? []) as $item) {
        if (!is_array($item)) {
            continue;
        }
        $title = trim((string)($item['title'] ?? ''));
        if ($title === '') {
            continue;
        }
        $pages[] = [
            'title'   => $title,
            'alias'   => hope_ai_slugify($item['alias'] ?? $title),
            'content' => trim((string)($item['content'] ?? '')),
        ];
    }
    $settings = is_array($plan['settings'] ?? null) ? $plan['settings'] : [];
    return [
        'ok'   => true,
        'data' => [
            'sitename'         => trim((string)($plan['sitename'] ?? '')),
            'siteinfo'         => trim((string)($plan['siteinfo'] ?? '')),
            'site_description' => trim((string)($plan['site_description'] ?? '')),
            'categories'       => $categories,
            'pages'            => $pages,
            'settings'         => $settings,
        ],
        'raw'  => $result['content'],
    ];
}

function hope_ai_slugify($text) {
    $text = strtolower(trim((string)$text));
    $text = preg_replace('/[^a-z0-9\-\s_]/u', '', $text);
    $text = preg_replace('/[\s_]+/', '-', $text);
    $text = trim($text, '-');
    if ($text === '') {
        $text = 'item-' . substr(md5((string)microtime(true)), 0, 6);
    }
    return substr($text, 0, 64);
}

/**
 * 应用建站方案：创建分类、页面，可选更新站点设置
 */
function hope_ai_apply_site_plan($plan, $options = []) {
    global $CACHE;
    if (!is_array($plan)) {
        return ['ok' => false, 'msg' => '方案无效'];
    }

    $createCategories = !empty($options['create_categories']);
    $createPages = !empty($options['create_pages']);
    $updateSettings = !empty($options['update_settings']);
    $created = ['categories' => [], 'pages' => [], 'settings' => []];

    if ($createCategories && !empty($plan['categories']) && is_array($plan['categories'])) {
        $Sort_Model = new Sort_Model();
        $existing = $Sort_Model->getSorts();
        $aliasMap = [];
        foreach ($existing as $s) {
            if (!empty($s['alias'])) {
                $aliasMap[$s['alias']] = true;
            }
        }
        foreach ($plan['categories'] as $cat) {
            $name = trim((string)($cat['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $alias = hope_ai_slugify($cat['alias'] ?? $name);
            if (isset($aliasMap[$alias])) {
                $alias .= '-' . substr(md5($name . microtime()), 0, 4);
            }
            $DB = Database::getInstance();
            $sortData = [
                'sortname'    => $DB->escape_string($name),
                'pid'         => 0,
                'style'       => '',
                'keywords'    => '',
                'description' => $DB->escape_string(trim((string)($cat['description'] ?? ''))),
                'logstyle'    => '',
                'alias'       => $DB->escape_string($alias),
            ];
            $sid = (int)$Sort_Model->addSort($sortData);
            $aliasMap[$alias] = true;
            $created['categories'][] = ['id' => $sid, 'name' => $name, 'alias' => $alias];
        }
        if (isset($CACHE)) {
            $CACHE->updateCache(['sort', 'logsort', 'menu']);
        }
    }

    if ($createPages && !empty($plan['pages']) && is_array($plan['pages'])) {
        $Log_Model = new Log_Model();
        foreach ($plan['pages'] as $page) {
            $title = trim((string)($page['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $alias = hope_ai_slugify($page['alias'] ?? $title);
            $pageId = (int)$Log_Model->addlog([
                'title'        => $title,
                'content'      => (string)($page['content'] ?? ''),
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
            $created['pages'][] = ['id' => $pageId, 'title' => $title, 'alias' => $alias];
        }
        if (isset($CACHE)) {
            $CACHE->updateCache(['options', 'logalias']);
        }
    }

    if ($updateSettings) {
        $allowed = hope_ai_agent_allowed_option_keys();
        $pairs = [
            'sitename'         => $plan['sitename'] ?? '',
            'siteinfo'         => $plan['siteinfo'] ?? '',
            'site_description' => $plan['site_description'] ?? '',
        ];
        if (!empty($plan['settings']) && is_array($plan['settings'])) {
            foreach ($plan['settings'] as $k => $v) {
                $pairs[$k] = $v;
            }
        }
        foreach ($pairs as $key => $value) {
            $key = trim((string)$key);
            $value = trim((string)$value);
            if ($key === '' || $value === '') {
                continue;
            }
            if (!in_array($key, $allowed, true)) {
                continue;
            }
            Option::updateOption($key, $value);
            $created['settings'][$key] = $value;
        }
        if (isset($CACHE) && !empty($created['settings'])) {
            $CACHE->updateCache('options');
        }
    }

    hope_audit_log('ai_studio', 'apply_site_plan', [
        'categories' => count($created['categories']),
        'pages'      => count($created['pages']),
        'settings'   => array_keys($created['settings']),
    ], 'success', '一键建站方案已应用');

    return [
        'ok'   => true,
        'msg'  => '建站方案已应用',
        'data' => $created,
    ];
}

/**
 * 完整写作流水线：文章包 + 可选配图 + 可选存草稿
 */
function hope_ai_write_article_pipeline($context = [], $options = []) {
    global $CACHE;
    $bundleResult = hope_ai_write_article_bundle($context);
    if (!$bundleResult['ok']) {
        return $bundleResult;
    }
    $data = $bundleResult['data'];
    $coverUrl = '';
    $coverAid = 0;

    if (!empty($options['with_cover'])) {
        $prompt = $data['cover_prompt'];
        if ($prompt === '') {
            $prompt = 'Blog cover illustration about: ' . $data['title'] . ', clean modern style';
        }
        $imgOptions = [
            'save_media' => true,
            'size'       => $options['image_size'] ?? '1024x1024',
        ];
        if (!empty($options['image_model'])) {
            $imgOptions = array_merge($imgOptions, hope_ai_resolve_model_override($options['image_model'], 'image'));
        }
        $img = hope_ai_generate_image($prompt, $imgOptions);
        if (!empty($img['ok'])) {
            $coverUrl = $img['url'] ?? '';
            $coverAid = (int)($img['aid'] ?? 0);
        }
    }

    $articleId = 0;
    if (!empty($options['save_draft'])) {
        $Log_Model = new Log_Model();
        $sortid = (int)($options['sortid'] ?? -1);
        $logData = [
            'title'        => $data['title'],
            'content'      => $data['content'],
            'excerpt'      => $data['excerpt'],
            'author'       => UID,
            'sortid'       => $sortid > 0 ? $sortid : -1,
            'date'         => time(),
            'hide'         => 'y',
            'allow_remark' => 'y',
            'checked'      => 'y',
            'type'         => 'blog',
            'cover'        => $coverUrl,
        ];
        $articleId = (int)$Log_Model->addlog($logData);
        if ($articleId > 0 && $data['tags'] !== '') {
            $Tag_Model = new Tag_Model();
            $Tag_Model->addTag($data['tags'], $articleId);
        }
        if (isset($CACHE)) {
            $CACHE->updateArticleCache();
        }
        hope_audit_log('ai_studio', 'write_article', ['id' => $articleId, 'title' => $data['title']], 'success', 'AI 文章草稿已创建');
    }

    return [
        'ok'   => true,
        'data' => array_merge($data, [
            'cover'      => $coverUrl,
            'cover_aid'  => $coverAid,
            'article_id' => $articleId,
        ]),
        'msg'  => $articleId > 0 ? ('草稿已创建，ID ' . $articleId) : '文章已生成',
    ];
}

function hope_ai_studio_options() {
    return [
        'ready'         => hope_ai_is_ready(),
        'chat_models'   => hope_ai_get_chat_models(),
        'image_models'  => hope_ai_get_image_models(),
        'default_chat'  => hope_ai_get_default_chat_model(),
        'default_image' => hope_ai_get_default_image_model(),
        'image_sizes'   => ['256x256', '512x512', '1024x1024', '1792x1024', '1024x1792'],
    ];
}

function hope_ai_sanitize_models_payload($raw, $existing_models = []) {
    if (is_array($raw)) {
        $arr = $raw;
    } else {
        $str = stripslashes((string)$raw);
        $arr = json_decode($str, true);
        if (!is_array($arr)) {
            $arr = json_decode((string)$raw, true);
        }
    }
    if (!is_array($arr)) {
        return [];
    }
    $existingMap = [];
    foreach ($existing_models as $em) {
        if (!empty($em['id'])) {
            $existingMap[$em['id']] = $em;
        }
    }
    $models = [];
    $seen = [];
    foreach ($arr as $item) {
        if (!is_array($item)) {
            continue;
        }
        $id = trim($item['id'] ?? '');
        if ($id === '' || isset($seen[$id])) {
            continue;
        }
        $label = trim($item['label'] ?? $id);
        $entry = ['id' => $id, 'label' => $label ?: $id];
        $apiBase = hope_ai_normalize_api_base($item['api_base'] ?? '');
        if ($apiBase !== '') {
            $entry['api_base'] = $apiBase;
        }
        $apiKey = trim(stripslashes((string)($item['api_key'] ?? '')));
        if ($apiKey === '' && isset($existingMap[$id]['api_key'])) {
            $apiKey = $existingMap[$id]['api_key'];
        }
        if ($apiKey !== '') {
            $entry['api_key'] = $apiKey;
        }
        $models[] = $entry;
        $seen[$id] = true;
    }
    return $models;
}

function hope_ai_settings_view() {
    $chatModels = hope_ai_get_chat_models();
    $imageModels = hope_ai_get_image_models();
    return [
        'enabled'              => Option::get('ai_enabled') === 'y',
        'chat_models'          => hope_ai_models_for_view($chatModels),
        'image_models'         => hope_ai_models_for_view($imageModels),
        'default_chat_model'   => hope_ai_get_default_chat_model(),
        'default_image_model'  => hope_ai_get_default_image_model(),
        'temperature'          => Option::get('ai_temperature') !== '' ? Option::get('ai_temperature') : '0.7',
        'max_tokens'           => (int)(Option::get('ai_max_tokens') ?: 4096),
        'system_prompt'        => Option::get('ai_system_prompt') ?: '',
        'agent_readonly'       => Option::get('ai_agent_readonly') === 'y',
    ];
}

function hope_ai_save_settings($input, $options_cache = []) {
    $existingChat = hope_ai_decode_models($options_cache['ai_chat_models'] ?? '');
    $existingImage = hope_ai_decode_models($options_cache['ai_image_models'] ?? '');
    $chatModels = hope_ai_sanitize_models_payload($input['ai_chat_models'] ?? '[]', $existingChat);
    $imageModels = hope_ai_sanitize_models_payload($input['ai_image_models'] ?? '[]', $existingImage);

    if (empty($chatModels)) {
        $fallback = trim($input['ai_default_chat_model'] ?? '') ?: 'gpt-4o-mini';
        $chatModels = [['id' => $fallback, 'label' => $fallback]];
    }
    if (empty($imageModels)) {
        $imageModels = [
            ['id' => 'dall-e-3', 'label' => 'DALL-E 3'],
            ['id' => 'dall-e-2', 'label' => 'DALL-E 2'],
        ];
    }

    $chatIds = array_column($chatModels, 'id');
    $imageIds = array_column($imageModels, 'id');

    $defaultChat = trim($input['ai_default_chat_model'] ?? '');
    if ($defaultChat === '' || !in_array($defaultChat, $chatIds, true)) {
        $defaultChat = $chatModels[0]['id'];
    }
    $defaultImage = trim($input['ai_default_image_model'] ?? '');
    if ($defaultImage === '' || !in_array($defaultImage, $imageIds, true)) {
        $defaultImage = $imageModels[0]['id'];
    }

    $data = [
        'ai_enabled'             => ($input['ai_enabled'] ?? 'n') === 'y' ? 'y' : 'n',
        'ai_chat_models'         => json_encode($chatModels, JSON_UNESCAPED_UNICODE),
        'ai_image_models'        => json_encode($imageModels, JSON_UNESCAPED_UNICODE),
        'ai_default_chat_model'  => $defaultChat,
        'ai_default_image_model' => $defaultImage,
        'ai_temperature'         => max(0, min(2, (float)($input['ai_temperature'] ?? 0.7))),
        'ai_max_tokens'          => max(256, min(32000, (int)($input['ai_max_tokens'] ?? 4096))),
        'ai_system_prompt'       => trim($input['ai_system_prompt'] ?? ''),
        'ai_agent_readonly'      => ($input['ai_agent_readonly'] ?? 'n') === 'y' ? 'y' : 'n',
    ];

    foreach ($data as $key => $val) {
        Option::updateOption($key, $val);
    }
    // 清理历史单字段配置
    foreach (['ai_model', 'ai_api_base'] as $legacyKey) {
        if (Option::get($legacyKey) !== '') {
            Option::updateOption($legacyKey, '');
        }
    }

    return $data;
}

require_once HOPE_ROOT . 'system/app/service/ai_agent_service.php';
