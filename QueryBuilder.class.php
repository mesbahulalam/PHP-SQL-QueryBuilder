<?php
// Initial Version.
// Only Support SELECT
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


// usage
$sql = new QueryBuilder('users'); // "FROM users"
echo $sql->select() // "SELECT *"
    ->where('id', 30) // "WHERE id = 30"
    ->massiveWhere(['role' => 'admin', 'country' => 'Malaysia']) // "WHERE role = 'admin' AND country = 'Malaysia'"
    ->orderBy('last_login', 'DESC'); // "ORDERBY last_login DESC"

// Will output: SELECT * FROM users WHERE id = 30 AND role = 'admin' AND country = 'Malaysia' ORDERBY last_login DESC
