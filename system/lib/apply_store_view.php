<?php
/**
 * 后台应用中心列表视图（系统核心，不依赖 apply 插件）
 */
defined('HOPE_ROOT') || exit('access denied!');

/** 是否处于有效促销期 */
function hope_apply_store_has_promo(array $app) {
    $price = (float)($app['price'] ?? 0);
    $promo = (float)($app['promo_price'] ?? 0);
    if ($price <= 0 || $promo <= 0 || $promo >= $price) {
        return false;
    }
    $end = (int)($app['promo_end_time'] ?? 0);
    if ($end > 0 && $end <= time()) {
        return false;
    }
    return true;
}

/** 促销实际售价 */
function hope_apply_store_sale_price(array $app) {
    return hope_apply_store_has_promo($app) ? (float)$app['promo_price'] : (float)($app['price'] ?? 0);
}

/** 预览图地址 */
function hope_apply_store_icon(array $app) {
    $icon = $app['icon'] ?? ($app['preview'] ?? '');
    $type = ($app['app_type'] ?? $app['type'] ?? '') === 'theme' ? 'theme' : 'plugin';
    if ($icon === '') {
        return './content/admin/img/' . $type . '-icon.png';
    }
    if (strpos($icon, 'http') === 0 || strpos($icon, './') === 0) {
        return $icon;
    }
    return rtrim(SITE_URL, '/') . '/' . ltrim($icon, '/');
}

/** 更新时间文案 */
function hope_apply_store_update_label(array $app) {
    if (!empty($app['update_Date'])) {
        return (string)$app['update_Date'];
    }
    if (!empty($app['update_time'])) {
        return function_exists('formatApplyUpdateTime')
            ? formatApplyUpdateTime($app['update_time'])
            : (string)$app['update_time'];
    }
    return '';
}

/** 价格 HTML（促销价与原价同一行，倒计时紧贴价格） */
function hope_apply_store_price_html(array $app) {
    $price = (float)($app['price'] ?? 0);
    if ($price <= 0) {
        return '<span class="apply-price-free">免费</span>';
    }
    if (hope_apply_store_has_promo($app)) {
        $sale = hope_apply_store_sale_price($app);
        $html = '<span class="apply-price-line">'
            . '<span class="apply-price-sale text-danger">¥' . number_format($sale, 2) . '</span>'
            . '<span class="apply-price-old">¥' . number_format($price, 2) . '</span>'
            . '</span>';
        $end = (int)($app['promo_end_time'] ?? 0);
        if ($end > 0) {
            $html .= '<div class="apply-admin-countdown" data-promo-end="' . $end . '">'
                . '<span class="apply-admin-countdown-label">限时</span> '
                . '<span class="apply-admin-countdown-time">--:--:--</span></div>';
        }
        return $html;
    }
    return '<span class="apply-price-sale text-danger">¥' . number_format($price, 2) . '</span>';
}

/** 官方购买链接 */
function hope_apply_store_purchase_url(array $app) {
    if (!empty($app['purchase_url'])) {
        return (string)$app['purchase_url'];
    }
    static $model = null;
    if ($model === null) {
        $model = new Apply_Model();
    }
    return $model->getPurchaseUrl((int)($app['id'] ?? 0));
}

/** 官方充值链接 */
function hope_apply_store_recharge_url() {
    static $model = null;
    if ($model === null) {
        $model = new Apply_Model();
    }
    return $model->getRechargeUrl(trim((string)Option::get('apply_bind_username')));
}

/** 官方应用详情/介绍页 */
function hope_apply_store_detail_url(array $app) {
    if (!empty($app['detail_url'])) {
        return (string)$app['detail_url'];
    }
    static $model = null;
    if ($model === null) {
        $model = new Apply_Model();
    }
    return $model->getDetailUrl((int)($app['id'] ?? 0));
}

