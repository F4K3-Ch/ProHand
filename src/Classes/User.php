<?php
    class User {
        private $db;

        public function __construct($db_connection) {
            $this->db = $db_connection;
        }

        // --- LOGIN METHOD ---
        public function login($email, $password) {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = $user['role'];
                    return $user['role']; // Return role for redirection
                }
            }
            return false;
        }

        // --- REGISTER METHOD ---
        public function register($data) {
            // Check for duplicate email
            $check_stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $check_stmt->bind_param("s", $data['email']);
            $check_stmt->execute();
            $check_stmt->store_result();
            
            if ($check_stmt->num_rows > 0) {
                return "Email is already registered!";
            }
            $check_stmt->close();

            // Insert new user
            $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
            $role = "client"; 
            $default_pic = 'uploads/profile_pictures/default.jpg';

            $insert_stmt = $this->db->prepare("
                INSERT INTO users (email, first_name, middle_name, last_name, password, profile_picture, contact_number, role) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $insert_stmt->bind_param(
                "ssssssss",
                $data['email'], $data['first_name'], $data['middle_name'], 
                $data['last_name'], $hashed_password, $default_pic, 
                $data['contact_number'], $role
            );

            return $insert_stmt->execute() ? true : "Registration failed.";
        }

        // --- LOGOUT METHOD ---
        public function logout() {
            session_unset();
            session_destroy();

            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
        }
    }