<?php 
defined('HOPE_ROOT') || exit('access denied!');

/**
 *
 * Field: backup
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */

if (!class_exists('CSF_Field_backup')) {
  class CSF_Field_backup extends CSF_Fields
  {

    public function __construct($field, $value = '', $unique = '', $where = '', $parent = '')
    {
      parent::__construct($field, $value, $unique, $where, $parent);
    }

    public function render()
    {

      $unique = $this->unique;

      echo $this->field_before();
      echo '<textarea name="hope_import_data" class="hope-import-data" placeholder="粘贴导出的json数据以进行导入"></textarea>';
      echo '<button type="submit" class="btn btn-danger hope-import"><i class="fa fa-cloud-arrow-up me-1"></i>导入设置数据</button><a  class="btn btn-success hope-export ms-1"><i class="fa fa-cloud-arrow-down me-1"></i>导出设置数据</a>';

      echo $this->field_after();
    }
  }
}
