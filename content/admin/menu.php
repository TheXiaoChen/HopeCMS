<?php defined('HOPE_ROOT') || exit('access denied!');

$hopeMenuToken = hope_admin_token_param();

function renderMenuItems($id,$children, $level = 0) {
    if (!is_array($children) || empty($children)) {
        return;
    }
    foreach ($children as $child):
        $child['url'] = Url::menu($child['type'], $child['typeId'], $child['url']);
        $menutype_names = Menu_Model::$menutype_name[$child['type']];
        $fields = unserialize($child['fields']);
        if (!is_array($fields))$fields = array(); ?>
        <li class="list-group-item d-flex justify-content-between align-items-center" draggable data-id="<?= $child['id'] ?>">
            <span>
                [<?= $menutype_names ?>]<?= $child['name'] ?><?= $child['hide'] == 'y' ? ' <span class="badge bg-gradient-secondary">隐藏</span>' : '' ?>
                <br>
                <a href="<?= $child['url'] ?>" title="<?= $child['url'] ?>" target="<?= $child['target'] == 'y' ? '_blank' : '_self' ?>">
                    <?= $child['url'] ?>
                </a>
            </span>
            <div class="dropdown">
                <span class="badge badge-primary badge-pill dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">操作</span>
                <ul class="dropdown-menu px-2 py-3" aria-labelledby="dropdownMenuButton">
                    <li><a class="dropdown-item border-radius-md" href="javascript:;" data-bs-toggle="modal" data-id="<?= $id ?>" data-item-id="<?= $child['id'] ?>" data-name="<?= htmlspecialchars($child['name'], ENT_QUOTES) ?>" data-url="<?= htmlspecialchars($child['url'], ENT_QUOTES) ?>" data-target="<?= $child['target'] ?>" data-type="<?= isset($child['type']) ? $child['type'] : 0 ?>" data-typeid="<?= isset($child['typeId']) ? $child['typeId'] : 0 ?>" data-pid="<?= isset($child['pid']) ? $child['pid'] : 0 ?>" data-fields="<?= htmlspecialchars(json_encode($fields), ENT_QUOTES) ?>"  data-bs-target="#AddMenuItemModal">编辑</a></li>
                    <li><a class="dropdown-item border-radius-md" href="javascript:;" data-bs-toggle="modal" data-id="<?= $id ?>" data-pid="<?= $child['id'] ?>" data-bs-target="#AddMenuItemModal">新增子菜单项</a></li>
                    <?php if ($child['hide'] == 'n'): ?>
                    <li><a class="dropdown-item border-radius-md text-danger" href="<?= SELF ?>?act=menu&hide&id=<?= $child['id']; ?>&<?= $hopeMenuToken ?>">隐藏</a></li>
                    <?php else: ?>
                    <li><a class="dropdown-item border-radius-md text-danger" href="<?= SELF ?>?act=menu&show&id=<?= $child['id']; ?>&<?= $hopeMenuToken ?>">显示</a></li>
                    <?php endif; ?>
                    <li><a class="dropdown-item border-radius-md text-danger" href="javascript: hp_delete(<?= $child['id'] ?>, 'menu', '<?= LoginAuth::genToken() ?>');">删除</a></li>
                </ul>
            </div>
        </li>
        
            <?php if (isset($child['child']) && !empty($child['child'])): ?>
            <li class="list-group-item" draggable>
                <ul class="list-group">
                    <?php renderMenuItems($id,$child['child'], $level + 1); ?>
                </ul>
            </li>
        <?php endif; ?>
    <?php endforeach;
}
 
?>
<div class="row" id="option">
  <div class="col-lg-3" >
        <div class="card">
            <div class="card-body pb-3">
				<div class="d-lg-flex">
					<div>
						<h5 class="mb-0">菜单</h5>
					</div>
					<div class="ms-auto my-auto mt-lg-0 mt-4">
						<div class="ms-auto my-auto">
							<button type="button" class="btn btn-xs btn-outline-primary" data-bs-toggle="modal" data-id="" data-bs-target="#MenuModal">添加</button>
						</div>
					</div>
				</div>
			</div>

            <div class="nav-wrapper position-relative end-0">
                <ul class="nav nav-pills nav-fill flex-column p-1" role="tablist">
                    <?php
                        $menu_id = $menu_root_ids ?? [];
                        foreach ($menu_id as $id):
                            $value = $menus[$id];
                            $is_active = ((int)$id === (int)($menu_default_id ?? 0));
                    ?>
                    <li class="nav-item d-flex ">
                        <a href="#menu<?= $id; ?>" class="nav-link mb-0 px-0 py-1 ps-2 menu-nav-link <?= $is_active ? 'active' : '' ?>" style="text-align: initial;" data-menu-target="#menu<?= $id; ?>" aria-controls="menu<?= $id; ?>" aria-selected="<?= $is_active ? 'true' : 'false' ?>">
                            <i class="fa fa-bars text-sm me-2"></i>
                            <span class="menu-nav-label"><?= $value['name'] ?></span>
                            <?php if ($value['home'] === 'n'): ?>
                            <span class="badge badge-sm bg-gradient-success ms-1">主菜单</span>
                            <?php endif; ?>
                        </a>
                        <div class="dropdown pe-1">
                            <span class="badge badge-primary badge-pill dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"> </span>
                            <ul class="dropdown-menu px-2 py-3" aria-labelledby="dropdownMenuButton">
                                <li><a class="dropdown-item border-radius-md" href="<?= SELF ?>?act=menu&home=<?= $id; ?>&<?= $hopeMenuToken ?>">设置为主菜单</a></li>
                                <li><a class="dropdown-item border-radius-md" href="javascript:;" data-bs-toggle="modal" data-id="<?= $id; ?>" data-name="<?= htmlspecialchars($value['name'], ENT_QUOTES) ?>" data-bs-target="#MenuModal">编辑</a></li>

                                <li><a class="dropdown-item border-radius-md text-danger" href="javascript: hp_delete(<?= $id; ?>, 'menu', '<?= LoginAuth::genToken() ?>');">删除</a></li>
                            </ul>
                        </div>
                    </li>
                    <?php endforeach; ?>
                    <li class="nav-item">
                        <a href="#side" class="nav-link mb-0 px-0 py-1 ps-2 menu-nav-link" style="text-align: initial;" data-menu-target="#side" aria-controls="side" aria-selected="false">
                            <i class="fa fa-table-columns text-sm me-2"></i> 侧边栏
                        </a>
                    </li>
                </ul>
            </div>
            
    </div>
  </div>
    <div class=" col-lg-9 mt-lg-0 mt-4">
