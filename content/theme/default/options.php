<?php
if (!defined('HOPE_ROOT')) exit;

$prefix = 'default_options';

CSF::createOptions($prefix, [
    'plugin_title' => '默认主题设置',
    'footer_text'  => 'Hope CMS · Default',
    'theme'        => 'light',
]);

CSF::createSection($prefix, [
    'title'  => '基本设置',
    'icon'   => 'fa fa-cog',
    'fields' => [
        [
            'id'      => 'site_brand',
            'type'    => 'text',
            'title'   => '站点品牌名',
            'default' => 'Hope CMS',
            'desc'    => '导航与页脚展示的品牌名称，留空则使用系统站点名',
        ],
        [
            'id'      => 'site_tagline',
            'type'    => 'text',
            'title'   => '站点标语',
            'default' => '轻量优雅的内容管理系统',
        ],
        [
            'id'      => 'nav_sort_limit',
            'type'    => 'number',
            'title'   => '导航分类数量',
            'default' => 8,
        ],
        [
            'id'      => 'footer_text',
            'type'    => 'textarea',
            'title'   => '页脚简介',
            'default' => 'Hope CMS 是一款轻量、优雅的内容管理系统，帮助你快速搭建博客、文档与内容站点。',
        ],
        [
            'id'      => 'footer_links',
            'type'    => 'textarea',
            'title'   => '快速链接',
            'default' => "首页|/\n最新文章|/\n全站搜索|/index.php?keyword=",
            'desc'    => '每行一条：标题|链接',
        ],
        [
            'id'      => 'footer_dev_links',
            'type'    => 'textarea',
            'title'   => '开发者资源链接',
            'default' => "官方网站|http://www.hopecms.cn\n使用文档|http://www.hopecms.cn/docs.html\n问题反馈|http://www.hopecms.cn/forum.html",
            'desc'    => '每行一条：标题|链接',
        ],
    ],
]);

CSF::createSection($prefix, [
    'title'  => '外观布局',
    'icon'   => 'fa fa-paint-brush',
    'fields' => [
        [
            'id'      => 'color_scheme',
            'type'    => 'select',
            'title'   => '默认配色',
            'options' => [
                'auto'  => '跟随系统',
                'light' => '浅色模式',
                'dark'  => '深色模式',
            ],
            'default' => 'light',
        ],
        [
            'id'      => 'primary_color',
            'type'    => 'color',
            'title'   => '主题色',
            'default' => '#3b82f6',
        ],
        [
            'id'      => 'site_layout',
            'type'    => 'select',
            'title'   => '页面布局',
            'options' => [
                'single' => '单栏（纯内容）',
                'double' => '双栏（内容 + 侧边栏）',
                'triple' => '三栏（宽屏多栏）',
            ],
            'default' => 'double',
        ],
        [
            'id'      => 'list_mode',
            'type'    => 'select',
            'title'   => '列表样式',
            'options' => [
                'standard' => '图文模式',
                'card'     => '卡片模式',
                'grid'     => '网格多图',
                'seamless' => '无缝列表',
            ],
            'default' => 'card',
        ],
    ],
]);

CSF::createSection($prefix, [
    'title'  => '首页 Hero',
    'icon'   => 'fa fa-image',
    'fields' => [
        [
            'id'      => 'hero_enable',
            'type'    => 'switcher',
            'title'   => '启用首页 Hero',
            'default' => true,
        ],
        [
            'id'      => 'hero_title',
            'type'    => 'text',
            'title'   => '主标题',
            'default' => '轻量 优雅 强大',
            'desc'    => '用空格分成三段时，中间词会高亮，如：轻量 优雅 强大',
        ],
        [
            'id'      => 'hero_desc',
            'type'    => 'textarea',
            'title'   => '副文案',
            'default' => 'Hope CMS 助你快速搭建博客与内容站点，专注写作、发布与运营。',
        ],
        [
            'id'      => 'hero_cta1_text',
            'type'    => 'text',
            'title'   => '主按钮文字',
            'default' => '开始阅读',
        ],
        [
            'id'      => 'hero_cta1_url',
            'type'    => 'text',
            'title'   => '主按钮链接',
            'default' => '#latest',
        ],
        [
            'id'      => 'hero_cta2_text',
            'type'    => 'text',
            'title'   => '次按钮文字',
            'default' => '立即体验',
        ],
        [
            'id'      => 'hero_cta2_url',
            'type'    => 'text',
            'title'   => '次按钮链接',
            'default' => '',
            'desc'    => '留空则跳转注册页',
        ],
        [
            'id'      => 'hero_bg',
            'type'    => 'text',
            'title'   => '背景图 URL（可选）',
            'default' => '',
            'desc'    => '留空使用渐变氛围背景',
        ],
        // 隐藏保留字段（历史配置迁移）
        [
            'id'      => 'hero_slides',
            'type'    => 'textarea',
            'title'   => '幻灯片数据',
            'default' => '',
            'class'   => 'hidden',
        ],
        [
            'id'      => 'hero_autoplay',
            'type'    => 'switcher',
            'title'   => '自动轮播',
            'default' => false,
            'class'   => 'hidden',
        ],
    ],
]);

CSF::createSection($prefix, [
    'title'  => '侧边栏',
    'icon'   => 'fa fa-columns',
    'fields' => [
        [
            'id'      => 'sidebar_about',
            'type'    => 'textarea',
            'title'   => '关于简介',
            'default' => 'Hope CMS — 轻量优雅的内容管理系统，助力内容创作与站点运营。',
        ],
        [
            'id'      => 'sidebar_recent_num',
            'type'    => 'number',
            'title'   => '推荐阅读数量',
            'default' => 6,
        ],
        [
            'id'      => 'sidebar_tag_num',
            'type'    => 'number',
            'title'   => '标签数量',
            'default' => 18,
        ],
    ],
]);

CSF::createSection($prefix, [
    'title'  => 'SEO 与性能',
    'icon'   => 'fa fa-rocket',
    'fields' => [
        [
            'id'      => 'cdn_base',
            'type'    => 'text',
            'title'   => '静态资源 CDN',
            'default' => '',
            'desc'    => '留空使用默认主题路径',
        ],
        [
            'id'      => 'seo_extra_meta',
            'type'    => 'textarea',
            'title'   => '额外 Meta 标签',
            'default' => '',
        ],
    ],
]);
