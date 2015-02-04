<?php

require_once('sqlbuilder.php');
require_once('table.php');

class Query {
    private $table;
    private $sql;

    function __construct($table) {
        $this->table = $table;
    }

    public function all() {
        $params = func_get_args();
        array_unshift($params, $this->sql);
        return call_user_func_array(array($table, 'query'), $params);
    }

    public function one() {
        $this->limit(1);
        $models = call_user_func_array(array($this, 'all'), func_get_args());
        if (!isset($models) || empty($models)) {
            return null;
        } else {
            return $models[0];
        }
    }

    public function select() {
        call_user_func_array(array($this->sql, 'select'), func_get_args());
        return $this;
    }

    public function from() {
        call_user_func_array(array($this->sql, 'from'), func_get_args());
        return $this;
    }

    public function join() {
        call_user_func_array(array($this->sql, 'join'), func_get_args());
        return $this;
    }

    public function where($condition) {
        $this->addCondition($condition);
        return $this;
    }

    public function groupBy() {
        call_user_func_array(array($this->sql, 'groupBy'), func_get_args());
        return $this;
    }

    public function having() {
        call_user_func_array(array($this->sql, 'having'), func_get_args());
        return $this;
    }

    public function orderBy() {
        call_user_func_array(array($this->sql, 'orderBy'), func_get_args());
        return $this;
    }

    public function limit($limit) {
        $this->sql->limit($limit);
        return $this;
    }

    public function offset($offset) {
        $this->sql->offset($offset);
        return $this;
    }
}
