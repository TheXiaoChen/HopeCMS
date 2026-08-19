<?php
defined('HOPE_ROOT') || exit('access denied!');

$role_label = '';
if (!empty($profile['role'])) {
    $role_map = ['admin' => '管理员', 'writer' => '创作者', 'visitor' => '访客'];
    $role_label = $role_map[$profile['role']] ?? $profile['role'];
}

$ico = static function ($name) {
    $icons = [
        'dashboard' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>',
        'user' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>',
        'wallet' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="14" rx="2"/><path d="M2 10h20"/><path d="M16 14h2"/></svg>',
        'gift' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="8" width="18" height="13" rx="2"/><path d="M12 8v13M3 12h18M12 8c-2-3-5-3.5-5-1.5S9 9 12 8c3-1 5-2.5 5-1.5S14 5 12 8z"/></svg>',
        'edit' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>',
        'folder' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z"/></svg>',
        'chat' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/></svg>',
        'logout' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>',
    ];
    return $icons[$name] ?? '';
};
?>
<section class="uc-band">
<div class="uc-shell" id="user-center"
     data-api="<?= htmlspecialchars($api_url) ?>"
     data-token="<?= htmlspecialchars($token) ?>"
     data-auth-api="<?= htmlspecialchars($auth_api) ?>"
     data-site="<?= htmlspecialchars(SITE_URL) ?>"
     data-invite-enabled="<?= !empty($invite_enabled) ? '1' : '0' ?>"
     data-payment-enabled="<?= !empty($payment_enabled) ? '1' : '0' ?>">

    <button type="button" class="uc-mobile-toggle" id="uc-mobile-toggle" aria-label="打开菜单">
        <span></span><span></span><span></span>
    </button>

    <aside class="uc-sidebar" id="uc-sidebar">
        <div class="uc-profile-card">
            <div class="avatar-lg<?= $avatar ? ' has-img' : '' ?>" id="sidebar-avatar">
                <?php if ($avatar): ?>
                <img src="<?= htmlspecialchars($avatar) ?>" alt="">
                <?php else: ?>
                <?= htmlspecialchars(mb_substr($name, 0, 1)) ?>
                <?php endif; ?>
            </div>
            <div class="uc-profile-meta">
                <div class="name" id="sidebar-name"><?= htmlspecialchars($name) ?></div>
                <?php if ($role_label): ?>
                <span class="uc-role-badge" id="sidebar-role"><?= htmlspecialchars($role_label) ?></span>
                <?php endif; ?>
                <div class="email" id="sidebar-email"><?= htmlspecialchars($profile['email'] ?? '') ?></div>
            </div>
            <div class="uc-balance-card">
                <span class="label">账户余额</span>
                <span class="value" id="sidebar-balance">¥<?= number_format($profile['balance'], 2) ?></span>
            </div>
        </div>

        <nav class="uc-nav">
            <div class="uc-nav-group">
                <div class="uc-nav-title">概览</div>
                <a href="#dashboard" class="active" data-panel="dashboard">
                    <span class="uc-nav-icon" aria-hidden="true"><?= $ico('dashboard') ?></span> 控制台
                </a>
            </div>
            <div class="uc-nav-group">
                <div class="uc-nav-title">账户</div>
                <a href="#profile" data-panel="profile"><span class="uc-nav-icon" aria-hidden="true"><?= $ico('user') ?></span> 个人资料</a>
                <?php if (!empty($payment_enabled)): ?>
                <a href="#recharge" data-panel="recharge"><span class="uc-nav-icon" aria-hidden="true"><?= $ico('wallet') ?></span> 账户充值</a>
                <?php endif; ?>
                <?php if (!empty($invite_enabled)): ?>
                <a href="#invite" data-panel="invite"><span class="uc-nav-icon" aria-hidden="true"><?= $ico('gift') ?></span> 邀请奖励</a>
                <?php endif; ?>
            </div>
            <div class="uc-nav-group">
                <div class="uc-nav-title">内容</div>
                <a href="#forum" data-panel="forum"><span class="uc-nav-icon" aria-hidden="true"><?= $ico('edit') ?></span> 我的文章</a>
                <a href="#media" data-panel="media"><span class="uc-nav-icon" aria-hidden="true"><?= $ico('folder') ?></span> 我的附件</a>
                <a href="#comments" data-panel="comments"><span class="uc-nav-icon" aria-hidden="true"><?= $ico('chat') ?></span> 我的评论</a>
            </div>
            <?php doAction('user_menu'); ?>
            <div class="uc-nav-group uc-nav-footer">
                <a href="#" class="logout-link" id="btn-logout"><span class="uc-nav-icon" aria-hidden="true"><?= $ico('logout') ?></span> 退出登录</a>
            </div>
        </nav>
    </aside>

    <main class="uc-main">
        <section class="uc-panel active" id="panel-dashboard">
            <div class="uc-panel-head">
                <h2>控制台</h2>
                <p class="panel-desc">欢迎回来，<?= htmlspecialchars($name) ?></p>
            </div>
            <div class="uc-stat-grid">
                <div class="uc-stat-card">
                    <span class="uc-stat-label">账户余额</span>
                    <strong class="uc-stat-value" id="dash-balance">¥<?= number_format($profile['balance'], 2) ?></strong>
                </div>
                <div class="uc-stat-card">
                    <span class="uc-stat-label">我的文章</span>
                    <strong class="uc-stat-value" id="dash-articles">—</strong>
                </div>
                <div class="uc-stat-card">
                    <span class="uc-stat-label">我的附件</span>
                    <strong class="uc-stat-value" id="dash-media">—</strong>
                </div>
                <div class="uc-stat-card">
                    <span class="uc-stat-label">我的评论</span>
                    <strong class="uc-stat-value" id="dash-comments">—</strong>
                </div>
            </div>
            <div class="uc-quick-grid">
                <?php if (!empty($payment_enabled)): ?>
                <button type="button" class="uc-quick-card" data-goto="recharge">
                    <strong>充值余额</strong><span>支付宝 / 微信</span>
                </button>
                <?php endif; ?>
                <button type="button" class="uc-quick-card" data-goto="forum">
                    <strong>写文章</strong><span>发布与编辑</span>
                </button>
                <button type="button" class="uc-quick-card" data-goto="media">
                    <strong>上传附件</strong><span>图片与文件</span>
                </button>
                <button type="button" class="uc-quick-card" data-goto="profile">
                    <strong>编辑资料</strong><span>头像与简介</span>
                </button>
            </div>
            <div class="uc-recent-block">
                <div class="uc-recent-head">
                    <h3>最新动态</h3>
                    <span class="uc-recent-hint">文章 · 附件 · 评论</span>
                </div>
                <div id="dash-activity-list" class="uc-activity-list">加载中…</div>
            </div>
        </section>

        <section class="uc-panel" id="panel-profile">
            <div class="uc-panel-head">
                <h2>个人资料</h2>
                <p class="panel-desc">完善个人信息，展示在个人主页</p>
            </div>
            <div class="avatar-upload-area">
                <div class="avatar-preview-lg" id="avatar-preview">
                    <?php if ($avatar): ?>
                    <img src="<?= htmlspecialchars($avatar) ?>" alt="">
                    <?php else: ?>
                    <?= htmlspecialchars(mb_substr($name, 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <div class="avatar-actions">
                    <label class="btn btn-ghost btn-sm">上传头像<input type="file" id="avatar-upload" accept="image/*" hidden></label>
                    <button type="button" class="btn btn-ghost btn-sm" id="btn-qq-avatar">使用 QQ 头像</button>
                </div>
            </div>
            <form id="profile-form" novalidate>
                <div class="form-row">
                    <div class="form-group"><label>昵称</label><input type="text" id="pf-nickname" class="form-input" value="<?= htmlspecialchars($profile['nickname']) ?>"></div>
                    <div class="form-group"><label>QQ</label><input type="text" id="pf-qq" class="form-input" placeholder="用于 QQ 头像" value="<?= htmlspecialchars($profile['qq']) ?>"></div>
                </div>
                <div class="form-group"><label>个人主页</label><input type="text" id="pf-homepage" class="form-input" placeholder="https:// 或留空" value="<?= htmlspecialchars($profile['homepage']) ?>"></div>
                <div class="form-group"><label>个人介绍</label><textarea id="pf-bio" class="form-textarea"><?= htmlspecialchars($profile['bio']) ?></textarea></div>
                <div class="form-row">
                    <div class="form-group"><label>账户余额</label><input type="text" id="pf-balance" class="form-input" readonly value="¥<?= number_format($profile['balance'], 2) ?>"></div>
                    <div class="form-group"><label>API 密钥</label><input type="text" id="pf-api-password" class="form-input" readonly value="<?= htmlspecialchars($profile['api_password']) ?>"><small class="form-hint">系统随机生成，不可修改</small></div>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">保存资料</button>
            </form>
        </section>

        <?php if (!empty($payment_enabled)): ?>
        <section class="uc-panel" id="panel-recharge">
            <div class="uc-panel-head">
                <h2>账户充值</h2>
                <p class="panel-desc">选择充值金额，支付后即时到账</p>
            </div>
            <div class="recharge-card">
                <div class="recharge-balance-display">当前余额：<strong id="recharge-balance">¥<?= number_format($profile['balance'], 2) ?></strong></div>
                <form id="recharge-form">
                    <div class="recharge-options">
                        <button type="button" class="recharge-amount" data-amount="10">¥10</button>
                        <button type="button" class="recharge-amount" data-amount="50">¥50</button>
                        <button type="button" class="recharge-amount active" data-amount="100">¥100</button>
                        <button type="button" class="recharge-amount" data-amount="200">¥200</button>
                        <button type="button" class="recharge-amount" data-amount="500">¥500</button>
                    </div>
                    <div class="form-group"><label>自定义金额</label><input type="number" id="recharge-custom" class="form-input" value="100" min="1" step="1"></div>
                    <div class="form-group">
                        <label>支付方式</label>
                        <div class="recharge-options">
                            <button type="button" class="recharge-amount recharge-pay-type active" data-pay-type="alipay">支付宝</button>
                            <button type="button" class="recharge-amount recharge-pay-type" data-pay-type="wechat">微信支付</button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">立即充值</button>
                </form>
            </div>
            <h3 class="uc-subtitle">充值记录</h3>
            <div id="recharge-records"></div>
            <div id="recharge-pagination" class="uc-pagination"></div>
        </section>
        <?php endif; ?>

        <?php if (!empty($invite_enabled)): ?>
        <section class="uc-panel" id="panel-invite">
            <div class="uc-panel-head">
                <h2>邀请奖励</h2>
                <p class="panel-desc" id="invite-desc">邀请好友注册，双方各得 ¥<?= number_format($invite_reward_amount ?? 5, 2) ?> 奖励</p>
            </div>
            <div class="invite-card">
                <div class="invite-stat-grid">
                    <div class="invite-stat"><span class="num" id="invite-count">0</span><span class="label">已邀请</span></div>
                    <div class="invite-stat"><span class="num" id="invite-reward">¥0</span><span class="label">累计奖励</span></div>
                    <div class="invite-stat"><span class="num" id="invite-code">----</span><span class="label">邀请码</span></div>
                </div>
                <div class="form-group">
                    <label>邀请链接</label>
                    <div class="input-with-btn">
                        <input type="text" id="invite-link" class="form-input" readonly>
                        <button type="button" class="btn btn-primary btn-sm" id="btn-copy-invite">复制</button>
                    </div>
                </div>
            </div>
            <h3 class="uc-subtitle">奖励记录</h3>
            <div id="invite-records"></div>
        </section>
        <?php endif; ?>

        <section class="uc-panel" id="panel-forum">
            <div class="uc-panel-head uc-panel-head--split">
                <div>
                    <h2>我的文章</h2>
                    <p class="panel-desc">发布、编辑文章，提交后等待管理员审核</p>
                </div>
                <div class="panel-toolbar">
                    <button class="btn btn-primary btn-sm" id="btn-new-forum">+ 发布文章</button>
                </div>
            </div>
            <div id="forum-posts-list" class="uc-forum-list"></div>
            <div id="forum-pagination" class="uc-pagination"></div>
        </section>

        <section class="uc-panel" id="panel-media">
            <div class="uc-panel-head uc-panel-head--split">
                <div>
                    <h2>我的附件</h2>
                    <p class="panel-desc">管理已上传的图片与文件，可复制链接插入文章</p>
                </div>
                <div class="panel-toolbar">
                    <div class="media-filter" id="media-filter" role="group" aria-label="附件类型筛选">
                        <button type="button" class="media-filter-btn active" data-filter="all" aria-pressed="true">全部</button>
                        <button type="button" class="media-filter-btn" data-filter="image" aria-pressed="false">图片</button>
                        <button type="button" class="media-filter-btn" data-filter="file" aria-pressed="false">文件</button>
                    </div>
                    <input type="search" id="media-search" class="form-input search-input" placeholder="搜索文件名…">
                    <button type="button" class="btn btn-primary btn-sm" id="btn-media-upload">上传附件</button>
                    <input type="file" id="media-upload-input" multiple hidden>
                </div>
            </div>
            <div class="media-dropzone" id="media-dropzone">
                <div class="media-dropzone-inner">
                    <strong>拖拽文件到此处上传</strong>
                    <span>支持多选；也可点击右上角「上传附件」</span>
                </div>
            </div>
            <div class="media-summary" id="media-summary" hidden></div>
            <div id="media-grid" class="uc-media-grid"></div>
            <div id="media-pagination" class="uc-pagination"></div>
        </section>

        <section class="uc-panel" id="panel-comments">
            <div class="uc-panel-head">
                <h2>我的评论</h2>
                <p class="panel-desc">查看你在站点内发表的全部评论</p>
            </div>
            <div id="comments-list" class="uc-comment-list"></div>
            <div id="comments-pagination" class="uc-pagination"></div>
        </section>
    </main>
</div>
</section>

<?php include THEME_PATH . 'user/modals.php'; ?>
<script src="<?= THEME_URL ?>js/markdown.js"></script>
<script src="<?= THEME_URL ?>js/auth_api.js"></script>
<script src="<?= THEME_URL ?>js/user_center.js"></script>