/** 操作按钮 HTML */
function hope_apply_store_actions_html(array $app) {
    $type = $app['app_type'] ?? $app['type'] ?? 'plugin';
    $detail_url = hope_apply_store_detail_url($app);
    $download_url = urlencode($app['download_url'] ?? '');
    $alias = htmlspecialchars($app['alias'] ?? '');
    $html = '<a href="' . htmlspecialchars($detail_url) . '" class="btn btn-outline-primary btn-xs mb-0" target="_blank" rel="noopener" title="前往官网查看介绍">详情</a>';
    $install_btn = '<a href="#" class="btn btn-success btn-xs mb-0 installBtn" data-url="' . $download_url . '" data-type="' . htmlspecialchars($type) . '" data-alias="' . $alias . '">安装</a>';
    $can_download = array_key_exists('can_download', $app)
        ? !empty($app['can_download'])
        : (!empty($app['purchased']) || (float)($app['price'] ?? 0) <= 0);
    if ($can_download) {
        $html .= $install_btn;
        return $html;
    }
    if ((float)($app['price'] ?? 0) > 0) {
        if (!Register::isBound() || !Register::isValid()) {
            $html .= '<span class="btn btn-light btn-xs disabled mb-0" title="请先绑定商店账号">需绑定</span>';
            return $html;
        }
        if (!empty($app['balance_enough'])) {
            $bal = isset($app['user_balance']) ? ' title="余额 ¥' . number_format((float)$app['user_balance'], 2) . '"' : '';
            $html .= '<a href="#" class="btn btn-xs btn-danger mb-0 applyStoreBuyBtn" data-apply-id="' . (int)($app['id'] ?? 0) . '"' . $bal . '>购买</a>';
        } else {
            $html .= '<a href="' . htmlspecialchars(hope_apply_store_recharge_url()) . '" class="btn btn-xs btn-warning mb-0" target="_blank" rel="noopener" title="余额不足，请先充值">充值</a>';
        }
        return $html;
    }
    $html .= '<span class="btn btn-light btn-xs disabled mb-0" title="请先绑定商店账号">需绑定</span>';
    return $html;
}

/** 统一主题风格应用卡片 */
function hope_apply_render_store_card(array $app) {
    $type = $app['app_type'] ?? $app['type'] ?? 'plugin';
    $typeBadge = $type === 'theme'
        ? '<span class="badge badge-success p-1">主题</span>'
        : '<span class="badge badge-primary p-1">插件</span>';
    $vipBadge = !empty($app['svip']) ? '<span class="badge badge-warning p-1">VIP</span>' : '';
    $icon = hope_apply_store_icon($app);
    $name = (string)($app['name'] ?? '');
    $nameShort = function_exists('subString') ? subString($name, 0, 16) : mb_substr($name, 0, 16);
    $info = (string)($app['info'] ?? ($app['summary'] ?? ''));
    $infoShort = function_exists('subString') ? subString($info, 0, 48) : mb_substr($info, 0, 48);
    $author = htmlspecialchars($app['author'] ?? '');
    $authorId = (int)($app['author_id'] ?? 0);
    $ver = htmlspecialchars($app['ver'] ?? ($app['version'] ?? ''));
    $update = htmlspecialchars(hope_apply_store_update_label($app));
    $downloads = htmlspecialchars((string)($app['downloads'] ?? ($app['download_count'] ?? 0)));
    $alias = htmlspecialchars($app['alias'] ?? '');
    ?>
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card hover-shadow apply-store-card" data-app-type="<?= htmlspecialchars($type) ?>" data-app-alias="<?= $alias ?>" data-app-version="<?= $ver ?>">
            <div class="card-body p-3">
                <div class="current-badge">v<?= $ver ?></div>
                <div class="update-badge apply-update-badge" style="display:none;">有更新</div>
                <img src="<?= htmlspecialchars($icon) ?>" alt="<?= htmlspecialchars($name) ?>" class="apply-store-cover w-100">
                <p class="text-sm mt-3 mb-0 apply-store-desc" title="<?= htmlspecialchars($info) ?>"><?= htmlspecialchars($infoShort) ?></p>
                <hr class="horizontal dark">
                <div class="row align-items-end">
                    <div class="col-7">
                        <h6 class="text-sm mb-1" title="<?= htmlspecialchars($name) ?>">
                            <a href="<?= htmlspecialchars(hope_apply_store_detail_url($app)) ?>" target="_blank" rel="noopener" class="text-dark"><?= htmlspecialchars($nameShort) ?></a>
                        </h6>
                        <p class="text-secondary text-sm mb-1">开发者：<?php if ($authorId > 0): ?><a href="javascript:;" class="apply-store-author-link" data-author-id="<?= $authorId ?>" data-author-name="<?= $author ?>" title="查看作品"><?= $author ?></a><?php else: ?><?= $author ?: '未知' ?><?php endif; ?></p>
                        <p class="text-secondary text-sm mb-0"><?= $typeBadge ?><?= $vipBadge ?> <?= $update ?></p>
                    </div>
                    <div class="col-5 text-end">
                        <div class="apply-store-price text-sm mb-1"><?= hope_apply_store_price_html($app) ?></div>
                        <p class="text-secondary text-xs mb-2"><i class="fa fa-download"></i> <?= $downloads ?></p>
                        <div class="apply-store-actions"><?= hope_apply_store_actions_html($app) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}
