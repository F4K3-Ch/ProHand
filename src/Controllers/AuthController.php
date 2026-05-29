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

            // Check if there is a saved email in the session from a previous failed attempt
            $email = $_SESSION['temp_email'] ?? "";
            unset($_SESSION['temp_email']); // Clear it so it doesn't stay there forever

            if ($_SERVER["REQUEST_METHOD"] === "POST") {
                $email = trim($_POST['email']);
                $password = $_POST['password'];

                $result = $this->user->login($email, $password);

                if (in_array($result, ['admin', 'client', 'tasker'])) {
                    header("Location: ../$result/{$result}_dashboard.php");
                    exit;
                } else {
                    // 1. Store the error message
                    $_SESSION['error'] = $result;
                    // 2. Store the email so the user doesn't have to re-type it
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

            // 1. Initialize default empty fields
            $fields = [
                'email' => '', 
                'first_name' => '', 
                'middle_name' => '', 
                'last_name' => '', 
                'contact_number' => ''
            ];

            // 2. Check if there is saved form data from a previous failed attempt
            if (isset($_SESSION['temp_fields'])) {
                $fields = $_SESSION['temp_fields'];
                unset($_SESSION['temp_fields']); // Clear it immediately after retrieving
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
                
                if ($data['password'] !== $_POST['confirm_password']) {
                    $_SESSION['error'] = "Passwords do not match!";
                    $_SESSION['temp_fields'] = $data; // Save inputs to session
                    header("Location: register.php");
                    exit;
                } else {
                    $result = $this->user->register($data);
                    if ($result === true) {
                        $_SESSION['success'] = "Registration successful! Please login.";
                        header("Location: login.php");
                        exit;
                    } else {
                        $_SESSION['error'] = $result;
                        $_SESSION['temp_fields'] = $data; // Save inputs to session
                        header("Location: register.php");
                        exit;
                    }
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