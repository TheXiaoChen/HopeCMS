<?php
/**
 * Hope CMS(希望CMS) Upload model
 * @author LinZiChen <271106735@qq.com>
 * @link http://www.hopecms.cn
 * @copyright 2027 Hope CMS All rights reserved.
 * @license http://www.apache.org/licenses/LICENSE-2.0
*/

class Upload_Model {

    private $db;
    private $table;
    private $table_sort;

    function __construct() {
        $this->db = Database::getInstance();
        $this->table = DB_PREFIX . 'upload';
        $this->table_sort = DB_PREFIX . 'upload_sort';
    }

    function getMedias($page = 1, $perpage_count = 24, $uid = 0, $sid = 0, $date = '', $keyword = '') {
        $startId = ($page - 1) * $perpage_count;
        $author = $uid ? 'and author=' . $uid : '';
        $sort = $sid ? 'and sortid=' . $sid : '';
        $partsA = explode(' to ', $date);
        if (count($partsA) === 2) {
            $startDate = trim($partsA[0]);
            $endDate = trim($partsA[1]);
            $date = 'and addtime >= ' . strtotime($startDate . ' 00:00:00') . ' and addtime <= ' . strtotime($endDate . ' 23:59:59');
        } else {
            $date = $date ? 'and addtime >= ' . strtotime($date . ' 00:00:00') . ' and addtime <= ' . strtotime($date . ' 23:59:59') : '';
        }
        $keyword = $keyword ? 'and filename like "%' . $keyword . '%"' : '';
        $limit = "LIMIT $startId, " . $perpage_count;

        $sql = "SELECT * FROM $this->table m LEFT JOIN $this->table_sort s ON m.sortid=s.id WHERE 1 = 1 $author $sort $date $keyword order by m.aid desc $limit";
        $query = $this->db->query($sql);
        $medias = [];
        while ($row = $this->db->fetch_array($query)) {
            $medias[$row['aid']] = $this->fetchMediaData($row);
        }
        return $medias;
    }

    function getMediaCount($uid = null, $sid = null, $date = '', $keyword = '') {
        $author = $uid ? 'and author=' . $uid : '';
        $sort = $sid ? 'and sortid=' . $sid : '';
        $partsA = explode(' to ', $date);
        if (count($partsA) === 2) {
            $startDate = trim($partsA[0]);
            $endDate = trim($partsA[1]);
            $date = 'and addtime >= ' . strtotime($startDate . ' 00:00:00') . ' and addtime <= ' . strtotime($endDate . ' 23:59:59');
        } else {
            $date = $date ? 'and addtime >= ' . strtotime($date . ' 00:00:00') . ' and addtime <= ' . strtotime($date . ' 23:59:59') : '';
        }
        $keyword = $keyword ? 'and filename like "%' . $keyword . '%"' : '';
        $sql = "SELECT count(*) as count FROM $this->table WHERE 1 = 1 $author $sort $date $keyword";
        $res = $this->db->once_fetch_array($sql);
        return $res['count'];
    }

    function getDetailByAlias($alias) {
        if (empty($alias)) {
            return false;
        }
        $sql = sprintf("SELECT * FROM $this->table WHERE alias = '%s'", $alias);
        $row = $this->db->once_fetch_array($sql);
        if (empty($row)) {
            return false;
        }
        return $this->fetchMediaData($row);
    }

    function getDetail($id) {
        if (empty($id)) {
            return false;
        }
        $sql = sprintf("SELECT * FROM $this->table WHERE aid = '%d'", $id);
        $row = $this->db->once_fetch_array($sql);
        if (empty($row)) {
            return false;
        }
        return $this->fetchMediaData($row);
    }

    private function fetchMediaData($row) {
        return [
            'alias'          => $row['alias'],
            'attsize'        => changeFileSize($row['filesize']),
            'filename'       => htmlspecialchars($row['filename']),
            'addtime'        => date("Y-m-d H:i:s", $row['addtime']),
            'aid'            => $row['aid'],
            'filepath_thum'  => $row['filepath'],
            'filepath'       => str_replace("thum-", '', $row['filepath']),
            'file_url'       => getFileUrl($row['filepath']),
            'width'          => $row['width'],
            'height'         => $row['height'],
            'mimetype'       => $row['mimetype'],
            'author'         => $row['author'],
            'sortid'         => $row['sortid'],
            'sortname'       => htmlspecialchars($row['sortname'] ?? ''),
            'download_count' => $row['download_count'],
        ];
    }

    function addMedia($file_info, $sortid, $uid = UID) {
        $file_path = $file_info['file_path'];
        if (isset($file_info['thum_file'])) {
            $file_path = $file_info['thum_file'];
        }
        $insertId = Database::table('upload')->insert([
            'alias'    => getRandStr(16, false),
            'author'   => (int)$uid,
            'sortid'   => (int)$sortid,
            'filename' => (string)$file_info['file_name'],
            'filesize' => (int)$file_info['size'],
            'filepath' => (string)$file_path,
            'addtime'  => time(),
            'width'    => (int)$file_info['width'],
            'height'   => (int)$file_info['height'],
            'mimetype' => (string)$file_info['mime_type'],
        ]);
        return $insertId ? (int)$insertId : 0;
    }

    function deleteMedia($media_id) {
        $author = User::haveEditPermission() ? '' : 'and author=' . UID;
        $query = $this->db->query("SELECT * FROM $this->table WHERE aid = $media_id $author");
        $attach = $this->db->fetch_array($query);
        if (empty($attach)) {
            return false;
        }
        $filepath = $attach['filepath'];
        if (stripos($filepath, 'http') !== 0) {
            $filepath = str_replace("../", '', $filepath);
            if (file_exists($filepath)) {
                @unlink($filepath) or hpMsg("删除失败!");
            }
        }
        doAction('del_media', $filepath);

        return $this->db->query("DELETE FROM $this->table WHERE aid = $media_id $author");
    }

    function updateMedia($data, $media_id) {
        $author = User::haveEditPermission() ? '' : 'and author=' . UID;
        $Item = [];
        foreach ($data as $key => $val) {
            $Item[] = "$key='$val'";
        }
        $upStr = implode(',', $Item);
        $this->db->query("UPDATE $this->table SET $upStr WHERE aid=$media_id $author");
    }

    function incrDownloadCount($media_id) {
        $this->db->query("UPDATE $this->table SET download_count=download_count+1 WHERE aid=$media_id");
    }

}
