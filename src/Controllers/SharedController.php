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
        // profile.php & sidebar.php
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

            $unread_count = $this->user->getUnreadNotificationCount($user_id);

            // Return everything needed for Sidebar, Profile, and Notifications
            return [
                'user'            => $userData,           // The raw array for profile inputs
                'full_name'       => $full_name,          // Clean name for sidebar/headings
                'role'            => ucfirst($userData['role'] ?? $role),
                'profile_picture' => $profile_picture_path,
                'back_url'        => $back_url,
                'unread_notifications' => $unread_count,
                'success'         => $_SESSION['success'] ?? null,
                'error'           => $_SESSION['error'] ?? null
            ];
        }

        // edit_profile_info.php
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

        // change_password.php
        public function handleChangePassword($post) {
            $this->auth->checkLogin();
            $user_id = $_SESSION['user_id'];
            
            $current_password = $post['current_password'] ?? '';
            $new_password     = $post['new_password'] ?? '';
            $confirm_password = $post['confirm_password'] ?? '';

            // 1. Fetch user data to get the current hashed password
            $userData = $this->user->getUserInfo($user_id);

            // 2. Validation
            if (!$userData) {
                $_SESSION['error'] = "Account not found.";
                header("Location: ../auth/login.php");
                exit;
            }

            if (!password_verify($current_password, $userData['password'])) {
                $_SESSION['error'] = "Current password is incorrect.";
                return; 
            }

            if ($new_password !== $confirm_password) {
                $_SESSION['error'] = "New password and confirm password do not match.";
                return;
            }

            // if (strlen($new_password) < 6) {
            //     $_SESSION['error'] = "New password must be at least 6 characters.";
            //     return;
            // }

            // 3. Execution
            if ($this->user->updatePassword($user_id, $new_password)) {
                $_SESSION['success'] = "Password changed successfully.";
                header("Location: profile.php");
                exit;
            } else {
                $_SESSION['error'] = "Failed to update password. Please try again.";
            }
        }

        // notifications.php
        public function notifications() {
            $this->auth->checkLogin();
            $user_id = $_SESSION['user_id'];

            // Mark all unread notifications as read
            $this->user->markNotificationsAsRead($user_id);

            // Handle search input
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';

            // Fetch using the original normalized logic from the Model
            $notif_result = $this->user->getNotifications($user_id, $search);

            // Return the data to be extracted in the view
            return [
                'notif_result' => $notif_result,   // Becomes $notif_result
                'search'        => $search         // Becomes $search
            ];
        }

        // Delete Notification Button
        public function handleDeleteNotification($post) {
            $this->auth->checkLogin();
            $user_id = $_SESSION['user_id'];

            // Single Delete
            if (isset($post['notification_id'])) {
                $notification_id = (int)$post['notification_id'];
                if ($this->user->deleteNotification($notification_id, $user_id)) {
                    $_SESSION['success'] = "Notification deleted.";
                } else {
                    $_SESSION['error'] = "Failed to delete notification.";
                }
            } 
            // Delete All
            elseif (isset($post['delete_all'])) {
                if ($this->user->deleteAllNotifications($user_id)) {
                    $_SESSION['success'] = "All notifications deleted.";
                } else {
                    $_SESSION['error'] = "Failed to delete notifications.";
                }
            }

            header("Location: notifications.php");
            exit;
        }

        public function handleDownload($id, $type = 'regular') {
            $this->auth->checkLogin();
            $role = $_SESSION['role'];
            $is_history = ($type === 'history');
            
            // 1. Fetch Attachment Details from Model
            $attachment = $this->user->getAttachmentDetails($id, $is_history);

            if (!$attachment) {
                $_SESSION['error'] = "File not found.";
                header("Location: ../$role/{$role}_dashboard.php");
                exit;
            }

            // 2. Set Paths - Updated to match your handleCreateErrand structure
            // We go up three levels from src/Controllers/ to reach the root, then into public/
            $base_dir = $is_history 
                ? "../../public/uploads/history/attachments/" 
                : "../../public/uploads/attachments/";
            
            // We use the file_path directly from DB as it already contains 'uploads/attachments/...'
            // So we just need to point to the public folder root.
            $file_path = "../../public/" . $attachment['file_path'];

            // 3. Verify Physical File
            if (!file_exists($file_path)) {
                // Debugging tip: If it still fails, uncomment the line below to see what PHP is actually looking for:
                // die("PHP is looking for: " . realpath($file_path)); 
                
                $_SESSION['error'] = "File does not exist on server.";
                header("Location: ../$role/{$role}_dashboard.php");
                exit;
            }

            // 4. Force Download Headers
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($attachment['original_name']) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file_path));

            ob_clean();
            flush();
            
            readfile($file_path);
            exit;
        }

        public function handleDeleteHistory() {
            $this->auth->checkLogin();
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
                $history_id = (int)$_POST['id'];
                $user_id = $_SESSION['user_id'];
                $role = $_SESSION['role'];

                // Get the result array from the model
                $result = $this->user->deleteHistoryRecord($history_id, $user_id, $role);
                $errand_title = $result['title'];
                $errand_id = $result['errand_id'];

                if ($result) {
                    $_SESSION['success'] = "Errand: \"{$errand_title}\" (ID: {$errand_id}) was deleted successfully.";
                } else {
                    $_SESSION['error'] = "Error deleting Errand: \"{$errand_title}\" (ID: {$errand_id}). Please try again.";
                }
                
                header("Location: ../$role/{$role}_finished_errands.php");
                exit;
            }
        }
    }