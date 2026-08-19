<?php
/**
 * Hope CMS 后台 AI 接口
 */
defined('HOPE_ROOT') || exit('access denied!');

require_once HOPE_ROOT . 'system/app/service/ai_service.php';

LoginAuth::checkToken();

$action = Input::getStrVar('action', Input::postStrVar('action'));

if ($action === 'test') {
    $override = hope_ai_test_override_from_post();
    $cfg = hope_ai_get_config($override);
    if ($cfg['api_key'] === '') {
        Output::error('请先为该模型配置 API Key');
    }
    if (empty($cfg['api_base'])) {
        Output::error('请先为该模型配置 API URL');
    }
    $result = hope_ai_generate('test', [], $override);
    if (!$result['ok']) {
        Output::error($result['msg']);
    }
    Output::ok(['content' => $result['content']]);
}

if ($action === 'models') {
    Output::ok(hope_ai_studio_options());
}

if (!hope_ai_is_ready()) {
    Output::error('请先在 AI 配置中添加模型并填写 API Key');
}

if ($action === 'chat') {
    $messagesRaw = (string)($_POST['messages'] ?? '');
    $messages = json_decode($messagesRaw, true);
    if (!is_array($messages)) {
        Output::error('对话数据格式错误');
    }

    $model = isset($_POST['model']) ? trim((string)$_POST['model']) : '';
    $override = hope_ai_resolve_model_override($model, 'chat');
    if ($model === '') {
        $override = [];
    }

    $result = hope_ai_conversation($messages, $override);
    if (!$result['ok']) {
        Output::error($result['msg']);
    }
    Output::ok([
        'content' => $result['content'],
        'model'   => $model ?: hope_ai_get_default_chat_model(),
    ]);
}

if ($action === 'agent_chat') {
    $messagesRaw = (string)($_POST['messages'] ?? '');
    $messages = json_decode($messagesRaw, true);
    if (!is_array($messages)) {
        Output::error('对话数据格式错误');
    }

    $model = isset($_POST['model']) ? trim((string)$_POST['model']) : '';
    $override = hope_ai_resolve_model_override($model, 'chat');
    if ($model === '') {
        $override = [];
    }

    $result = hope_ai_agent_chat($messages, $override);
    if (!$result['ok']) {
        Output::error($result['msg']);
    }
    Output::ok([
        'content'         => $result['content'],
        'pending_actions' => $result['pending_actions'] ?? [],
        'model'           => $model ?: hope_ai_get_default_chat_model(),
    ]);
}

if ($action === 'agent_execute') {
    $actionRaw = (string)($_POST['agent_action'] ?? '');
    $confirmed = (string)($_POST['confirmed'] ?? '');
    if ($confirmed !== 'y') {
        Output::error('操作未确认');
    }
    $result = hope_ai_agent_execute_confirmed($actionRaw);
    if (!$result['ok']) {
        Output::error($result['msg']);
    }
    Output::ok([
        'msg'  => $result['msg'] ?? '操作成功',
        'data' => $result['data'] ?? [],
    ]);
}

if ($action === 'image') {
    $prompt = Input::postStrVar('prompt');
    $model = trim(Input::postStrVar('model'));
    $size = trim(Input::postStrVar('size', '1024x1024'));
    $saveMedia = Input::postStrVar('save_media', 'y') === 'y';

    $options = [
        'save_media' => $saveMedia,
        'size'       => $size,
    ];
    if ($model !== '') {
        $options = array_merge($options, hope_ai_resolve_model_override($model, 'image'));
    }

    $result = hope_ai_generate_image($prompt, $options);
    if (!$result['ok']) {
        Output::error($result['msg']);
    }
    Output::ok([
        'url'    => $result['url'],
        'remote' => !empty($result['remote']),
        'aid'    => $result['aid'] ?? 0,
    ]);
}

if ($action === 'generate') {
    $task = Input::postStrVar('task');
    $allowed = ['title', 'excerpt', 'content', 'polish', 'tags', 'translate', 'cover_prompt', 'article_bundle'];
    if (!in_array($task, $allowed, true)) {
        Output::error('不支持的任务类型');
    }

    $context = [
        'title'   => Input::postStrVar('title'),
        'content' => Input::postStrVar('content'),
        'excerpt' => Input::postStrVar('excerpt'),
        'prompt'  => Input::postStrVar('prompt'),
        'model'   => Input::postStrVar('model'),
        'lang'    => Input::postStrVar('lang', 'en'),
    ];

    $override = hope_ai_resolve_model_override(trim($context['model']), 'chat');
    $result = hope_ai_generate($task, $context, $override);
    if (!$result['ok']) {
        Output::error($result['msg']);
    }
    $payload = ['content' => $result['content'], 'task' => $task];
    if ($task === 'article_bundle') {
        $bundle = hope_ai_extract_json($result['content']);
        if (is_array($bundle)) {
            $payload['bundle'] = [
                'title'        => trim((string)($bundle['title'] ?? '')),
                'excerpt'      => trim((string)($bundle['excerpt'] ?? '')),
                'content'      => trim((string)($bundle['content'] ?? '')),
                'tags'         => trim(str_replace('，', ',', (string)($bundle['tags'] ?? ''))),
                'cover_prompt' => trim((string)($bundle['cover_prompt'] ?? '')),
            ];
        }
    }
    Output::ok($payload);
}