<?php
    foreach ($menu_id as $key => $id):
        $value = $menus[$id];
        $is_default = ((int)$id === (int)($menu_default_id ?? 0));
        ?>
      <div class="collapse menu-panel-collapse <?= $is_default ? 'show' : '' ?>" id="menu<?= $id ?>" aria-expanded="<?= $is_default ? 'true' : 'false' ?>">
        <div class="card">
			<div class="card-body pb-3">
				<div class="d-lg-flex">
					<div>
						<h5 class="mb-0"><?= htmlspecialchars($value['name']) ?><?php if ($value['home'] === 'n'): ?> <span class="badge bg-gradient-success">主菜单</span><?php endif; ?></h5>
					</div>
                    <div class="ms-auto my-auto mt-lg-0 mt-4">
                        <div class="ms-auto my-auto">
                            <button type="button" class="btn btn-xs btn-outline-primary" data-bs-toggle="modal" data-id="<?= $id ?>" data-bs-target="#AddMenuItemModal">添加</button>
                            <button type="button" class="btn btn-xs btn-primary save-order-btn" data-id="<?= $id ?>">保存排序</button>
                        </div>
                    </div>
				</div>
			</div>
            <ul class="list-group">
                <?php renderMenuItems($id,$value['child'] ?? []);?>
            </ul>
		</div>
      </div>
    <?php endforeach; ?>
    
      <?php
        $hope_side_slots = hope_theme_side_slots();
        $hope_side_has_slots = !empty($hope_side_slots);
        if (!$hope_side_has_slots) {
            $hope_side_slots = [];
            $hope_side_slot_active = 'sidebar';
        } else {
            $hope_side_slot_active = hope_theme_side_normalize_slot($_GET['side_slot'] ?? 'sidebar');
            if (!in_array($hope_side_slot_active, $hope_side_slots, true)) {
                $hope_side_slot_active = $hope_side_slots[0];
            }
        }
        $hope_side_registry = hope_theme_side_registry();
      ?>
      <div class="collapse menu-panel-collapse" id="side" aria-expanded="false">
        <div class="card">
			<div class="card-body pb-3">
				<div class="d-lg-flex">
					<div>
						<h5 class="mb-0">侧边栏</h5>
                        <p class="text-xs text-secondary mb-0 mt-1">组件归属主题 widgets/ 下侧边栏组（sidebar.php / sidebar1.php…）</p>
					</div>
                    <?php if ($hope_side_has_slots): ?>
                    <div class="ms-auto my-auto mt-lg-0 mt-4">
                        <div class="ms-auto my-auto">
                            <button type="button" class="btn bg-gradient-primary btn-sm mb-0" data-bs-toggle="modal" data-bs-target="#SideModal">添加</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm mb-0 ms-2 save-side-order-btn" data-id="side">保存排序</button>
                        </div>
                    </div>
                    <?php endif; ?>
				</div>
                <?php if (!$hope_side_has_slots): ?>
                <p class="text-sm text-secondary mb-0 mt-3">当前主题未提供侧边栏（无 <code>widgets/sidebar.php</code> 等容器）。无侧边栏功能的主题可不创建 <code>widgets/</code> 目录。</p>
                <?php elseif (count($hope_side_slots) > 1): ?>
                <div class="nav-wrapper mt-3">
                    <ul class="nav nav-pills nav-fill p-1" role="tablist" id="hope-side-slot-tabs">
                        <?php foreach ($hope_side_slots as $slot): ?>
                        <li class="nav-item">
                            <a class="nav-link mb-0 px-2 py-1 hope-side-slot-tab <?= $slot === $hope_side_slot_active ? 'active' : '' ?>" href="#side-<?= htmlspecialchars($slot) ?>" data-side-slot="<?= htmlspecialchars($slot, ENT_QUOTES) ?>"><?= htmlspecialchars(hope_theme_side_slot_label($slot)) ?> <span class="text-xs opacity-6">(<?= htmlspecialchars($slot) ?>.php)</span></a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
			</div>
            <?php foreach ($hope_side_slots as $slot): ?>
            <ul class="list-group hope-side-slot-list <?= $slot === $hope_side_slot_active ? '' : 'd-none' ?>" data-side-slot="<?= htmlspecialchars($slot, ENT_QUOTES) ?>">
                <?php
                $slotHas = false;
                foreach ($menus as $key => $value):
                    if ((int)($value['type'] ?? 0) !== 5) {
                        continue;
                    }
                    if (hope_theme_side_resolve_item_slot($value) !== $slot) {
                        continue;
                    }
                    $slotHas = true;
                    $fields = @unserialize($value['fields'] ?? '');
                    if (!is_array($fields)) {
                        $fields = [];
                    }
                    $typeKey = (string)($value['typeId'] ?? '');
                    $typeLabel = $typeKey === '' || $typeKey === '0'
                        ? '自定义'
                        : (string)($hope_side_registry[$typeKey]['title'] ?? $typeKey);
                ?>
                <li class="list-group-item d-flex justify-content-between align-items-center" draggable data-id="<?= (int)$value['id'] ?>">
                    <span>
                        <span class="badge bg-gradient-secondary me-1"><?= htmlspecialchars($typeLabel) ?></span>
                        <?= htmlspecialchars($value['name']) ?>
                        <?= $value['hide'] == 'y' ? ' <span class="badge bg-gradient-secondary">隐藏</span>' : '' ?>
                    </span>
                    <div class="dropdown">
                        <span class="badge badge-primary badge-pill dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">操作</span>
                        <ul class="dropdown-menu px-2 py-3" aria-labelledby="dropdownMenuButton">
                            <li><a class="dropdown-item border-radius-md" href="javascript:;" data-bs-toggle="modal" data-id="<?= (int)$value['id']; ?>" data-name="<?= htmlspecialchars($value['name'], ENT_QUOTES) ?>" data-fields="<?= htmlspecialchars(json_encode($fields, JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>" data-typeid="<?= htmlspecialchars($typeKey, ENT_QUOTES) ?>" data-side-slot="<?= htmlspecialchars($slot, ENT_QUOTES) ?>" data-bs-target="#SideModal">编辑</a></li>
                            <?php if ($value['hide'] == 'n'): ?>
                            <li><a class="dropdown-item border-radius-md text-danger" href="<?= SELF ?>?act=menu&hide&id=<?= (int)$value['id']; ?>&<?= $hopeMenuToken ?>">隐藏</a></li>
                            <?php else: ?>
                            <li><a class="dropdown-item border-radius-md text-danger" href="<?= SELF ?>?act=menu&show&id=<?= (int)$value['id']; ?>&<?= $hopeMenuToken ?>">显示</a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item border-radius-md text-danger" href="javascript: hp_delete(<?= (int)$value['id'] ?>, 'menu', '<?= LoginAuth::genToken() ?>');">删除</a></li>
                        </ul>
                    </div>
                </li>
                <?php endforeach; ?>
                <?php if (!$slotHas): ?>
                <li class="list-group-item text-secondary text-sm">该侧边栏组暂无组件，点击「添加」开始配置。</li>
                <?php endif; ?>
            </ul>
            <?php endforeach; ?>
		</div>
      </div>
    </div>
