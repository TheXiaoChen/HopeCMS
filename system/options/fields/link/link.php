<?php defined('HOPE_ROOT') || exit('access denied!');
/**
 *
 * Field: link
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if (!class_exists('CSF_Field_link')) {
  class CSF_Field_link extends CSF_Fields
  {

    public function __construct($field, $value = '', $unique = '', $where = '', $parent = '')
    {
      parent::__construct($field, $value, $unique, $where, $parent);
    }

    public function render()
    {

      $args = hope_parse_args($this->field, array(
        'add_title'    => '新增链接',
        'edit_title'   => '编辑链接',
        'remove_title' => '移除链接',
      ));

      $default_values = array(
        'url'    => '',
        'text'  => '',
        'target' => '',
      );

      $value = hope_parse_args($this->value, $default_values);

      $hidden = (!empty($value['url']) || !empty($value['url']) || !empty($value['url'])) ? ' hidden' : '';

      $maybe_hidden = (empty($hidden)) ? ' hidden' : '';

      echo $this->field_before();

      echo '<textarea readonly="readonly" class="hope--link hidden"></textarea>';

      echo '<div class="' . esc_attr($maybe_hidden) . '"><div class="hope--result">' . sprintf('{url:"%s", text:"%s", target:"%s"}', $value['url'], $value['text'], $value['target']) . '</div></div>';

      echo '<input type="text" name="' . esc_attr($this->field_name('[url]')) . '" value="' . esc_attr($value['url']) . '"' . $this->field_attributes(array('class' => 'hope--url hidden')) . ' />';
      echo '<input type="text" name="' . esc_attr($this->field_name('[text]')) . '" value="' . esc_attr($value['text']) . '" class="hope--text hidden" />';
      echo '<input type="text" name="' . esc_attr($this->field_name('[target]')) . '" value="' . esc_attr($value['target']) . '" class="hope--target hidden" />';

      echo '<a href="#" class="btn btn-primary hope-link-add' . esc_attr($hidden) . '">' . $args['add_title'] . '</a> ';
      echo '<a href="#" class="btn btn-secondary hope--edit' . esc_attr($maybe_hidden) . '">' . $args['edit_title'] . '</a> ';
      echo '<a href="#" class="btn btn-danger hope--remove' . esc_attr($maybe_hidden) . '">' . $args['remove_title'] . '</a>';

      echo $this->field_after();
    }

    public function enqueue()
    {
      self::hope_link_modal();
    }

    public static function hope_link_modal()
    {
        ?>
        <div id="hope-modal-link" class="hope-modal hope-modal-link hidden">
            <div class="hope-modal-table">
                <div class="hope-modal-table-cell">
                    <div class="hope-modal-overlay"></div>
                    <div class="hope-modal-inner">
                        <div class="hope-modal-title">
                            <?php echo '插入或编辑链接' ?>
                            <div class="hope-modal-close hope-icon-close"></div>
                        </div>
                        <div id="link-selector">
                            <div id="link-options">
                                <div>
                                    <label>
                                        <span>链接地址</span>
                                        <input id="hope-link-url" type="text">
                                    </label>
                                </div>
                                <div class="hope-link-text-field">
                                    <label>
                                        <span>链接文字</span>
                                        <input id="hope-link-text" type="text">
                                    </label>
                                </div>
                                <div class="link-target hope-fieldset">
                                    <label>
                                        <input type="checkbox" id="hope-link-target"> 在新标签页中打开链接
                                    </label>
                                </div>
                                <div class="hope-fieldset">
                                    <label>
                                        <span>搜索链接</span>
                                        <input type="text" id="hope-link-search" placeholder="输入标题关键字搜索...">
                                    </label>
                                </div>
                            </div>
                            <div id="most-recent-results" class="query-results" tabindex="0">
                                <ul>
                                    <?php
                                    $db = Database::getInstance();
                                    $query = $db->query("SELECT gid,title,type,date FROM " . DB_PREFIX . "article WHERE hide='n' AND checked='y' ORDER By date DESC");
                                    while ($value = $db->fetch_array($query)) {
                                        $new = $value['date'] >= strtotime('-3 days') ? '<small class="new-tip">NEW</small>' : '';
                                        $log_type = $value['type'] == 'blog' ? '<span class="item-info">文章</span>' : '<span class="item-info">页面</span>';
                                        echo '<li>';
                                        echo '<input type="hidden" class="item-permalink" value="' . Url::log($value['gid']) . '">';
                                        echo '<span class="item-title">' . htmlspecialchars($value['title']) . $new .'</span>';
                                        echo $log_type;
                                        echo '</li>';
                                    }
                                    ?>
                                </ul>
                            </div>
                        </div>
                        <div class="submitbox">
                            <div id="link-cancel">
                                <button type="button" class="btn btn-warning">取消</button>
                            </div>
                            <div id="link-update">
                                <button type="button" class="btn btn-primary" id="hope-link-submit">添加链接</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
  }
}
