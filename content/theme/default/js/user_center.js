/* 个人中心 */
(function () {
    var root = document.getElementById('user-center');
    if (!root) return;

    var API = root.dataset.api;
    var TOKEN = root.dataset.token;
    var AUTH_API = root.dataset.authApi;
    var SITE = root.dataset.site;
    var inviteEnabled = root.dataset.inviteEnabled !== '0';
    var paymentEnabled = root.dataset.paymentEnabled !== '0';
    var forumEditor = null;
    var forumPage = 1;
    var forumPerPage = 10;
    var rechargePage = 1;
    var mediaPage = 1;
    var mediaPerPage = 12;
    var mediaSearchTimer = null;
    var commentsPage = 1;
    var commentsPerPage = 10;

    function esc(str) {
        if (!str) return '';
        var d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    function showToast(msg, type) {
        var toast = document.createElement('div');
        toast.className = 'toast toast-' + (type === 'error' ? 'error' : 'success');
        toast.textContent = msg;
        document.body.appendChild(toast);
        setTimeout(function () { toast.classList.add('show'); }, 10);
        setTimeout(function () {
            toast.classList.remove('show');
            setTimeout(function () { toast.remove(); }, 300);
        }, 2500);
    }

    function api(action, opts) {
        opts = opts || {};
        var method = opts.method || 'GET';
        var url = API;
        if (method === 'GET') {
            url += '&action=' + encodeURIComponent(action);
        }
        if (opts.query) {
            Object.keys(opts.query).forEach(function (k) {
                var v = opts.query[k];
                if (v === '' || v === null || v === undefined) return;
                url += '&' + encodeURIComponent(k) + '=' + encodeURIComponent(v);
            });
        }
        var init = { method: method, credentials: 'same-origin' };
        if (method === 'POST') {
            if (opts.formData) {
                opts.formData.append('token', TOKEN);
                opts.formData.append('action', action);
                init.body = opts.formData;
            } else {
                init.headers = { 'Content-Type': 'application/x-www-form-urlencoded' };
                var body = 'token=' + encodeURIComponent(TOKEN) + '&action=' + encodeURIComponent(action);
                if (opts.data) {
                    Object.keys(opts.data).forEach(function (k) {
                        body += '&' + encodeURIComponent(k) + '=' + encodeURIComponent(opts.data[k]);
                    });
                }
                init.body = body;
            }
        }
        return fetch(url, init).then(function (r) {
            return r.text().then(function (text) {
                var res;
                try {
                    res = JSON.parse(text);
                } catch (e) {
                    throw new Error('服务器响应异常，请刷新页面重试');
                }
                if (!r.ok && (!res || res.code === undefined)) {
                    throw new Error('请求失败 (' + r.status + ')');
                }
                return res;
            });
        }).then(function (res) {
            if (res.code !== 1 && res.code !== 200) throw new Error(res.msg || '请求失败');
            return res.data;
        });
    }

    function renderPagination(containerId, total, page, perPage, onPage) {
        var el = document.getElementById(containerId);
        if (!el) return;
        var pages = Math.ceil(total / perPage) || 1;
        if (total <= 0 || pages <= 1) {
            el.innerHTML = '';
            el.hidden = true;
            return;
        }
        el.hidden = false;
        var html = '<span class="uc-pagination-info">第 ' + page + ' / ' + pages + ' 页 · 共 ' + total + ' 条</span>';
        html += '<div class="uc-pagination-btns">';
        html += '<button type="button" ' + (page <= 1 ? 'disabled' : '') + ' data-page="' + (page - 1) + '">上一页</button>';
        var start = Math.max(1, page - 2);
        var end = Math.min(pages, start + 4);
        start = Math.max(1, end - 4);
        for (var i = start; i <= end; i++) {
            html += '<button type="button" class="' + (i === page ? 'active' : '') + '" data-page="' + i + '">' + i + '</button>';
        }
        html += '<button type="button" ' + (page >= pages ? 'disabled' : '') + ' data-page="' + (page + 1) + '">下一页</button>';
        html += '</div>';
        el.innerHTML = html;
        el.querySelectorAll('button[data-page]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var p = parseInt(btn.dataset.page, 10);
                if (!btn.disabled && p >= 1 && p <= pages) onPage(p);
            });
        });
    }

    function rechargeStatusClass(status) {
        return { success: 'paid', pending: 'pending', failed: 'failed' }[status] || 'pending';
    }

    function openModal(id) { document.getElementById(id).classList.add('show'); }
    function closeModal(id) { document.getElementById(id).classList.remove('show'); }

    function refreshSidebar(profile) {
        var name = profile.nickname || profile.username || '用户';
        var avEl = document.getElementById('sidebar-avatar');
        if (profile.photo) {
            avEl.innerHTML = '<img src="' + esc(profile.photo) + '" alt="">';
            avEl.classList.add('has-img');
        } else {
            avEl.textContent = name.charAt(0).toUpperCase();
            avEl.classList.remove('has-img');
        }
        document.getElementById('sidebar-name').textContent = name;
        document.getElementById('sidebar-email').textContent = profile.email || '';
        var balText = '¥' + (profile.balance || 0).toFixed(2);
        document.getElementById('sidebar-balance').textContent = balText;
        var dashBal = document.getElementById('dash-balance');
        if (dashBal) dashBal.textContent = balText;
        var pfBal = document.getElementById('pf-balance');
        if (pfBal) pfBal.value = balText;
    }

    function updateAvatarPreview(url, name) {
        var el = document.getElementById('avatar-preview');
        if (url) {
            el.innerHTML = '<img src="' + esc(url) + '" alt="">';
        } else {
            el.textContent = (name || 'U').charAt(0).toUpperCase();
        }
    }

    function switchPanel(panel) {
        document.querySelectorAll('.uc-nav a[data-panel]').forEach(function (l) {
            l.classList.toggle('active', l.dataset.panel === panel);
        });
        document.querySelectorAll('.uc-panel').forEach(function (p) {
            p.classList.toggle('active', p.id === 'panel-' + panel);
        });
        history.replaceState(null, '', '?tab=' + panel);
        renderPanel(panel);
        var sidebar = document.getElementById('uc-sidebar');
        if (sidebar) sidebar.classList.remove('is-open');
    }

    document.querySelectorAll('.uc-nav a[data-panel]').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            switchPanel(link.dataset.panel);
        });
    });

    document.querySelectorAll('.uc-quick-card[data-goto]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            switchPanel(btn.dataset.goto);
        });
    });

    var mobileToggle = document.getElementById('uc-mobile-toggle');
    var ucSidebar = document.getElementById('uc-sidebar');
    if (mobileToggle && ucSidebar) {
        mobileToggle.addEventListener('click', function () {
            ucSidebar.classList.toggle('is-open');
        });
    }

    document.getElementById('btn-logout').addEventListener('click', function (e) {
        e.preventDefault();
        if (window.HopeAuthApi && AUTH_API) {
            HopeAuthApi.logout(AUTH_API, TOKEN).catch(function (err) {
                showToast(err.message, 'error');
            });
        } else {
            window.location.href = SITE + 'admin/?act=logout';
        }
    });

    function parseDashTime(str) {
        if (!str) return 0;
        var t = Date.parse(String(str).replace(/-/g, '/'));
        return isNaN(t) ? 0 : t;
    }

    function renderDashActivity(posts, media, comments) {
        var el = document.getElementById('dash-activity-list');
        if (!el) return;
        var items = [];
        (posts || []).forEach(function (p) {
            items.push({
                type: 'article',
                typeLabel: '文章',
                title: p.title || '无标题',
                meta: (p.status_label || '') + (p.sort_name ? ' · ' + p.sort_name : ''),
                date: p.date || '',
                time: parseDashTime(p.date),
                url: p.url || '',
                panel: 'forum'
            });
        });
        (media || []).forEach(function (m) {
            items.push({
                type: 'media',
                typeLabel: m.is_image ? '图片' : '附件',
                title: m.name || '未命名文件',
                meta: (m.size || '') + (m.is_image ? ' · 图片' : ''),
                date: m.date || '',
                time: parseDashTime(m.date),
                url: m.url || '',
                panel: 'media'
            });
        });
        (comments || []).forEach(function (c) {
            items.push({
                type: 'comment',
                typeLabel: c.is_reply ? '回复' : '评论',
                title: c.content || '（空评论）',
                meta: '文章：' + (c.article || '未知'),
                date: c.date || '',
                time: parseDashTime(c.date),
                url: c.comment_url || c.article_url || '',
                panel: 'comments'
            });
        });
        items.sort(function (a, b) { return b.time - a.time; });
        items = items.slice(0, 10);
        if (!items.length) {
            el.innerHTML = '<div class="uc-empty">暂无动态，去发布文章或评论吧</div>';
            return;
        }
        el.innerHTML = items.map(function (it) {
            var dateShort = (it.date || '').slice(0, 16);
            var title = it.url
                ? '<a class="uc-activity-title" href="' + esc(it.url) + '" target="_blank" rel="noopener" title="' + esc(it.title) + '">' + esc(it.title) + '</a>'
                : '<button type="button" class="uc-activity-title is-btn" data-goto="' + esc(it.panel) + '" title="' + esc(it.title) + '">' + esc(it.title) + '</button>';
            return '<article class="uc-activity-item is-' + esc(it.type) + '">' +
                '<span class="uc-activity-type">' + esc(it.typeLabel) + '</span>' +
                '<div class="uc-activity-body">' +
                title +
                (it.meta ? '<div class="uc-activity-meta">' + esc(it.meta) + '</div>' : '') +
                '</div>' +
                '<time class="uc-activity-date" title="' + esc(it.date) + '">' + esc(dateShort) + '</time>' +
                '</article>';
        }).join('');
        el.querySelectorAll('.uc-activity-title.is-btn[data-goto]').forEach(function (btn) {
            btn.addEventListener('click', function () { switchPanel(btn.dataset.goto); });
        });
    }

    function renderDashboard() {
        api('profile').then(function (p) { refreshSidebar(p); });
        var activityEl = document.getElementById('dash-activity-list');
        if (activityEl) activityEl.innerHTML = '<div class="uc-empty">加载中…</div>';

        Promise.all([
            api('forum_posts', { query: { page: 1, per_page: 5 } }).catch(function () { return { list: [], total: 0 }; }),
            api('my_media', { query: { page: 1, per_page: 5 } }).catch(function () { return { list: [], total: 0 }; }),
            api('my_comments', { query: { page: 1, per_page: 5 } }).catch(function () { return { list: [], total: 0 }; })
        ]).then(function (results) {
            var postsData = results[0] || {};
            var mediaData = results[1] || {};
            var commentsData = results[2] || {};
            var articlesEl = document.getElementById('dash-articles');
            var mediaEl = document.getElementById('dash-media');
            var commentsEl = document.getElementById('dash-comments');
            if (articlesEl) articlesEl.textContent = String(postsData.total || 0);
            if (mediaEl) mediaEl.textContent = String(mediaData.total || 0);
            if (commentsEl) commentsEl.textContent = String(commentsData.total || 0);
            renderDashActivity(postsData.list || [], mediaData.list || [], commentsData.list || []);
        });
    }

    function renderPanel(name) {
        if (name === 'invite' && !inviteEnabled) {
            name = 'dashboard';
        }
        if (name === 'recharge' && !paymentEnabled) {
            name = 'dashboard';
        }
        var map = {
            dashboard: renderDashboard,
            profile: function () {},
            recharge: renderRecharge,
            invite: renderInvite,
            forum: renderForumPosts,
            media: renderMedia,
            comments: renderComments
        };
        if (map[name]) map[name]();
    }

    document.getElementById('avatar-upload').addEventListener('change', function (e) {
        var file = e.target.files[0];
        if (!file) return;
        var fd = new FormData();
        fd.append('avatar', file);
        api('avatar_upload', { method: 'POST', formData: fd }).then(function () {
            showToast('头像已更新');
            api('profile').then(function (p) {
                refreshSidebar(p);
                updateAvatarPreview(p.photo, p.nickname);
            });
        }).catch(function (err) { showToast(err.message, 'error'); });
    });

    document.getElementById('btn-qq-avatar').addEventListener('click', function () {
        var qq = document.getElementById('pf-qq').value.trim();
        api('avatar_qq', { method: 'POST', data: { qq: qq } }).then(function () {
            showToast('已使用 QQ 头像');
            api('profile').then(function (p) {
                refreshSidebar(p);
                updateAvatarPreview(p.photo, p.nickname);
            });
        }).catch(function (err) { showToast(err.message, 'error'); });
    });

    document.getElementById('profile-form').addEventListener('submit', function (e) {
        e.preventDefault();
        api('profile', {
            method: 'POST',
            data: {
                nickname: document.getElementById('pf-nickname').value.trim(),
                qq: document.getElementById('pf-qq').value.trim(),
                homepage: document.getElementById('pf-homepage').value.trim(),
                bio: document.getElementById('pf-bio').value.trim()
            }
        }).then(function (p) {
            showToast('资料已保存');
            refreshSidebar(p);
        }).catch(function (err) { showToast(err.message, 'error'); });
    });

    function renderRecharge(page) {
        rechargePage = page || 1;
        api('profile').then(function (p) {
            document.getElementById('recharge-balance').textContent = '¥' + p.balance.toFixed(2);
        });
        api('recharge_records', { query: { page: rechargePage, per_page: 10 } }).then(function (data) {
            var records = data.list || [];
            var el = document.getElementById('recharge-records');
            if (!records.length) {
                el.innerHTML = '<div class="empty-state">暂无充值记录</div>';
                renderPagination('recharge-pagination', 0, 1, 10, renderRecharge);
                return;
            }
            el.innerHTML = '<table class="data-table"><thead><tr><th>金额</th><th>时间</th><th>状态</th></tr></thead><tbody>' +
                records.map(function (r) {
                    var statusLabel = r.status_label || (r.status === 'success' ? '成功' : (r.status === 'failed' ? '支付失败' : '待支付'));
                    return '<tr><td>¥' + r.amount + '</td><td>' + esc(r.date) + '</td><td><span class="status-badge ' + rechargeStatusClass(r.status) + '">' + esc(statusLabel) + '</span></td></tr>';
                }).join('') + '</tbody></table>';
            renderPagination('recharge-pagination', data.total || 0, data.page || 1, data.per_page || 10, renderRecharge);
        });
    }

    document.querySelectorAll('.recharge-amount').forEach(function (btn) {
        if (btn.classList.contains('recharge-pay-type')) return;
        btn.addEventListener('click', function () {
            document.querySelectorAll('.recharge-amount:not(.recharge-pay-type)').forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            var custom = document.getElementById('recharge-custom');
            if (custom) custom.value = btn.dataset.amount;
        });
    });

    var selectedPayType = 'alipay';
    document.querySelectorAll('.recharge-pay-type').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.recharge-pay-type').forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            selectedPayType = btn.dataset.payType;
        });
    });

    var rechargeForm = document.getElementById('recharge-form');
    if (rechargeForm) {
    rechargeForm.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!paymentEnabled) {
            showToast('支付功能已关闭', 'error');
            return;
        }
        var amount = parseFloat(document.getElementById('recharge-custom').value);
        if (!amount || amount <= 0) {
            showToast('请输入有效的充值金额', 'error');
            return;
        }
        api('recharge', { method: 'POST', data: { amount: amount, pay_type: selectedPayType } }).then(function (data) {
            var pay = data.payment || {};
            if (!pay.payment_url) {
                showToast('创建支付失败', 'error');
                return;
            }
            showToast('正在跳转支付…');
            if (pay.method === 'qrcode') {
                window.open(pay.payment_url, '_blank');
            } else {
                window.location.href = pay.payment_url;
            }
        }).catch(function (err) { showToast(err.message, 'error'); });
    });
    }

    function renderInvite() {
        if (!inviteEnabled) return;
        api('invite_info').then(function (info) {
            document.getElementById('invite-code').textContent = info.code;
            document.getElementById('invite-link').value = info.link;
            document.getElementById('invite-count').textContent = info.invited_count;
            document.getElementById('invite-reward').textContent = '¥' + info.total_reward.toFixed(2);
            var descEl = document.getElementById('invite-desc');
            if (descEl && info.reward_amount != null) {
                descEl.textContent = '邀请好友注册，双方各得 ¥' + info.reward_amount.toFixed(2) + ' 奖励';
            }
            var el = document.getElementById('invite-records');
            if (!info.rewards.length) {
                el.innerHTML = '<div class="empty-state">暂无奖励记录，分享邀请码给好友吧</div>';
                return;
            }
            el.innerHTML = '<table class="data-table"><thead><tr><th>来源</th><th>金额</th><th>时间</th></tr></thead><tbody>' +
                info.rewards.map(function (r) {
                    return '<tr><td>' + esc(r.from_user) + '</td><td>+¥' + r.amount + '</td><td>' + esc(r.date) + '</td></tr>';
                }).join('') + '</tbody></table>';
        });
    }

    var copyInviteBtn = document.getElementById('btn-copy-invite');
    if (copyInviteBtn) {
        copyInviteBtn.addEventListener('click', function () {
            var input = document.getElementById('invite-link');
            input.select();
            document.execCommand('copy');
            showToast('邀请链接已复制');
        });
    }

    function resolveMediaUrl(url) {
        if (!url) return '';
        if (/^https?:\/\//i.test(url) || url.indexOf('//') === 0) return url;
        var base = SITE || '/';
        if (url.charAt(0) === '/') return base.replace(/\/$/, '') + url;
        return base + url.replace(/^\.\.\//, '').replace(/^\//, '');
    }

    function bindCommentReplyEvents(container) {
        if (!container) return;
        container.querySelectorAll('.btn-comment-reply').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var item = btn.closest('.uc-comment-item');
                if (!item) return;
                container.querySelectorAll('.uc-comment-reply-form').forEach(function (form) {
                    if (form.closest('.uc-comment-item') !== item) {
                        form.classList.add('hidden');
                    }
                });
                var form = item.querySelector('.uc-comment-reply-form');
                if (form) {
                    form.classList.remove('hidden');
                    var ta = form.querySelector('textarea');
                    if (ta) ta.focus();
                }
            });
        });
        container.querySelectorAll('.btn-comment-reply-cancel').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var form = btn.closest('.uc-comment-reply-form');
                if (!form) return;
                form.classList.add('hidden');
                var ta = form.querySelector('textarea');
                if (ta) ta.value = '';
            });
        });
        container.querySelectorAll('.btn-comment-reply-submit').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var form = btn.closest('.uc-comment-reply-form');
                if (!form) return;
                var pid = parseInt(form.dataset.pid, 10);
                var ta = form.querySelector('textarea');
                var content = ta ? ta.value.trim() : '';
                if (!content) {
                    showToast('请输入回复内容', 'error');
                    return;
                }
                btn.disabled = true;
                api('comment_reply', { method: 'POST', data: { pid: pid, content: content } }).then(function (data) {
                    showToast(data.status_label ? ('回复成功：' + data.status_label) : '回复成功');
                    if (ta) ta.value = '';
                    form.classList.add('hidden');
                    renderComments(commentsPage);
                }).catch(function (err) {
                    showToast(err.message, 'error');
                }).then(function () {
                    btn.disabled = false;
                });
            });
        });
    }

    var forumSorts = [];

    function renderForumPosts(page) {
        forumPage = page || 1;
        var el = document.getElementById('forum-posts-list');
        if (!el) return;
        el.innerHTML = '<div class="empty-state">加载中…</div>';
        api('forum_posts', { query: { page: forumPage, per_page: forumPerPage } }).then(function (data) {
            var posts = data.list || [];
            var total = data.total || 0;
            var curPage = data.page || forumPage;
            var perPage = data.per_page || forumPerPage;
            if (!posts.length) {
                el.innerHTML = '<div class="empty-state">暂无文章，点击「发布文章」开始</div>';
                renderPagination('forum-pagination', 0, 1, perPage, renderForumPosts);
                return;
            }
            el.innerHTML = posts.map(function (p) {
                var cover = p.cover
                    ? '<img src="' + esc(p.cover) + '" alt="" loading="lazy">'
                    : '<span class="uc-forum-cover-ph">' + esc((p.title || '文').charAt(0)) + '</span>';
                var titleText = p.title || '无标题';
                var title = p.url
                    ? '<a href="' + esc(p.url) + '" target="_blank" rel="noopener" class="forum-post-link" title="' + esc(titleText) + '">' + esc(titleText) + '</a>'
                    : '<span class="forum-post-link" title="' + esc(titleText) + '">' + esc(titleText) + '</span>';
                var dateShort = (p.date || '').slice(0, 10);
                var viewBtn = p.url
                    ? '<a class="uc-forum-act" href="' + esc(p.url) + '" target="_blank" rel="noopener">查看</a>'
                    : '';
                return '<article class="uc-forum-item">' +
                    '<div class="uc-forum-cover">' + cover + '</div>' +
                    '<div class="uc-forum-body">' +
                    '<div class="uc-forum-top">' +
                    '<span class="status-badge ' + esc(p.status) + '">' + esc(p.status_label) + '</span>' +
                    (p.sort_name ? '<span class="uc-forum-sort" title="' + esc(p.sort_name) + '">' + esc(p.sort_name) + '</span>' : '') +
                    '</div>' +
                    '<h3 class="uc-forum-title">' + title + '</h3>' +
                    '<div class="uc-forum-foot">' +
                    '<div class="uc-forum-meta" title="' + esc(p.date || '') + '"><span>' + esc(dateShort) + '</span><span>' + esc(String(p.views || 0)) + ' 阅读</span></div>' +
                    '<div class="uc-forum-actions">' +
                    viewBtn +
                    '<button type="button" class="uc-forum-act btn-forum-edit" data-id="' + p.id + '">编辑</button>' +
                    '<button type="button" class="uc-forum-act is-danger btn-forum-del" data-id="' + p.id + '">删除</button>' +
                    '</div></div></div></article>';
            }).join('');
            el.querySelectorAll('.btn-forum-edit').forEach(function (btn) {
                btn.addEventListener('click', function () { openForumModal(parseInt(btn.dataset.id, 10)); });
            });
            el.querySelectorAll('.btn-forum-del').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (!confirm('确定删除该文章？')) return;
                    api('forum_delete', { method: 'POST', data: { id: btn.dataset.id } }).then(function () {
                        showToast('已删除');
                        var nextPage = forumPage;
                        if (posts.length <= 1 && forumPage > 1) nextPage = forumPage - 1;
                        renderForumPosts(nextPage);
                    }).catch(function (err) { showToast(err.message, 'error'); });
                });
            });
            renderPagination('forum-pagination', total, curPage, perPage, renderForumPosts);
        }).catch(function (err) {
            el.innerHTML = '<div class="empty-state">' + esc(err.message || '加载失败') + '</div>';
            showToast(err.message, 'error');
        });
    }

    document.getElementById('btn-new-forum').addEventListener('click', function () { openForumModal(0); });
    document.getElementById('modal-forum-cancel').addEventListener('click', function () { closeModal('modal-forum'); });

    function loadForumSorts(cb) {
        if (forumSorts.length) { cb(); return; }
        api('forum_sorts').then(function (list) {
            forumSorts = list || [];
            cb();
        }).catch(function (err) {
            showToast(err.message, 'error');
            cb();
        });
    }

    function openForumModal(id) {
        loadForumSorts(function () {
            if (!forumSorts.length) {
                showToast('暂无可用分类，请先在后台创建文章分类', 'error');
                return;
            }
            var sel = document.getElementById('forum-sort');
            sel.innerHTML = forumSorts.map(function (s) {
                return '<option value="' + s.id + '">' + esc(s.name) + '</option>';
            }).join('');
            if (!forumEditor) {
                if (typeof MarkdownEditor === 'undefined') {
                    showToast('编辑器加载失败，请刷新页面重试', 'error');
                    return;
                }
                forumEditor = MarkdownEditor.render(document.getElementById('forum-content-editor'), '');
            }
            document.getElementById('modal-forum-title').textContent = id ? '编辑文章' : '发布文章';
            document.getElementById('forum-id').value = id || '';
            if (id) {
                api('forum_get', { query: { id: id } }).then(function (post) {
                    document.getElementById('forum-title').value = post.title;
                    document.getElementById('forum-sort').value = post.sort_id;
                    document.getElementById('forum-alias').value = post.alias || '';
                    document.getElementById('forum-password').value = post.password || '';
                    document.getElementById('forum-tags').value = post.tags || '';
                    document.getElementById('forum-allow-remark').checked = post.allow_remark !== 'n';
                    document.getElementById('forum-submit-type').value = post.submit_type || 'pending';
                    document.getElementById('forum-cover-path').value = post.cover_path || '';
                    document.getElementById('forum-cover-preview').innerHTML = post.cover
                        ? '<img src="' + esc(post.cover) + '" alt="">' : '';
                    forumEditor.setValue(post.content);
                    openModal('modal-forum');
                });
            } else {
                document.getElementById('forum-form').reset();
                document.getElementById('forum-cover-path').value = '';
                document.getElementById('forum-cover-preview').innerHTML = '';
                document.getElementById('forum-allow-remark').checked = true;
                forumEditor.setValue('');
                openModal('modal-forum');
            }
        });
    }

    document.getElementById('forum-cover').addEventListener('change', function (e) {
        var file = e.target.files[0];
        if (!file) return;
        var reader = new FileReader();
        reader.onload = function () {
            document.getElementById('forum-cover-preview').innerHTML = '<img src="' + reader.result + '" alt="">';
        };
        reader.readAsDataURL(file);
    });

    document.getElementById('forum-form').addEventListener('submit', function (e) {
        e.preventDefault();
        var fd = new FormData();
        fd.append('id', document.getElementById('forum-id').value);
        fd.append('title', document.getElementById('forum-title').value.trim());
        fd.append('content', forumEditor ? forumEditor.getValue() : '');
        fd.append('sort_id', document.getElementById('forum-sort').value);
        fd.append('alias', document.getElementById('forum-alias').value.trim());
        fd.append('password', document.getElementById('forum-password').value.trim());
        fd.append('tags', document.getElementById('forum-tags').value.trim());
        fd.append('allow_remark', document.getElementById('forum-allow-remark').checked ? 'y' : 'n');
        fd.append('submit_type', document.getElementById('forum-submit-type').value);
        fd.append('cover_path', document.getElementById('forum-cover-path').value);
        var coverFile = document.getElementById('forum-cover').files[0];
        if (coverFile) fd.append('cover', coverFile);
        api('forum_save', { method: 'POST', formData: fd }).then(function (data) {
            closeModal('modal-forum');
            showToast(data.status_label ? ('已保存：' + data.status_label) : '文章已保存');
            renderForumPosts(forumPage || 1);
        }).catch(function (err) { showToast(err.message, 'error'); });
    });

    function copyText(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text);
        }
        return new Promise(function (resolve, reject) {
            var ta = document.createElement('textarea');
            ta.value = text;
            document.body.appendChild(ta);
            ta.select();
            try {
                document.execCommand('copy');
                resolve();
            } catch (err) {
                reject(err);
            }
            ta.remove();
        });
    }

    function mediaExt(name) {
        var m = String(name || '').match(/\.([a-z0-9]+)$/i);
        return m ? m[1].toLowerCase() : '';
    }

    function mediaTypeLabel(item) {
        if (item.is_image) return '图片';
        var ext = mediaExt(item.name);
        if (['mp4', 'webm', 'mov', 'avi'].indexOf(ext) >= 0) return '视频';
        if (['mp3', 'wav', 'flac', 'aac'].indexOf(ext) >= 0) return '音频';
        if (['zip', 'rar', '7z', 'tar', 'gz'].indexOf(ext) >= 0) return '压缩包';
        if (['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'].indexOf(ext) >= 0) return '文档';
        return ext ? ext.toUpperCase() : '文件';
    }

    function mediaFileIcon(item) {
        var ext = mediaExt(item.name);
        if (['mp4', 'webm', 'mov'].indexOf(ext) >= 0) return '▶';
        if (['mp3', 'wav', 'flac'].indexOf(ext) >= 0) return '♪';
        if (['zip', 'rar', '7z'].indexOf(ext) >= 0) return '▣';
        if (ext === 'pdf') return 'PDF';
        return (ext || 'FILE').toUpperCase().slice(0, 4);
    }

    var mediaFilter = 'all';
    var mediaUploading = false;

    function setMediaFilter(next, btn) {
        next = next || 'all';
        if (next !== 'all' && next !== 'image' && next !== 'file') next = 'all';
        var same = next === mediaFilter;
        mediaFilter = next;
        var box = document.getElementById('media-filter');
        if (box) {
            box.querySelectorAll('.media-filter-btn').forEach(function (b) {
                var on = btn ? (b === btn) : ((b.getAttribute('data-filter') || 'all') === mediaFilter);
                b.classList.toggle('active', on);
                b.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
        }
        if (!same) renderMedia(1);
    }

    function renderMediaItem(item) {
        var fileUrl = resolveMediaUrl(item.url);
        var typeLabel = mediaTypeLabel(item);
        var preview = item.is_image
            ? '<img src="' + esc(fileUrl) + '" alt="" loading="lazy">'
            : '<span class="uc-media-file-icon" data-ext="' + esc(mediaExt(item.name)) + '">' + esc(mediaFileIcon(item)) + '</span>';
        var dateShort = (item.date || '').replace(/:\d{2}$/, '');
        return '<article class="uc-media-item' + (item.is_image ? ' is-image' : ' is-file') + '" data-id="' + item.id + '">' +
            '<div class="uc-media-preview">' + preview +
            '<span class="uc-media-type">' + esc(typeLabel) + '</span></div>' +
            '<div class="uc-media-meta">' +
            '<div class="uc-media-name" title="' + esc(item.name) + '">' + esc(item.name) + '</div>' +
            '<div class="uc-media-info"><span>' + esc(item.size || '—') + '</span><span>' + esc(dateShort) + '</span></div>' +
            '</div>' +
            '<div class="uc-media-actions">' +
            '<a href="' + esc(fileUrl) + '" class="uc-media-act" target="_blank" rel="noopener">打开</a>' +
            '<button type="button" class="uc-media-act btn-copy-url" data-url="' + esc(fileUrl) + '">链接</button>' +
            (item.is_image
                ? '<button type="button" class="uc-media-act btn-copy-md" data-url="' + esc(fileUrl) + '" data-name="' + esc(item.name) + '">MD</button>'
                : '') +
            '<button type="button" class="uc-media-act is-danger btn-media-del" data-id="' + item.id + '">删除</button>' +
            '</div></article>';
    }

    function bindMediaActions(el) {
        el.querySelectorAll('.btn-copy-url').forEach(function (btn) {
            btn.addEventListener('click', function () {
                copyText(btn.dataset.url).then(function () {
                    showToast('链接已复制');
                }).catch(function () {
                    showToast('复制失败', 'error');
                });
            });
        });
        el.querySelectorAll('.btn-copy-md').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var md = '![' + (btn.dataset.name || 'image') + '](' + btn.dataset.url + ')';
                copyText(md).then(function () {
                    showToast('Markdown 图片语法已复制');
                }).catch(function () {
                    showToast('复制失败', 'error');
                });
            });
        });
        el.querySelectorAll('.btn-media-del').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (!confirm('确定删除该附件？')) return;
                api('media_delete', { method: 'POST', data: { id: btn.dataset.id } }).then(function () {
                    showToast('已删除');
                    var item = btn.closest('.uc-media-item');
                    var remain = item && item.parentElement
                        ? item.parentElement.querySelectorAll('.uc-media-item').length - 1
                        : 0;
                    var nextPage = mediaPage;
                    if (remain <= 0 && mediaPage > 1) nextPage = mediaPage - 1;
                    renderMedia(nextPage);
                }).catch(function (err) { showToast(err.message, 'error'); });
            });
        });
    }

    function renderMediaSummary(total) {
        var box = document.getElementById('media-summary');
        if (!box) return;
        if (!total) {
            box.hidden = true;
            box.textContent = '';
            return;
        }
        var filterText = mediaFilter === 'image' ? '图片' : (mediaFilter === 'file' ? '文件' : '附件');
        box.hidden = false;
        box.innerHTML = '共 <strong>' + total + '</strong> 个' + filterText;
    }

    function renderMedia(page) {
        mediaPage = page || 1;
        var el = document.getElementById('media-grid');
        if (!el) return;
        var kw = ((document.getElementById('media-search') || {}).value || '').trim();
        el.innerHTML = '<div class="empty-state media-empty">加载中…</div>';
        api('my_media', {
            query: {
                page: mediaPage,
                per_page: mediaPerPage,
                keyword: kw,
                media_type: mediaFilter || 'all',
                type: mediaFilter || 'all'
            }
        }).then(function (data) {
            var items = data.list || [];
            var total = data.total || 0;
            renderMediaSummary(total);
            if (!items.length) {
                var tip = kw
                    ? '未找到匹配的附件'
                    : (mediaFilter === 'image'
                        ? '暂无图片'
                        : (mediaFilter === 'file' ? '暂无非图片文件' : '暂无附件，拖拽或点击上传开始'));
                el.innerHTML = '<div class="empty-state media-empty">' + tip + '</div>';
                renderPagination('media-pagination', total, data.page || 1, data.per_page || mediaPerPage, renderMedia);
                return;
            }
            el.innerHTML = items.map(renderMediaItem).join('');
            bindMediaActions(el);
            renderPagination('media-pagination', total, data.page || 1, data.per_page || mediaPerPage, renderMedia);
        }).catch(function (err) {
            el.innerHTML = '<div class="empty-state media-empty">' + esc(err.message || '加载失败') + '</div>';
            showToast(err.message || '附件加载失败', 'error');
        });
    }

    function uploadMediaFiles(files) {
        if (!files.length || mediaUploading) return;
        mediaUploading = true;
        var list = Array.prototype.slice.call(files);
        var idx = 0;
        var ok = 0;
        var fail = 0;

        function next() {
            if (idx >= list.length) {
                mediaUploading = false;
                if (ok) showToast('成功上传 ' + ok + ' 个文件' + (fail ? '，失败 ' + fail + ' 个' : ''));
                else if (fail) showToast('上传失败', 'error');
                renderMedia(1);
                return;
            }
            var fd = new FormData();
            fd.append('file', list[idx]);
            idx += 1;
            api('media_upload', { method: 'POST', formData: fd }).then(function () {
                ok += 1;
                next();
            }).catch(function () {
                fail += 1;
                next();
            });
        }
        next();
    }

    var mediaSearchEl = document.getElementById('media-search');
    if (mediaSearchEl) {
        mediaSearchEl.addEventListener('input', function () {
            clearTimeout(mediaSearchTimer);
            mediaSearchTimer = setTimeout(function () { renderMedia(1); }, 300);
        });
    }

    var mediaFilterEl = document.getElementById('media-filter');
    if (mediaFilterEl) {
        mediaFilterEl.querySelectorAll('.media-filter-btn').forEach(function (btn) {
            btn.setAttribute('aria-pressed', btn.classList.contains('active') ? 'true' : 'false');
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                setMediaFilter(btn.getAttribute('data-filter') || 'all', btn);
            });
        });
    }

    var mediaUploadInput = document.getElementById('media-upload-input');
    var mediaUploadBtn = document.getElementById('btn-media-upload');
    if (mediaUploadBtn && mediaUploadInput) {
        mediaUploadBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            mediaUploadInput.click();
        });
    }
    if (mediaUploadInput) {
        mediaUploadInput.addEventListener('change', function (e) {
            var files = e.target.files;
            if (!files || !files.length) return;
            uploadMediaFiles(files);
            e.target.value = '';
        });
    }

    var dropzone = document.getElementById('media-dropzone');
    if (dropzone) {
        ['dragenter', 'dragover'].forEach(function (ev) {
            dropzone.addEventListener(ev, function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.add('is-dragover');
            });
        });
        ['dragleave', 'drop'].forEach(function (ev) {
            dropzone.addEventListener(ev, function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.remove('is-dragover');
            });
        });
        dropzone.addEventListener('drop', function (e) {
            var files = e.dataTransfer && e.dataTransfer.files;
            if (files && files.length) uploadMediaFiles(files);
        });
        dropzone.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (mediaUploadInput) mediaUploadInput.click();
        });
    }

    function renderCommentParentQuote(c) {
        if (!c.is_reply || !c.parent) return '';
        var p = c.parent;
        var author = esc(p.author || '某用户');
        var viewLink = p.url
            ? '<a class="uc-comment-parent-link" href="' + esc(p.url) + '" target="_blank" rel="noopener">查看原评论</a>'
            : '';
        return '<blockquote class="uc-comment-parent">' +
            '<span class="uc-comment-parent-label">回复 @' + author + '</span>' +
            '<p class="uc-comment-parent-body">' + esc(p.content) + '</p>' +
            (viewLink ? '<div class="uc-comment-parent-foot">' + viewLink + '</div>' : '') +
            '</blockquote>';
    }

    function renderComments(page) {
        commentsPage = page || 1;
        var el = document.getElementById('comments-list');
        if (!el) return;
        el.innerHTML = '<div class="empty-state">加载中…</div>';
        api('my_comments', { query: { page: commentsPage, per_page: commentsPerPage } }).then(function (data) {
            var items = data.list || [];
            if (!items.length) {
                el.innerHTML = '<div class="empty-state">暂无评论，去文章页参与讨论吧</div>';
                renderPagination('comments-pagination', 0, 1, commentsPerPage, renderComments);
                return;
            }
            el.innerHTML = items.map(function (c) {
                var articleTitle = c.article || '未知文章';
                var articleLink = c.article_url
                    ? '<a href="' + esc(c.article_url) + '" target="_blank" rel="noopener" title="' + esc(articleTitle) + '">' + esc(articleTitle) + '</a>'
                    : '<span title="' + esc(articleTitle) + '">' + esc(articleTitle) + '</span>';
                var statusClass = c.status === 'hidden' ? 'failed' : 'published';
                var dateShort = (c.date || '').slice(0, 16);
                return '<article class="uc-comment-item' + (c.is_reply ? ' is-reply' : '') + '" data-cid="' + esc(String(c.id)) + '">' +
                    '<div class="uc-comment-head">' +
                    '<div class="uc-comment-head-main">' +
                    '<span class="uc-comment-label">来自文章</span>' +
                    '<div class="uc-comment-article">' + articleLink + '</div>' +
                    '</div>' +
                    '<div class="uc-comment-head-side">' +
                    (c.is_reply ? '<span class="uc-comment-tag">回复</span>' : '<span class="uc-comment-tag is-root">评论</span>') +
                    '<span class="status-badge ' + statusClass + '">' + esc(c.status_label) + '</span>' +
                    '</div></div>' +
                    renderCommentParentQuote(c) +
                    '<div class="uc-comment-body">' + esc(c.content) + '</div>' +
                    '<div class="uc-comment-foot">' +
                    '<time class="uc-comment-date" title="' + esc(c.date || '') + '">' + esc(dateShort) + '</time>' +
                    '<div class="uc-comment-actions">' +
                    (c.comment_url ? '<a class="uc-comment-act" href="' + esc(c.comment_url) + '" target="_blank" rel="noopener">查看</a>' : '') +
                    '<button type="button" class="uc-comment-act btn-comment-reply" data-pid="' + esc(String(c.id)) + '">回复</button>' +
                    '</div></div>' +
                    '<div class="uc-comment-reply-form hidden" data-pid="' + esc(String(c.id)) + '">' +
                    '<textarea class="form-textarea" rows="3" placeholder="写下你的回复…"></textarea>' +
                    '<div class="uc-comment-reply-actions">' +
                    '<button type="button" class="btn btn-primary btn-sm btn-comment-reply-submit">发送回复</button>' +
                    '<button type="button" class="btn btn-ghost btn-sm btn-comment-reply-cancel">取消</button>' +
                    '</div></div>' +
                    '</article>';
            }).join('');
            bindCommentReplyEvents(el);
            renderPagination('comments-pagination', data.total || 0, data.page || 1, data.per_page || commentsPerPage, renderComments);
        }).catch(function (err) {
            el.innerHTML = '<div class="empty-state">' + esc(err.message || '加载失败') + '</div>';
            showToast(err.message, 'error');
        });
    }

    var tab = new URLSearchParams(location.search).get('tab') || 'dashboard';
    if (tab === 'invite' && !inviteEnabled) tab = 'dashboard';
    if (tab === 'recharge' && !paymentEnabled) tab = 'dashboard';
    var link = document.querySelector('.uc-nav a[data-panel="' + tab + '"]');
    if (link) {
        switchPanel(tab);
    } else {
        renderDashboard();
    }
})();
