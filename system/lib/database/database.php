<?php
/**
 * Hope CMS(希望CMS) Database operation routing
 * @author LinZiChen <271106735@qq.com>
 * @link http://www.hopecms.cn
 * @copyright 2027 Hope CMS All rights reserved.
 * @license http://www.apache.org/licenses/LICENSE-2.0
 */

class Database {
    public static function getInstance() {
        if (class_exists('mysqli', FALSE)) {
            return MySqlii::getInstance();
        }

        if (class_exists('pdo', false)) {
            return Mysqlpdo::getInstance();
        }

        hpMsg('服务器PHP不支持MySQL数据库');
    }

    /**
     * 获取支持链式操作的查询构建器
     */
    public static function table($table, $alias = '') {
        $db = self::getInstance();
        return new DatabaseQueryBuilder($db, $table, $alias);
    }

    /**
     * 简单查询多条记录
     * $where 支持数组 ['gid'=>1] 或字符串 "gid=1 AND hide='n'"
     */
    public static function select($table, $where = []) {
        $query = self::table($table);
        if (is_string($where) && $where !== '') {
            $query->where($where);
        } elseif (!empty($where)) {
            $query->where($where);
        }
        return $query->findAll();
    }

    /**
     * 执行原生 SQL（写操作）
     */
    public static function execute($sql, $ignoreError = false) {
        return self::getInstance()->query($sql, $ignoreError);
    }

    /**
     * 查询多行
     */
    public static function fetchAll($sql) {
        $db = self::getInstance();
        $result = $db->query($sql);
        $rows = [];
        while ($row = $db->fetch_array($result)) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * 查询单行
     */
    public static function fetchOne($sql) {
        return self::getInstance()->once_fetch_array($sql);
    }

    /**
     * 执行 SQL 文件（备份恢复 / 手工迁移等）
     */
    public static function executeFile($file, $options = []) {
        return SqlRunner::executeFile($file, $options);
    }

    /**
     * 简单查询单条记录
     */
    public static function getOne($table, $where) {
        return self::table($table)
            ->where($where)
            ->find();
    }

    /**
     * 简单插入数据
     */
    public static function insert($table, $data) {
        return self::table($table)->insert($data);
    }

    /**
     * 简单更新数据
     */
    public static function update($table, $where, $data) {
        return self::table($table)
            ->where($where)
            ->update($data);
    }

    /**
     * 简单删除数据
     */
    public static function delete($table, $where) {
        return self::table($table)
            ->where($where)
            ->delete();
    }

    /**
     * 计算记录数
     */
    public static function count($table, $where = []) {
        $query = self::table($table);
        if (!empty($where)) {
            $query->where($where);
        }
        return $query->count();
    }

    /**
     * 转义字符串（用于少量 whereRaw 场景，优先使用链式 where / whereLike）
     */
    public static function escape($value) {
        return self::getInstance()->escape_string($value);
    }
}