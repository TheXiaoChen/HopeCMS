<?php defined('HOPE_ROOT') || exit('access denied!'); ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <div class="d-lg-flex">
                            <div>
                                <h5 class="mb-0"><a href="<?= SELF ?>?act=user" >用户</a></h5>
                                <div class="text-sm mb-0 d-flex align-items-center">
                                    <span class="me-2">用户共有<?= $userCount ?>位</span>
                                    <span class="me-2">每页显示</span>
                                    <select class="form-select form-select-sm hope-choices-skip" style="width: auto;" name="perpage_num" onchange="changePerPage(this);">
                                        <?php if($perPage != 10 && $perPage != 15 && $perPage != 20 && $perPage != 50 && $perPage != 100 && $perPage != 500): ?><option value="<?= $perPage ?>" selected ><?= $perPage ?></option><?php endif?>
                                        <option value="10" <?= ($perPage == 10) ? 'selected' : '' ?>>10</option>
                                        <option value="15" <?= ($perPage == 15) ? 'selected' : '' ?>>15</option>
                                        <option value="20" <?= ($perPage == 20) ? 'selected' : '' ?>>20</option>
                                        <option value="50" <?= ($perPage == 50) ? 'selected' : '' ?>>50</option>
                                        <option value="100" <?= ($perPage == 100) ? 'selected' : '' ?>>100</option>
                                        <option value="500" <?= ($perPage == 500) ? 'selected' : '' ?>>500</option>
                                    </select>
                                </div>
                            </div>

								<div class="ms-auto my-auto mt-lg-0 mt-4">
									<div class="ms-auto my-auto d-flex align-items-center">
										<div>
											<div class="input-group">
												<span class="input-group-text text-body"><i class="fa fa-search" aria-hidden="true"></i></span>
												<input type="text" class="form-control" placeholder="输入用户昵称搜索..." onfocus="focused(this)" onfocusout="defocused(this)">
											</div>
										</div>
                                        <button type="button" class="btn btn-outline-primary btn-xs ms-2" data-bs-toggle="modal" data-bs-target="#userModal"><i class="fa fa-user-plus text-success text-sm opacity-10"></i></button>
										<div class="dropdown d-inline ms-2">
											<a href="javascript:;" class="btn bg-gradient-primary btn-xs text-sm dropdown-toggle " data-bs-toggle="dropdown" id="navbarDropdownMenuLink2">显示</a>
											<ul class="dropdown-menu dropdown-menu-lg-start px-2 py-3" aria-labelledby="navbarDropdownMenuLink2" data-popper-placement="left-start">
												<li><a class="dropdown-item border-radius-md" href="<?= SELF ?>?act=user&order=update">最近活跃</a></li>
												<li><hr class="horizontal dark my-2"></li>
												<li><a class="dropdown-item border-radius-md" href="<?= SELF ?>?act=user&order=date">最新注册</a></li>
												<li><hr class="horizontal dark my-2"></li>
												<li><a class="dropdown-item border-radius-md" href="<?= SELF ?>?act=user&order=admin">管理优先</a></li>
												<li><hr class="horizontal dark my-2"></li>
												<li><a class="dropdown-item border-radius-md" href="<?= SELF ?>?act=user&order=forbid">禁用优先</a></li>
											</ul>
										</div></div>
								</div>
							</div>
						</div>

						<form act="<?= SELF ?>?act=user" method="post" name="form_log" id="form_log">
							<div class="table-responsive">
								<table class="table table-flush">
									<thead class="thead-light">
										<tr>
											<th class="align-middle">
												<div class="form-check d-flex justify-content-center">
													<input class="form-check-input" type="checkbox" id="checkAll">
												</div>
											</th>
											<th>昵称</th>
                                            <th>邮箱</th>
											<th>登录地址</th>
                                            <th>活跃时间</th>
                                            <th>注册时间</th>
											<th>操作</th>
										</tr>
									</thead>
									<tbody>
									<?php foreach ($users as $user):
                                        // 设置默认头像
                                        $avatar = hope_avatar_url($user_cache[$user['uid']]['avatar'] ?? '', './content/admin/img/avatar.png');
                                        // 判断用户是否被禁用
                                        $isForbid = $user['state'] == 1;
                                        // 获取用户的日志数量，默认为 0
                                        $userLogNum = isset($sta_cache[$user['uid']]['lognum']) ? $sta_cache[$user['uid']]['lognum'] : 0;
										?>

                                        <tr>
											<td class="align-middle" style="width: 10px;">
												<div class="form-check d-flex justify-content-center">
													<input class="ids form-check-input" type="checkbox" name="id[]" value="<?= $user['uid'] ?>">
												</div>
											</td>
                                            <td>
                                                <div class="d-flex px-2 py-1">
                                                    <div>
                                                        <img src="<?= htmlspecialchars($avatar) ?>" class="avatar avatar-sm me-3" title="ID:<?= $user['uid'] ?>">
                                                    </div>
                                                    <div class="d-flex flex-column justify-content-center">
                                                        <h6 class="mb-0 text-sm"><a href="#" data-bs-toggle="modal" data-bs-target="#userModal" data-uid="<?= $user['uid'] ?>" data-email="<?= htmlspecialchars($user['email']) ?>" data-role="<?= $user['role'] ?>" data-username="<?= $user['login'] ?>" data-nickname="<?= $user['name'] ?>" data-description="<?= $user['description'] ?>" data-balance="<?= htmlspecialchars((string)($user['balance'] ?? '0')) ?>" data-api-password="<?= htmlspecialchars((string)($user['api_password'] ?? '')) ?>"><?= empty($user['name']) ? $user['login'] : htmlspecialchars($user['name']) ?></a> <span class="small"><?= htmlspecialchars($user['role']) ?></span> <?php if ($isForbid): ?><span class="badge badge-warning">已禁用</span><?php endif ?></h6>
                                                        <p class="text-xs text-secondary mb-0">
                                                            <span class="small">ID：<?= $user['uid'] ?></span>
                                                        <?php if ($userLogNum > 0): ?>
                                                            <span class="small mr-2"> 文章：<a href="<?= SELF ?>?act=article&uid=<?= $user['uid'] ?>"><?= $userLogNum ?></a></span>
                                                        <?php endif ?>
                                                        <?php if ($user['credits'] > 0): ?>
                                                            <span class="small"> 积分：<?= $user['credits'] ?></span>
                                                        <?php endif ?>
                                                            <span class="small"> 余额：¥<?= number_format((float)($user['balance'] ?? 0), 2) ?></span>
                                                        <?php doAction(HopeHooks::ADM_USER_DISPLAY, $user); ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td><?= htmlspecialchars($user['ip']) ?></td>
                                            <td><?= $user['update_time'] ?></td>
                                            <td><?= $user['create_time'] ?></td>
                                            <td>
                                                <?php if (UID!= $user['uid']): ?>
                                                    <a href="javascript: hp_delete(<?= $user['uid'] ?>, 'del_user', '<?= LoginAuth::genToken() ?>');" class="badge badge-danger">删除</a>
                                                    <?php if ($isForbid): ?>
                                                        <a href="<?= SELF ?>?act=user&unforbid&uid=<?= $user['uid'] ?>&token=<?= LoginAuth::genToken() ?>" class="badge badge-success">解禁</a>
                                                    <?php else: ?>
                                                        <a href="javascript: hp_delete(<?= $user['uid'] ?>, 'forbid_user', '<?= LoginAuth::genToken() ?>');" class="badge badge-warning">禁用</a>
                                                    <?php endif ?>
                                                <?php endif ?>
                                            </td>
                                        </tr>
									<?php endforeach ?>
									</tbody>
								</table>
							</div>
							<input type="hidden" name="operate" id="operate" value=""/>
						</form>

						<div class="d-flex justify-content-between px-4 py-3">
							<div class="dropdown d-inline">
								<button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
									<i class="fa fa-cog me-2"></i>操作
								</button>
								<ul class="dropdown-menu px-2 py-3">
									<li><a class="dropdown-item border-radius-md text-warning" href="javascript:useract('forbid')">
											<i class="fa fa-lock me-2"></i>禁用
										</a></li>
									<li><a class="dropdown-item border-radius-md" href="javascript:useract('unforbid')">
											<i class="fa fa-lock-open me-2"></i>  解用
										</a></li>
									<li><hr class="horizontal dark my-2"></li>
									<li><a class="dropdown-item border-radius-md text-danger" href="javascript:useract('delete')">
											<i class="fa fa-trash me-2"></i>删除
										</a></li>
								</ul>
							</div>
							
							<!-- 右侧分页 -->
							<nav aria-label="Page navigation example">
								<ul class="pagination justify-content-end mb-0">
									<?= $pageurl ?>
								</ul>
							</nav>
						</div>




			<div class="modal fade" id="userModal" tabindex="-1" role="dialog" aria-labelledby="Modal-form" aria-hidden="true">
				<div class="modal-dialog modal-dialog-centered" role="document">
					<div class="modal-content">
						<form method="post" action="<?= SELF ?>?act=user&save">
							<div class="modal-header">
								<h5 class="modal-title" id="Modal-form">添加用户</h5>
								<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
								<span aria-hidden="true">&times;</span>
								</button>
							</div>
							<div class="modal-body">
								<div class="row g-2 mb-3">
									<div class="col-md-7">
										<label class="form-label text-sm mb-1" for="email">登录邮箱</label>
										<input type="text" name="email" id="email" class="form-control" placeholder="登录邮箱">
									</div>
									<div class="col-md-5">
										<label class="form-label text-sm mb-1" for="role">用户角色</label>
										<select name="role" id="role" class="form-control" data-hope-choices title="注册用户：可以发文投稿、管理自己的文章、图文资源
