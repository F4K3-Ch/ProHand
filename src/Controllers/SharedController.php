<?php
    // src/Controllers/SharedController.php

    class SharedController {
        private $db;
        private $user;
        private $auth;

        public function __construct($db) {
            $this->db = $db;
            $this->user = new User($db);
            $this->auth = new Auth($db);
        }

        /**
         * Logic for viewing the profile and gathering sidebar data
         */
        public function profile() {
            // 1. Security Check
            $this->auth->checkLogin();
            
            $user_id = $_SESSION['user_id'];
            $role = $_SESSION['role'];
            
            // 2. Fetch raw database data
            $userData = $this->user->getUserInfo($user_id);

            if (!$userData) {
                return null; // Handle case where user session exists but DB record is gone
            }

            // 3. Setup Profile Picture logic
            $profile_dir = '../../public/uploads/profile_pictures/';
            $db_pic = $userData['profile_picture'] ?? '';
            $profile_pic = !empty($db_pic) ? basename($db_pic) : 'default.jpg';
            $profile_picture_path = $profile_dir . $profile_pic;

            // 4. Handle the Back URL (Origin logic)
            $from = $_GET['from'] ?? '';
            if ($from === 'notification') {
                $back_url = "../shared/notifications.php";
            } else {
                $back_url = "../$role/" . $role . "_dashboard.php";
            }

            // 5. Pre-format names for the view
            $first = $userData['first_name'] ?? '';
            $middle = $userData['middle_name'] ?? '';
            $last = $userData['last_name'] ?? '';
            $full_name = trim("$first $middle $last");

            // Return everything needed for Sidebar, Profile, and Notifications
            return [
                'user'            => $userData,           // The raw array for profile inputs
                'full_name'       => $full_name,          // Clean name for sidebar/headings
                'role'            => ucfirst($userData['role'] ?? $role),
                'profile_picture' => $profile_picture_path,
                'back_url'        => $back_url,
                'success'         => $_SESSION['success'] ?? null,
                'error'           => $_SESSION['error'] ?? null
            ];
        }
    }