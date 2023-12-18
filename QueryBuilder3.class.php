<?php
// Version+1+1+1
// Supports SELECT, UPDATE, DELETE, INSERT
// Supports
class QueryBuilder
{
	
    /**
     * The complete SQL query string
     */
    private $sqlString;
	
    /**
     * The type of the query (SELECT, UPDATE, DELETE)
     */
    private $queryType;

    /**
     * The database table for the query
     */
    private $table;

    /**
     * Columns to be selected in a SELECT query
     */
    private $selectables = [];

    /**
     * Associative array for WHERE conditions
     */
    private $where = [];

    /**
     * Associative array for INSERT data
     */
    private $insertData = [];

    /**
     * Associative array for SET data in UPDATE queries
     */
    private $set = [];

    /**
     * Flag to determine if query is a COUNT query
     */
    private $isCount = false;

    /**
     * OFFSET value for query results
     */
    private $offset;

    /**
     * LIMIT value for query results
     */
    private $limit;

    /**
     * ORDER BY direction (ASC/DESC)
     */
    private $order;

    /**
     * Column to apply ORDER BY
     */
    private $orderby;

    /**
     * Array for HAVING conditions
     */
    private $having = [];

    /**
     * Array for storing parameter bindings
     */
    private $bindings = [];
    
    /**
     * String representing the types of the bindings, used for bind_param
     */
    private $bindingTypes = '';
    
    /**
     * Constructor to initialize the query with the table name
     *
     * @param string $table Table name for the query
     */
    public function __construct($table) {
        $this->table = $table;
		return $this;
    }

    /**
     * Sets the type of query to SELECT and specifies the columns to select
     *
     * @param string|array $select The column(s) to select
     * @return $this
     */
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

    /**
     * Sets the type of query to UPDATE
     *
     * @return $this
     */
	public function update(){
		$this->queryType = 'UPDATE';
		return $this;
	}

    /**
     * Sets the type of query to DELETE
     *
     * @return $this
     */
	public function delete(){
		$this->queryType = 'DELETE';
		return $this;
	}

    /**
     * Adds a SET clause for an UPDATE query
     *
     * @param string $column Column to set
     * @param mixed $value Value to set the column to
     * @return $this
     */
	public function set($column, $value = null){
		$this->set[$column] = $value;
		return $this;
	}

    /**
     * Adds a WHERE condition
     *
     * @param string $column Column for the condition
     * @param mixed $value Value for the condition
     * @return $this
     */
	public function where($column, $value = null){
		$this->where[$column] = $value;
		return $this;
	}

    /**
     * Adds multiple WHERE conditions
     *
     * @param array $array Associative array of conditions
     * @return $this
     */
	public function massiveWhere($array = []){
		foreach($array as $key => $value){
			$this->where($key, $value);
		}
		return $this;
	}

    /**
     * Adds a raw SQL WHERE condition
     *
     * @param string $sql Raw SQL condition
     * @return $this
     */
	public function sqlwhere($sql){
		$this->where[] = $sql;
		return $this;
	}

    /**
     * Adds a HAVING clause for a SELECT query
     *
     * @param string $sql Raw SQL HAVING condition
     * @return $this
     */
	public function having($sql){
		$this->having[] = $sql;
		return $this;
	}

    /**
     * Marks the query as a COUNT query
     *
     * @return $this
     */
	public function count(){
		$this->is_count = true;
		return $this;
	}

    /**
     * Sets the OFFSET for query results
     *
     * @param int $n Number to set as OFFSET
     * @return $this
     */
	public function offset($n){
		$this->offset = $n;
		return $this;
	}

    /**
     * Sets the LIMIT for query results
     *
     * @param int $n Number to set as LIMIT
     * @return $this
     */
	public function limit($n){
		$this->limit = $n;
		return $this;
	}

    /**
     * Sets the ORDER BY clause for a SELECT query
     *
     * @param string $column Column to order by
     * @param string $d Direction (ASC/DESC)
     * @return $this
     */
    public function orderby($column, $d = 'ASC'){
      $this->order = $d;
      $this->orderby = $column;
      return $this;
	  }
	
	  /**
     * Sets the type of query to INSERT and specifies the data to insert
     *
     * @param array $data Associative array of column-value pairs to insert
     * @return $this
     */
    public function insert(array $data) {
        $this->queryType = 'INSERT';
        $this->insertData = $data;
        return $this;
    }

	/**
     * Constructs and returns the SQL query based on the query type.
     * Also, constructs the complete SQL string.
     *
     * @return string The prepared SQL query with placeholders
     */
    public function build() {
        $this->sqlString = ''; // Reset the SQL string
        $this->bindings = []; // Reset bindings
        $this->bindingTypes = ''; // Reset binding types
		
        switch ($this->queryType) {
            case 'SELECT':
                $preparedQuery = $this->buildSelect();
                break;
            case 'UPDATE':
                $preparedQuery = $this->buildUpdate();
                break;
            case 'DELETE':
                $preparedQuery = $this->buildDelete();
                break;
            case 'INSERT':
                $preparedQuery = $this->buildInsert();
                break;
        }

        $this->sqlString = $this->createCompleteSqlString($preparedQuery, $this->bindings);
        // Set the binding types
        foreach ($this->bindings as $binding) {
            $this->bindingTypes .= $this->getBindingType($binding);
        }

        return $preparedQuery;
    }

