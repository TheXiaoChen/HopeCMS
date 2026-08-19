<?php defined('HOPE_ROOT') || exit('access denied!'); ?>
<style>
.ai-studio-wrap { min-height: calc(100vh - 180px); }
.ai-chat-box {
    height: 520px;
    overflow-y: auto;
    background: #f8f9fa;
    border-radius: .75rem;
    padding: 1rem;
}
.ai-msg { margin-bottom: 1rem; display: flex; gap: .75rem; }
.ai-msg.user { flex-direction: row-reverse; }
.ai-msg .bubble {
    max-width: 78%;
    padding: .65rem .9rem;
    border-radius: .75rem;
    white-space: pre-wrap;
    word-break: break-word;
    font-size: .925rem;
    line-height: 1.55;
}
.ai-msg.user .bubble { background: #5e72e4; color: #fff; }
.ai-msg.assistant .bubble { background: #fff; border: 1px solid #e9ecef; color: #344767; }
.ai-msg .role-tag { font-size: .7rem; color: #8392ab; min-width: 3rem; text-align: center; padding-top: .35rem; }
.ai-image-result img { max-width: 100%; border-radius: .75rem; box-shadow: 0 4px 20px rgba(0,0,0,.08); }
.ai-empty-hint { color: #8392ab; text-align: center; padding: 3rem 1rem; }
.ai-model-table td { vertical-align: middle; }
.ai-model-table .form-control-sm { min-width: 120px; }
.ai-model-table .api-base-cell { max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: .8rem; color: #67748e; }
.ai-config-section { border: 1px solid #e9ecef; border-radius: .75rem; padding: 1rem; margin-bottom: 1.25rem; }
.ai-config-section h6 { margin-bottom: .75rem; }
</style>

<div class="row ai-studio-wrap">
    <div class="col-12">
        <div class="card">
            <div class="card-header pb-0">
                <div class="d-lg-flex align-items-center">
                    <h5 class="mb-0">AI 工作台</h5>
                    <p class="text-sm text-secondary mb-0 ms-lg-3 mt-2 mt-lg-0">AI 写作 · 摘要配图 · 多语言翻译 · 一键建站 · 对话运维</p>
                </div>
                <ul class="nav nav-pills nav-fill p-1 mt-3 flex-wrap" role="tablist" id="ai-studio-tabs">
                    <li class="nav-item">
                        <a class="nav-link mb-0 <?= !empty($studio['ready']) ? 'active' : '' ?>" data-bs-toggle="tab" href="#ai-tab-write" role="tab">AI 写作</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link mb-0" data-bs-toggle="tab" href="#ai-tab-translate" role="tab">AI 翻译</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link mb-0" data-bs-toggle="tab" href="#ai-tab-setup" role="tab">一键建站</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link mb-0" data-bs-toggle="tab" href="#ai-tab-chat" role="tab">对话聊天</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link mb-0" data-bs-toggle="tab" href="#ai-tab-image" role="tab">生成图像</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link mb-0 <?= empty($studio['ready']) ? 'active' : '' ?>" data-bs-toggle="tab" href="#ai-tab-config" role="tab">AI 配置</a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <div class="tab-pane <?= !empty($studio['ready']) ? 'active' : '' ?>" id="ai-tab-write">
                        <?php if (empty($studio['ready'])): ?>
                        <div class="alert alert-warning text-sm mb-0">
                            请先在 <a href="#ai-tab-config" data-bs-toggle="tab">AI 配置</a> 中启用 AI 并添加文本对话模型。
                        </div>
                        <?php else: ?>
                        <div class="row g-3">
                            <div class="col-lg-5">
                                <label class="form-label text-sm">文本模型</label>
                                <select class="form-control mb-3" id="ai-write-model" data-hope-choices data-preset="search">
                                    <?php foreach ($studio['chat_models'] as $m): ?>
                                    <option value="<?= htmlspecialchars($m['id']) ?>" <?= $studio['default_chat'] === $m['id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['label']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <label class="form-label text-sm">写作主题 / 要求</label>
                                <textarea class="form-control mb-3" id="ai-write-prompt" rows="5" placeholder="例如：写一篇面向新手的网盘资源站运营指南，包含选品、SEO、会员转化三点，约1200字"></textarea>
                                <label class="form-label text-sm">指定标题（可选）</label>
                                <input type="text" class="form-control mb-3" id="ai-write-title" placeholder="留空则由 AI 生成">
                                <label class="form-label text-sm">发布到分类（存草稿时）</label>
                                <select class="form-control mb-3" id="ai-write-sortid" data-hope-choices data-preset="search">
                                    <option value="-1">未分类</option>
                                    <?php foreach ($studio_sorts as $sid => $sort): ?>
                                    <option value="<?= (int)$sid ?>"><?= $sort['sortname'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="ai-write-with-cover" checked>
                                    <label class="form-check-label text-sm" for="ai-write-with-cover">同时 AI 配图（封面）</label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="ai-write-save-draft" checked>
                                    <label class="form-check-label text-sm" for="ai-write-save-draft">生成后保存为草稿</label>
                                </div>
                                <button type="button" class="btn btn-primary w-100 mb-0" id="ai-write-generate">一键生成文章</button>
                                <p class="text-xs text-muted mt-2 mb-0">将自动生成：标题、摘要、Markdown 正文、标签，可选封面图并写入草稿。</p>
                            </div>
                            <div class="col-lg-7">
                                <div class="border rounded p-3 bg-gray-100" id="ai-write-result" style="min-height:420px;">
                                    <div class="ai-empty-hint mb-0">生成结果将显示在这里</div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="tab-pane" id="ai-tab-translate">
                        <?php if (empty($studio['ready'])): ?>
                        <div class="alert alert-warning text-sm mb-0">请先完成 AI 配置。</div>
                        <?php else: ?>
                        <div class="row g-3">
                            <div class="col-lg-6">
                                <label class="form-label text-sm">文本模型</label>
                                <select class="form-control mb-3" id="ai-trans-model" data-hope-choices data-preset="search">
                                    <?php foreach ($studio['chat_models'] as $m): ?>
                                    <option value="<?= htmlspecialchars($m['id']) ?>" <?= $studio['default_chat'] === $m['id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['label']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <label class="form-label text-sm">目标语言</label>
                                <select class="form-control mb-3" id="ai-trans-lang" data-hope-choices>
                                    <option value="en">英语</option>
                                    <option value="ja">日语</option>
                                    <option value="ko">韩语</option>
                                    <option value="zh-cn">简体中文</option>
                                    <option value="zh-tw">繁体中文</option>
                                    <option value="fr">法语</option>
                                    <option value="de">德语</option>
                                    <option value="es">西班牙语</option>
                                    <option value="ru">俄语</option>
                                    <option value="vi">越南语</option>
                                </select>
                                <label class="form-label text-sm">待翻译内容</label>
                                <textarea class="form-control mb-3" id="ai-trans-content" rows="12" placeholder="粘贴文章标题+正文，或任意 Markdown 文本"></textarea>
                                <button type="button" class="btn btn-primary w-100 mb-0" id="ai-trans-run">开始翻译</button>
                            </div>
                            <div class="col-lg-6">
                                <label class="form-label text-sm">译文</label>
                                <textarea class="form-control" id="ai-trans-result" rows="18" placeholder="翻译结果"></textarea>
                                <button type="button" class="btn btn-outline-secondary btn-sm mt-2 mb-0" id="ai-trans-copy">复制译文</button>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="tab-pane" id="ai-tab-setup">
                        <?php if (empty($studio['ready'])): ?>
                        <div class="alert alert-warning text-sm mb-0">请先完成 AI 配置。</div>
                        <?php else: ?>
                        <div class="row g-3">
                            <div class="col-lg-5">
                                <label class="form-label text-sm">文本模型</label>
                                <select class="form-control mb-3" id="ai-setup-model" data-hope-choices data-preset="search">
                                    <?php foreach ($studio['chat_models'] as $m): ?>
                                    <option value="<?= htmlspecialchars($m['id']) ?>" <?= $studio['default_chat'] === $m['id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['label']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <label class="form-label text-sm">站点主题 / 建站需求</label>
                                <textarea class="form-control mb-3" id="ai-setup-brief" rows="6" placeholder="例如：做一个软件资源下载站，面向个人站长，需要教程、软件、素材分类，以及关于我们、投稿指南页面"></textarea>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="ai-setup-cat" checked>
                                    <label class="form-check-label text-sm" for="ai-setup-cat">创建分类</label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="ai-setup-page" checked>
                                    <label class="form-check-label text-sm" for="ai-setup-page">创建页面</label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="ai-setup-settings" checked>
                                    <label class="form-check-label text-sm" for="ai-setup-settings">更新站名/SEO 描述等设置</label>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-primary mb-0 flex-fill" id="ai-setup-plan">生成方案</button>
                                    <button type="button" class="btn btn-primary mb-0 flex-fill" id="ai-setup-apply" disabled>确认应用</button>
                                </div>
                                <p class="text-xs text-muted mt-2 mb-0">先预览方案，确认后再写入分类、页面与基础设置。敏感配置不会被修改。</p>
                            </div>
                            <div class="col-lg-7">
                                <pre class="border rounded p-3 bg-gray-100 text-sm mb-0" id="ai-setup-preview" style="min-height:420px;white-space:pre-wrap;word-break:break-word;">方案预览将显示在这里</pre>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="tab-pane" id="ai-tab-chat">
                        <?php if (empty($studio['ready'])): ?>
                        <div class="alert alert-warning text-sm mb-0">
                            请先在 <a href="#ai-tab-config" data-bs-toggle="tab">AI 配置</a> 中启用 AI 并添加文本对话模型（含 API Key）。
                        </div>
                        <?php else: ?>
                        <div class="row g-3">
                            <div class="col-lg-9">
                                <div class="ai-chat-box" id="ai-chat-messages">
                                    <div class="ai-empty-hint">开始与 AI 对话吧。可让助手创建分类、页面、草稿，或整理内容与改设置（写操作需确认）。</div>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <label class="form-label text-sm">文本对话模型</label>
                                <select class="form-control mb-3" id="ai-chat-model" data-hope-choices data-preset="search">
                                    <?php foreach ($studio['chat_models'] as $m): ?>
                                    <option value="<?= htmlspecialchars($m['id']) ?>" <?= $studio['default_chat'] === $m['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($m['label']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn btn-outline-secondary btn-sm w-100 mb-3" id="ai-chat-clear">清空对话</button>
                                <p class="text-xs text-muted mb-0">对话记录仅保存在当前页面，刷新后清空。</p>
                            </div>
                            <div class="col-12">
                                <div class="input-group">
                                    <textarea class="form-control" id="ai-chat-input" rows="2" placeholder="输入消息，Enter 发送，Shift+Enter 换行"></textarea>
                                    <button class="btn btn-primary mb-0" type="button" id="ai-chat-send">发送</button>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="tab-pane" id="ai-tab-image">
                        <?php if (empty($studio['ready'])): ?>
                        <div class="alert alert-warning text-sm mb-0">
                            请先在 <a href="#ai-tab-config" data-bs-toggle="tab">AI 配置</a> 中完成配置并添加图像生成模型。
                        </div>
                        <?php else: ?>
                        <div class="row g-3">
                            <div class="col-lg-8">
                                <div class="ai-image-result text-center p-4 bg-gray-100 border-radius-lg" id="ai-image-preview" style="min-height:360px;">
                                    <div class="ai-empty-hint mb-0">生成的图像将显示在这里</div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <label class="form-label text-sm">图像生成模型</label>
                                <select class="form-control mb-3" id="ai-image-model" data-hope-choices data-preset="search">
                                    <?php foreach ($studio['image_models'] as $m): ?>
                                    <option value="<?= htmlspecialchars($m['id']) ?>" <?= $studio['default_image'] === $m['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($m['label']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <label class="form-label text-sm">图像尺寸</label>
                                <select class="form-control mb-3" id="ai-image-size" data-hope-choices>
                                    <?php foreach ($studio['image_sizes'] as $sz): ?>
                                    <option value="<?= htmlspecialchars($sz) ?>" <?= $sz === '1024x1024' ? 'selected' : '' ?>><?= htmlspecialchars($sz) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="ai-image-save-media" checked>
                                    <label class="form-check-label text-sm" for="ai-image-save-media">保存到媒体库</label>
                                </div>
                                <label class="form-label text-sm">图像描述（Prompt）</label>
                                <textarea class="form-control mb-3" id="ai-image-prompt" rows="6" placeholder="描述你想生成的图像，例如：一只在窗台晒太阳的橘猫，水彩风格"></textarea>
                                <button type="button" class="btn btn-primary w-100 mb-0" id="ai-image-generate">生成图像</button>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="tab-pane <?= empty($studio['ready']) ? 'active' : '' ?>" id="ai-tab-config">
                        <form id="ai-config-form">
                            <div class="alert alert-light border text-sm mb-4" role="alert">
                                <div class="mb-2"><strong>兼容服务：</strong>OpenAI、DeepSeek、通义千问、Moonshot 等 OpenAI 兼容接口</div>
                                <div class="mb-0">在下方「添加模型」中为每个模型配置 API URL、API Key、Model</div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <div class="form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" value="y" name="ai_enabled" id="ai-enabled-switch" <?= !empty($config['enabled']) ? 'checked' : '' ?>>
                                        </div>
                                        <span class="text-dark font-weight-bold text-sm ms-2">启用 AI 功能</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <div class="form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" value="y" name="ai_agent_readonly" id="ai-agent-readonly-switch" <?= !empty($config['agent_readonly']) ? 'checked' : '' ?>>
                                        </div>
                                        <span class="text-dark font-weight-bold text-sm ms-2">AI 助手只读模式</span>
                                    </div>
                                    <p class="text-xs text-muted mb-0 mt-1">开启后头部 AI 仅可查询，不可改库（写操作记录到 content/logs/audit.log）</p>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label class="form-label text-sm">Temperature</label>
                                    <input type="number" class="form-control" name="ai_temperature" id="ai-temperature" step="0.1" min="0" max="2" value="<?= htmlspecialchars($config['temperature']) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-sm">Max Tokens</label>
                                    <input type="number" class="form-control" name="ai_max_tokens" id="ai-max-tokens" min="256" max="32000" value="<?= (int)$config['max_tokens'] ?>">
                                </div>
                            </div>

                            <div class="ai-config-section">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0"><i class="fa fa-comments text-primary me-1"></i> 文本对话模型</h6>
                                    <button type="button" class="btn btn-sm btn-outline-primary mb-0" id="ai-add-chat-model">+ 添加模型</button>
                                </div>
                                <p class="text-xs text-muted mb-2">用于 AI 写作、摘要、翻译、一键建站、对话助手；点击「添加模型」可配置接口预设、API URL、API Key、Model</p>
                                <div class="table-responsive">
                                    <table class="table table-sm ai-model-table mb-0" id="ai-chat-models-table">
                                        <thead>
                                            <tr>
                                                <th style="width:22%">显示名称</th>
                                                <th style="width:22%">Model</th>
                                                <th style="width:28%">API URL</th>
                                                <th style="width:10%">Key</th>
                                                <th style="width:8%">默认</th>
                                                <th style="width:10%"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="ai-chat-models-body"></tbody>
                                    </table>
                                </div>
                                <input type="hidden" name="ai_default_chat_model" id="ai-default-chat-model" value="<?= htmlspecialchars($config['default_chat_model']) ?>">
                            </div>

                            <div class="ai-config-section">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0"><i class="fa fa-image text-info me-1"></i> 图像生成模型</h6>
                                    <button type="button" class="btn btn-sm btn-outline-primary mb-0" id="ai-add-image-model">+ 添加模型</button>
                                </div>
                                <p class="text-xs text-muted mb-2">用于 AI 工作台图像生成；点击「添加模型」可配置接口预设、API URL、API Key、Model</p>
                                <div class="table-responsive">
                                    <table class="table table-sm ai-model-table mb-0" id="ai-image-models-table">
                                        <thead>
                                            <tr>
                                                <th style="width:22%">显示名称</th>
                                                <th style="width:22%">Model</th>
                                                <th style="width:28%">API URL</th>
                                                <th style="width:10%">Key</th>
                                                <th style="width:8%">默认</th>
                                                <th style="width:10%"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="ai-image-models-body"></tbody>
                                    </table>
                                </div>
                                <input type="hidden" name="ai_default_image_model" id="ai-default-image-model" value="<?= htmlspecialchars($config['default_image_model']) ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-sm">系统提示词（System Prompt）</label>
                                <textarea class="form-control" name="ai_system_prompt" id="ai-system-prompt" rows="3" placeholder="定义 AI 的角色与输出风格"><?= htmlspecialchars($config['system_prompt']) ?></textarea>
                            </div>

                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <button type="button" class="btn btn-primary mb-0" id="ai-config-save">保存配置</button>
                                <button type="button" class="btn btn-outline-secondary mb-0" id="ai-test-btn">测试连接</button>
                                <span id="ai-test-result" class="text-sm"></span>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="ai-model-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ai-model-modal-title">添加模型</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="ai-model-modal-type" value="chat">
                <input type="hidden" id="ai-model-modal-index" value="-1">
                <div class="mb-3">
                    <label class="form-label text-sm">接口预设</label>
                    <select class="form-control" id="ai-model-preset" data-hope-choices data-preset="search">
                        <option value="">自定义</option>
                        <option value="https://api.openai.com/v1">OpenAI</option>
                        <option value="https://api.deepseek.com/v1">DeepSeek</option>
                        <option value="https://dashscope.aliyuncs.com/compatible-mode/v1">通义千问</option>
                        <option value="https://api.moonshot.cn/v1">Moonshot</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label text-sm">API URL</label>
                    <input type="text" class="form-control" id="ai-model-api-base" placeholder="https://api.deepseek.com/v1">
                    <small class="text-muted">填写 Base URL，系统会自动拼接 <code>/chat/completions</code> 或 <code>/images/generations</code></small>
                </div>
                <div class="mb-3">
                    <label class="form-label text-sm">API Key</label>
                    <input type="password" class="form-control" id="ai-model-api-key" placeholder="sk-..." autocomplete="new-password">
                    <small class="text-muted" id="ai-model-api-key-hint">格式如 sk-****，每个模型单独配置</small>
                </div>
                <div class="mb-3">
                    <label class="form-label text-sm">预设 Model（可选）</label>
                    <select class="form-control" id="ai-model-preset-model" data-hope-choices data-preset="search">
                        <option value="">手动填写 Model</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label text-sm">Model（模型 ID）</label>
                    <input type="text" class="form-control" id="ai-model-id" placeholder="deepseek-v4-flash">
                </div>
                <div class="mb-0">
                    <label class="form-label text-sm">显示名称</label>
                    <input type="text" class="form-control" id="ai-model-label" placeholder="DeepSeek V4 Flash">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary mb-0" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary mb-0" id="ai-model-modal-save">确定</button>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="token" value="<?= LoginAuth::genToken() ?>">
<script>
window.HOPE_AI_URL = '<?= SELF ?>?act=ai';
window.HOPE_AI_SAVE_URL = '<?= SELF ?>?act=ai_studio&save';
window.HOPE_AI_READY = <?= !empty($studio['ready']) ? 'true' : 'false' ?>;
window.HOPE_AI_CONFIG = <?= json_encode([
    'chat_models' => $config['chat_models'],
    'image_models' => $config['image_models'],
    'default_chat_model' => $config['default_chat_model'],
    'default_image_model' => $config['default_image_model'],
], JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="<?= SITE_URL ?>content/admin/js/ai_workspace.js?v=<?= Option::HOPE_VERSION_TIMESTAMP ?>"></script>
<script>
$("#menu_system_manage").addClass('active');
$("#menu_system").addClass('show');
$("#menu_ai_studio").addClass('active');
</script>
