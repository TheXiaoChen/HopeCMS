<?php defined('HOPE_ROOT') || exit('access denied!'); ?>
<link href="<?= SITE_URL ?>content/admin/editor.md/css/editormd.css?v=<?= Option::HOPE_VERSION_TIMESTAMP ?>" rel="stylesheet" />
<style>
.fa { width:auto }
#preset_box .form-group { margin-bottom: auto; }
</style>
<div id="editor-md-dialog"></div>
<form action="<?= SELF ?>?act=page_edit" method="post" enctype="multipart/form-data" id="editlog" name="editlog">
    <div class="row">
        <div class="col-lg-8 col-12">
            <div class="card card-body">
                <div class="d-lg-flex">
                    <h5 class="mb-0"><?= $pagetitle ?></h5>
                    <div class="ms-auto">
                        <input name="token" id="token" value="<?= LoginAuth::genToken() ?>" type="hidden" />
                        <input type="hidden" name="gid" id="gid" value="<?= $pageId ?>"/>
                        <button type="button" id="save" class="btn bg-gradient-success btn-light m-0" onclick="checkPage(1);">保存</button>
                        <button type="submit" class="btn bg-gradient-primary m-0 ms-2" onclick="return checkPage();">提交</button>
                    </div>
                </div>
                <hr class="horizontal dark my-2">
                <input type="text" class="form-control" placeholder="文章标题" id="title" name="title" value="<?= $title ?>" required>
                <div class="d-flex align-items-center mt-2 mb-2">
                    <button type="button" class="btn btn-xs btn-outline-primary" data-bs-toggle="modal" data-bs-target="#mediaModal">
                        <i class="fa fa-folder-open"></i> 资源库
                    </button>
                    <?php doAction('adm_writelog_bar', isset($pageId) ? (int)$pageId : 0); ?>
                </div>
                <div id="logcontent" style="border-radius: .5rem;">
                    <textarea style="display:none;"><?= $content ?></textarea>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-12 mt-4 mt-lg-0">
            <div class="card card-body" id="option">
                <div class="nav-wrapper position-relative end-0">
                    <ul class="nav nav-pills nav-fill p-1" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link mb-0 px-0 py-1 active" data-bs-toggle="collapse" data-bs-target="#Basic" aria-controls="Basic" aria-selected="true">
                                <i class="fa fa-sliders text-sm me-2"></i> 基本选项
                            </a>
                        </li>
                    </ul>
                </div>
                <div id="Basic" class="accordion-collapse collapse show" data-bs-parent="#option"
                    aria-expanded="true">
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
                        <select class="form-control" name="template" id="template" placeholder="页面模板" data-hope-choices data-preset="search">
                        <?php
                        $hasPageDefault = false;
                        foreach ($customThemes as $v) {
                            if (($v['filename'] ?? '') === 'page') {
                                $hasPageDefault = true;
                                break;
                            }
                        }
                        if (!$hasPageDefault): ?>
                            <option value="page" <?= ($template === 'page' || $template === '') ? 'selected="selected"' : '' ?>>默认页面</option>
                        <?php endif;
                        foreach ($customThemes as $v) {
                            $tplFile = $v['filename'];
                            $label = ($v['comment'] !== '' ? $v['comment'] : $tplFile);
                            $sel = ($tplFile == $template || ($template === '' && $tplFile === 'page')) ? 'selected="selected"' : '';
                            echo '<option value="' . htmlspecialchars($tplFile) . '" ' . $sel . '>' . htmlspecialchars($label) . '</option>';
                        }
                        ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>链接别名</label>
                        <input name="alias" id="alias" class="form-control" value="<?= $alias ?>">
                    </div>
                    <div class="form-group row">
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <div class="form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" name="home_page" value="y" <?= $is_home ?>>
                                </div>
                                <span class="text-dark font-weight-bold text-sm">设为首页</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <div class="form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" name="remark" value="y" <?= $is_comment ?>>
                                </div>
                                <span class="text-dark font-weight-bold text-sm">允许评论</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="Other" class="accordion-collapse collapse" data-bs-parent="#option">
                    <div class="form-group">
                        <label>下载地址</label>
                        <input type="text" name="down" id="down" class="form-control" value="">
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
<div class="dropzone-previews" style="display: none;"></div>
<script src="<?= SITE_URL ?>content/admin/js/plugins/dropzone.min.js?v=<?= Option::HOPE_VERSION_TIMESTAMP ?>"></script>
<script src="<?= SITE_URL ?>content/admin/js/media-lib.js?v=<?= Option::HOPE_VERSION_TIMESTAMP ?>"></script>
<script src="<?= SITE_URL ?>content/admin/editor.md/editormd.js?v=<?= Option::HOPE_VERSION_TIMESTAMP ?>"></script>
<script>
    $("#menu_content_manage").addClass('active');
    $("#menu_content").addClass('show');
    $("#menu_page").addClass('active');
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
    });
    function checkPage(act) {
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
            $.post('<?= SELF ?>?act=page_edit&save', $("#editlog").serialize(), function (data) {
                if (hopeApiSuccess(data.code)) {
                    var payload = data.data;
                    var pageId = (payload && typeof payload === 'object') ? payload.id : payload;
                    var previewUrl = (payload && payload.url) ? payload.url : ('<?= SITE_URL ?>?article=' + pageId);
                    Cookies.set('saveTime', new Date().getTime()); // 把保存成功时间戳记录（或更新）到 cookie 中
                    $("#gid").val(pageId);
                    $("#save").attr("disabled", false);
                    Toast.fire({ icon: 'success', title: '保存成功：<a href="' + previewUrl + '" target="_blank">预览页面</a>' });
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