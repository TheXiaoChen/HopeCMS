<?php defined('HOPE_ROOT') || exit('access denied!'); ?>
<style>.table td{ white-space: inherit;}</style>
<div class="row">
	<div class="col-12">
		<div class="card">
			<div class="card-header pb-0">
				<h5 class="mb-0">评论</h5>
				<div class="text-sm mb-0 d-flex align-items-center"><?php if ($hideCommNum > 0) : ?>
					<span class="me-2"><a href="<?= SELF ?>?act=comment&hide=y&<?= $addUrl_1 ?>">审核<?php $hidecmnum = User::haveEditPermission() ? $sta_cache['hidecomnum'] : $sta_cache[UID]['hidecommentnum'];
					if ($hidecmnum > 0)echo '(' . $hidecmnum . ')'; ?></a></span><?php endif ?>
					<span class="me-2">每页显示</span>
					<select class="form-select form-select-sm hope-choices-skip" style="width: auto;" name="perpage_num" onchange="changePerPage(this);">
						<?php if ($perPage != 10 && $perPage != 15 && $perPage != 20 && $perPage != 50 && $perPage != 100 && $perPage != 500): ?><option value="<?= $perPage ?>" selected><?= $perPage ?></option><?php endif ?>
						<option value="10" <?= ($perPage == 10) ? 'selected' : '' ?>>10</option>
						<option value="15" <?= ($perPage == 15) ? 'selected' : '' ?>>15</option>
						<option value="20" <?= ($perPage == 20) ? 'selected' : '' ?>>20</option>
						<option value="50" <?= ($perPage == 50) ? 'selected' : '' ?>>50</option>
						<option value="100" <?= ($perPage == 100) ? 'selected' : '' ?>>100</option>
						<option value="500" <?= ($perPage == 500) ? 'selected' : '' ?>>500</option>
					</select>
				</div>
			</div>
			<form id="form_log" action="<?= SELF ?>?act=comment" method="post" name="form_log">
				<div class="table-responsive">
					<table class="table table-flush">
						<thead class="thead-light">
							<tr>
								<th class="align-middle">
									<div class="form-check d-flex justify-content-center">
										<input class="form-check-input" type="checkbox" id="checkAll">
									</div>
								</th>
								<th>评论人</th>
								<th>内容</th>
								<th>文章</th>
								<th>时间</th>
								<th>操作</th>
							</tr>
						</thead>
						<tbody>
						<?php foreach ($comment as $key => $value):
							$ishide = $value['hide'] == 'y' ? '<span class="text-danger">[待审]</span>' : '';
							$mail = $value['mail'] ? "({$value['mail']})" : '';
							$ip = $value['ip'];
							$gid = $value['gid'];
							$cid = $value['cid'];
							$ip_info = $ip ? "<br />来自IP：{$ip}" : '';
							$comment = $value['comment'];
							$poster = !empty($value['uid']) ? '<a href="'.SELF.'?act=comment&uid=' . $value['uid'] . '">' . $value['poster'] . '</a>' : $value['poster'];
							$title = subString($value['title'], 0, 42);
							$hide = $value['hide'];
							$date = $value['date'];
							$top = $value['top'];
							doAction('adm_comment_display');
							?>
							<tr>
								<td class="align-middle" style="width: 10px;">
									<div class="form-check d-flex justify-content-center">
										<input class="ids form-check-input" type="checkbox" name="id[]" value="<?= $value['cid'] ?>">
									</div>
								</td>
								<td>
									<div class="d-flex px-2 py-1">
										<div>
											<img src="<?= $value['cover'] ?: SITE_URL . 'content/admin/img/cover.png' ?>" class="avatar avatar-lg me-3">
										</div>
										<div class="d-flex flex-column justify-content-center">
											<p class="text-xxs mb-0"><?= $poster ?><?= $mail ?><?php if ($top == 'y'): ?> <span class="badge badge-info" title="置顶"><i class="fa fa-list"></i> 置顶</span><?php endif ?><?= $ip_info ?></p>
											<p class="text-xs text-secondary mb-0"><?= $value['os'] ?> - <?= $value['browse'] ?></p>
										</div>
									</div>
								</td>
								<td class="align-middle font-weight-bold">
									<span class="my-2 text-xs">
										<a href="<?= Url::log($gid) ?>#comment-<?= $cid ?>" target="_blank">🔗</a>
										<?= $ishide ?>
										<?= $comment ?>
									</span>
								</td>
								<td class="align-middle font-weight-bold">
									<span class="my-2 text-xs">
										<a href="<?= Url::log($gid) ?>" target="_blank"><?= $title ?></a><br>
										<a href="<?= SELF ?>?act=comment&gid=<?= $gid ?>" class="badge badge-info">该文所有评论</a>
									</span>
								</td>
								<td class="align-middle font-weight-bold">
									<span class="my-2 text-xs"><?= $date ?></span>
								</td>
								<td class="align-middle font-weight-bold">
                                    <?php if ($value['hide'] === 'y' && User::haveEditPermission()): ?>
									<a class="badge badge-warning" href="<?= SELF ?>?act=comment&action=pub&id=<?= $cid ?>&token=<?= LoginAuth::genToken() ?>">审核</a>
                                    <?php endif ?>
									<a href="#" class="badge badge-success" data-bs-toggle="modal" data-bs-target="#commentModal" data-cid="<?= $cid ?>" data-comment="<?= $comment ?>" data-hide="<?= $value['hide'] ?>" >回复</a>
									<a href="javascript: hp_delete('<?= $ip ?>', 'commentbyip', '<?= LoginAuth::genToken() ?>');" class="badge badge-pill badge-danger">IP删</a>
									<a href="javascript: hp_delete('<?= $cid ?>', 'comment', '<?= LoginAuth::genToken() ?>');" class="badge badge-pill badge-danger">删除</a>
								</td>
							</tr>
						<?php endforeach ?>
						</tbody>
					</table>
				</div>
				<input name="operate" id="operate" value="" type="hidden" />
			</form>
			<div class="d-flex justify-content-between px-4">
				<div class="btn-group dropup">
					<button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
						<i class="fa fa-cog me-2"></i>操作
					</button>
					<ul class="dropdown-menu px-2 py-3">
						<li><a class="dropdown-item border-radius-md" href="javascript:comment('top')">
							<i class="fa fa-arrow-up me-2"></i>置顶</a></li>
						<li><a class="dropdown-item border-radius-md" href="javascript:comment('untop')">
							<i class="fa fa-arrow-down me-2"></i>取消置顶</a></li>
						<li><a class="dropdown-item border-radius-md text-primary" href="javascript:comment('hide')">
							<i class="fa fa-eye-slash me-2"></i>隐藏</a></li>
						<li><a class="dropdown-item border-radius-md text-primary" href="javascript:comment('pub')">
							<i class="fa fa-check me-2"></i>审核</a></li>
						<li><a class="dropdown-item border-radius-md text-danger" href="javascript:comment('delete')">
							<i class="fa fa-trash me-2"></i>删除</a></li>
					</ul>
				</div>
				
				<!-- 右侧分页 -->
				<nav aria-label="Page navigation example">
					<ul class="pagination justify-content-end mb-0">
						<?= $pageurl ?>
					</ul>
				</nav>
			</div>
		</div>
	</div>
