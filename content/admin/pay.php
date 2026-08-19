<?php defined('HOPE_ROOT') || exit('access denied!'); ?>
<style>
	#form_log .card-body { overflow: visible; }
	.pay-select-row {
		position: relative;
		z-index: 50;
		margin-bottom: 1.25rem;
	}
	.pay-select-row .form-select {
		position: relative;
		z-index: 51;
		cursor: pointer;
	}
</style>
<form action="<?= SELF ?>?act=pay&save" method="post" name="form_log" id="form_log">
	<div class="row align-items-center mb-3">
		<div class="col-lg-4 col-sm-8">
			<ul class="nav nav-pills nav-fill p-1" role="tablist">
				<li class="nav-item">
					<a class="nav-link mb-0 px-0 py-1 active" data-bs-toggle="tab" href="#set1" role="tab">支付配置</a>
				</li>
				<li class="nav-item">
					<a class="nav-link mb-0 px-0 py-1" data-bs-toggle="tab" href="#set2" role="tab">订单列表</a>
				</li>
			</ul>
		</div>
		<div class="col-lg-1 ms-auto">
			<input name="token" id="token" value="<?= LoginAuth::genToken() ?>" type="hidden" />
			<button class="btn btn-white mb-0" type="submit" title="保存">保存</button>
		</div>
	</div>

	<div class="tab-content tab-space">
		<div class="tab-pane active" id="set1">
			<div class="card mt-2">
				<div class="card-body">
					<h5 class="mb-3">支付概览</h5>
					<div class="row g-3 mb-4">
						<div class="col-md-3 col-6">
							<div class="border rounded p-3 h-100">
								<div class="text-sm text-muted">待支付订单</div>
								<div class="h4 mb-0"><?= (int)$pay_stats['pending'] ?></div>
							</div>
						</div>
						<div class="col-md-3 col-6">
							<div class="border rounded p-3 h-100">
								<div class="text-sm text-muted">已支付订单</div>
								<div class="h4 mb-0"><?= (int)$pay_stats['paid'] ?></div>
							</div>
						</div>
						<div class="col-md-3 col-6">
							<div class="border rounded p-3 h-100">
								<div class="text-sm text-muted">累计收款</div>
								<div class="h4 mb-0 text-success">¥<?= number_format((float)$pay_stats['paid_amount'], 2) ?></div>
							</div>
						</div>
						<div class="col-md-3 col-6">
							<div class="border rounded p-3 h-100">
								<div class="text-sm text-muted">当前通道</div>
								<div class="mt-1">
									<span class="badge bg-gradient-<?= $pay_status['wechat']['ready'] ? 'success' : 'secondary' ?> me-1"><?= htmlspecialchars($pay_status['wechat']['label']) ?></span>
									<span class="badge bg-gradient-<?= $pay_status['alipay']['ready'] ? 'success' : 'secondary' ?>"><?= htmlspecialchars($pay_status['alipay']['label']) ?></span>
								</div>
								<div class="text-xs text-muted mt-2">绿色表示参数已填写，可正常发起支付</div>
							</div>
						</div>
					</div>

					<h5 class="mb-3">收款接口</h5>
					<div class="row pay-select-row mb-3">
						<div class="col-12">
							<div class="form-check form-switch">
								<input type="hidden" name="payment_enabled" value="0">
								<input class="form-check-input" type="checkbox" name="payment_enabled" id="payment_enabled" value="1" <?= (!isset($payment['payment_enabled']) || !empty($payment['payment_enabled'])) ? 'checked' : '' ?>>
								<label class="form-check-label" for="payment_enabled"><strong>启用支付功能</strong></label>
							</div>
							<div class="form-text">关闭后，前台个人中心将隐藏充值入口，且无法发起新的支付订单（不影响已存在订单的回调与退款）。</div>
						</div>
					</div>
					<div class="row pay-select-row mb-3">
						<div class="col-12">
							<div class="form-check form-switch">
								<input type="hidden" name="pay_direct_redirect" value="0">
								<input class="form-check-input" type="checkbox" name="pay_direct_redirect" id="pay_direct_redirect" value="1" <?= !empty($payment['pay_direct_redirect']) ? 'checked' : '' ?>>
								<label class="form-check-label" for="pay_direct_redirect">直接跳转支付（跳过站内收银台，创建订单后直达支付网关）</label>
							</div>
							<div class="form-text">关闭时统一进入站内收银台页面；开启后易支付/码支付/支付宝网站应用等将直接跳转第三方收银台。</div>
						</div>
					</div>
					<div class="row pay-select-row">
						<div class="col-md-6 mb-3 mb-md-0">
							<label class="form-label">微信收款接口</label>
							<select class="form-select pay-native-select" name="weixinapi" id="weixinapi" data-hope-choices>
								<option value="0" <?= $wx0 ?>>微信官方</option>
								<option value="1" <?= $wx1 ?>>虎皮椒</option>
								<option value="2" <?= $wx2 ?>>易支付</option>
								<option value="3" <?= $wx3 ?>>码支付</option>
							</select>
							<div class="form-text">当前：<?= htmlspecialchars($pay_status['wechat']['label']) ?> <?= $pay_status['wechat']['ready'] ? '（已就绪）' : '（未配置）' ?></div>
						</div>
						<div class="col-md-6">
							<label class="form-label">支付宝收款接口</label>
							<select class="form-select pay-native-select" name="alipayapi" id="alipayapi" data-hope-choices>
								<option value="0" <?= $zfb0 ?>>支付宝官方</option>
								<option value="1" <?= $zfb1 ?>>虎皮椒</option>
								<option value="2" <?= $zfb2 ?>>易支付</option>
								<option value="3" <?= $zfb3 ?>>码支付</option>
							</select>
							<div class="form-text">当前：<?= htmlspecialchars($pay_status['alipay']['label']) ?> <?= $pay_status['alipay']['ready'] ? '（已就绪）' : '（未配置）' ?></div>
						</div>
					</div>
					<hr class="horizontal dark mt-2 my-3">

					<div id="option">
						<ul class="nav nav-pills nav-fill p-1 mb-3" role="tablist">
							<li class="nav-item"><a class="nav-link mb-0 px-0 py-1 pay-panel-tab active" data-panel="wxpay" href="javascript:;">微信官方</a></li>
							<li class="nav-item"><a class="nav-link mb-0 px-0 py-1 pay-panel-tab" data-panel="alipay" href="javascript:;">支付宝官方</a></li>
							<li class="nav-item"><a class="nav-link mb-0 px-0 py-1 pay-panel-tab" data-panel="hpjpay" href="javascript:;">虎皮椒</a></li>
							<li class="nav-item"><a class="nav-link mb-0 px-0 py-1 pay-panel-tab" data-panel="ypay" href="javascript:;">易支付</a></li>
							<li class="nav-item"><a class="nav-link mb-0 px-0 py-1 pay-panel-tab" data-panel="mpay" href="javascript:;">码支付</a></li>
						</ul>

						<div id="wxpay" class="pay-panel">
							<div class="alert alert-light mb-3">
								<h6>微信官方（Native 扫码）</h6>
								<ol class="mb-2 ps-3">
									<li>登录 <a href="https://pay.weixin.qq.com/" target="_blank">微信支付商户平台</a>，完成商户入驻。</li>
									<li>在「产品中心」开通 <strong>Native 支付</strong>（扫码支付）。</li>
									<li>在「账户中心 → API 安全」获取 API 密钥（KEY），填入下方。</li>
									<li>在「产品中心 → 开发配置」设置支付授权目录与回调域名（需备案 HTTPS 域名）。</li>
								</ol>
								<p class="mb-0 text-sm">异步通知地址：<code><?= htmlspecialchars($pay_urls['notify_official']) ?></code></p>
							</div>
							<div class="row">
								<div class="col-md-6 mb-3">
									<label class="form-label">微信 APPID</label>
									<input name="wx_appid" class="form-control" type="text" value="<?= htmlspecialchars($payment['wx_appid'] ?? '') ?>" placeholder="wx********">
								</div>
								<div class="col-md-6 mb-3">
									<label class="form-label">微信商户号</label>
									<input name="wx_mchid" class="form-control" type="text" value="<?= htmlspecialchars($payment['wx_mchid'] ?? '') ?>" placeholder="16 位数字">
								</div>
							</div>
							<div class="form-group">
								<label class="form-label">微信 API 密钥（KEY）</label>
								<input name="wx_key" class="form-control" value="<?= htmlspecialchars($payment['wx_key'] ?? '') ?>" placeholder="32 位密钥">
							</div>
						</div>

						<div id="alipay" class="pay-panel d-none">
							<div class="alert alert-light mb-3">
								<h6>支付宝官方</h6>
								<ol class="mb-2 ps-3">
									<li>注册 <a href="https://open.alipay.com/develop/manage" target="_blank">支付宝开放平台</a>，完成认证。</li>
									<li>申请开通 <a href="https://open.alipay.com/api/detail?code=I1080300001000041016" target="_blank">当面付</a>（扫码）或创建网站应用（PC 跳转）。</li>
									<li>应用加密方式选择「密钥」，获取 <code>应用私钥</code> 与 <code>支付宝公钥</code>。</li>
									<li>在应用开发设置中填写网关与回调地址（见下方接口地址）。</li>
								</ol>
								<p class="mb-1 text-sm"><strong>当面付</strong>：用户扫码支付；<strong>网站应用</strong>：PC 浏览器自动跳转支付宝收银台（优先使用）。</p>
								<p class="mb-0 text-sm">异步通知 / 授权回调：<code><?= htmlspecialchars($pay_urls['notify_official']) ?></code></p>
							</div>
							<div class="form-group mb-3">
								<label class="form-label">支付宝公钥</label>
								<textarea class="form-control" name="alipay_public_key" rows="4" placeholder="用于验签异步通知，不含头尾标记"><?= htmlspecialchars($payment['alipay_public_key'] ?? '') ?></textarea>
							</div>
							<p class="text-sm text-muted mb-2">当面付（扫码，二选一或同时配置）</p>
							<div class="row mb-3">
								<div class="col-md-6">
									<label class="form-label">当面付 APPID</label>
									<input type="text" name="alipay_f2f_appid" class="form-control" value="<?= htmlspecialchars($payment['alipay_f2f_appid'] ?? '') ?>">
								</div>
								<div class="col-md-6">
									<label class="form-label">当面付应用私钥</label>
									<textarea name="alipay_f2f_private_key" class="form-control" rows="3" placeholder="不含 -----BEGIN RSA PRIVATE KEY----- 头尾"><?= htmlspecialchars($payment['alipay_f2f_private_key'] ?? '') ?></textarea>
								</div>
							</div>
							<p class="text-sm text-muted mb-2">网站应用（PC 跳转，二选一或同时配置）</p>
							<div class="row">
								<div class="col-md-6">
									<label class="form-label">网站应用 APPID</label>
									<input type="text" class="form-control" name="alipay_web_appid" value="<?= htmlspecialchars($payment['alipay_web_appid'] ?? '') ?>">
								</div>
								<div class="col-md-6">
									<label class="form-label">网站应用私钥</label>
									<textarea name="alipay_web_private_key" class="form-control" rows="3" placeholder="不含头尾标记"><?= htmlspecialchars($payment['alipay_web_private_key'] ?? '') ?></textarea>
								</div>
							</div>
						</div>

						<div id="hpjpay" class="pay-panel d-none">
							<div class="alert alert-light mb-3">
								<h6>虎皮椒支付</h6>
								<ol class="mb-2 ps-3">
									<li>注册 <a href="https://www.xunhupay.com/" target="_blank">虎皮椒</a> 并完成商户认证。</li>
									<li>分别创建微信、支付宝应用，获取 APPID 与 APPSECRET。</li>
									<li>在虎皮椒后台设置异步通知：<code><?= htmlspecialchars($pay_urls['notify_hpj']) ?></code></li>
								</ol>
							</div>
							<div class="row">
								<div class="col-md-6 mb-3">
									<label class="form-label">微信 API 网关</label>
									<input name="hpj_wx_api" class="form-control" value="<?= htmlspecialchars($payment['hpj_wx_api'] ?? 'https://api.xunhupay.com/payment/do.html') ?>" placeholder="https://api.xunhupay.com/payment/do.html">
								</div>
								<div class="col-md-6 mb-3">
									<label class="form-label">支付宝 API 网关</label>
									<input name="hpj_alipay_api" class="form-control" value="<?= htmlspecialchars($payment['hpj_alipay_api'] ?? 'https://api.xunhupay.com/payment/do.html') ?>" placeholder="https://api.xunhupay.com/payment/do.html">
								</div>
								<div class="col-md-6 mb-3">
									<label class="form-label">微信 APPID</label>
									<input name="hpj_wx_id" class="form-control" type="text" value="<?= htmlspecialchars($payment['hpj_wx_id'] ?? '') ?>">
								</div>
								<div class="col-md-6 mb-3">
									<label class="form-label">微信 APPSECRET</label>
									<input name="hpj_wx_key" class="form-control" type="text" value="<?= htmlspecialchars($payment['hpj_wx_key'] ?? '') ?>">
								</div>
								<div class="col-md-6 mb-3">
									<label class="form-label">支付宝 APPID</label>
									<input name="hpj_alipay_id" class="form-control" type="text" value="<?= htmlspecialchars($payment['hpj_alipay_id'] ?? '') ?>">
								</div>
								<div class="col-md-6 mb-3">
									<label class="form-label">支付宝 APPSECRET</label>
									<input name="hpj_alipay_key" class="form-control" type="text" value="<?= htmlspecialchars($payment['hpj_alipay_key'] ?? '') ?>">
								</div>
							</div>
						</div>

						<div id="ypay" class="pay-panel d-none">
							<div class="alert alert-light mb-3">
								<h6>易支付</h6>
								<ol class="mb-2 ps-3">
									<li>在兼容易支付接口的平台注册商户，获取 PID、KEY 与网关地址。</li>
									<li>易支付平台良莠不齐，请谨慎选择，避免资金风险。</li>
									<li>异步通知地址：<code><?= htmlspecialchars($pay_urls['notify_epay']) ?></code></li>
								</ol>
							</div>
							<div class="form-group mb-3">
								<label class="form-label">易支付 API 网关</label>
								<input name="epay_api" class="form-control" value="<?= htmlspecialchars($payment['epay_api'] ?? '') ?>" placeholder="https://pay.example.com">
							</div>
							<div class="row">
								<div class="col-md-6">
									<label class="form-label">商户 ID（PID）</label>
									<input name="epay_id" class="form-control" type="text" value="<?= htmlspecialchars($payment['epay_id'] ?? '') ?>">
								</div>
								<div class="col-md-6">
									<label class="form-label">商户密钥（KEY）</label>
									<input name="epay_key" class="form-control" type="text" value="<?= htmlspecialchars($payment['epay_key'] ?? '') ?>">
								</div>
							</div>
						</div>

						<div id="mpay" class="pay-panel d-none">
							<div class="alert alert-light mb-3">
								<h6>码支付</h6>
								<p class="mb-2">填写码支付平台提供的 API 地址、商户 ID 与密钥。</p>
								<p class="mb-0 text-sm">异步通知地址：<code><?= htmlspecialchars($pay_urls['notify_codepay']) ?></code></p>
							</div>
							<div class="form-group mb-3">
								<label class="form-label">码支付 API</label>
								<input name="codepay_api" class="form-control" value="<?= htmlspecialchars($payment['codepay_api'] ?? '') ?>">
							</div>
							<div class="row">
								<div class="col-md-6">
									<label class="form-label">商户 ID</label>
									<input name="codepay_id" class="form-control" type="text" value="<?= htmlspecialchars($payment['codepay_id'] ?? '') ?>">
								</div>
								<div class="col-md-6">
									<label class="form-label">商户密钥</label>
									<input name="codepay_key" class="form-control" type="text" value="<?= htmlspecialchars($payment['codepay_key'] ?? '') ?>">
								</div>
							</div>
						</div>
					</div>

					<div class="alert alert-light border mb-4">
						<h6 class="mb-2">使用说明</h6>
						<ul class="mb-0 ps-3">
							<li>前台用户可在 <strong>个人中心 → 账户充值</strong> 发起支付，系统自动创建订单并跳转收银台。</li>
							<li>上方分别选择微信、支付宝要使用的收款接口；下方填写对应通道的参数并保存。</li>
							<li>关闭「直接跳转支付」时，订单将进入站内收银台；开启后易支付/码支付等将直达第三方网关。</li>
							<li>订单列表支持 <strong>查单同步</strong>、<strong>退款</strong>（已支付）、<strong>删除</strong>（待支付/已关闭/已退款）。</li>
							<li>官方支付宝支持 <strong>网站应用（PC 跳转）</strong> 或 <strong>当面付（扫码）</strong>，至少配置一组即可。</li>
							<li>第三方支付（虎皮椒 / 易支付 / 码支付）请将异步通知地址填到对应平台后台。</li>
						</ul>
					</div>

					<div class="alert alert-light border mb-4">
						<h6 class="mb-2">系统接口地址</h6>
						<div class="table-responsive">
							<table class="table table-sm align-middle mb-0">
								<tbody>
									<tr>
										<td class="text-muted" style="width:120px">官方回调</td>
										<td><code class="pay-copy-url" data-url="<?= htmlspecialchars($pay_urls['notify_official']) ?>"><?= htmlspecialchars($pay_urls['notify_official']) ?></code></td>
										<td class="text-end" style="width:70px"><button type="button" class="btn btn-sm btn-outline-secondary pay-copy-btn">复制</button></td>
									</tr>
									<tr>
										<td class="text-muted">虎皮椒回调</td>
										<td><code class="pay-copy-url" data-url="<?= htmlspecialchars($pay_urls['notify_hpj']) ?>"><?= htmlspecialchars($pay_urls['notify_hpj']) ?></code></td>
										<td class="text-end"><button type="button" class="btn btn-sm btn-outline-secondary pay-copy-btn">复制</button></td>
									</tr>
									<tr>
										<td class="text-muted">易支付回调</td>
										<td><code class="pay-copy-url" data-url="<?= htmlspecialchars($pay_urls['notify_epay']) ?>"><?= htmlspecialchars($pay_urls['notify_epay']) ?></code></td>
										<td class="text-end"><button type="button" class="btn btn-sm btn-outline-secondary pay-copy-btn">复制</button></td>
									</tr>
									<tr>
										<td class="text-muted">码支付回调</td>
										<td><code class="pay-copy-url" data-url="<?= htmlspecialchars($pay_urls['notify_codepay']) ?>"><?= htmlspecialchars($pay_urls['notify_codepay']) ?></code></td>
										<td class="text-end"><button type="button" class="btn btn-sm btn-outline-secondary pay-copy-btn">复制</button></td>
									</tr>
									<tr>
										<td class="text-muted">收银台</td>
										<td><code><?= htmlspecialchars($pay_urls['cashier']) ?></code></td>
										<td></td>
									</tr>
									<tr>
										<td class="text-muted">支付完成跳转</td>
										<td><code><?= htmlspecialchars($pay_urls['return']) ?></code></td>
										<td></td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>

				</div>
			</div>
		</div>

		<div class="tab-pane" id="set2">
			<div class="card mt-2">
				<div class="card-body">
					<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
						<h5 class="mb-0">订单列表</h5>
						<div class="d-flex align-items-center gap-2 flex-wrap">
							<form method="get" class="d-flex align-items-center gap-2 mb-0">
								<input type="hidden" name="act" value="pay">
								<select name="order_status" class="form-select form-select-sm hope-choices-skip" style="width:auto" onchange="this.form.submit()">
									<option value="all" <?= $order_status === 'all' ? 'selected' : '' ?>>全部状态</option>
									<option value="0" <?= $order_status === '0' ? 'selected' : '' ?>>待支付</option>
									<option value="1" <?= $order_status === '1' ? 'selected' : '' ?>>已支付</option>
									<option value="2" <?= $order_status === '2' ? 'selected' : '' ?>>已关闭</option>
									<option value="3" <?= $order_status === '3' ? 'selected' : '' ?>>已退款</option>
								</select>
								<select name="order_biz" class="form-select form-select-sm hope-choices-skip" style="width:auto" onchange="this.form.submit()">
									<option value="all" <?= $order_biz === 'all' ? 'selected' : '' ?>>全部业务</option>
									<option value="shop_log" <?= $order_biz === 'shop_log' ? 'selected' : '' ?>>资源购买</option>
									<option value="shop_vip" <?= $order_biz === 'shop_vip' ? 'selected' : '' ?>>会员开通</option>
									<option value="recharge" <?= $order_biz === 'recharge' ? 'selected' : '' ?>>账户充值</option>
									<option value="apply" <?= $order_biz === 'apply' ? 'selected' : '' ?>>应用购买</option>
								</select>
							</form>
							<span class="text-sm text-muted">共 <?= (int)$pay_order_total ?> 条</span>
						</div>
					</div>
					<form id="form_orders" action="<?= SELF ?>?act=pay" method="post">
						<input type="hidden" name="token" id="order_token" value="<?= LoginAuth::genToken() ?>">
						<input type="hidden" name="order_operate" id="order_operate" value="">
						<div class="d-flex flex-wrap gap-2 mb-3">
							<button type="button" class="btn btn-sm btn-outline-primary" onclick="payBatchAction('sync')">批量查单</button>
							<button type="button" class="btn btn-sm btn-outline-dark" onclick="payBatchAction('close')">批量关闭</button>
							<button type="button" class="btn btn-sm btn-outline-danger" onclick="payBatchAction('delete')">批量删除</button>
						</div>
					<div class="table-responsive">
						<table class="table align-items-center mb-0">
							<thead>
								<tr>
									<th style="width:36px">
										<div class="form-check mb-0">
											<input class="form-check-input" type="checkbox" id="checkAllOrders">
										</div>
									</th>
									<th>订单号</th>
									<th>用户</th>
									<th>业务</th>
									<th>标题</th>
									<th>金额</th>
									<th>方式</th>
									<th>状态</th>
									<th>创建时间</th>
									<th>支付时间</th>
									<th>操作</th>
								</tr>
							</thead>
							<tbody>
								<?php if (!empty($pay_orders['list'])): foreach ($pay_orders['list'] as $o):
									$st = (int)$o['status'];
									$cashier_url = Pay_Sdk::officialCashierUrl($o['order_no']);
								?>
								<tr data-order-no="<?= htmlspecialchars($o['order_no']) ?>">
									<td>
										<div class="form-check mb-0">
											<input class="form-check-input order-ids" type="checkbox" name="order_no[]" value="<?= htmlspecialchars($o['order_no']) ?>">
										</div>
									</td>
									<td><code class="text-sm"><?= htmlspecialchars($o['order_no']) ?></code></td>
									<td><?= htmlspecialchars($o['user_label']) ?></td>
									<td><?= htmlspecialchars($o['biz_label']) ?></td>
									<td><?= htmlspecialchars($o['title']) ?></td>
									<td>¥<?= number_format((float)$o['amount'], 2) ?></td>
									<td><?= htmlspecialchars($o['pay_type_label'] ?? ($o['pay_type'] === 'wechat' ? '微信' : ($o['pay_type'] === 'balance' ? '余额' : '支付宝'))) ?></td>
									<td><span class="badge bg-gradient-<?= $st === 1 ? 'success' : ($st === 3 ? 'warning' : ($st === 2 ? 'dark' : 'secondary')) ?> order-status-badge"><?= htmlspecialchars($o['status_label']) ?></span></td>
									<td class="text-sm"><?= htmlspecialchars($o['create_time_fmt']) ?></td>
									<td class="text-sm order-pay-time"><?= htmlspecialchars($o['pay_time_fmt']) ?></td>
									<td class="text-sm">
										<?php if ($st === 0): ?>
										<a href="<?= htmlspecialchars($cashier_url) ?>" target="_blank" class="badge badge-info me-1">收银台</a>
										<button type="button" class="badge badge-primary me-1 pay-order-sync" data-no="<?= htmlspecialchars($o['order_no']) ?>">查单</button>
										<button type="button" class="badge badge-danger pay-order-del" data-no="<?= htmlspecialchars($o['order_no']) ?>">删除</button>
										<?php elseif ($st === 1): ?>
										<button type="button" class="badge badge-warning pay-order-refund" data-no="<?= htmlspecialchars($o['order_no']) ?>">退款</button>
										<?php elseif ($st === 3): ?>
										<button type="button" class="badge badge-danger pay-order-del" data-no="<?= htmlspecialchars($o['order_no']) ?>">删除</button>
										<?php else: ?>
										<button type="button" class="badge badge-danger pay-order-del" data-no="<?= htmlspecialchars($o['order_no']) ?>">删除</button>
										<?php endif; ?>
									</td>
								</tr>
								<?php endforeach; else: ?>
								<tr><td colspan="11" class="text-center text-muted py-4">暂无订单，用户充值后将在此显示</td></tr>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
					</form>
					<?php if ($pay_order_pages > 1): ?>
					<nav class="d-flex justify-content-center mt-3" aria-label="订单分页">
						<ul class="pagination mb-0"><?= pagination($pay_order_total, $order_per_page, $order_page, $pay_order_page_url, '#set2') ?></ul>
					</nav>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</form>