</div>

<div class="modal fade" id="SideModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form method="post" action="<?= SELF ?>?act=menu&upmenu&<?= $hopeMenuToken ?>">
                <div class="modal-header">
                    <h5 class="modal-title">添加组件</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">类型</label>
                        <select class="form-control hope-choices-skip" id="side_type" name="type_id">
                            <option value="0" selected data-title="自定义组件">自定义组件</option>
                            <?php
                            $hope_side_registry_modal = [];
                            foreach ($hope_side_registry as $key => $value):
                                $value['default'] = !empty($value['default']) && is_array($value['default']) ? $value['default'] : [];
                                $hope_side_registry_modal[$key] = [
                                    'title' => (string)($value['title'] ?? $key),
                                    'default' => $value['default'],
                                ];
                            ?>
                                <option value="<?= htmlspecialchars($key, ENT_QUOTES) ?>" data-title="<?= htmlspecialchars($value['title'] ?? '', ENT_QUOTES) ?>"><?= htmlspecialchars($value['title'] ?? $key) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <script>
                            window.HOPE_SIDE_REGISTRY = <?= json_encode($hope_side_registry_modal, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
                            window.HOPE_SIDE_ACTIVE_SLOT = <?= json_encode($hope_side_slot_active, JSON_UNESCAPED_UNICODE) ?>;
                        </script>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">组件名称</label>
                        <input type="text" class="form-control" id="side_name" name="name" required>
                    </div>
                    <div class="mb-3" id="side_custom_content">
                        <label class="form-label">内容</label>
                        <textarea name="fields[content]" class="form-control" rows="6"></textarea>
                    </div>
                    <div id="side_fields_container" class="d-none"></div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="id"/>
                    <input type="hidden" name="side_slot" id="side_slot" value="<?= htmlspecialchars($hope_side_slot_active, ENT_QUOTES) ?>"/>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="MenuModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form method="post" action="<?= SELF ?>?act=menu&upmenu&<?= $hopeMenuToken ?>">
                <div class="modal-header">
                    <h5 class="modal-title">添加菜单</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                            <label class="form-label">菜单名称</label>
                            <!-- 唯一 id，避免与 side 的 name 冲突 -->
                            <input type="text" class="form-control" id="menu_name" name="name" required>
                        </div>
                </div>
                <div class="modal-footer">
                    <!-- 保留 name="id" 提交，避免与其它模态框重复 id -->
                    <input type="hidden" name="id" id="menu_modal_id"/>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="AddMenuItemModal" tabindex="-1" role="dialog"  aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form method="post" action="<?= SELF ?>?act=menu&addmenuitem&<?= $hopeMenuToken ?>">
                <div class="modal-header">
                    <h5 class="modal-title" >添加菜单项</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">上级菜单项</label>
                        <select class="form-control" name="pid" data-hope-choices data-preset="search">
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">类型</label>
                        <select class="form-control" id="menu_type" name="type" data-hope-choices>
                            <option value="0" selected>自定义链接</option>
                            <option value="1">文章</option>
                            <option value="2">页面</option>
                            <option value="3">分类</option>
                            <option value="4">标签</option>
                        </select>
                    </div>
                    <div class="menu-option" id="option_0">
                        <div class="mb-3">
                            <label class="form-label">名称</label>
                            <input type="text" class="form-control" name="name" placeholder="菜单项显示名称">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">链接地址</label>
                            <input type="text" class="form-control" name="url" placeholder="http://">
                        </div>
                    </div>
                    <div class="menu-option d-none" id="option_1">
                        <div class="mb-3">
                            <label class="form-label type-label">选择文章</label>
                            <select class="form-control type-id-select" data-type="1" disabled data-hope-choices data-preset="search">
                                <option value="">请选择</option>
                            </select>
                        </div>
                    </div>
                    <div class="menu-option d-none" id="option_2">
                        <div class="mb-3">
                            <label class="form-label type-label">选择页面</label>
                            <select class="form-control type-id-select" data-type="2" disabled data-hope-choices data-preset="search">
                                <option value="">请选择</option>
                            </select>
                        </div>
                    </div>
                    <div class="menu-option d-none" id="option_3">
                        <div class="mb-2">
                            <label class="form-label type-label mb-1">选择分类</label>
                            <div class="text-xs text-secondary mb-2">一级分类与子分类已分层显示，可直接选择任一层级</div>
                            <select class="form-control type-id-select" data-type="3" disabled data-hope-choices data-preset="search">
                                <option value="">请选择</option>
                            </select>
                        </div>
                        <div class="form-check mt-2 d-none" id="sort_with_children_wrap">
                            <input class="form-check-input" type="checkbox" value="1" id="sort_with_children" name="with_children">
                            <label class="form-check-label" for="sort_with_children">
                                同时添加该分类下的所有子分类
                            </label>
                        </div>
                        <div id="sort_tree_preview" class="border rounded p-2 mt-2 bg-gray-100 d-none" style="max-height: 180px; overflow: auto;"></div>
                    </div>
                    <div class="menu-option d-none" id="option_4">
                        <div class="mb-3">
                            <label class="form-label type-label">选择标签</label>
                            <select class="form-control type-id-select" data-type="4" disabled data-hope-choices data-preset="search">
                                <option value="">请选择</option>
                            </select>
                        </div>
                    </div>

                    <div id="field_box"></div>
                </div>
                <div class="modal-footer">
                    <!-- 保留 name 属性用于后端接收，避免多个模态框有相同 id 导致冲突 -->
                    <input type="hidden" name="id" id="addmenu_modal_id"/>
                    <input type="hidden" name="item_id" id="addmenu_item_id"/>
                    <button type="button" class="btn btn-outline-primary" id="preset_add">添加字段</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">添加</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
	$("#menu_surface_manage").addClass('active');
	$("#menu_surface").addClass('show');
	$("#menu_menu").addClass('active');
    var menu_data = <?= json_encode($menus) ?>;

    // 左侧菜单切换：统一由 JS 控制面板显示，避免 Bootstrap collapse 冲突
    function getMenuHashTarget() {
        var hash = window.location.hash || '';
        if (!hash) {
            var parts = window.location.href.split('#');
            if (parts.length > 1) hash = '#' + parts[1];
        }
        return hash.charAt(0) === '#' ? hash.slice(1) : hash;
    }

    function activateMenuPanel(targetId) {
        if (!targetId) {
            var $first = $('#option .menu-panel-collapse').first();
            if ($first.length) targetId = $first.attr('id');
        }
        var $target = $('#' + targetId);
        if (!$target.length || !$target.hasClass('menu-panel-collapse')) return;

        $('#option .menu-panel-collapse').removeClass('show').attr('aria-expanded', 'false');
        $('#option .menu-nav-link').removeClass('active').attr({ 'aria-selected': 'false', 'aria-expanded': 'false' });

        $target.addClass('show').attr('aria-expanded', 'true');
        var $navLink = $('#option .menu-nav-link[data-menu-target="#' + targetId + '"]');
        $navLink.addClass('active').attr({ 'aria-selected': 'true', 'aria-expanded': 'true' });

        if (typeof hopeUpdateMovingTab === 'function') {
            var navEl = document.querySelector('#option ul.nav-pills[role="tablist"]');
            if (navEl && $navLink.length) {
                hopeUpdateMovingTab(navEl, $navLink[0]);
            }
        } else {
            var $moving = $('#option .moving-tab');
            if (!$moving.length) $moving = $('.moving-tab');
            if ($moving.length && $navLink.length) {
                var top = $navLink.position().top - 3;
                var width = $navLink.outerWidth();
                $moving.css({ 'transition': 'all 0.3s ease', 'transform': 'translate3d(0px,' + top + 'px, 0px)', 'width': width + 'px' });
                var text = $navLink.clone().children().remove().end().text().trim();
                $moving.find('a').text(text);
            }
        }
    }

    function activateMenuByHash() {
        var target = getMenuHashTarget();
        if (target && target.indexOf('side-') === 0) {
            var slot = target.slice(5) || 'sidebar';
            activateMenuPanel('side');
            setActiveSideSlot(slot);
            return;
        }
        if (target === 'side') {
            activateMenuPanel('side');
            setActiveSideSlot(window.HOPE_SIDE_ACTIVE_SLOT || 'sidebar');
            return;
        }
        activateMenuPanel(target);
    }

    $(function() {
        var defaultMenuId = <?= (int)($menu_default_id ?? 0) ?>;
        if (!getMenuHashTarget() && defaultMenuId > 0) {
            activateMenuPanel('menu' + defaultMenuId);
        } else if (!getMenuHashTarget()) {
            activateMenuPanel($('#option .menu-panel-collapse').first().attr('id'));
        } else {
            activateMenuByHash();
        }
        window.addEventListener('hashchange', activateMenuByHash);
    });

    $(document).on('click', '#option .menu-nav-link', function (e) {
        e.preventDefault();
        var target = $(this).attr('data-menu-target') || $(this).attr('href') || '';
        if (!target) return;
        if (target.charAt(0) !== '#') target = '#' + target;
        if (history && history.replaceState) {
            history.replaceState(null, '', window.location.pathname + window.location.search + target);
        } else {
            window.location.hash = target;
        }
        activateMenuPanel(target.slice(1));
    });

    function monitorType() {
        $('.menu-option').addClass('d-none');
        var type = String($('#menu_type').val() || '0');
        $('#option_' + type).removeClass('d-none');

        resetTypeSelects();
        resetSortTreeUi();

        if (type === '0') {
            return;
        }

        var postUrl = '<?= SELF ?>?act=menu&get_type_list';
        var loadingSelect = $('#option_' + type).find('.type-id-select');
        loadingSelect.empty().append('<option value="">加载中...</option>').prop('disabled', true);
        if (typeof hopeRefreshChoices === 'function') {
            hopeRefreshChoices(loadingSelect[0]);
        }

        $.post(postUrl, { type: type }, function (res) {
            try {
                var data = typeof res === 'string' ? JSON.parse(res) : res;
            } catch (e) {
                console.error('返回数据解析失败', res);
                return;
            }
            var select = $('#option_' + type).find('.type-id-select');
            select.empty();
            select.append('<option value="">请选择</option>');

            if (data && data._format === 'sort_tree') {
                fillSortTreeSelect(select, data.items || []);
                return;
            }

            if (!data || Object.keys(data).length === 0) {
                select.append('<option value="">暂无可选项</option>');
                select.prop('disabled', true).removeAttr('name');
                if (typeof hopeRefreshChoices === 'function') {
                    hopeRefreshChoices(select[0]);
                }
                return;
            }

            for (var key in data) {
                if (data.hasOwnProperty(key)) {
                    select.append('<option value="' + key + '">' + data[key] + '</option>');
                }
            }
            select.prop('disabled', false).attr('name', 'typeId');
            if (typeof hopeRefreshChoices === 'function') {
                hopeRefreshChoices(select[0]);
            }
        }).fail(function (xhr) {
            console.error('请求类型列表失败', xhr.responseText);
        });
    }

    $(document).on('change', '#menu_type', function () {
        monitorType();
    });

    function resetSortTreeUi() {
        $('#sort_with_children_wrap').addClass('d-none');
        $('#sort_with_children').prop('checked', false);
        $('#sort_tree_preview').addClass('d-none').empty();
        window._hopeSortTreeMap = {};
    }

    function fillSortTreeSelect($select, items) {
        resetSortTreeUi();
        window._hopeSortTreeMap = {};
        if (!items || !items.length) {
            $select.append('<option value="">暂无分类</option>');
            $select.prop('disabled', true).removeAttr('name');
            if (typeof hopeRefreshChoices === 'function') {
                hopeRefreshChoices($select[0]);
            }
            return;
        }

        items.forEach(function (parent) {
            var pid = String(parent.id);
            var pname = parent.name || '';
            window._hopeSortTreeMap[pid] = parent;
            var $group = $('<optgroup></optgroup>').attr('label', '一级分类 · ' + pname);
            $group.append(
                $('<option></option>')
                    .val(pid)
                    .attr('data-level', '0')
                    .attr('data-has-children', (parent.children && parent.children.length) ? '1' : '0')
                    .text(pname + '（一级分类）')
            );
            if (parent.children && parent.children.length) {
                parent.children.forEach(function (child, idx) {
                    var cid = String(child.id);
                    var cname = child.name || '';
                    var isLast = idx === parent.children.length - 1;
                    var prefix = isLast ? '└ ' : '├ ';
                    $group.append(
                        $('<option></option>')
                            .val(cid)
                            .attr('data-level', '1')
                            .attr('data-parent', pid)
                            .text('　　' + prefix + cname + '（子分类）')
                    );
                });
            }
            $select.append($group);
        });

        $select.prop('disabled', false).attr('name', 'typeId');
        if (typeof hopeRefreshChoices === 'function') {
            hopeRefreshChoices($select[0]);
        }
        renderSortTreePreview(items);
        $select.off('change.hopeSort').on('change.hopeSort', function () {
            updateSortChildrenCheckbox($(this).val());
        });
    }

    function renderSortTreePreview(items) {
        var $box = $('#sort_tree_preview');
        if (!items || !items.length) {
            $box.addClass('d-none').empty();
            return;
        }
        var html = '<div class="text-xs text-secondary mb-1">分类结构预览</div><ul class="mb-0 ps-3" style="list-style:none;">';
        items.forEach(function (parent) {
            html += '<li class="mb-1"><span class="badge bg-gradient-info me-1">一级</span><strong>' + escapeHtml(parent.name || '') + '</strong>';
            if (parent.children && parent.children.length) {
                html += '<ul class="mt-1 mb-0 ps-3" style="list-style:none;">';
                parent.children.forEach(function (child) {
                    html += '<li class="text-sm text-secondary"><span class="badge badge-sm bg-gradient-secondary me-1">子</span>' + escapeHtml(child.name || '') + '</li>';
                });
                html += '</ul>';
            } else {
                html += ' <span class="text-xs text-muted">（无子分类）</span>';
            }
            html += '</li>';
        });
        html += '</ul>';
        $box.html(html).removeClass('d-none');
    }

    function updateSortChildrenCheckbox(selectedId) {
        var $wrap = $('#sort_with_children_wrap');
        var map = window._hopeSortTreeMap || {};
        var item = map[String(selectedId)];
        if (item && item.children && item.children.length) {
            $wrap.removeClass('d-none');
        } else {
            $wrap.addClass('d-none');
            $('#sort_with_children').prop('checked', false);
        }
    }

    // 清理 type-id-select 的公共函数，避免重复代码
    function resetTypeSelects() {
        $('.type-id-select').each(function() {
            if (typeof hopeDestroyChoices === 'function') {
                hopeDestroyChoices(this);
            }
            $(this).removeAttr('name').prop('disabled', true).empty().append('<option value="">请选择</option>');
        });
    }
    // 预设字段管理 modal 相关
    var presetFields = [];
    // 新增预设行
    $('#preset_add').on('click', function(){
        var newField = `
            <div class="row g-2 mb-2 preset-row">
                <div class="col-4">
                    <input class="form-control" name="field_key[]" placeholder="字段 key">
                </div>
                <div class="col-7">
                    <input class="form-control" name="field_value[]" placeholder="内容 value">
                </div>
                <div class="col-1 d-flex align-items-center">
                    <button type="button" class="btn btn-xs btn-outline-danger preset-row-del"><i class="fa fa-trash"></i></button>
                </div>
            </div>
                `;
        $('#field_box').append(newField);
    });
    // 删除预设行（事件委托）
    $(document).on('click', '.preset-row-del', function() {
        $(this).closest('.preset-row').remove();
    });

    $('#MenuModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget)
        var id = button.data('id')
        var name = button.data('name')
        var modal = $(this)

        // 使用 name 选择器或 modal 内部 id，避免跨模态冲突
        modal.find('input[name="id"]').val(id)
        modal.find('#menu_name').val(name)
        modal.find('.modal-title').text(name ? '编辑菜单' : '添加菜单');

        var pidSelect = modal.find('select[name="pid"]');
        pidSelect.empty();
        pidSelect.append('<option value="0">顶级菜单</option>');
        
        if (menu_data[id] && menu_data[id].child && menu_data[id].child.length > 0) {
            $.each(menu_data[id].child, function(childIndex, child) {
                pidSelect.append('<option value="' + child.id + '">' + child.name + '</option>');
            });
        }

    })
    
    // 分类编辑
    $('#AddMenuItemModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget)
        var id = button.data('id')
        var pid = button.data('pid')
        var itemId = button.data('item-id')
        var name = button.data('name')
        var url = button.data('url')
        var target = button.data('target')
        var type = button.data('type')
        var typeId = button.data('typeid')
        var fields = button.data('fields')

        var modal = $(this)
        // 通过 name 字段设置 id，避免依赖全局 id
        modal.find('input[name="id"]').val(id)
        // 清除 item_id 隐藏域（默认新增模式）
        modal.find('input[name="item_id"]').val('')
        var pidSelect = modal.find('select[name="pid"]');
        pidSelect.empty();
        pidSelect.append('<option value="0">顶级菜单</option>');

        // 递归函数：获取所有子菜单项
        function getAllChildren(menuItem, prefix) {
            var children = [];
            if (menuItem.child && menuItem.child.length > 0) {
                $.each(menuItem.child, function(index, child) {
                    children.push({
                        id: child.id,
                        name: prefix + child.name
                    });
                    
                    // 递归获取更深层的子菜单
                    var grandChildren = getAllChildren(child, prefix + '&nbsp;&nbsp;&nbsp;&nbsp;');
                    children = children.concat(grandChildren);
                });
            }
            return children;
        }
        
        // 获取并添加所有层级的子菜单项
        if (menu_data[id]) {
            var allChildren = getAllChildren(menu_data[id], '');
            $.each(allChildren, function(index, child) {
                pidSelect.append('<option value="' + child.id + '">' + child.name + '</option>');
            });
        }


        // 设置默认选中项：如果有 pid 且不为空，则选中对应项；否则默认选中“顶级菜单”
        if (typeof pid !== 'undefined' && pid !== '') {
            pidSelect.val(pid);
        } else {
            pidSelect.val('0'); // 默认选中顶级菜单
        }

        // 如果触发者包含 item-id，则进入编辑模式
        var form = modal.find('form');
        if (typeof itemId !== 'undefined' && itemId) {
            // 填充表单字段
            modal.find('.modal-title').text('编辑菜单项');
            modal.find('.modal-body input[name="name"]').val(name || '');
            modal.find('.modal-body input[name="url"]').val(url || '');
            modal.find('.modal-body select[name="target"]').val(target || 'n');
            // 设置类型并触发监测以显示对应选择器
            if (typeof type !== 'undefined') {
                $('#menu_type').val(type);
                monitorType();
                // 填充 typeId 到选择器（如果存在），在 AJAX 填充完成后设置选中
                if (type && typeId) {
                    setTimeout(function() {
                        var selEl = $('.type-id-select[data-type="' + type + '"]')[0];
                        if (!selEl) return;
                        $(selEl).val(typeId).prop('disabled', false).attr('name', 'typeId');
                        if (typeof hopeRefreshChoices === 'function') {
                            hopeRefreshChoices(selEl);
                        }
                        if (selEl._hopeChoices && typeof selEl._hopeChoices.setChoiceByValue === 'function') {
                            selEl._hopeChoices.setChoiceByValue(String(typeId));
                        }
                        if (String(type) === '3') {
                            updateSortChildrenCheckbox(typeId);
                            $('#sort_with_children_wrap').addClass('d-none');
                        }
                    }, 500);
                }
            }
            // 编辑时不显示「同时添加子分类」
            $('#sort_with_children_wrap').addClass('d-none');
            // 设置隐藏的 item_id
            modal.find('input[name="item_id"]').val(itemId);
            // 修改 form action 为更新
            form.attr('action', '<?= SELF ?>?act=menu&upmenuitem&<?= $hopeMenuToken ?>');
            // 改变提交按钮文本
            form.find('button[type="submit"]').text('保存');
            var container = $('#field_box');
            Object.keys(fields).forEach(function (key) {
                var val = fields[key];
                var newField = `
                    <div class="row g-2 mb-2 preset-row">
                        <div class="col-4">
                            <input class="form-control" name="field_key[]" value="${escapeHtml(key)}" placeholder="字段 key">
                        </div>
                        <div class="col-7">
                            <input class="form-control" name="field_value[]" value="${escapeHtml(val)}" placeholder="内容 value">
                        </div>
                        <div class="col-1 d-flex align-items-center">
                            <button type="button" class="btn btn-xs btn-outline-danger preset-row-del"><i class="fa fa-trash"></i></button>
                        </div>
                    </div>
                `;
                container.append(newField);
            });
        } else {
            // 新增模式：重置标题与按钮，并清空字段
            modal.find('.modal-title').text('添加菜单项');
            modal.find('.modal-body input[name="name"]').val('');
            modal.find('.modal-body input[name="url"]').val('');
            modal.find('.modal-body select[name="target"]' ).val('n');
            $('#menu_type').val('0');
            monitorType();
            modal.find('input[name="item_id"]').val('');
            form.attr('action', '<?= SELF ?>?act=menu&addmenuitem&<?= $hopeMenuToken ?>');
            form.find('button[type="submit"]').text('添加');
        }

    })

    // 在模态关闭时清理所有 typeId 名称，避免表单提交时重复字段
    $('#AddMenuItemModal').on('hidden.bs.modal', function () {
        resetTypeSelects();
        resetSortTreeUi();
        // 清除 field_box 内所有行
        $('#field_box').html('');
        // 恢复到默认类型（自定义链接）
        $('#menu_type').val('0');
        $('.menu-option').addClass('d-none');
        $('#option_0').removeClass('d-none');
    });

    // ---------- 拖拽排序功能 (Drag & Drop) ----------
    (function() {
        // helper: 找到 y 坐标下最近的非拖动元素
        function getDragAfterElement(container, y) {
            var draggableElements = Array.from(container.querySelectorAll('.list-group-item[data-id]:not(.dragging)'));
            var closest = { offset: Number.NEGATIVE_INFINITY, element: null };
            draggableElements.forEach(function(child) {
                var box = child.getBoundingClientRect();
                var offset = y - box.top - box.height / 2;
                if (offset < 0 && offset > closest.offset) {
                    closest = { offset: offset, element: child };
                }
            });
            return closest.element;
        }

        // 绑定拖拽事件到所有可拖项（动态添加项需要重新绑定，页面加载时先绑定一次）
        function bindDragHandlers() {
            $('.list-group-item[data-id]').each(function() {
                var el = this;
                el.draggable = true;
                el.addEventListener('dragstart', function(e) {
                    el.classList.add('dragging');
                    try { e.dataTransfer.effectAllowed = 'move'; } catch (err) {}
                    try { e.dataTransfer.setData('text/plain', el.getAttribute('data-id')); } catch (err) {}
                });
                el.addEventListener('dragend', function() {
                    el.classList.remove('dragging');
                });
            });
        }

        // 绑定每个 list 容器的 dragover/drop 行为
        function bindListContainers() {
            document.querySelectorAll('ul.list-group').forEach(function(list) {
                list.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    var afterElement = getDragAfterElement(list, e.clientY);
                    var dragging = document.querySelector('.dragging');
                    if (!dragging) return;
                    if (afterElement == null) {
                        list.appendChild(dragging);
                    } else {
                        list.insertBefore(dragging, afterElement);
                    }
                });
            });
        }

        // 触发保存排序：收集每个 ul 中的顺序，并推断父 id（若嵌套则为上层菜单项 id，否则为 0）
        function collectOrderForPanel(panelId) {
            var items = [];
            var $panel = $('#menu' + panelId);
            if (!$panel.length) return items;

            $panel.find('ul.list-group').each(function() {
                var $ul = $(this);
                // 推断该 ul 的父 id：如果 ul 被一个 li 包裹（wrapper），则 wrapper 的前一个有 data-id 的 li 为父项
                var pid = 0;
                var $parentWrapper = $ul.parent('li');
                if ($parentWrapper.length) {
                    var $prev = $parentWrapper.prev('li[data-id]');
                    if ($prev.length) {
                        pid = parseInt($prev.attr('data-id')) || 0;
                    }
                }
                if (!pid) pid = panelId;

                var order = 0;
                $ul.children('li[data-id]').each(function() {
                    var id = parseInt($(this).attr('data-id')) || 0;
                    items.push({ id: id, pid: pid, taxis: order });
                    order++;
                });
            });

            return items;
        }

        // 保存排序按钮处理
        $(document).on('click', '.save-order-btn', function() {
            var menuId = $(this).data('id');
            var items = collectOrderForPanel(menuId);
            if (!items || items.length === 0) {
                alert('没有可保存的排序');
                return;
            }

            var postUrl = '<?= SELF ?>?act=menu&menu_taxis&<?= $hopeMenuToken ?>';
            var $btn = $(this);
            $btn.prop('disabled', true).text('保存中...');

            $.post(postUrl, { items: JSON.stringify(items) }, function(res) {
                try {
                    var data = typeof res === 'string' ? JSON.parse(res) : res;
                } catch (e) {
                    alert('返回数据解析失败');
                    $btn.prop('disabled', false).text('保存排序');
                    return;
                }
                if (data && hopeApiSuccess(data.code)) {
                    // 成功后刷新页面以读取最新顺序缓存
                    location.reload();
                } else {
                    alert(data.msg || '保存失败');
                    $btn.prop('disabled', false).text('保存排序');
                }
            }).fail(function(xhr) {
                alert('保存失败：' + xhr.responseText);
                $btn.prop('disabled', false).text('保存排序');
            });
        });

        // 保存侧边栏排序处理（仅当前侧边栏组）
        $(document).on('click', '.save-side-order-btn', function() {
            var $btn = $(this);
            var items = [];

            $('#side ul.hope-side-slot-list:not(.d-none) > li[data-id]').each(function(idx) {
                var id = parseInt($(this).attr('data-id')) || 0;
                items.push({ id: id, pid: 0, taxis: idx });
            });

            if (!items || items.length === 0) {
                alert('没有可保存的排序');
                return;
            }

            var postUrl = '<?= SELF ?>?act=menu&menu_taxis&<?= $hopeMenuToken ?>';
            $btn.prop('disabled', true).text('保存中...');

            $.post(postUrl, { items: JSON.stringify(items) }, function(res) {
                try {
                    var data = typeof res === 'string' ? JSON.parse(res) : res;
                } catch (e) {
                    alert('返回数据解析失败');
                    $btn.prop('disabled', false).text('保存排序');
                    return;
                }
                if (data && hopeApiSuccess(data.code)) {
                    location.reload();
                } else {
                    alert(data.msg || '保存失败');
                    $btn.prop('disabled', false).text('保存排序');
                }
            }).fail(function(xhr) {
                alert('保存失败：' + xhr.responseText);
                $btn.prop('disabled', false).text('保存排序');
            });
        });

        // 初始绑定
        bindDragHandlers();
        bindListContainers();
    })();

    // ---------- Side 模态：根据 side_type 切换自定义或模板字段 ----------
    function escapeHtml(unsafe) {
        if (unsafe === null || typeof unsafe === 'undefined') return '';
        return String(unsafe).replace(/[&<>"'`]/g, function (s) {
            return ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
                '`': '&#96;'
            })[s];
        });
    }

    function renderSideFields(fields) {
        var container = $('#side_fields_container');
        container.empty();
        if (!fields || (typeof fields !== 'object')) {
            container.append('<div class="mb-3"><div class="text-muted">该组件无需额外配置</div></div>');
            return;
        }

        if (Array.isArray(fields)) {
            if (!fields.length) {
                container.append('<div class="mb-3"><div class="text-muted">该组件无需额外配置</div></div>');
                return;
            }
            fields.forEach(function (f, idx) {
                var label = f.label || f.name || ('字段 ' + (idx + 1));
                var key = f.name || ('field_' + idx);
                var def = (typeof f.default !== 'undefined') ? f.default : '';
                container.append('<div class="mb-3"><label class="form-label">' + escapeHtml(label) + '</label><input class="form-control side-field-input" name="fields[' + escapeHtml(key) + ']" value="' + escapeHtml(def) + '"></div>');
            });
            return;
        }

        var keys = Object.keys(fields);
        if (!keys.length) {
            container.append('<div class="mb-3"><div class="text-muted">该组件无需额外配置</div></div>');
            return;
        }
        keys.forEach(function (key) {
            var val = fields[key];
            if (val !== null && typeof val === 'object') {
                var label = val.label || key;
                var def = typeof val.default !== 'undefined' ? val.default : JSON.stringify(val);
                container.append('<div class="mb-3"><label class="form-label">' + escapeHtml(label) + '</label><input class="form-control side-field-input" name="fields[' + escapeHtml(key) + ']" value="' + escapeHtml(def) + '"></div>');
            } else {
                container.append('<div class="mb-3"><label class="form-label">' + escapeHtml(key) + '</label><input class="form-control side-field-input" name="fields[' + escapeHtml(key) + ']" value="' + escapeHtml(val) + '"></div>');
            }
        });
    }

    function getSideRegistryItem(type) {
        var registry = window.HOPE_SIDE_REGISTRY || {};
        if (!type || type === '0') {
            return null;
        }
        return registry[type] || null;
    }

    function monitorSideType(overrideFields, keepName) {
        var sel = $('#side_type option:selected');
        var type = String($('#side_type').val() || '0');
        var item = getSideRegistryItem(type);
        var currentName = keepName ? $('#side_name').val() : '';

        if (type === '0') {
            $('#side_fields_container').addClass('d-none').empty();
            $('#side_custom_content').removeClass('d-none');
            if (!keepName || !currentName) {
                $('#side_name').val(sel.attr('data-title') || sel.data('title') || '自定义组件');
            }
            return;
        }

        var title = (item && item.title) || sel.attr('data-title') || sel.data('title') || '';
        var fields = (typeof overrideFields !== 'undefined' && overrideFields !== null)
            ? overrideFields
            : ((item && item.default) ? item.default : {});
        $('#side_custom_content').addClass('d-none');
        $('#side_fields_container').removeClass('d-none');
        renderSideFields(fields);
        if (keepName && currentName) {
            $('#side_name').val(currentName);
        } else if (title) {
            $('#side_name').val(title);
        }
    }

    function setActiveSideSlot(slot) {
        slot = String(slot || 'sidebar');
        window.HOPE_SIDE_ACTIVE_SLOT = slot;
        $('#side_slot').val(slot);
        $('#hope-side-slot-tabs .hope-side-slot-tab').removeClass('active');
        $('#hope-side-slot-tabs .hope-side-slot-tab[data-side-slot="' + slot + '"]').addClass('active');
        $('#side ul.hope-side-slot-list').addClass('d-none');
        $('#side ul.hope-side-slot-list[data-side-slot="' + slot + '"]').removeClass('d-none');
        if (history && history.replaceState) {
            history.replaceState(null, '', window.location.pathname + window.location.search + '#side-' + slot);
        }
    }

    $(document).on('click', '.hope-side-slot-tab', function (e) {
        e.preventDefault();
        setActiveSideSlot($(this).attr('data-side-slot') || 'sidebar');
        activateMenuPanel('side');
    });

    $(document).on('change', '#side_type', function () {
        monitorSideType();
    });

    $('#SideModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var id = button.data('id');
        var name = button.data('name');
        var fieldsAttr = button.attr('data-fields') || button.data('fields');
        var typeId = button.attr('data-typeid') || button.data('typeid') || button.attr('data-type') || button.data('type');
        var sideSlot = button.attr('data-side-slot') || button.data('side-slot') || window.HOPE_SIDE_ACTIVE_SLOT || 'sidebar';
        var modal = $(this);
        var isEdit = typeof id !== 'undefined' && id !== null && id !== '';

        modal.find('.modal-title').text(isEdit ? '编辑组件' : '添加组件');
        modal.find('input[name="id"]').val(id || '');
        $('#side_slot').val(String(sideSlot));
        $('#side_fields_container').empty().addClass('d-none');
        $('#side_custom_content').removeClass('d-none');
        $('#side_custom_content textarea[name="fields[content]"]').val('');

        var parsed = null;
        if (fieldsAttr) {
            try {
                parsed = (typeof fieldsAttr === 'string') ? JSON.parse(fieldsAttr) : fieldsAttr;
            } catch (e) {
                try {
                    var decoded = String(fieldsAttr).replace(/&quot;/g, '"').replace(/&amp;/g, '&').replace(/&lt;/g, '<').replace(/&gt;/g, '>');
                    parsed = JSON.parse(decoded);
                } catch (e2) {
                    console.error('SideModal: 无法解析 data-fields', fieldsAttr, e2);
                }
            }
        }

        if (typeId && $('#side_type option[value="' + typeId + '"]').length) {
            $('#side_type').val(String(typeId));
        } else if (!isEdit) {
            $('#side_type').val('0');
        }

        if (typeof name !== 'undefined' && name !== null && name !== '') {
            $('#side_name').val(name);
        }

        var type = String($('#side_type').val() || '0');
        if (type === '0' && parsed && parsed.hasOwnProperty('content')) {
            $('#side_custom_content').removeClass('d-none');
            $('#side_fields_container').empty().addClass('d-none');
            $('#side_custom_content textarea[name="fields[content]"]').val(parsed.content || '');
            if (!name) {
                $('#side_name').val('自定义组件');
            }
        } else if (type !== '0') {
            monitorSideType(parsed && typeof parsed === 'object' ? parsed : null, !!name);
        } else {
            monitorSideType(null, !!name);
        }
    });

    $('#SideModal').on('hidden.bs.modal', function () {
        // 关闭时恢复到默认状态：移除动态字段并显示自定义内容
        $('#side_fields_container').empty().addClass('d-none');
        $('#side_custom_content').removeClass('d-none');
    });
</script>
<style>
    /* 保证菜单右侧 dropdown 显示在最上层 */
    #option .dropdown-menu {
        z-index: 3000 !important;
    }
</style>