内容编辑：负责全站文章、资源、评论等内容的管理
管理员：拥有站点全部管理权限，可以管理用户、进行系统设置等">
											<option value="writer">注册用户</option>
											<option value="editor">内容编辑</option>
											<option value="admin">管理员</option>
										</select>
										<small class="text-muted d-none" id="roleLockHint">创始人账户角色不可修改</small>
									</div>
								</div>
								<div class="input-group mb-3">
									<input type="text" name="username" id="username" class="form-control" placeholder="用户名(为空则使用邮箱登录)">
									<input type="text" name="nickname" id="nickname" class="form-control" placeholder="用户昵称(为空则随机生成)">
								</div>
								<div class="mb-3">
									<textarea class="form-control" name="description" id="description" rows="3" placeholder="个人描述..."></textarea>
								</div>
								<div class="input-group mb-3">
									<input type="password" name="password" id="password" class="form-control" placeholder="新密码(不修改请留空)">
									<input type="password" name="password2" id="password2" class="form-control" placeholder="再次输入新密码">
								</div>
								<div class="mb-3 hope-user-balance-field" style="display:none;">
									<label class="form-label text-sm mb-1" for="user_balance">账户余额</label>
									<div class="input-group">
										<span class="input-group-text">¥</span>
										<input type="number" step="0.01" min="0" name="balance" id="user_balance" class="form-control" placeholder="0.00">
									</div>
								</div>
								<?php doAction(HopeHooks::ADM_USER_FORM); ?>
							</div>
							<div class="modal-footer">
								<input type="hidden" name="uid" id="uid" value=""/>
								<button type="button" class="btn bg-gradient-secondary" data-bs-dismiss="modal" title="取消">取消</button>
								<button type="submit" class="btn bg-gradient-primary" title="保存">保存</button>
							</div>
						</form>
					</div>
				</div>
			</div>

