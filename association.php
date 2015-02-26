<?php

require_once('utils.php');

class Association {
    private $relations;
    private $onlyOne;
    private $ancestor;

    private $assoc;
    public $target;
    public $key;

    function __construct($relations, $name, $onlyOne, $ancestor) {
        $this->relations = $relations;
        $this->onlyOne = $onlyOne;
        $this->ancestor = $ancestor;

        $this->target = $name;
        $this->key = $name . '_id';
        $this->assoc = null;
    }

    public function __get($name) {
        if ($name === 'onlyOne') {
            return $this->onlyOne;
        } elseif ($name === 'ancestor') {
            return $this->ancestor;
        } elseif ($name === 'cross') {
            return $this->assoc !== null;
        }
        return null;
    }

    public function by($key) {
        $this->key = $key;
        return $this;
    }

    public function in($table_name) {
        $this->target = $table_name;
        return $this;
    }

    public function through($assoc) {
        $assoc = parseKeyParameter($assoc);
        if (isset($this->relations[$assoc])) {
            $this->assoc = $this->relations[$assoc];
        } else {
            throw new Exception("Undefined Association {$assoc}");
        }
        return $this;
    }

    public function assoc($source, $id) {
        if ($this->cross) {
            $other = $this->assoc->assoc($source, $id);
            if ($this->ancestor) {
                return "{$this->assoc->target} on {$this->target}.{$this->key} = {$this->assoc->target}.id join {$other}";
            } else {
                return "{$this->assoc->target} on {$this->assoc->target}.{$this->key} = {$this->target}.id join {$other}";
            }
        } else {
            if ($this->ancestor) {
                return "{$source} on {$this->target}.{$this->key} = {$source}.id and {$source}.id = {$id}";
            } else {
                return "{$source} on {$source}.{$this->key} = {$this->target}.id and {$source}.id = {$id}";
            }
        }
    }
}