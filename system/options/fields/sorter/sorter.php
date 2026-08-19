<?php defined('HOPE_ROOT') || exit('access denied!');
 // Cannot access directly.
/**
 *
 * Field: sorter
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if ( ! class_exists( 'CSF_Field_sorter' ) ) {
  class CSF_Field_sorter extends CSF_Fields {

    public function __construct( $field, $value = '', $unique = '', $where = '', $parent = '' ) {
      parent::__construct( $field, $value, $unique, $where, $parent );
    }

    public function render() {

      $args = hope_parse_args( $this->field, array(
        'disabled'       => true,
        'enabled_title'  => '启用',
        'disabled_title' => '已停用',
      ) );

      echo $this->field_before();

      $this->value      = ( ! empty( $this->value ) ) ? $this->value : $this->field['default'];
      $enabled_options  = ( ! empty( $this->value['enabled'] ) ) ? $this->value['enabled'] : array();
      $disabled_options = ( ! empty( $this->value['disabled'] ) ) ? $this->value['disabled'] : array();

      echo ( $args['disabled'] ) ? '<div class="hope-modules">' : '';

      echo ( ! empty( $args['enabled_title'] ) ) ? '<div class="hope-sorter-title">'. esc_attr( $args['enabled_title'] ) .'</div>' : '';
      echo '<ul class="hope-enabled">';
      if ( ! empty( $enabled_options ) ) {
        foreach ( $enabled_options as $key => $value ) {
          echo '<li><input type="hidden" name="'. esc_attr( $this->field_name( '[enabled]['. $key .']' ) ) .'" value="'. esc_attr( $value ) .'"/><label>'. esc_attr( $value ) .'</label></li>';
        }
      }
      echo '</ul>';

      // Check for hide/show disabled section
      if ( $args['disabled'] ) {

        echo '</div>';

        echo '<div class="hope-modules">';
        echo ( ! empty( $args['disabled_title'] ) ) ? '<div class="hope-sorter-title">'. esc_attr( $args['disabled_title'] ) .'</div>' : '';
        echo '<ul class="hope-disabled">';
        if ( ! empty( $disabled_options ) ) {
          foreach ( $disabled_options as $key => $value ) {
          echo '<li><input type="hidden" name="'. esc_attr( $this->field_name( '[disabled]['. $key .']' ) ) .'" value="'. esc_attr( $value ) .'"/><label>'. esc_attr( $value ) .'</label></li>';
          }
        }
        echo '</ul>';
        echo '</div>';

      }

      echo $this->field_after();

    }


  }
}
