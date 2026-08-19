<?php defined('HOPE_ROOT') || exit('access denied!');
 // Cannot access directly.
/**
 *
 * Field: date
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if ( ! class_exists( 'CSF_Field_date' ) ) {
  class CSF_Field_date extends CSF_Fields {

    public function __construct( $field, $value = '', $unique = '', $where = '', $parent = '' ) {
      parent::__construct( $field, $value, $unique, $where, $parent );
    }

    public function render() {

      $default_settings = array(
        'dateFormat' => 'yy/mm/dd',
      );

      $settings = ( ! empty( $this->field['settings'] ) ) ? $this->field['settings'] : array();
      $settings = hope_parse_args( $settings, $default_settings );

      echo $this->field_before();

      if ( ! empty( $this->field['from_to'] ) ) {

        $args = hope_parse_args( $this->field, array(
          'text_from' => '从',
          'text_to'   => '到',
        ) );

        $value = hope_parse_args( $this->value, array(
          'from' => '',
          'to'   => '',
        ) );

        echo '<label class="hope--from">'. esc_attr( $args['text_from'] ) .' <input type="text" name="'. esc_attr( $this->field_name( '[from]' ) ) .'" value="'. esc_attr( $value['from'] ) .'"'. $this->field_attributes() .'/></label>';
        echo '<label class="hope--to">'. esc_attr( $args['text_to'] ) .' <input type="text" name="'. esc_attr( $this->field_name( '[to]' ) ) .'" value="'. esc_attr( $value['to'] ) .'"'. $this->field_attributes() .'/></label>';

      } else {

        echo '<input type="text" name="'. esc_attr( $this->field_name() ) .'" value="'. esc_attr( $this->value ) .'"'. $this->field_attributes() .'/>';

      }

      echo '<div class="hope-date-settings" data-settings="'. esc_attr( json_encode( $settings ) ) .'"></div>';

      echo $this->field_after();

    }
  }
}
