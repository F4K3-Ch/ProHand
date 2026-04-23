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
            $error = "";
            $email = "";

            if ($_SERVER["REQUEST_METHOD"] === "POST") {
                $email = trim($_POST['email']);
                $password = $_POST['password'];

                $result = $this->user->login($email, $password);

                // If the result is a role, redirect
                if (in_array($result, ['admin', 'client', 'tasker'])) {
                    header("Location: ../$result/{$result}_dashboard.php");
                    exit;
                } else {
                    // Otherwise, $result contains our specific error string
                    $error = $result;
                }
            }
            
            return [
                'error' => $error, 
                'email' => $email
            ];
        }

        // REGISTER LOGIC
        public function register() {
            $this->auth->redirectIfLoggedIn();
            $error = "";
            $fields = [
                'email' => '', 
                'first_name' => '', 
                'middle_name' => '', 
                'last_name' => '', 
                'contact_number' => ''
            ];

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
                    $error = "Passwords do not match!";
                } else {
                    $result = $this->user->register($data);
                    if ($result === true) {
                        $_SESSION['success'] = "Registration successful! Please login.";
                        header("Location: login.php");
                        exit;
                    } else {
                        $error = $result;
                    }
                }
                $fields = $data; // Keep inputs for the HTML form
            }
            return ['error' => $error, 'fields' => $fields];
        }

        // LOGOUT LOGIC
        public function logout() {
            $this->user->logout();
            header("Location: login.php");
            exit;
        }
    }