<?php

interface Dialect {
    public function convert($identifier); // 数据库底层名称大小写转换
    public function identity();           // 自增长语法
    public function sequence($table_name); // 自增长字段名称
    public function tables();              // 获取所有表名的SQL
    public function columns();             // 获得所有列名的SQL
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
