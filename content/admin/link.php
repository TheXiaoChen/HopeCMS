<?php defined('HOPE_ROOT') || exit('access denied!'); ?>
			<div class="row">
				<div class="col-12">
					<div class="card">
						<div class="card-header pb-0">
							<div class="d-lg-flex">
								<div>
									<h5>链接</h5>
								</div>
								<div class="ms-auto my-auto mt-lg-0 mt-4">
									<div class="ms-auto my-auto">
                                        <button type="button" class="btn btn-xs btn-outline-primary" data-bs-toggle="modal" data-bs-target="#linkModal">创建链接</button>
									</div>
								</div>
							</div>
						</div>
						<form act="" method="post" name="form_log">
							<div class="table-responsive">
								<table class="table table-flush">
									<thead class="thead-light">
										<tr>
											<th>名称</th>
											<th>链接</th>
											<th>操作</th>
										</tr>
									</thead>
									<tbody>
									<?php
									foreach ($links as $key => $value):
										doAction('adm_link_display');
										?>
										<tr style="cursor: move" draggable="true" data-id="<?= $value['id'] ?>">
											<td>
												<div class="d-flex px-2 py-1">
													<div>
														<img src="<?= $value['icon'] ? : SITE_URL.'content/admin/img/cover.png' ?>" class="avatar avatar-sm me-3">
													</div>
													<div class="d-flex flex-column justify-content-center">
														<h6 class="mb-0 text-sm"><a href="<?= $value['siteurl'] ?>" target="_blank">🔗</a> <a href="#" data-bs-toggle="modal" data-bs-target="#linkModal" data-linkid="<?= $value['id'] ?>" data-sitename="<?= $value['sitename'] ?>" data-siteurl="<?= $value['siteurl'] ?>" data-icon="<?= $value['icon'] ?>" data-description="<?= $value['description'] ?>"><?= $value['sitename'] ?></a> <?php if ($value['hide'] == 'y'): ?><span class="badge badge-warning">已隐藏</span><?php endif ?></h6>
														<p class="text-sm text-secondary mb-0"><?= $value['description'] ?></p>
													</div>
												</div>
											</td>
											<td class="align-middle">
												<a href="<?= $value['siteurl'] ?>" target="_blank"><?= subString($value['siteurl'], 0, 39) ?></a>
											</td>
											<td class="align-middle">
												<?php if ($value['hide'] == 'n'): ?>
													<a href="<?= SELF ?>?act=link&linkid=<?= $value['id'] ?>&hide" class="badge badge-primary">隐藏</a>
												<?php else: ?>
													<a href="<?= SELF ?>?act=link&linkid=<?= $value['id'] ?>&show" class="badge badge-warning">显示</a>
												<?php endif ?>
												<a href="javascript: hp_delete(<?= $value['id'] ?>, 'link', '<?= LoginAuth::genToken() ?>');" class="badge badge-danger"><i class="fa fa-trash text-secondary" aria-hidden="true"></i></a>
											</td>
										</tr>
									<?php endforeach ?>
									</tbody>
								</table>
							</div>
						</form>
					</div>
				</div>
			</div>
			

			<div class="modal fade" id="linkModal" tabindex="-1" role="dialog" aria-labelledby="Modal-form" aria-hidden="true">
				<div class="modal-dialog modal-dialog-centered" role="document">
					<div class="modal-content">
						<form action="<?= SELF ?>?act=link&save" method="post">
							<div class="modal-header">
								<h5 class="modal-title" id="Modal-form">链接</h5>
								<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
								<span aria-hidden="true">&times;</span>
								</button>
							</div>
							<div class="modal-body">
								<label>名称 / 地址</label>
								<div class="input-group mb-3">
									<input class="form-control" type="text" name="sitename" id="sitename" required>
									<input class="form-control" type="text" name="siteurl" id="siteurl" required>
								</div>
								<label>图标URL</label>
								<div class="input-group mb-3">
									<input class="form-control" type="text" name="icon" id="icon">
								</div>
								<label>网站描述</label>
								<div class="input-group mb-3">
									<textarea class="form-control" name="description" id="description" ></textarea>
								</div>
							</div>
							<div class="modal-footer">
								<input type="hidden" name="linkid" id="linkid"/>
								<button type="button" class="btn bg-gradient-secondary" data-bs-dismiss="modal" title="取消">取消</button>
								<button type="submit" class="btn bg-gradient-primary" title="保存">保存</button>
							</div>
						</form>
					</div>
				</div>
			</div>
			<script>
				$("#menu_content_manage").addClass('active');
				$("#menu_content").addClass('show');
				$("#menu_link").addClass('active');
				$(function () {
					// 分类编辑
					$('#linkModal').on('show.bs.modal', function (event) {
						var button = $(event.relatedTarget)
						var linkid = button.data('linkid')
						var sitename = button.data('sitename')
						var siteurl = button.data('siteurl')
						var icon = button.data('icon')
						var description = button.data('description')
						var modal = $(this)
                        if(linkid > 0) {
                            modal.find('.modal-header #Modal-form').text('编辑链接')
                        }else{
                            modal.find('.modal-header #Modal-form').text('创建链接')
                        }
						modal.find('.modal-body #sitename').val(sitename)
						modal.find('.modal-body #siteurl').val(siteurl)
						modal.find('.modal-body #icon').val(icon)
						modal.find('.modal-body #description').val(description)
						modal.find('.modal-footer #linkid').val(linkid)
					})
				});
				// 行拖拽排序（HTML5 Drag & Drop + jQuery）
				(function () {
					// 添加样式
					$('<style>').text('tr.dragging{opacity:0.5;}').appendTo('head');

					var $tbody = $('table.table-flush tbody');
					var $dragged = null;

					// 启用拖拽开始
					$tbody.on('dragstart', 'tr[draggable]', function (e) {
						$dragged = $(this);
						e.originalEvent.dataTransfer.effectAllowed = 'move';
						try { e.originalEvent.dataTransfer.setData('text/plain', ''); } catch (ex) {}
						$dragged.addClass('dragging');
					});

					// 拖拽结束：移除样式并保存顺序
					$tbody.on('dragend', 'tr[draggable]', function (e) {
						$(this).removeClass('dragging');
						// 收集顺序
						var order = [];
						$tbody.find('tr[data-id]').each(function () {
							order.push($(this).data('id'));
						});
						if (order.length) {
							// 发送到后端保存（使用现有接口 act=link_taxis，传入 link[]）
							var data = {token: '<?= LoginAuth::genToken() ?>'};
							// jQuery 会将数组序列化为 link[]=id1&link[]=id2...
							data['link[]'] = order;
							$.post('<?= SELF ?>?act=link&taxis', data)
								.done(function (res) {
									if (res.code == '1') {
										Toast.fire({icon: 'success', title: '排序已保存'});
									}else{
										Toast.fire({icon: 'error', title: res.msg});
									}
								})
								.fail(function () {
									Toast.fire({icon: 'error', title: '保存排序失败'});
								});
						}
						$dragged = null;
					});

					// 拖动经过目标行时调整位置
					$tbody.on('dragover', 'tr[draggable]', function (e) {
						e.preventDefault();
						if (!$dragged) return;
						var $target = $(this);
						if ($target[0] === $dragged[0]) return;
						var relY = e.originalEvent.clientY - $target.offset().top;
						var height = $target.outerHeight();
						if (relY > height / 2) {
							$target.after($dragged);
						} else {
							$target.before($dragged);
						}
					});

					// 确保 tbody 本身也允许 drop
					$tbody.on('dragover', function (e) { e.preventDefault(); });
				})();
				<?php if (Input::getStrVar('code') === 'del'): ?>Toast.fire({ icon: 'success', title: '删除成功' });
<?php endif ?><?php if (Input::getStrVar('code') === 'save'): ?>Toast.fire({ icon: 'success', title: '保存成功' });
<?php endif ?><?php if (Input::getStrVar('code') === 'empty'): ?>Toast.fire({ icon: 'error', title: '名称和地址不能为空' });<?php endif ?>

			</script>