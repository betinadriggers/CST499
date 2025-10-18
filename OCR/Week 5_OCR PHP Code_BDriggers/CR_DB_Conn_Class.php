<?php
class Database {
    public $host = "localhost";
    public $username = "root";
    public $password = "";
    public $database = "course_registration";
    public $con;

    public function __construct() {
        $this->con = new mysqli($this->host, $this->username, $this->password, $this->database);
        if ($this->con->connect_error) {
            die("Connection failed: " . $this->con->connect_error);
        }
    }

    public function executeSelectQuery($sql) {
        $result = $this->con->query($sql);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_all(MYSQLI_ASSOC);
        } else {
            return [];
        }
    }

    public function closeConnection() {
        if ($this->con) {
            $this->con->close();
        }
    }
}
?>