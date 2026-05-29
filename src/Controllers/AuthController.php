<?php
    // src/Controllers/AuthController.php

    class AuthController {
        private $user;
        private $auth;

        public function __construct($db) {
            $this->user = new User($db);
            $this->auth = new Auth($db);
        }

        // LOGIN LOGIC
        public function login() {
            $this->auth->redirectIfLoggedIn();

            $email = $_SESSION['temp_email'] ?? "";
            unset($_SESSION['temp_email']); 

            if ($_SERVER["REQUEST_METHOD"] === "POST") {
                $email = trim($_POST['email']);
                $password = $_POST['password'];

                // 1. Controller validation rule: Check if email even exists
                if (!$this->user->isEmailRegistered($email)) {
                    $_SESSION['error'] = "Email not found.";
                    $_SESSION['temp_email'] = $email;
                    header("Location: login.php");
                    exit;
                }

                // 2. Commit credential verification check via model
                $result = $this->user->login($email, $password);

                if (is_array($result)) {
                    $_SESSION['user_id'] = $result['id'];
                    $_SESSION['role'] = $result['role'];
                    
                    $role = $result['role'];
                    header("Location: ../$role/{$role}_dashboard.php");
                    exit;
                } else {
                    // If it isn't an array, the password verification failed
                    $_SESSION['error'] = "Incorrect password.";
                    $_SESSION['temp_email'] = $email;
                    header("Location: login.php");
                    exit;
                }
            }
            
            return ['email' => $email];
        }

        // REGISTER LOGIC
        public function register() {
            $this->auth->redirectIfLoggedIn();

            $fields = [
                'email' => '', 
                'first_name' => '', 
                'middle_name' => '', 
                'last_name' => '', 
                'contact_number' => ''
            ];

            if (isset($_SESSION['temp_fields'])) {
                $fields = $_SESSION['temp_fields'];
                unset($_SESSION['temp_fields']); 
            }

            if ($_SERVER['REQUEST_METHOD'] === "POST") {
                $data = [
                    'email'          => trim($_POST['email']),
                    'first_name'     => trim($_POST['first_name']),
                    'middle_name'    => trim($_POST['middle_name']),
                    'last_name'      => trim($_POST['last_name']),
                    'contact_number' => trim($_POST['contact_number']),
                    'password'       => $_POST['password']
                ];
                
                // 1. Controller validation rule
                if ($data['password'] !== $_POST['confirm_password']) {
                    $_SESSION['error'] = "Passwords do not match!";
                    $_SESSION['temp_fields'] = $data; 
                    header("Location: register.php");
                    exit;
                } 
                
                // 2. Controller duplicate email validation rule via model helper
                if ($this->user->isEmailRegistered($data['email'])) {
                    $_SESSION['error'] = "Email is already registered!";
                    $_SESSION['temp_fields'] = $data;
                    header("Location: register.php");
                    exit;
                }

                // 3. Commit clean payload to database
                if ($this->user->register($data)) {
                    $_SESSION['success'] = "Registration successful! Please login.";
                    header("Location: login.php");
                    exit;
                } else {
                    $_SESSION['error'] = "Registration failed due to a system error. Please try again.";
                    $_SESSION['temp_fields'] = $data; 
                    header("Location: register.php");
                    exit;
                }
            }

            return ['fields' => $fields];
        }

        // LOGOUT LOGIC
        public function logout() {
            $this->user->logout();
            header("Location: ../auth/login.php");
            exit;
        }
    }