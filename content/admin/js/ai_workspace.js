(function () {
    'use strict';

    var chatMessages = [];
    var chatBox = document.getElementById('ai-chat-messages');
    var chatInput = document.getElementById('ai-chat-input');
    var chatModel = document.getElementById('ai-chat-model');
    var tokenEl = document.getElementById('token');

    function getToken() {
        return tokenEl ? tokenEl.value : '';
    }

    function escapeHtml(s) {
        return String(s || '').replace(/[&<>"']/g, function (c) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
        });
    }

    function showLoading(title) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ title: title || '处理中…', allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } });
        }
    }

    function hideLoading() {
        if (typeof Swal !== 'undefined') Swal.close();
    }

    function toast(icon, title) {
        if (typeof Toast !== 'undefined') {
            Toast.fire({ icon: icon, title: title });
        } else if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: icon, title: title, timer: 2500, showConfirmButton: false });
        } else {
            alert(title);
        }
    }

    function postAi(action, data) {
        var fd = new FormData();
        fd.append('token', getToken());
        fd.append('action', action);
        Object.keys(data || {}).forEach(function (k) {
            fd.append(k, data[k]);
        });
        return fetch(window.HOPE_AI_URL || '?act=ai', { method: 'POST', body: fd }).then(function (r) {
            return r.json();
        });
    }

    /* ---------- 对话聊天 ---------- */
    function renderChat() {
        if (!chatBox) return;
        if (!chatMessages.length) {
            chatBox.innerHTML = '<div class="ai-empty-hint">开始与 AI 对话吧。可创建分类/页面/草稿，整理内容或改设置（写操作需确认）。</div>';
            return;
        }
        var html = '';
        chatMessages.forEach(function (msg) {
            if (msg.role === 'system') return;
            var roleClass = msg.role === 'user' ? 'user' : 'assistant';
            var roleName = msg.role === 'user' ? '我' : 'AI';
            html += '<div class="ai-msg ' + roleClass + '">' +
                '<span class="role-tag">' + roleName + '</span>' +
                '<div class="bubble">' + escapeHtml(msg.content) + '</div></div>';
        });
        chatBox.innerHTML = html;
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    function sendChat() {
        if (!chatInput || !window.HOPE_AI_READY) return;
        var text = chatInput.value.trim();
        if (!text) return;

        chatMessages.push({ role: 'user', content: text });
        chatInput.value = '';
        renderChat();

        showLoading('AI 思考中…');
        postAi('chat', {
            model: chatModel ? chatModel.value : '',
            messages: JSON.stringify(chatMessages)
        }).then(function (res) {
            hideLoading();
            if (!hopeApiSuccess(res.code)) {
                chatMessages.pop();
                renderChat();
                toast('error', res.msg || '对话失败');
                return;
            }
            var reply = res.data && res.data.content ? res.data.content : '';
            chatMessages.push({ role: 'assistant', content: reply });
            renderChat();
        }).catch(function () {
            hideLoading();
            chatMessages.pop();
            renderChat();
            toast('error', '网络请求失败');
        });
    }

    var sendBtn = document.getElementById('ai-chat-send');
    if (sendBtn) sendBtn.addEventListener('click', sendChat);

    if (chatInput) {
        chatInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendChat();
            }
        });
    }

    var clearBtn = document.getElementById('ai-chat-clear');
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            chatMessages = [];
            renderChat();
        });
    }

    /* ---------- AI 写作 ---------- */
    var writeBtn = document.getElementById('ai-write-generate');
    if (writeBtn) {
        writeBtn.addEventListener('click', function () {
            if (!window.HOPE_AI_READY) return;
            var promptEl = document.getElementById('ai-write-prompt');
            var titleEl = document.getElementById('ai-write-title');
            var modelEl = document.getElementById('ai-write-model');
            var sortEl = document.getElementById('ai-write-sortid');
            var coverEl = document.getElementById('ai-write-with-cover');
            var draftEl = document.getElementById('ai-write-save-draft');
            var resultEl = document.getElementById('ai-write-result');
            var prompt = promptEl ? promptEl.value.trim() : '';
            var title = titleEl ? titleEl.value.trim() : '';
            if (!prompt && !title) {
                toast('warning', '请填写写作主题或指定标题');
                return;
            }
            showLoading('正在生成文章（含摘要/标签' + (coverEl && coverEl.checked ? '/配图' : '') + '）…');
            postAi('write_article', {
                prompt: prompt,
                title: title,
                model: modelEl ? modelEl.value : '',
                sortid: sortEl ? sortEl.value : '-1',
                with_cover: coverEl && coverEl.checked ? 'y' : 'n',
                save_draft: draftEl && draftEl.checked ? 'y' : 'n',
                image_model: (document.getElementById('ai-image-model') || {}).value || '',
                image_size: '1024x1024'
            }).then(function (res) {
                hideLoading();
                if (!hopeApiSuccess(res.code)) {
                    toast('error', res.msg || '生成失败');
                    return;
                }
                var d = res.data || {};
                var html = '';
                if (d.cover) {
                    html += '<div class="mb-3 text-center"><img src="' + escapeHtml(d.cover) + '" alt="cover" style="max-width:100%;border-radius:.75rem;"></div>';
                }
                html += '<h5 class="mb-2">' + escapeHtml(d.title || '') + '</h5>';
                if (d.excerpt) html += '<p class="text-sm text-muted">' + escapeHtml(d.excerpt) + '</p>';
                if (d.tags) html += '<p class="text-xs mb-2"><strong>标签：</strong>' + escapeHtml(d.tags) + '</p>';
                if (d.article_id) {
                    html += '<p class="text-sm text-success mb-2">已保存草稿 #' + d.article_id +
                        ' · <a href="?act=article_edit&gid=' + d.article_id + '">去编辑</a></p>';
                }
                html += '<pre class="bg-white border rounded p-3 text-sm" style="white-space:pre-wrap;max-height:360px;overflow:auto;">' +
                    escapeHtml(d.content || '') + '</pre>';
                if (resultEl) resultEl.innerHTML = html;
                toast('success', d.msg || '文章已生成');
            }).catch(function () {
                hideLoading();
                toast('error', '网络请求失败');
            });
        });
    }

    /* ---------- AI 翻译 ---------- */
    var transBtn = document.getElementById('ai-trans-run');
    if (transBtn) {
        transBtn.addEventListener('click', function () {
            if (!window.HOPE_AI_READY) return;
            var contentEl = document.getElementById('ai-trans-content');
            var langEl = document.getElementById('ai-trans-lang');
            var modelEl = document.getElementById('ai-trans-model');
            var resultEl = document.getElementById('ai-trans-result');
            var content = contentEl ? contentEl.value.trim() : '';
            if (!content) {
                toast('warning', '请输入待翻译内容');
                return;
            }
            showLoading('正在翻译…');
            postAi('translate', {
                content: content,
                lang: langEl ? langEl.value : 'en',
                model: modelEl ? modelEl.value : ''
            }).then(function (res) {
                hideLoading();
                if (!hopeApiSuccess(res.code)) {
                    toast('error', res.msg || '翻译失败');
                    return;
                }
                if (resultEl) resultEl.value = (res.data && res.data.content) || '';
                toast('success', '翻译完成');
            }).catch(function () {
                hideLoading();
                toast('error', '网络请求失败');
            });
        });
    }
    var transCopy = document.getElementById('ai-trans-copy');
    if (transCopy) {
        transCopy.addEventListener('click', function () {
            var resultEl = document.getElementById('ai-trans-result');
            if (!resultEl || !resultEl.value) return;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(resultEl.value).then(function () {
                    toast('success', '已复制');
                });
            } else {
                resultEl.select();
                document.execCommand('copy');
                toast('success', '已复制');
            }
        });
    }

    /* ---------- 一键建站 ---------- */
    var setupPlanData = null;
    var setupPlanBtn = document.getElementById('ai-setup-plan');
    var setupApplyBtn = document.getElementById('ai-setup-apply');
    if (setupPlanBtn) {
        setupPlanBtn.addEventListener('click', function () {
            if (!window.HOPE_AI_READY) return;
            var briefEl = document.getElementById('ai-setup-brief');
            var modelEl = document.getElementById('ai-setup-model');
            var previewEl = document.getElementById('ai-setup-preview');
            var brief = briefEl ? briefEl.value.trim() : '';
            if (!brief) {
                toast('warning', '请输入站点主题或建站需求');
                return;
            }
            showLoading('正在生成建站方案…');
            postAi('site_plan', {
                prompt: brief,
                model: modelEl ? modelEl.value : ''
            }).then(function (res) {
                hideLoading();
                if (!hopeApiSuccess(res.code)) {
                    toast('error', res.msg || '方案生成失败');
                    return;
                }
                setupPlanData = res.data || null;
                if (previewEl) {
                    previewEl.textContent = JSON.stringify(setupPlanData, null, 2);
                }
                if (setupApplyBtn) setupApplyBtn.disabled = !setupPlanData;
                toast('success', '方案已生成，请确认后应用');
            }).catch(function () {
                hideLoading();
                toast('error', '网络请求失败');
            });
        });
    }
    if (setupApplyBtn) {
        setupApplyBtn.addEventListener('click', function () {
            if (!setupPlanData) {
                toast('warning', '请先生成方案');
                return;
            }
            var doApply = function () {
                showLoading('正在应用建站方案…');
                postAi('apply_site_plan', {
                    plan: JSON.stringify(setupPlanData),
                    confirmed: 'y',
                    create_categories: document.getElementById('ai-setup-cat') && document.getElementById('ai-setup-cat').checked ? 'y' : 'n',
                    create_pages: document.getElementById('ai-setup-page') && document.getElementById('ai-setup-page').checked ? 'y' : 'n',
                    update_settings: document.getElementById('ai-setup-settings') && document.getElementById('ai-setup-settings').checked ? 'y' : 'n'
                }).then(function (res) {
                    hideLoading();
                    if (!hopeApiSuccess(res.code)) {
                        toast('error', res.msg || '应用失败');
                        return;
                    }
                    var d = res.data || {};
                    var msg = '已创建分类 ' + ((d.categories || []).length) +
                        ' 个，页面 ' + ((d.pages || []).length) + ' 个';
                    toast('success', msg);
                    var previewEl = document.getElementById('ai-setup-preview');
                    if (previewEl) {
                        previewEl.textContent = '应用结果：\n' + JSON.stringify(d, null, 2);
                    }
                }).catch(function () {
                    hideLoading();
                    toast('error', '网络请求失败');
                });
            };
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: '确认应用建站方案？',
                    text: '将按勾选项创建分类/页面并更新基础设置',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: '确认应用',
                    cancelButtonText: '取消'
                }).then(function (r) {
                    if (r.isConfirmed) doApply();
                });
            } else if (confirm('确认应用建站方案？')) {
                doApply();
            }
        });
    }

    var imageBtn = document.getElementById('ai-image-generate');
    if (imageBtn) {
        imageBtn.addEventListener('click', function () {
            if (!window.HOPE_AI_READY) return;
            var promptEl = document.getElementById('ai-image-prompt');
            var modelEl = document.getElementById('ai-image-model');
            var sizeEl = document.getElementById('ai-image-size');
            var saveEl = document.getElementById('ai-image-save-media');
            var preview = document.getElementById('ai-image-preview');
            var prompt = promptEl ? promptEl.value.trim() : '';
            if (!prompt) {
                toast('warning', '请输入图像描述');
                return;
            }

            showLoading('正在生成图像…');
            postAi('image', {
                prompt: prompt,
                model: modelEl ? modelEl.value : '',
                size: sizeEl ? sizeEl.value : '1024x1024',
                save_media: saveEl && saveEl.checked ? 'y' : 'n'
            }).then(function (res) {
                hideLoading();
                if (!hopeApiSuccess(res.code)) {
                    toast('error', res.msg || '生成失败');
                    return;
                }
                var url = res.data && res.data.url ? res.data.url : '';
                if (!url || !preview) return;
                var note = res.data.aid ? '已保存到媒体库' : (res.data.remote ? '远程链接（未本地化）' : '已保存到本站');
                preview.innerHTML = '<img src="' + escapeHtml(url) + '" alt="AI Image">' +
                    '<div class="mt-3">' +
                    '<a href="' + escapeHtml(url) + '" target="_blank" class="btn btn-sm btn-outline-primary me-2">查看原图</a>' +
                    '<span class="text-sm text-muted">' + note + '</span></div>';
                toast('success', '图像生成成功');
            }).catch(function () {
                hideLoading();
                toast('error', '网络请求失败');
            });
        });
    }

    /* ---------- AI 配置：多模型管理（弹窗添加） ---------- */
    var presetModels = {
        'https://api.openai.com/v1': {
            chat: [
                { id: 'gpt-4o-mini', label: 'GPT-4o Mini' },
                { id: 'gpt-4o', label: 'GPT-4o' }
            ],
            image: [
                { id: 'dall-e-3', label: 'DALL-E 3' },
                { id: 'dall-e-2', label: 'DALL-E 2' }
            ]
        },
        'https://api.deepseek.com/v1': {
            chat: [
                { id: 'deepseek-v4-flash', label: 'DeepSeek V4 Flash' },
                { id: 'deepseek-chat', label: 'DeepSeek Chat' },
                { id: 'deepseek-reasoner', label: 'DeepSeek Reasoner' }
            ],
            image: []
        },
        'https://dashscope.aliyuncs.com/compatible-mode/v1': {
            chat: [
                { id: 'qwen-plus', label: '通义千问 Plus' },
                { id: 'qwen-turbo', label: '通义千问 Turbo' }
            ],
            image: [{ id: 'wanx-v1', label: '通义万相' }]
        },
        'https://api.moonshot.cn/v1': {
            chat: [
                { id: 'moonshot-v1-8k', label: 'Moonshot 8K' },
                { id: 'moonshot-v1-32k', label: 'Moonshot 32K' }
            ],
            image: []
        }
    };

    var chatModelsStore = [];
    var imageModelsStore = [];
    var defaultChatModelId = '';
    var defaultImageModelId = '';
    var modelModalInstance = null;

    function cloneModel(m) {
        return {
            id: m.id || '',
            label: m.label || m.id || '',
            api_base: m.api_base || '',
            api_key: m.api_key || '',
            has_api_key: !!m.has_api_key,
            api_key_masked: m.api_key_masked || ''
        };
    }

    function getModelStore(type) {
        return type === 'chat' ? chatModelsStore : imageModelsStore;
    }

    function getDefaultModelId(type) {
        return type === 'chat' ? defaultChatModelId : defaultImageModelId;
    }

    function setDefaultModelId(type, id) {
        if (type === 'chat') defaultChatModelId = id;
        else defaultImageModelId = id;
        var hidden = document.getElementById(type === 'chat' ? 'ai-default-chat-model' : 'ai-default-image-model');
        if (hidden) hidden.value = id;
    }

    function shortApiBase(url) {
        if (!url) return '<span class="text-muted">未配置</span>';
        return escapeHtml(url);
    }

    function keyStatusCell(m) {
        if (m.api_key) return '<span class="text-success">已填</span>';
        if (m.has_api_key) return '<span class="text-info">已存</span>';
        return '<span class="text-danger">未配置</span>';
    }

    function renderModelTable(type) {
        var body = document.getElementById(type === 'chat' ? 'ai-chat-models-body' : 'ai-image-models-body');
        if (!body) return;
        var store = getModelStore(type);
        var defaultId = getDefaultModelId(type);
        if (!store.length) {
            body.innerHTML = '<tr><td colspan="6" class="text-center text-muted text-sm py-3">暂无模型，请点击「添加模型」</td></tr>';
            return;
        }
        var html = '';
        store.forEach(function (m, idx) {
            var isDef = m.id === defaultId || (!defaultId && idx === 0);
            html += '<tr data-index="' + idx + '">' +
                '<td>' + escapeHtml(m.label || m.id) + '</td>' +
                '<td><code class="text-xs">' + escapeHtml(m.id) + '</code></td>' +
                '<td class="api-base-cell">' + shortApiBase(m.api_base) + '</td>' +
                '<td>' + keyStatusCell(m) + '</td>' +
                '<td class="text-center"><input type="radio" name="default_' + type + '" class="form-check-input model-default"' +
                (isDef ? ' checked' : '') + ' data-id="' + escapeHtml(m.id) + '"></td>' +
                '<td class="text-nowrap">' +
                '<button type="button" class="btn btn-sm btn-outline-primary mb-0 me-1 model-edit" data-type="' + type + '" data-index="' + idx + '">编辑</button>' +
                '<button type="button" class="btn btn-sm btn-outline-danger mb-0 model-remove" data-type="' + type + '" data-index="' + idx + '">删除</button>' +
                '</td></tr>';
        });
        body.innerHTML = html;

        body.querySelectorAll('.model-default').forEach(function (radio) {
            radio.addEventListener('change', function () {
                if (this.checked) setDefaultModelId(type, this.getAttribute('data-id') || '');
            });
        });
        body.querySelectorAll('.model-edit').forEach(function (btn) {
            btn.addEventListener('click', function () {
                openModelModal(btn.getAttribute('data-type'), parseInt(btn.getAttribute('data-index'), 10));
            });
        });
        body.querySelectorAll('.model-remove').forEach(function (btn) {
            btn.addEventListener('click', function () {
                removeModel(btn.getAttribute('data-type'), parseInt(btn.getAttribute('data-index'), 10));
            });
        });

        if (!defaultId && store.length) {
            setDefaultModelId(type, store[0].id);
        }
    }

    function fillPresetModelSelect(type, presetUrl) {
        var sel = document.getElementById('ai-model-preset-model');
        if (!sel) return;
        sel.innerHTML = '<option value="">手动填写 Model</option>';
        var preset = presetModels[presetUrl];
        if (!preset) return;
        var list = type === 'image' ? (preset.image || []) : (preset.chat || []);
        list.forEach(function (m) {
            var opt = document.createElement('option');
            opt.value = m.id;
            opt.textContent = m.label + ' (' + m.id + ')';
            opt.setAttribute('data-label', m.label);
            sel.appendChild(opt);
        });
    }

    function syncModalPresetSelect(apiBase) {
        var presetSel = document.getElementById('ai-model-preset');
        if (!presetSel) return;
        var matched = '';
        Array.from(presetSel.options).forEach(function (opt) {
            if (opt.value && opt.value === apiBase) matched = opt.value;
        });
        presetSel.value = matched;
    }

    function getModelModal() {
        var el = document.getElementById('ai-model-modal');
        if (!el || typeof bootstrap === 'undefined') return null;
        if (!modelModalInstance) modelModalInstance = bootstrap.Modal.getOrCreateInstance(el);
        return modelModalInstance;
    }

    function openModelModal(type, index) {
        var isEdit = index >= 0;
        var store = getModelStore(type);
        var model = isEdit ? cloneModel(store[index]) : {
            id: '', label: '', api_base: '', api_key: '', has_api_key: false, api_key_masked: ''
        };

        document.getElementById('ai-model-modal-type').value = type;
        document.getElementById('ai-model-modal-index').value = String(index);
        document.getElementById('ai-model-modal-title').textContent = isEdit ? '编辑模型' : '添加模型';
        document.getElementById('ai-model-api-base').value = model.api_base || '';
        document.getElementById('ai-model-api-key').value = model.api_key || '';
        document.getElementById('ai-model-id').value = model.id || '';
        document.getElementById('ai-model-label').value = model.label || '';

        var keyHint = document.getElementById('ai-model-api-key-hint');
        if (keyHint) {
            if (model.has_api_key && model.api_key_masked) {
                keyHint.textContent = '已配置 ' + model.api_key_masked + '，留空不修改';
            } else {
                keyHint.textContent = '格式如 sk-****，每个模型单独配置';
            }
        }

        syncModalPresetSelect(model.api_base || '');
        fillPresetModelSelect(type, document.getElementById('ai-model-preset').value);

        var modal = getModelModal();
        if (modal) modal.show();
    }

    function saveModelModal() {
        var type = document.getElementById('ai-model-modal-type').value || 'chat';
        var index = parseInt(document.getElementById('ai-model-modal-index').value, 10);
        var id = document.getElementById('ai-model-id').value.trim();
        var label = document.getElementById('ai-model-label').value.trim();
        var apiBase = document.getElementById('ai-model-api-base').value.trim().replace(/\/chat\/completions\/?$/, '');
        var apiKey = document.getElementById('ai-model-api-key').value.trim();
        var store = getModelStore(type);

        if (!id) {
            toast('warning', '请填写 Model（模型 ID）');
            return;
        }
        if (!apiKey && !(index >= 0 && store[index].has_api_key)) {
            toast('warning', '请填写 API Key');
            return;
        }
        var dup = store.some(function (m, i) { return m.id === id && i !== index; });
        if (dup) {
            toast('warning', 'Model ID 已存在：' + id);
            return;
        }

        var entry = {
            id: id,
            label: label || id,
            api_base: apiBase,
            api_key: apiKey
        };
        if (index >= 0) {
            if (!apiKey && store[index].has_api_key) {
                entry.has_api_key = true;
                entry.api_key_masked = store[index].api_key_masked;
            }
            store[index] = Object.assign({}, store[index], entry);
        } else {
            if (!apiKey) entry.has_api_key = false;
            store.push(entry);
            if (store.length === 1) setDefaultModelId(type, id);
        }

        renderModelTable(type);
        var modal = getModelModal();
        if (modal) modal.hide();
    }

    function removeModel(type, index) {
        var store = getModelStore(type);
        if (store.length <= 1) {
            toast('warning', '至少保留一个模型');
            return;
        }
        var removedId = store[index].id;
        store.splice(index, 1);
        if (getDefaultModelId(type) === removedId) {
            setDefaultModelId(type, store[0] ? store[0].id : '');
        }
        renderModelTable(type);
    }

    function collectModels(type) {
        var store = getModelStore(type);
        var models = store.map(function (m) {
            var item = { id: m.id, label: m.label || m.id };
            if (m.api_base) item.api_base = m.api_base;
            if (m.api_key) item.api_key = m.api_key;
            return item;
        }).filter(function (m) { return m.id !== ''; });
        return { models: models, defaultId: getDefaultModelId(type) || (models[0] && models[0].id) || '' };
    }

    function initConfigTab() {
        var cfg = window.HOPE_AI_CONFIG || {};
        chatModelsStore = (cfg.chat_models || []).map(cloneModel).filter(function (m) { return m.id !== ''; });
        imageModelsStore = (cfg.image_models || []).map(cloneModel).filter(function (m) { return m.id !== ''; });
        defaultChatModelId = cfg.default_chat_model || '';
        defaultImageModelId = cfg.default_image_model || '';

        renderModelTable('chat');
        renderModelTable('image');

        var addChatBtn = document.getElementById('ai-add-chat-model');
        var addImageBtn = document.getElementById('ai-add-image-model');
        if (addChatBtn) addChatBtn.addEventListener('click', function () { openModelModal('chat', -1); });
        if (addImageBtn) addImageBtn.addEventListener('click', function () { openModelModal('image', -1); });

        var modalSaveBtn = document.getElementById('ai-model-modal-save');
        if (modalSaveBtn) modalSaveBtn.addEventListener('click', saveModelModal);

        var modelPreset = document.getElementById('ai-model-preset');
        var modelApiBase = document.getElementById('ai-model-api-base');
        if (modelPreset && modelApiBase) {
            modelPreset.addEventListener('change', function () {
                if (this.value) modelApiBase.value = this.value;
                var type = document.getElementById('ai-model-modal-type').value || 'chat';
                fillPresetModelSelect(type, this.value);
            });
        }

        var modelPresetModel = document.getElementById('ai-model-preset-model');
        if (modelPresetModel) {
            modelPresetModel.addEventListener('change', function () {
                if (!this.value) return;
                var opt = this.options[this.selectedIndex];
                document.getElementById('ai-model-id').value = this.value;
                document.getElementById('ai-model-label').value = opt.getAttribute('data-label') || this.value;
            });
        }

        var saveBtn = document.getElementById('ai-config-save');
        if (saveBtn) {
            saveBtn.addEventListener('click', function () {
                var chatData = collectModels('chat');
                var imageData = collectModels('image');
                if (!chatData.models.length) {
                    toast('warning', '请至少添加一个文本对话模型');
                    return;
                }

                var fd = new FormData(document.getElementById('ai-config-form'));
                fd.append('token', getToken());
                fd.append('ai_enabled', document.getElementById('ai-enabled-switch') && document.getElementById('ai-enabled-switch').checked ? 'y' : 'n');
                fd.append('ai_agent_readonly', document.getElementById('ai-agent-readonly-switch') && document.getElementById('ai-agent-readonly-switch').checked ? 'y' : 'n');
                fd.append('ai_chat_models', JSON.stringify(chatData.models));
                fd.append('ai_image_models', JSON.stringify(imageData.models));
                fd.append('ai_default_chat_model', chatData.defaultId);
                fd.append('ai_default_image_model', imageData.defaultId);

                showLoading('保存配置…');
                fetch(window.HOPE_AI_SAVE_URL || '?act=ai_studio&save', { method: 'POST', body: fd })
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        hideLoading();
                        if (hopeApiSuccess(res.code)) {
                            toast('success', '配置已保存');
                            setTimeout(function () { window.location.reload(); }, 800);
                        } else {
                            toast('error', res.msg || '保存失败');
                        }
                    })
                    .catch(function () {
                        hideLoading();
                        toast('error', '保存请求失败');
                    });
            });
        }

        var testBtn = document.getElementById('ai-test-btn');
        if (testBtn) {
            testBtn.addEventListener('click', function () {
                var resultEl = document.getElementById('ai-test-result');
                var chatData = collectModels('chat');
                var testModelId = chatData.defaultId || (chatData.models[0] && chatData.models[0].id) || '';
                var testModel = chatModelsStore.find(function (m) { return m.id === testModelId; }) || {};

                if (!testModelId) {
                    toast('warning', '请先添加文本对话模型');
                    return;
                }
                if (!testModel.api_key && !testModel.has_api_key) {
                    toast('warning', '请为该模型填写 API Key');
                    return;
                }

                if (resultEl) resultEl.innerHTML = '<span class="text-info">正在测试…</span>';

                var fd = new FormData();
                fd.append('token', getToken());
                fd.append('action', 'test');
                fd.append('ai_model', testModelId);
                if (testModel.api_base) {
                    fd.append('ai_api_base', testModel.api_base);
                }
                if (testModel.api_key) {
                    fd.append('ai_api_key', testModel.api_key);
                }

                fetch(window.HOPE_AI_URL || '?act=ai', { method: 'POST', body: fd })
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        if (!resultEl) return;
                        if (hopeApiSuccess(res.code)) {
                            resultEl.innerHTML = '<span class="text-success">连接成功：' + escapeHtml(res.data && res.data.content ? res.data.content : 'ok') + '</span>';
                        } else {
                            resultEl.innerHTML = '<span class="text-danger">连接失败：' + escapeHtml(res.msg || '未知错误') + '</span>';
                        }
                    })
                    .catch(function () {
                        if (resultEl) resultEl.innerHTML = '<span class="text-danger">请求失败</span>';
                    });
            });
        }
    }

    if (document.getElementById('ai-config-form')) {
        initConfigTab();
    }
})();
