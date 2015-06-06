<?php

require_once('table.php');
require_once('dialect.php');

class DB {
    public static function open($url, ...$options) {
        $dialect = null;
        if (preg_match("/^pgsql:/", $url)) {
            $dialect = new PostgreSQLDialect();
        } elseif (preg_match("/^mysql:/", $url)) {
            $dialect = new MySQLDialect();
        } else {
            throw new Exception("Unsupported Database Type");
        }
        $base = new PDO($url, ...$options);
        $base->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return new DB($base, $dialect);
    }

    private $base;
    private $dialect;
    private $columns;
    private $relations;
    private $hooks;
    private $cached;
    private $transaction_level;

    function __construct($base, $dialect) {
        $this->base = $base;
        $this->dialect = $dialect;
        $this->columns = [];
        $this->relations = [];
        $this->hooks = [];
        $this->cached = [];
        $this->transaction_level = 0;
    }

    public function prepare($sql) {
        return $this->base->prepare($sql);
    }

    public function execute($sql, ...$parameters) {
        $call = $this->base->prepare($sql);
        return $call->execute($parameters);
    }

    public function lastInsertId($table_name) {
        return $this->base->lastInsertId($this->dialect->sequence($table_name));
    }

    public function query($sql, ...$parameters) {
        $call = $this->base->prepare($sql);
        $call->execute($parameters);
        return $call->fetchAll();
    }

    public function one($sql, ...$parameters) {
        $rows = $this->one($sql, ...$parameters);
        return empty($rows)? null: $rows[0];
    }

    public function value($sql, ...$parameters) {
        $row = one($sql, ...$parameters);
        return empty($row)? null: $row[0];
    }

    public function createTable($table_name, ...$columns) {
        $template = "create table if not exists %s (id %s, %s, created_at timestamp default current_timestamp, updated_at timestamp default current_timestamp)";
        $sql = sprintf($template, $table_name, $this->dialect->identity(), implode(", ", $columns));
        $this->execute($sql);
        return $this->__get($table_name);
    }

    public function dropTable($table_name) {
        return $this->execute("drop table if exists {$table_name}");
    }

    public function alterTable($table_name, $statement) {
        return $this->execute("alter table {$table_name} {$statement}");
    }

    public function createIndex($index_name, $table_name, ...$column_names) {
        $template = "create index %s on %s(%s)";
        $sql = sprintf($template, $index_name, $table_name, implode(", ", $column_names));
        return $this->execute($sql);
    }

    public function dropIndex($index_name) {
        return $this->execute("drop index {$index_name}");
    }
    
    public function getTableNames() {
        return array_map(function($row) {
            return $row[0];
        }, $this->query($this->dialect->tables()));
    }

    public function getTables() {
        return array_map(function($table_name) {
            return $this->$table_name;
        }, $this->getTableNames());
    }

    public function getColumns($table_name) {
        if (!isset($this->columns[$table_name])) {
            $columns = array_map(function($row) {
                return $row[0];
            }, $this->query($this->dialect->columns(), $table_name));
            $this->columns[$table_name] = array_filter($columns, function($column_name) {
                return !in_array($column_name, ['id', 'created_at', 'updated_at']);
            });
        }
        return $this->columns[$table_name];
    }

    public function __get($table_name) {
        if (isset($this->cached[$table_name])) {
            return $this->cached[$table_name];
        }

        $table_name = $this->dialect->convert($table_name);
        if (!isset($this->relations[$table_name])) {
            $this->relations[$table_name] = [];
        }
        if (!isset($this->hooks[$table_name])) {
            $this->hooks[$table_name] = [];
        }

        $cached[$table_name] = new Table($this, $table_name, $this->getColumns($table_name), $this->relations[$table_name], $this->hooks[$table_name]);
        return $cached[$table_name];
    }

    public function tx($fn) {
        if ($this->transaction_level === 0) {
            $this->base->beginTransaction();
        }
        $this->transaction_level++;
        try {
            $result = $fn();
            $this->transaction_level--;
            if ($this->transaction_level === 0) {
                $this->base->commit();
            }
            return $result;
        } catch (Exception $e) {
            logger($e->__toString());
            $this->transaction_level = 0;
            $this->base->rollBack();
            throw $e;
        }
    }
}