</div>
<div class="modal fade" id="commentModal" tabindex="-1" role="dialog" aria-labelledby="Modal-form" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<form method="post" action="<?= SELF ?>?act=comment&reply">
				<div class="modal-header">
					<h5 class="modal-title" id="Modal-form">回复评论</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<div class="input-group mb-3">
						<textarea class="form-control" id="reply" name="reply" required></textarea>
					</div>
				</div>
				<div class="modal-footer">
					<input type="hidden" value="" name="cid" id="cid"/>
					<input type="hidden" value="" name="hide" id="hide"/>
					<button type="button" class="btn bg-gradient-secondary" data-bs-dismiss="modal" title="取消">取消</button>
					<button type="submit" class="btn bg-gradient-primary" title="回复">回复</button>
				</div>
			</form>
		</div>
	</div>
</div>
<script>
	$("#menu_content_manage").addClass('active');
	$("#menu_content").addClass('show');
	$("#menu_comment").addClass('active');
	$(function () {
		$('#commentModal').on('show.bs.modal', function (event) {
			var button = $(event.relatedTarget)
			var comment = button.html()
			var cid = button.data('cid')
			var hide = button.data('hide')
			var modal = $(this)
			modal.find('.modal-footer #cid').val(cid)
			modal.find('.modal-body #hide').val(hide)
		})
	});

	function comment(action) {
		if (getChecked('ids') === false) {
			Toast.fire({
				icon: 'warning',
				title: '请选择要操作的评论'
			});
			return;
		}

		if (action === 'delete') {
			Swal.fire({
				title: '确定要删除所选评论？',
				text: '彻底删除将无法恢复',
				icon: 'warning',
				showDenyButton: true,
				showCancelButton: true,
				confirmButtonText: "彻底删除",
				cancelButtonText: '取消',
				denyButtonText: '隐藏'
			}).then((result) => {
				if (result.isConfirmed) {
					$("#operate").val(action);
					$("#form_log").submit();
				} else if (result.isDenied) {
					$("#operate").val("hide");
					$("#form_log").submit();
				}
			});
			return;
		}
		$("#operate").val(action);
		$("#form_log").submit();
	}
	<?php if (Input::getStrVar('code') === 'show'): ?>Toast.fire({ icon: 'success', title: '审核成功' });
<?php endif ?><?php if (Input::getStrVar('code') === 'hide'): ?>Toast.fire({ icon: 'success', title: '隐藏成功' });
<?php endif ?><?php if (Input::getStrVar('code') === 'top'): ?>Toast.fire({ icon: 'success', title: '置顶成功' });
<?php endif ?><?php if (Input::getStrVar('code') === 'untop'): ?>Toast.fire({ icon: 'success', title: '取消置顶成功' });
<?php endif ?><?php if (Input::getStrVar('code') === 'edit'): ?>Toast.fire({ icon: 'error', title: '修改成功' });
<?php endif ?><?php if (Input::getStrVar('code') === 'rep'): ?>Toast.fire({ icon: 'error', title: '回复成功' });
<?php endif ?><?php if (Input::getStrVar('code') === 'a'): ?>Toast.fire({ icon: 'error', title: '请选择要执行操作的评论' });
<?php endif ?><?php if (Input::getStrVar('code') === 'b'): ?>Toast.fire({ icon: 'error', title: '请选择要执行的操作' });
<?php endif ?><?php if (Input::getStrVar('code') === 'c'): ?>Toast.fire({ icon: 'error', title: '回复内容不能为空' });
<?php endif ?><?php if (Input::getStrVar('code') === 'd'): ?>Toast.fire({ icon: 'error', title: '内容过长' });<?php endif ?><?php if (Input::getStrVar('code') === 'e'): ?>Toast.fire({ icon: 'error', title: '评论内容不能为空' });<?php endif ?>
</script>


