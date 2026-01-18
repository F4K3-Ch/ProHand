<?php
class Auth {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function login($email, $password) {
        $email = trim($email);
        $password = trim($password);

        // Check if email exists
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verify password
            if (password_verify($password, $user['password'])) {
                return ['success' => true, 'user' => $user, 'error' => ''];
            } else {
                return ['success' => false, 'user' => null, 'error' => 'Incorrect password.'];
            }
        } else {
            return ['success' => false, 'user' => null, 'error' => 'Email not found.'];
        }
    }

    public function register($data) {
        $email = trim($data['email']);
        $first_name = trim($data['first_name']);
        $middle_name = trim($data['middle_name']);
        $last_name = trim($data['last_name']);
        $contact_number = trim($data['contact_number']);
        $password = $data['password'];
        $confirm_password = $data['confirm_password'];

        // Validate email and password
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid email format.'];
        } elseif ($password !== $confirm_password) {
            return ['success' => false, 'error' => 'Passwords do not match!'];
        }

        // Check for duplicate email
        $check_stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_stmt->store_result();
        if ($check_stmt->num_rows > 0) {
            $check_stmt->close();
            return ['success' => false, 'error' => 'Email is already registered!'];
        }
        $check_stmt->close();

        // Insert user with default profile picture
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = "client"; // Default role: client
        $default_profile_picture = 'uploads/profile_pictures/default.jpg';

        $insert_stmt = $this->conn->prepare("
            INSERT INTO users (
                email, first_name, middle_name, last_name, password,
                profile_picture, contact_number, role
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $insert_stmt->bind_param(
            "ssssssss",
            $email,
            $first_name,
            $middle_name,
            $last_name,
            $hashed_password,
            $default_profile_picture,
            $contact_number,
            $role
        );

        if ($insert_stmt->execute()) {
            $insert_stmt->close();
            return ['success' => true, 'error' => ''];
        } else {
            return ['success' => false, 'error' => 'Error: Could not complete registration.'];
        }
    }

    public function logout() {
        session_start();

        /* Remove all session variables */
        session_unset();

        /* Destroy the session */
        session_destroy();

        /* Delete the session cookie */
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
    }
}
?>
