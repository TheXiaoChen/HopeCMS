<?php defined('HOPE_ROOT') || exit('access denied!'); ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body pb-3">
                <div class="d-lg-flex align-items-center">
                    <div class="flex-grow-1 mb-3 mb-lg-0">
                        <h5 class="mb-0">插件</h5>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <div class="input-group me-2" style="width: 200px;">
                            <span class="input-group-text"><i class="fa fa-search"></i></span>
                            <input type="text" class="form-control" id="pluginSearch" placeholder="搜索插件...">
                        </div>
						<button type="button" class="btn btn-xs btn-outline-primary" data-bs-toggle="modal" data-bs-target="#pluginModal">安装插件</button>
						<button type="button" class="btn btn-xs btn-dribbble" title="应用中心" onclick="window.location.href='<?= SELF ?>?act=apply'">应用中心</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<section class="py-3">
	<div class="row mt-lg-4 mt-2" id="pluginList">
    <?php  foreach ($plugins as $i => $val): ?>
		<div class="col-lg-4 col-md-6 mb-4">
			<div class="card" data-plugin-alias="<?= $val['Plugin'] ?>" data-plugin-version="<?= $val['Version'] ?>" >
				<div class="card-body p-3">
					<div class="d-flex">
						<div class="avatar avatar-xl">
							<img src="<?= $val['preview'] ?>" class="border-radius-lg">
						</div>
						<div class="ms-3 my-auto">
							<h6><?= $val['Name'] ?> V<?= $val['Version'] ?></h6>
							<p>
							<?php if ($val['Author'] != ''): ?>
								<?php if (strpos($val['AuthorUrl'], 'http://www.hopecms.cn') === 0): ?>
									<a href="<?= $val['AuthorUrl'] ?>" target="_blank"><?= $val['Author'] ?></a>
								<?php else: ?>
									<?= $val['Author'] ?>
								<?php endif ?>
							<?php endif ?></p>
						</div>
						<div class="ms-auto">
							<div class="form-switch mb-0">
								<input class="form-check-input" type="checkbox" id="<?= 'sw'.$i ?>" <?= empty($val['active']) ? '' : 'checked'?> onchange="toggleSwitch('<?= $val['alias'] ?>', '<?= 'sw'.$i ?>', '<?= LoginAuth::genToken() ?>')">
							</div>
						</div>
					</div>
					<p class="text-sm mt-3"><?= $val['Description'] ?> <?php if (strpos($val['Url'], 'http://www.hopecms.cn') === 0): ?><a href="<?= $val['Url'] ?>" target="_blank">更多信息&raquo;</a><?php endif ?></p>
					<hr class="horizontal dark">
					<div class="row">
						<div class="col-6">
							<?php if (TRUE === $val['Setting']): ?>
							<a href="<?= SELF ?>?act=plugin_set&plugin=<?= $val['Plugin'] ?>" class="btn btn-xs btn-primary">设置</a>
							<?php endif; ?>
						</div>
						<div class="col-6 text-end">
							<span class="update-btn"></span>
							<a href="javascript: hp_delete('<?= $val['Plugin'] ?>', 'plu', '<?= LoginAuth::genToken() ?>');" class="btn btn-xs btn-danger">删除</a>
						</div>
					</div>
				</div>
			</div>
		</div>
    <?php endforeach ?>
	</div>
</section>
<div class="modal fade" id="pluginModal" tabindex="-1" role="dialog" aria-labelledby="Modal-form" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<form action="<?= SELF ?>?act=plugin&upzip" method="post" enctype="multipart/form-data">
				<div class="modal-header">
					<h5 class="modal-title" id="Modal-form">安装插件</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<div>
						<p>上传一个zip压缩格式的插件安装包</p>
						<p>
							<input name="pluzip" type="file" accept=".zip,application/zip" required/>
						</p>
					</div>
				</div>
				<div class="modal-footer">
					<input type="hidden" name="token" id="token" value="<?= LoginAuth::genToken() ?>" />
					<button type="button" class="btn bg-gradient-secondary" data-bs-dismiss="modal" title="取消">取消</button>
					<button type="submit" class="btn bg-gradient-primary" title="确定">确定</button>
				</div>
			</form>
		</div>
	</div>
