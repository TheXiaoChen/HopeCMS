
		<form action="<?= SELF ?>?act=setting&save" method="post" name="form_log" id="form_log">
			<div class="row">
				<div class="col-lg-4 col-sm-8">
					<div class="nav-wrapper position-relative end-0">
						<ul class="nav nav-pills nav-fill p-1" role="tablist">
							<li class="nav-item">
								<a class="nav-link mb-0 px-0 py-1 active" data-bs-toggle="tab" href="#set1" aria-controls="set1" role="tab" aria-selected="true">
									<?= __('admin.setting.basic') ?>
								</a>
							</li>
							<li class="nav-item">
								<a class="nav-link mb-0 px-0 py-1 " data-bs-toggle="tab" href="#set2"
									aria-controls="set2" role="tab" aria-selected="false">
									<?= __('admin.setting.user') ?>
								</a>
							</li>
							<li class="nav-item">
								<a class="nav-link mb-0 px-0 py-1 " data-bs-toggle="tab" href="#set3"
									aria-controls="set3" role="tab" aria-selected="false">
									<?= __('admin.setting.feature') ?>
								</a>
							</li>
							<li class="nav-item">
								<a class="nav-link mb-0 px-0 py-1 " data-bs-toggle="tab" href="#set4"
									aria-controls="set4" role="tab" aria-selected="false">
									<?= __('admin.setting.api') ?>
								</a>
							</li>
							<li class="nav-item">
								<a class="nav-link mb-0 px-0 py-1 " data-bs-toggle="tab" href="#set5"
									aria-controls="set5" role="tab" aria-selected="false">
									<?= __('admin.setting.data') ?>
								</a>
							</li>
							
						</ul>
					</div>
				</div>
				<div class="col-lg-1 ms-auto">
					<input name="token" id="token" value="<?= LoginAuth::genToken() ?>" type="hidden"/>
					<button class="btn bg-gradient-success mb-0 " type="submit" title="<?= htmlspecialchars(__('admin.setting.save')) ?>"><?= __('admin.setting.save') ?></button>
				</div>
			</div>
			<div class="row">
				<div class="mt-lg-0 mt-4">
					<div class="tab-content tab-space">
						<div class="card mt-4 tab-pane active" id="set1">
							<div class="card-header">
								<h5><?= __('admin.setting.site_info') ?></h5>
							</div>
							<div class="card-body pt-0">
								<div class="row">
									<div class="col-6">
										<label class="form-label">站点标题</label>
										<div class="input-group">
											<input name="sitename" class="form-control" type="text" value="<?= $sitename ?>">
										</div>
									</div>
									<div class="col-6">
									<label class="form-label">站点地址</label>
										<div class="input-group mb-3">
											<input type="text" class="form-control" name="siteurl" value="<?= htmlspecialchars($siteurl) ?>" data-current-url="<?= htmlspecialchars($siteurl) ?>">
											<button class="btn btn-outline-primary form-switch mb-0">
												&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
												<input class="form-check-input" type="checkbox" name="detect_url" value="y" <?= $conf_detect_url ?>>
												<span class="ms-2">自动检测</span>
											</button>
										</div>
									</div>
								</div>
								<div class="row">
									<div class="col-6">
										<label class="form-label">站点副标题</label>
										<div class="input-group">
											<textarea class="form-control" name="siteinfo" rows="3"><?= $siteinfo ?></textarea>
										</div>
									</div>
									<div class="col-6">
										<label class="form-label">版权说明</label>
										<div class="input-group">
											<textarea class="form-control" name="footer_info" placeholder="可以填入网站统计、备案号等内容。" rows="3"><?= stripslashes($footer_info) ?></textarea>
										</div>
									</div>
								</div>
								<div class="row mt-3">
									<div class="col-6">
										<label class="form-label"><?= __('admin.setting.timezone') ?></label>
										<select name="timezone" class="form-control" data-hope-choices data-preset="search">
										<?php foreach ($tzlist as $key => $value):
											$ex = $key == $timezone ? "selected=\"selected\"" : '' ?>
											<option value="<?= $key ?>" <?= $ex ?>><?= $value ?></option>
										<?php endforeach ?>
										</select>
									</div>
									<div class="col-6">
										<label class="form-label"><?= __('admin.setting.language') ?></label>
										<select class="form-control" name="language" id="site-default-language" data-hope-choices data-preset="search">
											<?php foreach ($hope_lang_registry as $code => $meta): ?>
											<option value="<?= htmlspecialchars($code) ?>" <?= $language === $code ? ' selected="selected"' : '' ?>>
												<?= htmlspecialchars($meta['native']) ?> (<?= htmlspecialchars($code) ?>)
											</option>
											<?php endforeach; ?>
										</select>
										<small class="text-muted"><?= __('admin.setting.lang_hint') ?></small>
									</div>
								</div>
								<div class="row mt-4 my-3">
									<div class="col-6">
										<div class="d-flex align-items-center">
											<div class="form-switch mb-0">
												<input class="form-check-input" type="checkbox" name="debug" value="y" <?= $conf_is_debug ?>>
											</div>
											<span class="text-dark font-weight-bold text-sm">调试模式</span>
										</div>
									</div>
									<div class="col-6">
										<div class="d-flex align-items-center">
											<div class="form-switch mb-0">
												<input class="form-check-input" type="checkbox" name="close" id="site-close-switch" value="y" <?= $conf_is_close ?>>
											</div>
											<span class="text-dark font-weight-bold text-sm">关闭网站</span>
										</div>
										<small class="text-muted d-block mt-1">开启后前台不可访问，管理员登录后仍可预览</small>
									</div>
								</div>
								<div class="row mt-2" id="site-close-fields" style="<?= $conf_is_close ? '' : 'display:none' ?>">
									<div class="col-6">
										<label class="form-label">关闭页标题</label>
										<input type="text" class="form-control" name="close_title" value="<?= htmlspecialchars($close_title) ?>" placeholder="网站维护中">
									</div>
									<div class="col-6">
										<label class="form-label">关闭页说明</label>
										<textarea class="form-control" name="close_msg" rows="2" placeholder="网站暂时关闭维护中，请稍后再访。"><?= htmlspecialchars($close_msg) ?></textarea>
									</div>
								</div>
								<hr class="horizontal dark mt-4 my-3">
								<h5>页头信息</h5>
								<div class="row mt-4 my-3">
									<div class="col-sm-4 col-6">
										<label class="form-label">站点浏览器标题</label>
										<div class="input-group">
											<input type="text" class="form-control" name="site_title" value="<?= $site_title ?>" placeholder="title">
										</div>
									</div>
									<div class="col-sm-4 col-6">
										<label class="form-label">站点关键字</label>
										<div class="input-group">
											<input type="text" class="form-control" name="site_key" value="<?= $site_key ?>" placeholder="keywords">
										</div>
									</div>
									<div class="col-sm-4 col-6">
										<label class="form-label">文章浏览器标题</label>
										<select class="form-control" name="log_title_style" data-hope-choices>
											<option value="0" <?= $opt0 ?>>文章标题</option>
											<option value="1" <?= $opt1 ?>>文章标题 - 站点标题</option>
											<option value="2" <?= $opt2 ?>>文章标题 - 站点浏览器标题</option>
										</select>
									</div>
								</div>
								<div class="row">
									<label class="form-label">站点浏览器描述</label>
									<div class="input-group">
										<textarea class="form-control" name="site_description" rows="3"><?= stripslashes($site_description) ?></textarea>
									</div>
								</div>
								<hr class="horizontal dark mt-4 my-3">
								<h5>展示数量</h5>
								<div class="row mt-4">
									<div class="col-6">
										<div class="input-group input-group-lg">
											<span class="input-group-text">文章显示数量</span>
											<input type="number" class="form-control" name="index_lognum" value="<?= $index_lognum ?>" >
										</div>
									</div>
									<div class="col-6">
										<div class="input-group input-group-lg">
											<span class="input-group-text">限搜索间隔（秒）</span>
											<input type="number" class="form-control" name="search_date" value="<?= $search_date ?>" >
										</div>
									</div>
								</div>

							</div>
						</div>







						<div class="card mt-4 tab-pane" id="set2">
							<div class="card-header">
								<h5>注册权限</h5>
							</div>
							<div class="card-body pt-0">
								<div class="row">
									<div class="col-6">
										<div class="d-flex align-items-center">
											<div class="form-switch mb-0">
												<input class="form-check-input" type="checkbox" value="y" name="is_signup" <?= $conf_is_signup ?> >
											</div>
											<span class="text-dark font-weight-bold text-sm">用户注册</span>
										</div>
									</div>
									<div class="col-6">
										<div class="d-flex align-items-center">
											<div class="form-switch mb-0">
												<input class="form-check-input" type="checkbox" value="y" name="ischkarticle" <?= $conf_ischkarticle ?> >
											</div>
											<span class="text-dark font-weight-bold text-sm">需要审核</span>
										</div>
									</div>
								</div>
								<div class="row mt-4 my-3">
									<div class="col-6">
										<div class="d-flex align-items-center">
											<div class="form-switch mb-0">
												<input class="form-check-input" type="checkbox" value="y" name="login_code" <?= $conf_login_code ?> >
											</div>
											<span class="text-dark font-weight-bold text-sm">图形验证码</span>
										</div>
									</div>
									<div class="col-6">
										<div class="d-flex align-items-center">
											<div class="form-switch mb-0">
												<input class="form-check-input" type="checkbox" value="y" name="email_code" <?= $conf_email_code ?> >
											</div>
											<span class="text-dark font-weight-bold text-sm">邮件验证码</span>
										</div>
									</div>
								</div>
								<div class="row mt-4 my-3">
									<div class="col-6">
										<div class="d-flex align-items-center">
											<div class="form-switch mb-0">
												<input class="form-check-input" type="checkbox" value="y" name="article_uneditable" <?= $conf_article_uneditable ?>>
											</div>
											<span class="text-dark font-weight-bold text-sm">审核通过的文章用户不可编辑、删除</span>
										</div>
									</div>
								</div>
								<hr class="horizontal dark mt-4 my-3">
								<h5>注册用户</h5>
								<p class="text-sm text-muted mb-2">配置「注册用户」角色可访问的后台模块（管理员不受此限制）</p>
								<div class="row mt-2">
									<?php foreach ($role_permission_registry as $perm_key => $perm_label): ?>
									<div class="col-6 col-lg-4">
										<div class="d-flex align-items-center mb-3">
											<div class="form-check mb-0">
												<input class="form-check-input" type="checkbox" name="writer_permissions[]" value="<?= htmlspecialchars($perm_key) ?>" id="writer-perm-<?= htmlspecialchars($perm_key) ?>"<?= in_array($perm_key, $writer_permissions_selected, true) ? ' checked' : '' ?>>
											</div>
											<label class="text-dark font-weight-bold text-sm mb-0 ms-2" for="writer-perm-<?= htmlspecialchars($perm_key) ?>"><?= htmlspecialchars($perm_label) ?></label>
										</div>
									</div>
									<?php endforeach; ?>
								</div>
								<hr class="horizontal dark mt-4 my-3">
								<h5>内容编辑</h5>
								<p class="text-sm text-muted mb-2">配置「内容编辑」角色可访问的后台模块（管理员不受此限制）</p>
								<div class="row mt-2">
									<?php foreach ($role_permission_registry as $perm_key => $perm_label): ?>
									<div class="col-6 col-lg-4">
										<div class="d-flex align-items-center mb-3">
											<div class="form-check mb-0">
												<input class="form-check-input" type="checkbox" name="editor_permissions[]" value="<?= htmlspecialchars($perm_key) ?>" id="editor-perm-<?= htmlspecialchars($perm_key) ?>"<?= in_array($perm_key, $editor_permissions_selected, true) ? ' checked' : '' ?>>
											</div>
											<label class="text-dark font-weight-bold text-sm mb-0 ms-2" for="editor-perm-<?= htmlspecialchars($perm_key) ?>"><?= htmlspecialchars($perm_label) ?></label>
										</div>
									</div>
									<?php endforeach; ?>
								</div>
								<div class="input-group ">
									<span class="input-group-text">限制注册用户发布文章 0 为无限</span>
									<input type="text" class="form-control" name="posts_per_day" value="<?= $conf_posts_per_day ?>" >
								</div>
								<div class="input-group mt-3">
									<span class="input-group-text">邀请奖励金额（元）</span>
									<input type="number" class="form-control" name="invite_reward_amount" value="<?= $invite_reward_amount ?>" min="0" step="0.01">
								</div>
								<small class="text-muted">新用户通过邀请码注册成功后，邀请人与被邀请人各获得该金额奖励；设为 0 则不启用邀请奖励</small>
								<hr class="horizontal dark mt-4 my-3">
								<h5>评论设置</h5>

								<div class="row mt-4 my-3">
									<div class="col-6">
										<div class="d-flex align-items-center">
											<div class="form-switch mb-0">
												<input class="form-check-input" type="checkbox" value="y" name="iscomment" <?= $conf_iscomment ?>>
											</div>
											<span class="text-dark font-weight-bold text-sm">评论功能</span>
										</div>
									</div>
									<div class="col-6">
										<div class="d-flex align-items-center">
											<div class="form-switch mb-0">
												<input class="form-check-input" type="checkbox" value="y" name="ischkcomment" <?= $conf_ischkcomment ?> >
											</div>
											<span class="text-dark font-weight-bold text-sm">评论审核</span>
										</div>
									</div>
								</div>
								<div class="row mt-4 my-3">
									<div class="col-6">
										<div class="d-flex align-items-center">
											<div class="form-switch mb-0">
												<input class="form-check-input" type="checkbox" value="y" name="comment_code" <?= $conf_comment_code ?> >
											</div>
											<span class="text-dark font-weight-bold text-sm">验证码</span>
										</div>
									</div>
									<div class="col-6">
										<div class="d-flex align-items-center">
											<div class="form-switch mb-0">
												<input class="form-check-input" type="checkbox" value="y" name="login_comment" <?= $conf_login_comment ?> >
											</div>
											<span class="text-dark font-weight-bold text-sm">登录评论</span>
										</div>
									</div>
								</div>
								<div class="row mt-4">
									<div class="col-6">
										<div class="d-flex align-items-center">
											<div class="form-switch mb-0">
												<input class="form-check-input" type="checkbox" value="y" name="comment_needchinese" <?= $conf_comment_needchinese ?> >
											</div>
											<span class="text-dark font-weight-bold text-sm">内容需含中文</span>
										</div>
									</div>
									<div class="col-6">
										<div class="d-flex align-items-center">
											<div class="form-switch mb-0">
												<input class="form-check-input" type="checkbox" value="y" name="comment_order" <?= $conf_comment_order ?>>
											</div>
											<span class="text-dark font-weight-bold text-sm">评论排序倒序</span>
										</div>
									</div>
								</div>
								<div class="row mt-4">
									<div class="col-6">
										<div class="input-group">
											<span class="input-group-text">发表评论间隔（秒）</span>
											<input type="number" class="form-control" name="comment_interval" value="<?= $comment_interval ?>" >
										</div>
									</div>
									<div class="col-6">
										<div class="input-group">
											<span class="input-group-text">显示数量/评论分页</span>
											<input type="text" class="form-control" name="comment_pnum" value="<?= $comment_pnum ?>" >
											<button class="btn btn-outline-primary form-switch mb-0">
												&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
												<input class="form-check-input" type="checkbox"name="comment_paging" value="y" <?= $conf_comment_paging ?>>
											</button>
										</div>
									</div>
								</div>
								<hr class="horizontal dark mt-4 my-3">
								<h5>上传附件</h5>
								<div class="row mt-4 my-3">
									<div class="col-6">
										<div class="d-flex align-items-center">
											<div class="form-switch mb-0">
												<input class="form-check-input" type="checkbox" value="y" name="forbid_user_upload" <?= $conf_forbid_user_upload ?>>
											</div>
											<span class="text-dark font-weight-bold text-sm">禁止用户上传资源</span>
										</div>
									</div>
								</div>
								<div class="row mt-4 my-3">
									<div class="col-6">
										<div class="input-group">
											<span class="input-group-text">允许上传的文件类型</span>
											<input type="text" class="form-control" name="att_type" value="<?= $att_type ?>" >
										</div>
									</div>
									<div class="col-6">
										<div class="input-group">
											<span class="input-group-text">允许上传文件的大小(MB)</span>
											<input type="number" class="form-control" name="att_maxsize" value="<?= $att_maxsize ?>" >
										</div>
									</div>
								</div>
							</div>
						</div>



						<div class="card mt-4 tab-pane" id="set3">
							<div class="card-header">
								<h5>邮件服务</h5>
							</div>
							<div class="card-body pt-0">
								<div class="row">
									<div class="col-sm-4 col-6">
										<div class="input-group">
											<span class="input-group-text">发送人邮箱</span>
											<input type="email" class="form-control" name="smtp_mail" value="<?= $smtp_mail ?>" >
										</div>
									</div>
									<div class="col-sm-4 col-6">
										<div class="input-group">
											<span class="input-group-text">SMTP密码</span>
											<input type="password" class="form-control" name="smtp_pw" value="<?= $smtp_pw ?>" >
										</div>
									</div>
									<div class="col-sm-4 col-6">
										<div class="input-group">
											<span class="input-group-text">发送人名称</span>
											<input type="text" class="form-control" name="smtp_from_name" value="<?= $smtp_from_name ?>" >
										</div>
									</div>
								</div>
								<div class="row mt-2">
									<div class="col-6">
										<div class="input-group">
											<span class="input-group-text">SMTP服务器</span>
											<input type="text" class="form-control" name="smtp_server" value="<?= $smtp_server ?>" >
										</div>
									</div>
									<div class="col-6">
										<div class="input-group">
											<span class="input-group-text">端口</span>
											<input type="text" class="form-control" name="smtp_port" value="<?= $smtp_port ?>" >
										</div>
									</div>
								</div>
								<div class="row mt-2">
									<div class="col-6">
										<div class="d-flex align-items-center">
											<div class="form-switch mb-0">
												<input class="form-check-input" type="checkbox" value="y" name="mail_notice_comment" <?= $conf_mail_notice_comment ?>>
											</div>
											<span class="text-dark font-weight-bold text-sm">评论邮件通知</span>
										</div>
									</div>
									<div class="col-6">
										<div class="d-flex align-items-center">
											<div class="form-switch mb-0">
												<input class="form-check-input" type="checkbox" value="y" name="mail_notice_post" <?= $conf_mail_notice_post ?> >
											</div>
											<span class="text-dark font-weight-bold text-sm">投稿邮件通知</span>
										</div>
									</div>
								</div>
								<div class="alert alert-light mt-4 my-3">
									<b>以QQ邮箱配置为例</b><br>
									发送人邮箱：你的QQ邮箱<br>
									SMTP密码：见QQ邮箱顶部设置-&gt; 账户 -&gt; 开启IMAP/SMTP服务 -&gt; 生成授权码（即为SMTP密码）<br>
									发送人名称：你的姓名或者站点名称<br>
									SMTP服务器：smtp.qq.com<br>
									端口：465<br>
								</div>
								<div class="col-md-4">
									<button type="button" class="btn bg-gradient-success btn-block mb-3" data-bs-toggle="modal" data-bs-target="#exampleModalMessage">发送测试</button>
									<!-- Modal -->
									<div class="modal fade" id="exampleModalMessage" tabindex="-1" role="dialog" aria-labelledby="exampleModalMessageTitle" aria-hidden="true">
									<div class="modal-dialog modal-dialog-centered" role="document">
										<div class="modal-content">
										<div class="modal-header">
											<h5 class="modal-title" id="exampleModalLabel">发送邮件</h5>
											<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
											<span aria-hidden="true">×</span>
											</button>
										</div>
										<div class="modal-body">
											<form>
											<div class="form-group">
												<label for="recipient-name" class="col-form-label">邮件:</label>
												<input type="text" class="form-control" value="271106735@qq.com" id="recipient-name">
											</div>
											<div class="form-group">
												<label for="message-text" class="col-form-label">内容:</label>
												<textarea class="form-control" id="message-text"></textarea>
											</div>
											</form>
										</div>
										<div class="modal-footer">
											<button type="button" class="btn bg-gradient-secondary" data-bs-dismiss="modal">关闭</button>
											<button type="button" class="btn bg-gradient-primary">发送</button>
										</div>
										</div>
									</div>
									</div>
								</div>
								<hr class="horizontal dark mt-4 my-3">
								<h5>伪静态管理</h5>
								<div class="row my-3">
									<div class="input-group">
										<div class="form-check">
											<input type="radio" class="form-check-input" name="linkmode" value="0" <?= $conf_linkmode_0 ?> onchange="changeOptions(0);" ><label class="form-label">动态&emsp;&emsp;</label>
										</div>
										<div class="form-check">
											<input type="radio" class="form-check-input" name="linkmode" value="1" <?= $conf_linkmode_1 ?> onchange="changeOptions(2);" ><label class="form-label">伪静态&emsp;</label>
										</div>
										<div class="form-check">
											<input type="radio" class="form-check-input" name="linkmode" value="2" <?= $conf_linkmode_2 ?> onchange="changeOptions(1);" ><label class="form-label">index.php式仿伪静态&emsp;</label>
										</div>
									</div>
								</div>

								<div class="alert alert-light text-white"><strong>使用伪静态</strong> 必须确认主机是否支持！</div>
								<div class="row">
									<div class="col-6">
										<div class="input-group">
											<span class="input-group-text">首页的URL配置</span>
											<input type="text" class="form-control" name="rule_index" id="rule_index" value="<?= $rule_index ?>" >
										</div>
										<div class="table-responsive">
											<table class="table table-striped">
												<tbody>
													<p style="display:none;"><input disabled="disabled" type="radio" name="radio_rule_index" value="{%host%}?page={%page%}" onclick="$('#rule_index').val($(this).val())"></p>
													<p style="display:none;"><input disabled="disabled" type="radio" name="radio_rule_index" value="{%host%}index.php/page_{%page%}.html" onclick="$('#rule_index').val($(this).val())" /></p>
													<tr>
														<td>
															<div class="form-check">
																<input class="form-check-input" type="radio" name="radio_rule_index" value="{%host%}page_{%page%}.html" id="rule_index1" onclick="$('#rule_index').val($(this).val())">
																<label class="form-check-label" for="rule_index1">{%host%}page_{%page%}.html</label>
															</div>
														</td>
													</tr>
													<tr>
														<td>
															<div class="form-check">
																<input class="form-check-input" type="radio" name="radio_rule_index" value="{%host%}page_{%page%}/" id="rule_index2" onclick="$('#rule_index').val($(this).val())">
																<label class="form-check-label" for="rule_index2">{%host%}page_{%page%}/</label>
															</div>
														</td>
													</tr>
													<tr>
														<td>
															<div class="form-check">
																<input class="form-check-input" type="radio" name="radio_rule_index" value="{%host%}page/{%page%}/" id="rule_index3" onclick="$('#rule_index').val($(this).val())">
																<label class="form-check-label" for="rule_index3">{%host%}page/{%page%}/</label>
															</div>
														</td>
													</tr>
												</tbody>
											</table>
										</div>



									</div>
									<div class="col-6">
										<div class="input-group">
											<span class="input-group-text">页面的URL配置</span>
											<input type="text" class="form-control" name="rule_page" id="rule_page" value="<?= $rule_page ?>" >
										</div>
										<div class="table-responsive">
											<table class="table table-striped">
												<tbody>
													<p style="display:none;"><input disabled="disabled" type="radio" name="radio_rule_page" value="{%host%}?id={%id%}" onclick="$('#rule_page').val($(this).val())"></p>
													<p style="display:none;"><input disabled="disabled" type="radio" name="radio_rule_page" value="{%host%}index.php/{%id%}.html" onclick="$('#rule_page').val($(this).val())" /></p>
													<tr>
														<td>
															<div class="form-check">
																<input class="form-check-input" type="radio" name="radio_rule_page" value="{%host%}{%id%}.html" id="rule_page1" onclick="$('#rule_page').val($(this).val())">
																<label class="form-check-label" for="rule_page1">{%host%}{%id%}.html</label>
															</div>
														</td>
													</tr>
													<tr>
														<td>
															<div class="form-check">
																<input class="form-check-input" type="radio" name="radio_rule_page"  value="{%host%}{%alias%}.html" id="rule_page2" onclick="$('#rule_page').val($(this).val())">
																<label class="form-check-label" for="rule_page2">{%host%}{%alias%}.html</label>
															</div>
														</td>
													</tr>
													<tr>
														<td>
															<div class="form-check">
																<input class="form-check-input" type="radio" name="radio_rule_page" value="{%host%}{%alias%}/" id="rule_page3" onclick="$('#rule_page').val($(this).val())">
																<label class="form-check-label" for="rule_page3">{%host%}{%alias%}/</label>
															</div>
														</td>
													</tr>
												</tbody>
											</table>
										</div>
									</div>
								</div>



								<div class="row">
									<div class="col-6">
										<div class="input-group">
											<span class="input-group-text">文章的URL配置</span>
											<input type="text" class="form-control" name="rule_article" id="rule_article" value="<?= $rule_article ?>" >
										</div>
										<div class="table-responsive">
											<table class="table table-striped">
												<tbody>
													<p style="display:none;"><input disabled="disabled" type="radio" name="radio_rule_article" value="{%host%}?article={%id%}" onclick="$('#rule_article').val($(this).val())"></p>
													<p style="display:none;"><input disabled="disabled" type="radio" name="radio_rule_article" value="{%host%}index.php/article/{%id%}.html" onclick="$('#rule_article').val($(this).val())" /></p>
													<tr>
														<td>
															<div class="form-check">
																<input class="form-check-input" type="radio" name="radio_rule_article" value="{%host%}article/{%id%}.html" id="rule_article1" onclick="$('#rule_article').val($(this).val())">
																<label class="form-check-label" for="rule_article1">{%host%}article/{%id%}.html</label>
															</div>
														</td>
													</tr>
													<tr>
														<td>
															<div class="form-check">
																<input class="form-check-input" type="radio" name="radio_rule_article" value="{%host%}article/{%alias%}.html" id="rule_article2" onclick="$('#rule_article').val($(this).val())">
																<label class="form-check-label" for="rule_article2">{%host%}article/{%alias%}.html</label>
															</div>
														</td>
													</tr>
													<tr>
														<td>
															<div class="form-check">
																<input class="form-check-input" type="radio" name="radio_rule_article" value="{%host%}{%year%}/{%month%}/{%id%}/" id="rule_article3" onclick="$('#rule_article').val($(this).val())">
																<label class="form-check-label" for="rule_article3">{%host%}{%year%}/{%month%}/{%id%}/</label>
															</div>
														</td>
													</tr>
													<tr>
														<td>
															<div class="form-check">
																<input class="form-check-input" type="radio" name="radio_rule_article" value="{%host%}{%category%}/{%alias%}/" id="rule_article4" onclick="$('#rule_article').val($(this).val())">
																<label class="form-check-label" for="rule_article4">{%host%}{%category%}/{%alias%}/</label>
															</div>
														</td>
													</tr>
												</tbody>
											</table>
										</div>
									</div>
									<div class="col-6">
										<div class="input-group">
											<span class="input-group-text">分类页的URL配置</span>
											<input type="text" class="form-control" name="rule_category" id="rule_category" value="<?= $rule_category ?>" >
										</div>
										<div class="table-responsive">
											<table class="table table-striped">
												<tbody>
													<p style="display:none;"><input disabled="disabled" type="radio" name="radio_rule_category" value="{%host%}?category={%id%}&page={%page%}" onclick="$('#rule_category').val($(this).val())"></p>
													<p style="display:none;"><input disabled="disabled" type="radio" name="radio_rule_category" value="{%host%}index.php/category-{%id%}_{%page%}.html" onclick="$('#rule_category').val($(this).val())" /></p>
													<tr>
														<td>
															<div class="form-check">
																<input class="form-check-input" type="radio" name="radio_rule_category" value="{%host%}category-{%id%}_{%page%}.html" id="rule_category1" onclick="$('#rule_category').val($(this).val())">
																<label class="form-check-label" for="rule_category1">{%host%}category-{%id%}_{%page%}.html</label>
															</div>
														</td>
													</tr>
													<tr>
														<td>
															<div class="form-check">
																<input class="form-check-input" type="radio" name="radio_rule_category" value="{%host%}category-{%alias%}_{%page%}.html" id="rule_category2" onclick="$('#rule_category').val($(this).val())">
																<label class="form-check-label" for="rule_category2">{%host%}category-{%alias%}_{%page%}.html</label>
															</div>
														</td>
													</tr>
													<tr>
														<td>
															<div class="form-check">
																<input class="form-check-input" type="radio" name="radio_rule_category" value="{%host%}category/{%id%}/{%page%}/" id="rule_category3" onclick="$('#rule_category').val($(this).val())">
																<label class="form-check-label" for="rule_category3">{%host%}category/{%id%}/{%page%}/</label>
															</div>
														</td>
													</tr>
													<tr>
														<td>
															<div class="form-check">
																<input class="form-check-input" type="radio" name="radio_rule_category" value="{%host%}category/{%alias%}/{%page%}/" id="rule_category4" onclick="$('#rule_category').val($(this).val())">
																<label class="form-check-label" for="rule_category4">{%host%}category/{%alias%}/{%page%}/</label>
															</div>
														</td>
													</tr>
												</tbody>
											</table>
										</div>

									</div>
								</div>

								<div class="row">
									<div class="col-6">
										<div class="input-group">
											<span class="input-group-text">标签页的URL配置</span>
											<input type="text" class="form-control" name="rule_tag" id="rule_tag" value="<?= $rule_tag ?>" >
										</div>
										<div class="table-responsive">
											<table class="table table-striped">
												<tbody>
													<p style="display:none;"><input disabled="disabled" type="radio" name="radio_rule_tag" value="{%host%}?tags={%tag%}&page={%page%}" onclick="$('#rule_tag').val($(this).val())"></p>
													<p style="display:none;"><input disabled="disabled" type="radio" name="radio_rule_tag" value="{%host%}index.php/tags/{%tag%}_{%page%}.html" onclick="$('#rule_tag').val($(this).val())" /></p>
													<tr>
														<td>
															<div class="form-check">
																<input class="form-check-input" type="radio" name="radio_rule_tag" value="{%host%}tags/{%tag%}_{%page%}.html" id="rule_tag1" onclick="$('#rule_tag').val($(this).val())">
																<label class="form-check-label" for="rule_tag1">{%host%}tags/{%tag%}_{%page%}.html</label>
															</div>
														</td>
													</tr>
													<tr>
														<td>
															<div class="form-check">
																<input class="form-check-input" type="radio" name="radio_rule_tag" value="{%host%}tags-{%tag%}_{%page%}.html" id="rule_tag2" onclick="$('#rule_tag').val($(this).val())">
																<label class="form-check-label" for="rule_tag2">{%host%}tags-{%tag%}_{%page%}.html</label>
															</div>
														</td>
													</tr>
												</tbody>
											</table>
										</div>

									</div>
									<div class="col-6">
										<div class="input-group">
											<span class="input-group-text">日期页的URL配置</span>
											<input type="text" class="form-control" name="rule_record" id="rule_record" value="<?= $rule_record ?>" >
										</div>
										<div class="table-responsive">
											<table class="table table-striped">
												<tbody>
													<p style="display:none;"><input disabled="disabled" type="radio" name="radio_rule_record" value="{%host%}?date={%date%}&page={%page%}" onclick="$('#rule_record').val($(this).val())"></p>
													<p style="display:none;"><input disabled="disabled" type="radio" name="radio_rule_record" value="{%host%}index.php/date-{%date%}_{%page%}.html" onclick="$('#rule_record').val($(this).val())" /></p>
													<tr>
														<td>
															<div class="form-check">
																<input class="form-check-input" type="radio" name="radio_rule_record" value="{%host%}date-{%record%}_{%page%}.html" id="rule_record1" onclick="$('#rule_record').val($(this).val())">
																<label class="form-check-label" for="rule_record1">{%host%}date-{%record%}_{%page%}.html</label>
															</div>
														</td>
													</tr>
													<tr>
														<td>
															<div class="form-check">
																<input class="form-check-input" type="radio" name="radio_rule_record" value="{%host%}date/{%record%}/{%page%}/" id="rule_record2" onclick="$('#rule_record').val($(this).val())">
																<label class="form-check-label" for="rule_record2">{%host%}date/{%record%}/{%page%}/</label>
															</div>
														</td>
													</tr>
													<tr>
														<td>
															<div class="form-check">
																<input class="form-check-input" type="radio" name="radio_rule_record" value="{%host%}date/{%record%}/{%page%}/" id="rule_record3" onclick="$('#rule_record').val($(this).val())">
																<label class="form-check-label" for="rule_record3">{%host%}date/{%record%}/{%page%}/</label>
															</div>
														</td>
													</tr>
												</tbody>
											</table>
										</div>
									</div>
								</div>
							</div>
						</div>



						<div class="card mt-4 tab-pane" id="set4">
							<div class="card-header">
								<h5>API 设置</h5>
								<p class="text-sm text-secondary mb-0">REST 接口开关、鉴权密钥与访问频率限制</p>
							</div>
							<div class="card-body pt-0">
								<div class="alert alert-light border text-sm mb-4" role="alert">
									<div class="mb-2"><strong>接口地址：</strong><code><?= htmlspecialchars($hope_api_base_url) ?>{方法名}</code></div>
									<div class="mb-2"><strong>签名规则：</strong>必须携带 <code>req_nonce</code>（16–64 位十六进制随机串），<code>req_sign = md5(req_time + req_nonce + apikey)</code></div>
									<div class="mb-2"><strong>时效：</strong><code>req_time</code> 与服务器时间差不得超过 300 秒；同一 <code>req_nonce</code> 不可重复提交</div>
									<div class="mb-0"><strong>说明：</strong>支付相关接口（<code>pay_notify</code>、<code>pay_create</code> 等）不受 OpenAPI 开关限制</div>
								</div>
								<div class="row">
									<div class="col-6">
										<div class="d-flex align-items-center">
											<div class="form-switch mb-0">
												<input class="form-check-input" type="checkbox" value="y" name="is_openapi" id="openapi-switch" <?= $conf_is_openapi ?> >
											</div>
											<span class="text-dark font-weight-bold text-sm">启用 OpenAPI</span>
										</div>
										<small class="text-muted d-block mt-1">关闭后除支付接口外，其余 REST API 不可用</small>
									</div>
									<div class="col-6">
										<div class="d-flex align-items-center">
											<div class="form-switch mb-0">
												<input class="form-check-input" type="checkbox" value="y" name="is_limitapi" id="limitapi-switch" <?= $conf_is_limitapi ?> >
											</div>
											<span class="text-dark font-weight-bold text-sm">启用 API 限流</span>
										</div>
										<small class="text-muted d-block mt-1">按 IP 限制每分钟请求次数</small>
									</div>
								</div>
								<div class="row mt-4 my-3">
									<div class="col-6">
										<div class="input-group input-group-lg">
											<span class="input-group-text">API 密钥</span>
											<input type="text" class="form-control" name="apikey" id="apikey-input" value="<?= htmlspecialchars($apikey) ?>" placeholder="开启 OpenAPI 时必填，留空将自动生成">
											<button class="btn btn-outline-primary mb-0" type="button" onclick="window.location.href='<?= SELF ?>?act=setting&api_reset&token=<?= LoginAuth::genToken() ?>'">重置</button>
										</div>
									</div>
									<div class="col-6">
										<div class="input-group input-group-lg">
											<span class="input-group-text">每分钟限制</span>
											<input type="number" class="form-control" name="limit_num" min="1" max="10000" value="<?= (int)$limit_num ?>" >
										</div>
									</div>
								</div>
								<p class="text-sm text-muted mb-0">示例：<code><?= htmlspecialchars($hope_api_base_url) ?>article_list&amp;page=1&amp;count=10</code></p>
							</div>
						</div>

		</form>
		<!-- set5 站点数据独立于保存表单，避免嵌套 form 导致备份/导入失效 -->

						<div class="card mt-4 tab-pane" id="set5">
							<div class="card-header pb-0">
								<h5>站点数据</h5>
							</div>
							<div class="card-body">
								<div class="row g-3 flex-wrap">
									<div class="col-12 col-md-4">
										<div class="card h-100">
											<div class="card-header pb-0 p-3">
												<h6 class="mb-0">数据库备份</h6>
											</div>
											<div class="card-body p-3">
												<p class="text-sm">将本站内容数据库备份到自己电脑手机上.</p>
												<div class="row mt-3 mb-3">
													<div class="input-group">
														<div class="form-check me-3">
															<input type="radio" class="form-check-input" name="zipbak" value="0" checked ><label class="form-label ms-1">SQL格式</label>
														</div>
														<div class="form-check">
															<input type="radio" class="form-check-input" name="zipbak" value="1"><label class="form-label ms-1">ZIP格式</label>
														</div>
													</div>
												</div>
												<hr class="horizontal dark">
												<div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
													<button type="button" class="btn btn-sm btn-primary mb-2 mb-md-0" id="download_backup">下载备份</button>
													<div class="avatar-group">
														<a href="javascript:;" class="avatar avatar-lg avatar-xs rounded-circle" data-bs-toggle="tooltip" data-bs-placement="bottom">
															<img alt="Image placeholder" src="https://q4.qlogo.cn/headimg_dl?dst_uin=271106735&spec=640">
														</a>
														<a href="javascript:;" class="avatar avatar-lg avatar-xs rounded-circle" data-bs-toggle="tooltip" data-bs-placement="bottom">
															<img alt="Image placeholder" src="https://q4.qlogo.cn/headimg_dl?dst_uin=2245314490&spec=640">
														</a>
													</div>
												</div>
											</div>
										</div>
									</div>
									<div class="col-12 col-md-4">
										<div class="card h-100">
											<div class="card-header pb-0 p-3">
												<h6 class="mb-0">导入备份</h6>
											</div>
											<div class="card-body p-3">
												<p class="text-sm">仅可导入相同版本的数据库备份文件，且数据库表前缀需保持一致.</p>
												<p class="text-sm">当前数据库表前缀：<?= DB_PREFIX ?></p>
												<hr class="horizontal dark">
												<div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
													<button type="button" class="btn btn-sm btn-primary mb-2 mb-md-0" id="import_backup">导入备份</button>
													<!-- 导入备份弹窗 -->
													<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
														<div class="modal-dialog modal-dialog-centered">
															<div class="modal-content">
																<div class="modal-header">
																	<h5 class="modal-title" id="importModalLabel">导入数据库备份</h5>
																	<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
																</div>
																<form method="post" action="<?= SELF ?>?act=setting&import" enctype="multipart/form-data">
																	<div class="modal-body">
																		<input name="token" id="import_token" value="<?= LoginAuth::genToken() ?>" type="hidden">
																		<input type="file" name="sqlfile" required accept=".sql,.zip" class="form-control mb-3">
																	</div>
																	<div class="modal-footer">
																		<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
																		<input type="submit" value="导入" class="btn btn-success">
																	</div>
																</form>
															</div>
														</div>
													</div>
													<div class="avatar-group">
														<a href="javascript:;" class="avatar avatar-lg avatar-xs rounded-circle" data-bs-toggle="tooltip" data-bs-placement="bottom">
															<img alt="Image placeholder" src="https://q4.qlogo.cn/headimg_dl?dst_uin=271106735&spec=640">
														</a>
														<a href="javascript:;" class="avatar avatar-lg avatar-xs rounded-circle" data-bs-toggle="tooltip" data-bs-placement="bottom">
															<img alt="Image placeholder" src="https://q4.qlogo.cn/headimg_dl?dst_uin=271106735&spec=640">
														</a>
													</div>
												</div>
											</div>
										</div>
									</div>
									<div class="col-12 col-md-4">
										<div class="card h-100">
											<div class="card-header pb-0 p-3">
												<h6 class="mb-0">更新缓存</h6>
											</div>
											<div class="card-body p-3">
												<p class="text-sm">缓存可以加快站点的加载速度，通常系统会自动更新缓存。特殊情况需要手动更新，如：缓存文件被修改、手动修改过数据库、页面出现异常等.</p>
												<hr class="horizontal dark">
												<div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
													<button type="button" class="btn btn-sm btn-primary mb-2 mb-md-0" id="hope-btn-update-cache">更新缓存</button>
													<div class="avatar-group">
														<a href="javascript:;" class="avatar avatar-lg avatar-xs rounded-circle" data-bs-toggle="tooltip" data-bs-placement="bottom">
															<img alt="Image placeholder" src="https://q4.qlogo.cn/headimg_dl?dst_uin=271106735&spec=640">
														</a>
														<a href="javascript:;" class="avatar avatar-lg avatar-xs rounded-circle" data-bs-toggle="tooltip" data-bs-placement="bottom">
															<img alt="Image placeholder" src="https://q4.qlogo.cn/headimg_dl?dst_uin=271106735&spec=640">
														</a>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>




					</div>
				
				</div>
			</div>
			<script>
				$("#menu_system_manage").addClass('active');
				$("#menu_system").addClass('show');
				$("#menu_setting").addClass('active');
				// 页面加载时初始化状态
				document.addEventListener('DOMContentLoaded', function() {
					const cacheBtn = document.getElementById('hope-btn-update-cache');
					if (cacheBtn) {
						cacheBtn.addEventListener('click', function () {
							const fd = new FormData();
							fd.append('action', 'Cache');
							fd.append('token', <?= json_encode(LoginAuth::genToken(), JSON_UNESCAPED_UNICODE) ?>);
							cacheBtn.disabled = true;
							fetch('<?= SELF ?>?act=setting', {
								method: 'POST',
								body: fd,
								credentials: 'same-origin'
							}).then(function () {
								window.location = '<?= SELF ?>?act=setting&code=cache_ok#set5';
							}).catch(function () {
								cacheBtn.disabled = false;
								alert('更新缓存失败，请重试');
							});
						});
					}
					const detectUrlCheckbox = document.querySelector('input[name="detect_url"]');
					const blogUrlInput = document.querySelector('input[name="siteurl"]');
					
					// 设置初始状态
					if (detectUrlCheckbox && blogUrlInput) {
						const syncSiteUrlState = function () {
							if (detectUrlCheckbox.checked) {
								blogUrlInput.readOnly = true;
								if (!blogUrlInput.value) {
									blogUrlInput.value = blogUrlInput.dataset.currentUrl || '';
								}
							} else {
								blogUrlInput.readOnly = false;
							}
						};
						syncSiteUrlState();
						detectUrlCheckbox.addEventListener('change', syncSiteUrlState);
					}

					const closeSwitch = document.getElementById('site-close-switch');
					const closeFields = document.getElementById('site-close-fields');
					if (closeSwitch && closeFields) {
						closeSwitch.addEventListener('change', function() {
							closeFields.style.display = this.checked ? '' : 'none';
						});
					}
				});

				function changeOptions(i){
					$('input[name^=rule]').each(function(){
						var s='radio_' + $(this).prop('name');
						$(this).val( $("input[type='radio'][name='"+s+"']").eq(i).val() );
					});
					if(i=='0'|| i=='1'){
						$("input[name^='radio']").prop('disabled',true);
					}else{
						$("input[name^='radio']").prop('disabled',false);
					}
				}

				// 提交表单
				$("#form_log").submit(function(event) {
					event.preventDefault();
					$.ajax({
						type: "POST",
						url: $(this).attr('action'),
						data: $(this).serialize(),
						dataType: 'json',
						success: function(res) {
							if (hopeApiSuccess(res.code)) {
								Toast.fire({ icon: 'success', title: (window.HOPE_I18N && window.HOPE_I18N.save_success) || '保存成功' });
								setTimeout(function() { window.location.reload(); }, 500);
							} else {
								Toast.fire({ icon: 'error', title: res.msg || 'Error' });
							}
						},
						error: function(xhr) {
							var msg = 'Error';
							try { msg = JSON.parse(xhr.responseText).msg; } catch (e) {}
							Toast.fire({ icon: 'error', title: msg });
						}
					});
				});

				document.getElementById('download_backup').onclick = function (e) {
					e.preventDefault();
					e.stopPropagation();
					var zipEl = document.querySelector('#set5 input[name="zipbak"]:checked');
					var zip = zipEl ? zipEl.value : '0';
					var token = <?= json_encode(LoginAuth::genToken(), JSON_UNESCAPED_UNICODE) ?>;
					// 与「重置 API 密钥」相同：带 token 的跳转，浏览器按 Content-Disposition 弹出下载
					window.location.href = '<?= SELF ?>?act=setting&backup&zipbak=' + encodeURIComponent(zip)
						+ '&token=' + encodeURIComponent(token);
				};

				document.getElementById('import_backup').onclick = function() {
					var modal = new bootstrap.Modal(document.getElementById('importModal'));
					modal.show();
				};
	<?php if (Input::getStrVar('code') === 'import'): ?>Toast.fire({
		icon: 'success',
		title: '备份导入成功'
	});
	<?php endif ?><?php if (Input::getStrVar('code') === 'mc'): ?>Toast.fire({
		icon: 'success',
		title: '缓存更新成功'
	});
	<?php endif ?><?php if (Input::getStrVar('code') === 'ok_reset'): ?>Toast.fire({
		icon: 'success',
		title: '接口秘钥重置成功'
	});
	<?php endif ?><?php if (Input::getStrVar('code') === 'error_a'): ?>Toast.fire({
		icon: 'error',
		title: '服务器空间不支持zip，无法导入zip备份'
	});
	<?php endif ?><?php if (Input::getStrVar('code') === 'error_b'): ?>Toast.fire({
		icon: 'error',
		title: '上传备份失败'
	});
	<?php endif ?><?php if (Input::getStrVar('code') === 'error_c'): ?>Toast.fire({
		icon: 'error',
		title: '错误的备份文件'
	});
	<?php endif ?><?php if (Input::getStrVar('code') === 'error_d'): ?>Toast.fire({
		icon: 'error',
		title: '服务器空间不支持zip，无法导出zip备份'
	});
	<?php endif ?>
			</script>