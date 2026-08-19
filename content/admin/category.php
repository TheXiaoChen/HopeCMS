<?php defined('HOPE_ROOT') || exit('access denied!'); ?>
			<div class="row">
				<div class="col-12">
					<div class="card">
						<div class="card-header pb-0">
							<div class="d-lg-flex">
								<div>
									<h5 class="mb-0">文章分类</h5>
									<p class="text-sm mb-0">分类共有<?= count($sorts) ?>个 </p>
								</div>
								<div class="ms-auto my-auto mt-lg-0 mt-4">
									<div class="ms-auto my-auto">
										<button type="button" class="btn btn-outline-primary btn-xs text-sm mb-0" data-bs-toggle="modal" data-bs-target="#categoryModal">创建分类</button>
										<div class="dropdown d-inline">
											<a href="javascript:;" class="btn bg-gradient-primary btn-xs text-sm mb-0 dropdown-toggle " data-bs-toggle="dropdown" >管理</a>
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
						<form act="<?= SELF ?>?act=category" method="post" name="form_log" id="form_log">
							<div class="table-responsive">
								<table class="table table-flush" id="categoryTable">
									<thead class="thead-light">
										<tr>
											<th>ID</th>
											<th>名称</th>
											<th>别名</th>
											<th>描述</th>
											<th>文章</th>
											<th>操作</th>
										</tr>
									</thead>
									<tbody>
									<?php foreach ($sorts as $key => $value):
										if ($value['pid'] != 0) continue;
										?>
										<tr data-id="<?= $value['sid'] ?>">
											<td class="align-middle">
												<div class="d-flex align-items-center">
													<div class="form-check">
														<input class="form-check-input" type="checkbox" name="sort[]" value="<?= $value['sid'] ?>">
													</div>
													<p class="text-xs font-weight-bold ms-2 mb-0"># <?= $value['sid'] ?></p>
												</div>
											</td>
											<td class="align-middle font-weight-bold">
												<span class="my-2 text-xs"><a href="<?= Url::sort($value['sid']) ?>" target="_blank">🔗</a> <a data-bs-toggle="modal" href="#"  data-bs-target="#categoryModal" data-sid="<?= $value['sid'] ?>" data-sortname="<?= $value['sortname'] ?>" data-alias="<?= $value['alias'] ?>" data-description="<?= $value['description'] ?>" data-keywords="<?= $value['keywords'] ?>" data-pid="<?= $value['pid'] ?>"  data-style="<?= $value['style'] ?>" data-logstyle="<?= $value['logstyle'] ?>"><?= $value['sortname'] ?></a></span>
											</td>
											<td class="align-middle font-weight-bold">
												<span class="my-2 text-xs"><?= $value['alias'] ?></span>
											</td>
											<td class="align-middle font-weight-bold">
												<span class="my-2 text-xs"><?= $value['description'] ?></span>
											</td>
											<td class="align-middle font-weight-bold">
												<span class="my-2 text-xs"><a href="<?= SELF ?>?act=article&sid=<?= $value['sid'] ?>"><?= $value['lognum'] ?></a></span>
											</td>
											<td class="align-middle">
												<a href="javascript: hp_delete(<?= $value['sid'] ?>, 'category', '<?= LoginAuth::genToken() ?>');" data-bs-toggle="tooltip" data-bs-original-title="Delete product"><i class="fa fa-trash text-secondary" aria-hidden="true"></i></a>
											</td>
										</tr>
										<?php
										$children = $value['children'];
										foreach ($children as $key):
											$value = $sorts[$key];
											?>
											<tr data-id="<?= $value['sid'] ?>">
												<td class="align-middle">
													<div class="d-flex align-items-center">
														<div class="form-check">
															<input class="form-check-input" type="checkbox" name="sort[]" value="<?= $value['sid'] ?>">
														</div>
														<p class="text-xs font-weight-bold ms-2 mb-0">#<a href="<?= SELF ?>?act=category_edit&sid=<?= $value['sid'] ?>"><?= $value['sid'] ?></a></p>
													</div>
												</td>
												<td class="align-middle font-weight-bold">
													<span class="my-2 text-xs">-- <a href="<?= Url::sort($value['sid']) ?>" target="_blank">🔗</a> <a data-bs-toggle="modal" href="#"  data-bs-target="#categoryModal" data-sid="<?= $value['sid'] ?>" data-sortname="<?= $value['sortname'] ?>" data-alias="<?= $value['alias'] ?>" data-description="<?= $value['description'] ?>" data-keywords="<?= $value['keywords'] ?>" data-pid="<?= $value['pid'] ?>"  data-style="<?= $value['style'] ?>" data-logstyle="<?= $value['logstyle'] ?>"><?= $value['sortname'] ?></a></span>
												</td>
												<td class="align-middle font-weight-bold">
													<span class="my-2 text-xs"><?= $value['alias'] ?></span>
												</td>
												<td class="align-middle font-weight-bold">
													<span class="my-2 text-xs"><?= $value['description'] ?></span>
												</td>
												<td class="align-middle font-weight-bold">
													<span class="my-2 text-xs"><a href="<?= SELF ?>?act=article&sid=<?= $value['sid'] ?>"><?= $value['lognum'] ?></a></span>
												</td>
												<td class="align-middle">
													<a href="javascript: hp_delete(<?= $value['sid'] ?>, 'sort', '<?= LoginAuth::genToken() ?>');" data-bs-toggle="tooltip" data-bs-original-title="Delete product"><i class="fa fa-trash text-secondary" aria-hidden="true"></i></a>
												</td>
											</tr>
										<?php endforeach ?>
									<?php endforeach ?>
									</tbody>
								</table>
							</div>
						</form>
					</div>
				</div>
			</div>


			<div class="modal fade" id="categoryModal" tabindex="-1" role="dialog" aria-labelledby="Modal-form" aria-hidden="true">
				<div class="modal-dialog modal-dialog-centered" role="document">
					<div class="modal-content">
						<form action="<?= SELF ?>?act=category&save" method="post">
							<div class="modal-header">
								<h5 class="modal-title" id="Modal-form">文章分类</h5>
								<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
								<span aria-hidden="true">&times;</span>
								</button>
							</div>
							<div class="modal-body">
								<label>分类名称 / 别名</label>
								<div class="input-group mb-3">
									<input class="form-control" type="text" name="sortname" id="sortname" required>
									<input class="form-control" name="alias" id="alias" >
									<span class="input-group-text" id="alias_msg">√</span>
								</div>
								<label>关键词</label>
								<div class="input-group mb-3">
									<input class="form-control" type="text" name="keywords" id="keywords">
								</div>
								<label>分类描述</label>
								<div class="input-group mb-3">
									<textarea class="form-control" name="description" id="description" ></textarea>
								</div>
								<label>父分类</label>
								<div class="input-group mb-3">
									<select class="form-control" name="pid" id="pid" data-hope-choices data-preset="search">
										<option value="0">无</option>
										<?php
										foreach ($sorts as $key => $value):
											if ($value['pid'] != 0) {
												continue;
											}
											?>
										<option value="<?= $key ?>"><?= $value['sortname'] ?></option>
										<?php endforeach ?>
									</select>
								</div>
								<label>分类模板 / 文章模板</label>
								<div class="input-group mb-3">
									<?php if ($sort_style):
										$sortListHtml = '<option value="log_list">默认</option>';
										foreach ($sort_style as $v) {
											$sortListHtml .= '<option value="' . str_replace('.php', '', $v['filename']) . '">' . ($v['comment']) . '</option>';
										}
										?>
									<select name="style" id="style" class="form-control" data-hope-choices data-preset="search"><?= $sortListHtml; ?></select>	
									<?php else: ?>
									<input class="form-control" id="style" name="style">
									<?php endif; ?>

									<?php if ($log_style):
										$sortListHtml = '<option value="echo_log">默认</option>';
										foreach ($log_style as $v) {
											$sortListHtml .= '<option value="' . str_replace('.php', '', $v['filename']) . '">' . ($v['comment']) . '</option>';
										}
										?>
									<select id="logstyle" name="logstyle" class="form-control" data-hope-choices data-preset="search"><?= $sortListHtml; ?></select>	
									<?php else: ?>
									<input class="form-control" id="logstyle" name="logstyle">
									<?php endif; ?>
								</div>
							</div>
							<div class="modal-footer">
								<input type="hidden" name="sid" id="sid"/>
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
				$("#menu_log").addClass('active');
				
				$("#alias").keyup(function () {
					var alias = $.trim($("#alias").val());
					if (1 == isalias(alias)) {
						$("#alias_msg").html('别名错误，应由字母、数字、下划线、短横线组成');
						$("#save").attr("disabled", "disabled");
					} else if (2 == isalias(alias)) {
						$("#alias_msg").html('别名错误，不能为纯数字');
						$("#save").attr("disabled", "disabled");
					} else if (3 == isalias(alias)) {
						$("#alias_msg").html('别名错误，不能为\'post\'或\'post-数字\'');
						$("#save").attr("disabled", "disabled");
					} else if (4 == isalias(alias)) {
						$("#alias_msg").html('别名错误，与系统链接冲突');
						$("#save").attr("disabled", "disabled");
					} else {
						$("#alias_msg").html('√');
						$("#save").attr("disabled", false);
					}
				});

				// 引入SortableJS CDN
				if (typeof Sortable === 'undefined') {
					var sortableScript = document.createElement('script');
					sortableScript.src = 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js';
					document.head.appendChild(sortableScript);
				}

				$(function () {
						// 分类拖拽排序
						function saveCategoryOrder() {
							var order = [];
							$('#categoryTable tbody tr[data-id]').each(function(){
								order.push($(this).data('id'));
							});
							$.post('<?= SELF ?>?act=category&taxis', {order: order, token: '<?= LoginAuth::genToken() ?>'}, function(res){
								Toast.fire({ icon: hopeApiSuccess(res.code)?'success':'error', title: res.msg||'排序已保存' });
							}, 'json');
						}
						function initSortable() {
							if (typeof Sortable === 'undefined') {
								setTimeout(initSortable, 200);
								return;
							}
							Sortable.create(document.querySelector('#categoryTable tbody'), {
								animation: 150,
								handle: '.align-middle',
								onEnd: function () {
									saveCategoryOrder();
								}
							});
						}
						initSortable();
					// 分类编辑
					$('#categoryModal').on('show.bs.modal', function (event) {
						var button = $(event.relatedTarget)
						var sid = button.data('sid')
						var sortname = button.data('sortname')
						var alias = button.data('alias')
						var description = button.data('description')
						var keywords = button.data('keywords')
						var pid = button.data('pid')
						var style = button.data('style') || 'log_list'
						var logstyle = button.data('logstyle') || 'echo_log'
						var sortimg = button.data('sortimg')
						var modal = $(this)
                        if(sid > 0) {
                            modal.find('.modal-header #Modal-form').text('编辑分类')
                        }else{
                            modal.find('.modal-header #Modal-form').text('创建分类')
                        }
						modal.find('.modal-body #sortname').val(sortname)
						modal.find('.modal-body #alias').val(alias)
						modal.find('.modal-body #description').val(description)
						modal.find('.modal-body #keywords').val(keywords)
						modal.find('.modal-body #pid').val(pid)
						modal.find('.modal-body #style').val(style)
						modal.find('.modal-body #logstyle').val(logstyle)
						modal.find('.modal-body #sortimg').val(sortimg)
						modal.find('.modal-footer #sid').val(sid)
					})
				});
				<?php if (Input::getStrVar('code') === 'taxis'): ?>Toast.fire({ icon: 'success', title: '排序更新成功' });
