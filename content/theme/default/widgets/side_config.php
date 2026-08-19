<?php
/**
 * 侧边栏组件注册（供后台「菜单管理 → 侧边栏」读取）
 * 键名须与同目录组件文件名一致（不含 .php）；default 字段名与组件内 hope_widget_field 一致
 */
defined('HOPE_ROOT') || exit('access denied!');

$sidebar = [];
$widgetDir = __DIR__ . DIRECTORY_SEPARATOR;

$defs = [
	'side_search' => [
		'title'   => '搜索',
		'default' => ['输入提示' => '搜索文章、标签…'],
	],
	'side_blogger' => [
		'title'   => '博主信息',
		'default' => [
			'QQ号码'   => '',
			'微信二维码' => '',
			'B站链接'  => '',
			'抖音二维码' => '',
			'快手二维码' => '',
		],
	],
	'side_calendar' => [
		'title' => '日历',
	],
	'side_tag' => [
		'title'   => '文章标签',
		'default' => ['显示数量' => '18'],
	],
	'side_newlog' => [
		'title'   => '最新文章',
		'default' => ['显示数量' => '6'],
	],
	'side_hotlog' => [
		'title'   => '热门文章',
		'default' => ['显示数量' => '5'],
	],
	'side_newcomm' => [
		'title'   => '最新评论',
		'default' => ['显示数量' => '8'],
	],
	'side_link' => [
		'title'   => '友情链接',
		'default' => ['显示数量' => '10'],
	],
	'side_twitter' => [
		'title'   => '碎言碎语',
		'default' => ['显示数量' => '5'],
	],
];

foreach ($defs as $key => $meta) {
	if (is_file($widgetDir . $key . '.php')) {
		$sidebar[$key] = $meta;
	}
}
