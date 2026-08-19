<?php defined('HOPE_ROOT') || exit('access denied!');
 // Cannot access directly.
/**
 *
 * Field: upload
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if ( ! class_exists( 'CSF_Field_upload' ) ) {
  class CSF_Field_upload extends CSF_Fields {

    private static $modal_enqueued = false;

    public function __construct( $field, $value = '', $unique = '', $where = '', $parent = '' ) {
      parent::__construct( $field, $value, $unique, $where, $parent );
    }

    public function render() {

      $args = hope_parse_args($this->field, array(
          'preview' => false,
          'preview_width' => '',
          'preview_height' => '',
          'button_title' => '上传',
          'remove_title' => '删除',
      ));
      
      echo $this->field_before();

      $hidden  = ( empty( $this->value ) ) ? ' hidden' : '';
      if (!empty($args['preview'])) {
          if(is_array($this->value)){
              $preview_type = '';
          }else{
              $preview_type = (!empty($this->value)) ? strtolower(substr(strrchr($this->value, '.'), 1)) : '';
          }
          $preview_src = (!empty($preview_type) && in_array($preview_type, array('jpg', 'jpeg', 'gif', 'png', 'svg', 'webp'))) ? $this->value : '';
          $preview_width = (!empty($args['preview_width'])) ? 'max-width:' . esc_attr($args['preview_width']) . 'px;' : '';
          $preview_height = (!empty($args['preview_height'])) ? 'max-height:' . esc_attr($args['preview_height']) . 'px;' : '';
          $preview_style = (!empty($preview_width) || !empty($preview_height)) ? ' style="' . esc_attr($preview_width . $preview_height) . '"' : '';
          $preview_hidden = (empty($preview_src)) ? ' hidden' : '';

          echo '<div class="hope--preview' . esc_attr($preview_hidden) . '">';
          echo '<div class="hope-image-preview">';
          echo '<i class="hope--remove fa fa-rectangle-xmark"></i><span><img ' . $preview_style . ' src="' . esc_url($preview_src) . '" class="hope--src" /></span>';
          echo '</div>';
          echo '</div>';
      }
      echo '<div class="hope--wrap">';
      echo '<input type="text" name="' . esc_attr($this->field_name()) . '" value="' . esc_attr($this->value) . '"' . $this->field_attributes() . '/>';
      echo '<a href="#" class="btn btn-primary hope--button hope-upload-img">' . $args['button_title'] . '</a>';
      echo '<a href="#" class="btn btn-danger hope-warning-primary hope--remove' . esc_attr($hidden) . '">' . $args['remove_title'] . '</a>';
      echo '</div>';

      echo $this->field_after();
    }

    public function enqueue() {
      if (self::$modal_enqueued) {
        return;
      }
      self::$modal_enqueued = true;

      $UploadSort_Model = new UploadSort_Model();
      $mediaSorts = $UploadSort_Model->getSorts();
      ?>

      <div class="modal fade" id="hope_mediaModal" tabindex="-1" aria-labelledby="hopeMediaModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-scrollable modal-xl modal-dialog-centered" role="document">
              <div class="modal-content hope-media-modal-content border-0 shadow">
                  <div class="modal-header hope-media-modal-header">
                      <h5 class="modal-title" id="hopeMediaModalLabel"><i class="fa fa-folder-open me-2 text-primary"></i>资源库</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body hope-media-modal-body">
                      <div id="upload-progress" class="progress hope-media-progress d-none mb-3">
                          <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                      </div>
                      <div class="hope-media-toolbar">
                          <label for="hope-img-input" class="btn btn-sm btn-success mb-0">
                              <i class="fa fa-cloud-arrow-up me-1"></i>上传附件
                          </label>
                          <input id="hope-img-input" class="d-none hope-img-input" type="file" name="image"
                                  accept="image/gif,image/jpeg,image/jpg,image/png,image/webp,image/bmp,image/svg+xml">
                          <div class="hope-media-toolbar-right">
                              <?php if (User::haveEditPermission()): ?>
                                  <select class="form-select form-select-sm" id="hope-media-sort-select">
                                      <option value="">全部分类</option>
                                      <?php foreach (($mediaSorts ?? []) as $v): ?>
                                          <option value="<?= (int)$v['id'] ?>"><?= htmlspecialchars($v['sortname']) ?></option>
                                      <?php endforeach ?>
                                  </select>
                              <?php endif ?>
                              <div class="hope-media-search">
                                  <input type="search" class="form-control form-control-sm" id="hope-img-search" placeholder="搜索资源名称…" autocomplete="off">
                              </div>
                          </div>
                      </div>
                      <div class="row g-3" id="hope-image-list"></div>
                      <div id="hope-media-empty" class="hope-media-empty d-none">
                          <i class="fa fa-images"></i>
                          <p>暂无资源，点击上方「上传附件」添加</p>
                      </div>
                      <div class="text-center hope-media-more">
                          <button type="button" class="btn btn-outline-primary btn-sm" id="hope-load-more">
                              <i class="fa fa-spinner d-none"></i> 加载更多
                          </button>
                      </div>
                  </div>
              </div>
          </div>
      </div>
      
      <?php
    }
  }
}
