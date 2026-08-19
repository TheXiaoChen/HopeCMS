<?php defined('HOPE_ROOT') || exit('access denied!');
 // Cannot access directly.
/**
 *
 * Field: icon
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if ( ! class_exists( 'CSF_Field_icon' ) ) {
  class CSF_Field_icon extends CSF_Fields {

    public function __construct( $field, $value = '', $unique = '', $where = '', $parent = '' ) {
      parent::__construct( $field, $value, $unique, $where, $parent );
    }

    public function render() {

      $args = hope_parse_args( $this->field, array(
        'button_title' => '新增图标',
        'remove_title' => '移除图标',
      ) );

      echo $this->field_before();

      $hidden = ( empty( $this->value ) ) ? ' hidden' : '';

      echo '<div class="hope-icon-select">';
      echo '<span class="hope-icon-preview'. esc_attr( $hidden ) .'"><i class="'. esc_attr( $this->value ) .'"></i></span>';
      echo '<a href="#" class="btn btn-primary hope-icon-add">'. $args['button_title'] .'</a>';
      echo '<a href="#" class="btn hope-warning-primary hope-icon-remove'. esc_attr( $hidden ) .'">'. $args['remove_title'] .'</a>';
      echo '<input type="text" name="'. esc_attr( $this->field_name() ) .'" value="'. esc_attr( $this->value ) .'" class="hope-icon-value"'. $this->field_attributes() .' />';
      echo '</div>';

      echo $this->field_after();

    }

    public function enqueue() {
      self::add_footer_modal_icon();
    }

    public function add_footer_modal_icon() {
    ?>
      <div id="hope-modal-icon" class="hope-modal hope-modal-icon hidden">
        <div class="hope-modal-table">
          <div class="hope-modal-table-cell">
            <div class="hope-modal-overlay"></div>
            <div class="hope-modal-inner">
              <div class="hope-modal-title">
                新增图标
                <div class="hope-modal-close hope-icon-close"></div>
              </div>
              <div class="hope-modal-header">
                <input type="text" placeholder="搜索..." class="hope-icon-search" />
              </div>
              <div class="hope-modal-content">
                <div class="hope-modal-loading"><div class="hope-loading"></div></div>
                <div class="hope-modal-load"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php
    }

  }
}
