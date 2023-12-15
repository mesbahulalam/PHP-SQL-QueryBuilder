<?php
// Version+1
// Spports SELECT, UPDATE, DELETE

class QueryBuilder
{
	private $queryType;
	private $table;
	private $selectables = [];
	private $where = [];
	private $set = [];
	private $is_count = false;
	private $offset;
	private $limit;
	private $order;
	private $orderby;
	private $having = [];
	
	public function __construct($table){
		$this->table = $table;
		return $this;
	}
	public function select($select = '*'){
		$this->queryType = 'SELECT';
		if(func_num_args() > 1){
			$this->selectables[] = func_get_args();
			return $this;
		}
		if(is_array($select)){
			$this->selectables = array_merge($this->selectables, $select);
		}else if(is_string($select)){
			$this->selectables[] = $select;
		}
		return $this;
	}
	public function update(){
		$this->queryType = 'UPDATE';
		return $this;
	}
	public function delete(){
		$this->queryType = 'DELETE';
		return $this;
	}
	public function set($column, $value = null){
		$this->set[$column] = $value;
		return $this;
	}
	public function where($column, $value = null){
		$this->where[$column] = $value;
		return $this;
	}
	public function massiveWhere($array = []){
		foreach($array as $key => $value){
			$this->where($key, $value);
		}
		return $this;
	}
	public function sqlwhere($sql){
		$this->where[] = $sql;
		return $this;
	}
	public function having($sql){
		$this->having[] = $sql;
		return $this;
	}
	public function count(){
		$this->is_count = true;
		return $this;
	}
	public function offset($n){
		$this->offset = $n;
		return $this;
	}
	public function limit($n){
		$this->limit = $n;
		return $this;
	}
	public function orderby($column, $d = 'ASC'){
		$this->order = $d;
		$this->orderby = $column;
		return $this;
	}
	public function build() {
		$sql = '';

		// Construct SELECT query
		if ($this->queryType === 'SELECT') {
			$sql = 'SELECT ' . ($this->is_count ? 'COUNT(*)' : implode(', ', $this->selectables));
			$sql .= ' FROM ' . $this->table;
		}

		// Construct UPDATE query
		if ($this->queryType === 'UPDATE') {
			$sql = 'UPDATE ' . $this->table . ' SET ';
			$setParts = [];
			foreach ($this->set as $column => $value) {
				$setParts[] = "$column = '$value'";
			}
			$sql .= implode(', ', $setParts);
		}

		// Construct DELETE query
		if ($this->queryType === 'DELETE') {
			$sql = 'DELETE FROM ' . $this->table;
		}

		// Add WHERE conditions
		if (!empty($this->where)) {
			$whereParts = [];
			foreach ($this->where as $column => $value) {
				if (is_numeric($column)) {
					$whereParts[] = $value; // Raw SQL conditions
				} else {
					$whereParts[] = "$column = '$value'";
				}
			}
			$sql .= ' WHERE ' . implode(' AND ', $whereParts);
		}

		// Add HAVING conditions
		if (!empty($this->having)) {
			$sql .= ' HAVING ' . implode(' AND ', $this->having);
		}

		// Add ORDER BY clause
		if (!empty($this->orderby)) {
			$sql .= ' ORDER BY ' . $this->orderby . ' ' . $this->order;
		}

		// Add LIMIT clause
		if (!empty($this->limit)) {
			$sql .= ' LIMIT ' . $this->limit;
		}

		// Add OFFSET clause
		if (!empty($this->offset)) {
			$sql .= ' OFFSET ' . $this->offset;
		}

		return $sql;
	}
	public function __toString(){
		return $this->build();
	}
}

// Usage
echo (new QueryBuilder('users')) // "FROM users"
                                ->select() // "SELECT *"
                                ->where('id', 20) // "WHERE id = 20"
                                ->massiveWhere(['role' => 'admin', 'status' => 'active']) // "WHERE role = 'admin' AND status = 'active'"
                                ->having('karma > 10') // "HAVING karma > 10"
                                ->limit(10) // "LIMIT 10"
                                ->offset(10) // "OFFSET 10"
                                ->orderby('date', 'DESC'); // "ORDERBY date DESC"
                                
echo (new QueryBuilder('users'))
                                ->update()
                                ->set('email', 'newemail@host')
                                ->where('user', 10);
                                
echo (new QueryBuilder('users'))
                                ->delete()
                                ->where('user', 99);
