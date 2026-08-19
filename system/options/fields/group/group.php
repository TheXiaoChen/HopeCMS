<?php
defined('HOPE_ROOT') || exit('access denied!');
/**
 *
 * Field: group
 *
 * @since 1.0.0
 * @version 1.0.1
 *
 */
if (!class_exists('CSF_Field_group')) {
  class CSF_Field_group extends CSF_Fields
  {

    public function __construct($field, $value = '', $unique = '', $where = '', $parent = '')
    {
      parent::__construct($field, $value, $unique, $where, $parent);
    }

    /**
     * 将标题展示值规范为字符串，避免 upload/数组 传入 esc_attr 报错
     */
    private function title_text($value)
    {
      if (is_array($value)) {
        if (isset($value['url'])) {
          return (string)$value['url'];
        }
        if (isset($value['title'])) {
          return (string)$value['title'];
        }
        $value = reset($value);
        return is_scalar($value) ? (string)$value : '';
      }
      if (is_object($value)) {
        return '';
      }
      return (string)$value;
    }

    public function render()
    {

      $args = hope_parse_args($this->field, array(
        'max'                    => 0,
        'min'                    => 0,
        'fields'                 => array(),
        'button_title'           => '新增',
        'accordion_title_prefix' => '',
        'accordion_title_number' => false,
        'accordion_title_auto'   => true,
      ));

      $title_prefix = (!empty($args['accordion_title_prefix'])) ? $args['accordion_title_prefix'] : '';
      $title_number = (!empty($args['accordion_title_number'])) ? true : false;
      $title_auto   = (!empty($args['accordion_title_auto'])) ? true : false;
      $sub_fields   = (!empty($this->field['fields']) && is_array($this->field['fields'])) ? $this->field['fields'] : array();

      if (preg_match('/' . preg_quote('[' . $this->field['id'] . ']') . '/', $this->unique)) {
        echo '<div class="hope-notice hope-notice-danger">错误: 字段ID冲突。</div>';
      } else {

        echo $this->field_before();

        echo '<div class="hope-cloneable-item hope-cloneable-hidden">';

        echo '<div class="hope-cloneable-helper">';
        echo '<i class="hope-cloneable-sort fas fa-arrows-alt"></i>';
        echo '<i class="hope-cloneable-clone far fa-clone"></i>';
        echo '<i class="hope-cloneable-remove hope-confirm fas fa-times" data-confirm="您确定想要删除吗?"></i>';
        echo '</div>';

        echo '<h4 class="hope-cloneable-title">';
        echo '<span class="hope-cloneable-text">';
        echo ($title_number) ? '<span class="hope-cloneable-title-number"></span>' : '';
        echo ($title_prefix) ? '<span class="hope-cloneable-title-prefix">' . esc_attr($title_prefix) . '</span>' : '';
        echo ($title_auto) ? '<span class="hope-cloneable-value"><span class="hope-cloneable-placeholder"></span></span>' : '';
        echo '</span>';
        echo '</h4>';

        echo '<div class="hope-cloneable-content">';
        foreach ($sub_fields as $field) {

          $field_default = (isset($field['default'])) ? $field['default'] : '';
          $field_unique  = (!empty($this->unique)) ? $this->unique . '[' . $this->field['id'] . '][0]' : $this->field['id'] . '[0]';

          CSF::field($field, $field_default, '___' . $field_unique, 'field/group');
        }
        echo '</div>';

        echo '</div>';

        echo '<div class="hope-cloneable-wrapper hope-data-wrapper" data-title-number="' . esc_attr($title_number) . '" data-field-id="[' . esc_attr($this->field['id']) . ']" data-max="' . esc_attr($args['max']) . '" data-min="' . esc_attr($args['min']) . '">';

        // 兼容旧版字符串配置（如 textarea 改成 group 后未重新保存）
        if (!empty($this->value) && is_array($this->value)) {

          $num = 0;

          foreach ($this->value as $value) {

            if (!is_array($value)) {
              continue;
            }

            $first_id    = (isset($sub_fields[0]['id'])) ? $sub_fields[0]['id'] : '';
            $first_value = ($first_id !== '' && isset($value[$first_id])) ? $value[$first_id] : '';
            $first_value = $this->title_text($first_value);

            echo '<div class="hope-cloneable-item">';

            echo '<div class="hope-cloneable-helper">';
            echo '<i class="hope-cloneable-sort fas fa-arrows-alt"></i>';
            echo '<i class="hope-cloneable-clone far fa-clone"></i>';
            echo '<i class="hope-cloneable-remove hope-confirm fas fa-times" data-confirm="您确定想要删除吗?"></i>';
            echo '</div>';

            echo '<h4 class="hope-cloneable-title">';
            echo '<span class="hope-cloneable-text">';
            echo ($title_number) ? '<span class="hope-cloneable-title-number">' . esc_attr($num + 1) . '.</span>' : '';
            echo ($title_prefix) ? '<span class="hope-cloneable-title-prefix">' . esc_attr($title_prefix) . '</span>' : '';
            echo ($title_auto) ? '<span class="hope-cloneable-value">' . esc_attr($first_value) . '</span>' : '';
            echo '</span>';
            echo '</h4>';

            echo '<div class="hope-cloneable-content">';

            foreach ($sub_fields as $field) {

              $field_unique = (!empty($this->unique)) ? $this->unique . '[' . $this->field['id'] . '][' . $num . ']' : $this->field['id'] . '[' . $num . ']';
              $field_value  = (isset($field['id']) && isset($value[$field['id']])) ? $value[$field['id']] : '';

              CSF::field($field, $field_value, $field_unique, 'field/group');
            }

            echo '</div>';

            echo '</div>';

            $num++;
          }
        }

        echo '</div>';

        echo '<div class="hope-cloneable-alert hope-cloneable-max">您不能再添加了。</div>';
        echo '<div class="hope-cloneable-alert hope-cloneable-min">您无法删除更多。</div>';
        echo '<a href="#" class="btn btn-primary hope-cloneable-add">' . $args['button_title'] . '</a>';

        echo $this->field_after();
      }
    }
  }
}
