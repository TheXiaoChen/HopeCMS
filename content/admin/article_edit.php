<?php defined('HOPE_ROOT') || exit('access denied!'); ?>
<link href="<?= SITE_URL ?>content/admin/editor.md/css/editormd.css?v=<?= Option::HOPE_VERSION_TIMESTAMP ?>" rel="stylesheet" />
<style>
.fa { width:auto }
#preset_box .preset-field-tabs { display:flex; flex-wrap:wrap; gap:.35rem; margin:0 0 .85rem; padding:0; list-style:none; }
#preset_box .preset-field-tabs .nav-link { padding:.28rem .7rem; font-size:.74rem; border-radius:999px; color:#67748e; background:#fff; border:1px solid #e9ecef; line-height:1.2; }
#preset_box .preset-field-tabs .nav-link.active { color:#fff; background:#5e72e4; border-color:#5e72e4; }
#preset_box .preset-tab-pane { display:none; }
#preset_box .preset-tab-pane.is-active { display:block; }
#preset_box .form-group { margin-bottom: .95rem; padding-bottom: .85rem; border-bottom: 1px dashed #eef2f6; }
#preset_box .preset-tab-pane .form-group:last-child { border-bottom: 0; margin-bottom: 0; padding-bottom: 0; }
#preset_box .form-group > label { display:flex; align-items:center; gap:.4rem; font-size: .8rem; font-weight: 600; margin-bottom: .35rem; color: #344767; }
#preset_box .form-group > label .preset-type-tag { font-size:.65rem; font-weight:600; padding:.1rem .4rem; border-radius:999px; background:#eef2ff; color:#5e72e4; }
#preset_box .field-desc { display:block; font-size: .72rem; color: #8392ab; margin-top: .28rem; line-height: 1.35; }
#preset_box .field-options { display:flex; flex-wrap:wrap; gap:.4rem .85rem; padding:.4rem .55rem; border:1px solid #e9ecef; border-radius:.5rem; background:#f8fafc; }
#preset_box .field-options .form-check { margin:0; min-height:auto; }
#preset_box textarea.form-control { min-height: 72px; }
#preset_box .choices { margin-bottom: 0; }
.preset-panel-head { display:flex; align-items:center; justify-content:space-between; gap:.5rem; margin-bottom:.75rem; }
.preset-panel-head .title { font-size:.82rem; font-weight:700; color:#344767; margin:0; }
#preset_list .preset-manage-tabs { display:flex; flex-wrap:wrap; gap:.35rem; margin:0 0 1rem; padding:0; list-style:none; }
#preset_list-wrap .preset-manage-tabs .nav-link,
#presetModal .preset-manage-tabs .nav-link { padding:.3rem .85rem; font-size:.78rem; border-radius:999px; color:#67748e; background:#fff; border:1px solid #e9ecef; }
#presetModal .preset-manage-tabs .nav-link.active { color:#fff; background:#5e72e4; border-color:#5e72e4; }
#presetModal .preset-manage-pane { display:none; }
#presetModal .preset-manage-pane.is-active { display:block; }
#preset_list .preset-card { border:1px solid #e9ecef; border-radius:.65rem; padding:1rem; margin-bottom:.85rem; background:#fff; box-shadow:0 1px 2px rgba(0,0,0,.03); }
#preset_list .preset-card .preset-card-title { display:flex; align-items:center; justify-content:space-between; gap:.5rem; margin-bottom:.75rem; }
#preset_list .preset-card .preset-card-title strong { font-size:.86rem; color:#344767; }
#preset_list .preset-card .preset-type-badge { font-size:.68rem; font-weight:600; padding:.15rem .5rem; border-radius:999px; background:#eef2ff; color:#5e72e4; }
#preset_list .preset-card .preset-options-wrap { display:none; margin-top:.75rem; padding-top:.75rem; border-top:1px dashed #e9ecef; }
#preset_list .preset-card.is-choice .preset-options-wrap { display:block; }
#preset_list .preset-hint { font-size:.72rem; color:#8392ab; margin-top:.25rem; }
</style>
<div id="editor-md-dialog"></div>
<form action="<?= SELF ?>?act=article_edit" method="post" enctype="multipart/form-data" id="editlog" name="editlog">
    <div class="row">
        <div class="col-lg-8 col-12">
            <div class="card card-body">
                <div class="d-lg-flex">
                    <h5 class="mb-0"><?= $pagetitle ?></h5>
                    <div class="ms-auto">
				        <input name="token" id="token" value="<?= LoginAuth::genToken() ?>" type="hidden" />
                        <input type="hidden" name="gid" id="gid" value="<?= $logid ?>"/>
                        <button type="button" id="save" class="btn bg-gradient-success" onclick="checkArticle(1);">保存</button>
                        <button type="submit" class="btn bg-gradient-primary ms-2" onclick="return checkArticle();">提交</button>
                    </div>
                </div>
                <hr class="horizontal dark my-2">
                <input type="text" class="form-control" placeholder="文章标题" id="title" name="title" value="<?= $title ?>" required>
                <?php if (!empty($conf_ai_enabled)): ?>
                <div class="d-flex align-items-center flex-wrap gap-2 mt-2 mb-1">
                    <select class="form-select form-select-sm" id="ai-assist-model" style="width:auto;max-width:180px;">
                        <?php foreach ($ai_chat_models as $m): ?>
                        <option value="<?= htmlspecialchars($m['id']) ?>" <?= $ai_default_chat_model === $m['id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="dropdown">
                        <button class="btn btn-xs btn-outline-info dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa fa-magic"></i> AI 助手
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" data-ai-task="article_bundle" data-ai-prompt="1">一键成稿（标题+摘要+正文+标签）</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" data-ai-task="title">生成标题</a></li>
                            <li><a class="dropdown-item" href="#" data-ai-task="excerpt">生成摘要</a></li>
                            <li><a class="dropdown-item" href="#" data-ai-task="content" data-ai-prompt="1">撰写正文</a></li>
                            <li><a class="dropdown-item" href="#" data-ai-task="content">续写正文（插入光标）</a></li>
                            <li><a class="dropdown-item" href="#" data-ai-task="polish">润色内容（优先选区）</a></li>
                            <li><a class="dropdown-item" href="#" data-ai-task="tags">生成标签</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" data-ai-task="article_cover">AI 配图（封面）</a></li>
                            <li><a class="dropdown-item" href="#" data-ai-task="translate" data-ai-prompt="1">翻译正文</a></li>
                        </ul>
                    </div>
                    <small class="text-muted">基于系统设置中的 AI 配置</small>
                </div>
                <?php endif; ?>
                <div class="d-flex align-items-center mt-2 mb-2">
                    <button type="button" class="btn btn-xs btn-outline-primary" data-bs-toggle="modal" data-bs-target="#mediaModal">
                        <i class="fa fa-folder-open"></i> 资源库
                    </button>
                    <?php doAction('adm_writelog_bar', isset($logid) ? (int)$logid : 0); ?>
                </div>
                <div id="logcontent" style="border-radius: .5rem;">
                    <textarea style="display:none;"><?= $content ?></textarea>
                </div>
                <div class="form-group">
                    <label>标签</label>
                    <input class="form-control" name="tag" type="text" value="<?= $tagStr ?>" data-hope-choices data-preset="tag">
                </div>
                <a class="btn btn-primary" data-bs-toggle="collapse" href="#excerpt" role="button" aria-expanded="<?= empty($excerpt) ? 'false' : 'true' ?>" aria-controls="excerpt">文章摘要</a>
                <div class="collapse<?= empty($excerpt) ? '' : ' show' ?>" id="excerpt">
                    <textarea class="form-control" rows="5" name="logexcerpt"><?= $excerpt ?></textarea>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-12 mt-4 mt-lg-0">
            <div class="card card-body" id="option">
                <div class="nav-wrapper position-relative end-0">
                    <ul class="nav nav-pills nav-fill p-1" id="optionTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button type="button" class="nav-link mb-0 px-0 py-1 active" id="option-tab-basic" data-bs-toggle="tab" data-bs-target="#Basic" role="tab" aria-controls="Basic" aria-selected="true">
                                <i class="fa fa-sliders text-sm me-2"></i> 基本选项
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button type="button" class="nav-link mb-0 px-0 py-1" id="option-tab-other" data-bs-toggle="tab" data-bs-target="#Other" role="tab" aria-controls="Other" aria-selected="false">
                                <i class="fa fa-laptop text-sm me-2"></i> 其他选项
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="tab-content pt-2">
                <div id="Basic" class="tab-pane fade show active" role="tabpanel" aria-labelledby="option-tab-basic" tabindex="0">
                    <div class="form-group">
                        <label>文章封面</label>
                        <div class="input-group input-group-alternative mb-4">
                            <input name="cover" id="cover" class="form-control" placeholder="封面图URL" value="<?= $cover ?>">
                            <span class="input-group-text">
                                <label for="upload_cover" style="margin-bottom:0; cursor:pointer;">
                                    <i class="fa fa-cloud-arrow-up"></i>
                                    <input type="file" id="upload_cover" accept="image/*" style="display:none" />
                                </label>
                            </span>
                        </div>
                        <select class="form-control" name="sort" data-hope-choices data-preset="search">
                        <option value="-1">选择分类...</option>
                            <?php
                            foreach ($sorts as $key => $value):
                                if ($value['pid'] != 0) {
                                    continue;
                                }
                                $flg = $value['sid'] == $sortid ? 'selected' : '';
                                ?>
                                <option value="<?= $value['sid'] ?>" <?= $flg ?>><?= $value['sortname'] ?></option>
                                <?php
                                $children = $value['children'];
                                foreach ($children as $key):
                                    $value = $sorts[$key];
                                    $flg = $value['sid'] == $sortid ? 'selected' : '';
                                    ?>
                                    <option value="<?= $value['sid'] ?>" <?= $flg ?>>&nbsp; &nbsp; &nbsp; <?= $value['sortname'] ?></option>
                                <?php
                                endforeach;
                            endforeach;
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>链接别名</label>
                        <input name="alias" id="alias" class="form-control" value="<?= $alias ?>">
                    </div>
                    <div class="form-group">
                        <label>访问密码</label>
                        <input type="text" name="password" id="password" class="form-control" value="<?= $password ?>">
                    </div>
                    <div class="form-group">
                        <label>文章作者</label>
                        <select class="form-control" name="author" data-hope-choices data-preset="search">
                        <?php foreach ($user_cache as $key => $val):
                            $flg = $key == $author ? 'selected' : '';
                            ?>
                            <option value="<?= $key ?>" <?= $flg ?>><?= $val['name'] ?></option>
                        <?php endforeach ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>发布时间</label>
                        <input class="form-control" name="date" type="datetime-local" value="<?= $date ?>">
                    </div>
                    <div class="form-group row">
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <div class="form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" name="hide" value="n" <?= $is_hide ?>>
                                </div>
                                <span class="text-dark font-weight-bold text-sm">公开状态</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <div class="form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" name="remark" value="y" <?= $is_remark ?>>
                                </div>
                                <span class="text-dark font-weight-bold text-sm">允许评论</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <div class="form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" name="top" value="y" <?= $is_top ?>>
                                </div>
                                <span class="text-dark font-weight-bold text-sm">首页置顶</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <div class="form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" name="sortop" value="y" <?= $is_sortop ?>>
                                </div>
                                <span class="text-dark font-weight-bold text-sm">分类置顶</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="Other" class="tab-pane fade" role="tabpanel" aria-labelledby="option-tab-other" tabindex="0">
                    <div class="preset-panel-head">
                        <p class="title">资源 / 自定义字段</p>
                        <button type="button" class="btn btn-xs btn-outline-secondary mb-0" data-bs-toggle="modal" data-bs-target="#presetModal">管理预设</button>
                    </div>
                    <div id="preset_box"></div>
                    <div id="preset_box_empty" class="text-sm text-muted py-2 d-none">暂无预设字段，点击右上角「管理预设」添加。</div>
                    <div id="field_box" class="mt-3"></div>
                </div>
                </div>
            </div>
        </div>
    </div>
</form>
<div class="modal fade" id="mediaModal" tabindex="-1" aria-labelledby="mediaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-xl modal-dialog-centered" role="document">
        <div class="modal-content media-modal-content border-0 shadow">
            <div class="modal-header media-modal-header">
                <h5 class="modal-title" id="mediaModalLabel"><i class="fa fa-folder-open me-2 text-primary"></i>资源库</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body media-modal-body">
                <div class="media-modal-toolbar">
                    <a href="javascript:;" id="mediaAdd" class="btn btn-sm btn-success mb-0"><i class="fa fa-cloud-arrow-up me-1"></i>上传附件</a>
                    <div class="media-modal-toolbar-right">
                    <?php if (User::haveEditPermission()): ?>
                        <select class="form-select form-select-sm" id="media-sort-select" data-hope-choices data-preset="search">
                            <option value="">全部分类</option>
                            <?php foreach (($mediaSorts ?? []) as $v): ?>
                                <option value="<?= (int)$v['id'] ?>"><?= htmlspecialchars($v['sortname']) ?></option>
                            <?php endforeach ?>
                        </select>
                    <?php endif ?>
                        <div class="media-modal-search">
                            <input type="search" class="form-control form-control-sm" id="media-keyword" placeholder="搜索资源名称…" autocomplete="off">
                        </div>
                    </div>
                </div>
                <div class="row g-3" id="image-list"></div>
                <div id="media-empty" class="media-modal-empty d-none">
                    <i class="fa fa-images"></i>
                    <p>暂无资源，点击上方「上传附件」添加</p>
                </div>
                <div class="text-center media-modal-more">
                    <button type="button" class="btn btn-outline-primary btn-sm" id="load-more">加载更多</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 预设字段管理 Modal -->
<div class="modal fade" id="presetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">管理预设字段</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-secondary text-sm mb-3 py-2" role="alert">
                    配置「其他选项」中的文章自定义字段。支持文本、下拉、单选、多选、开关等。
                    选项写法：每行一个，可用 <code>值</code> 或 <code>值|显示名</code>。
                    分组名用于侧栏 Tab 分区（如 <code>ResHub · 软件</code>）。
                </div>
                <ul class="preset-manage-tabs nav" id="preset_manage_tabs" role="tablist"></ul>
                <div id="preset_list"></div>
                <div id="preset_empty" class="text-center text-muted py-4 d-none">暂无预设字段，点击下方「添加字段」开始配置。</div>
            </div>
            <div class="modal-footer">
                <button type="button" id="preset_add" class="btn btn-outline-primary"><i class="fa fa-plus me-1"></i>添加字段</button>
                <button type="button" id="preset_save" class="btn btn-primary">保存并应用</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>


<div class="dropzone-previews" style="display: none;"></div>
<script src="<?= SITE_URL ?>content/admin/js/plugins/dropzone.min.js?v=<?= Option::HOPE_VERSION_TIMESTAMP ?>"></script>
<script src="<?= SITE_URL ?>content/admin/js/media-lib.js?v=<?= Option::HOPE_VERSION_TIMESTAMP ?>"></script>
<?php if (!empty($conf_ai_enabled)): ?>
<script>window.HOPE_AI_URL = '<?= SELF ?>?act=ai';</script>
<script src="<?= SITE_URL ?>content/admin/js/ai_assist.js?v=<?= Option::HOPE_VERSION_TIMESTAMP ?>"></script>
<?php endif; ?>
<script src="<?= SITE_URL ?>content/admin/editor.md/editormd.js?v=<?= Option::HOPE_VERSION_TIMESTAMP ?>"></script>
<script>
    $("#menu_content_manage").addClass('active');
    $("#menu_content").addClass('show');
    $("#menu_log").addClass('active');
    // 编辑器
    var Editor;
    $(function() {
        Editor = editormd("logcontent", {
            width: "100%",
            height: 560,
            path: "<?= SITE_URL ?>content/admin/editor.md/lib/",
            toolbarIcons: function () {
                return ["bold", "del", "italic", "quote", "|", "h1", "h2", "h3", "|", "list-ul", "list-ol", "hr", "|",
                    "link", "image", "audio", "video", "preformatted-text", "code-block", "table", "|",
                    "goto-line", "search", "clear", "|", "watch", "preview", "fullscreen", "help"];
            },
            watch: false,
            htmlDecode: true,
            imageUpload: true,
            imageFormats: ["jpg", "jpeg", "gif", "png", "bmp", "webp"],
            imageUploadURL: <?= json_encode(SELF . '?act=upload&uploade&editor=1&' . hope_admin_token_param(), JSON_UNESCAPED_SLASHES) ?>,
            videoUpload: true,
            videoFormats: ["mp4", "webm", "ogg"],
            videoUploadURL: <?= json_encode(SELF . '?act=upload&uploade&editor=1&' . hope_admin_token_param(), JSON_UNESCAPED_SLASHES) ?>,
            toolbarCustomize: true,
            autoHeightFit: true,
            pasteUpload: true,
            syncScrolling: "single",
            placeholder: "开始写作… 支持粘贴/拖拽图片；右侧列表可勾选显示的工具按钮",
            onfullscreen: function () {
                this.watch();
                document.body.classList.add("editormd-fullscreen");
            },
            onfullscreenExit: function () {
                this.unwatch();
                document.body.classList.remove("editormd-fullscreen");
            },
            onload: function () {
                hooks.doAction("loaded", this);
            }
        });
        Editor.setToolbarAutoFixed(false);

        // 右侧「基本 / 其他」Tab：切换后刷新 Choices（隐藏面板内下拉需重新初始化）
        $('#optionTabs [data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
            var target = e.target && e.target.getAttribute('data-bs-target');
            var pane = target ? document.querySelector(target) : null;
            if (pane && typeof window.hopeInitChoicesAll === 'function') {
                window.hopeInitChoicesAll(pane);
            }
            if (target === '#Other') {
                try { renderPresets(); } catch (err) {}
            }
        });

        // 预设字段管理
        var presetFields = [];
        var blogFields = <?php
            $__bf = [];
            if (!empty($fields)) {
                $__bf = @unserialize($fields);
                if (!is_array($__bf)) $__bf = [];
            }
            echo json_encode($__bf, JSON_UNESCAPED_UNICODE);
        ?>;
        if (!blogFields || typeof blogFields !== 'object') blogFields = {};

        var FIELD_TYPES = [
            {v:'text', t:'单行文本'},
            {v:'textarea', t:'多行文本'},
            {v:'number', t:'数字'},
            {v:'url', t:'链接'},
            {v:'select', t:'下拉选择'},
            {v:'radio', t:'单选'},
            {v:'checkbox', t:'多选'},
            {v:'switch', t:'开关'}
        ];

        function htmlEscape(s) {
            return String(s == null ? '' : s).replace(/[&<>"'`]/g, function (c) {
                return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','`':'&#96;'})[c];
            });
        }

        function normalizePreset(p) {
            p = p || {};
            var type = String(p.type || 'text').toLowerCase();
            var allowed = FIELD_TYPES.map(function(x){ return x.v; });
            if (allowed.indexOf(type) < 0) type = 'text';
            return {
                key: String(p.key || p.name || '').trim(),
                label: String(p.label || p.title || p.key || '').trim(),
                type: type,
                placeholder: String(p.placeholder || ''),
                default: Array.isArray(p.default) ? p.default.join(',') : String(p.default == null ? '' : p.default),
                options: typeof p.options === 'string' ? p.options : (Array.isArray(p.options) ? p.options.join('\n') : String(p.options || p.values || '')),
                description: String(p.description || p.desc || ''),
                group: String(p.group || p.group_name || '').trim()
            };
        }

        function parseOptions(text) {
            var rows = [];
            String(text || '').split(/[\r\n,]+/).forEach(function(line) {
                line = String(line || '').trim();
                if (!line) return;
                var value, label;
                if (line.indexOf('|') >= 0) {
                    var parts = line.split('|');
                    value = String(parts[0] || '').trim();
                    label = String(parts.slice(1).join('|') || '').trim();
                } else {
                    value = line;
                    label = line;
                }
                if (!value) return;
                rows.push({ value: value, label: label || value });
            });
            return rows;
        }

        function resolveFieldValue(p) {
            var k = p.key;
            if (!k) return '';
            if (Object.prototype.hasOwnProperty.call(blogFields, k) && blogFields[k] !== null && blogFields[k] !== undefined) {
                return blogFields[k];
            }
            if (p.type === 'checkbox') {
                return String(p.default || '').split(/[,，]/).map(function(s){ return s.trim(); }).filter(Boolean);
            }
            if (p.type === 'switch') {
                return p.default || '';
            }
            return p.default || '';
        }

        function isCheckedValue(current, optionValue) {
            if (Array.isArray(current)) {
                return current.map(String).indexOf(String(optionValue)) >= 0;
            }
            return String(current) === String(optionValue);
        }

        function buildControlHtml(p, idx) {
            var val = resolveFieldValue(p);
            var ph = htmlEscape(p.placeholder || '');
            var name = 'field_value[' + idx + ']';
            var opts = parseOptions(p.options);
            var html = '';

            if (p.type === 'textarea') {
                html = '<textarea class="form-control" name="' + name + '" rows="3" placeholder="' + ph + '">' + htmlEscape(val) + '</textarea>';
            } else if (p.type === 'number') {
                html = '<input type="number" class="form-control" name="' + name + '" value="' + htmlEscape(val) + '" placeholder="' + ph + '" step="any">';
            } else if (p.type === 'url') {
                html = '<input type="url" class="form-control" name="' + name + '" value="' + htmlEscape(val) + '" placeholder="' + ph + '">';
            } else if (p.type === 'select') {
                html = '<select class="form-control" name="' + name + '" data-hope-choices data-preset="search"><option value="">请选择</option>';
                opts.forEach(function(o) {
                    html += '<option value="' + htmlEscape(o.value) + '"' + (String(val) === String(o.value) ? ' selected' : '') + '>' + htmlEscape(o.label) + '</option>';
                });
                html += '</select>';
            } else if (p.type === 'radio') {
                html = '<div class="field-options">';
                if (!opts.length) opts = [{value:'开启', label:'开启'}, {value:'关闭', label:'关闭'}];
                opts.forEach(function(o, i) {
                    var id = 'pf_' + idx + '_r_' + i;
                    html += '<div class="form-check"><input class="form-check-input" type="radio" name="' + name + '" id="' + id + '" value="' + htmlEscape(o.value) + '"' + (isCheckedValue(val, o.value) ? ' checked' : '') + '><label class="form-check-label" for="' + id + '">' + htmlEscape(o.label) + '</label></div>';
                });
                html += '</div>';
            } else if (p.type === 'checkbox') {
                html = '<div class="field-options">';
                if (!opts.length) opts = [{value:'开启', label:'开启'}];
                opts.forEach(function(o, i) {
                    var id = 'pf_' + idx + '_c_' + i;
                    html += '<div class="form-check"><input class="form-check-input" type="checkbox" name="' + name + '[]" id="' + id + '" value="' + htmlEscape(o.value) + '"' + (isCheckedValue(val, o.value) ? ' checked' : '') + '><label class="form-check-label" for="' + id + '">' + htmlEscape(o.label) + '</label></div>';
                });
                html += '</div>';
            } else if (p.type === 'switch') {
                var onVal = opts[0] ? opts[0].value : '开启';
                var offVal = opts[1] ? opts[1].value : '';
                var on = isCheckedValue(val, onVal) || (val === true || val === 1 || val === '1' || val === 'y' || val === 'on');
                html = '<div class="form-check form-switch"><input type="hidden" name="' + name + '" value="' + htmlEscape(offVal) + '"><input class="form-check-input" type="checkbox" role="switch" name="' + name + '" value="' + htmlEscape(onVal) + '"' + (on ? ' checked' : '') + '><span class="ms-2 text-sm">' + htmlEscape(on ? (opts[0] ? opts[0].label : onVal) : (opts[1] ? opts[1].label : '关闭')) + '</span></div>';
            } else {
                html = '<input type="text" class="form-control" name="' + name + '" value="' + htmlEscape(val) + '" placeholder="' + ph + '">';
            }
            return html;
        }

        function typeLabel(type) {
            for (var i = 0; i < FIELD_TYPES.length; i++) {
                if (FIELD_TYPES[i].v === type) return FIELD_TYPES[i].t;
            }
            return type || '文本';
        }

        function groupTabLabel(name) {
            var n = String(name || '其他字段');
            if (n.indexOf('ResHub · ') === 0) return n.slice(9);
            if (n.indexOf('ResHub ') === 0) return n.slice(7);
            return n;
        }

        function collectPresetGroups(list) {
            var groups = {};
            var groupOrder = [];
            (list || []).forEach(function(raw) {
                var p = normalizePreset(raw);
                if (!p.key && !(p.label || p.group)) return;
                var g = p.group || '其他字段';
                if (!groups[g]) {
                    groups[g] = [];
                    groupOrder.push(g);
                }
                groups[g].push(p);
            });
            return { groups: groups, order: groupOrder };
        }

        var presetActiveTab = '';
        var presetManageActiveTab = '';

        function renderPresets() {
            var $box = $('#preset_box');
            var $empty = $('#preset_box_empty');
            if (!$box.length) return;
            $box.empty();
            var idx = 0;
            var packed = collectPresetGroups(presetFields);
            var groups = packed.groups;
            var groupOrder = packed.order.filter(function(g) {
                return (groups[g] || []).some(function(p){ return !!p.key; });
            });
            if (!groupOrder.length) {
                if ($empty.length) $empty.removeClass('d-none');
                return;
            }
            if ($empty.length) $empty.addClass('d-none');
            if (!presetActiveTab || groupOrder.indexOf(presetActiveTab) < 0) {
                presetActiveTab = groupOrder[0];
            }
            var $tabs = $('<ul class="preset-field-tabs nav" role="tablist"></ul>');
            groupOrder.forEach(function(gName, i) {
                var active = gName === presetActiveTab;
                var $li = $('<li class="nav-item" role="presentation"></li>');
                $li.append(
                    $('<button type="button" class="nav-link' + (active ? ' active' : '') + '"></button>')
                        .attr('data-preset-tab', gName)
                        .text(groupTabLabel(gName) + ' (' + groups[gName].filter(function(p){ return !!p.key; }).length + ')')
                );
                $tabs.append($li);
            });
            if (groupOrder.length > 1) {
                $box.append($tabs);
            }
            groupOrder.forEach(function(gName) {
                var $pane = $('<div class="preset-tab-pane' + (gName === presetActiveTab ? ' is-active' : '') + '"></div>')
                    .attr('data-preset-pane', gName);
                groups[gName].forEach(function(p) {
                    if (!p.key) return;
                    var group = $('<div class="form-group"></div>');
                    group.append('<label>' + htmlEscape(p.label || p.key) + '<span class="preset-type-tag">' + htmlEscape(typeLabel(p.type)) + '</span></label>');
                    group.append('<input type="hidden" name="field_key[]" value="' + htmlEscape(p.key) + '">');
                    group.append(buildControlHtml(p, idx));
                    if (p.description) {
                        group.append('<span class="field-desc">' + htmlEscape(p.description) + '</span>');
                    }
                    $pane.append(group);
                    idx++;
                });
                $box.append($pane);
            });
            $box.find('.form-switch input[type=checkbox]').each(function(){
                var $cb = $(this);
                var $hidden = $cb.siblings('input[type=hidden][name="' + $cb.attr('name') + '"]');
                if ($hidden.length) $hidden.prop('disabled', $cb.prop('checked'));
            });
            if (typeof window.hopeInitChoicesAll === 'function') {
                window.hopeInitChoicesAll($box.find('.preset-tab-pane.is-active')[0] || $box[0]);
            }
        }

        function typeOptionsHtml(selected) {
            return FIELD_TYPES.map(function(t) {
                return '<option value="' + t.v + '"' + (selected === t.v ? ' selected' : '') + '>' + t.t + '</option>';
            }).join('');
        }

        function isChoiceType(type) {
            return ['select', 'radio', 'checkbox', 'switch'].indexOf(type) >= 0;
        }

        function syncPresetFieldsFromModal() {
            var arr = [];
            $('#preset_list .preset-card').each(function() {
                var $c = $(this);
                arr.push(normalizePreset({
                    key: $c.find('.preset-key').val(),
                    label: $c.find('.preset-label').val(),
                    type: $c.find('.preset-type').val(),
                    placeholder: $c.find('.preset-placeholder').val(),
                    default: $c.find('.preset-default').val(),
                    options: $c.find('.preset-options').val(),
                    description: $c.find('.preset-description').val(),
                    group: $c.find('.preset-group-name').val()
                }));
            });
            presetFields = arr;
            return arr;
        }

        function buildPresetCardHtml(p, i) {
            var choice = isChoiceType(p.type);
            var title = p.label || p.key || ('字段 ' + (i + 1));
            return '<div class="preset-card' + (choice ? ' is-choice' : '') + '" data-preset-card-group="' + htmlEscape(p.group || '其他字段') + '">' +
                '<div class="preset-card-title">' +
                    '<strong>' + htmlEscape(title) + '</strong>' +
                    '<span class="d-flex align-items-center gap-2">' +
                        '<span class="preset-type-badge">' + htmlEscape(typeLabel(p.type)) + '</span>' +
                        '<button type="button" class="btn btn-sm btn-outline-danger preset-row-del mb-0" title="删除"><i class="fa fa-trash"></i></button>' +
                    '</span>' +
                '</div>' +
                '<div class="row g-2 align-items-start">' +
                    '<div class="col-md-3"><label class="form-label text-xs mb-1">字段 Key</label><input class="form-control form-control-sm preset-key" value="' + htmlEscape(p.key) + '" placeholder="如 claim"><div class="preset-hint">程序读取用，勿重复</div></div>' +
                    '<div class="col-md-3"><label class="form-label text-xs mb-1">显示名称</label><input class="form-control form-control-sm preset-label" value="' + htmlEscape(p.label) + '" placeholder="下载权限"></div>' +
                    '<div class="col-md-3"><label class="form-label text-xs mb-1">字段类型</label><select class="form-control form-control-sm preset-type" data-hope-choices>' + typeOptionsHtml(p.type) + '</select></div>' +
                    '<div class="col-md-3"><label class="form-label text-xs mb-1">分组</label><input class="form-control form-control-sm preset-group-name" value="' + htmlEscape(p.group) + '" placeholder="如 ResHub · 软件" list="preset-group-suggestions"></div>' +
                '</div>' +
                '<div class="row g-2 mt-1">' +
                    '<div class="col-md-4"><label class="form-label text-xs mb-1">默认值</label><input class="form-control form-control-sm preset-default" value="' + htmlEscape(p.default) + '" placeholder="可选"></div>' +
                    '<div class="col-md-4"><label class="form-label text-xs mb-1">占位提示</label><input class="form-control form-control-sm preset-placeholder" value="' + htmlEscape(p.placeholder) + '" placeholder="输入框提示文字"></div>' +
                    '<div class="col-md-4"><label class="form-label text-xs mb-1">字段说明</label><input class="form-control form-control-sm preset-description" value="' + htmlEscape(p.description) + '" placeholder="显示在字段下方"></div>' +
                '</div>' +
                '<div class="preset-options-wrap">' +
                    '<label class="form-label text-xs mb-1">选项列表（下拉 / 单选 / 多选 / 开关）</label>' +
                    '<textarea class="form-control form-control-sm preset-options" rows="3" placeholder="每行一个：值 或 值|显示名&#10;示例：&#10;0|白嫖会员&#10;1|白银会员">' + htmlEscape(p.options) + '</textarea>' +
                    '<div class="preset-hint">下拉会显示为选择栏；开关第一行为开启值，第二行为关闭值。</div>' +
                '</div>' +
            '</div>';
        }

        function populateModalList() {
            var $list = $('#preset_list');
            var $tabs = $('#preset_manage_tabs');
            var $empty = $('#preset_empty');
            $list.empty();
            $tabs.empty();
            if (!presetFields.length) {
                $empty.removeClass('d-none');
                return;
            }
            $empty.addClass('d-none');
            var packed = collectPresetGroups(presetFields);
            var groups = packed.groups;
            var groupOrder = packed.order;
            if (!presetManageActiveTab || groupOrder.indexOf(presetManageActiveTab) < 0) {
                presetManageActiveTab = groupOrder[0];
            }
            groupOrder.forEach(function(gName) {
                var count = groups[gName].length;
                $tabs.append(
                    $('<li class="nav-item" role="presentation"></li>').append(
                        $('<button type="button" class="nav-link' + (gName === presetManageActiveTab ? ' active' : '') + '"></button>')
                            .attr('data-manage-tab', gName)
                            .text(groupTabLabel(gName) + ' (' + count + ')')
                    )
                );
            });
            groupOrder.forEach(function(gName) {
                var $pane = $('<div class="preset-manage-pane' + (gName === presetManageActiveTab ? ' is-active' : '') + '"></div>')
                    .attr('data-manage-pane', gName);
                groups[gName].forEach(function(p, i) {
                    $pane.append(buildPresetCardHtml(p, i));
                });
                $list.append($pane);
            });
            if (!$('#preset-group-suggestions').length) {
                $('body').append('<datalist id="preset-group-suggestions"><option value="ResHub · 软件"></option><option value="ResHub · 视频"></option><option value="其他字段"></option></datalist>');
            }
            if (typeof window.hopeInitChoicesAll === 'function') {
                window.hopeInitChoicesAll($list.find('.preset-manage-pane.is-active')[0] || $list[0]);
            }
        }

        function loadPresetFields(cb) {
            $.get('<?= SELF ?>?act=article_edit&get_blog_fields', function(resp) {
                var arr = [];
                if (resp) {
                    if (hopeApiSuccess(resp.code) && resp.data) arr = resp.data;
                    else if (Array.isArray(resp)) arr = resp;
                    else if (resp.data && Array.isArray(resp.data)) arr = resp.data;
                }
                presetFields = (arr || []).map(normalizePreset);
                try { renderPresets(); } catch (e) {}
                if (cb) cb();
            }, 'json').fail(function() {
                console.error('预设字段加载失败');
                if (cb) cb();
            });
        }

        $('#presetModal').on('show.bs.modal', function() {
            loadPresetFields(function(){ populateModalList(); });
        });

        $(document).on('click', '#preset_box [data-preset-tab]', function(){
            var name = String($(this).attr('data-preset-tab') || '');
            if (!name) return;
            presetActiveTab = name;
            $('#preset_box [data-preset-tab]').removeClass('active');
            $(this).addClass('active');
            $('#preset_box [data-preset-pane]').removeClass('is-active');
            var $pane = $('#preset_box [data-preset-pane]').filter(function(){
                return $(this).attr('data-preset-pane') === name;
            }).addClass('is-active');
            if (typeof window.hopeInitChoicesAll === 'function' && $pane[0]) {
                window.hopeInitChoicesAll($pane[0]);
            }
        });

        $(document).on('click', '#preset_manage_tabs [data-manage-tab]', function(){
            var name = String($(this).attr('data-manage-tab') || '');
            if (!name) return;
            presetManageActiveTab = name;
            $('#preset_manage_tabs [data-manage-tab]').removeClass('active');
            $(this).addClass('active');
            $('#preset_list [data-manage-pane]').removeClass('is-active');
            var $pane = $('#preset_list [data-manage-pane]').filter(function(){
                return $(this).attr('data-manage-pane') === name;
            }).addClass('is-active');
            if (typeof window.hopeInitChoicesAll === 'function' && $pane[0]) {
                window.hopeInitChoicesAll($pane[0]);
            }
        });

        $('#preset_add').on('click', function(){
            syncPresetFieldsFromModal();
            presetFields.push(normalizePreset({
                key: '',
                label: '',
                type: 'text',
                group: presetManageActiveTab || '其他字段'
            }));
            populateModalList();
        });

        $(document).on('click', '#preset_list .preset-row-del', function(){
            syncPresetFieldsFromModal();
            var $card = $(this).closest('.preset-card');
            var idx = $('#preset_list .preset-card').index($card);
            if (idx >= 0) presetFields.splice(idx, 1);
            populateModalList();
        });

        $(document).on('change', '#preset_list .preset-type', function(){
            var $card = $(this).closest('.preset-card');
            var type = $(this).val();
            $card.toggleClass('is-choice', isChoiceType(type));
            $card.find('.preset-type-badge').text(typeLabel(type));
        });

        $(document).on('input', '#preset_list .preset-label, #preset_list .preset-key', function(){
            var $card = $(this).closest('.preset-card');
            var label = $.trim($card.find('.preset-label').val());
            var key = $.trim($card.find('.preset-key').val());
            $card.find('.preset-card-title strong').text(label || key || '未命名字段');
        });

        $(document).on('change', '#preset_list .preset-group-name', function(){
            // 改分组后重新按 Tab 归类
            syncPresetFieldsFromModal();
            var g = $.trim($(this).val()) || '其他字段';
            presetManageActiveTab = g;
            populateModalList();
        });

        $('#preset_save').on('click', function(){
            var arr = syncPresetFieldsFromModal().filter(function(p){ return !!p.key; });
            var $btn = $(this).prop('disabled', true);
            $.post('<?= SELF ?>?act=article_edit&save_blog_fields', {data: JSON.stringify(arr)}, function(resp){
                $btn.prop('disabled', false);
                if (resp && hopeApiSuccess(resp.code)) {
                    presetFields = (resp.data && Array.isArray(resp.data) ? resp.data : arr).map(normalizePreset);
                    try { renderPresets(); } catch (e) {}
                    var modalEl = document.getElementById('presetModal');
                    if (modalEl) {
                        var m = bootstrap.Modal.getInstance(modalEl);
                        if (m) m.hide();
                    }
                    if (typeof Toast !== 'undefined') Toast.fire({icon:'success', title:'保存成功'});
                } else {
                    if (typeof Toast !== 'undefined') Toast.fire({icon:'error', title:'保存失败'});
                    else alert('保存失败');
                }
            }).fail(function(){
                $btn.prop('disabled', false);
                if (typeof Toast !== 'undefined') Toast.fire({icon:'error', title:'保存失败'});
            });
        });

        // 开关：避免 hidden + checkbox 同名时未勾选仍提交两个值导致取错
        $(document).on('change', '#preset_box .form-switch input[type=checkbox]', function(){
            var $cb = $(this);
            var $hidden = $cb.siblings('input[type=hidden][name="' + $cb.attr('name') + '"]');
            if ($hidden.length) $hidden.prop('disabled', $cb.prop('checked'));
            var onLabel = $cb.val();
            var offLabel = $hidden.val() || '关闭';
            $cb.next('span').text($cb.prop('checked') ? onLabel : offLabel);
        });

        loadPresetFields();

    });

    
    function checkArticle(act) {
        var timeout = 60000;
        var alias = $.trim($("#alias").val());
        var title = $.trim($("#title").val());

        if(act == 1){
            if (alias != '' && 0 != isalias(alias)) {
                Toast.fire({ icon: 'error', title: '保存失败：链接别名错误！' });
                return;
            }
            // 距离上次保存成功时间小于三秒时不允许保存
            if ((new Date().getTime() - Cookies.get('saveTime')) < 3000) {
                Toast.fire({ icon: 'error', title: '保存失败：请勿频繁操作！' });
                return;
            }
            $("#save").attr("disabled", "disabled");
            $.post('<?= SELF ?>?act=article_edit&save', $("#editlog").serialize(), function (data) {
                if (hopeApiSuccess(data.code)) {
                    var payload = data.data;
                    var logid = (payload && typeof payload === 'object') ? payload.id : payload;
                    var previewUrl = (payload && payload.url) ? payload.url : ('<?= SITE_URL ?>?article=' + logid);
                    Cookies.set('saveTime', new Date().getTime()); // 把保存成功时间戳记录（或更新）到 cookie 中
                    $("#gid").val(logid);
                    $("#save").attr("disabled", false);
                    Toast.fire({ icon: 'success', title: '保存成功：<a href="' + previewUrl + '" target="_blank">预览文章</a>' });
                }else{
                    $("#save").attr("disabled", false);
                    Toast.fire({ icon: 'error', title: '保存失败：可能文章不可编辑或达到每日发文限额' });
                }
            });
        }else{
            if (isalias(alias) == 0) {
                return true;
            } else {
                Toast.fire({ icon: 'error', title: '保存失败：链接别名错误！' });
                $("#alias").focus();
                return false;
            }
        }
    }

</script>
</script>