if ($action === 'write_article') {
    $context = [
        'title'   => Input::postStrVar('title'),
        'content' => Input::postStrVar('content'),
        'prompt'  => Input::postStrVar('prompt'),
        'model'   => Input::postStrVar('model'),
    ];
    $options = [
        'with_cover'  => Input::postStrVar('with_cover', 'n') === 'y',
        'save_draft'  => Input::postStrVar('save_draft', 'n') === 'y',
        'sortid'      => Input::postIntVar('sortid', -1),
        'image_model' => Input::postStrVar('image_model'),
        'image_size'  => Input::postStrVar('image_size', '1024x1024'),
    ];
    $result = hope_ai_write_article_pipeline($context, $options);
    if (!$result['ok']) {
        Output::error($result['msg']);
    }
    Output::ok($result['data'] + ['msg' => $result['msg'] ?? '']);
}

if ($action === 'translate') {
    $context = [
        'title'   => Input::postStrVar('title'),
        'content' => Input::postStrVar('content'),
        'excerpt' => Input::postStrVar('excerpt'),
        'prompt'  => Input::postStrVar('prompt'),
        'model'   => Input::postStrVar('model'),
        'lang'    => Input::postStrVar('lang', 'en'),
    ];
    $override = hope_ai_resolve_model_override(trim($context['model']), 'chat');
    $result = hope_ai_translate_text($context, $override);
    if (!$result['ok']) {
        Output::error($result['msg']);
    }
    Output::ok($result['data']);
}

if ($action === 'site_plan') {
    $brief = Input::postStrVar('prompt');
    $model = Input::postStrVar('model');
    $override = hope_ai_resolve_model_override(trim($model), 'chat');
    $result = hope_ai_generate_site_plan($brief, $override);
    if (!$result['ok']) {
        Output::error($result['msg']);
    }
    Output::ok($result['data']);
}

if ($action === 'apply_site_plan') {
    $planRaw = (string)($_POST['plan'] ?? '');
    $plan = json_decode($planRaw, true);
    if (!is_array($plan)) {
        Output::error('建站方案数据无效');
    }
    $confirmed = Input::postStrVar('confirmed', 'n');
    if ($confirmed !== 'y') {
        Output::error('请先确认后再应用建站方案');
    }
    $result = hope_ai_apply_site_plan($plan, [
        'create_categories' => Input::postStrVar('create_categories', 'y') === 'y',
        'create_pages'      => Input::postStrVar('create_pages', 'y') === 'y',
        'update_settings'   => Input::postStrVar('update_settings', 'y') === 'y',
    ]);
    if (!$result['ok']) {
        Output::error($result['msg']);
    }
    Output::ok($result['data'] + ['msg' => $result['msg'] ?? '']);
}

if ($action === 'article_cover') {
    $title = Input::postStrVar('title');
    $content = Input::postStrVar('content');
    $excerpt = Input::postStrVar('excerpt');
    $prompt = Input::postStrVar('prompt');
    $model = Input::postStrVar('model');
    $imageModel = Input::postStrVar('image_model');
    $size = Input::postStrVar('size', '1024x1024');

    $override = hope_ai_resolve_model_override(trim($model), 'chat');
    $promptResult = hope_ai_generate('cover_prompt', [
        'title'   => $title,
        'content' => $content,
        'excerpt' => $excerpt,
        'prompt'  => $prompt,
    ], $override);
    if (!$promptResult['ok']) {
        Output::error($promptResult['msg']);
    }
    $coverPrompt = $promptResult['content'];
    $imgOptions = ['save_media' => true, 'size' => $size];
    if ($imageModel !== '') {
        $imgOptions = array_merge($imgOptions, hope_ai_resolve_model_override($imageModel, 'image'));
    }
    $img = hope_ai_generate_image($coverPrompt, $imgOptions);
    if (!$img['ok']) {
        Output::error($img['msg']);
    }
    Output::ok([
        'url'          => $img['url'],
        'aid'          => $img['aid'] ?? 0,
        'cover_prompt' => $coverPrompt,
    ]);
}

Output::error('未知操作');

function hope_ai_test_override_from_post() {
    $model = isset($_POST['ai_model']) ? trim((string)$_POST['ai_model']) : '';
    if ($model === '') {
        $model = hope_ai_get_default_chat_model();
    }
    $override = hope_ai_resolve_model_override($model, 'chat');

    $base = isset($_POST['ai_api_base']) ? trim((string)$_POST['ai_api_base']) : '';
    if ($base !== '') {
        $override['api_base'] = hope_ai_normalize_api_base($base);
    } elseif (empty($override['api_base'])) {
        $override['api_base'] = hope_ai_resolve_api_base($model, 'chat');
    }

    $key = isset($_POST['ai_api_key']) ? trim((string)$_POST['ai_api_key']) : '';
    if ($key !== '') {
        $override['api_key'] = $key;
    } elseif (empty($override['api_key'])) {
        $override['api_key'] = hope_ai_resolve_api_key($model, 'chat');
    }

    $override['model'] = $model;
    return $override;
}