<script>
				$("#menu_system_manage").addClass('active');
				$("#menu_system").addClass('show');
				$("#menu_user").addClass('active');
				function useract(action) {
					if (getChecked('ids') === false) {
						Toast.fire({
							icon: 'warning',
							title: '请选择要操作的用户'
						});
						return;
					}

					if (action === 'forbid') {
						Swal.fire({
							title: '',
							text: '封禁所选用户？',
							icon: 'warning',
							showCancelButton: true,
							cancelButtonText: '取消',
							confirmButtonText: '确定'
						}).then((result) => {
							if (result.isConfirmed) {
								$("#operate").val("forbid");
								$("#form_log").submit();
							}
						});
						return;
					}

					if (action === 'unforbid') {
						Swal.fire({
							title: '',
							text: '解禁所选用户？',
							icon: 'warning',
							showCancelButton: true,
							cancelButtonText: '取消',
							confirmButtonText: '确定'
						}).then((result) => {
							if (result.isConfirmed) {
								$("#operate").val("unforbid");
								$("#form_log").submit();
							}
						});
						return;
					}

					$("#operate").val(action);
					$("#form_log").submit();
				}
				$(function () {
                    setTimeout(hideActived, 3600);
                    $("#menu_user").addClass('active');
                    // 分类编辑
					$('#userModal').on('show.bs.modal', function (event) {
						var button = $(event.relatedTarget)
						var uid = button.data('uid')
						var email = button.data('email')
						var role = button.data('role')
						var username = button.data('username')
						var nickname = button.data('nickname')
						var description = button.data('description')
						var modal = $(this)
                        if(uid > 0) {
                            modal.find('.modal-header #Modal-form').text('编辑用户信息')
                            modal.find('.modal-body #nickname').removeClass('d-none')
                            modal.find('.modal-body #description').closest('.mb-3').removeClass('d-none')
                        }else{
                            modal.find('.modal-header #Modal-form').text('新增用户信息')
                            modal.find('.modal-body #nickname').addClass('d-none')
                            modal.find('.modal-body #description').closest('.mb-3').addClass('d-none')
                        }
                        var isFounder = parseInt(uid, 10) === 1
						modal.find('.modal-body #email').val(email || '')
						var roleVal = role || 'writer'
						if (['admin', 'editor', 'writer'].indexOf(roleVal) < 0) {
							roleVal = 'writer'
						}
						var roleEl = modal.find('.modal-body #role')[0]
						if (roleEl) {
                            roleEl.disabled = isFounder
							$(roleEl).val(roleVal)
							if (typeof hopeRefreshChoices === 'function') {
								hopeRefreshChoices(roleEl)
							}
							if (roleEl._hopeChoices && typeof roleEl._hopeChoices.setChoiceByValue === 'function') {
								roleEl._hopeChoices.setChoiceByValue(roleVal)
							}
						}
                        modal.find('#roleLockHint').toggleClass('d-none', !isFounder)
						modal.find('.modal-body #username').val(username || '')
						modal.find('.modal-body #nickname').val(nickname || '')
						modal.find('.modal-body #description').val(description || '')
						modal.find('.modal-footer #uid').val(uid || '')
						var balance = button.data('balance')
						var apiPassword = button.data('api-password')
						var $bal = modal.find('#user_balance')
						$bal.val(uid > 0 ? (balance !== undefined && balance !== '' ? balance : '0') : '')
						modal.find('.hope-user-balance-field').toggle(uid > 0)
						var $api = modal.find('#hope_user_api_password')
						if ($api.length) {
							$api.val(uid > 0 ? (apiPassword || '') : '')
							$api.closest('.hope-user-api-field').toggle(uid > 0)
						}
					})
					$('#userModal').on('shown.bs.modal', function () {
						var roleEl = document.getElementById('role')
						if (roleEl && typeof hopeRefreshChoices === 'function') {
							hopeRefreshChoices(roleEl)
						}
					})
				});

