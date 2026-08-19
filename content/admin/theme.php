<?php defined('HOPE_ROOT') || exit('access denied!'); ?>
<div class="row">
	<div class="col-12">
		<div class="card">
			<div class="card-body pb-3">
				<div class="d-lg-flex">
					<div>
						<h5 class="mb-0">主题</h5>
					</div>
					<div class="ms-auto my-auto">
						<button type="button" class="btn btn-xs btn-outline-primary" data-bs-toggle="modal" data-bs-target="#themeModal">安装主题</button>
						<button type="button" class="btn btn-xs btn-dribbble" title="应用中心" onclick="window.location.href='<?= SELF ?>?act=apply'">应用中心</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<section class="py-3">
	<div class="row mt-lg-4 mt-2">
    <?php foreach ($themes as $key => $value): ?>
		<div class="col-lg-4 col-md-6 mb-4">
			<div class="card hover-shadow <?php if ($nonce_theme == $value['tplfile']) echo ' current-usage'; ?>" data-app-alias="<?= $value['tplfile'] ?>" data-app-version="<?= $value['version'] ?>">
			<?php if ($nonce_theme == $value['tplfile']): ?>
				<div class="current-badge">当前使用</div>
			<?php endif; ?>
				<div class="update-badge">更新</div>
				<div class="card-body p-3">
					<img src="<?= $value['preview'] ?>" style="border-radius: var(--bs-card-border-radius);" class="w-100">
					<p class="text-sm mt-3"><?= $value['tpldes'] ?><?php if (strpos($value['tplurl'], 'http://www.hopecms.cn') === 0): ?><a href="<?= $value['tplurl'] ?>" target="_blank">更多信息&rarr;</a><?php endif ?></p>
					<hr class="horizontal dark">
					<div class="row">
						<div class="col-6">
							<h6 class="text-sm mb-0 <?= $nonce_theme == $value['tplfile'] ? "text-success" : '' ?>" title="<?= $value['tplfile'] ?>" >
								<?php if ($nonce_theme == $value['tplfile']): ?>
									<span class="current-theme-indicator"></span>
								<?php endif; ?>
								<?= $value['tplname'] ?> <?php if ($value['version']): ?>V<?= $value['version'] ?><?php endif ?>
							</h6>
							<p class="text-secondary text-sm mb-0">开发者：<?php if ($value['author'] && strpos($value['author_url'], 'http://www.hopecms.cn') === 0): ?><a href="<?= $value['author_url'] ?>" title="查看作品" target="_blank"><?= $value['author'] ?></a><?php elseif ($value['author']): ?><?= $value['author'] ?><?php endif ?></p>
						</div>
						<div class="col-6 text-end">
							<div class="dropdown">
								<p class="text-secondary text-sm font-weight-bold mb-0" id="navbarDropdownMenuLink"
									data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-gear"></i> 设置</p>
								<div class="dropdown-menu dropdown-menu-end me-sm-n4 me-n3"
									aria-labelledby="navbarDropdownMenuLink" data-popper-placement="bottom-end"
									style="position: absolute; inset: 0px 0px auto auto; margin: 0px; transform: translate3d(0px, 42.7636px, 0px);">
									<?php if ($nonce_theme !== $value['tplfile']): ?><a class="dropdown-item" href="<?= SELF ?>?act=theme&use&tpl=<?= $value['tplfile'] ?>&token=<?= LoginAuth::genToken() ?>">启用</a><?php endif; ?>
									<?php if(is_tpl_options_exists($value['tplfile'])): ?><a class="dropdown-item" href="<?= SELF ?>?act=theme_set&tpl=<?= $value['tplfile'] ?>">编辑</a><?php endif; ?>
									<a class="dropdown-item" href="javascript: hp_delete('<?= $value['tplfile'] ?>', 'tpl', '<?= LoginAuth::genToken() ?>');">删除</a>
								</div>
							</div>
							<h6 class="text-sm mb-0"><i class="fa fa-palette text-warning text-sm opacity-10"></i> <?= $value['tpltype'] ?></h6>
						</div>
					</div>
				</div>
			</div>
		</div>
    <?php endforeach ?>
	</div>
