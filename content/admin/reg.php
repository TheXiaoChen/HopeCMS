<?php defined('HOPE_ROOT') || exit('access denied!'); ?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<link rel="apple-touch-icon" sizes="76x76" href="<?= SITE_URL ?>content/admin/img/favicon.ico">
	<link rel="icon" type="image/png" href="<?= SITE_URL ?>content/admin/img/favicon.ico">
	<title>注册 - <?= Option::get('sitename') ?></title>
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
									<h4 class="font-weight-bolder">注 册</h4>
									<p class="mb-0">输入您的电子邮箱和密码进行注册</p>
								</div>
								<div class="card-body">
									<form method="post" class="user" action="<?= SELF ?>?act=signup">
										<input type="hidden" name="do" value="1" />
										<div class="mb-4 input-group">
											<span class="input-group-text"><i class="fa fa-user" aria-hidden="true"></i></span>
											<input type="email" class="form-control form-control-lg" name="mail" placeholder="电子邮箱">
										</div>
										<div class="mb-3">
											<input type="password" class="form-control form-control-lg" name="passwd" placeholder="密码">
										</div>
										<div class="mb-3">
											<input type="password" class="form-control form-control-lg" name="repasswd" placeholder="再次输入密码">
										</div>
                                        <?php if ($email_code): ?>
                                            <div class="form-group form-inline">
                                                <input type="text" name="mail_code" class="form-control form-control-user" style="width: 180px;" id="mail_code" placeholder="邮件验证码"
                                                    required>
                                                <button class="btn btn-success btn-user mx-2" type="button" id="send-btn">发送邮件验证码</button>
                                            </div>
                                        <?php endif ?>
                                        <?php if ($login_code): ?>
											<div class="mb-3 input-group">
                                                <input type="text" name="login_code" class="form-control form-control-user" style="width:180px;" id="login_code" placeholder="验证码"
                                                    required>
                                                <img src="<?= SITE_URL ?>system/checkcode.php" id="checkcode" class="mx-2">
                                            </div>
                                        <?php endif ?>
										<div class="text-center">
											<button type="submit" class="btn btn-lg btn-primary btn-lg w-100 mt-4 mb-0">注 册</button>
										</div>
									</form>
								</div>
                                <div><?php doAction('signup_ext') ?></div>
								<div class="card-footer text-center pt-0 px-lg-2 px-1">
									<p class="text-sm mx-auto">
										已经有账号了？<a href="<?= SELF ?>??act=login" class="text-primary text-gradient font-weight-bold">前往登录</a>&emsp;
									</p>
									<p class="text-sm mx-auto"><a href="<?= SITE_URL ?>" class="text-primary text-gradient font-weight-bold">返回首页</a></p>
								</div>
                                
							</div>
						</div>
						<div
							class="col-6 d-lg-flex d-none h-100 my-auto pe-0 position-absolute top-0 end-0 text-center justify-content-center flex-column">
							<div class="position-relative bg-gradient-primary h-100 m-3 px-7 border-radius-lg d-flex flex-column justify-content-center overflow-hidden"
								style="background-image: url('http://api.qemao.com/api/acgn/?type=pc');background-size: cover;">
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
			<?php if (Input::getStrVar('code') === 'ckcode'): ?>Toast.fire({ icon: 'error', title: '图形验证错误' });<?php endif ?>
			<?php if (Input::getStrVar('code') === 'mail_code'): ?>Toast.fire({ icon: 'error', title: '邮件验证码错误' });<?php endif ?>
			<?php if (Input::getStrVar('code') === 'login'): ?>Toast.fire({ icon: 'error', title: '错误的邮箱格式' });<?php endif ?>
			<?php if (Input::getStrVar('code') === 'exist'): ?>Toast.fire({ icon: 'error', title: '该邮箱已被注册' });<?php endif ?>
			<?php if (Input::getStrVar('code') === 'pwd_len'): ?>Toast.fire({ icon: 'error', title: '密码不小于6位' });<?php endif ?>
			<?php if (Input::getStrVar('code') === 'pwd2'): ?>Toast.fire({ icon: 'error', title: '两次输入的密码不一致' });<?php endif ?>
            setTimeout(hideActived, 6000);
			$('#checkcode').click(function () {
				var timestamp = new Date().getTime();
				$(this).attr("src", "<?= SITE_URL ?>system/checkcode.php?" + timestamp);
			});
            $('#send-btn').click(function () {
                const email = $('#mail').val();
                const sendBtn = $(this);
                const sendBtnResp = $('#send-btn-resp');
                sendBtnResp.html('')
                sendBtn.prop('disabled', true);
                $.ajax({
                    type: 'POST',
                    url: '<?= SELF ?>?act=send_email_code',
                    data: {
                        mail: email
                    },
                    success: function (response) {
                        // 发送邮件成功后，启动倒计时
                        let seconds = 60;
                        // 启动倒计时
                        const countdownInterval = setInterval(() => {
                            seconds--;
                            if (seconds <= 0) {
                                clearInterval(countdownInterval);
                                sendBtn.html('发送邮件验证码');
                                sendBtn.prop('disabled', false);
                            } else {
                                sendBtn.html('已发送,请查收邮件 ' + seconds + '秒');
                            }
                        }, 1000);
                    },
                    error: function (data) {
                        sendBtnResp.html(data.responseJSON.msg).addClass('text-danger').show()
                        sendBtn.prop('disabled', false);
                    }
                });
            });
        });
	</script>

</body>

</html>
