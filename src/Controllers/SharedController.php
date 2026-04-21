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

        public function handleProfileUpdate($post, $files) {
            $this->auth->checkLogin();
            $user_id = $_SESSION['user_id'];
            $userData = $this->user->getUserInfo($user_id);
            
            // Path configuration 
            $profile_dir = '../../public/uploads/profile_pictures/';

            // --- 1. REMOVE PICTURE LOGIC ---
            if (isset($post['remove_picture'])) {
                if (!empty($userData['profile_picture']) && basename($userData['profile_picture']) !== 'default.jpg') {
                    $old_file = $profile_dir . basename($userData['profile_picture']);
                    if (file_exists($old_file)) unlink($old_file);
                }
                
                $this->user->updateProfilePicture($user_id, 'uploads/profile_pictures/default.jpg');
                $_SESSION['success'] = "Profile picture removed.";
                header("Location: edit_profile_info.php");
                exit;
            }

            // --- 2. UPLOAD PICTURE LOGIC ---
            if (isset($files['profile_picture']) && $files['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $files['profile_picture']['tmp_name'];
                $image_info = getimagesize($file_tmp);
                $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];

                if (!$image_info || !in_array($image_info['mime'], $allowed_types)) {
                    $_SESSION['error'] = "Invalid image format. Use JPG or PNG.";
                } elseif ($files['profile_picture']['size'] > 2 * 1024 * 1024) {
                    $_SESSION['error'] = "Image too large (Max 2MB).";
                } else {
                    $extension = pathinfo($files['profile_picture']['name'], PATHINFO_EXTENSION);
                    $new_name = 'user_' . $user_id . '_' . time() . '.' . $extension;
                    $destination = $profile_dir . $new_name;

                    if (move_uploaded_file($file_tmp, $destination)) {
                        // Delete the old photo if it isn't the default
                        if (!empty($userData['profile_picture']) && basename($userData['profile_picture']) !== 'default.jpg') {
                            $old_file = $profile_dir . basename($userData['profile_picture']);
                            if (file_exists($old_file)) unlink($old_file);
                        }
                        
                        $this->user->updateProfilePicture($user_id, 'uploads/profile_pictures/' . $new_name);
                        $_SESSION['success'] = "Profile picture updated!";
                        header("Location: edit_profile_info.php");
                        exit;
                    }
                }
            }

            // --- 3. TEXT INFO UPDATE LOGIC ---
            if (isset($post['update_info'])) {
                $email = trim($post['email']);
                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $_SESSION['error'] = "Invalid email format.";
                } elseif ($this->user->isEmailTakenByOthers($email, $user_id)) {
                    $_SESSION['error'] = "Email is already taken.";
                } else {
                    $data = [
                        'email'          => $email,
                        'first_name'     => trim($post['first_name']),
                        'middle_name'    => trim($post['middle_name']),
                        'last_name'      => trim($post['last_name']),
                        'contact_number' => trim($post['contact_number']),
                        'user_id'        => $user_id
                    ];

                    if ($this->user->updateProfileInfo($data)) {
                        $_SESSION['success'] = "Profile information updated!";
                        header("Location: profile.php");
                        exit;
                    }
                }
            }
        }
    }