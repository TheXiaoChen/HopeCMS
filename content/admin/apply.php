<?php if (isset($_GET['error'])): ?><div class="alert alert-danger">商店暂不可用，可能是网络问题</div><?php endif ?>
<style>
	.apply-store-cover {
		border-radius: var(--bs-card-border-radius);
		height: 170px;
		object-fit: cover;
		background: #f8f9fa;
	}
	.apply-store-desc {
		min-height: 2.8em;
		line-height: 1.45;
		display: -webkit-box;
		-webkit-line-clamp: 2;
		-webkit-box-orient: vertical;
		overflow: hidden;
	}
	.apply-store-price {
		display: inline-flex;
		flex-direction: column;
		align-items: flex-end;
		gap: 2px;
		line-height: 1.25;
		min-height: 0;
	}
	.apply-store-price .apply-price-line {
		display: inline-flex;
		align-items: baseline;
		justify-content: flex-end;
		flex-wrap: wrap;
		gap: 4px;
	}
	.apply-store-price .apply-price-old {
		display: inline;
		font-size: 11px;
		color: #999;
		text-decoration: line-through;
	}
	.apply-store-price .apply-price-sale { font-weight: 600; }
	.apply-store-price .apply-price-free { color: #137333; font-weight: 600; }
	.apply-store-actions .btn { margin-left: 2px; }
	.apply-admin-countdown {
		margin: 0;
		padding: 1px 6px;
		border-radius: 4px;
		background: rgba(234, 67, 53, 0.08);
		font-size: 10px;
		line-height: 1.4;
		color: #c5221f;
		white-space: nowrap;
	}
	.apply-admin-countdown-time {
		font-variant-numeric: tabular-nums;
		font-weight: 700;
	}
	.apply-store-card .update-badge.apply-update-badge {
		position: absolute;
		top: 10px;
		left: 10px;
		z-index: 2;
		background: #fb8c00;
		color: #fff;
		font-size: 11px;
		padding: 2px 8px;
		border-radius: 4px;
	}
	.blurred-background {
		background: rgba(200, 200, 200, 0.4);
		backdrop-filter: blur(8px);
	}

	.loading {
		pointer-events: none;
		opacity: 0.7;
	}

	.loading:after {
		content: "加载中...";
		margin-left: 8px;
		font-size: 12px;
	}
</style>
<div class="card">
	<div class="card-body p-3">
		<div class="row gx-4">
			<div class="col-auto">
				<div class="avatar avatar-xl position-relative">
					<img id="apply-bind-avatar" src="<?= htmlspecialchars($apply_display_avatar !== '' ? $apply_display_avatar : '../content/admin/img/Face.png') ?>" alt="profile_image"
						class="w-100 border-radius-lg shadow-sm" data-default-src="../content/admin/img/Face.png"
						onerror="this.onerror=null;this.src=this.getAttribute('data-default-src');">
				</div>
			</div>
			<div class="col-auto my-auto">
				<div class="h-100">
					<h5 class="mb-1">应用中心</h5>
					<p class="mb-0 font-weight-bold text-sm" id="apply-bind-summary">
						<?php if ($apply_bind_valid): ?>
						<span class="text-success" id="apply-bind-nickname"><?= htmlspecialchars($apply_display_name) ?></span>
						<?php if ($apply_display_balance !== null): ?>
						 · 余额 <span class="text-danger" id="apply-bind-balance">¥<?= htmlspecialchars(number_format($apply_display_balance, 2)) ?></span>
						<?php else: ?>
						 · 余额 <span class="text-danger" id="apply-bind-balance">--</span>
						<?php endif; ?>
						<?php elseif ($apply_is_bound): ?>
						账号已失效，请重新绑定商店邮箱
						<?php else: ?>
						未绑定商店账号
						<?php endif; ?>
					</p>
				</div>
			</div>
			<div class="col-lg-4 col-md-6 my-sm-auto ms-sm-auto me-sm-0 mx-auto mt-3">
				<div class="nav-wrapper position-relative end-0">
					<ul class="nav nav-pills nav-fill p-1" role="tablist" id="mainTabNav">
						<li class="nav-item">
							<a class="nav-link mb-0 px-0 py-1 active d-flex align-items-center justify-content-center " data-bs-toggle="tab" href="#all" role="tab" aria-selected="true" data-area="all">
								<i class="fa fa-border-all"></i>
								<span class="ms-2">全部</span>
							</a>
						</li>
						<li class="nav-item">
							<a class="nav-link mb-0 px-0 py-1 d-flex align-items-center justify-content-center "
								data-bs-toggle="tab" href="#theme" role="tab" aria-selected="false" data-area="theme">
								<i class="fa fa-palette"></i>
								<span class="ms-2">主题</span>
							</a>
						</li>
						<li class="nav-item">
							<a class="nav-link mb-0 px-0 py-1 d-flex align-items-center justify-content-center "
								data-bs-toggle="tab" href="#plugin" role="tab" aria-selected="false" data-area="plugin">
								<i class="fa fa-plug"></i>
								<span class="ms-2">插件</span>
							</a>
						</li>
						<li class="nav-item">
							<a class="nav-link mb-0 px-0 py-1 d-flex align-items-center justify-content-center "
								data-bs-toggle="tab" href="#app5" role="tab" aria-selected="false" data-area="app5">
								<i class="fa fa-user"></i>
								<span class="ms-2">我的</span>
							</a>
						</li>
					</ul>
				</div>
			</div>
		</div>
	</div>
</div>

<section class="py-3 tab-content tab-space">
	<div class="p-0 tab-pane active" id="all">
		<div class="card">
			<div class="card-body pb-2">
				<div class="d-lg-flex">
					<div class="py-1">
						<a href="javascript:;" class="badge bg-gradient-primary p-2" data-type="all" data-tag="" data-sort="">全部</a>
						<a href="javascript:;" class="badge badge-light text-primary p-2" data-type="theme" data-tag="博客" data-sort="">博客</a>
						<a href="javascript:;" class="badge badge-light text-primary p-2" data-type="theme" data-tag="资源" data-sort="">资源</a>
						<a href="javascript:;" class="badge badge-light text-primary p-2" data-type="theme" data-tag="社区" data-sort="">社区</a>
						<a href="javascript:;" class="badge badge-light text-primary p-2" data-type="theme" data-tag="企业" data-sort="">企业</a>
						<a href="javascript:;" class="badge badge-light text-primary p-2" data-type="plugin" data-tag="SEO优化" data-sort="">SEO优化</a>
						<a href="javascript:;" class="badge badge-light text-primary p-2" data-type="plugin" data-tag="内容创作" data-sort="">内容创作</a>
						<a href="javascript:;" class="badge badge-light text-primary p-2" data-type="plugin" data-tag="装饰特效" data-sort="">装饰特效</a>
						<a href="javascript:;" class="badge badge-light text-primary p-2" data-type="plugin" data-tag="官方生态" data-sort="">官方生态</a>
					</div>
					<div class="ms-md-auto d-flex align-items-start">
						<form action="javascript:;" method="get" id="searchForm">
							<input type="hidden" name="act" value="apply">
							<div class="input-group">
								<span class="input-group-text text-body"><i class="fa fa-search" aria-hidden="true"></i></span>
								<input type="text" class="form-control" name="keyword" placeholder="搜索应用..."
									onfocus="focused(this)" onfocusout="defocused(this)" id="autoSearchInput">
							</div>
						</form>
						<div class="dropdown d-inline">
							<a href="javascript:;" class="btn bg-gradient-primary ms-2 dropdown-toggle" data-bs-toggle="dropdown" id="navbarDropdownMenuLink2">排序</a>
							<ul class="dropdown-menu dropdown-menu-lg-start px-2 py-3" aria-labelledby="navbarDropdownMenuLink2" data-popper-placement="left-start">
								<li><a href="javascript:;" class="dropdown-item border-radius-md" data-type="all" data-tag="" data-sort="down">下载榜</a></li>
								<li><a href="javascript:;" class="dropdown-item border-radius-md" data-type="all" data-tag="" data-sort="hot">热搜榜</a></li>
								<li><a href="javascript:;" class="dropdown-item border-radius-md" data-type="all" data-tag="" data-sort="new">最新榜</a></li>
								<li>
									<hr class="horizontal dark my-2">
								</li>
								<li><a href="javascript:;" class="dropdown-item border-radius-md" data-type="all" data-tag="" data-sort="free">免费</a></li>
								<li><a href="javascript:;" class="dropdown-item border-radius-md" data-type="all" data-tag="" data-sort="paid">付费</a></li>
								<li><a href="javascript:;" class="dropdown-item border-radius-md" data-type="all" data-tag="" data-sort="promo">特惠</a></li>
							</ul>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="row mt-lg-4 mt-2 app-list">
			<?php if (!empty($app['apps'])): ?>
				<?php foreach ($app['apps'] as $v): hope_apply_render_store_card($v); endforeach; ?>
			<?php else: ?>
				<div class="col-md-12" id="emptyTip">暂未找到结果，应用中心进货中，敬请期待！</div>
			<?php endif ?>
		</div>
	</div>
	<div class="p-0 tab-pane" id="theme">
		<div class="card">
			<div class="card-body pb-2">
				<div class="d-lg-flex">
					<div class="py-1">
						<?php
						// 主题tab标签按钮
						if (isset($theme_categories) && is_array($theme_categories)) {
							$first = true;
							foreach ($theme_categories as $name => $list) {
								$active = $first ? 'bg-gradient-primary' : 'badge-light text-primary';
								$data_tag = $name === '全部' ? '' : $name;
								echo '<a href="javascript:;" class="badge ' . $active . ' p-2" data-type="theme" data-tag="' . $data_tag . '" data-sort="">' . $name . '</a>';
								$first = false;
							}
						}
						?>
					</div>
					<div class="ms-md-auto d-flex align-items-start">
						<form action="<?= SELF ?>" method="get">
							<input type="hidden" name="act" value="apply">
							<input type="hidden" name="theme">
							<input type="hidden" name="hide" value="n">
							<div class="input-group">
								<span class="input-group-text text-body"><i class="fa fa-search" aria-hidden="true"></i></span>
								<input type="text" class="form-control" name="keyword" placeholder="搜索主题..." onfocus="focused(this)" onfocusout="defocused(this)" id="autoSearchInput">
							</div>
						</form>
						<div class="dropdown d-inline">
							<a href="javascript:;" class="btn bg-gradient-primary ms-2 dropdown-toggle" data-bs-toggle="dropdown" id="navbarDropdownMenuLink2">排序</a>
							<ul class="dropdown-menu dropdown-menu-lg-start px-2 py-3" aria-labelledby="navbarDropdownMenuLink2" data-popper-placement="left-start">
								<li><a href="javascript:;" class="dropdown-item border-radius-md" data-type="theme" data-tag="" data-sort="down">下载榜</a></li>
								<li><a href="javascript:;" class="dropdown-item border-radius-md" data-type="theme" data-tag="" data-sort="hot">热搜榜</a></li>
								<li><a href="javascript:;" class="dropdown-item border-radius-md" data-type="theme" data-tag="" data-sort="new">最新榜</a></li>
								<li>
									<hr class="horizontal dark my-2">
								</li>
								<li><a href="javascript:;" class="dropdown-item border-radius-md" data-type="theme" data-tag="" data-sort="free">免费</a></li>
								<li><a href="javascript:;" class="dropdown-item border-radius-md" data-type="theme" data-tag="" data-sort="paid">付费</a></li>
								<li><a href="javascript:;" class="dropdown-item border-radius-md" data-type="theme" data-tag="" data-sort="promo">特惠</a></li>
							</ul>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="row mt-lg-4 mt-2 app-list">
			<?php if (!empty($app['theme'])): ?>
				<?php foreach ($app['theme'] as $v): hope_apply_render_store_card($v); endforeach; ?>
			<?php else: ?>
				<div class="col-md-12">暂未找到结果，应用中心进货中，敬请期待！</div>
			<?php endif ?>
		</div>
	</div>
	<div class="p-0 tab-pane" id="plugin">
		<div class="card">
			<div class="card-body pb-2">
				<div class="d-lg-flex">
					<div class="py-1">
						<?php
						// 插件tab标签按钮
						if (isset($plugin_categories) && is_array($plugin_categories)) {
							$first = true;
							foreach ($plugin_categories as $name => $list) {
								$active = $first ? 'bg-gradient-primary' : 'badge-light text-primary';
								$data_tag = $name === '全部' ? '' : $name;
								echo '<a href="javascript:;" class="badge ' . $active . ' p-2" data-type="plugin" data-tag="' . $data_tag . '" data-sort="">' . $name . '</a>';
								$first = false;
							}
						}
						?>
					</div>
					<div class="ms-md-auto d-flex align-items-start">
						<form action="<?= SELF ?>" method="get">
							<input type="hidden" name="act" value="apply">
							<input type="hidden" name="plugin">
							<div class="input-group">
								<span class="input-group-text text-body"><i class="fa fa-search" aria-hidden="true"></i></span>
								<input type="text" class="form-control" name="keyword" placeholder="搜索插件..." onfocus="focused(this)" onfocusout="defocused(this)" id="autoSearchInput">
							</div>
						</form>
						<div class="dropdown d-inline">
							<a href="javascript:;" class="btn bg-gradient-primary ms-2 dropdown-toggle" data-bs-toggle="dropdown" id="navbarDropdownMenuLink2">排序</a>
							<ul class="dropdown-menu dropdown-menu-lg-start px-2 py-3" aria-labelledby="navbarDropdownMenuLink2" data-popper-placement="left-start">
								<li><a href="javascript:;" class="dropdown-item border-radius-md" data-type="plugin" data-tag="" data-sort="down">下载榜</a></li>
								<li><a href="javascript:;" class="dropdown-item border-radius-md" data-type="plugin" data-tag="" data-sort="hot">热搜榜</a></li>
								<li><a href="javascript:;" class="dropdown-item border-radius-md" data-type="plugin" data-tag="" data-sort="new">最新榜</a></li>
								<li>
									<hr class="horizontal dark my-2">
								</li>
								<li><a href="javascript:;" class="dropdown-item border-radius-md" data-type="plugin" data-tag="" data-sort="free">免费</a></li>
								<li><a href="javascript:;" class="dropdown-item border-radius-md" data-type="plugin" data-tag="" data-sort="paid">付费</a></li>
								<li><a href="javascript:;" class="dropdown-item border-radius-md" data-type="plugin" data-tag="" data-sort="promo">特惠</a></li>
							</ul>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row mt-lg-4 mt-2 app-list">
			<?php if (!empty($app['plugin'])): ?>
				<?php foreach ($app['plugin'] as $v): hope_apply_render_store_card($v); endforeach; ?>
			<?php else: ?>
				<div class="col-md-12">暂未找到结果，应用中心进货中，敬请期待！</div>
			<?php endif ?>
		</div>

	</div>
	<div class="p-0 tab-pane" id="app5">
		<div class="card mb-4" id="apply-bind-card">
			<div class="card-body">
				<?php if ($apply_bind_valid): ?>
				<div id="apply-bind-ok-panel">
					<h5 class="mb-3">商店账号</h5>
					<p class="text-sm text-success mb-2" id="apply-bind-status-ok">
						当前账号：<?= htmlspecialchars($apply_display_name) ?><?php if ($apply_display_balance !== null): ?> · 余额 ¥<?= htmlspecialchars(number_format($apply_display_balance, 2)) ?><?php endif; ?>
					</p>
					<p class="text-sm text-muted mb-2">绑定有效，无需重复绑定。账号失效后将自动要求重新绑定。</p>
					<p class="text-sm mt-2 mb-0">
						<a href="<?= htmlspecialchars($apply_recharge_url) ?>" target="_blank" rel="noopener" class="text-primary apply-recharge-link">前往官方商店充值余额 &rarr;</a>
					</p>
				</div>
				<div id="apply-bind-form-panel" style="display:none;">
				<?php else: ?>
				<div id="apply-bind-ok-panel" style="display:none;">
					<h5 class="mb-3">商店账号</h5>
					<p class="text-sm text-success mb-2" id="apply-bind-status-ok"></p>
					<p class="text-sm text-muted mb-2">绑定有效，无需重复绑定。</p>
					<p class="text-sm mt-2 mb-0">
						<a href="<?= htmlspecialchars($apply_recharge_url) ?>" target="_blank" rel="noopener" class="text-primary apply-recharge-link">前往官方商店充值余额 &rarr;</a>
					</p>
				</div>
				<div id="apply-bind-form-panel">
				<?php endif; ?>
					<h5 class="mb-3">绑定商店账号</h5>
					<p class="text-sm text-muted">填写官方应用商店的<strong>用户邮箱</strong>与 <strong>API 密钥</strong>（在官方站个人中心复制）。绑定即对接商店 API：校验账号、拉取应用、下载更新等均使用该凭证。</p>
					<?php if ($apply_is_bound && !$apply_bind_valid): ?>
					<div class="alert alert-warning text-sm py-2">当前绑定已失效，请使用商店邮箱与 API 密钥重新绑定。</div>
					<?php endif; ?>
					<form id="apply-bind-form" class="row g-3 align-items-end">
						<div class="col-md-4">
							<label class="form-label">商店用户邮箱</label>
							<input type="email" class="form-control" id="apply-email" name="email" value="<?= htmlspecialchars($apply_show_bind_form ? $apply_default_email : '') ?>" placeholder="官方商店登录邮箱" autocomplete="email">
						</div>
						<div class="col-md-5">
							<label class="form-label">API 密钥</label>
							<input type="text" class="form-control" id="apply-api-key" name="api_password" value="" placeholder="官方商店 API 密钥" autocomplete="off">
						</div>
						<div class="col-md-3">
							<button type="submit" class="btn btn-primary w-100">绑定激活</button>
						</div>
					</form>
					<p class="text-sm mt-2 mb-0 text-muted" id="apply-bind-form-hint">
						填写邮箱与 API 密钥后点击绑定
					</p>
					<p class="text-sm mt-2 mb-0">
						<a href="<?= htmlspecialchars($apply_recharge_url) ?>" target="_blank" rel="noopener" class="text-primary apply-recharge-link">前往官方商店充值余额 &rarr;</a>
					</p>
				</div>
			</div>
		</div>
		<div class="card">
			<div class="card-header">
				<div class="row align-items-center">
					<div class="col">
						<h5 class="mb-0">我的已购应用</h5>
						<p class="text-sm text-muted mb-0" id="mine-count-tip">加载中…</p>
					</div>
				</div>
			</div>
			<div class="card-body">
				<div class="row mt-lg-2 mt-2 app-list" id="mine-app-list">
					<div class="col-md-12 text-muted">请先绑定 API 密钥后查看已购应用</div>
				</div>
			</div>
		</div>
	</div>

	<div class="col-md-12 text-center my-4" id="loadingTip" style="display: none;">
		<div class="spinner-border text-primary" role="status">
			<span class="visually-hidden">Loading...</span>
		</div>
	</div>

</section>
<script>
	$(function() {
		initApplyStoreCountdowns();
		const applyPurchaseBase = <?= json_encode($apply_purchase_base, JSON_UNESCAPED_UNICODE) ?>;
		const applyRechargeUrl = <?= json_encode($apply_recharge_url, JSON_UNESCAPED_UNICODE) ?>;
		const applyLocalVersions = <?= json_encode($apply_local_versions, JSON_UNESCAPED_UNICODE) ?>;
		let applyBindValid = <?= $apply_bind_valid ? 'true' : 'false' ?>;
		const applyStoreToken = <?= json_encode(LoginAuth::genToken(), JSON_UNESCAPED_UNICODE) ?>;
		// 自动搜索功能
		let searchTimer = null;
		$(document).on('input', '#autoSearchInput', function() {
			clearTimeout(searchTimer);
			const val = $(this).val();
			searchTimer = setTimeout(function() {
				state.keyword = val;
				state.pages[state.area] = 1;
				state.hasMore = true;
				fetchApps(false);
			}, 500);
		});
		$("#menu_apply").addClass('active');
		setTimeout(hideActived, 3600);
		// 全局状态
		const state = {
			area: "all",
			type: "all", // 应用类型：all / theme / plugin
			tag: "", // 标签筛选，用于按分类筛选应用
			sort: "", // 排序方式，如："down"(下载榜), "hot"(热搜榜), "new"(最新榜), "free"(免费), "paid"(付费)
			keyword: "", // 搜索关键词
			author_id: 0, // 开发者筛选
			author_name: "",
			pages: {
				"all": 1,
				"theme": 1,
				"plugin": 1,
				"app5": 1 // 我的
			},
			hasMore: true, // 是否还有更多数据可加载，用于控制滚动加载
			loading: false, // 局部加载状态，控制列表区域的加载动画
			globalLoading: false // 全局加载状态，控制整个页面的加载遮罩层
		};

		// 主tab切换，切换state.area并高亮
		$('#mainTabNav .nav-link').on('click', function(e) {
			var $this = $(this);
			var area = $this.data('area');
			if (area && state.area !== area) {
				state.area = area;
				state.type = area === 'app5' ? 'mine' : area;
				state.hasMore = true;
			}
			if (area === 'app5') {
				loadMineApps();
			} else if (area === 'all' || area === 'theme' || area === 'plugin') {
				state.tag = '';
				state.sort = '';
				fetchApps(false);
			}
		});

		function loadMineApps() {
			$('#mine-count-tip').text('加载中…');
			$.get('<?= SELF ?>', { act: 'apply', ajax_mine: 1 }, function(res) {
				if (res.code !== 200 || !Array.isArray(res.data.apps)) {
					$('#mine-app-list').html('<div class="col-md-12 text-danger">加载失败，请先绑定 API 密钥</div>');
					$('#mine-count-tip').text('加载失败');
					return;
				}
				$('#mine-count-tip').text('已购应用 ' + res.data.count);
				if (!res.data.apps.length) {
					$('#mine-app-list').html('<div class="col-md-12 text-muted">暂无已购应用</div>');
					return;
				}
				state.area = 'app5';
				renderApps(res.data.apps, false);
			}, 'json');
		}

		function updateApplyBindAvatar(url) {
			var $img = $('#apply-bind-avatar');
			if (!$img.length) return;
			var fallback = $img.data('default-src') || '../content/admin/img/Face.png';
			$img.attr('src', url ? url : fallback);
		}

		function updateApplyBindSummary(nickname, balance) {
			nickname = nickname || '';
			var html = '<span class="text-success" id="apply-bind-nickname">' + $('<div>').text(nickname).html() + '</span>';
			if (typeof balance === 'number' && !isNaN(balance)) {
				html += ' · 余额 <span class="text-danger" id="apply-bind-balance">¥' + balance.toFixed(2) + '</span>';
			} else {
				html += ' · 余额 <span class="text-danger" id="apply-bind-balance">--</span>';
			}
			$('#apply-bind-summary').html(html);
			var statusText = '当前账号：' + nickname;
			if (typeof balance === 'number' && !isNaN(balance)) {
				statusText += ' · 余额 ¥' + balance.toFixed(2);
			}
			$('#apply-bind-status-ok').text(statusText);
		}

		function updateApplyBindStatus(data) {
			var nickname = (data && data.nickname) ? data.nickname : ((data && data.username) ? data.username : $('#apply-email').val().trim());
			var balance = (data && typeof data.balance !== 'undefined') ? parseFloat(data.balance) : NaN;
			updateApplyBindSummary(nickname, isNaN(balance) ? null : balance);
			updateApplyBindAvatar(data && data.profile_image ? data.profile_image : '');
			applyBindValid = true;
			$('#apply-bind-form-panel').hide();
			$('#apply-bind-ok-panel').show();
			if (data && data.recharge_url) {
				$('.apply-recharge-link').attr('href', data.recharge_url);
			}
		}

		$('#apply-bind-form').on('submit', function(e) {
			e.preventDefault();
			var email = $('#apply-email').val().trim();
			var key = $('#apply-api-key').val().trim();
			if (!email) {
				Toast.fire({ icon: 'warning', title: '请输入商店用户邮箱' });
				return;
			}
			if (email.indexOf('@') < 0) {
				Toast.fire({ icon: 'warning', title: '请输入有效的商店用户邮箱' });
				return;
			}
			if (!key) {
				Toast.fire({ icon: 'warning', title: '请输入 API 密钥' });
				return;
			}
			$.post('<?= SELF ?>?act=apply&bind_account=1', {
				token: '<?= LoginAuth::genToken() ?>',
				username: email,
				email: email,
				api_password: key
			}, function(res) {
				if (hopeApiSuccess(res.code)) {
					var payload = res.data || {};
					Toast.fire({ icon: 'success', title: payload.msg || res.msg || '绑定成功' });
					updateApplyBindStatus(payload);
					loadMineApps();
				} else {
					Toast.fire({ icon: 'error', title: res.msg || '绑定失败' });
				}
			}, 'json').fail(function(xhr) {
				var msg = '绑定请求失败';
				var res = xhr.responseJSON;
				if (!res && xhr.responseText) {
					try { res = JSON.parse(xhr.responseText); } catch (err) { res = null; }
				}
				if (res && res.msg) msg = res.msg;
				Toast.fire({ icon: 'error', title: msg });
			});
		});

		<?php if ($apply_bind_valid): ?>loadMineApps();<?php endif; ?>

		// 显示/隐藏加载状态
		function toggleLoading(show) {
			state.loading = show;
			if (show) {
				$("#loadingTip").show();
				$("#loadMoreBtn").addClass("loading");
				$("#emptyTip").hide();
			} else {
				$("#loadingTip").hide();
				$("#loadMoreBtn").removeClass("loading");
			}
		}

		// 更新标签高亮
		function updateActiveTag($target) {
			$target.parent().find("a").removeClass("bg-gradient-primary").addClass("badge-light text-primary");
			$target.removeClass("badge-light text-primary").addClass("bg-gradient-primary");
		}

		// 渲染应用卡片（统一主题风格，主题/插件一致）
		function escapeHtml(text) {
			return $('<div>').text(text == null ? '' : String(text)).html();
		}

		function applyStoreHasPromo(app) {
			var price = parseFloat(app.price) || 0;
			var promo = parseFloat(app.promo_price) || 0;
			if (price <= 0 || promo <= 0 || promo >= price) return false;
			var end = parseInt(app.promo_end_time, 10) || 0;
			if (end > 0 && end * 1000 <= Date.now()) return false;
			return true;
		}

		function applyStorePriceHtml(app) {
			var price = parseFloat(app.price) || 0;
			if (price <= 0) return '<span class="apply-price-free">免费</span>';
			if (applyStoreHasPromo(app)) {
				var sale = parseFloat(app.promo_price) || 0;
				var end = parseInt(app.promo_end_time, 10) || 0;
				var html = '<span class="apply-price-line">'
					+ '<span class="apply-price-sale text-danger">¥' + sale.toFixed(2) + '</span>'
					+ '<span class="apply-price-old">¥' + price.toFixed(2) + '</span>'
					+ '</span>';
				if (end > 0) {
					html += '<div class="apply-admin-countdown" data-promo-end="' + end + '">'
						+ '<span class="apply-admin-countdown-label">限时</span> '
						+ '<span class="apply-admin-countdown-time">--:--:--</span></div>';
				}
				return html;
			}
			return '<span class="apply-price-sale text-danger">¥' + price.toFixed(2) + '</span>';
		}

		function applyStoreDetailUrl(app) {
			return app.detail_url || (applyPurchaseBase + '/apps/' + (app.id || 0));
		}

		function applyStoreActionsHtml(app) {
			var detailUrl = applyStoreDetailUrl(app);
			var dl = encodeURIComponent(app.download_url || '');
			var type = app.app_type || 'plugin';
			var alias = escapeHtml(app.alias || '');
			var installBtn = '<a href="#" class="btn btn-success btn-xs mb-0 installBtn" data-url="' + dl + '" data-type="' + type + '" data-alias="' + alias + '">安装</a>';
			var html = '<a href="' + detailUrl + '" class="btn btn-outline-primary btn-xs mb-0" target="_blank" rel="noopener" title="前往官网查看介绍">详情</a>';
			var canDownload = (typeof app.can_download !== 'undefined')
				? !!app.can_download
				: (app.purchased === true || (parseFloat(app.price) || 0) <= 0);
			if (canDownload) {
				return html + installBtn;
			}
			if ((parseFloat(app.price) || 0) > 0) {
				if (!applyBindValid) {
					return html + '<span class="btn btn-light btn-xs disabled mb-0" title="请先绑定商店账号">需绑定</span>';
				}
				if (app.balance_enough) {
					var balTitle = (typeof app.user_balance !== 'undefined')
						? (' title="余额 ¥' + (parseFloat(app.user_balance) || 0).toFixed(2) + '"')
						: '';
					return html + '<a href="#" class="btn btn-xs btn-danger mb-0 applyStoreBuyBtn" data-apply-id="' + (app.id || 0) + '"' + balTitle + '>购买</a>';
				}
				return html + '<a href="' + applyRechargeUrl + '" class="btn btn-xs btn-warning mb-0" target="_blank" rel="noopener" title="余额不足，请先充值">充值</a>';
			}
			return html + '<span class="btn btn-light btn-xs disabled mb-0" title="请先绑定商店账号">需绑定</span>';
		}

		$(document).on('click', '.applyStoreBuyBtn', function(e) {
			e.preventDefault();
			var $btn = $(this);
			var applyId = $btn.data('apply-id');
			if (!applyId || $btn.hasClass('disabled')) return;
			$btn.addClass('disabled').text('购买中…');
			$.post('<?= SELF ?>?act=apply&purchase_app=1', {
				token: applyStoreToken,
				apply_id: applyId
			}, function(res) {
				if (hopeApiSuccess(res.code)) {
					var okMsg = (res.data && res.data.msg) ? res.data.msg : (res.msg || '购买成功，可点击安装');
					alert(okMsg);
					if (res.data && typeof res.data.balance !== 'undefined') {
						var nick = $('#apply-bind-nickname').text() || '';
						updateApplyBindSummary(nick, parseFloat(res.data.balance));
					}
					if (state.area === 'app5') {
						loadMineApps();
					} else {
						fetchApps(false);
					}
				} else {
					alert(res.msg || '购买失败');
					$btn.removeClass('disabled').text('购买');
				}
			}, 'json').fail(function() {
				alert('购买请求失败');
				$btn.removeClass('disabled').text('购买');
			});
		});

		function applyStoreCountdownTick(el) {
			var end = parseInt(el.getAttribute('data-promo-end'), 10) * 1000;
			var timeEl = el.querySelector('.apply-admin-countdown-time');
			if (!end || !timeEl) return;
			var diff = end - Date.now();
			if (diff <= 0) {
				timeEl.textContent = '已结束';
				return;
			}
			var totalSec = Math.floor(diff / 1000);
			var days = Math.floor(totalSec / 86400);
			var hours = Math.floor((totalSec % 86400) / 3600);
			var minutes = Math.floor((totalSec % 3600) / 60);
			var seconds = totalSec % 60;
			var pad = function (n) { return n < 10 ? '0' + n : String(n); };
			timeEl.textContent = days > 0
				? ('剩' + days + '天' + hours + '时')
				: (pad(hours) + ':' + pad(minutes) + ':' + pad(seconds));
		}

		function initApplyStoreCountdowns(root) {
			var scope = root ? $(root) : $(document);
			scope.find('[data-promo-end]').each(function () {
				applyStoreCountdownTick(this);
			});
		}

		if (!window.__applyStoreCountdownTimer) {
			window.__applyStoreCountdownTimer = setInterval(function () {
				document.querySelectorAll('[data-promo-end]').forEach(applyStoreCountdownTick);
			}, 1000);
		}

		function switchApplyStoreTab(area) {
			var $tab = $('#mainTabNav .nav-link[data-area="' + area + '"]');
			if (!$tab.length) return;
			$('#mainTabNav .nav-link').removeClass('active').attr('aria-selected', 'false');
			$tab.addClass('active').attr('aria-selected', 'true');
			$('.tab-pane').removeClass('active show');
			$('#' + area).addClass('active show');
		}

		function updateAuthorFilterBar() {
			$('.apply-author-filter-bar').remove();
			if (!state.author_id || state.area === 'app5') return;
			var name = state.author_name || ('UID ' + state.author_id);
			var html = '<div class="col-md-12 apply-author-filter-bar mb-3">'
				+ '<div class="alert alert-info py-2 px-3 mb-0 d-flex align-items-center justify-content-between">'
				+ '<span><i class="fa fa-user me-1"></i> 正在查看开发者：<strong>' + escapeHtml(name) + '</strong> 的应用</span>'
				+ '<a href="javascript:;" class="btn btn-sm btn-outline-secondary mb-0 apply-author-filter-clear">清除筛选</a>'
				+ '</div></div>';
			$('#' + state.area + ' .app-list').prepend(html);
		}

		function filterByAuthor(authorId, authorName) {
			authorId = parseInt(authorId, 10) || 0;
			if (authorId <= 0) return;
			state.author_id = authorId;
			state.author_name = authorName || '';
			state.area = 'all';
			state.type = 'all';
			state.tag = '';
			state.sort = '';
			state.pages.all = 1;
			state.hasMore = true;
			switchApplyStoreTab('all');
			fetchApps(false);
		}

		function applyStoreAuthorHtml(app) {
			var authorId = parseInt(app.author_id, 10) || 0;
			var author = app.author || '';
			if (authorId > 0) {
				return '<a href="javascript:;" class="apply-store-author-link" data-author-id="' + authorId + '" data-author-name="' + escapeHtml(author) + '" title="查看作品">' + escapeHtml(author) + '</a>';
			}
			return escapeHtml(author || '未知');
		}

		function renderApps(apps, append) {
			var html = '';
			apps.forEach(function (app) {
				var icon = app.icon || (app.app_type === 'theme' ? './content/admin/img/theme-icon.png' : './content/admin/img/plugin-icon.png');
				var typeBadge = app.app_type === 'theme'
					? '<span class="badge badge-success p-1">主题</span>'
					: '<span class="badge badge-primary p-1">插件</span>';
				var vipBadge = app.svip ? '<span class="badge badge-warning p-1">VIP</span>' : '';
				var name = app.name || '';
				var info = app.info || app.summary || app.description || '';
				var update = app.update_Date || app.update_time || '';
				var isMineList = state.area === 'app5';
				var priceBlock = isMineList ? '' : ('<div class="apply-store-price text-sm mb-1">' + applyStorePriceHtml(app) + '</div>');
				html += '<div class="col-lg-3 col-md-6 mb-4">'
					+ '<div class="card hover-shadow apply-store-card" data-app-type="' + escapeHtml(app.app_type) + '" data-app-alias="' + escapeHtml(app.alias || '') + '" data-app-version="' + escapeHtml(app.ver || '') + '">'
					+ '<div class="card-body p-3">'
					+ '<div class="current-badge">v' + escapeHtml(app.ver || '') + '</div>'
					+ '<div class="update-badge apply-update-badge" style="display:none;">有更新</div>'
					+ '<img src="' + escapeHtml(icon) + '" alt="' + escapeHtml(name) + '" class="apply-store-cover w-100">'
					+ '<p class="text-sm mt-3 mb-0 apply-store-desc" title="' + escapeHtml(info) + '">' + escapeHtml(info.substring(0, 48) + (info.length > 48 ? '...' : '')) + '</p>'
					+ '<hr class="horizontal dark">'
					+ '<div class="row align-items-end">'
					+ '<div class="col-7">'
					+ '<h6 class="text-sm mb-1" title="' + escapeHtml(name) + '">'
					+ '<a href="' + applyStoreDetailUrl(app) + '" target="_blank" rel="noopener" class="text-dark">' + escapeHtml(name.substring(0, 16) + (name.length > 16 ? '...' : '')) + '</a>'
					+ '</h6>'
					+ '<p class="text-secondary text-sm mb-1">开发者：' + applyStoreAuthorHtml(app) + '</p>'
					+ '<p class="text-secondary text-sm mb-0">' + typeBadge + vipBadge + ' ' + escapeHtml(String(update)) + '</p>'
					+ '</div>'
					+ '<div class="col-5 text-end">'
					+ priceBlock
					+ '<p class="text-secondary text-xs mb-2"><i class="fa fa-download"></i> ' + escapeHtml(String(app.downloads || app.download_count || 0)) + '</p>'
					+ '<div class="apply-store-actions">' + applyStoreActionsHtml(app) + '</div>'
					+ '</div></div></div></div></div>';
			});

			var $list = $('#' + state.area + ' .app-list');
			if (append) {
				$list.append(html);
			} else {
				$list.html(html);
			}
			initApplyStoreCountdowns($list[0]);
			checkApplyStoreUpdates($list[0]);
			updateAuthorFilterBar();
		}

		function checkApplyStoreUpdates(root) {
			var $cards = root ? $(root).find('.apply-store-card') : $('.apply-store-card');
			var apps = [];
			$cards.each(function() {
				var $card = $(this);
				var alias = $card.data('app-alias');
				var version = $card.data('app-version');
				var type = $card.data('app-type');
				if (!alias) return;
				var localVer = applyLocalVersions[alias];
				if (!localVer) return;
				apps.push({ name: alias, alias: alias, version: localVer, type: type });
			});
			if (!apps.length) return;
			$.post('<?= SELF ?>?act=apply&check_update=1', { apps: apps }, function(res) {
				if (!hopeApiSuccess(res.code) || !Array.isArray(res.data)) return;
				res.data.forEach(function(item) {
					$('.apply-store-card[data-app-alias="' + item.name + '"] .apply-update-badge').show();
				});
			}, 'json');
		}

		// 获取应用列表
		function fetchApps(loadMore = false) {
			if (state.loading || (!state.hasMore && loadMore)) return;
			// 显示局部 loadingTip
			toggleLoading(true);
			const params = {
				act: 'apply',
				ajax_load: 1,
				type: state.type,
				tag: state.tag,
				list: state.sort,
				keyword: state.keyword,
				author_id: state.author_id || '',
				page: loadMore ? state.pages[state.area] + 1 : 1
			};
			$.get('<?= SELF ?>', params, function(res) {
				if (res.code === 200 && Array.isArray(res.data.apps)) {
					if (loadMore) state.pages[state.area]++;
					else state.pages[state.area] = 1;
					state.hasMore = res.has_more !== false;
					renderApps(res.data.apps, loadMore);
					// 更新 UI
					// 只在最后一次加载后显示“已加载全部内容”，并隐藏 loadingTip
					if (!state.hasMore) {
						$("#loadingTip").hide();
						// 避免重复插入
						if ($("#" + state.area + " .app-list .all-loaded-tip").length === 0) {
							$("#" + state.area + " .app-list").append('<div class="col-md-12 text-center text-muted all-loaded-tip">已加载全部内容</div>');
						}
					} else {
						$("#" + state.area + " .app-list .all-loaded-tip").remove();
					}
					if (res.data.apps.length === 0 && !loadMore) {
						$("#" + state.area + " .app-list .all-loaded-tip").remove();
						$("#" + state.area + " .app-list").append('<div class="col-md-12" id="emptyTip">暂未找到结果，应用中心进货中，敬请期待！</div>');
					}
				} else {
					$("#" + state.area + " .app-list").empty().append('<div class="col-md-12 text-danger">数据加载失败，请重试</div>');
					state.hasMore = false;
				}
			}, 'json').fail(function(xhr, status) {
				$("#" + state.area + " .app-list").empty().append(`<div class="col-md-12 text-danger">网络错误（${status}），请检查网络</div>`);
				state.hasMore = false;
			}).always(function() {
				// 隐藏局部 loadingTip
				toggleLoading(false);
			});
		}

		$(document).on('click', '.apply-store-author-link', function(e) {
			e.preventDefault();
			var $a = $(this);
			filterByAuthor($a.data('author-id'), $a.data('author-name') || $.trim($a.text()));
		});

		$(document).on('click', '.apply-author-filter-clear', function(e) {
			e.preventDefault();
			state.author_id = 0;
			state.author_name = '';
			state.pages[state.area] = 1;
			state.hasMore = true;
			fetchApps(false);
		});

		// 标签点击切换列表
		$(document).on("click", ".py-1 a[data-type]", function() {
			const $this = $(this);
			state.type = $this.data("type");
			state.tag = $this.data("tag") || "";
			state.sort = $this.data("sort") || "";
			state.pages[state.area] = 1;
			state.hasMore = true;
			updateActiveTag($this);
			if (state.area === 'theme' || state.area === 'plugin' || state.area === 'all') {
				fetchApps(false);
			}
		});
		// 排序点击
		$(document).on("click", ".dropdown-item[data-sort]", function() {
			const $this = $(this);
			if ($this.data("type")) {
				state.type = $this.data("type");
			}
			state.sort = $this.data("sort");
			state.pages[state.area] = 1;
			state.hasMore = true;
			$this.closest('.tab-pane').find('.dropdown-toggle').first().text($this.text());
			$this.closest('.dropdown-menu').removeClass('show');
			$this.closest('.dropdown').removeClass('show');
			fetchApps(false);
		});
		// 移除加载更多按钮相关事件
		// 滚动自动加载
		$(window).scroll(function() {
			if (!state.hasMore || state.loading) return;
			if (state.area === 'app5') return;
			if ($(window).scrollTop() + $(window).height() >= $(document).height() - 100) {
				fetchApps(true);
			}
		});

		// 首屏检测已安装应用更新
		checkApplyStoreUpdates(document);
	});
</script>