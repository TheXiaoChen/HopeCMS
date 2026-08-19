(function () {
    'use strict';

    function getToken() {
        var el = document.getElementById('token');
        return el ? el.value : '';
    }

    function getTitle() {
        var el = document.getElementById('title');
        return el ? el.value.trim() : '';
    }

    function getContent() {
        if (typeof Editor !== 'undefined' && Editor && Editor.getMarkdown) {
            return Editor.getMarkdown().trim();
        }
        var ta = document.querySelector('#logcontent textarea');
        return ta ? ta.value.trim() : '';
    }

    function getSelection() {
        if (typeof Editor !== 'undefined' && Editor && Editor.cm) {
            return (Editor.cm.getSelection() || '').trim();
        }
        return '';
    }

    function setTitle(val) {
        var el = document.getElementById('title');
        if (el) el.value = val;
    }

    function setExcerpt(val) {
        var el = document.querySelector('textarea[name="logexcerpt"]');
        if (!el) return;
        el.value = val;
        var collapse = document.getElementById('excerpt');
        if (collapse && !collapse.classList.contains('show')) {
            collapse.classList.add('show');
        }
    }

    function setTags(val) {
        var el = document.querySelector('input[name="tag"]');
        if (el) el.value = val;
    }

    /**
     * @param {string} val
     * @param {'replace'|'append'|'cursor'} mode
     */
    function setContent(val, mode) {
        if (typeof Editor === 'undefined' || !Editor) return;
        mode = mode || 'replace';
        if (mode === 'cursor' && Editor.cm) {
            Editor.cm.replaceSelection(String(val || ''));
            Editor.cm.focus();
            return;
        }
        if (mode === 'append' && Editor.getMarkdown) {
            var cur = Editor.getMarkdown();
            Editor.setMarkdown(cur ? cur.replace(/\s*$/, '') + '\n\n' + val : val);
            return;
        }
        Editor.setMarkdown(val);
    }

    function showLoading(msg) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: msg || 'AI 处理中…',
                allowOutsideClick: false,
                didOpen: function () { Swal.showLoading(); }
            });
        }
    }

    function hideLoading() {
        if (typeof Swal !== 'undefined') Swal.close();
    }

    function toast(icon, title) {
        if (typeof Toast !== 'undefined') {
            Toast.fire({ icon: icon, title: title });
        } else if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: icon, title: title, timer: 2000, showConfirmButton: false });
        } else {
            alert(title);
        }
    }

    function setCover(url) {
        var el = document.getElementById('cover');
        if (el) el.value = url;
        var preview = document.getElementById('cover_preview');
        if (preview && url) {
            preview.src = url;
            preview.style.display = '';
        }
    }

    function getExcerpt() {
        var el = document.querySelector('textarea[name="logexcerpt"]');
        return el ? el.value.trim() : '';
    }

    function aiRequest(task, extra) {
        var fd = new FormData();
        fd.append('token', getToken());
        var action = 'generate';
        if (task === 'article_cover') action = 'article_cover';
        if (task === 'translate') action = 'translate';
        fd.append('action', action);
        if (action === 'generate') fd.append('task', task);
        fd.append('title', getTitle());
        var selection = getSelection();
        // 润色优先用选区；续写/撰写仍传全文
        var contentForAi = (task === 'polish' && selection) ? selection : getContent();
        fd.append('content', contentForAi);
        fd.append('excerpt', getExcerpt());
        var modelEl = document.getElementById('ai-assist-model');
        if (modelEl && modelEl.value) fd.append('model', modelEl.value);
        if (extra && extra.prompt) fd.append('prompt', extra.prompt);
        if (extra && extra.lang) fd.append('lang', extra.lang);

        showLoading('AI 处理中，请稍候…');
        return fetch(window.HOPE_AI_URL || '?act=ai', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .finally(hideLoading);
    }

    function applyBundle(bundle) {
        if (!bundle) return;
        if (bundle.title) setTitle(String(bundle.title).replace(/^["'「『]|["'」』]$/g, ''));
        if (bundle.excerpt) setExcerpt(bundle.excerpt);
        if (bundle.content) setContent(bundle.content, 'replace');
        if (bundle.tags) setTags(String(bundle.tags).replace(/，/g, ','));
    }

    function handleTask(task, needPrompt) {
        var run = function (prompt, lang) {
            aiRequest(task, { prompt: prompt || '', lang: lang || 'en' }).then(function (res) {
                if (!hopeApiSuccess(res.code)) {
                    toast('error', res.msg || 'AI 请求失败');
                    return;
                }
                var data = res.data || {};
                if (task === 'article_cover') {
                    if (!data.url) {
                        toast('error', '未生成封面');
                        return;
                    }
                    setCover(data.url);
                    toast('success', '封面已生成并填入');
                    return;
                }
                if (task === 'translate') {
                    var translated = data.content || '';
                    if (!translated) {
                        toast('error', '译文为空');
                        return;
                    }
                    setContent(translated, 'replace');
                    toast('success', '正文已替换为译文');
                    return;
                }
                if (task === 'article_bundle') {
                    if (data.bundle) {
                        applyBundle(data.bundle);
                        toast('success', '一键成稿完成');
                        return;
                    }
                    toast('error', '文章结构解析失败，请重试');
                    return;
                }
                var text = data.content || '';
                if (!text) {
                    toast('error', 'AI 返回为空');
                    return;
                }
                switch (task) {
                    case 'title':
                        setTitle(text.replace(/^["'「『]|["'」』]$/g, ''));
                        break;
                    case 'excerpt':
                        setExcerpt(text);
                        break;
                    case 'tags':
                        setTags(text.replace(/，/g, ','));
                        break;
                    case 'content':
                        // 有正文：续写插入光标；无正文：整篇写入
                        setContent(text, getContent() ? 'cursor' : 'replace');
                        break;
                    case 'polish':
                        if (getSelection()) {
                            setContent(text, 'cursor');
                        } else if (getContent()) {
                            setContent(text, 'replace');
                        } else {
                            setExcerpt(text);
                        }
                        break;
                }
                toast('success', '完成');
            }).catch(function () {
                toast('error', '网络请求失败');
            });
        };

        if (task === 'translate') {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: '翻译正文',
                    input: 'select',
                    inputOptions: {
                        en: '英语',
                        ja: '日语',
                        ko: '韩语',
                        'zh-cn': '简体中文',
                        'zh-tw': '繁体中文',
                        fr: '法语',
                        de: '德语',
                        es: '西班牙语',
                        ru: '俄语',
                        vi: '越南语'
                    },
                    inputValue: 'en',
                    showCancelButton: true,
                    confirmButtonText: '翻译',
                    cancelButtonText: '取消'
                }).then(function (result) {
                    if (result.isConfirmed) run('', result.value || 'en');
                });
            } else {
                run('', 'en');
            }
            return;
        }

        if (needPrompt) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: task === 'article_bundle' ? '输入成稿要求' : '输入写作要求',
                    input: 'textarea',
                    inputPlaceholder: '例如：写一篇关于 Spring Boot 入门教程，800 字左右',
                    showCancelButton: true,
                    confirmButtonText: '生成',
                    cancelButtonText: '取消'
                }).then(function (result) {
                    if (result.isConfirmed) run(result.value || '');
                });
            } else {
                var p = prompt('输入写作要求（可选）：');
                if (p !== null) run(p);
            }
            return;
        }
        run('');
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-ai-task]');
        if (!btn) return;
        e.preventDefault();
        var task = btn.getAttribute('data-ai-task');
        var needPrompt = btn.getAttribute('data-ai-prompt') === '1';
        handleTask(task, needPrompt);
    });
})();
