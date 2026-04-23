<?php
    class User {
        private $db;

        public function __construct($db_connection) {
            $this->db = $db_connection;
        }

        // LOGIN METHOD
        public function login($email, $password) {
            $stmt = $this->db->prepare("SELECT id, password, role FROM users WHERE email = ? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                return "Email not found."; // Unique string for email error
            }

            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                return $user['role']; 
            } else {
                return "Incorrect password."; // Unique string for password error
            }
        }
        
        // REGISTER METHOD
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

        // LOGOUT METHOD
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

        public function getUserInfo($user_id) {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            return $result->fetch_assoc();
        }

        public function isEmailTakenByOthers($email, $user_id) {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
            $stmt->bind_param("si", $email, $user_id);
            $stmt->execute();
            return $stmt->get_result()->num_rows > 0;
        }

        public function updateProfilePicture($user_id, $path) {
            $stmt = $this->db->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
            $stmt->bind_param("si", $path, $user_id);
            return $stmt->execute();
        }

        public function updateProfileInfo($data) {
            // Note: $data['id'] is now $data['user_id'] to match your request
            $stmt = $this->db->prepare("UPDATE users SET email=?, first_name=?, middle_name=?, last_name=?, contact_number=? WHERE id=?");
            $stmt->bind_param("sssssi", 
                $data['email'], 
                $data['first_name'], 
                $data['middle_name'], 
                $data['last_name'], 
                $data['contact_number'], 
                $data['user_id']
            );
            return $stmt->execute();
        }

        public function updatePassword($user_id, $new_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);
            return $stmt->execute();
        }

        public function getNotifications($user_id, $search = '') {
            if (!empty($search)) {
                $search_query = '%' . str_replace(',', '', $search) . '%';
                $stmt = $this->db->prepare("
                    SELECT * FROM notifications 
                    WHERE user_id = ? 
                    AND (type LIKE ? OR message LIKE ? OR DATE_FORMAT(created_at, '%M %e %Y') LIKE ?)
                    ORDER BY created_at DESC
                ");
                $stmt->bind_param("isss", $user_id, $search_query, $search_query, $search_query);
            } else {
                $stmt = $this->db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
                $stmt->bind_param("i", $user_id);
            }
            
            $stmt->execute();
            return $stmt->get_result();
        }

        public function markNotificationsAsRead($user_id) {
            $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
            $stmt->bind_param("i", $user_id);
            return $stmt->execute();
        }

        public function getUnreadNotificationCount($user_id) {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            return $result['count'] ?? 0;
        }

        public function deleteNotification($notification_id, $user_id) {
            $stmt = $this->db->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $notification_id, $user_id);
            return $stmt->execute();
        }

        public function deleteAllNotifications($user_id) {
            $stmt = $this->db->prepare("DELETE FROM notifications WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            return $stmt->execute();
        }
    }