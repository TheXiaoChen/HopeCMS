(function () {
    'use strict';

    var cfg = window.HOPE_AI_HEADER || {};
    var tokenEl = document.getElementById('hope-admin-token');

    function toast(icon, title) {
        if (typeof Toast !== 'undefined') {
            Toast.fire({ icon: icon, title: title });
        } else if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: icon, title: title, timer: 2500, showConfirmButton: false });
        }
    }

    function apiSuccess(code) {
        return Number(code) === 200;
    }

    var shortcutsSaveBtn = document.getElementById('hope-header-shortcuts-save');
    if (shortcutsSaveBtn) {
        shortcutsSaveBtn.addEventListener('click', function () {
            var ids = [];
            document.querySelectorAll('.hope-header-shortcut-cb:checked').forEach(function (cb) {
                ids.push(cb.value);
            });
            if (!ids.length) {
                toast('warning', '请至少勾选一个快捷入口');
                return;
            }

            var fd = new FormData();
            fd.append('token', tokenEl ? tokenEl.value : '');
            fd.append('header_shortcuts', JSON.stringify(ids));

            shortcutsSaveBtn.disabled = true;
            fetch(cfg.shortcutsSaveUrl || '?act=header_shortcuts&save', { method: 'POST', body: fd })
                .then(function (r) {
                    return r.text().then(function (text) {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            throw new Error('invalid_json');
                        }
                    });
                })
                .then(function (res) {
                    shortcutsSaveBtn.disabled = false;
                    if (!apiSuccess(res.code)) {
                        toast('error', res.msg || '保存失败');
                        return;
                    }
                    toast('success', '快捷入口已更新');
                    var modalEl = document.getElementById('hopeHeaderShortcutsModal');
                    if (modalEl && typeof bootstrap !== 'undefined') {
                        bootstrap.Modal.getInstance(modalEl)?.hide();
                    }
                    setTimeout(function () { window.location.reload(); }, 600);
                })
                .catch(function (err) {
                    shortcutsSaveBtn.disabled = false;
                    toast('error', err && err.message === 'invalid_json' ? '保存失败，请刷新页面后重试' : '保存请求失败');
                });
        });
    }

    (function initHeaderShortcutsScroll() {
        var outer = document.querySelector('.hope-header-shortcuts-outer');
        if (!outer) return;
        var row = outer.querySelector('.hope-header-shortcuts-row');
        var prevBtn = outer.querySelector('.hope-header-shortcuts-nav-prev');
        var nextBtn = outer.querySelector('.hope-header-shortcuts-nav-next');
        if (!row || !prevBtn || !nextBtn) return;

        function updateNav() {
            var maxScroll = row.scrollWidth - row.clientWidth;
            var hasOverflow = maxScroll > 4;
            prevBtn.hidden = !hasOverflow || row.scrollLeft <= 4;
            nextBtn.hidden = !hasOverflow || row.scrollLeft >= maxScroll - 4;
        }

        function scrollBy(dir) {
            row.scrollBy({ left: dir * Math.max(120, row.clientWidth * 0.55), behavior: 'smooth' });
        }

        prevBtn.addEventListener('click', function () { scrollBy(-1); });
        nextBtn.addEventListener('click', function () { scrollBy(1); });
        row.addEventListener('scroll', updateNav, { passive: true });
        window.addEventListener('resize', updateNav);
        row.addEventListener('wheel', function (e) {
            if (row.scrollWidth <= row.clientWidth) return;
            if (Math.abs(e.deltaY) > Math.abs(e.deltaX)) {
                e.preventDefault();
                row.scrollLeft += e.deltaY;
            }
        }, { passive: false });

        updateNav();
        setTimeout(updateNav, 150);
    })();

    if (!cfg.ready) {
        return;
    }

    var messages = [];
    var box = document.getElementById('hope-header-ai-messages');
    var input = document.getElementById('hope-header-ai-input');
    var modelEl = document.getElementById('hope-header-ai-model');
    var sendBtn = document.getElementById('hope-header-ai-send');
    var clearBtn = document.getElementById('hope-header-ai-clear');

    function escapeHtml(s) {
        return String(s || '').replace(/[&<>"']/g, function (c) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
        });
    }

    function renderPendingActions(pending) {
        if (!pending || !pending.length || !box) return;
        var html = '<div class="hope-header-ai-pending">';
        html += '<div class="text-xs text-warning mb-2"><i class="fa fa-shield-halved me-1"></i>以下操作需确认后执行：</div>';
        pending.forEach(function (item) {
            var cls = item.danger ? 'danger' : (item.sensitive ? 'warning' : 'info');
            html += '<div class="hope-header-ai-pending-item border-' + cls + '">' +
                '<div class="text-sm mb-2">' + escapeHtml(item.label) + '</div>' +
                '<button type="button" class="btn btn-sm btn-' + (item.danger ? 'danger' : 'primary') + ' mb-0 hope-header-ai-confirm" data-action="' + escapeHtml(item.action_json) + '">' +
                (item.danger ? '确认删除' : '确认执行') + '</button></div>';
        });
        html += '</div>';
        box.insertAdjacentHTML('beforeend', html);
        box.querySelectorAll('.hope-header-ai-confirm').forEach(function (btn) {
            btn.addEventListener('click', function () {
                executePendingAction(btn.getAttribute('data-action'), btn);
            });
        });
        box.scrollTop = box.scrollHeight;
    }

    function executePendingAction(actionJson, btnEl) {
        if (!actionJson) return;
        var action = null;
        try { action = JSON.parse(actionJson); } catch (e) { return; }
        var meta = action.tool === 'delete_article';
        var label = btnEl && btnEl.closest('.hope-header-ai-pending-item')
            ? btnEl.closest('.hope-header-ai-pending-item').querySelector('.text-sm').textContent
            : '该操作';

        var doExecute = function () {
            if (btnEl) btnEl.disabled = true;
            var fd = new FormData();
            fd.append('token', tokenEl ? tokenEl.value : '');
            fd.append('action', 'agent_execute');
            fd.append('confirmed', 'y');
            fd.append('agent_action', actionJson);

            fetch(cfg.url, { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (!apiSuccess(res.code)) {
                        toast('error', res.msg || '执行失败');
                        if (btnEl) btnEl.disabled = false;
                        return;
                    }
                    var msg = res.msg || '操作成功';
                    messages.push({ role: 'assistant', content: '✅ ' + msg });
                    if (btnEl && btnEl.closest('.hope-header-ai-pending-item')) {
                        btnEl.closest('.hope-header-ai-pending-item').innerHTML =
                            '<span class="text-success text-sm"><i class="fa fa-check me-1"></i>' + escapeHtml(msg) + '</span>';
                    } else {
                        appendBubble('assistant', '✅ ' + msg);
                    }
                    toast('success', msg);
                })
                .catch(function () {
                    toast('error', '请求失败');
                    if (btnEl) btnEl.disabled = false;
                });
        };

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: meta ? '确认删除？' : '确认执行敏感操作？',
                html: '<p class="text-sm">' + escapeHtml(label) + '</p><p class="text-xs text-muted mb-0">此操作将修改系统数据库，请确认无误。</p>',
                icon: meta ? 'warning' : 'question',
                showCancelButton: true,
                confirmButtonText: meta ? '确认删除' : '确认执行',
                cancelButtonText: '取消',
                confirmButtonColor: meta ? '#f5365c' : '#5e72e4'
            }).then(function (result) {
                if (result.isConfirmed) doExecute();
            });
        } else if (confirm('确认执行：' + label + '？')) {
            doExecute();
        }
    }

    function appendBubble(role, content) {
        if (!box) return;
        var empty = box.querySelector('.hope-header-ai-empty');
        if (empty) empty.remove();
        var cls = role === 'user' ? 'user' : 'assistant';
        var name = role === 'user' ? '我' : 'AI';
        box.insertAdjacentHTML('beforeend',
            '<div class="hope-header-ai-msg ' + cls + '">' +
            '<span class="hope-header-ai-role">' + name + '</span>' +
            '<div class="hope-header-ai-bubble">' + escapeHtml(content) + '</div></div>');
        box.scrollTop = box.scrollHeight;
    }

    function render() {
        if (!box) return;
        if (!messages.length) {
            box.innerHTML = '<div class="hope-header-ai-empty text-center text-muted text-sm">可询问站点数据，或让我帮你改配置、写文章</div>';
            return;
        }
        var html = '';
        messages.forEach(function (msg) {
            if (msg.role === 'system') return;
            var cls = msg.role === 'user' ? 'user' : 'assistant';
            var name = msg.role === 'user' ? '我' : 'AI';
            html += '<div class="hope-header-ai-msg ' + cls + '">' +
                '<span class="hope-header-ai-role">' + name + '</span>' +
                '<div class="hope-header-ai-bubble">' + escapeHtml(msg.content) + '</div></div>';
        });
        box.innerHTML = html;
        box.scrollTop = box.scrollHeight;
    }

    function sendChat() {
        if (!input) return;
        var text = input.value.trim();
        if (!text) return;

        messages.push({ role: 'user', content: text });
        input.value = '';
        render();

        if (sendBtn) sendBtn.disabled = true;
        var fd = new FormData();
        fd.append('token', tokenEl ? tokenEl.value : '');
        fd.append('action', 'agent_chat');
        fd.append('model', modelEl ? modelEl.value : '');
        fd.append('messages', JSON.stringify(messages));

        fetch(cfg.url, { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (sendBtn) sendBtn.disabled = false;
                if (!apiSuccess(res.code)) {
                    messages.pop();
                    render();
                    toast('error', res.msg || '对话失败');
                    return;
                }
                var reply = res.data && res.data.content ? res.data.content : '';
                messages.push({ role: 'assistant', content: reply });
                render();
                if (res.data && res.data.pending_actions && res.data.pending_actions.length) {
                    renderPendingActions(res.data.pending_actions);
                }
            })
            .catch(function () {
                if (sendBtn) sendBtn.disabled = false;
                messages.pop();
                render();
                toast('error', '网络请求失败');
            });
    }

    if (sendBtn) sendBtn.addEventListener('click', sendChat);
    if (input) {
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendChat();
            }
        });
    }
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            messages = [];
            render();
        });
    }
})();
