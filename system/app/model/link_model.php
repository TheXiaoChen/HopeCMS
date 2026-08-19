<?php
/**
 * Hope CMS(希望CMS) links model
 * @author LinZiChen <271106735@qq.com>
 * @link http://www.hopecms.cn
 * @copyright 2027 Hope CMS All rights reserved.
 * @license http://www.apache.org/licenses/LICENSE-2.0
*/

class Link_Model {

    private $db;
    private $table;

    function __construct() {
        $this->db = Database::getInstance();
        $this->table = DB_PREFIX . 'link';
    }

    function getLinks() {
        $res = $this->db->query("SELECT * FROM $this->table ORDER BY taxis ASC");
        $links = [];
        while ($row = $this->db->fetch_array($res)) {
            $row['sitename'] = htmlspecialchars($row['sitename']);
            $row['description'] = htmlClean($row['description'], false);
            $links[] = $row;
        }
        return $links;
    }

    function updateLink($linkData, $linkId) {
        $Item = [];
        foreach ($linkData as $key => $data) {
            $Item[] = "$key='$data'";
        }
        $upStr = implode(',', $Item);
        $this->db->query("update $this->table set $upStr where id=$linkId");
    }

    public function addLink($logData) {
        $kItem = $dItem = [];
        foreach ($logData as $key => $data) {
            $kItem[] = $key;
            $dItem[] = $data;
        }
        $field = implode(',', $kItem);
        $values = "'" . implode("','", $dItem) . "'";
        $this->db->query("INSERT INTO $this->table ($field) VALUES ($values)");
        return $this->db->insert_id();
    }

    function deleteLink($linkId) {
        $this->db->query("DELETE FROM $this->table where id=$linkId");
    }
}
