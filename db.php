<?php

require_once('table.php');

class DB {
    public static function open() {
        $reflect = new ReflectionClass('PDO');
        $pdo = $reflect->newInstanceArgs(func_get_args());
        return new DB($pdo);
    }

    private $base;
    private $columns;
    private $relations;

    function __construct($base) {
        $this->base = $base;
        $this->columns = array();
        $this->relations = array();
    }

    function execute() {
        $parameters = func_get_args();
        $sql = array_shift($parameters);
        $call = $this->base->prepare($sql);
        return $call->execute($parameters);
    }

    function query() {
        $parameters = func_get_args();
        $sql = array_shift($parameters);
        $call = $this->base->prepare($sql);
        $call->execute($parameters);
        return $call->fetchAll();
    }

    function using($database_name) {
        $this->execute("use {$database_name}");
        return $this;
    }

    function create($database_name) {
        $this->execute("create database if not exists {$database_name} default character set utf8 collate utf8_general_ci");
        $this->using($database_name);
        return $this;
    }

    function drop($database_name) {
        $this->execute("drop database if exists {$database_name}");
        return $this;
    }

    function createTable() {
        $columns = func_get_args();
        $table_name = array_shift($columns);

        $template = "create table if not exists %s (id integer primary key auto_increment, %s, created_at timestamp default current_timestamp, updated_at datetime)";
        $sql = sprintf($template, $table_name, implode(", ", $columns));
        $this->execute($sql);
        return $this->$table_name;
    }

    function dropTable($table_name) {
        return $this->execute("drop table if exists {$table_name}");
    }

    function createIndex() {
        $column_names = func_get_args();
        $index_name = array_shift($column_names);
        $table_name = array_shift($column_names);

        $template = "create index %s on %s(%s)";
        $sql = sprintf($template, $index_name, $table_name, implode(", ", $column_names));
        return $this->execute($sql);
    }

    function dropIndex($index_name) {
        return $this->execute("drop index {$index_name}");
    }
    
    function getTableNames() {
        return array_map(function($row) {
            return $row[0];
        }, $this->query('show tables'));
    }

    function getTables() {
        return array_map(function($table_name) {
            return $this->$table_name;
        }, $this->getTableNames());
    }

    function getColumns($table_name) {
        if (!isset($this->columns[$table_name])) {
            $columns = array_map(function($row) {
                return $row[0];
            }, $this->query("show columns from {$table_name}"));
            $this->columns[$table_name] = array_filter($columns, function($column_name) {
                return !in_array($column_name, array('id', 'created_at', 'updated_at'));
            });
        }
        return $this->columns[$table_name];
    }

    function __get($table_name) {
        if (!isset($this->relations[$table_name])) {
            $this->relations[$table_name] = array();
        }

        return new Table($this, $table_name, $this->getColumns($table_name), $this->relations[$table_name]);
    }
}
