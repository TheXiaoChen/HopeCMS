<?php defined('HOPE_ROOT') || exit('access denied!');
 // Cannot access directly.
/**
 *
 * Field: code_editor
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if ( ! class_exists( 'CSF_Field_code_editor' ) ) {
  class CSF_Field_code_editor extends CSF_Fields {
    public $version = '5.65.16';
    public $cdn_url = SITE_URL . 'content/admin/assets/codemirror/';

    public function __construct( $field, $value = '', $unique = '', $where = '', $parent = '' ) {
      parent::__construct( $field, $value, $unique, $where, $parent );
    }

    public function render() {

      $default_settings = array(
        'tabSize'       => 2,
        'lineNumbers'   => true,
        'theme'         => 'default',
        'mode'          => 'htmlmixed',
        'cdnURL'        => $this->cdn_url . $this->version,
      );

      $settings = ( ! empty( $this->field['settings'] ) ) ? $this->field['settings'] : array();
      $settings = hope_parse_args( $settings, $default_settings );

      echo $this->field_before();
      echo '<textarea name="'. esc_attr( $this->field_name() ) .'"'. $this->field_attributes() .' data-editor="'. esc_attr( json_encode( $settings ) ) .'">'. $this->value .'</textarea>';
      echo $this->field_after();

    }
      public function enqueue()
      {
          $main_css = $this->cdn_url . $this->version .'/lib/codemirror.css';
          $main_js = $this->cdn_url . $this->version .'/lib/codemirror.js';
          $load_js = $this->cdn_url . $this->version .'/addon/mode/loadmode.js';
          echo '<link href="' . $main_css . '" id="hope-codemirror-css" type="text/css" rel="stylesheet"/>';
          echo '<script src="' . $main_js . '"></script>';
          echo '<script src="' . $load_js . '"></script>';
      }
  }
}