<?php if (Input::getStrVar('code') === 'del'): ?>Toast.fire({ icon: 'success', title: '删除成功' });<?php endif ?>
<?php if (Input::getStrVar('code') === 'add'): ?>Toast.fire({ icon: 'success', title: '添加用户成功' });<?php endif ?>
<?php if (Input::getStrVar('code') === 'fb'): ?>Toast.fire({ icon: 'success', title: '禁用成功，该用户无法再登录' });<?php endif ?>
<?php if (Input::getStrVar('code') === 'unfb'): ?>Toast.fire({ icon: 'success', title: '解禁成功' });<?php endif ?>
<?php if (Input::getStrVar('code') === 'update'): ?>Toast.fire({ icon: 'success', title: '修改成功' });<?php endif ?>

<?php if (Input::getStrVar('code') === 'nickname'): ?>Toast.fire({ icon: 'error', title: '昵称不能为空' });<?php endif ?>
<?php if (Input::getStrVar('code') === 'email'): ?>Toast.fire({ icon: 'error', title: '邮箱和用户名不能都为空' });<?php endif ?>
<?php if (Input::getStrVar('code') === 'exist'): ?>Toast.fire({ icon: 'error', title: '用户名已被占用' });<?php endif ?>
<?php if (Input::getStrVar('code') === 'exist_email'): ?>Toast.fire({ icon: 'error', title: '该邮箱已被占用' });<?php endif ?>
<?php if (Input::getStrVar('code') === 'pwd_len'): ?>Toast.fire({ icon: 'error', title: '密码长度不得小于6位' });<?php endif ?>
<?php if (Input::getStrVar('code') === 'pwd2'): ?>Toast.fire({ icon: 'error', title: '两次输入密码不一致' });<?php endif ?>
<?php if (Input::getStrVar('code') === 'del_a'): ?>Toast.fire({ icon: 'error', title: '不能删除创始人' });<?php endif ?>
<?php if (Input::getStrVar('code') === 'del_b'): ?>Toast.fire({ icon: 'error', title: '不能修改创始人信息' });<?php endif ?>
</script>