</section>
<div class="modal fade" id="themeModal" tabindex="-1" role="dialog" aria-labelledby="Modal-form" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<form action="<?= SELF ?>?act=theme&upzip" method="post" enctype="multipart/form-data">
				<div class="modal-header">
					<h5 class="modal-title" id="Modal-form">安装主题</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<div>
						<p>上传一个zip压缩格式的主题安装包</p>
						<p>
							<input name="tplzip" type="file" accept=".zip,application/zip" required/>
						</p>
					</div>
				</div>
				<div class="modal-footer">
					<input type="hidden" name="linkid" id="linkid" />
					<button type="button" class="btn bg-gradient-secondary" data-bs-dismiss="modal"
						title="取消">取消</button>
					<button type="submit" class="btn bg-gradient-primary" title="确定">确定</button>
				</div>
			</form>
		</div>
	</div>
</div>
<script>
	$("#menu_surface_manage").addClass('active');
	$("#menu_surface").addClass('show');
	$("#menu_theme").addClass('active');
    $(function() {
        var themeList = [];
        $('section .card').each(function() {
            var $card = $(this);
            var alias = $card.data('app-alias');
            var version = $card.data('app-version');
            themeList.push({
                name: alias,
                version: version
            });
        });
        $.ajax({
            url: '<?= SELF ?>?act=theme&check_update',
            type: 'POST',
            data: {
                theme: themeList
            },
            success: function(response) {
                if (hopeApiSuccess(response.code)) {
                    var pluginsToUpdate = response.data;
					$.each(pluginsToUpdate, function(index, item) {
						var $card = $('section .card[data-app-alias="' + item.name + '"]');
						var $updateBadge = $card.find('.update-badge');
						$updateBadge.addClass('update-available')
									.html('<a href="javascript:void(0);" class="update-link">更新</a>')
									.show()
									.find('a').on('click', function() {
										updateTheme(item.name, this);
									});
					});
                } else {
                    console.log('更新接口返回错误');
                }
            },
            error: function() {
                console.log('请求更新接口失败');
            }
        });
    });

	function updateTheme(alias, $updateLink) {
		var $link = $($updateLink);
		$link.text('正在更新...').prop('disabled', true);
		
		$.ajax({
			url: '<?= SELF ?>?act=theme&upgrade',
			type: 'GET',
			data: {
				alias: alias,
				token: '<?= LoginAuth::genToken() ?>'
			},
			success: function(response) {
				if (hopeApiSuccess(response.code)) {
					location.href = '<?= SELF ?>?act=theme&code=upgrade';
				} else {
					$link.text('更新').prop('disabled', false);
					Toast.fire({ icon: 'error', title: response.msg || '更新失败' });
				}
			},
			error: function(xhr) {
				$link.text('更新').prop('disabled', false);
				Toast.fire({ icon: 'error', title: '更新请求失败，请稍后重试' });
			}
		});
	}

	<?php if (Input::getStrVar('code') === 'activated'): ?>Toast.fire({ icon: 'success', title: '主题更换成功' });<?php endif ?>
<?php if (Input::getStrVar('code') === 'install'): ?>Toast.fire({ icon: 'success', title: '主题安装成功' });<?php endif ?>
<?php if (Input::getStrVar('code') === 'upgrade'): ?>Toast.fire({ icon: 'success', title: '主题更新成功' });<?php endif ?>
<?php if (Input::getStrVar('code') === 'del'): ?>Toast.fire({ icon: 'success', title: '删除主题成功' });<?php endif ?>
<?php if (Input::getStrVar('code') === 'error_f'): ?>Toast.fire({ icon: 'error', title: '删除失败，请检查主题文件权限' });<?php endif ?>
<?php if (Input::getStrVar('code') === 'error_a'): ?>Toast.fire({ icon: 'error', title: '只支持zip压缩格式的主题包' });<?php endif ?>
<?php if (Input::getStrVar('code') === 'error_b'): ?>Toast.fire({ icon: 'error', title: '上传失败，主题目录(content/theme)不可写' });<?php endif ?>
<?php if (Input::getStrVar('code') === 'error_d'): ?>Toast.fire({ icon: 'error', title: '请选择一个zip格式的主题安装包' });<?php endif ?>
<?php if (Input::getStrVar('code') === 'error_e'): ?>Toast.fire({ icon: 'error', title: '安装失败，主题安装包不符合标准' });<?php endif ?>
<?php if (Input::getStrVar('code') === 'error_c'): ?>Toast.fire({ icon: 'error', title: '服务器PHP不支持zip模块' });<?php endif ?>
<?php if (Input::getStrVar('code') === 'error_h'): ?>Toast.fire({ icon: 'error', title: '更新失败，无法下载更新包，可能是服务器网络问题。' });<?php endif ?>
<?php if (!$nonce_theme_data): ?>Toast.fire({ icon: 'error', title: '当前使用的主题(<?= $nonce_theme ?>)已被删除或损坏，请选择其他主题。' });<?php endif ?>

</script>