<script>
	$("#menu_system_manage").addClass('active');
	$("#menu_system").addClass('show');
	$("#menu_pay").addClass('active');

	$("#form_log").submit(function(event) {
		event.preventDefault();
		submitForm("#form_log");
	});

	var panelMap = { '0': 'wxpay', '1': 'hpjpay', '2': 'ypay', '3': 'mpay' };
	var aliPanelMap = { '0': 'alipay', '1': 'hpjpay', '2': 'ypay', '3': 'mpay' };

	function showPayPanel(id) {
		document.querySelectorAll('.pay-panel').forEach(function(el) { el.classList.add('d-none'); });
		document.querySelectorAll('.pay-panel-tab').forEach(function(el) { el.classList.remove('active'); });
		var panel = document.getElementById(id);
		if (panel) panel.classList.remove('d-none');
		var tab = document.querySelector('.pay-panel-tab[data-panel="' + id + '"]');
		if (tab) {
			tab.classList.add('active');
			if (typeof hopeUpdateMovingTab === 'function') {
				var nav = tab.closest('ul.nav-pills[role="tablist"]');
				if (nav) hopeUpdateMovingTab(nav, tab);
			}
		}
	}

	function syncPanelFromSelect() {
		var wx = document.getElementById('weixinapi');
		var ali = document.getElementById('alipayapi');
		if (!wx || !ali) return;
		var wxPanel = panelMap[wx.value] || 'wxpay';
		var aliPanel = aliPanelMap[ali.value] || 'alipay';
		showPayPanel(wx.value === ali.value ? wxPanel : wxPanel);
	}

	document.querySelectorAll('.pay-panel-tab').forEach(function(tab) {
		tab.addEventListener('click', function() {
			showPayPanel(tab.getAttribute('data-panel'));
		});
	});

	document.querySelectorAll('.pay-copy-btn').forEach(function(btn) {
		btn.addEventListener('click', function() {
			var code = btn.closest('tr').querySelector('.pay-copy-url');
			var url = code ? (code.getAttribute('data-url') || code.textContent) : '';
			if (!url) return;
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(url).then(function() { cocoMessage.success('已复制'); });
			} else {
				var ta = document.createElement('textarea');
				ta.value = url;
				document.body.appendChild(ta);
				ta.select();
				document.execCommand('copy');
				document.body.removeChild(ta);
				cocoMessage.success('已复制');
			}
		});
	});

	var wxSelect = document.getElementById('weixinapi');
	var aliSelect = document.getElementById('alipayapi');
	if (wxSelect) {
		wxSelect.addEventListener('change', function() { showPayPanel(panelMap[wxSelect.value] || 'wxpay'); });
	}
	if (aliSelect) {
		aliSelect.addEventListener('change', function() { showPayPanel(aliPanelMap[aliSelect.value] || 'alipay'); });
	}

	showPayPanel(panelMap[<?= (int)($payment['weixinapi'] ?? 0) ?>] || 'wxpay');

	var payToken = document.getElementById('token').value;

	function payOrderAction(action, orderNo, confirmMsg) {
		Swal.fire({
			title: confirmMsg,
			icon: 'question',
			showCancelButton: true,
			confirmButtonText: '确定',
			cancelButtonText: '取消'
		}).then(function(res) {
			if (!res.isConfirmed) return;
			$.ajax({
				type: 'GET',
				url: '?act=pay&' + action + '&order_no=' + encodeURIComponent(orderNo) + '&token=' + encodeURIComponent(payToken),
				dataType: 'json',
				success: function(resp) {
					var ok = resp && resp.code === 200;
					Toast.fire({ icon: ok ? 'success' : 'error', title: (resp.data && resp.data.msg) || resp.msg || (ok ? '操作成功' : '操作失败') });
					if (ok && action === 'sync' && resp.data && resp.data.status === 1) {
						var row = document.querySelector('tr[data-order-no="' + orderNo + '"]');
						if (row) {
							var badge = row.querySelector('.order-status-badge');
							if (badge) { badge.textContent = '已支付'; badge.className = 'badge bg-gradient-success order-status-badge'; }
							var payTime = row.querySelector('.order-pay-time');
							if (payTime) payTime.textContent = new Date().toLocaleString('sv-SE').replace('T', ' ').slice(0, 19);
						}
						setTimeout(function(){ location.reload(); }, 1200);
					} else if (ok && (action === 'del' || action === 'refund')) {
						setTimeout(function(){ location.reload(); }, 1200);
					}
				},
				error: function(xhr) {
					var msg = '操作失败';
					try { msg = JSON.parse(xhr.responseText).msg || msg; } catch(e) {}
					Toast.fire({ icon: 'error', title: msg });
				}
			});
		});
	}

	document.querySelectorAll('.pay-order-del').forEach(function(btn) {
		btn.addEventListener('click', function() {
			payOrderAction('del', btn.getAttribute('data-no'), '确定删除该订单吗？');
		});
	});
	document.querySelectorAll('.pay-order-refund').forEach(function(btn) {
		btn.addEventListener('click', function() {
			payOrderAction('refund', btn.getAttribute('data-no'), '确定对该订单发起退款吗？充值订单将扣回用户余额。');
		});
	});
	document.querySelectorAll('.pay-order-sync').forEach(function(btn) {
		btn.addEventListener('click', function() {
			payOrderAction('sync', btn.getAttribute('data-no'), '向支付平台查询该订单状态并同步？');
		});
	});

	var checkAllOrders = document.getElementById('checkAllOrders');
	if (checkAllOrders) {
		checkAllOrders.addEventListener('change', function() {
			document.querySelectorAll('.order-ids').forEach(function(cb) {
				cb.checked = checkAllOrders.checked;
			});
		});
	}

	function payBatchAction(action) {
		var checked = document.querySelectorAll('.order-ids:checked');
		if (!checked.length) {
			Toast.fire({ icon: 'warning', title: '请先选择订单' });
			return;
		}
		var msgs = {
			delete: '确定删除所选订单吗？已支付订单将被跳过。',
			close: '确定关闭所选待支付订单吗？',
			sync: '向支付平台批量查询并同步所选订单？'
		};
		Swal.fire({
			title: msgs[action] || '确定执行批量操作？',
			icon: 'question',
			showCancelButton: true,
			confirmButtonText: '确定',
			cancelButtonText: '取消'
		}).then(function(res) {
			if (!res.isConfirmed) return;
			document.getElementById('order_operate').value = action;
			var form = document.getElementById('form_orders');
			var fd = new FormData(form);
			$.ajax({
				type: 'POST',
				url: '?act=pay',
				data: fd,
				processData: false,
				contentType: false,
				dataType: 'json',
				success: function(resp) {
					var ok = resp && resp.code === 200;
					Toast.fire({ icon: ok ? 'success' : 'error', title: (resp.data && resp.data.msg) || resp.msg || (ok ? '操作成功' : '操作失败') });
					if (ok) setTimeout(function(){ location.reload(); }, 1200);
				},
				error: function(xhr) {
					var msg = '操作失败';
					try { msg = JSON.parse(xhr.responseText).msg || msg; } catch(e) {}
					Toast.fire({ icon: 'error', title: msg });
				}
			});
		});
	}

	if (location.hash === '#set2' || <?= (int)$order_page ?> > 1 || '<?= htmlspecialchars($order_status, ENT_QUOTES) ?>' !== 'all' || '<?= htmlspecialchars($order_biz ?? 'all', ENT_QUOTES) ?>' !== 'all') {
		var tab2 = document.querySelector('[href="#set2"]');
		if (tab2) {
			var tab = new bootstrap.Tab(tab2);
			tab.show();
		}
	}
</script>
