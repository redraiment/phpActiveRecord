<?php

require_once('utils.php');
require_once('db.php');
require_once('association.php');
require_once('query.php');
require_once('record.php');

class Table {
    private $db;
    private $name;
    private $columns;
    private $relations;
    private $primaryKey;
    private $foreignKeys;
    private $foreignTableName;

    function __construct($db, $name, $columns, &$relations) {
        $this->db = $db;
        $this->name = $name;
        $this->columns = $columns;
        $this->relations = &$relations;
        $this->primaryKey = $name . ".id";
        $this->foreignKeys = array();
    }

    public function __get($name) {
        if (in_array($name, array('db', 'name', 'columns', 'relations'))) {
            return $this->$name;
        }
        return null;
    }

    /* Association */
    private function assoc($name, $onlyOne, $ancestor) {
        $assoc = new Association($this->relations, $name, $onlyOne, $ancestor);
        $this->relations[$name] = $assoc;
        return $assoc;
    }

    public function belongsTo($name) {
        return $this->assoc($name, true, false);
    }

    public function hasOne($name) {
        return $this->assoc($name, true, true);
    }

    public function hasMany($name) {
        return $this->assoc($name, false, true);
    }

    public function hasAndBelongsToMany($name) {
        return $this->assoc($name, false, false);
    }

    private function getForeignKeys() {
        return array_map(function($key) {
            return "{$this->name}.{$key} = {$this->foreignKeys[$key]}";
        }, array_keys($this->foreignKeys));
    }

    public function constrain($key, $id) {
        $this->foreignKeys[parseKeyParameter($key)] = $id;
        return $this;
    }

    public function join($table) {
        $this->foreignTableName = $table;
        return $this;
    }

    /* CRUD */

    public function create() {
        // TODO
    }

    public function update($record) {
        // TODO
    }

    public function delete($record) {
        $sql = new SqlBuilder();
        $sql->delete()->from($this->name)->where("{$this->primaryKey} = {$record->id}");
        $this->db->execute($sql->__toString());
    }

    public function purge() {
        foreach ($this->all() as $record) {
            $this->delete($record);
        }
    }

    public function query() {
        return array_map(function($row) {
            return new Record($this, $row);
        }, call_user_func_array(array($this->db, 'query'), func_get_args()));
    }

    public function select() {
        $sql = new Query($this);
        if (func_num_args() === 0) {
            $sql->select("{$this->name}.*");
        } else {
            call_user_func_array(array($sql, 'select'), func_num_args());
        }
        $sql->from($this->name);
        if (!empty($this->foreignTableName)) {
            $sql->join($this->foreignTableName);
        }
        foreach ($this->getForeignKeys() as $condition) {
            $sql->where($condition);
        }
        return $sql->orderBy($this->primaryKey);
    }

    public function first() {
        if (func_num_args() === 0) {
            return $this->select()->limit(1)->one();
        } else {
            $args = func_get_args();
            $condition = array_unshift($args);
            $query = $this->select()->where($condition)->limit(1);
            return call_user_func_array(array($query, 'one'), $args);
        }
    }

    public function last() {
        if (func_num_args() === 0) {
            return $this->select()->orderBy("{$this->primaryKey} desc")->limit(1)->one();
        } else {
            $args = func_get_args();
            $condition = array_unshift($args);
            $query = $this->select()->where($condition)->orderBy("{$this->primaryKey} desc")->limit(1);
            return call_user_func_array(array($query, 'one'), $args);
        }
    }

    public function find($id) {
        return $this->first("{$this->primaryKey} = ?", $id);
    }

    public function findA($key, $value) {
        $key = parseKeyParameter($key);
        if ($value != null) {
            return $this->first("{$key} = ?", $value);
        } else {
            return $this->first("{$key} is null");
        }
    }

    public function findBy($key, $value) {
        $key = parseKeyParameter($key);
        if ($value != null) {
            return $this->where("{$key} = ?", $value);
        } else {
            return $this->where("{$key} is null");
        }
    }

    public function all() {
        return $this->select()->all();
    }

    public function where() {
        $args = func_get_args();
        $condition = array_unshift($args);
        $query = $this->select()->where($condition);
        return call_user_func_array(array($query, 'all'), args);
    }

    public function paging($page, $size) {
        return $this->select()->limit($size)->offset($page * $size)->all();
    }
}
