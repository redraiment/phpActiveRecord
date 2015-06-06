<?php

require_once('table.php');

class Record {
    private $table;
    private $values;

    function __construct($table, $values) {
        $this->table = $table;
        $this->values = $values;
    }

    public function columnNames() {
        return array_keys($this->values);
    }

    public function __get($name) {
        $values = $this->values;
        $table = $this->table;
        $relations = $table->relations;
        $hooks = $table->hooks;

        $value = null;
        if (isset($values[$name])) {
            $value = $values[$name];
        } elseif (isset($relations[$name])) {
            $relation = $relations[$name];
            $target = $relation->target;
            $alias = ($relation->alias === null)? $table->name: $relation->alias;
            $active = $table->db->$target;
            $active->join($relation->assoc($table->name, $alias, $values['id']));
            if ($relation->ancestor && !$relation->cross) {
                $active->constrain($relation->key, $values['id']);
            }
            $value = $relation->onlyOne? $active->first(): $active;
        }

        $key = 'get_' . $name;
        if (isset($hooks[$key])) {
            $value = $hooks[$key]($this, $value);
        } elseif (isset($hooks['get_*'])) {
            $value = $hooks['get_*']($this, $name, $value);
        }
        return (is_numeric($value) && preg_match('/^[1-9]/', $value))? ($value + 0): $value;
    }

    public function __set($name, $value) {
        $name = parseKeyParameter($name);

        $hooks = $this->table->hooks;
        $key = 'set_' . $name;
        if (isset($hooks[$key])) {
            $value = $hooks[$key]($this, $value);
        }

        $this->values[$name] = $value;
        return $this;
    }

    public function __call($name, $arguments) {
        $hooks = $this->table->hooks;
        $key = 'call_' . $name;
        if (isset($hooks[$key])) {
            return $hooks[$key]($this, ...$arguments);
        } elseif (isset($hooks['call_*'])) {
            return $hooks['call_*']($this, $name, $arguments);
        }
        return null;
    }

    public function save() {
        $this->table->update($this);
        return $this;
    }

    public function update() {
        $args = func_get_args();
        for ($i = 0; $i < func_num_args(); $i += 2) {
            $this->__set($args[$i], $args[$i + 1]);
        }
        return $this->save();
    }

    public function destroy() {
        $this->table->delete($this);
    }

    public function __toString() {
        return implode("\n", array_map(function($key) {
            return $key . ' = ' . $this->values[$key];
        }, array_keys($this->values)));
    }
}
