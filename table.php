<?php

require_once('utils.php')
require_once('db.php');
require_once('association.php');

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
        $this->foreignKeys = [];
    }

    public function __get($name) {
        if ($name === 'columns') {
            return $this->columns;
        }
        return null;
    }
}
