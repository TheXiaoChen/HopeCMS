<?php 
defined('HOPE_ROOT') || exit('access denied!');

/**
 * CSF 设置框架入口类（带中文注释）
 *
 * 说明：此文件为框架主类，负责初始化框架、注册不同类型的选项（管理面板、元框、短码、菜单、配置器等）
 *       并统一管理资源加载（CSS/JS）、字段类型引入、语言包加载等通用功能。
 *
 * 注：不修改原代码逻辑，仅在关键位置加中文注释以方便二次开发与维护。
 */
/**
 *
 * Setup Class
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */


if (!class_exists('CSF')) {
    class CSF
    {

        // Default constants
        public static $version  = '2.2.0';
        public static $dir      = '';
        public static $url      = '';
        public static $css      = '';
        public static $webfonts = array();
        public static $subsets  = array();
        public static $inited   = array();
        public static $fields   = array();
        public static $args     = array(
            'admin_options'     => array(),
        );


        // 插件初始化
        public static function init()
        {

            // 设置其它常量
            self::constants();

            // 引入相关文件
            self::includes();

            // Setup textdomain  载入语音
            //self::textdomain();
        }

        // Setup frameworks
        public static function setup()
        {

            // Welcome page
            //self::include_plugin_file('views/welcome.php');

            // Setup admin option framework
            $params = array();
            if (!empty(self::$args['admin_options'])) {
                foreach (self::$args['admin_options'] as $key => $value) {
                    if (!empty(self::$args['sections'][$key]) && !isset(self::$inited[$key])) {

                        $params['args']     = $value;
                        $params['sections'] = self::$args['sections'][$key];
                        self::$inited[$key] = true;

                        CSF_Options::instance($key, $params);

                        if (!empty($value['show_in_customizer'])) {
                            $value['output_css']                   = false;
                            $value['enqueue_webfont']              = false;
                            self::$args['customize_options'][$key] = $value;
                            self::$inited[$key]                    = null;
                        }

                    }
                }
            }

        }

        // Create options
        public static function createOptions($id, $args = array())
        {
            // 注册一个管理面板（admin）配置
            // 参数：
            //  - $id   : 唯一键名，用于标识该 options（通常用于菜单 slug）
            //  - $args : 配置数组（标题、菜单位置、保存行为等），框架会在 setup() 中实例化处理
            self::$args['admin_options'][$id] = $args;
        }


        // Create section
        public static function createSection($id, $sections)
        {
            // 向指定的 options id 下添加一个 section（多个 section 会作为数组保存）
            // $sections 可以包含 fields 字段数组，set_used_fields 会递归收集字段类型
            self::$args['sections'][$id][] = $sections;
            self::set_used_fields($sections);
        }

        // 设置目录常量
        public static function constants()
        {

            // 计算并设置框架目录路径和 URL（支持插件或主题两种安装位置）
            // 逻辑：检测当前文件夹是否位于 protocol_uri 下，若是则视为插件安装，否则视为主题下的框架
            $dirname        = str_replace('//', '/', dirname(dirname(__FILE__)));
            $theme_dir      = str_replace('//', '/', THEME_PATH);
            $plugin_dir     = PLUGIN_URL . basename($dirname);
            // sanitize_dirname 用于去除非字母字符，避免路径比较因特殊字符失败
            $located_plugin = (preg_match('#' . self::sanitize_dirname($plugin_dir) . '#', self::sanitize_dirname($dirname))) ? true : false;
            $directory      = ($located_plugin) ? $plugin_dir : $theme_dir;
            $directory_uri  = ($located_plugin) ? $plugin_uri : THEME_URL;
            $foldername     = str_replace($directory, '', $dirname);

            // 保存到静态属性，供 include_plugin_url 等方法使用
            self::$dir = $dirname;
            self::$url = $directory_uri . $foldername;
        }

        //统一引入文件
        public static function include_plugin_file($file, $load = true)
        {
            if(empty($file)) hpMsg('文件或目录不存在');
            if (file_exists(self::$dir . '/' . $file)) {
                $path = self::$dir . '/' . $file;
            }

            if (!empty($path) && !empty($file) && $load) {
                require_once $path;
            }else{
                return self::$dir . '/' . $file;
            }
            return '';
        }

        // Is active plugin helper
        public static function is_active_plugin($file = '')
        {
            // 判断某个插件文件是否在 WordPress 激活插件列表中
            return in_array($file, (array) get_option('active_plugins', array()));
        }

        // Sanitize dirname
        public static function sanitize_dirname($dirname)
        {
            // 仅保留字母字符，常用于路径/目录名称比较时简化字符串，避免特殊字符干扰
            return preg_replace('/[^A-Za-z]/', '', $dirname);
        }

        // Set url constant
        public static function include_plugin_url($file)
        {
            // 返回基于框架 URL 的资源完整 URL（用于 enqueue 时传入）
            return esc_url(self::$url) . '/' . ltrim($file, '/');
        }

        // Include files
        public static function includes()
        {

            // 引入必要的 helper 函数与类定义
            // helpers: 通用函数（actions/helpers/sanitize/validate）
            self::include_plugin_file('functions/actions.php');
            self::include_plugin_file('functions/helpers.php');
            self::include_plugin_file('functions/sanitize.php');
            self::include_plugin_file('functions/validate.php');
            // 包含免费版核心类（字段基类、选项处理类）
            self::include_plugin_file('classes/abstract.class.php');
            self::include_plugin_file('classes/fields.class.php');
            self::include_plugin_file('classes/admin-options.class.php');

        }

        // Maybe include a field class
        public static function maybe_include_field($type = '')
        {
            // 根据字段类型动态包含字段类文件（避免一次性加载所有字段，按需加载提高性能）
            if (!class_exists('CSF_Field_' . $type) && class_exists('CSF_Fields')) {
                self::include_plugin_file('fields/' . $type . '/' . $type . '.php');
            }
        }

        // Setup textdomain
        public static function textdomain()
        {
            // 加载框架的翻译文件（languages/<locale>.mo），供国际化使用
            load_textdomain('csf', self::$dir . '/languages/' . get_locale() . '.mo');
        }

        // Set all of used fields
        public static function set_used_fields($sections)
        {

            // 递归遍历 section 中的 fields，收集所有使用到的字段类型
            if (!empty($sections['fields'])) {

                foreach ($sections['fields'] as $field) {

                    // 支持嵌套字段（group/repeater 等）
                    if (!empty($field['fields'])) {
                        self::set_used_fields($field);
                    }

                    // 支持 tabs（选项卡）场景
                    if (!empty($field['tabs'])) {
                        self::set_used_fields(array('fields' => $field['tabs']));
                    }

                    // 支持手风琴（accordions）场景
                    if (!empty($field['accordions'])) {
                        self::set_used_fields(array('fields' => $field['accordions']));
                    }

                    // 最终记录字段类型，用于 enqueue 时按需加载字段脚本/样式
                    if (!empty($field['type'])) {
                        self::$fields[$field['type']] = $field;
                    }

                }

            }

        }

        // 队列管理和字段样式和脚本
        public static function add_admin_enqueue_scripts()
        {
            if (!empty(self::$fields)) {
                foreach (self::$fields as $field) {
                    if (!empty($field['type'])) {
                        $classname = 'CSF_Field_' . $field['type'];
                        self::maybe_include_field($field['type']);
                        if (class_exists($classname) && method_exists($classname, 'enqueue')) {
                            $instance = new $classname($field);
                            if (method_exists($classname, 'enqueue')) {
                                $instance->enqueue();
                            }
                            unset($instance);
                        }
                    }
                }
            }

        }

        // Add typography enqueue styles to front page
        public static function add_typography_enqueue_styles()
        {

            if (!empty(self::$webfonts)) {

                if (!empty(self::$webfonts['enqueue'])) {

                    $query = array();
                    $fonts = array();

                    foreach (self::$webfonts['enqueue'] as $family => $styles) {
                        $fonts[] = $family . ((!empty($styles)) ? ':' . implode(',', $styles) : '');
                    }

                    if (!empty($fonts)) {
                        $query['family'] = implode('%7C', $fonts);
                    }

                    if (!empty(self::$subsets)) {
                        $query['subset'] = implode(',', self::$subsets);
                    }

                    $query['display'] = 'swap';

                    // 前台按需加载 Google 字体样式表
                    wp_enqueue_style('hope-google-web-fonts', esc_url(add_query_arg($query, '//fonts.googleapis.com/css')), array(), null);

                }

                if (!empty(self::$webfonts['async'])) {

                    $fonts = array();

                    foreach (self::$webfonts['async'] as $family => $styles) {
                        $fonts[] = $family . ((!empty($styles)) ? ':' . implode(',', $styles) : '');
                    }

                    // 异步加载 WebFont JS，并通过 WebFontConfig 传递需要加载的字体族
                    wp_enqueue_script('hope-google-web-fonts', esc_url('//ajax.googleapis.com/ajax/libs/webfont/1.6.26/webfont.js'), array(), null);

                    wp_localize_script('hope-google-web-fonts', 'WebFontConfig', array('google' => array('families' => $fonts)));

                }

            }

        }

        // Add admin body class
        public static function add_admin_body_class($classes)
        {

            // 如果启用了 FA4 兼容模式，则在 body 上添加 shim class
            if (apply_filters('csf_fa4', false)) {
                $classes .= 'hope-fa5-shims';
            }

            return $classes;

        }

        // Add custom css to front page
        public static function add_custom_css()
        {

            // 将用户在框架中设置的自定义 CSS 直接输出到前台 head 中
            if (!empty(self::$css)) {
                echo '<style type="text/css">' . wp_strip_all_tags(self::$css) . '</style>';
            }

        }

        // Add a new framework field
        public static function field($field = array(), $value = '', $unique = '', $where = '', $parent = '')
        {

            // 渲染单个字段（field）的前置处理逻辑
            // 支持：
            //  - 不允许的字段（_notice 标记）
            //  - 依赖显示（dependency） -> 在外层元素上添加 data-* 属性以供前端 JS 控制显示/隐藏
            //  - 标题 / 副标题 / fancy title 渲染
            //  - 根据 type 动态包含字段类并调用其 render 方法

            // Check for unallow fields（不允许的字段会被替换成 notice）
            if (!empty($field['_notice'])) {

                $field_type = $field['type'];

                $field            = array();
                $field['content'] = '哎呀! 不允许。 <strong>(' . $field_type . ')</strong>';
                $field['type']    = 'notice';
                $field['style']   = 'danger';

            }

            $depend     = '';
            $visible    = '';
            $unique     = (!empty($unique)) ? $unique : '';
            $class      = (!empty($field['class'])) ? ' ' . esc_attr($field['class']) : '';
            $is_pseudo  = (!empty($field['pseudo'])) ? ' hope-pseudo-field' : '';
            $field_type = (!empty($field['type'])) ? esc_attr($field['type']) : '';

            // 处理依赖配置（支持多维 dependency 数组或单一依赖）
            if (!empty($field['dependency'])) {

                $dependency      = $field['dependency'];
                $depend_visible  = '';
                $data_controller = '';
                $data_condition  = '';
                $data_value      = '';
                $data_global     = '';

                if (is_array($dependency[0])) {
                    // 多个依赖的情况，拼接为管道分隔的字符串以便前端解析
                    $data_controller = implode('|', array_column($dependency, 0));
                    $data_condition  = implode('|', array_column($dependency, 1));
                    $data_value      = implode('|', array_column($dependency, 2));
                    $data_global     = implode('|', array_column($dependency, 3));
                    $depend_visible  = implode('|', array_column($dependency, 4));
                } else {
                    // 单一依赖配置
                    $data_controller = (!empty($dependency[0])) ? $dependency[0] : '';
                    $data_condition  = (!empty($dependency[1])) ? $dependency[1] : '';
                    $data_value      = (!empty($dependency[2])) ? $dependency[2] : '';
                    $data_global     = (!empty($dependency[3])) ? $dependency[3] : '';
                    $depend_visible  = (!empty($dependency[4])) ? $dependency[4] : '';
                }

                $depend .= ' data-controller="' . esc_attr($data_controller) . '"';
                $depend .= ' data-condition="' . esc_attr($data_condition) . '"';
                $depend .= ' data-value="' . esc_attr($data_value) . '"';
                $depend .= (!empty($data_global)) ? ' data-depend-global="true"' : '';

                // 根据 depend_visible 决定默认是显示还是隐藏
                $visible = (!empty($depend_visible)) ? ' hope-depend-visible' : ' hope-depend-hidden';

            }

            if (!empty($field_type)) {

                // These attributes has been sanitized above.
                echo '<div class="hope-field hope-field-' . $field_type . $is_pseudo . $class . $visible . '"' . $depend . '>';

                // fancy title 与常规 title 支持
                if (!empty($field['fancy_title'])) {
                    echo '<div class="hope-fancy-title">' . $field['fancy_title'] . '</div>';
                }

                if (!empty($field['title'])) {
                    echo '<div class="hope-title">';
                    echo '<h4>' . $field['title'] . '</h4>';
                    echo (!empty($field['subtitle'])) ? '<div class="hope-subtitle-text">' . $field['subtitle'] . '</div>' : '';
                    echo '</div>';
                }

                echo (!empty($field['title']) || !empty($field['fancy_title'])) ? '<div class="hope-fieldset">' : '';

                $value = (!isset($value) && isset($field['default'])) ? $field['default'] : $value;
                $value = (isset($field['value'])) ? $field['value'] : $value;

                // 按需包含字段类并创建实例渲染
                self::maybe_include_field($field_type);

                $classname = 'CSF_Field_' . $field_type;

                if (class_exists($classname)) {
                    $instance = new $classname($field, $value, $unique, $where, $parent);
                    $instance->render();
                } else {
                    echo '<p>未找到字段!</p>';
                }

            } else {
                echo '<p>未找到字段!</p>';
            }

            echo (!empty($field['title']) || !empty($field['fancy_title'])) ? '</div>' : '';
            echo '<div class="clear"></div>';
            echo '</div>';

        }

    }

    CSF::init();
}
