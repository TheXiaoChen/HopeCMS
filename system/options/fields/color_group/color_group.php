<?php defined('HOPE_ROOT') || exit('access denied!');
 // Cannot access directly.
/**
 *
 * Field: color_group
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if ( ! class_exists( 'CSF_Field_color_group' ) ) {
  class CSF_Field_color_group extends CSF_Fields {

    public function __construct( $field, $value = '', $unique = '', $where = '', $parent = '' ) {
      parent::__construct( $field, $value, $unique, $where, $parent );
    }

    public function render() {

      $options = ( ! empty( $this->field['options'] ) ) ? $this->field['options'] : array();

      echo $this->field_before();

      if ( ! empty( $options ) ) {
        foreach ( $options as $key => $option ) {

          $color_value  = ( ! empty( $this->value[$key] ) ) ? $this->value[$key] : '';
          $default_attr = ( ! empty( $this->field['default'][$key] ) ) ? ' data-default-color="'. esc_attr( $this->field['default'][$key] ) .'"' : '';

          echo '<div class="hope--left hope-field-color">';
          echo '<div class="hope--title">'. $option .'</div>';
          echo '<div class="hope-flex gap-1">';
          echo '<input type="text" name="'. esc_attr( $this->field_name( '['. $key .']' ) ) .'" value="'. esc_attr( $color_value ) .'" class="hope-color"'. $default_attr . $this->field_attributes() .'/>';
          echo '</div>';
          echo '</div>';

        }
      }

      echo $this->field_after();

    }

  }
}
