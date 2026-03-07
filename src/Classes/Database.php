<?php
    class Database {
        private $host = "localhost";
        private $username = "root";
        private $password = "";
        private $db = "errand_business_db";

        public $conn;

        public function getConnection() {
            // Reset connection to null before trying
            $this->conn = null;

            $this->conn = new mysqli(
                $this->host,
                $this->username,
                $this->password,
                $this->db
            );

            if ($this->conn->connect_error) {
                die("Connection failed: " . $this->conn->connect_error);
            }

            // IMPORTANT: Return the connection so other classes can use it
            return $this->conn;
        }

        public function closedConnection() {
            if ($this->conn) {
                $this->conn->close();
            }
        }
    }
?>