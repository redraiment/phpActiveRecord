<?php

interface Dialect {
    public function convert($identifier);
    public function identity();
    public function sequence($table_name);
    public function tables();
    public function columns();
}

class MySQLDialect implements Dialect {
    public function convert($identifier) {
        return $identifier;
    }

    public function identity() {
        return "integer primary auto_increment";
    }

    public function sequence($table_name) {
        return "id";
    }

    public function tables() {
        return "show tables";
    }

    public function columns() {
        return "show columns from ?";
    }
}

class PostgreSQLDialect implements Dialect {
    public function convert($identifier) {
        return strtolower($identifier);
    }

    public function identity() {
        return "serial primary key";
    }

    public function sequence($table_name) {
        return "{$table_name}_id_seq";
    }

    public function tables() {
        return "select table_name from information_schema.tables where table_schema = 'public'";
    }

    public function columns() {
        return "select column_name from information_schema.columns where table_name = ? and table_schema != 'information_schema'";
    }
}
