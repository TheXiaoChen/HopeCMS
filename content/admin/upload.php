<?php defined('HOPE_ROOT') || exit('access denied!');
$hopeUploadToken = hope_admin_token_param();
?>
<link rel="stylesheet" href="<?= SITE_URL ?>content/admin/css/fancybox.css?v=<?= Option::HOPE_VERSION_TIMESTAMP ?>" type="text/css" />
<link rel="stylesheet" href="<?= SITE_URL ?>content/admin/css/plugins/dropzone.min.css?v=<?= Option::HOPE_VERSION_TIMESTAMP ?>" type="text/css" />
<div class="row">
	<div class="col-12">
		<div class="card">
			<div class="card-header p-3">
				<div class="d-lg-flex">
					<div>
						<h5 class="mb-0">附件资源</h5>
						<div>
							<a href="<?= SELF ?>?act=upload" class="btn btn-xs btn-success sid_0">全部资源</a>
							
							<?php foreach ($sorts as $key => $val): ?>
							<div class="btn-group">
								<a href="<?= SELF ?>?act=upload&sid=<?= $val['id'] ?>" class="btn btn-xs btn-success sid_<?= $val['id']  ?>"><?= $val['sortname'] ?></a>
								<button type="button" class="btn btn-xs btn-success dropdown-toggle dropdown-toggle-split sid_<?= $val['id'] ?>"" data-bs-toggle="dropdown" aria-expanded="false">
								</button>
								<ul class="dropdown-menu">
									<li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#editsortModal" data-id="<?= $val['id'] ?>" data-sortname="<?= $val['sortname'] ?>">编辑</a></li>
									<li><a class="dropdown-item text-danger" href="javascript:deleteSort(<?= $val['id'] ?>)">删除</a></li>
								</ul>
							</div>
							<?php endforeach ?>
							<button type="button" class="btn btn-xs btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#addSortModal">
								<i class="fa fa-plus"></i> 分类
							</button>
						</div>
					</div>
					<div class="ms-auto my-auto mt-lg-0 mt-4">
						<div class="ms-auto my-auto d-flex align-items-center">
							<div>
								<div class="input-group" id="datepicker" data-wrap="true" >
									<input class="form-control" style="width:auto" placeholder="查看指定日期的资源" data-input> 
									<a class="input-group-text" data-clear><i class="fa fa-gauge-high text-primary text-sm opacity-10"></i></a>
								</div>
							</div>
							<div class="ms-2">
								<form action="<?= SELF ?>" method="get">
									<input type="hidden" name="act" value="upload">
									<div class="input-group">
										<span class="input-group-text text-body"><i class="fa fa-search" aria-hidden="true"></i></span>
										<input type="text" class="form-control" placeholder="搜索资源文件名..." name="keyword" value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
									</div>
								</form>
							</div>
							<button type="button" class="btn btn-xs bg-gradient-primary text-sm ms-2" data-bs-toggle="modal" data-bs-target="#uploadModal">上传</button>
							<button type="button" class="btn btn-xs btn-outline-primary text-sm ms-1" data-bs-toggle="modal" data-bs-target="#externalModal">添加外部资源</button>
						</div>
					</div>
				</div>
			</div>
			<div class="card-body p-3">
				<?php 
				if($page <= 1){
					$updata = [];
					$db = Database::getInstance();
					$row = $db->once_fetch_array("SELECT COUNT(DISTINCT author) AS user_count FROM " . DB_PREFIX . "upload");
					$updata['user_count'] = (int)($row['user_count'] ?? 0);
					// 本月 / 总占用：按附件库记录汇总，不扫描磁盘（避免读取 .htaccess 等配置文件、降低内存）
					$monthStart = strtotime(gmdate('Y-m-01 00:00:00'));
					$monthEnd = strtotime(gmdate('Y-m-t 23:59:59'));
					$row = $db->once_fetch_array("SELECT COALESCE(SUM(filesize),0) AS month_bytes FROM " . DB_PREFIX . "upload WHERE addtime >= " . (int)$monthStart . " AND addtime <= " . (int)$monthEnd);
					$monthBytes = (int)($row['month_bytes'] ?? 0);
					$updata['Month_size'] = $monthBytes > 0 ? changeFileSize($monthBytes) : '无';
					$row = $db->once_fetch_array("SELECT COUNT(*) AS total_count, COALESCE(SUM(filesize),0) AS total_bytes FROM " . DB_PREFIX . "upload");
					$updata['total_count']  = (int)($row['total_count'] ?? 0);
					$totalBytes = (int)($row['total_bytes'] ?? 0);
					$updata['Total_size'] = $totalBytes > 0 ? changeFileSize($totalBytes) : '0 B';
				?>
				<div class="row">
					<div class="col-lg-3 col-6 text-center">
						<div class="border-dashed border-1 border-secondary border-radius-md py-3">
							<h6 class="text-primary mb-0">上传用户</h6>
							<h4 class="font-weight-bolder"><?= $updata['user_count'] ?></h4>
						</div>
					</div>
					<div class="col-lg-3 col-6 text-center">
						<div class="border-dashed border-1 border-secondary border-radius-md py-3">
							<h6 class="text-primary mb-0">本月上传</h6>
							<h4 class="font-weight-bolder"><?= $updata['Month_size'] ?></h4>
						</div>
					</div>
					<div class="col-lg-3 col-6 text-center">
						<div class="border-dashed border-1 border-secondary border-radius-md py-3">
							<h6 class="text-primary mb-0">附件资源</h6>
							<h4 class="font-weight-bolder"><?= $updata['total_count'] ?></h4>
						</div>
					</div>
					<div class="col-lg-3 col-6 text-center">
						<div class="border-dashed border-1 border-secondary border-radius-md py-3">
							<h6 class="text-primary mb-0">资源占用</h6>
							<h4 class="font-weight-bolder"><?= $updata['Total_size'] ?></h4>
						</div>
					</div>
				</div><?php } ?>
				<form id="form_log" action="<?= SELF ?>?act=upload&operate&<?= $hopeUploadToken ?>" method="post" >
					<div class="row mt-2">

						<?php foreach ($medias as $key => $value):
							$raw_file_url = getFileUrl($value['filepath']);
							$is_external = (stripos((string)$value['filepath'], 'http') === 0);
							// 真实源地址（完整链接）；外部资源保留查询参数
							$source_url = $is_external ? $raw_file_url : rmUrlParams($raw_file_url);
							// 公开下载：隐藏真实路径，name 对接 alias，免登录
							$public_url = SITE_URL . '?resource_alias=' . $value['alias'] . '&name=' . rawurlencode($value['alias']);
							// 登录下载：仅 alias，需登录
							$login_down_url = SITE_URL . '?resource_alias=' . $value['alias'];
							$media_name = $value['filename']; // 模型内已 htmlspecialchars
							$add_time = htmlspecialchars($value['addtime']);
							$mime_type = htmlspecialchars($value['mimetype']);
							$author_name = htmlspecialchars($user_cache[$value['author']]['name'] ?? '');
							$file_size = htmlspecialchars($value['attsize']);
							$download_count = (int)($value['download_count'] ?? 0);
							$aid = (int)$value['aid'];
							$author_id = (int)$value['author'];
							$media_sortid = (int)($value['sortid'] ?? 0);
							$media_sortname = $value['sortname'] !== '' ? $value['sortname'] : '未分类';
							$is_image = isImage($value['mimetype']);
							$is_zip = isZip($value['filename']);
							$source_js = json_encode($source_url, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
							$public_js = json_encode($public_url, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
							$login_js = json_encode($login_down_url, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
							$source_title = htmlspecialchars('源地址：' . $source_url, ENT_QUOTES, 'UTF-8');
							$public_title = htmlspecialchars('公开下载：' . $public_url, ENT_QUOTES, 'UTF-8');
							$login_title = htmlspecialchars('登录下载：' . $login_down_url, ENT_QUOTES, 'UTF-8');

							$fancybox = '';
							$media_fa = '';
							$media_icon = '';
							if ($is_image) {
								$media_icon = htmlspecialchars(getFileUrl($value['filepath_thum']));
								$fancybox = ' data-fancybox="gallery" data-src="'.htmlspecialchars($source_url, ENT_QUOTES, 'UTF-8').'" data-caption="'.$media_name.'"';
							} else {
								$media_fa = getMediaFaIcon($value['filename'], $value['mimetype']);
							}
						?>
						<div class="col-xl-4 col-lg-6 col-12">
							<div class="card mb-4 hover-shadow media-upload-card">
								<div class="media-upload-thumb<?= $is_image ? '' : ' is-icon' ?>">
									<label class="media-upload-check">
										<input class="form-check-input ids" type="checkbox" name="aids[]" value="<?= $key ?>">
									</label>
									<?php if ($is_external): ?><span class="media-upload-tag">外部</span><?php endif; ?>
									<?php if ($is_zip): ?><span class="media-upload-downs" title="登录下载次数"><i class="fa fa-download"></i> <?= $download_count ?></span><?php endif; ?>
									<?php if ($is_image): ?>
										<img src="<?= $media_icon ?>" alt="<?= $media_name ?>"<?= $fancybox ?>>
									<?php else: ?>
										<i class="fa <?= htmlspecialchars($media_fa) ?> media-upload-fa" aria-hidden="true"></i>
									<?php endif; ?>
								</div>
								<div class="media-upload-body">
									<a href="#" class="media-upload-name text-truncate" title="<?= $media_name ?>" data-bs-toggle="modal" data-bs-target="#editModal" data-name="<?= $media_name ?>" data-id="<?= $key ?>"><?= $media_name ?></a>
									<div class="media-upload-meta">
										<a href="<?= SELF ?>?act=upload&sid=<?= $media_sortid ?>" title="按分类筛选"><?= $media_sortname ?></a>
										<span><?= $mime_type ?></span>
										<span><?= $file_size ?></span>
										<a href="<?= SELF ?>?act=upload&uid=<?= $author_id ?>"><?= $author_name ?></a>
										<span><?= $add_time ?></span>
									</div>
									<div class="media-upload-actions">
										<button type="button" class="btn btn-xs btn-outline-secondary mb-0" onclick='copyToClipboard(<?= $source_js ?>, "源地址已复制")' title="<?= $source_title ?>"><i class="fa fa-globe"></i></button>
										<?php if ($is_zip): ?>
										<button type="button" class="btn btn-xs btn-outline-secondary mb-0" onclick='copyToClipboard(<?= $public_js ?>, "公开下载地址已复制")' title="<?= $public_title ?>"><i class="fa fa-link"></i></button>
										<button type="button" class="btn btn-xs btn-outline-secondary mb-0" onclick='copyToClipboard(<?= $login_js ?>, "登录下载地址已复制")' title="<?= $login_title ?>"><i class="fa fa-lock"></i></button>
										<?php endif; ?>
										<span class="media-upload-actions-sep"></span>
										<button type="button" class="btn btn-xs btn-outline-primary mb-0" onclick='window.open(<?= $source_js ?>, "_blank")' title="访问"><i class="fa fa-external-link"></i></button>
										<button type="button" class="btn btn-xs btn-outline-danger mb-0" onclick="hp_delete(<?= $aid ?>, 'upload', '<?= LoginAuth::genToken() ?>');" title="删除"><i class="fa fa-trash"></i></button>
									</div>
								</div>
							</div>
						</div>
						<?php endforeach ?>
					</div>
					<div class="d-flex justify-content-between px-4 py-3">
						<div class="dropdown d-inline">
							<button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
								<i class="fa fa-cog me-2"></i>操作
							</button>
							<div class="form-check form-check-inline">
								<input type="checkbox" class="form-check-input" id="checkAllItem">
								<label class="form-check-label" for="checkAllItem">全选</label>
							</div>
							<ul class="dropdown-menu px-2 py-3">
								<li>
									<a class="dropdown-item border-radius-md" href="javascript:batch_move_category()">
										<i class="fa fa-folder me-2"></i>移动分类
									</a>
								</li>
								<li>
									<a class="dropdown-item border-radius-md text-danger" href="javascript:pageact('delete')">
										<i class="fa fa-trash me-2"></i>删除
									</a>
								</li>
							</ul>
						</div>
						<nav aria-label="Page navigation example">
							<ul class="pagination justify-content-end mb-0">
								<?= $page_url ?>
							</ul>
						</nav>
					</div>
					<input name="sort" id="sort" value="" type="hidden"/>
					<input name="operate" id="operate" value="" type="hidden"/>
				</form>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="uploadModal" tabindex="-1" role="dialog" aria-labelledby="Modal-form" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="Modal-form">上传附件</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
				<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<div class="mb-3">
					<label for="upload_sid" class="form-label">分类</label>
					<select class="form-select" id="upload_sid" data-hope-choices data-preset="search">
						<option value="0">未分类</option>
						<?php foreach ($sorts as $key => $val): ?>
						<option value="<?= $val['id'] ?>"<?= ((int)$sid === (int)$val['id']) ? ' selected' : '' ?>><?= $val['sortname'] ?></option>
						<?php endforeach ?>
					</select>
				</div>
				<form action="<?= SELF ?>?act=upload&uploade&<?= $hopeUploadToken ?>" class="dropzone" id="my-dropzone">
					<div class="fallback">
						<input name="file" type="file" multiple />
					</div>
					<div class="dz-message">
						<i class="fa fa-cloud-upload-alt"></i>
						<p>将文件拖到此处或<span>点击上传</span></p>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="externalModal" tabindex="-1" role="dialog" aria-labelledby="externalModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<form method="post" action="<?= SELF ?>?act=upload&add_external&<?= $hopeUploadToken ?>">
				<div class="modal-header">
					<h5 class="modal-title" id="externalModalLabel">添加外部资源</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div class="mb-3">
						<label for="external_url" class="form-label">资源地址 <span class="text-danger">*</span></label>
						<input type="url" class="form-control" id="external_url" name="url" required placeholder="https://example.com/file.zip">
					</div>
					<div class="mb-3">
						<label for="external_name" class="form-label">显示名称</label>
						<input type="text" class="form-control" id="external_name" name="name" placeholder="留空则自动取文件名">
					</div>
					<div class="mb-3">
						<label for="external_sid" class="form-label">分类</label>
						<select class="form-select" id="external_sid" name="sid" data-hope-choices data-preset="search">
							<option value="0">未分类</option>
							<?php foreach ($sorts as $key => $val): ?>
							<option value="<?= $val['id'] ?>"<?= ((int)$sid === (int)$val['id']) ? ' selected' : '' ?>><?= $val['sortname'] ?></option>
							<?php endforeach ?>
						</select>
					</div>
					<div class="mb-0">
						<label for="external_mimetype" class="form-label">MIME 类型</label>
						<input type="text" class="form-control" id="external_mimetype" name="mimetype" placeholder="留空自动识别，如 application/zip">
						<div class="form-text">用于识别图片/压缩包等类型，影响缩略图与登录下载。</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
					<button type="submit" class="btn btn-primary">添加</button>
				</div>
			</form>
		</div>
	</div>
</div>

<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="Modal-form" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<form method="post" action="<?= SELF ?>?act=upload&update&<?= $hopeUploadToken ?>">
				<div class="modal-header">
					<h5 class="modal-title" id="Modal-form">编辑资源名称</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<div class="form-group">
                        <input name="name" id="name" class="form-control" value="">
                    </div>
				</div>
				<div class="modal-footer">
					<input type="hidden" name="id" id="id" value=""/>
					<button type="button" class="btn bg-gradient-secondary" data-bs-dismiss="modal" title="取消">取消</button>
					<button type="submit" class="btn bg-gradient-primary" title="保存">保存</button>
				</div>
			</form>
		</div>
	</div>
</div>
<div class="modal fade" id="addSortModal" tabindex="-1" role="dialog" aria-labelledby="addSortModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form method="post" action="<?= SELF ?>?act=upload&add_sort&<?= $hopeUploadToken ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSortModalLabel">添加新分类</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="sortname" class="form-label">分类名称</label>
                        <input type="text" class="form-control" id="sortname" name="sortname" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">添加</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="editsortModal" tabindex="-1" role="dialog" aria-labelledby="editsortModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form method="post" action="<?= SELF ?>?act=upload&update_sort&<?= $hopeUploadToken ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="editsortModal">编辑分类</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_sortname" class="form-label">分类名称</label>
                        <input type="text" class="form-control" id="edit_sortname" name="sortname" required>
                        <input type="hidden" id="id" name="id">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="<?= SITE_URL ?>content/admin/js/plugins/flatpickr.min.js?v=<?= Option::HOPE_VERSION_TIMESTAMP ?>"></script>
<script src="<?= SITE_URL ?>content/admin/js/plugins/dropzone.min.js?v=<?= Option::HOPE_VERSION_TIMESTAMP ?>"></script>
<script src="<?= SITE_URL ?>content/admin/js/fancybox.umd.js?v=<?= Option::HOPE_VERSION_TIMESTAMP ?>"></script>
<script type="text/javascript">
	$("#menu_system_manage").addClass('active');
	$("#menu_system").addClass('show');
	$("#menu_upload").addClass('active');
	$(".sid_<?= $sid ?>").removeClass('btn-success').addClass("btn-primary");
	Fancybox.bind('[data-fancybox]', {});
	flatpickr('#datepicker', {
		defaultDate: "<?= $date ?>",
		dateFormat: "Y/m/d",
		mode: "range",
		onChange: function(selectedDates, dateStr, instance) {
			if (selectedDates.length === 2) {
				var url = '<?= SELF ?>?act=upload&date=' + dateStr;
				window.location.href = url;
			}
		}
	});

	function copyToClipboard(url, successMsg) {
		var msg = successMsg || '链接已成功复制到剪贴板';
		function ok() {
			Toast.fire({ icon: 'success', title: msg });
		}
		function fail() {
			Toast.fire({ icon: 'error', title: '复制失败，请手动复制' });
		}
		function fallbackCopy() {
			var ta = document.createElement('textarea');
			ta.value = url;
			ta.setAttribute('readonly', '');
			ta.style.cssText = 'position:fixed;left:-9999px;top:0';
			document.body.appendChild(ta);
			ta.select();
			ta.setSelectionRange(0, ta.value.length);
			var copied = false;
			try {
				copied = document.execCommand('copy');
			} catch (e) {}
			document.body.removeChild(ta);
			copied ? ok() : fail();
		}
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(url).then(ok).catch(fallbackCopy);
		} else {
			fallbackCopy();
		}
	}

	Dropzone.autoDiscover = false;
	var uploadDzBase = '<?= SELF ?>?act=upload&uploade&<?= $hopeUploadToken ?>';
	function uploadDzUrl(sid) {
		return uploadDzBase + '&sid=' + (parseInt(sid, 10) || 0);
	}
	var uploadDropzone = new Dropzone("#my-dropzone", {
		url: uploadDzUrl(<?= (int)$sid ?>),
		maxFilesize: 20480,
		filesizeBase: 1024,
		timeout: 3600000,
		init: function () {
			this.on('queuecomplete', function () {
				var sid = $('#upload_sid').val() || 0;
				setTimeout(function () {
					window.location.href = '<?= SELF ?>?act=upload' + (sid > 0 ? '&sid=' + sid : '');
				}, 600);
			});
		}
	});
	$('#upload_sid').on('change', function () {
		uploadDropzone.options.url = uploadDzUrl($(this).val());
	});

	$('#editsortModal').on('show.bs.modal', function (event) {
		var button = $(event.relatedTarget);
		var id = button.data('id');
		var sortname = button.data('sortname');
		var modal = $(this);
		modal.find('.modal-body #edit_sortname').val(sortname);
		modal.find('.modal-body #id').val(id);
	});

	function deleteSort(sortId) {
		if (confirm('确定要删除这个分类吗？')) {
			window.location.href = '<?= SELF ?>?act=upload&delete_sort=' + sortId;
		}
	}


	$('#uploadModal').on('show.bs.modal', function (event) {
		uploadDropzone.options.url = uploadDzUrl($('#upload_sid').val());
		uploadDropzone.removeAllFiles(true);
	});
	$('#editModal').on('show.bs.modal', function (event) {
		var button = $(event.relatedTarget)
		var id = button.data('id')
		var name = button.data('name')
		var modal = $(this)
		modal.find('.modal-body #name').val(name)
		modal.find('.modal-footer #id').val(id)
	})




<?php if (Input::getStrVar('code') === 'del'): ?>Toast.fire({ icon: 'success', title: '删除成功' });<?php endif ?>
<?php if (Input::getStrVar('code') === 'mov'): ?>Toast.fire({ icon: 'success', title: '移动成功' });<?php endif ?>
<?php if (Input::getStrVar('code') === 'edit'): ?>Toast.fire({ icon: 'success', title: '修改成功' });<?php endif ?>
<?php if (Input::getStrVar('code') === 'add'): ?>Toast.fire({ icon: 'success', title: '添加成功' });<?php endif ?>
<?php if (Input::getStrVar('code') === 'a'): ?>Toast.fire({ icon: 'error', title: '名称不能为空' });<?php endif ?>
<?php if (Input::getStrVar('code') === 'badurl'): ?>Toast.fire({ icon: 'error', title: '请输入有效的 http/https 地址' });<?php endif ?>

// 全选/反选功能
$('#checkAllItem').on('change', function() {
    var checked = $(this).is(':checked');
    $('input[name="aids[]"]').prop('checked', checked);
});

// 若有单个checkbox取消勾选，则全选按钮也取消
$('input[name="aids[]"]').on('change', function() {
    var all = $('input[name="aids[]"]').length;
    var checked = $('input[name="aids[]"]:checked').length;
    $('#checkAllItem').prop('checked', all === checked);
});


// 批量移动分类功能
function batch_move_category() {
	if (getChecked('ids') === false) {
		Toast.fire({
			icon: 'warning',
			title: '请至少选择一个资源'
		});
		return;
	}

    // 显示移动分类的模态框或下拉选择
    var moveModal = `
        <div class="modal fade" id="moveCategoryModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">移动到分类</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <select class="form-select" id="targetSort" data-hope-choices data-preset="search">
                            <option value="0">未分类</option>
                            <?php foreach ($sorts as $key => $val): ?>
                            <option value="<?= $val['id'] ?>"><?= $val['sortname'] ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="button" class="btn btn-primary" id="confirmMove">确定</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // 如果模态框不存在，则添加到页面中
    if ($('#moveCategoryModal').length === 0) {
        $('body').append(moveModal);
    }
    
    // 显示模态框
    $('#moveCategoryModal').modal('show');
    
    // 确认移动
    $('#confirmMove').off('click').on('click', function() {
		var targetSort = $('#targetSort').val();
		$("#operate").val("move");
		$("#sort").val(targetSort);
		$("#form_log").submit();

		Toast.fire({
			icon: 'success',
			title: '移动成功'
		});
    });
}

// 批量删除功能
function pageact(action = 'delete') {
	if (getChecked('ids') === false) {
		Toast.fire({
			icon: 'warning',
			title: '请至少选择一个资源'
		});
		return;
	}

	
	// 确认删除
	if (!confirm('确定要删除选中的资源吗？')) {
		return;
	}
	$("#operate").val("del");
	$("#form_log").submit();

	Toast.fire({ icon: 'success', title: '删除成功' });
	setTimeout(function() {
		window.location.reload();
	}, 1000);        
}
</script>
