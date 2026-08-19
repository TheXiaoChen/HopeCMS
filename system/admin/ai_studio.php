<?php
/**
 * Hope CMS 后台 AI 工作台
 */
defined('HOPE_ROOT') || exit('access denied!');

require_once HOPE_ROOT . 'system/app/service/ai_service.php';

if (isset($_GET['save'])) {
    LoginAuth::checkToken();
    $options_cache = $CACHE->readCache('options');
    $chatRaw = isset($_POST['ai_chat_models']) ? trim((string)$_POST['ai_chat_models']) : '[]';
    $imageRaw = isset($_POST['ai_image_models']) ? trim((string)$_POST['ai_image_models']) : '[]';
    hope_ai_save_settings([
        'ai_enabled'             => Input::postStrVar('ai_enabled', 'n'),
        'ai_chat_models'         => $chatRaw,
        'ai_image_models'        => $imageRaw,
        'ai_default_chat_model'  => Input::postStrVar('ai_default_chat_model'),
        'ai_default_image_model' => Input::postStrVar('ai_default_image_model'),
        'ai_temperature'         => Input::postStrVar('ai_temperature', '0.7'),
        'ai_max_tokens'          => Input::postStrVar('ai_max_tokens', '4096'),
        'ai_system_prompt'       => isset($_POST['ai_system_prompt']) ? trim((string)$_POST['ai_system_prompt']) : '',
        'ai_agent_readonly'      => Input::postStrVar('ai_agent_readonly', 'n'),
    ], $options_cache);
    $CACHE->updateCache('options');
    Output::ok();
}

$config = hope_ai_settings_view();
$studio = hope_ai_studio_options();
$Sort_Model = new Sort_Model();
$studio_sorts = $Sort_Model->getSorts();
$pagetitle = 'AI 工作台';
