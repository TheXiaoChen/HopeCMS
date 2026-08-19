<?php defined('HOPE_ROOT') || exit('access denied!');
 // Cannot access directly.
/**
 *
 * Field: repeater
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if ( ! class_exists( 'CSF_Field_repeater' ) ) {
  class CSF_Field_repeater extends CSF_Fields {

    public function __construct( $field, $value = '', $unique = '', $where = '', $parent = '' ) {
      parent::__construct( $field, $value, $unique, $where, $parent );
    }

    public function render() {

      $args = hope_parse_args( $this->field, array(
        'max'          => 0,
        'min'          => 0,
        'button_title' => '<i class="fas fa-plus-circle"></i>',
      ) );

      if ( preg_match( '/'. preg_quote( '['. $this->field['id'] .']' ) .'/', $this->unique ) ) {

        echo '<div class="hope-notice hope-notice-danger">错误: 字段ID冲突。</div>';

      } else {

        echo $this->field_before();

        echo '<div class="hope-repeater-item hope-repeater-hidden">';
        echo '<div class="hope-repeater-content">';
        foreach ( $this->field['fields'] as $field ) {

          $field_default = ( isset( $field['default'] ) ) ? $field['default'] : '';
          $field_unique  = ( ! empty( $this->unique ) ) ? $this->unique .'['. $this->field['id'] .'][0]' : $this->field['id'] .'[0]';

          CSF::field( $field, $field_default, '___'. $field_unique, 'field/repeater' );

        }
        echo '</div>';
        echo '<div class="hope-repeater-helper">';
        echo '<div class="hope-repeater-helper-inner">';
        echo '<i class="hope-repeater-sort fas fa-arrows-alt"></i>';
        echo '<i class="hope-repeater-clone far fa-clone"></i>';
        echo '<i class="hope-repeater-remove hope-confirm fas fa-times" data-confirm="您确定想要删除吗?"></i>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        echo '<div class="hope-repeater-wrapper hope-data-wrapper" data-field-id="['. esc_attr( $this->field['id'] ) .']" data-max="'. esc_attr( $args['max'] ) .'" data-min="'. esc_attr( $args['min'] ) .'">';

        if ( ! empty( $this->value ) && is_array( $this->value ) ) {

          $num = 0;

          foreach ( $this->value as $key => $value ) {

            echo '<div class="hope-repeater-item">';
            echo '<div class="hope-repeater-content">';
            foreach ( $this->field['fields'] as $field ) {

              $field_unique = ( ! empty( $this->unique ) ) ? $this->unique .'['. $this->field['id'] .']['. $num .']' : $this->field['id'] .'['. $num .']';
              $field_value  = ( isset( $field['id'] ) && isset( $this->value[$key][$field['id']] ) ) ? $this->value[$key][$field['id']] : '';

              CSF::field( $field, $field_value, $field_unique, 'field/repeater' );

            }
            echo '</div>';
            echo '<div class="hope-repeater-helper">';
            echo '<div class="hope-repeater-helper-inner">';
            echo '<i class="hope-repeater-sort fas fa-arrows-alt"></i>';
            echo '<i class="hope-repeater-clone far fa-clone"></i>';
            echo '<i class="hope-repeater-remove hope-confirm fas fa-times" data-confirm="您确定想要删除吗?"></i>';
            echo '</div>';
            echo '</div>';
            echo '</div>';

            $num++;

          }

        }

        echo '</div>';

        echo '<div class="hope-repeater-alert hope-repeater-max">您不能再添加了。</div>';
        echo '<div class="hope-repeater-alert hope-repeater-min">您无法删除更多。</div>';
        echo '<a href="#" class="btn btn-primary hope-repeater-add">'. $args['button_title'] .'</a>';

        echo $this->field_after();

      }

    }

  }
}
