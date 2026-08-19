<?php
/**
 * Hope CMS 侧边栏：注册表、多组槽位、组件渲染
 *
 * 约定：有侧边栏的主题将容器与配置放在 widgets/ 下；
 * 无侧边栏的主题可不创建 widgets/。
 * 容器命名：sidebar.php / sidebar1.php / sidebar2.php…
 */
defined('HOPE_ROOT') || exit('access denied!');

if (!function_exists('hope_theme_widgets_path')) {
	/**
	 * 主题 widgets 目录（末尾带 /）；目录不存在时返回空字符串
	 */
	function hope_theme_widgets_path() {
		if (!defined('THEME_PATH')) {
			return '';
		}
		$dir = THEME_PATH . 'widgets/';
		return is_dir($dir) ? $dir : '';
	}
}

if (!function_exists('hope_theme_side_file')) {
	/**
	 * 解析侧边栏相关文件路径（优先 widgets/，其次主题根目录）
	 * @param string $name 如 sidebar.php / sidebar1.php / side_config.php / side_blogger.php
	 * @return string 绝对路径；不存在则为空
	 */
	function hope_theme_side_file($name) {
		$name = ltrim((string)$name, '/\\');
		if ($name === '' || !defined('THEME_PATH')) {
			return '';
		}
		$widgets = hope_theme_widgets_path();
		if ($widgets !== '' && is_file($widgets . $name)) {
			return $widgets . $name;
		}
		$root = THEME_PATH . $name;
		return is_file($root) ? $root : '';
	}
}

if (!function_exists('hope_theme_side_normalize_slot')) {
	/**
	 * 规范化侧边栏组名：仅使用 sidebar / sidebar1 / sidebar2 …
	 * 读取时将历史 side / sideN 映射到 sidebar / sidebarN
	 */
	function hope_theme_side_normalize_slot($slot) {
		$slot = trim((string)$slot);
		if ($slot === '' || $slot === 'side' || $slot === 'sidebar') {
			return 'sidebar';
		}
		if (preg_match('/^side(\d+)$/', $slot, $m)) {
			return 'sidebar' . $m[1];
		}
		if ($slot === 'sidebar' || preg_match('/^sidebar\d+$/', $slot)) {
			return $slot;
		}
		return 'sidebar';
	}
}

if (!function_exists('hope_theme_side_slots')) {
	/**
	 * 扫描主题侧边栏容器：widgets/sidebar.php、widgets/sidebarN.php（亦扫描主题根目录）
	 * 无侧边栏文件时返回空数组（主题可不提供 widgets/）
	 * @return string[]
	 */
	function hope_theme_side_slots() {
		static $slots = null;
		if ($slots !== null) {
			return $slots;
		}
		$slots = [];
		if (!defined('THEME_PATH') || !is_dir(THEME_PATH)) {
			return $slots;
		}

		$scanDirs = [];
		$widgets = hope_theme_widgets_path();
		if ($widgets !== '') {
			$scanDirs[] = $widgets;
		}
		$scanDirs[] = THEME_PATH;

		$found = [];
		foreach ($scanDirs as $dir) {
			if (is_file($dir . 'sidebar.php')) {
				$found['sidebar'] = true;
			}
			$files = @scandir($dir);
			if (!is_array($files)) {
				continue;
			}
			foreach ($files as $file) {
				// 仅容器：sidebar1.php / sidebar2.php…（不含 side_blogger.php 等组件）
				if (preg_match('/^sidebar\d+\.php$/', $file)) {
					$found[substr($file, 0, -4)] = true;
				}
			}
		}

		$slots = array_keys($found);
		usort($slots, function ($a, $b) {
			if ($a === 'sidebar') {
				return -1;
			}
			if ($b === 'sidebar') {
				return 1;
			}
			return strnatcmp($a, $b);
		});
		return $slots;
	}
}

if (!function_exists('hope_theme_side_slot_label')) {
	function hope_theme_side_slot_label($slot) {
		$slot = hope_theme_side_normalize_slot($slot);
		if ($slot === 'sidebar') {
			return '默认侧边栏';
		}
		if (preg_match('/^sidebar(\d+)$/', $slot, $m)) {
			return '侧边栏 ' . $m[1];
		}
		return $slot;
	}
}

if (!function_exists('hope_theme_side_resolve_item_slot')) {
	/**
	 * 菜单项归属的侧边栏组（url 为空 / side / 主题名 → sidebar）
	 */
	function hope_theme_side_resolve_item_slot($item) {
		$url = trim((string)($item['url'] ?? ''));
		if ($url === '' || $url === 'side' || $url === 'sidebar') {
			return 'sidebar';
		}
		if (preg_match('/^side(\d+)$/', $url, $m)) {
			return 'sidebar' . $m[1];
		}
		if (preg_match('/^sidebar\d+$/', $url)) {
			return $url;
		}
		$theme = defined('THEME_PATH') ? Option::get('nonce_theme') : '';
		if ($theme !== '' && $url === $theme) {
			return 'sidebar';
		}
		$slots = hope_theme_side_slots();
		if (in_array($url, $slots, true)) {
			return $url;
		}
		return 'sidebar';
	}
}

