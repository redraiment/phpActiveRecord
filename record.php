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
        $relations = $this->relations;

        if (isset($values[$name])) {
            return $values[$name];
        } elseif (isset($table->relations[$name])) {
            $relation = $table->relations[$name];
            $active = $table->db->active($relation->target);
            $active->join($relation->assoc($table->name, values['id']));
            if ($relation->ancestor && !$relation->cross) {
                $active->constrain($relation->key, values['id'])
            }
            return $relation->onlyOne? $active->first(): $active;
        }

        return null;
    }

    public function __set($name, $value) {
        $this->values[$name] = $value;
        return $this;
    }

    public function save() {
        $this->table->update($this);
        return $this;
    }

    public function update() {
        $args = func_get_args();
        for ($i = 0; $i < func_num_args(); $i += 2) {
            $this->set($args[$i], $args[$i + 1]);
        }
        return $this->save();
    }

    public function destroy() {
        $this->table->delete($this);
    }

    public function __toString() {
        return implode("\n", array_map(function($key) {
            return $key . ' = ' . $this->values[$key];
        }, array_keys($this->values));
    }
}
