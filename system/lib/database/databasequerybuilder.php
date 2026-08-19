<?php
/**
 * 数据库查询构建器
 * 支持链式 WHERE、JOIN、IN、LIKE 等
 */
class DatabaseQueryBuilder {
    private $db;
    private $table;
    private $tableAlias = '';
    private $select_fields = '*';
    private $joins = [];
    private $where_parts = [];
    private $order_clause = '';
    private $limit_clause = '';
    private $group_clause = '';

    public function __construct($db, $table, $alias = '') {
        $this->db = $db;
        $this->table = DB_PREFIX . $table;
        $this->tableAlias = $alias;
    }

    public function select($fields) {
        if (is_array($fields)) {
            $this->select_fields = implode(',', array_map(function ($field) {
                return strpos($field, '`') !== false || strpos($field, '.') !== false ? $field : "`{$field}`";
            }, $fields));
        } else {
            $this->select_fields = $fields;
        }
        return $this;
    }

    public function where($condition) {
        if (is_array($condition)) {
            $this->where_parts[] = $this->buildWhereFromArray($condition);
        } elseif ($condition !== '' && $condition !== null) {
            $this->where_parts[] = $condition;
        }
        return $this;
    }

    public function whereRaw($sql) {
        if ($sql !== '' && $sql !== null) {
            $this->where_parts[] = $sql;
        }
        return $this;
    }

    public function whereIn($field, $values) {
        $ints = array_values(array_unique(array_filter(array_map('intval', (array)$values), function ($v) {
            return $v > 0;
        })));
        $col = strpos($field, '.') !== false
            ? '`' . implode('`.`', explode('.', $field)) . '`'
            : "`{$field}`";
        if (empty($ints)) {
            $this->where_parts[] = '1=0';
        } else {
            $this->where_parts[] = "{$col} IN (" . implode(',', $ints) . ')';
        }
        return $this;
    }

    public function whereLike($field, $value) {
        $escaped = $this->db->escape_string($value);
        $escaped = str_replace(['%', '_'], ['\%', '\_'], $escaped);
        $this->where_parts[] = "`{$field}` LIKE '%{$escaped}%'";
        return $this;
    }

    public function join($table, $alias, $on, $type = 'INNER') {
        $prefixed = strpos($table, DB_PREFIX) === 0 ? $table : DB_PREFIX . $table;
        $this->joins[] = [
            'type'  => strtoupper($type),
            'table' => $prefixed,
            'alias' => $alias,
            'on'    => $on,
        ];
        return $this;
    }

    public function leftJoin($table, $alias, $on) {
        return $this->join($table, $alias, $on, 'LEFT');
    }

    public function order($order) {
        $this->order_clause = $order;
        return $this;
    }

    public function limit($limit, $offset = 0) {
        $limit = (int)$limit;
        $offset = (int)$offset;
        $this->limit_clause = $offset > 0 ? "{$offset},{$limit}" : (string)$limit;
        return $this;
    }

    public function group($group) {
        $this->group_clause = $group;
        return $this;
    }

    public function findAll() {
        $result = $this->db->query($this->buildSelectSql());
        $data = [];
        while ($row = $this->db->fetch_array($result)) {
            $data[] = $row;
        }
        $this->reset();
        return $data;
    }

    public function find() {
        $this->limit(1);
        $data = $this->findAll();
        return !empty($data) ? $data[0] : null;
    }

    public function insert($data) {
        $fields = array_keys($data);
        $escaped_values = array_map([$this->db, 'escape_string'], array_values($data));
        $field_str = '`' . implode('`,`', $fields) . '`';
        $value_str = "'" . implode("','", $escaped_values) . "'";
        $sql = "INSERT INTO `{$this->table}` ({$field_str}) VALUES ({$value_str})";
        $result = $this->db->query($sql);
        $this->reset();
        return $result ? $this->db->insert_id() : false;
    }

    public function update($data) {
        $set_parts = [];
        foreach ($data as $field => $value) {
            $escaped_value = $this->db->escape_string($value);
            $set_parts[] = "`{$field}` = '{$escaped_value}'";
        }
        $sql = "UPDATE `{$this->table}` SET " . implode(', ', $set_parts);
        if ($this->where_parts) {
            $sql .= ' WHERE ' . implode(' AND ', $this->where_parts);
        }
        $result = $this->db->query($sql);
        $this->reset();
        return $result ? $this->db->affected_rows() : false;
    }

    public function delete() {
        $sql = "DELETE FROM `{$this->table}`";
        if ($this->where_parts) {
            $sql .= ' WHERE ' . implode(' AND ', $this->where_parts);
        } else {
            $sql .= ' WHERE 1=1';
        }
        $result = $this->db->query($sql);
        $this->reset();
        return $result ? $this->db->affected_rows() : false;
    }

    public function count() {
        if ($this->joins || $this->tableAlias !== '') {
            $sql = $this->buildSelectSql(true);
        } else {
            $sql = "SELECT COUNT(*) as total FROM `{$this->table}`";
            if ($this->where_parts) {
                $sql .= ' WHERE ' . implode(' AND ', $this->where_parts);
            }
        }
        $result = $this->db->query($sql);
        $row = $this->db->fetch_array($result);
        $this->reset();
        return (int)($row['total'] ?? 0);
    }

    private function buildSelectSql($countOnly = false) {
        $from = "`{$this->table}`";
        if ($this->tableAlias !== '') {
            $from .= " `{$this->tableAlias}`";
        }
        $select = $countOnly ? 'COUNT(*) as total' : $this->select_fields;
        $sql = "SELECT {$select} FROM {$from}";
        foreach ($this->joins as $join) {
            $sql .= " {$join['type']} JOIN `{$join['table']}` `{$join['alias']}` ON {$join['on']}";
        }
        if ($this->where_parts) {
            $sql .= ' WHERE ' . implode(' AND ', $this->where_parts);
        }
        if (!$countOnly) {
            if ($this->group_clause) {
                $sql .= " GROUP BY {$this->group_clause}";
            }
            if ($this->order_clause) {
                $sql .= " ORDER BY {$this->order_clause}";
            }
            if ($this->limit_clause) {
                $sql .= " LIMIT {$this->limit_clause}";
            }
        }
        return $sql;
    }

    private function buildWhereFromArray($conditions) {
        $parts = [];
        foreach ($conditions as $field => $value) {
            $col = strpos($field, '.') !== false
                ? '`' . implode('`.`', explode('.', $field)) . '`'
                : "`{$field}`";
            if (is_int($value) || is_float($value)) {
                $parts[] = "{$col} = {$value}";
            } else {
                $escaped_value = $this->db->escape_string($value);
                $parts[] = "{$col} = '{$escaped_value}'";
            }
        }
        return implode(' AND ', $parts);
    }

    private function reset() {
        $this->select_fields = '*';
        $this->joins = [];
        $this->where_parts = [];
        $this->order_clause = '';
        $this->limit_clause = '';
        $this->group_clause = '';
    }
}
