<?php defined('HOPE_ROOT') || exit('access denied!'); ?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<link rel="apple-touch-icon" sizes="76x76" href="<?= SITE_URL ?>content/admin/img/favicon.ico">
	<link rel="icon" type="image/png" href="<?= SITE_URL ?>content/admin/img/favicon.ico">
	<title>登录 - <?= Option::get('sitename') ?></title>
	<link href="<?= SITE_URL ?>content/admin/css/fontawesome7.css?v=<?= Option::HOPE_VERSION_TIMESTAMP ?>" rel="stylesheet" />
	<link href="<?= SITE_URL ?>content/admin/css/argon-dashboard.min.css?v=<?= Option::HOPE_VERSION_TIMESTAMP ?>" rel="stylesheet" />
    <script src="<?= SITE_URL ?>content/admin/js/jquery-3.7.1.min.js?v=<?= Option::HOPE_VERSION_TIMESTAMP ?>"></script>
	<script src="<?= SITE_URL ?>content/admin/js/plugins/sweetalert.min.js?v=<?= Option::HOPE_VERSION_TIMESTAMP ?>"></script>
	<script src="<?= SITE_URL ?>content/admin/js/common.js?v=<?= Option::HOPE_VERSION_TIMESTAMP ?>"></script>
    <?php doAction('login_head') ?>
</head>

<body>
	<main class="main-content mt-0">
		<section>
			<div class="page-header min-vh-100">
				<div class="container">
					<div class="row">
						<div class="col-xl-4 col-lg-5 col-md-7 d-flex flex-column mx-lg-0 mx-auto">
							<div class="card card-plain">
								<div class="card-header pb-0 text-start">
									<h4 class="font-weight-bolder">登 录</h4>
									<p class="mb-0">输入您的电子邮箱或用户名和密码登录</p>
								</div>
								<div class="card-body">
									<form method="post" class="user" action="<?= SELF ?>?act=login">
										<input type="hidden" name="do" value="1" />
										<div class="mb-3 input-group">
											<span class="input-group-text"><i class="fa fa-user" aria-hidden="true"></i></span>
											<input type="text" class="form-control form-control-lg" name="user" placeholder="电子邮箱\用户名" value="<?= htmlspecialchars($rememberUser ?? '', ENT_QUOTES, 'UTF-8') ?>" autocomplete="username">
										</div>
										<div class="mb-3 input-group">
											<input type="password" class="form-control form-control-lg" name="password" placeholder="密码" autocomplete="current-password">
										</div>
                                        <?php if ($login_code): ?>
											<div class="mb-3 input-group">
                                                <input type="text" name="login_code" class="form-control form-control-user" style="width:180px;" id="login_code" placeholder="验证码"
                                                    required>
                                                <img src="<?= SITE_URL ?>system/checkcode.php" id="checkcode" class="mx-2">
                                            </div>
                                        <?php endif ?>
										<div class="form-check form-switch ps-0">
											<input class="form-check-input ms-0" type="checkbox" name="rememberMe" id="rememberMe" value="1" <?= !empty($rememberUser) ? 'checked' : '' ?>>
											<label class="form-check-label ms-2" for="rememberMe">记住我</label>
										</div>
										<div class="text-center">
											<button type="submit"
												class="btn btn-lg btn-primary btn-lg w-100 mt-4 mb-0">登 录</button>
										</div>
									</form>
								</div>
								<div>
									<?php doAction('login_ext') ?>
								</div>
								<div class="card-footer text-center pt-0 px-lg-2 px-1">
									<p class="text-sm mx-auto">
										<?php if ($is_signup): ?>
											还没有帐户？<a href="<?= SELF ?>?act=reg"
												class="text-primary text-gradient font-weight-bold">前往注册</a>&emsp;
										<?php endif ?>
										忘记密码了？<a href="javascript:Toast.fire({icon: 'error',title: '不支持管理员找回密码!'});"
											class="text-primary text-gradient font-weight-bold">前往找回</a>
									</p>
									<p class="text-sm mx-auto"><a href="<?= SITE_URL ?>"
											class="text-primary text-gradient font-weight-bold">返回首页</a></p>
								</div>

							</div>
						</div>
						<div
							class="col-6 d-lg-flex d-none h-100 my-auto pe-0 position-absolute top-0 end-0 text-center justify-content-center flex-column">
							<div class="position-relative bg-gradient-primary h-100 m-3 px-7 border-radius-lg d-flex flex-column justify-content-center overflow-hidden"
								style="background-image: url('http://api.qemao.com/api/acgn/?type=ad'); background-size: cover;">
								<span class="mask bg-gradient-primary opacity-6"></span>
								<h4 class="mt-5 text-white font-weight-bolder position-relative">"注意力是新程序"</h4>
								<p class="text-white position-relative">写作看起来越轻松，作者在这个过程中投入的精力就越多.</p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>
	</main>
	<script>
	$(function () {
		<?php if (Input::getStrVar('code') === 'reg'): ?>Toast.fire({ icon: 'success', title: '注册成功，请登录' });
		<?php endif ?><?php if (Input::getStrVar('code') === 'reset'): ?>Toast.fire({ icon: 'success', title: '密码重置成功，请登录' });
		<?php endif ?><?php if (Input::getStrVar('code') === 'ckcode'): ?>Toast.fire({ icon: 'error', title: '验证错误，请重新输入' });
		<?php endif ?><?php if (Input::getStrVar('code') === 'login'): ?>Toast.fire({ icon: 'error', title: '用户或密码错误，请重新输入' });<?php endif ?>
        $('#checkcode').click(function () {
            var timestamp = new Date().getTime();
            $(this).attr("src", "<?= SITE_URL ?>system/checkcode.php?" + timestamp);
        });
    });
	</script>

</body>

</html>