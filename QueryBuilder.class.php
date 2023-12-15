<?php
// Initial Version
// Only Support SELECT queries

class QueryBuilder {
    protected $table;
    protected $conditions = [];
    protected $orderBy;

    public function select($table) {
        $this->table = $table;
        return $this;
    }

    public function where($key, $value) {
        $this->conditions[] = "$key = '$value'";
        return $this;
    }

    public function massiveWhere($conditions) {
        foreach ($conditions as $key => $value) {
            $this->conditions[] = "$key = '$value'";
        }
        return $this;
    }
	
    public function orderBy($column, $direction = 'ASC') {
        $this->orderBy = "ORDER BY $column $direction";
        return $this;
    }

    public function build() {
        $sql = "SELECT * FROM $this->table";
        if (!empty($this->conditions)) {
            $sql .= " WHERE " . implode(" AND ", $this->conditions);
        }
        if (!empty($this->orderBy)) {
            $sql .= " $this->orderBy";
        }
        return $sql;
    }
	public function __toString(){
		return $this->build();
	}
}


// Usage
$query = new QueryBuilder('users'); // "FROM users"
$query = new QueryBuilder('users')->select(); // "SELECT * FROM users"
$query = new QueryBuilder('users')->select()->where('id', 1); // "SELECT * FROM users WHERE id = 1"
$query = new QueryBuilder('users')->select()->where(['role' => 'admin', 'status' => 'active']); // "SELECT * FROM users WHERE role = 'admin' AND status = 'active'"
