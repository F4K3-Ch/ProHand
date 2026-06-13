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

        // profile.php, admin/view_user_info.php & sidebar.php
        public function profile($specific_user_id = null) {
            $this->auth->checkLogin();
            
            // Use provided ID (for Admin viewing others) or Session ID (for self)
            $user_id = $specific_user_id ?? $_SESSION['user_id'];
            $userData = $this->user->getUserInfo($user_id);

            if (!$userData) return null;

            // --- MERGED LOGIC START ---
            $from = $_GET['from'] ?? 'dashboard';

            // 1. Name Formatting
            $full_name = $this->user->getFullName($user_id);
            
            // 2. Picture Path Logic
            $db_pic = $userData['profile_picture'] ?? '';
            $file = !empty($db_pic) ? basename($db_pic) : 'default.jpg';
            $path = "../../public/uploads/profile_pictures/" . $file;

            // 3. Back URL Logic
            $role_raw = $userData['role'] ?? 'user';
            if ($from === 'notification') {
                $back_url = "../shared/notifications.php";
            } elseif ($from === 'admin_manage') {
                $back_url = "manage_users.php";
            } else {
                $back_url = "../$role_raw/" . $role_raw . "_dashboard.php";
            }

            // 4. Notification Count
            $unread_count = ($user_id > 0) ? $this->user->getUnreadNotificationCount($user_id) : 0;

            // Assemble final array
            $formatted = [
                'user'                 => $userData,
                'full_name'            => $full_name,
                'role'                 => $role_raw,
                'role_label'           => ucfirst($role_raw),
                'profile_picture'      => $path,
                'user_profile_picture' => $path, 
                'back_url'             => $back_url,
                'notif_count'          => $unread_count,
                'success'              => $_SESSION['success'] ?? null,
                'error'                => $_SESSION['error'] ?? null
            ];
            // --- MERGED LOGIC END ---

            return $formatted;
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

            // if (strlen($new_password) < 8) {
            //     $_SESSION['error'] = "New password must be at least 8 characters.";
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

        // Standard Attachment Download Controller
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

            // 2. Safely maps the single file path string
            $file_path = "../../public/" . $attachment['file_path'];

            if (!file_exists($file_path)) {
                $_SESSION['error'] = "File does not exist on server.";
                header("Location: ../$role/{$role}_dashboard.php");
                exit;
            }

            // 3. Streams the single file directly to the browser
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

        // Receipt Download Controller
        public function handleReceiptDownload($errand_id, $type = 'regular') {
            $this->auth->checkLogin();
            $role = $_SESSION['role'];
            $is_history = ($type === 'history');
            
            // 1. Fetch Receipt Details from Model
            $receipt = $this->user->getPaymentReceiptDetails((int)$errand_id, $is_history);

            if (!$receipt || empty($receipt['payment_receipt_path'])) {
                $_SESSION['error'] = "File not found.";
                header("Location: ../$role/{$role}_dashboard.php");
                exit;
            }

            // 2. Safely maps the single file path string
            $file_path = "../../public/" . $receipt['payment_receipt_path'];

            if (!file_exists($file_path)) {
                $_SESSION['error'] = "File does not exist on server.";
                header("Location: ../$role/{$role}_dashboard.php");
                exit;
            }

            // 3. Streams the single file directly to the browser
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($receipt['payment_receipt_path']) . '"');
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
            // 1. Security Check at the very top
            $this->auth->checkLogin();
            
            // 2. Set up your clean project standard variables
            $history_id = (int)($_POST['id'] ?? 0);
            $role       = $_SESSION['role'] ?? '';
            $user_id    = (int)($_SESSION['user_id'] ?? 0);

            // 3. The Bouncer Gate Check
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $history_id <= 0 || empty($role)) {
                $_SESSION['error'] = "Invalid request.";
                header("Location: ../$role/{$role}_finished_errands.php");
                exit;
            }

            // 4. Main Feature Logic
            $result = $this->user->deleteHistoryRecord($history_id, $user_id, $role);

            if ($result) {
                $errand_title = $result['title'];
                $errand_id    = $result['errand_id'];
                $_SESSION['success'] = "Errand: \"{$errand_title}\" (ID: {$errand_id}) was deleted successfully from your history.";
            } else {
                $_SESSION['error'] = "Error deleting the requested history record. Please try again.";
            }
            
            // Dynamic redirect based on their role
            header("Location: ../{$role}/{$role}_finished_errands.php");
            exit;
        }

        public function handleDeleteAccount() {
            // 1. Security Check via your Auth class
            $this->auth->checkLogin();

            // 2. The Bouncer Gate Check (Blocks everything except a valid POST request)
            $userId = (int)($_SESSION['user_id'] ?? 0);
            $role   = trim($_SESSION['role'] ?? '');

            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $userId <= 0 || empty($role)) {
                $_SESSION['error'] = "Invalid request.";
                header("Location: profile.php");
                exit;
            }

            // 2. Role-specific Blocking Logic
            if ($role === 'client') {
                $clientObj = new Client($this->db);
                
                // Check for 'in_process' errands specifically to block deletion
                $metrics = $clientObj->getClientMetrics('active', $userId);
                if ($metrics['in_process'] > 0) {
                    $_SESSION['error'] = "You can't delete your account because you have an Errand In process. Please wait for it to finish or you may request to cancel your errand first.";
                    header("Location: profile.php");
                    exit;
                }
            }

            // 3. Execution Logic in User.php (Now properly returns structural metadata payload!)
            $deletion_payload = $this->user->performAccountDeletion($userId, $role);

            if ($deletion_payload && $deletion_payload['success']) {
                // Handle disk mutations directly here in the Controller
                $profile_dir = "../../public/uploads/profile_pictures/";
                $attachment_dir = "../../public/uploads/attachments/";
                $receipt_dir = "../../public/uploads/receipts/";

                // 1. Process profile picture deletion
                if (!empty($deletion_payload['profile_picture'])) {
                    $profile_filename = basename($deletion_payload['profile_picture']);
                    if ($profile_filename !== "default.jpg") {
                        $profile_path = $profile_dir . $profile_filename;
                        if (file_exists($profile_path)) {
                            unlink($profile_path);
                        }
                    }
                }

                // 2. Process all collected single errand attachments array safely
                if (!empty($deletion_payload['attachment_paths'])) {
                    foreach ($deletion_payload['attachment_paths'] as $raw_path) {
                        $attachment_path = $attachment_dir . basename($raw_path);
                        if (file_exists($attachment_path)) {
                            unlink($attachment_path);
                        }
                    }
                }

                // 3. Process all collected single payment receipts array safely
                if (!empty($deletion_payload['receipt_paths'])) {
                    foreach ($deletion_payload['receipt_paths'] as $raw_receipt) {
                        $receipt_path = $receipt_dir . basename($raw_receipt);
                        if (file_exists($receipt_path)) {
                            unlink($receipt_path);
                        }
                    }
                }

                $this->user->logout();
                header("Location: ../auth/login.php?success=Your account has been permanently deleted.");
                exit;
            }
        }
    }