<?php defined('HOPE_ROOT') || exit('access denied!');

$hope_ai_header_ready = false;
$hope_ai_header_models = [];
$hope_ai_header_default = '';
$hope_header_shortcuts = [];
$hope_header_shortcuts_catalog = [];
$hope_ai_agent_readonly = false;
if (is_file(HOPE_ROOT . 'system/app/service/header_shortcuts.php')) {
    require_once HOPE_ROOT . 'system/app/service/header_shortcuts.php';
    $hope_header_shortcuts = hope_get_header_shortcuts_links();
    $hope_header_shortcuts_catalog = hope_header_shortcuts_catalog();
}
if (is_file(HOPE_ROOT . 'system/app/service/ai_service.php')) {
    require_once HOPE_ROOT . 'system/app/service/ai_service.php';
    if (Option::get('ai_enabled') === 'y') {
        $hope_ai_agent_readonly = Option::get('ai_agent_readonly') === 'y';
        $hope_ai_header_ready = hope_ai_is_ready();
        if ($hope_ai_header_ready) {
            $hope_ai_header_models = hope_ai_get_chat_models();
            $hope_ai_header_default = hope_ai_get_default_chat_model();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(hope_lang_html_attr()) ?>"<?= hope_lang_is_rtl() ? ' dir="rtl"' : '' ?>>

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="apple-touch-icon" sizes="76x76" href="<?= SITE_URL ?>content/admin/img/apple-icon.png">
  <link rel="icon" type="image/png" href="<?= SITE_URL ?>content/admin/img/favicon.png">
  <title><?= htmlspecialchars(__('admin.panel_title')) ?> - <?= Option::get('sitename') ?></title>
  <link href="<?= SITE_URL ?>content/admin/css/fontawesome7.css?v=<?= Option::HOPE_VERSION_TIMESTAMP ?>" rel="stylesheet" />

  <link href="<?= SITE_URL ?>content/admin/css/argon-dashboard.min.css?v=<?= Option::HOPE_VERSION_TIMESTAMP ?>" id="pagestyle"
    rel="stylesheet" />
  <link href="<?= SITE_URL ?>content/admin/css/style.css?v=<?= Option::HOPE_VERSION_TIMESTAMP ?>" rel="stylesheet" />
  <script src="<?= SITE_URL ?>content/admin/js/js.cookie-2.2.1.min.js?v=<?= Option::HOPE_VERSION_TIMESTAMP ?>"></script>
  <script src="<?= SITE_URL ?>content/admin/js/jquery-3.7.1.min.js?v=<?= Option::HOPE_VERSION_TIMESTAMP ?>"></script>
  <script src="<?= SITE_URL ?>content/admin/js/jquery-ui.min.js?v=<?= Option::HOPE_VERSION_TIMESTAMP ?>"></script>
  <script src="<?= SITE_URL ?>content/admin/js/plugins/sweetalert.min.js?v=<?= Option::HOPE_VERSION_TIMESTAMP ?>"></script>
  <script src="<?= SITE_URL ?>content/admin/js/common.js?v=<?= Option::HOPE_VERSION_TIMESTAMP ?>"></script>
  <script>window.HOPE_I18N = <?= json_encode(hope_lang_js_strings(), JSON_UNESCAPED_UNICODE) ?>;window.HOPE_SITE_URL = <?= json_encode(SITE_URL, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;</script>
  <?php doAction('adm_head') ?>
</head>

<body class="g-sidenav-show bg-gray-100">
  <div class="min-height-300 bg-primary position-absolute w-100"></div>
  <aside
    class="sidenav bg-white navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-4"
    id="sidenav-main">
    <div class="sidenav-header">
      <i class="fa fa-xmark p-3 cursor-pointer text-secondary opacity-5 position-absolute end-0 top-0 d-none d-xl-none"
        aria-hidden="true" id="iconSidenav"></i>
      <a class="navbar-brand m-0" href="<?= SITE_URL ?>" target="_blank">
        <img src="<?= SITE_URL ?>content/admin/img/logo-ct-dark.png" class="navbar-brand-img h-100" alt="main_logo">
        <span class="ms-1 font-weight-bold">
            <?= Option::get('sitename') ?>
        </span>
      </a>
    </div>
    <hr class="horizontal dark mt-0">
    <div class="collapse navbar-collapse  w-auto " id="sidenav-collapse-main">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" id="menu_home" href="<?= SELF ?>">
            <div
              class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
              <i class="fa fa-gauge-high text-primary text-sm opacity-10"></i>
            </div>
            <span class="nav-link-text ms-1"><?= __('admin.menu.home') ?></span>
          </a>
        </li>
        <li class="nav-item">
          <a data-bs-toggle="collapse" class="nav-link" id="menu_content_manage" role="button" href="#menu_content" aria-expanded="false">
            <div
              class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
              <i class="fa fa-file-lines text-success text-sm opacity-10"></i>
            </div>
            <span class="nav-link-text ms-1"><?= __('admin.menu.content') ?></span>
          </a>
          <div class="collapse " id="menu_content">
            <ul class="nav ms-4">
              <li class="nav-item" id="menu_log">
                <a class="nav-link" href="<?= SELF ?>?act=article">
                  <span class="sidenav-mini-icon text-xs"> <?= __('admin.menu.article') ?> </span>
                  <span class="sidenav-normal"><?= __('admin.menu.article') ?></span>
                </a>
              </li>
              <li class="nav-item" id="menu_page">
                <a class="nav-link" href="<?= SELF ?>?act=page">
                  <span class="sidenav-mini-icon text-xs"> <?= __('admin.menu.page') ?> </span>
                  <span class="sidenav-normal"><?= __('admin.menu.page') ?></span>
                </a>
              </li>
              <li class="nav-item" id="menu_comment">
                <a class="nav-link" href="<?= SELF ?>?act=comment">
                  <span class="sidenav-mini-icon text-xs"> <?= __('admin.menu.comment') ?> </span>
                  <span class="sidenav-normal"><?= __('admin.menu.comment') ?></span>
                </a>
              </li>
              <li class="nav-item" id="menu_link">
                <a class="nav-link" href="<?= SELF ?>?act=link">
                  <span class="sidenav-mini-icon text-xs"> <?= __('admin.menu.link') ?> </span>
                  <span class="sidenav-normal"><?= __('admin.menu.link') ?></span>
                </a>
              </li>
              <li class="nav-item" id="menu_twitter">
                <a class="nav-link" href="<?= SELF ?>?act=twitter">
                  <span class="sidenav-mini-icon text-xs"> <?= __('admin.menu.twitter') ?> </span>
                  <span class="sidenav-normal"><?= __('admin.menu.twitter') ?></span>
                </a>
              </li>
            </ul>
          </div>
        </li>

        <li class="nav-item">
          <a data-bs-toggle="collapse" class="nav-link" id="menu_surface_manage" role="button" href="#menu_surface"
            aria-controls="menu_view" aria-expanded="false">
            <div
              class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
              <i class="fa fa-palette text-warning text-sm opacity-10"></i>
            </div>
            <span class="nav-link-text ms-1"><?= __('admin.menu.appearance') ?></span>
          </a>
          <div class="collapse " id="menu_surface">
            <ul class="nav ms-4">
              <li class="nav-item" id="menu_theme">
                <a class="nav-link" href="<?= SELF ?>?act=theme">
                  <span class="sidenav-mini-icon text-xs"> <?= __('admin.menu.theme') ?> </span>
                  <span class="sidenav-normal"><?= __('admin.menu.theme') ?></span>
                </a>
              </li>
              <li class="nav-item" id="menu_menu">
                <a class="nav-link" href="<?= SELF ?>?act=menu">
                  <span class="sidenav-mini-icon text-xs"> <?= __('admin.menu.menu') ?> </span>
                  <span class="sidenav-normal"><?= __('admin.menu.menu') ?></span>
                </a>
              </li>
            </ul>
          </div>
        </li>

        <li class="nav-item">
          <a data-bs-toggle="collapse" class="nav-link" id="menu_system_manage" role="button" href="#menu_system"
            aria-controls="menu_view" aria-expanded="false">
            <div
              class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
              <i class="fa fa-gear text-info text-sm opacity-10"></i>
            </div>
            <span class="nav-link-text ms-1"><?= __('admin.menu.system') ?></span>
          </a>
          <div class="collapse " id="menu_system">
            <ul class="nav ms-4">
              <li class="nav-item" id="menu_setting">
                <a class="nav-link" href="<?= SELF ?>?act=setting">
                  <span class="sidenav-mini-icon text-xs"> <?= __('admin.menu.setting') ?> </span>
                  <span class="sidenav-normal"><?= __('admin.menu.setting') ?></span>
                </a>
              </li>
              <li class="nav-item" id="menu_ai_studio">
                <a class="nav-link" href="<?= SELF ?>?act=ai_studio">
                  <span class="sidenav-mini-icon text-xs"> AI </span>
                  <span class="sidenav-normal"><?= __('admin.menu.ai_studio') ?></span>
                </a>
              </li>
              <li class="nav-item" id="menu_pay">
                <a class="nav-link" href="<?= SELF ?>?act=pay">
                  <span class="sidenav-mini-icon text-xs"> <?= __('admin.menu.pay') ?> </span>
                  <span class="sidenav-normal"><?= __('admin.menu.pay') ?></span>
                </a>
              </li>
              <li class="nav-item" id="menu_upload">
                <a class="nav-link" href="<?= SELF ?>?act=upload">
                  <span class="sidenav-mini-icon text-xs"> <?= __('admin.menu.upload') ?> </span>
                  <span class="sidenav-normal"><?= __('admin.menu.upload') ?></span>
                </a>
              </li>
              <li class="nav-item" id="menu_user">
                <a class="nav-link" href="<?= SELF ?>?act=user">
                  <span class="sidenav-mini-icon text-xs"> <?= __('admin.menu.user') ?> </span>
                  <span class="sidenav-normal"><?= __('admin.menu.user') ?></span>
                </a>
              </li>
            </ul>
          </div>
        </li>

        <?php doAction('adm_menu'); ?>

        <li class="nav-item">
          <hr class="horizontal dark">
          <a class="nav-link" id="menu_apply" href="<?= SELF ?>?act=apply">
            <div
              class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
              <i class="fa fa-store text-danger text-sm opacity-10"></i>
            </div>
            <span class="nav-link-text ms-1"><?= __('admin.menu.apply') ?></span>
          </a>
        </li>

        <li class="nav-item">
          <a data-bs-toggle="collapse" class="nav-link" id="menu_plugin_category" role="button" href="#menu_plugin"
            aria-controls="menu_plugin" aria-expanded="false">
            <div
              class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
              <i class="fa fa-plug text-muted text-sm opacity-10"></i>
            </div>
            <span class="nav-link-text ms-1"><?= __('admin.menu.plugin') ?></span>
          </a>
          <div class="collapse " id="menu_plugin">
            <ul class="nav ms-4">
              <li class="nav-item" id="menu_plugins">
                <a class="nav-link" href="<?= SELF ?>?act=plugin">
                  <span class="sidenav-mini-icon text-xs"> <?= __('admin.menu.plugin_manage') ?> </span>
                  <span class="sidenav-normal"><?= __('admin.menu.plugin_manage') ?></span>
                </a>
              </li>
            </ul>
          </div>
        </li>
        <?php doAction('adm_menu_ext'); ?>
      </ul>
    </div>
  </aside>
  <main class="main-content position-relative border-radius-lg hope-admin-main">
    <nav class="navbar navbar-main navbar-expand-lg px-0 mx-4 shadow-none border-radius-xl z-index-sticky"
      id="navbarBlur" data-scroll="false">
      <div class="container-fluid py-2 px-3 d-flex align-items-center">
        <div class="d-flex align-items-center hope-navbar-left">
        <div class="sidenav-toggler sidenav-toggler-inner d-xl-block d-none">
          <a href="javascript:;" class="nav-link p-0">
            <div class="sidenav-toggler-inner"> <i class="sidenav-toggler-line bg-white"></i> <i
                class="sidenav-toggler-line bg-white"></i> <i class="sidenav-toggler-line bg-white"></i> </div>
          </a>
        </div>
        <div class="hope-header-shortcuts-outer ms-2">
          <button type="button" class="hope-header-shortcuts-nav hope-header-shortcuts-nav-prev" aria-label="向左滚动" hidden>
            <i class="fa fa-chevron-left"></i>
          </button>
          <div class="hope-header-shortcuts d-flex align-items-center flex-nowrap hope-header-shortcuts-row">
          <a href="javascript:;" class="nav-link text-white px-2 py-1 hope-header-ai-btn" data-bs-toggle="offcanvas" data-bs-target="#hopeAiChatOffcanvas" title="AI 对话">
            <i class="fa fa-robot me-sm-1"></i><span class="d-none d-lg-inline">AI 对话</span>
          </a>
          <?php if (!empty($hope_header_shortcuts_catalog) && User::isAdmin()): ?>
          <a href="javascript:;" class="nav-link text-white px-2 py-1 hope-header-quick-settings" data-bs-toggle="modal" data-bs-target="#hopeHeaderShortcutsModal" title="快捷入口设置">
            <i class="fa fa-sliders"></i>
          </a>
          <?php endif; ?>
          <?php foreach ($hope_header_shortcuts as $item): ?>
          <a href="<?= SELF . htmlspecialchars($item['href']) ?>" class="nav-link text-white px-2 py-1 hope-header-quick-link"<?= !empty($item['target']) ? ' target="' . htmlspecialchars($item['target']) . '"' : '' ?> title="<?= htmlspecialchars($item['label']) ?>">
            <i class="fa <?= htmlspecialchars($item['icon']) ?> me-sm-1"></i><span class="d-none d-xl-inline"><?= htmlspecialchars($item['label']) ?></span>
          </a>
          <?php endforeach; ?>
          </div>
          <button type="button" class="hope-header-shortcuts-nav hope-header-shortcuts-nav-next" aria-label="向右滚动" hidden>
            <i class="fa fa-chevron-right"></i>
          </button>
        </div>
        </div>
        <div class="collapse navbar-collapse mt-sm-0 mt-2 me-md-0 me-sm-4 flex-shrink-0" id="navbar">
          <div class="ms-md-auto pe-md-3 d-flex align-items-center"></div>
          <ul class="navbar-nav justify-content-end">
            <li class="nav-item px-3 d-flex align-items-center">
              <a href="<?= SELF ?>?act=user" class="nav-link text-white font-weight-bold px-0"
                target="_blank"> <i class="fa fa-user me-sm-1"></i> <span class="d-sm-inline d-none">
                  <?= $user_cache[UID]['name'] ?>
                </span> </a>
            </li>
            <li class="nav-item d-flex align-items-center">
              <a href="<?= SELF ?>?act=logout" class="nav-link text-white p-0"> <i class="fa fa-right-from-bracket"></i> </a>
            </li>
            <li class="nav-item d-xl-none ps-3 d-flex align-items-center">
              <a href="javascript:;" class="nav-link text-white p-0" id="iconNavbarSidenav">
                <div class="sidenav-toggler-inner"> <i class="sidenav-toggler-line bg-white"></i> <i
                    class="sidenav-toggler-line bg-white"></i> <i class="sidenav-toggler-line bg-white"></i></div>
              </a>
            </li>
          </ul>
        </div>
      </div>
    </nav>
    <?php if (!empty($hope_header_shortcuts_catalog) && User::isAdmin()): ?>
    <div class="modal fade" id="hopeHeaderShortcutsModal" tabindex="-1" aria-labelledby="hopeHeaderShortcutsModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header py-3">
            <h5 class="modal-title text-sm" id="hopeHeaderShortcutsModalLabel"><i class="fa fa-bolt text-warning me-1"></i>快捷入口设置</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body py-2 hope-header-shortcuts-modal-body">
            <p class="text-xs text-muted mb-2">勾选要在顶部显示的菜单</p>
            <div id="hope-header-shortcuts-form">
              <?php
              $hopeHeaderShortcutsEnabled = array_flip(hope_get_header_shortcuts_enabled());
              foreach ($hope_header_shortcuts_catalog as $sc):
              ?>
              <div class="form-check mb-2">
                <input class="form-check-input hope-header-shortcut-cb" type="checkbox" value="<?= htmlspecialchars($sc['id']) ?>" id="hope-sc-<?= htmlspecialchars($sc['id']) ?>" <?= isset($hopeHeaderShortcutsEnabled[$sc['id']]) ? 'checked' : '' ?>>
                <label class="form-check-label text-sm" for="hope-sc-<?= htmlspecialchars($sc['id']) ?>">
                  <span class="text-muted"><?= htmlspecialchars($sc['group']) ?></span> · <?= htmlspecialchars($sc['label']) ?>
                </label>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="modal-footer py-2">
            <button type="button" class="btn btn-secondary btn-sm mb-0" data-bs-dismiss="modal">取消</button>
            <button type="button" class="btn btn-primary btn-sm mb-0" id="hope-header-shortcuts-save">保存</button>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>
    <div class="offcanvas offcanvas-end hope-ai-offcanvas" tabindex="-1" id="hopeAiChatOffcanvas" aria-labelledby="hopeAiChatOffcanvasLabel">
      <div class="offcanvas-header border-bottom">
        <div>
          <h5 class="offcanvas-title mb-0" id="hopeAiChatOffcanvasLabel"><i class="fa fa-robot text-primary me-1"></i> AI 助手</h5>
          <p class="text-xs text-muted mb-0"><?= !empty($hope_ai_agent_readonly) ? '只读模式：仅可查询数据' : '可查询/修改站点数据，敏感操作需确认' ?></p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body d-flex flex-column p-0">
        <?php if (!$hope_ai_header_ready): ?>
        <div class="p-4 text-center flex-grow-1 d-flex flex-column justify-content-center">
          <i class="fa fa-robot fa-2x text-muted mb-3"></i>
          <p class="text-sm text-muted mb-3">请先在 AI 配置中启用 AI 并添加对话模型</p>
          <a href="<?= SELF ?>?act=ai_studio#ai-tab-config" class="btn btn-primary btn-sm mb-0">去配置</a>
        </div>
        <?php else: ?>
        <div class="px-3 pt-3">
          <label class="form-label text-xs text-muted mb-1">对话模型</label>
          <select class="form-control form-control-sm mb-0" id="hope-header-ai-model" data-hope-choices>
            <?php foreach ($hope_ai_header_models as $m): ?>
            <option value="<?= htmlspecialchars($m['id']) ?>" <?= $hope_ai_header_default === $m['id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['label']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="hope-header-ai-messages flex-grow-1" id="hope-header-ai-messages">
          <div class="hope-header-ai-empty text-center text-muted text-sm">开始对话吧</div>
        </div>
        <div class="hope-header-ai-input border-top p-3">
          <div class="input-group">
            <textarea class="form-control" id="hope-header-ai-input" rows="2" placeholder="输入消息，Enter 发送"></textarea>
            <button class="btn btn-primary mb-0" type="button" id="hope-header-ai-send">发送</button>
          </div>
          <div class="d-flex justify-content-between align-items-center mt-2">
            <a href="<?= SELF ?>?act=ai_studio" class="text-xs">打开 AI 工作台</a>
            <button type="button" class="btn btn-link btn-sm text-muted mb-0 p-0" id="hope-header-ai-clear">清空</button>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <input type="hidden" id="hope-admin-token" value="<?= LoginAuth::genToken() ?>">
    <div class="container-fluid py-4 hope-admin-shell">
    <div class="hope-admin-page">
