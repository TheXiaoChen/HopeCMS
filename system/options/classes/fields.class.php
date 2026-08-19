<?php 
defined('HOPE_ROOT') || exit('access denied!');
/**
 *
 * Fields Class
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if (!class_exists('CSF_Fields')) {
    abstract class CSF_Fields extends CSF_Abstract
    {

        //添加声明，否则PHP 8.2 会报错
        public $field  = '';
        public $value  = '';
        public $unique = '';

        public function __construct($field = array(), $value = '', $unique = '')
        {
            $this->field = $field;
            $this->value = $value;
            $this->unique = $unique;
        }

        public function field_name($nested_name = '')
        {

            $field_id   = (!empty($this->field['id'])) ? $this->field['id'] : '';
            $unique_id  = (!empty($this->unique)) ? $this->unique . '[' . $field_id . ']' : $field_id;
            $field_name = (!empty($this->field['name'])) ? $this->field['name'] : $unique_id;
            $tag_prefix = (!empty($this->field['tag_prefix'])) ? $this->field['tag_prefix'] : '';

            if (!empty($tag_prefix)) {
                $nested_name = str_replace('[', '[' . $tag_prefix, $nested_name);
            }
            return $field_name . $nested_name;
        }

        public function field_attributes($custom_atts = array())
        {

            $field_id   = (!empty($this->field['id'])) ? $this->field['id'] : '';
            $attributes = (!empty($this->field['attributes'])) ? $this->field['attributes'] : array();

            if (!empty($field_id) && empty($attributes['data-depend-id'])) {
                $attributes['data-depend-id'] = $field_id;
            }

            if (!empty($this->field['placeholder'])) {
                $attributes['placeholder'] = $this->field['placeholder'];
            }

            $attributes = hope_parse_args($attributes, $custom_atts);

            $atts = '';

            if (!empty($attributes)) {
                foreach ($attributes as $key => $value) {
                    if ($value === 'only-key') {
                        $atts .= ' ' . esc_attr($key);
                    } else {
                        $atts .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
                    }
                }
            }

            return $atts;
        }

        public function field_before()
        {
            return (!empty($this->field['before'])) ? '<div class="hope-before-text">' . $this->field['before'] . '</div>' : '';
        }

        public function field_after()
        {

            $output = (!empty($this->field['after'])) ? '<div class="hope-after-text">' . $this->field['after'] . '</div>' : '';
            $output .= (!empty($this->field['desc'])) ? '<div class="clear"></div><div class="hope-desc-text">' . $this->field['desc'] . '</div>' : '';
            $output .= (!empty($this->field['help'])) ? '<div class="hope-help"><span class="hope-help-text">' . $this->field['help'] . '</span><i class="fas fa-question-circle"></i></div>' : '';
            $output .= (!empty($this->field['_error'])) ? '<div class="hope-error-text">' . $this->field['_error'] . '</div>' : '';

            return $output;
        }

        public static function field_data($type = '', $term = false, $query_args = array())
        {

            $options      = array();
            $array_search = false;
            $db = Database::getInstance();

            // sanitize type name
            if (in_array($type, array('page', 'pages'))) {
                $option = 'page';
            } else if (in_array($type, array('post', 'posts'))) {
                $option = 'blog';
            } else if (in_array($type, array('category', 'categories'))) {
                $option = 'category';
            } else if (in_array($type, array('tag', 'tags'))) {
                $option = 'post_tag';
            } else if (in_array($type, array('menu', 'menus'))) {
                $option = 'nav_menu';
            } else {
                $option = 'blog';
            }

            // switch type
            switch ($type) {

                case 'blog':
                case 'blogs':
                case 'page':
                case 'pages':
                case 'post':
                case 'posts':

                    if (!empty($term)) {
                        $query = hope_sql_select('gid,title', 'article', "hide='n' AND checked='y' AND type='$option' AND (title like '%$term%' OR content like '%$term%') ORDER BY gid DESC");
                    } else {
                        $query = hope_sql_select('gid,title', 'article', "hide='n' AND checked='y' AND type='$option' ORDER BY gid DESC");
                    }
                    if (!empty($query)) {
                        while ($data = $db->fetch_array($query)) {
                            $options[$data['gid']] = $data['title'];
                        }
                    }
                    break;

                case 'sort':
                case 'sorts':
                case 'category':
                case 'categories':
                    if (!empty($term)) {
                        $query = hope_sql_select('sid,sortname', 'sort', "sortname like '%$term%'");
                    } else {
                        $query = hope_sql_select('sid,sortname', 'sort');
                    }
                    if (!empty($query)) {
                        while ($data = $db->fetch_array($query)) {
                            $options[$data['sid']] = $data['sortname'];
                        }
                    }
                    break;
                case 'tag':
                case 'tags':
                    if (!empty($term)) {
                        $query = hope_sql_select('tid,tagname', 'tag', "tagname like '%$term%'");
                    } else {
                        $query = hope_sql_select('tid,tagname', 'tag');
                    }
                    if (!empty($query)) {
                        while ($data = $db->fetch_array($query)) {
                            $options[$data['tid']] = $data['tagname'];
                        }
                    }
                    break;
                case 'user':
                case 'users':
                    if (!empty($term)) {
                        $query = hope_sql_select('uid,username', 'user', "nickname like '%$term%' OR username like '%$term%' OR email like '%$term%'");
                    } else {
                        $query = hope_sql_select('uid,username', 'user');
                    }
                    if (!empty($query)) {
                        while ($data = $db->fetch_array($query)) {
                            $options[$data['uid']] = $data['username'];
                        }
                    }
                    break;
                case 'menu':
                case 'menus':
                    global $CACHE;
                    $menu_cache = $CACHE->readCache('menu');
                    if (!is_array($menu_cache)) {
                        break;
                    }
                    $Menu_Model = new Menu_Model();
                    $menuItems = $Menu_Model->getMenus($menu_cache, 'lord');
                    $flattenMenuOptions = function ($items, $prefix = '') use (&$flattenMenuOptions, &$options) {
                        foreach ($items as $item) {
                            $id = $item['id'] ?? 0;
                            $name = $item['name'] ?? '';
                            if ($id && $name !== '') {
                                $options[$id] = $prefix . $name;
                            }
                            if (!empty($item['child']) && is_array($item['child'])) {
                                $flattenMenuOptions($item['child'], $prefix . '— ');
                            }
                        }
                    };
                    $flattenMenuOptions($menuItems);
                    break;
                default:
                    if (is_callable($type)) {
                        if (!empty($term)) {
                            $options = call_user_func($type, $query_args);
                        } else {
                            $options = call_user_func($type, $term, $query_args);
                        }
                    }

                    break;
            }

            // Array search by "term"
            if (!empty($term) && !empty($options) && !empty($array_search)) {
                $options = preg_grep('/' . $term . '/i', $options);
            }

            // 制作用于ajax搜索的多维数组
            if (!empty($term) && !empty($options)) {
                $arr = array();
                foreach ($options as $option_key => $option_value) {
                    $arr[] = array('value' => $option_key, 'text' => $option_value);
                }
                $options = $arr;
            }

            return $options;
        }

        public function field_query_data_title($type, $values)
        {

            $options = array();
            if (!empty($values) && is_array($values)) {
                foreach ($values as $value) {
                    switch ($type) {
                        case 'blog':
                        case 'post':
                        case 'posts':
                        case 'page':
                        case 'pages':

                            $query = hope_sql_select('gid,title', 'article', "gid='$value'", true);
                            if (!empty($query)) {
                                $options[$value] = $query['title'];
                            }
                            break;
                        case 'category':
                        case 'categories':
                        case 'sort':
                        case 'sorts':
                            $query = hope_sql_select('sid,sortname', 'sort', "sid='$value'", true);
                            if (!empty($query)) {
                                $options[$value] = $query['sortname'];
                            }
                            break;
                        case 'tag':
                        case 'tags':
                            $query = hope_sql_select('tid,tagname', 'tag', "tid='$value'", true);
                            if (!empty($query)) {
                                $options[$value] = $query['tagname'];
                            }
                            break;
                        case 'user':
                        case 'users':
                            $query = hope_sql_select('uid,username', 'user', "uid='$value'", true);
                            if (!empty($query)) {
                                $options[$value] = $query['username'];
                            }
                            break;
                        default:

                            if (is_callable($type . '_title')) {
                                $options[$value] = call_user_func($type . '_title', $value);
                            } else {
                                $options[$value] = ucfirst($value);
                            }

                            break;
                    }
                }
            }

            return $options;
        }
    }
}
