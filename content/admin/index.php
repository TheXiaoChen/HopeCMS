<?php defined('HOPE_ROOT') || exit('access denied!'); ?>
<?php doAction('adm_main_top'); ?>
<div class="row">
        <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
          <div class="card">
            <div class="card-body p-3">
              <div class="row">
                <div class="col-8">
                  <div class="numbers">
                    <p class="text-sm mb-0 text-uppercase font-weight-bold">文章</p>
                    <h5 class="font-weight-bolder"><?= $sta_cache['lognum'] ?>+</h5>
                  </div>
                </div>
                <div class="col-4 text-end">
                  <div class="icon icon-shape bg-gradient-primary shadow-primary text-center rounded-circle d-flex align-items-center justify-content-center">
                    <i class="fa fa-file-lines text-lg opacity-10" aria-hidden="true"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
          <div class="card">
            <div class="card-body p-3">
              <div class="row">
                <div class="col-8">
                  <div class="numbers">
                    <p class="text-sm mb-0 text-uppercase font-weight-bold">评论</p>
                    <h5 class="font-weight-bolder"><?= $sta_cache['comnum_all'] ?>+</h5>
                  </div>
                </div>
                <div class="col-4 text-end">
                  <div class="icon icon-shape bg-gradient-danger shadow-danger text-center rounded-circle d-flex align-items-center justify-content-center">
                    <i class="fa fa-comments text-lg opacity-10" aria-hidden="true"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
          <div class="card">
            <div class="card-body p-3">
              <div class="row">
                <div class="col-8">
                  <div class="numbers">
                    <p class="text-sm mb-0 text-uppercase font-weight-bold">附件</p>
                    <h5 class="font-weight-bolder"><?= (int)($sta_cache['uploadnum'] ?? 0) ?>+</h5>
                  </div>
                </div>
                <div class="col-4 text-end">
                  <div class="icon icon-shape bg-gradient-success shadow-success text-center rounded-circle d-flex align-items-center justify-content-center">
                    <i class="fa fa-folder text-lg opacity-10" aria-hidden="true"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-xl-3 col-sm-6">
          <div class="card">
            <div class="card-body p-3">
              <div class="row">
                <div class="col-8">
                  <div class="numbers">
                    <p class="text-sm mb-0 text-uppercase font-weight-bold">用户</p>
                    <h5 class="font-weight-bolder"><?= $sta_cache['usernum'] ?>+</h5>
                  </div>
                </div>
                <div class="col-4 text-end">
                  <div class="icon icon-shape bg-gradient-warning shadow-warning text-center rounded-circle d-flex align-items-center justify-content-center">
                    <i class="fa fa-users text-lg opacity-10" aria-hidden="true"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="row mt-4">
        <div class="col-lg-7 mb-lg-0 mb-4">
			<div class="card">
				<div class="card-header pb-0 pt-3 bg-transparent">
				  <h5 class="text-capitalize">网站信息</h5>
				</div>
				<div class="table-responsive">
				  <table class="table align-items-center mb-0">
					<tbody>
					  <tr>
						<td><div class="d-flex px-2"><h6 class="mb-0 text-sm">当前用户</h6></div></td>
						<td><p class="text-sm font-weight-bold mb-0"><?= $user_cache[UID]['name'] ?></p></td>
						<td><h6 class="mb-0 text-sm">当前版本</h6></td>
						<td>
						  <div class="d-flex align-items-center flex-wrap gap-2">
							<p class="text-sm font-weight-bold mb-0" id="hope-current-version"><?= Option::HOPE_VERSION ?></p>
							<?php if (User::isAdmin()): ?>
							<button type="button" class="btn btn-xs btn-outline-primary mb-0" id="hope-check-update-btn">检查更新</button>
							<button type="button" class="btn btn-xs btn-success mb-0 d-none" id="hope-do-update-btn">立即更新</button>
							<?php endif; ?>
						  </div>
						  <div class="text-xs mt-1 d-flex align-items-center flex-wrap gap-2" style="min-height:1.1rem;">
							<span class="text-muted" id="hope-update-status"></span>
							<a href="javascript:;" class="d-none text-primary text-decoration-none" id="hope-view-changelog">查看说明</a>
						  </div>
						</td>
					  </tr>
					  <tr>
						<td><div class="d-flex px-2"><h6 class="mb-0 text-sm">当前主题</h6></div></td>
						<td><p class="text-sm font-weight-bold mb-0"><?= Option::get('nonce_theme') ?></p></td>
						<td><h6 class="mb-0 text-sm">使用插件</h6></td>
						<td><p class="text-sm font-weight-bold mb-0"><?= count(Option::get('active_plugins')) ?></p></td>
					  </tr>
					  <tr>
						<td><div class="d-flex px-2"><h6 class="mb-0 text-sm">PHP</h6></div></td>
						<td><p class="text-sm font-weight-bold mb-0"><?= $php_ver ?></p></td>
						<td><h6 class="mb-0 text-sm">数据库</h6></td>
						<td><p class="text-sm font-weight-bold mb-0">MySQL <?= $mysql_ver ?></p></td>
					  </tr>
					  <tr>
						<td><div class="d-flex px-2"><h6 class="mb-0 text-sm">web服务</h6></div></td>
						<td><p class="text-sm font-weight-bold mb-0"><?= $_SERVER['SERVER_SOFTWARE'] ?></p></td>
						<td><h6 class="mb-0 text-sm">占用大小</h6></td>
						<td><p class="text-sm font-weight-bold mb-0"><?= changeFileSize(getDirSize(HOPE_ROOT)) ?></p></td>
					  </tr>
					  <tr>
						<td><div class="d-flex px-2"><h6 class="mb-0 text-sm">操作系统</h6></div></td>
						<td><p class="text-sm font-weight-bold mb-0"><?= php_uname('s') . ' ' . php_uname('m') ?></p></td>
						<td><h6 class="mb-0 text-sm">当前时间</h6></td>
						<td><p class="text-sm font-weight-bold mb-0"><?= date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']);?></p></td>
					  </tr>
					</tbody>
				  </table>
				</div>
			</div>
        </div>
        <div class="col-lg-5">
          <div class="card card-carousel overflow-hidden h-100 p-0">
            <div id="carouselExampleCaptions" class="carousel slide h-100" data-bs-ride="carousel">
              <div class="carousel-inner border-radius-lg h-100">
                <?php
                $carousel_items = isset($apply_dashboard['carousel']) && is_array($apply_dashboard['carousel']) ? $apply_dashboard['carousel'] : [];
                if (empty($carousel_items)) {
                    $carousel_items = [[
                        'image' => SITE_URL . 'content/admin/img/carousel-1.jpg',
                        'title' => 'Hope CMS 应用中心',
                        'link'  => SELF . '?act=apply',
                        'desc'  => '浏览主题与插件，一键安装扩展',
                        'icon'  => 'fa fa-store',
                    ]];
                }
                foreach ($carousel_items as $ci => $slide):
                    $img = $slide['image'] ?? '';
                    if ($img !== '' && strpos($img, 'http') !== 0) {
                        $img = SITE_URL . ltrim($img, '/');
                    }
                    if ($img === '') {
                        $img = SITE_URL . 'content/admin/img/carousel-1.jpg';
                    }
                    $icon = $slide['icon'] ?? 'fa fa-star';
                ?>
                <div class="carousel-item h-100<?= $ci === 0 ? ' active' : '' ?>" style="background-image: url('<?= htmlspecialchars($img, ENT_QUOTES) ?>');background-size: cover;">
                  <div class="carousel-caption d-none d-md-block bottom-0 text-start start-0 ms-5">
                    <div class="icon icon-shape icon-sm bg-white text-center border-radius-md mb-3 d-flex align-items-center justify-content-center">
                      <i class="<?= htmlspecialchars($icon) ?> text-dark opacity-10"></i>
                    </div>
                    <?php if (!empty($slide['title'])): ?>
                    <h5 class="text-white mb-1"><?= htmlspecialchars($slide['title']) ?></h5>
                    <?php endif; ?>
                    <?php if (!empty($slide['desc'])): ?>
                    <p><?= htmlspecialchars($slide['desc']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($slide['link'])): ?>
                    <a href="<?= htmlspecialchars($slide['link']) ?>" class="btn btn-sm btn-white text-dark">查看详情</a>
                    <?php endif; ?>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
              <?php if (count($carousel_items) > 1): ?>
              <button class="carousel-control-prev w-5 me-3" type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">上一张</span>
              </button>
              <button class="carousel-control-next w-5 me-3" type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">下一张</span>
              </button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <div class="row mt-4">
        <div class="col-lg-7 mb-lg-0 mb-4">
			<div class="card">
			  <div class="card-header">
				<h5 class="mb-0 text-capitalize">最近登录</h5> </div>
			  <div class="card-body pt-0">
				<ul class="list-group list-group-flush">
				<?php foreach ($users as $user):
					$avatar = hope_avatar_url($user_cache[$user['uid']]['avatar'] ?? '', './content/admin/img/avatar.png');?>
				  <li class="list-group-item px-0">
					<div class="row align-items-center">
					  <div class="col-auto d-flex align-items-center">
						<a href="javascript:;" class="avatar"> <img class="border-radius-lg" alt="Image placeholder" src="<?= $avatar ?>"> </a>
					  </div>
					  <div class="col ms-1">
						<h6 class="mb-0"><a href="javascript:;"><?= empty($user['name']) ? $user['login'] : htmlspecialchars($user['name']) ?></a> <span class="badge badge-success badge-sm"><?= htmlspecialchars($user['role']) ?></span></h6>
						<span><?= $user['update_time'] ?></span>
					  </div>
					  <div class="col-auto">
						<button class="btn btn-link btn-icon-only btn-rounded btn-sm text-dark icon-move-right my-auto"><i class="fa fa-chevron-right" aria-hidden="true"></i></button>
					  </div>
					</div>
				  </li>

				<?php endforeach; ?>

				</ul>
			  </div>
			</div>
        </div>
        <div class="col-lg-5">
			<div class="card h-100">
			  <div class="card-header pb-0">
				<h6>最新动态</h6>
			  </div>
			  <div class="card-body p-3">
				<div class="timeline timeline-one-side overflow-auto" style="height: 222px;">
                <?php
                $news_items = isset($apply_dashboard['news']) && is_array($apply_dashboard['news']) ? $apply_dashboard['news'] : [];
                if (empty($news_items)) {
                    $news_items = [[
                        'type'  => 'notice',
                        'title' => '欢迎使用',
                        'text'  => '可在应用中心浏览扩展与最新动态',
                        'time'  => date('Y-m-d'),
                    ]];
                }
                $news_icon = ['notice' => 'fa-bell', 'update' => 'fa-cloud-arrow-up', 'alert' => 'fa-triangle-exclamation'];
                foreach ($news_items as $item):
                    $type = $item['type'] ?? 'notice';
                    $icon = $news_icon[$type] ?? 'fa-bell';
                ?>
				  <div class="timeline-block mb-3">
				    <span class="timeline-step"><i class="fa <?= htmlspecialchars($icon) ?> text-success"></i></span>
					<div class="timeline-content">
					  <h6 class="text-dark text-sm font-weight-bold mb-0"><?= htmlspecialchars($item['title'] ?? '') ?></h6>
					  <p class="font-weight-bold text-xs mt-1 mb-0 text-success opacity-8"><?= htmlspecialchars($item['text'] ?? '') ?></p>
                      <?php if (!empty($item['time'])): ?>
                      <small class="text-muted"><?= htmlspecialchars($item['time']) ?></small>
                      <?php endif; ?>
					</div>
				  </div>
                <?php endforeach; ?>
				</div>
			  </div>
			</div>
        </div>
      </div>
	  <style>
		.hope-update-swal-note {
			max-height: 280px;
			overflow: auto;
			margin-top: 12px;
			padding: 10px 12px;
			border: 1px solid #e9ecef;
			border-radius: 0.5rem;
			background: #f8f9fa;
			text-align: left;
			font-size: 0.8125rem;
			line-height: 1.55;
			white-space: pre-wrap;
			word-break: break-word;
			color: #344767;
		}
	  </style>
	  <script>
		$("#menu_home").addClass('active');
		<?php if (User::isAdmin()): ?>
		(function () {
			var $checkBtn = $('#hope-check-update-btn');
			var $updateBtn = $('#hope-do-update-btn');
			var $status = $('#hope-update-status');
			var $viewNote = $('#hope-view-changelog');
			var pending = null;

			function escapeHtml(str) {
				return String(str)
					.replace(/&/g, '&amp;')
					.replace(/</g, '&lt;')
					.replace(/>/g, '&gt;')
					.replace(/"/g, '&quot;')
					.replace(/'/g, '&#39;');
			}

			function setStatus(text, cls) {
				$status.removeClass('text-success text-danger text-warning text-muted').addClass(cls || 'text-muted').text(text || '');
			}

			function setViewNoteVisible(show) {
				$viewNote.toggleClass('d-none', !show);
			}

			function showChangelog(version, note) {
				Swal.fire({
					title: version ? ('v' + version + ' 更新说明') : '更新说明',
					html: '<div class="hope-update-swal-note" style="margin-top:0;">' + escapeHtml(note || '暂无说明') + '</div>',
					width: 560,
					confirmButtonText: '关闭'
				});
			}

			function resetUpdateBtn() {
				$updateBtn.addClass('d-none').prop('disabled', false).text('立即更新');
				pending = null;
				setViewNoteVisible(false);
			}

			$viewNote.on('click', function (e) {
				e.preventDefault();
				if (!pending || !pending.changelog) {
					return;
				}
				showChangelog(pending.version, pending.changelog);
			});

			$checkBtn.on('click', function () {
				$checkBtn.prop('disabled', true).text('检查中...');
				resetUpdateBtn();
				setStatus('正在检查版本…', 'text-muted');
				$.ajax({
					url: '<?= SELF ?>?act=upgrade&action=check_update',
					type: 'GET',
					dataType: 'json',
					timeout: 30000
				}).done(function (res) {
					if (!res || !hopeApiSuccess(res.code)) {
						setStatus((res && res.msg) ? res.msg : '检查失败', 'text-danger');
						return;
					}
					var data = res.data || {};
					if (data.has_update && data.source) {
						pending = {
							source: data.source,
							upsql: data.upsql || '',
							version: data.version || '',
							changelog: data.changelog || ''
						};
						setStatus('发现新版本 ' + pending.version + (data.from_cache ? '（本地缓存）' : ''), 'text-warning');
						setViewNoteVisible(!!pending.changelog);
						$updateBtn.removeClass('d-none');
					} else {
						setStatus('已是最新版本（' + (data.current || '<?= Option::HOPE_VERSION ?>') + '）', 'text-success');
					}
				}).fail(function () {
					setStatus('检查失败，请稍后重试', 'text-danger');
				}).always(function () {
					$checkBtn.prop('disabled', false).text('检查更新');
				});
			});

			$updateBtn.on('click', function () {
				if (!pending || !pending.source) {
					return;
				}
				var html = '<p class="mb-0">更新过程请勿关闭页面，建议先备份数据。</p>';
				if (pending.changelog) {
					html += '<div class="hope-update-swal-note">' + escapeHtml(pending.changelog) + '</div>';
				}
				Swal.fire({
					title: '确认更新到 ' + pending.version + '？',
					html: html,
					icon: 'warning',
					showCancelButton: true,
					confirmButtonText: '开始更新',
					cancelButtonText: '取消'
				}).then(function (result) {
					if (!result.isConfirmed) {
						return;
					}
					$updateBtn.prop('disabled', true).text('更新中...');
					$checkBtn.prop('disabled', true);
					setStatus('正在下载并安装更新包…', 'text-muted');
					setViewNoteVisible(false);
					$.ajax({
						url: hopeWithToken('<?= SELF ?>?act=upgrade&action=update'),
						type: 'POST',
						data: {
							source: pending.source,
							upsql: pending.upsql || '',
							version: pending.version || '',
							token: hopeAdminToken()
						},
						timeout: 300000
					}).done(function (res) {
						var text = (typeof res === 'string') ? res.trim() : '';
						if (text === 'succ') {
							setStatus('更新成功，即将刷新…', 'text-success');
							Toast.fire({ icon: 'success', title: '系统更新成功' });
							setTimeout(function () { location.reload(); }, 1200);
							return;
						}
						var map = {
							error_down: '下载更新包失败',
							error_dir: '解压失败，请检查目录权限',
							error_zip: '服务器未启用 ZipArchive',
							error_auth: '权限不足',
							error: '更新参数错误'
						};
						setStatus(map[text] || ('更新失败：' + (text || '未知错误')), 'text-danger');
						$updateBtn.prop('disabled', false).text('立即更新');
						$checkBtn.prop('disabled', false);
						setViewNoteVisible(!!(pending && pending.changelog));
					}).fail(function () {
						setStatus('更新请求失败，请稍后重试', 'text-danger');
						$updateBtn.prop('disabled', false).text('立即更新');
						$checkBtn.prop('disabled', false);
						setViewNoteVisible(!!(pending && pending.changelog));
					});
				});
			});
		})();
		<?php endif; ?>
	  </script>
<?php doAction('adm_main_bottom'); ?>