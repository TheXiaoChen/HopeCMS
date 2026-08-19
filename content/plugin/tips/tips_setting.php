<?php
defined('HOPE_ROOT') || exit('access denied!');

require_once HOPE_ROOT . 'content/plugin/tips/tips_lib.php';

function plugin_setting_view() {
    $preview = tips_random();
    $defaults = tips_default_list();
    ?>
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h4 mb-1 text-gray-800">小贴士</h1>
            <p class="text-sm text-secondary mb-0">插件启用后，后台首页顶部将自动随机展示一句使用提示</p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header pb-0">
                    <h6 class="mb-0">插件说明</h6>
                </div>
                <div class="card-body">
                    <p class="text-sm text-muted mb-3">启用本插件即可在后台首页展示提示。提示内容来自内置列表，每次随机显示一条。</p>
                    <?php if (tips_is_default_admin_entry()): ?>
                    <div class="alert alert-danger text-sm mb-3" role="alert">
                        <strong>当前检测到默认后台入口 admin.php</strong>，后台首页将显示红色安全警告，请尽快重命名该文件。
                    </div>
                    <?php else: ?>
                    <p class="text-sm text-success mb-3">✓ 后台入口已自定义为 <code><?= htmlspecialchars(SELF) ?></code>，不会显示默认入口警告。</p>
                    <?php endif; ?>
                    <p class="text-sm text-muted mb-0">另请确认根目录 <code>install.php</code> 已在安装完成后删除。</p>
                </div>
            </div>
            <div class="card shadow mb-4">
                <div class="card-header pb-0">
                    <h6 class="mb-0">内置提示（<?= count($defaults) ?> 条）</h6>
                </div>
                <div class="card-body">
                    <ol class="text-sm mb-0" style="line-height:1.8;padding-left:20px;">
                        <?php foreach ($defaults as $item): ?>
                        <li><?= htmlspecialchars($item) ?></li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header pb-0">
                    <h6 class="mb-0">预览效果</h6>
                </div>
                <div class="card-body">
                    <link rel="stylesheet" href="<?= htmlspecialchars(tips_asset_url('tips.css?v=4.2')) ?>">
                    <?php if (tips_is_default_admin_entry()): ?>
                    <div class="tips-banner tips-banner--danger" role="alert">
                        <span class="tips-banner-icon" aria-hidden="true">⚠️</span>
                        <div class="tips-banner-text">
                            <strong>安全警告：</strong>检测到当前仍使用默认后台入口 <code>admin.php</code>，请立即重命名。
                        </div>
                    </div>
                    <hr>
                    <?php endif; ?>
                    <div class="tips-banner" role="note">
                        <span class="tips-banner-icon" aria-hidden="true">💡</span>
                        <span class="tips-banner-text"><?= htmlspecialchars($preview) ?></span>
                    </div>
                    <hr>
                    <p class="text-sm text-muted mb-2">本插件遵循 Hope CMS 插件规范，包含入口、回调、设置页与 lib 分离，可作为开发参考。</p>
                    <a class="text-sm" href="<?= htmlspecialchars(tips_page_url()) ?>" target="_blank" rel="noopener">查看前台示例页</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        $("#menu_category_ext").addClass('active');
    </script>
    <?php
}