if (!function_exists('hope_theme_side_registry')) {
	/**
	 * 读取主题侧边栏组件注册表（优先 widgets/side_config.php）
	 * 注册变量统一为 $sidebar；仍可读历史 $side
	 */
	function hope_theme_side_registry() {
		static $registry = null;
		if ($registry !== null) {
			return $registry;
		}
		$registry = [];
		if (!defined('THEME_PATH')) {
			return $registry;
		}
		$sidebar = null;
		$config = hope_theme_side_file('side_config.php');
		if ($config !== '') {
			include $config;
		} elseif (!empty($GLOBALS['sidebar']) && is_array($GLOBALS['sidebar'])) {
			$sidebar = $GLOBALS['sidebar'];
		} elseif (!empty($GLOBALS['side']) && is_array($GLOBALS['side'])) {
			$sidebar = $GLOBALS['side'];
		} elseif (is_file(THEME_PATH . 'module.php')) {
			include_once THEME_PATH . 'module.php';
			if (!isset($sidebar) && !empty($GLOBALS['sidebar']) && is_array($GLOBALS['sidebar'])) {
				$sidebar = $GLOBALS['sidebar'];
			} elseif (!isset($sidebar) && !empty($GLOBALS['side']) && is_array($GLOBALS['side'])) {
				$sidebar = $GLOBALS['side'];
			}
		}
		if (isset($sidebar) && is_array($sidebar)) {
			$registry = $sidebar;
		} elseif (isset($side) && is_array($side)) {
			$registry = $side;
		}
		return $registry;
	}
}

if (!function_exists('hope_widget_field')) {
	function hope_widget_field($fields, $key, $default = '') {
		if (!is_array($fields) || !isset($fields[$key])) {
			return $default;
		}
		$value = trim((string)$fields[$key]);
		return $value === '' ? $default : $value;
	}
}

if (!function_exists('hope_menu_cache')) {
	function hope_menu_cache() {
		global $CACHE;
		$menu = $CACHE->readCache('menu');
		return is_array($menu) ? $menu : [];
	}
}

if (!function_exists('hope_menu_main_nav_items')) {
	function hope_menu_main_nav_items() {
		$Menu_Model = new Menu_Model();
		return $Menu_Model->getMenus(hope_menu_cache(), 'lord');
	}
}

if (!function_exists('hope_menu_sidebar_widgets')) {
	/**
	 * @param string $slot sidebar / sidebar1 …
	 */
	function hope_menu_sidebar_widgets($slot = 'sidebar') {
		$slot = hope_theme_side_normalize_slot($slot);
		$Menu_Model = new Menu_Model();
		$widgets = $Menu_Model->getMenus(hope_menu_cache(), 'sidebar');
		if (!is_array($widgets) || empty($widgets)) {
			return [];
		}
		$filtered = [];
		foreach ($widgets as $widget) {
			if (hope_theme_side_resolve_item_slot($widget) === $slot) {
				$filtered[] = $widget;
			}
		}
		return $filtered;
	}
}

if (!function_exists('hope_render_sidebar_widget')) {
	function hope_render_sidebar_widget($widget) {
		if (($widget['hide'] ?? 'n') === 'y') {
			return;
		}
		$fields = @unserialize($widget['fields'] ?? '');
		if (!is_array($fields)) {
			$fields = [];
		}
		$widget_key = trim((string)($widget['typeId'] ?? ''));
		$widget_title = trim((string)($widget['name'] ?? ''));
		$widget_config = $widget_key !== '' ? (hope_theme_side_registry()[$widget_key] ?? []) : [];
		if ($widget_title === '' && !empty($widget_config['title'])) {
			$widget_title = $widget_config['title'];
		}

		if ($widget_key !== '' && $widget_key !== '0') {
			$widget_file = hope_theme_side_file($widget_key . '.php');
			if ($widget_file !== '') {
				include $widget_file;
				return;
			}
		}

		$content = hope_widget_field($fields, 'content');
		if ($content === '') {
			return;
		}
		$custom = hope_theme_side_file('side_custom.php');
		if ($custom !== '') {
			include $custom;
			return;
		}
		echo '<div class="widget widget-custom">';
		echo '<h3 class="widget-title">' . htmlspecialchars($widget_title ?: '组件') . '</h3>';
		echo '<div class="widget-body">' . $content . '</div>';
		echo '</div>';
	}
}

if (!function_exists('hope_render_sidebar_widgets')) {
	/**
	 * @param string $slot
	 * @return bool 有配置并已输出时 true；无配置 false（便于主题兜底）
	 */
	function hope_render_sidebar_widgets($slot = 'sidebar') {
		$widgets = hope_menu_sidebar_widgets($slot);
		if (empty($widgets)) {
			return false;
		}
		foreach ($widgets as $widget) {
			hope_render_sidebar_widget($widget);
		}
		return true;
	}
}

if (!function_exists('hope_include_sidebar_widget')) {
	/**
	 * 兜底：按组件键直接 include 一个 widget（构造最小上下文）
	 */
	function hope_include_sidebar_widget($widget_key, $title = '', $fields = []) {
		$widget_key = trim((string)$widget_key);
		if ($widget_key === '') {
			return;
		}
		$widget_file = hope_theme_side_file($widget_key . '.php');
		if ($widget_file === '') {
			return;
		}
		$widget_config = hope_theme_side_registry()[$widget_key] ?? [];
		$widget_title = $title !== '' ? $title : (string)($widget_config['title'] ?? $widget_key);
		if (!is_array($fields)) {
			$fields = [];
		}
		include $widget_file;
	}
}
