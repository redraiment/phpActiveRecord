<?php

class SqlBuilder {
    private $mode;

    private $fields;
    private $tables;
    private $conditions;
    private $groups;
    private $havings;
    private $orders;
    private $limit;
    private $offset;

    private function start($mode) {
        $this->mode = $mode;
        $this->fields = array();
        $this->tables = array();
        $this->conditions = array();
        $this->groups = array();
        $this->havings = array();
        $this->orders = array();
        $this->limit = -1;
        $this->offset = -1;
        return $this;
    }

    public function addField($field) {
        $this->fields[] = $field;
        return $this;
    }

    public function addTable($table) {
        $this->tables[] = $table;
        return $this;
    }

    public function addCondition($condition) {
        $this->conditions[] = $condition;
        return $this;
    }

    public function addGroup($group) {
        $this->groups[] = $group;
        return $this;
    }

    public function addHaving($having) {
        $this->havings[] = $having;
        return $this;
    }

    public function addOrder($order) {
        $this->orders[] = $order;
        return $this;
    }

    public function setField() {
        $this->fields = func_get_args();
        return $this;
    }

    public function setTable() {
        $this->tables = func_get_args();
        return $this;
    }

    public function setCondition() {
        $this->conditions = func_get_args();
        return $this;
    }

    public function setGroup() {
        $this->groups = func_get_args();
        return $this;
    }

    public function setHaving() {
        $this->havings = func_get_args();
        return $this;
    }

    public function setOrder() {
        $this->orders = func_get_args();
        return $this;
    }

    public function setLimit($limit) {
        $this->limit = $limit;
        return $this;
    }

    public function setOffset($offset) {
        $this->offset = $offset;
        return $this;
    }

    // TSQL

    public function insert() {
        return $this->start('insert');
    }

    public function into($table) {
        return $this->setTables($table);
    }

    public function value() {
        return call_user_func_array(array($this, 'setFields'), func_get_args());
    }

    public function update($table) {
        $this->start('update');
        return $this->setTables($table);
    }

    public function set() {
        return call_user_func_array(array($this, 'setFields'), func_get_args());
    }

    public function select() {
        $this->start('select');
        if (func_num_args() === 0) {
            return $this->setFields('*');
        } else {
            return call_user_func_array(array($this, 'setFields'), func_get_args());
        }
    }

    public function delete() {
        return $this->start('delete');
    }

    public function from($table) {
        return $this->setTables($table);
    }

    public function join($table) {
        return $this->addTable($table);
    }

    public function on() {
        $index = count($this->tables) - 1;
        $table = $this->tables[$index];
        $table .= ' on ';
        $table .= implode(' and ', func_get_args());
        $this->tables[$index] = $table;
        return $this;
    }

    public function where() {
        return call_user_func_array(array($this, 'setConditions'), func_get_args());
    }

    public function groupBy() {
        return call_user_func_array(array($this, 'setGroups'), func_get_args());
    }

    public function having() {
        return call_user_func_array(array($this, 'setHavings'), func_get_args());
    }

    public function orderBy() {
        return call_user_func_array(array($this, 'setOrders'), func_get_args());
    }

    public function limit($limit) {
        return $this->setLimit($limit);
    }

    public function offset($offset) {
        return $this->setOffset($offset);
    }

    // toString

    private function selectToString() {
        $sql = 'select ';
        $sql .= implode(', ', $this->fields);
        $sql .= ' from ';
        $sql .= implode(' join ', $this->tables);
        if (count($this->conditions) > 0) {
            $sql .= ' where ';
            $sql .= implode(' and ', $this->conditions);
        }
        if (count($this->groups) > 0) {
            $sql .= ' group by ';
            $sql .= implode(', ', $this->groups);
            if (count($this->havings) > 0) {
                $sql .= ' having ';
                $sql .= implode(' and ', $this->havings);
            }
        }
        if (count($this->orders) > 0) {
            $sql .= ' order by ';
            $sql .= implode(', ', $this->orders);
        }
        if ($this->limit > 0) {
            $sql .= ' limit ';
            $sql .= $this->limit;
        }
        if ($this->offset > -1) {
            $sql .= ' offset ';
            $sql .= $this->offset;
        }
        return $sql;
    }

    private function insertToString() {
        $sql = "insert into {$this->tables[0]} (";
        $sql .= implode(', ', $this->fields);
        $sql .= ') values (';
        $sql .= implode(', ', array_map(function($field) {
            return '?';
        }, $this->fields));
        $sql .= ')';
        return $sql;
    }

    private function updateToString() {
        $sql = "update {$this->tables[0]} set ";
        $sql .= implode(', ', array_map(function($field) {
            return "{$field} = ?";
        }, $this->fields));
        if (count($this->conditions) > 0) {
            $sql .= ' where ';
            $sql .= implode(' and ', $this->conditions);
        }
        return $sql;
    }

    private function deleteToString() {
        $sql = "delete from {$this->tables[0]}";
        if (count($this->conditions) > 0) {
            $sql .= ' where ';
            $sql .= implode(' and ', $this->conditions);
        }
        return $sql;
    }

    public function __toString() {
        switch ($this->mode) {
        case 'select':
            return selectToString();
            break;
        case 'insert':
            return insertToString();
            break;
        case 'update':
            return updateToString();
            break;
        case 'delete':
            return deleteToString();
            break;
        }
    }
}