    private function buildSelect() {
        $columns = $this->isCount ? 'COUNT(*)' : implode(', ', $this->selectables);
        $sql = "SELECT $columns FROM $this->table";
        $sql .= $this->buildWhere();
        $sql .= $this->buildHaving();
        $sql .= $this->buildOrderBy();
        $sql .= $this->buildLimitOffset();

        return $sql;
    }

    private function buildUpdate() {
        $sql = "UPDATE $this->table SET ";
        $setParts = [];
        foreach ($this->set as $column => $value) {
            $setParts[] = "$column = ?";
            $this->bindings[] = $value;
        }
        $sql .= implode(', ', $setParts);
        $sql .= $this->buildWhere();

        return $sql;
    }

    private function buildDelete() {
        $sql = "DELETE FROM $this->table";
        $sql .= $this->buildWhere();

        return $sql;
    }

    private function buildInsert() {
        $columns = implode(', ', array_keys($this->insertData));
        $placeholders = implode(', ', array_fill(0, count($this->insertData), '?'));

        $sql = "INSERT INTO $this->table ($columns) VALUES ($placeholders)";

        foreach ($this->insertData as $value) {
            $this->bindings[] = $value;
        }

        return $sql;
    }
	
    private function buildWhere() {
        if (empty($this->where)) {
            return '';
        }
        $whereParts = [];
        foreach ($this->where as $column => $value) {
            if (is_numeric($column)) {
                $whereParts[] = $value; // Raw SQL conditions
            } else {
                $whereParts[] = "$column = ?";
                $this->bindings[] = $value;
            }
        }
        return ' WHERE ' . implode(' AND ', $whereParts);
    }

    private function buildHaving() {
        if (empty($this->having)) {
            return '';
        }
        return ' HAVING ' . implode(' AND ', $this->having);
    }

    private function buildOrderBy() {
        return empty($this->orderby) ? '' : " ORDER BY $this->orderby $this->order";
    }

    private function buildLimitOffset() {
        $sql = '';
        if (!empty($this->limit)) {
            $sql .= " LIMIT $this->limit";
        }
        if (!empty($this->offset)) {
            $sql .= " OFFSET $this->offset";
        }
        return $sql;
    }

    /**
     * Returns the array of parameter bindings
     *
     * @return array
     */
    public function getBindings() {
        return $this->bindings;
    }
    /**
     * Determines the type of a binding value for bind_param
     *
     * @param mixed $binding The binding value
     * @return string The type character ('i', 'd', 's', 'b')
     */
    private function getBindingType($binding) {
        if (is_int($binding)) {
            return 'i';
        } elseif (is_double($binding)) {
            return 'd';
        } elseif (is_string($binding)) {
            return 's';
        } else {
            // Default to string for other types, or add more logic for blobs, etc.
            return 's';
        }
    }

    /**
     * Gets the string representing the types of the bindings
     *
     * @return string The binding types string
     */
    public function getBindingTypes() {
        return $this->bindingTypes;
    }

    /**
     * Creates a complete SQL string with actual values inserted
     *
     * @param string $query The prepared query with placeholders
     * @param array $bindings The array of values to bind
     * @return string The complete SQL query string
     */
    private function createCompleteSqlString($query, $bindings) {
        foreach ($bindings as $binding) {
            $value = is_numeric($binding) ? $binding : "'" . addslashes($binding) . "'";
            $query = preg_replace('/\?/', $value, $query, 1);
        }
        return $query;
    }
    
    /**
     * Generates and returns the SQL query with inline values
     * Note: Use this method cautiously to avoid SQL injection risks.
     *
     * @return string The generated SQL query with inline values
     */
    public function toSql() {
        $this->bindings = []; // Reset bindings to capture new ones
        $sql = $this->build();

        foreach ($this->bindings as $binding) {
            $value = is_numeric($binding) ? $binding : "'" . addslashes($binding) . "'";
            $sql = preg_replace('/\?/', $value, $sql, 1);
        }

        return $sql;
    }
    
    /**
     * Clears the current query state for reuse
     *
     * @return $this
     */
    public function clear() {
        $this->queryType = null;
        $this->selectables = [];
        $this->where = [];
        $this->set = [];
        $this->insertData = [];
        $this->isCount = false;
        $this->offset = null;
        $this->limit = null;
        $this->order = null;
        $this->orderby = null;
        $this->having = [];
        $this->bindings = [];

        return $this;
    }
    
    /**
     * Converts the built query to a string
     *
     * @return string
     */
    public function __toString() {
        return $this->build();
    }
}


// Usage
$sql = new QueryBuilder('users');
$query = $sql->select()->where('user', 'admin'); // with placeholders for values
$query = $sql->select()->where('user', 'admin')->toSql(); // sql in plain text
$types = $sql->getBindingTypes();
$bindings = $sql->getBindings();





// Assuming $mysqli is your MySQLi connection object
$mysqli = new mysqli('localhost', 'root', 'password', 'database');

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Prepare the statement
$stmt = $mysqli->prepare($query);

// Check if preparation is successful
if ($stmt === false) {
    die("Error in preparing statement: " . $mysqli->error);
}

// Dynamically bind parameters
if (!empty($bindings)) {
    $stmt->bind_param($types, $bindings);
}

// Execute the query
if ($stmt->execute()) {
    $result = $stmt->get_result();

    // Fetch results
    while ($row = $result->fetch_assoc()) {
        // Process each row
        print_r($row);
    }
} else {
    // Handle query execution error
    echo "Error in executing query: " . $stmt->error;
}

// Close statement and connection
$stmt->close();
$mysqli->close();
