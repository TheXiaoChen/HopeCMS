<?php defined('HOPE_ROOT') || exit('access denied!');
 // Cannot access directly.
/**
 *
 * Field: sortable
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if ( ! class_exists( 'CSF_Field_sortable' ) ) {
  class CSF_Field_sortable extends CSF_Fields {

    public function __construct( $field, $value = '', $unique = '', $where = '', $parent = '' ) {
      parent::__construct( $field, $value, $unique, $where, $parent );
    }

    public function render() {

      echo $this->field_before();

      echo '<div class="hope-sortable">';

      $pre_sortby = array();
      $pre_fields = array();

      // Add array-keys to defined fields for sort by
      foreach ( $this->field['fields'] as $key => $field ) {
        $pre_fields[$field['id']] = $field;
      }

      // Set sort by by saved-value or default-value
      if ( ! empty( $this->value ) ) {

        foreach ( $this->value as $key => $value ) {
          $pre_sortby[$key] = $pre_fields[$key];
        }

        $diff = array_diff_key( $pre_fields, $this->value );

        if( ! empty( $diff ) ) {
          $pre_sortby = array_merge( $pre_sortby, $diff );
        }

      } else {

        foreach ( $pre_fields as $key => $value ) {
          $pre_sortby[$key] = $value;
        }

      }

      foreach ( $pre_sortby as $key => $field ) {

        echo '<div class="hope-sortable-item">';

          echo '<div class="hope-sortable-content">';

          $field_default = ( isset( $this->field['default'][$key] ) ) ? $this->field['default'][$key] : '';
          $field_value   = ( isset( $this->value[$key] ) ) ? $this->value[$key] : $field_default;
          $unique_id     = ( ! empty( $this->unique ) ) ? $this->unique .'['. $this->field['id'] .']' : $this->field['id'];

          CSF::field( $field, $field_value, $unique_id, 'field/sortable' );

          echo '</div>';

          echo '<div class="hope-sortable-helper"><i class="fas fa-arrows-alt"></i></div>';

        echo '</div>';

      }

      echo '</div>';

      echo $this->field_after();

    }
  }
}
