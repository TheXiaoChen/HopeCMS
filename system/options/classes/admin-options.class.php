<?php
defined('HOPE_ROOT') || exit('access denied!');
// Cannot access directly.
/**
 *
 * Options Class
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if (!class_exists('CSF_Options')) {
  class CSF_Options extends CSF_Abstract
  {

    // 常量
    public $unique = '';
    public $notice = '';
    public $abstract = 'options';
    public $sections = array();
    public $options = array();
    public $errors = array();
    public $pre_tabs = array();
    public $pre_fields = array();
    public $pre_sections = array();
    public $args = array(

      // framework title
      'theme_title' => '&#27426;&#36814;&#20351;&#29992;&#72;&#79;&#80;&#69;&#67;&#77;&#83;',
      'framework_class' => '',

      // menu settings
      'menu_title' => '',
      'menu_slug' => '',
      'menu_type' => 'menu',
      'menu_capability' => 'manage_options',
      'menu_icon' => null,
      'menu_position' => null,
      'menu_hidden' => false,
      'menu_parent' => '',
      'sub_menu_title' => '',

      // menu extras
      'show_bar_menu' => true,
      'show_sub_menu' => true,
      'show_in_network' => true,
      'show_in_customizer' => false,

      'show_search' => true,
      'show_reset_all' => true,
      'show_reset_section' => true,
      'show_footer' => true,
      'show_all_options' => true,
      'show_form_warning' => true,
      'sticky_header' => true,
      'save_defaults' => true,
      'ajax_save' => true,

      // admin bar menu settings
      'admin_bar_menu_icon' => '',
      'admin_bar_menu_priority' => 50,

      // footer
      'footer_text' => '',
      'footer_after' => '',
      'footer_credit' => '',

      // database model
      'database' => '', // options, transient, theme_mod, network
      'transient_time' => 0,

      // contextual help
      'contextual_help' => array(),
      'contextual_help_sidebar' => '',

      // typography options
      'enqueue_webfont' => true,
      'async_webfont' => false,

      // others
      'output_css' => true,

      // theme
      'theme' => 'dark',
      'class' => '',

      // external default values
      'defaults' => array(),

    );

    // run framework construct
    public function __construct($key, $params = array())
    {

      $this->unique = $key;
      $this->args = hope_parse_args($params['args'], $this->args);
      $this->sections = $params['sections'];

      // run only is admin panel options, avoid performance loss
      $this->pre_tabs = $this->pre_tabs($this->sections);
      $this->pre_fields = $this->pre_fields($this->sections);
      $this->pre_sections = $this->pre_sections($this->sections);

      //对区块渲染数据
      $this->get_options();
      $this->save_defaults();
      $this->add_options_html();


      // wp enqeueu for typography and output css
      parent::__construct();
    }

    // instance
    public static function instance($key, $params = array())
    {
      return new self($key, $params);
    }

    public function pre_tabs($sections)
    {

      $result = array();
      $parents = array();
      $count = 100;

      foreach ($sections as $key => $section) {
        if (!empty($section['parent'])) {
          $section['priority'] = (isset($section['priority'])) ? $section['priority'] : $count;
          $parents[$section['parent']][] = $section;
          unset($sections[$key]);
        }
        $count++;
      }

      foreach ($sections as $key => $section) {
        $section['priority'] = (isset($section['priority'])) ? $section['priority'] : $count;
        if (!empty($section['id']) && !empty($parents[$section['id']])) {
          $section['subs'] = hope_list_sort($parents[$section['id']], array('priority' => 'ASC'), 'ASC', true);
        }
        $result[] = $section;
        $count++;
      }

      return hope_list_sort($result, array('priority' => 'ASC'), 'ASC', true);
    }

    public function pre_fields($sections)
    {

      $result = array();

      foreach ($sections as $key => $section) {
        if (!empty($section['fields'])) {
          foreach ($section['fields'] as $field) {
            $result[] = $field;
          }
        }
      }

      return $result;
    }

    public function pre_sections($sections)
    {

      $result = array();

      foreach ($this->pre_tabs as $tab) {
        if (!empty($tab['subs'])) {
          foreach ($tab['subs'] as $sub) {
            $sub['ptitle'] = $tab['title'];
            $result[] = $sub;
          }
        }
        if (empty($tab['subs'])) {
          $result[] = $tab;
        }
      }

      return $result;
    }


    // get default value
    public function get_default($field)
    {

      $default = (isset($field['default'])) ? $field['default'] : '';
      $default = (isset($this->args['defaults'][$field['id']])) ? $this->args['defaults'][$field['id']] : $default;

      return $default;
    }

    // 保存默认值并将新字段值渲染
    public function save_defaults()
    {

      $tmp_options = $this->options;

      foreach ($this->pre_fields as $field) {
        if (!empty($field['id'])) {
          $this->options[$field['id']] = (isset($this->options[$field['id']])) ? $this->options[$field['id']] : $this->get_default($field);
        }
      }

      if ($this->args['save_defaults'] && empty($tmp_options)) {
        $this->save_options($this->options);
      }
    }
    // 保存区块数据
    public function save_options($data)
    {
      $CACHE = Cache::getInstance();
      Option::updateOption($this->unique, serialize($data));
      $CACHE->updateCache('options');
    }

    // 获取区块数据
    public function get_options()
    {
      $this->options = unserialize(Option::get( $this->unique));
      if (empty($this->options)) {
        $this->options = array();
      }

      return $this->options;
    }

    public function add_page_on_load()
    {

      if (!empty($this->args['contextual_help'])) {

        $screen = get_current_screen();

        foreach ($this->args['contextual_help'] as $tab) {
          $screen->add_help_tab($tab);
        }

        if (!empty($this->args['contextual_help_sidebar'])) {
          $screen->set_help_sidebar($this->args['contextual_help_sidebar']);
        }
      }

      add_filter('admin_footer_text', array(&$this, 'add_admin_footer_text'));
    }

    public function add_admin_footer_text()
    {
      $default = 'Thank you for creating with <a href="http://codestarframework.com/" target="_blank">Codestar Framework</a>';
      echo (!empty($this->args['footer_credit'])) ? $this->args['footer_credit'] : $default;
    }

    public function error_check($sections, $err = '')
    {

      if (!$this->args['ajax_save']) {

        if (!empty($sections['fields'])) {
          foreach ($sections['fields'] as $field) {
            if (!empty($field['id'])) {
              if (array_key_exists($field['id'], $this->errors)) {
                $err = '<span class="hope-label-error">!</span>';
              }
            }
          }
        }

        if (!empty($sections['subs'])) {
          foreach ($sections['subs'] as $sub) {
            $err = $this->error_check($sub, $err);
          }
        }

        if (!empty($sections['id']) && array_key_exists($sections['id'], $this->errors)) {
          $err = $this->errors[$sections['id']];
        }
      }

      return $err;
    }

    // option page html output
    public function add_options_html()
    {



      $has_nav = (count($this->pre_tabs) > 1) ? true : false;
      $show_all = (!$has_nav) ? ' hope-show-all' : '';
      $ajax_class = ($this->args['ajax_save']) ? ' hope-save-ajax' : '';
      $sticky_class = ($this->args['sticky_header']) ? ' hope-sticky-header' : '';
      $wrapper_class = ($this->args['framework_class']) ? ' ' . $this->args['framework_class'] : '';
      $theme = ($this->args['theme']) ? ' hope-theme-' . $this->args['theme'] : '';
      $class = ($this->args['class']) ? ' ' . $this->args['class'] : '';


      echo '<div class="hope hope-options' . esc_attr($theme . $class . $wrapper_class) . '" data-slug="' . esc_attr($this->args['menu_slug']) . '" data-unique="' . esc_attr($this->unique) . '">';
      echo '<div class="hope-container">';
      echo '<form method="post" action="" enctype="multipart/form-data" id="hope-form" autocomplete="off" novalidate="novalidate">';
      echo '<input type="hidden" class="hope-section-id" name="hope_transient[section]" value="1">';
      echo '<div class="hope-header' . esc_attr($sticky_class) . '">';
      echo '<div class="hope-header-inner">';
      echo '<div class="hope-header-left">';
      echo '<h1>' . $this->args['theme_title'] . '</h1>';
      echo '</div>';
      echo '<div class="hope-header-right">';

      $notice_class = (!empty($this->notice)) ? 'hope-form-show' : '';
      $notice_text = (!empty($this->notice)) ? $this->notice : '';

      echo '<div class="hope-form-result hope-form-success ' . esc_attr($notice_class) . '">' . $notice_text . '</div>';
      echo ($this->args['show_form_warning']) ? '<div class="hope-form-result hope-form-warning">有设置已修改，请记得保存！</div>' : '';
      echo ($has_nav && $this->args['show_all_options']) ? '<div class="hope-expand-all" title="显示所有选项"><i class="fas fa-outdent"></i></div>' : '';
      echo ($this->args['show_search']) ? '<div class="hope-search"><input type="text" name="hope-search" placeholder="输入标题关键字搜索..." autocomplete="off" /></div>' : '';
      echo '</div>';
      echo '<div class="hope-buttons">';
      echo '<a href="javascript:;" class="hope-menu hidden" title="显示选项菜单"><i class="fas fa-bars"></i></a>';
      echo '<input type="submit" name="' . esc_attr($this->unique) . '[_nonce][save]" class="button button-primary hope-top-save hope-save' . esc_attr($ajax_class) . '" value="保存" data-save="保存中...">';
      echo ($this->args['show_reset_all']) ? '<input type="submit" name="hope_transient[reset]" class="button hope-warning-primary hope-reset-all hope-confirm" value="重置" data-confirm="是否要将所有设置重置为默认值?">' : '';
      echo '</div>';
      echo '<div class="clear"></div>';
      echo '</div>';
      echo '</div>';
      echo '<div class="hope-wrapper' . esc_attr($show_all) . '">';

      if ($has_nav) {

        echo '<div class="hope-nav hope-nav-options">';
        echo '<ul>';

        foreach ($this->pre_tabs as $tab) {
          $tab_id = sanitize_title($tab['title']);
          $tab_error = $this->error_check($tab);
          $tab_icon = (!empty($tab['icon'])) ? '<i class="hope-tab-icon ' . esc_attr($tab['icon']) . '"></i>' : '';

          if (!empty($tab['subs'])) {

            echo '<li class="hope-tab-item">';
            echo '<a href="#tab=' . esc_attr($tab_id) . '" data-tab-id="' . esc_attr($tab_id) . '" class="hope-arrow">' . $tab_icon . $tab['title'] . $tab_error . '</a>';
            echo '<ul>';

            foreach ($tab['subs'] as $sub) {
              $sub_id = $tab_id . '/' . sanitize_title($sub['title']);
              $sub_error = $this->error_check($sub);
              $sub_icon = (!empty($sub['icon'])) ? '<i class="hope-tab-icon ' . esc_attr($sub['icon']) . '"></i>' : '';
              echo '<li><a href="#tab=' . esc_attr($sub_id) . '" data-tab-id="' . esc_attr($sub_id) . '">' . $sub_icon . $sub['title'] . $sub_error . '</a></li>';
            }

            echo '</ul>';
            echo '</li>';
          } else {

            echo '<li class="hope-tab-item"><a href="#tab=' . esc_attr($tab_id) . '" data-tab-id="' . esc_attr($tab_id) . '">' . $tab_icon . $tab['title'] . $tab_error . '</a></li>';
          }
        }
        echo '</ul>';
        echo '</div>';
      }

      echo '<div class="hope-content">';
      echo '<div class="hope-sections">';

      foreach ($this->pre_sections as $section) {

        $section_onload = (!$has_nav) ? ' hope-onload' : '';
        $section_class = (!empty($section['class'])) ? ' ' . $section['class'] : '';
        $section_icon = (!empty($section['icon'])) ? '<i class="hope-section-icon ' . esc_attr($section['icon']) . '"></i>' : '';
        $section_title = (!empty($section['title'])) ? $section['title'] : '';
        $section_parent = (!empty($section['ptitle'])) ? sanitize_title($section['ptitle']) . '/' : '';
        $section_slug = (!empty($section['title'])) ? sanitize_title($section_title) : '';
        echo '<div class="hope-section hidden' . esc_attr($section_onload . $section_class) . '" data-section-id="' . esc_attr($section_parent . $section_slug) . '">';
        echo ($has_nav) ? '<div class="hope-section-title"><h3>' . $section_icon . $section_title . '</h3></div>' : '';
        echo (!empty($section['description'])) ? '<div class="hope-field hope-section-description">' . $section['description'] . '</div>' : '';

        if (!empty($section['fields'])) {

          foreach ($section['fields'] as $field) {

            $is_field_error = $this->error_check($field);

            if (!empty($is_field_error)) {
              $field['_error'] = $is_field_error;
            }

            if (!empty($field['id'])) {
              $field['default'] = $this->get_default($field);
            }

            $value = (!empty($field['id']) && isset($this->options[$field['id']])) ? $this->options[$field['id']] : '';

            CSF::field($field, $value, $this->unique, 'options');
          }
        } else {

          echo '<div class="hope-no-option">没有可用数据。</div>';
        }

        echo '</div>';
      }

      echo '</div>';

      echo '<div class="clear"></div>';

      echo '</div>';

      echo '<div class="hope-nav-background"></div>';

      echo '</div>';

      if (!empty($this->args['show_footer'])) {

        echo '<div class="hope-footer">';

        echo '<div class="hope-buttons">';
        echo '<a href="javascript:;" class="hope-menu hidden" title="显示选项菜单"><i class="fas fa-bars"></i></a>';
        echo '<input type="submit" name="hope_transient[save]" class="button button-primary hope-save' . esc_attr($ajax_class) . '" value="保存" data-save="保存中...">';
        echo ($this->args['show_reset_all']) ? '<input type="submit" name="hope_transient[reset]" class="button hope-warning-primary hope-reset-all hope-confirm" value="重置" data-confirm="您确定要将所有设置重置为默认值吗？">' : '';
        echo '</div>';

        echo (!empty($this->args['footer_text'])) ? '<div class="hope-copyright">' . $this->args['footer_text'] . '</div>' : '';

        echo '<div class="clear"></div>';
        echo '</div>';
      }

      echo '</form>';

      echo '</div>';

      echo '<div class="clear"></div>';

      echo (!empty($this->args['footer_after'])) ? $this->args['footer_after'] : '';

      echo '</div>';
    }
  }
}
