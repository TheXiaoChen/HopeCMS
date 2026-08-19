<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<?php if ($isAutoGo) {echo "<meta http-equiv=\"refresh\" content=\"2;url=$url\" />";} ?>
	<link rel="icon" type="image/png" href="<?= SITE_URL ?>content/admin/img/favicon.ico">
	<title>404 Not Found</title>
	<link href="<?= SITE_URL ?>content/admin/css/fontawesome7.css?v=<?= Option::HOPE_VERSION_TIMESTAMP ?>" rel="stylesheet" />
	<link id="pagestyle" href="<?= SITE_URL ?>content/admin/css/argon-dashboard.min.css?v=<?= Option::QCLOG_VERSION ?>" rel="stylesheet" />
</head>
<body class="error-page">
	<main class="main-content  mt-0">
		<div class="page-header min-vh-100" style="background-image: url('./img/404.svg');">
			<div class="container">
				<div class="row justify-content-center">
					<div class="col-lg-6 col-md-7 mx-auto text-center"><?php if($msg == '404'){ ?>
						<h1 class="display-1 text-bolder text-primary">404 Not Found</h1>
						<h2>抱歉，你所请求的页面不存在！</h2>
						<?php }else{ ?>
						<h1 class="display-1 text-bolder text-primary">Access error</h1>
						<p class="lead"><?=$msg?></p>
						<?php } ?><?php if ($url != 'none') { echo '<button type="button" class="btn bg-gradient-dark mt-4" href="'.$url.'">点击返回</button>'; } ?>
					</div>
				</div>
			</div>
		</div>
	</main>
	<script src="<?= SITE_URL ?>content/admin/js/core/popper.min.js"></script>
	<script src="<?= SITE_URL ?>content/admin/js/core/bootstrap.min.js"></script>
	<script src="<?= SITE_URL ?>content/admin/js/plugins/perfect-scrollbar.min.js"></script>
	<script src="<?= SITE_URL ?>content/admin/js/plugins/smooth-scrollbar.min.js"></script>
	<script>
		var win = navigator.platform.indexOf('Win') > -1;
		if (win && document.querySelector('#sidenav-scrollbar')) {
			var options = {
				damping: '0.5'
			}
			Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
		}
	</script>
</body>
</html>