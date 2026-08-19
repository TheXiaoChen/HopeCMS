<?php defined('HOPE_ROOT') || exit('access denied!');
$keyword = isset($keyword) ? (string)$keyword : Input::getStrVar('keyword');
$token = LoginAuth::genToken();
?>
<style>
.hope-tag-page .tag-toolbar { display:flex; flex-wrap:wrap; gap:.5rem; align-items:center; justify-content:space-between; }
.hope-tag-page .tag-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(148px, 1fr));
	gap: .65rem;
}
.hope-tag-page .tag-card {
	display: flex;
	align-items: flex-start;
	gap: .5rem;
	border: 1px solid #e9ecef;
	border-radius: .65rem;
	background: #fff;
	padding: .65rem .7rem;
	transition: border-color .15s, box-shadow .15s, transform .15s;
	min-height: 72px;
}
.hope-tag-page .tag-card:hover {
	border-color: #c7d2fe;
	box-shadow: 0 4px 14px rgba(94, 114, 228, .12);
	transform: translateY(-1px);
}
.hope-tag-page .tag-card.is-selected {
	border-color: #5e72e4;
	background: #f7f8ff;
}
.hope-tag-page .tag-card .tag-check {
	flex: 0 0 auto;
	margin: .1rem 0 0;
	padding: 0;
	min-height: auto;
}
.hope-tag-page .tag-card .tag-check .form-check-input {
	width: .95rem;
	height: .95rem;
	margin: 0;
	float: none;
}
.hope-tag-page .tag-card-body {
	flex: 1 1 auto;
	min-width: 0;
	padding-left: 0;
}
.hope-tag-page .tag-title {
	display: block;
	font-size: .86rem;
	font-weight: 650;
	color: #344767;
	line-height: 1.3;
	text-decoration: none;
	word-break: break-word;
	cursor: pointer;
}
.hope-tag-page .tag-title:hover { color: #5e72e4; }
.hope-tag-page .tag-foot {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: .35rem;
	margin-top: .45rem;
}
.hope-tag-page .tag-count {
	font-size: .72rem;
	color: #8392ab;
	text-decoration: none;
}
.hope-tag-page .tag-count strong { color: #5e72e4; font-weight: 700; }
.hope-tag-page .tag-count.is-empty strong { color: #adb5bd; }
.hope-tag-page .tag-actions { display:flex; gap: .15rem; opacity: .55; }
.hope-tag-page .tag-card:hover .tag-actions { opacity: 1; }
.hope-tag-page .tag-actions .btn-link {
	padding: 0 .2rem;
	font-size: .78rem;
	line-height: 1;
	color: #8392ab;
}
.hope-tag-page .tag-actions .btn-link:hover { color: #5e72e4; }
.hope-tag-page .tag-actions .btn-link.danger:hover { color: #f5365c; }
.hope-tag-page .tag-seo-dot {
	display: inline-block;
	width: .4rem;
	height: .4rem;
	border-radius: 50%;
	background: #2dce89;
	margin-left: .25rem;
	vertical-align: middle;
}
.hope-tag-page .empty-state { padding: 2.5rem 1rem; text-align: center; color: #8392ab; }
.hope-tag-page .empty-state i { font-size: 2rem; opacity: .45; margin-bottom: .75rem; display: block; }
@media (max-width: 576px) {
	.hope-tag-page .tag-grid { grid-template-columns: repeat(auto-fill, minmax(132px, 1fr)); gap: .5rem; }
}
</style>
<div class="row hope-tag-page">
	<div class="col-12">
		<div class="card">
			<div class="card-header pb-0">
				<div class="d-lg-flex">
					<div>
						<h5 class="mb-0">标签管理</h5>
						<p class="text-sm mb-0">
							共 <strong><?= (int)$tags_count ?></strong> 个
							<?php if ($keyword !== ''): ?>
								· 「<?= htmlspecialchars($keyword) ?>」
							<?php endif; ?>
						</p>
					</div>
					<div class="ms-auto my-auto mt-lg-0 mt-4">
						<div class="ms-auto my-auto d-flex align-items-center flex-wrap gap-2">
							<form action="<?= SELF ?>" method="get" class="mb-0">
								<input type="hidden" name="act" value="tag">
								<div class="input-group">
									<span class="input-group-text text-body"><i class="fa fa-search" aria-hidden="true"></i></span>
									<input type="text" class="form-control" name="keyword" value="<?= htmlspecialchars($keyword) ?>" placeholder="搜索标签..." onfocus="focused(this)" onfocusout="defocused(this)">
								</div>
							</form>
							<button type="button" class="btn btn-outline-primary btn-xs text-sm mb-0" data-bs-toggle="modal" data-bs-target="#tagModal">
								<i class="fa fa-plus me-1"></i>创建
							</button>
							<div class="dropdown d-inline">
								<a href="javascript:;" class="btn bg-gradient-primary btn-xs text-sm mb-0 dropdown-toggle" data-bs-toggle="dropdown">管理</a>
								<ul class="dropdown-menu dropdown-menu-lg-start px-2 py-3">
									<li><a class="dropdown-item border-radius-md" href="<?= SELF ?>?act=article">文章</a></li>
									<li><hr class="horizontal dark my-2"></li>
									<li><a class="dropdown-item border-radius-md" href="<?= SELF ?>?act=article&hide=y">草稿</a></li>
									<li><hr class="horizontal dark my-2"></li>
									<li><a class="dropdown-item border-radius-md" href="<?= SELF ?>?act=category">分类</a></li>
									<li><hr class="horizontal dark my-2"></li>
									<li><a class="dropdown-item border-radius-md" href="<?= SELF ?>?act=tag">标签</a></li>
								</ul>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="card-body">
				<?php if ($tags): ?>
				<div class="tag-toolbar mb-3">
					<div class="d-flex align-items-center gap-2 flex-wrap">
						<div class="form-check mb-0">
							<input class="form-check-input" type="checkbox" id="tag_check_all">
							<label class="form-check-label text-sm" for="tag_check_all">全选</label>
						</div>
						<button type="button" id="bulk_delete_tags" class="btn btn-sm btn-outline-danger mb-0" disabled>
							批量删除
						</button>
						<span class="text-xs text-secondary" id="tag_selected_hint">未选择</span>
					</div>
					<nav aria-label="标签分页">
						<ul class="pagination pagination-sm mb-0">
							<?= $pageurl ?>
						</ul>
					</nav>
				</div>

				<div class="tag-grid">
				<?php foreach ($tags as $v):
					$count = empty($v['gid']) ? 0 : count(array_filter(explode(',', (string)$v['gid'])));
					$hasSeo = trim((string)($v['title'] ?? '')) !== '' || trim((string)($v['keywords'] ?? '')) !== '' || trim((string)($v['description'] ?? '')) !== '';
					$attrs = ' data-tid="' . (int)$v['tid'] . '"'
						. ' data-tagname="' . htmlspecialchars($v['tagname'], ENT_QUOTES) . '"'
						. ' data-title="' . htmlspecialchars((string)($v['title'] ?? ''), ENT_QUOTES) . '"'
						. ' data-keywords="' . htmlspecialchars((string)($v['keywords'] ?? ''), ENT_QUOTES) . '"'
						. ' data-description="' . htmlspecialchars((string)($v['description'] ?? ''), ENT_QUOTES) . '"';
				?>
					<div class="tag-card" data-card-tid="<?= (int)$v['tid'] ?>">
						<div class="form-check tag-check">
							<input class="form-check-input tag-checkbox" type="checkbox" value="<?= (int)$v['tid'] ?>" aria-label="选择 <?= htmlspecialchars($v['tagname']) ?>">
						</div>
						<div class="tag-card-body">
							<a href="javascript:;" class="tag-title" data-bs-toggle="modal" data-bs-target="#tagModal"<?= $attrs ?>>
								<?= htmlspecialchars($v['tagname']) ?>
								<?php if ($hasSeo): ?><span class="tag-seo-dot" title="已设置 SEO"></span><?php endif; ?>
							</a>
							<div class="tag-foot">
								<?php if ($count > 0): ?>
									<a class="tag-count" href="<?= SELF ?>?act=article&tagid=<?= (int)$v['tid'] ?>" title="查看文章"><strong><?= $count ?></strong> 篇</a>
								<?php else: ?>
									<span class="tag-count is-empty"><strong>0</strong> 篇</span>
								<?php endif; ?>
								<div class="tag-actions">
									<button type="button" class="btn btn-link" title="编辑" data-bs-toggle="modal" data-bs-target="#tagModal"<?= $attrs ?>><i class="fa fa-pen-to-square"></i></button>
									<?php if (method_exists('Url', 'tag')): ?>
									<a href="<?= Url::tag($v['tagname']) ?>" target="_blank" class="btn btn-link" title="前台"><i class="fa fa-external-link"></i></a>
									<?php endif; ?>
									<a href="javascript:;" class="btn btn-link danger hope-tag-del" data-tid="<?= (int)$v['tid'] ?>" title="删除"><i class="fa fa-trash"></i></a>
								</div>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
				</div>

				<div class="tag-toolbar mt-3">
					<div></div>
					<nav aria-label="标签分页">
						<ul class="pagination pagination-sm mb-0">
							<?= $pageurl ?>
						</ul>
					</nav>
				</div>
				<?php else: ?>
				<div class="empty-state">
					<i class="fa fa-tags"></i>
					<div class="mb-2"><?= $keyword !== '' ? '没有找到匹配的标签' : '还没有标签' ?></div>
					<p class="text-sm mb-3"><?= $keyword !== '' ? '试试其他关键词' : '写文章时可打标签，也可直接创建' ?></p>
					<?php if ($keyword !== ''): ?>
						<a href="<?= SELF ?>?act=tag" class="btn btn-sm btn-outline-secondary">清空搜索</a>
					<?php else: ?>
						<button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#tagModal">创建标签</button>
					<?php endif; ?>
				</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="tagModal" tabindex="-1" aria-labelledby="tagModalTitle" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-lg" role="document">
		<div class="modal-content">
			<form method="post" action="<?= SELF ?>?act=tag&update&token=<?= urlencode($token) ?>">
				<div class="modal-header">
					<h5 class="modal-title" id="tagModalTitle">创建标签</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div class="row g-3">
						<div class="col-md-6">
							<label class="form-label" for="tagname">标签名称 <span class="text-danger">*</span></label>
							<input class="form-control" type="text" name="tagname" id="tagname" required placeholder="如：教程">
						</div>
						<div class="col-md-6">
							<label class="form-label" for="title">SEO 标题</label>
							<input class="form-control" type="text" name="title" id="title" placeholder="可选，支持变量">
							<div class="form-text">可用：{{site_title}}、{{site_name}}、{{tag_name}}</div>
						</div>
						<div class="col-12">
							<label class="form-label" for="keywords">SEO 关键词</label>
							<input class="form-control" type="text" name="keywords" id="keywords" placeholder="多个关键词用逗号分隔">
						</div>
						<div class="col-12">
							<label class="form-label" for="description">SEO 描述</label>
							<textarea class="form-control" name="description" id="description" rows="3" placeholder="标签页的描述文案"></textarea>
						</div>
					</div>
				</div>
				<div class="modal-footer justify-content-between">
					<div>
						<button type="button" class="btn btn-outline-danger d-none" id="tag_modal_delete">删除</button>
					</div>
					<div>
						<input type="hidden" value="" id="tid" name="tid">
						<button type="button" class="btn bg-gradient-secondary" data-bs-dismiss="modal">取消</button>
						<button type="submit" class="btn bg-gradient-primary">保存</button>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>

<script>
$("#menu_content_manage").addClass('active');
$("#menu_content").addClass('show');
$("#menu_log").addClass('active');

(function () {
	var token = <?= json_encode($token) ?>;
	var delBase = <?= json_encode(SELF . '?act=tag&del&token=') ?>;

	function selectedIds() {
		var ids = [];
		$('.tag-checkbox:checked').each(function () { ids.push($(this).val()); });
		return ids;
	}

	function refreshSelectionUi() {
		var ids = selectedIds();
		var total = $('.tag-checkbox').length;
		$('#bulk_delete_tags').prop('disabled', ids.length === 0);
		$('#tag_selected_hint').text(ids.length ? ('已选 ' + ids.length + ' 项') : '未选择');
		$('#tag_check_all').prop('checked', total > 0 && ids.length === total);
		$('#tag_check_all').prop('indeterminate', ids.length > 0 && ids.length < total);
		$('.tag-card').each(function () {
			var tid = String($(this).data('card-tid'));
			$(this).toggleClass('is-selected', ids.indexOf(tid) >= 0);
		});
	}

	function confirmDelete(ids) {
		if (!ids.length) {
			Toast.fire({ icon: 'error', title: '请先选择要删除的标签' });
			return;
		}
		Swal.fire({
			title: ids.length > 1 ? ('确认删除选中的 ' + ids.length + ' 个标签？') : '确认删除该标签？',
			text: '删除后将解除与文章的关联',
			icon: 'warning',
			showCancelButton: true,
			confirmButtonText: '删除',
			cancelButtonText: '取消',
			confirmButtonColor: '#f5365c'
		}).then(function (result) {
			if (result.isConfirmed) {
				window.location = delBase + encodeURIComponent(token) + '&tid=' + ids.join(',');
			}
		});
	}

	$('#tagModal').on('show.bs.modal', function (event) {
		var button = $(event.relatedTarget);
		var tid = parseInt(button.data('tid'), 10) || 0;
		var modal = $(this);
		modal.find('#tagModalTitle').text(tid > 0 ? '编辑标签' : '创建标签');
		modal.find('#tagname').val(button.data('tagname') || '');
		modal.find('#title').val(button.data('title') || '');
		modal.find('#keywords').val(button.data('keywords') || '');
		modal.find('#description').val(button.data('description') || '');
		modal.find('#tid').val(tid || '');
		modal.find('#tag_modal_delete').toggleClass('d-none', !(tid > 0)).data('tid', tid);
	});

	$('#tag_check_all').on('change', function () {
		$('.tag-checkbox').prop('checked', this.checked);
		refreshSelectionUi();
	});
	$(document).on('change', '.tag-checkbox', refreshSelectionUi);
	$('#bulk_delete_tags').on('click', function () { confirmDelete(selectedIds()); });
	$(document).on('click', '.hope-tag-del', function (e) {
		e.preventDefault();
		e.stopPropagation();
		confirmDelete([String($(this).data('tid'))]);
	});
	$('#tag_modal_delete').on('click', function () {
		var tid = $(this).data('tid');
		if (tid) confirmDelete([String(tid)]);
	});

	refreshSelectionUi();

	<?php if (Input::getStrVar('code') === 'del'): ?>Toast.fire({ icon: 'success', title: '删除标签成功' });<?php endif ?>
	<?php if (Input::getStrVar('code') === 'edit'): ?>Toast.fire({ icon: 'success', title: '保存标签成功' });<?php endif ?>
	<?php if (Input::getStrVar('code') === 'empty'): ?>Toast.fire({ icon: 'error', title: '请填写标签名称' });<?php endif ?>
	<?php if (Input::getStrVar('code') === 'exist'): ?>Toast.fire({ icon: 'error', title: '标签已存在' });<?php endif ?>
})();
</script>
