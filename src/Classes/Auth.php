<?php
    class Auth {
        private $db;

        public function __construct($db_connection) {
            // We receive the connection from the Database class
            $this->db = $db_connection;
        }

        /**
         * Protects pages by checking if a user is logged in.
         * If not, it kicks them back to the login page.
         */
        public function checkLogin() {
            if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
                header("Location: ../auth/login.php");
                exit;
            }
        }

        /**
         * Checks if the logged-in user has the correct role (admin, client, tasker).
         * This prevents a Client from manually typing "admin_dashboard.php" in the URL.
         */
        public function restrictTo($required_role) {
            $this->checkLogin(); // First, make sure they are even logged in

            if ($_SESSION['role'] !== $required_role) {
                // If they are a client trying to see admin pages, send them to their own dashboard
                header("Location: ../" . $_SESSION['role'] . "/" . $_SESSION['role'] . "_dashboard.php");
                exit;
            }
        }

        /**
         * Redirects users who are already logged in away from Login/Register pages.
         */
        public function redirectIfLoggedIn() {
            if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
                $role = $_SESSION['role'];
                header("Location: ../$role/{$role}_dashboard.php");
                exit;
            }
        }
    }