<?php endif ?><?php if (Input::getStrVar('code') === 'del'): ?>Toast.fire({ icon: 'success', title: '删除分类成功' });
<?php endif ?><?php if (Input::getStrVar('code') === 'edit'): ?>Toast.fire({ icon: 'success', title: '修改分类成功' });
<?php endif ?><?php if (Input::getStrVar('code') === 'save'): ?>Toast.fire({ icon: 'success', title: '分类保存成功' });
<?php endif ?><?php if (Input::getStrVar('code') === 'empty'): ?>Toast.fire({ icon: 'error', title: '分类名称不能为空' });
<?php endif ?><?php if (Input::getStrVar('code') === 'no'): ?>Toast.fire({ icon: 'error', title: '没有可排序的分类' });
<?php endif ?><?php if (Input::getStrVar('code') === 'format'): ?>Toast.fire({ icon: 'error', title: '别名格式错误' });
<?php endif ?><?php if (Input::getStrVar('code') === 'digit'): ?>Toast.fire({ icon: 'error', title: '别名不能纯数字' });
<?php endif ?><?php if (Input::getStrVar('code') === 'repeat'): ?>Toast.fire({ icon: 'error', title: '别名不能重复' });
<?php endif ?><?php if (Input::getStrVar('code') === 'oneself'): ?>Toast.fire({ icon: 'error', title: '不能设置自身为父分类' });
<?php endif ?><?php if (Input::getStrVar('code') === 'system'): ?>Toast.fire({ icon: 'error', title: '别名不得包含系统保留关键字' });<?php endif ?>
			</script>