</div>
<script>
	$("#menu_plugin_category").addClass('active');
	$("#menu_plugin").addClass('show');
	$("#menu_plugins").addClass('active');
	function toggleSwitch(plugin, id, token) {
		var switchElement = document.getElementById(id);
		if (switchElement.checked) {
			window.location.href = '<?= SELF ?>?act=plugin&active&plugin=' + plugin + '&token=' + token;
		} else {
			window.location.href = '<?= SELF ?>?act=plugin&inactive&plugin=' + plugin + '&token=' + token;
		}
	}


    $(function() {
        var pluginList = [];
        $('#pluginList .card').each(function () {
            var $tr = $(this);
            var pluginAlias = $tr.data('plugin-alias');
            var currentVersion = $tr.data('plugin-version');
            pluginList.push({
                name: pluginAlias,
                version: currentVersion
            });
        });
        $.ajax({
            url: '<?= SELF ?>?act=plugin&check_update',
            type: 'POST',
            data: {
                plugins: pluginList
            },
            success: function(response) {
                if (hopeApiSuccess(response.code)) {
                    var pluginsToUpdate = response.data;
                    $.each(pluginsToUpdate, function(index, item) {
                        var $tr = $('#pluginList .card[data-plugin-alias="' + item.name + '"]');
                        var $updateBtn = $tr.find('.update-btn');
                        var $updateLink = $('<a>').addClass('btn btn-success btn-xs').text('更新').attr("href", "javascript:void(0);");
                        $updateLink.on('click', function() {
                            updatePlugin(item.name, $updateLink);
                        });
                        $updateBtn.append($updateLink);
                    });
                } else {
                    console.log('更新接口返回错误码:' + response.code);
                }
            },
            error: function(xhr) {
                var responseText = xhr.responseText;
                var responseObject = JSON.parse(responseText);
                var msgValue = responseObject.msg;
				console.log('插件更新检查异常： ' + msgValue);
            }
        });
		$('#pluginSearch').on('keyup', function() {
			var value = $(this).val().toLowerCase();
			$('#pluginList .col-lg-4').each(function() {
				var cardText = $(this).text().toLowerCase();
				$(this).toggle(cardText.indexOf(value) > -1);
			});
		});
		
    });

    function updatePlugin(pluginAlias, $updateLink) {
        $updateLink.text('正在更新...').prop('disabled', true);
        $.ajax({
            url: '<?= SELF ?>?act=plugin&upgrade',
            type: 'GET',
            data: {
                alias: pluginAlias,
                token: '<?= LoginAuth::genToken() ?>'
            },
            success: function(response) {
                if (hopeApiSuccess(response.code)) {
                    location.href = '<?= SELF ?>?act=plugin&code=upgrade';
                } else {
                    $updateLink.text('更新').prop('disabled', false);
					Toast.fire({ icon: 'error', title: response.msg || '更新失败' });
                }
            },
            error: function(xhr) {
                $updateLink.text('更新').prop('disabled', false);
				Toast.fire({ icon: 'error', title: '更新请求失败，请稍后重试' });
            }
        });
    }



	
<?php if (Input::getStrVar('code') === 'activated'): ?>Toast.fire({ icon: 'success', title: '插件更换成功' });<?php endif ?>
<?php if (Input::getStrVar('code') === 'install'): ?>Toast.fire({ icon: 'success', title: '插件安装成功' });<?php endif ?>
<?php if (Input::getStrVar('code') === 'upgrade'): ?>Toast.fire({ icon: 'success', title: '插件更新成功' });<?php endif ?>
<?php if (Input::getStrVar('code') === 'del'): ?>Toast.fire({ icon: 'success', title: '删除插件成功' });<?php endif ?>
<?php if (Input::getStrVar('code') === 'error'): ?>Toast.fire({ icon: 'error', title: '插件开启失败' });<?php endif ?>
<?php if (Input::getStrVar('code') === 'error_a'): ?>Toast.fire({ icon: 'error', title: '只支持zip压缩格式的插件包' });<?php endif ?>
<?php if (Input::getStrVar('code') === 'error_b'): ?>Toast.fire({ icon: 'error', title: '上传失败，插件目录(content/plugin)不可写' });<?php endif ?>
<?php if (Input::getStrVar('code') === 'error_d'): ?>Toast.fire({ icon: 'error', title: '请选择一个zip格式的插件安装包' });<?php endif ?>
<?php if (Input::getStrVar('code') === 'error_e'): ?>Toast.fire({ icon: 'error', title: '安装失败，插件安装包不符合标准' });<?php endif ?>
<?php if (Input::getStrVar('code') === 'error_f'): ?>Toast.fire({ icon: 'error', title: '上传安装包大小超出PHP限制' });<?php endif ?>
<?php if (Input::getStrVar('code') === 'error_c'): ?>Toast.fire({ icon: 'error', title: '服务器PHP不支持zip模块' });<?php endif ?>
<?php if (Input::getStrVar('code') === 'error_h'): ?>Toast.fire({ icon: 'error', title: '更新失败，无法下载更新包，可能是服务器网络问题。' });<?php endif ?>
<?php if (Input::getStrVar('code') === 'error_i'): ?>Toast.fire({ icon: 'error', title: 'Hope CMS 尚未完成授权注册' });<?php endif ?>
</script>