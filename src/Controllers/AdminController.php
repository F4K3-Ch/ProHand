<?php
    // src/Controllers/AdminController.php

    class AdminController {
        private $admin;
        private $auth;
        private $user;

        public function __construct($db) {
            $this->admin = new Admin($db);
            $this->auth = new Auth($db);
            $this->user = new User($db);
        }

        // admin_dashboard.php
        public function index() {
            $this->auth->restrictTo('admin');
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            
            // Use the new combined method
            $metrics = $this->admin->getAdminMetrics('active');
            $errands = $this->admin->getActiveErrands($search);

            return [
                'active_errands' => $errands,
                'search'         => $search,
                'status_counts'  => [
                    'pending'    => $metrics['pending'],
                    'pending_payment'   => $metrics['pending_payment'],
                    'in_process' => $metrics['in_process']
                ],
                'total_errands'  => $metrics['total'],
                'status_labels'  => [
                    'pending' => 'Pending', 
                    'pending_payment' => 'Pending Payment', 
                    'in_process' => 'In Process'
                ]
            ];
        }

        // finished_errands.php
        public function finishedErrands() {
            $this->auth->restrictTo('admin');
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';

            // Use the new combined method
            $metrics = $this->admin->getAdminMetrics('history');
            $errands = $this->admin->getFinishedErrands($search);

            return [
                'result_finished' => $errands,
                'search'          => $search,
                'status_counts'   => [
                    'completed'   => $metrics['completed'],
                    'failed'      => $metrics['failed']
                ],
                'total_errands'   => $metrics['total'],
                'earnings'        => $metrics['earnings'],
                'failed_earnings' => $metrics['failed_earnings'],
                'status_labels'   => [
                    'completed' => 'Completed', 
                    'failed' => 'Failed'
                ]
            ];
        }

        // manage_users.php
        public function manageUsers() {
            // 1. Security Check
            $this->auth->restrictTo('admin');

            // 2. Setup Variables
            $admin_id = $_SESSION['user_id'];
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            
            // 3. Fetch Data from the Admin Class
            $metrics = $this->admin->getAdminMetrics('users');
            $users = $this->admin->searchUsers($search, $admin_id);

            // 4. Return clean data to the View
            return [
                'all_users_result' => $users,
                'search'           => $search,
                'role_counts'      => [
                    'admin'  => $metrics['admin']  ?? 0,
                    'tasker' => $metrics['tasker'] ?? 0,
                    'client' => $metrics['client'] ?? 0
                ],
                'total_users'      => $metrics['total'] ?? 0
            ];
        }

        public function viewErrandDetails($errand_id) {
            $this->auth->restrictTo('admin');

            // 1. Back URL Logic
            $from = $_GET['from'] ?? 'dashboard';
            $back_urls = [
                'dashboard' => 'admin_dashboard.php',
                'finished' => 'finished_errands.php',
                'notification' => '../shared/notifications.php'
            ];
            $back_url = $back_urls[$from] ?? 'admin_dashboard.php';

            // 2. Status Check
            $status_res = $this->admin->getErrandStatus($errand_id);
            if ($status_res->num_rows !== 1) return null;
            
            $status = $status_res->fetch_assoc()['status'];
            $is_active = in_array($status, ['pending', 'pending_payment', 'in_process']);
            $is_finished = in_array($status, ['completed', 'failed']);

            if ($is_active) {
                $res = $this->admin->getActiveErrandFullDetails($errand_id);
                if ($res->num_rows !== 1) return null;
                $errand = $res->fetch_assoc();
                $attachment = $this->admin->getAttachment($errand_id, false);
                $profile_dir = '../../public/uploads/profile_pictures/';
            } elseif ($is_finished) {
                $res = $this->admin->getFinishedErrandDetails($errand_id);
                if ($res->num_rows !== 1) return null;
                $errand = $res->fetch_assoc();
                $attachment = $this->admin->getAttachment($errand['id'], true);
                $profile_dir = '../../public/uploads/history/profile_pictures/';
            } else {
                return null;
            }

            // Process Client Picture
            $client_picture = $profile_dir . 'default.jpg';
            if (!empty($errand['client_profile_picture'])) {
                $c_path = $profile_dir . basename($errand['client_profile_picture']);
                if (file_exists($c_path)) $client_picture = $c_path;
            }

            // Process Tasker Picture
            $tasker_picture = $profile_dir . 'default.jpg';
            if (!empty($errand['tasker_profile_picture'])) {
                $t_path = $profile_dir . basename($errand['tasker_profile_picture']);
                if (file_exists($t_path)) $tasker_picture = $t_path;
            }

            return [
                'errand' => $errand,
                'attachment_result' => $attachment,
                'client_picture' => $client_picture,
                'tasker_picture' => $tasker_picture,
                'is_active' => $is_active,
                'is_finished' => $is_finished,
                'back_url' => $back_url,
                'status_labels' => [
                    'pending' => 'Pending', 'pending_payment' => 'Pending Payment', 
                    'in_process' => 'In Process', 'completed' => 'Completed', 'failed' => 'Failed'
                ]
            ];
        }

        // view_user_info.php
        private function formatUserData($userData, $from) {
            // 1. Picture Path Logic
            $db_pic = $userData['profile_picture'] ?? '';
            $file = !empty($db_pic) ? basename($db_pic) : 'default.jpg';
            $path = "../../public/uploads/profile_pictures/" . $file;

            // 2. Back URL Logic (Matches your HTML <a> tag)
            if ($from === 'notification') {
                $back_url = "../shared/notifications.php";
            } else {
                // Default back to the management list
                $back_url = "manage_users.php";
            }

            return [
                'user'                 => $userData,
                'full_name'            => ($userData['first_name'] ?? '') . ' ' . ($userData['last_name'] ?? ''),
                'user_profile_picture' => $path, // Matches your HTML variable
                'back_url'             => $back_url,
                'role_label'           => ucfirst($userData['role'] ?? 'user')
            ];
        }

        // view_user_info.php
        public function viewUserInfo() {
            // 1. Security Check
            $this->auth->restrictTo('admin');
            
            $target_user_id = (int)($_GET['id'] ?? 0);
            if ($target_user_id <= 0) {
                header("Location: manage_users.php");
                exit;
            }

            // 2. Fetch Raw Data (User + Stats)
            $data = $this->admin->getUserDetails($target_user_id);
            if (!$data || !isset($data['user'])) {
                header("Location: manage_users.php");
                exit;
            }

            // 3. Initial Formatting
            $from = $_GET['from'] ?? 'admin_manage';
            $formatted = $this->formatUserData($data['user'], $from);

            // 4. Populate Tasker Metrics (Matches your HTML conditional blocks)
            if ($data['user']['role'] === 'tasker') {
                $formatted['active_status'] = [
                    'accepted'   => (int)($data['active']['accepted'] ?? 0),
                    'in_process' => (int)($data['active']['in_process'] ?? 0)
                ];
                
                $formatted['finished_status'] = [
                    'completed' => (int)($data['history']['completed'] ?? 0),
                    'failed'    => (int)($data['history']['failed'] ?? 0)
                ];

                $formatted['total_active_errands']   = (int)($data['active']['total_active'] ?? 0);
                $formatted['total_finished_errands'] = (int)($data['history']['total_finished'] ?? 0);
                $formatted['earnings']               = (float)($data['history']['earnings'] ?? 0);
                $formatted['failed_earnings']        = (float)($data['history']['failed_earnings'] ?? 0);
                $formatted['total_errands']          = $formatted['total_active_errands'] + $formatted['total_finished_errands'];
            } else {
                // Provide empty defaults for non-taskers to prevent "Undefined Variable" if the HTML check fails
                $formatted['active_status'] = ['accepted' => 0, 'in_process' => 0];
                $formatted['finished_status'] = ['completed' => 0, 'failed' => 0];
                $formatted['total_active_errands'] = 0;
                $formatted['total_finished_errands'] = 0;
                $formatted['earnings'] = 0.0;
                $formatted['failed_earnings'] = 0.0;
                $formatted['total_errands'] = 0;
            }

            return $formatted;
        }

        // Reset Password button
        public function handlePasswordReset() {
            // 1. Authorization Check
            $this->auth->restrictTo('admin');
            $admin_id = (int)$_SESSION['user_id'];

            // 2. Validate Target User ID
            $target_user_id = (int)($_POST['id'] ?? 0);
            if ($target_user_id <= 0) {
                $_SESSION['error'] = "Invalid user identification.";
                header("Location: manage_users.php");
                exit;
            }

            // 3. Gather Target Details (needed for the user's role)
            $target_data = $this->admin->getUserDetails($target_user_id);

            if (!$target_data || !isset($target_data['user'])) {
                $_SESSION['error'] = "User validation failed.";
                header("Location: manage_users.php");
                exit;
            }

            $user = $target_data['user'];

            // 4. REUSED HELPERS: Get clean full names effortlessly
            $admin_full_name = $this->user->getFullName($admin_id);
            $user_full_name  = $this->user->getFullName($target_user_id);

            // 5. Generate Random Temporary Password
            $charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
            $temporary_password = substr(str_shuffle($charset), 0, 10);
            $hashed_password = password_hash($temporary_password, PASSWORD_DEFAULT);

            // 6. Commit to Database
            if ($this->admin->resetUserPassword($target_user_id, $hashed_password)) {
                
                // 7. Setup Notification Payload
                $notif_type = "system";
                $notif_message = "Password Reset | User: \"{$user_full_name}\" (ID: {$target_user_id}) | Role: \"" . ucfirst($user['role'] ?? 'user') . "\" | Reset by Admin: \"{$admin_full_name}\" (ID: {$admin_id}) | Temporary Password: \"{$temporary_password}\".";
                $notif_link = "../admin/view_user_info.php?id={$target_user_id}";

                // Broadcasts to all other admins automatically
                $this->user->notifyOtherAdmins($admin_id, $notif_type, $notif_message, $notif_link);

                $_SESSION['success'] = "Password reset successfully! Temporary password: \"$temporary_password\"";
            } else {
                $_SESSION['error'] = "Failed to reset password. Please try again.";
            }

            header("Location: view_user_info.php?id=" . $target_user_id);
            exit;
        }

        // Delete Active Errand
        public function handleActiveErrandDeletion() {
            $this->auth->restrictTo('admin');
            $admin_id = (int)$_SESSION['user_id'];

            $errand_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if ($errand_id <= 0) {
                $_SESSION['error'] = "Invalid request.";
                header("Location: admin_dashboard.php");
                exit;
            }

            // 1. Fetch active errand details via the model before deleting it
            $errand = $this->admin->getActiveErrandDetails($errand_id);
            if (!$errand) {
                $_SESSION['error'] = "Errand not found.";
                header("Location: admin_dashboard.php");
                exit;
            }

            $errand_title = $errand['title'];
            $status       = $errand['status'];
            $client_id    = (int)$errand['client_id'];
            $tasker_id    = isset($errand['tasker_id']) ? (int)$errand['tasker_id'] : 0;

            $admin_full_name  = $this->user->getFullName($admin_id);
            $client_full_name = $this->user->getFullName($client_id);
            $tasker_full_name = ($tasker_id > 0) ? $this->user->getFullName($tasker_id) : "None";

            // Map status codes to your readable labels
            $status_labels = [
                'pending'    => 'Pending',
                'accepted'   => 'Accepted',
                'in_process' => 'In-Process'
            ];

            // 2. FILE SYSTEM CLEANUP (Handled in Controller to keep Admin.php pure query)
            $attachment_dir = "../../public/uploads/attachments/";
            $attachment_res = $this->admin->getAttachment($errand_id);
            
            if ($attachment_res && $attachment_res->num_rows > 0) {
                while ($attachment = $attachment_res->fetch_assoc()) {
                    $file_path = $attachment_dir . basename($attachment['file_path']);
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                }
            }

            // 3. Command the pure-query model to execute database removals
            if ($this->admin->deleteActiveErrandRecord($errand_id)) {
                $notif_type = "errand";
                $notif_link = "";

                // Notify Client
                if ($client_id > 0) {
                    $client_msg = "Your Active Errand: \"{$errand_title}\" (ID: {$errand_id}) has been permanently deleted from the system by Admin: \"{$admin_full_name}\" (ID: {$admin_id}).";
                    $this->user->addNotification($client_id, $notif_type, $client_msg, $notif_link);
                }

                // Notify Tasker (if one was assigned to the active errand)
                if ($tasker_id > 0) {
                    $tasker_msg = "The Active Errand: \"{$errand_title}\" (ID: {$errand_id}) that you were assigned to has been permanently deleted from the system by Admin: \"{$admin_full_name}\" (ID: {$admin_id}).";
                    $this->user->addNotification($tasker_id, $notif_type, $tasker_msg, $notif_link);
                }

                // Broadcast notification to all other admins using your User object method
                if (in_array($status, ['accepted', 'in_process'])) {
                    $admin_msg = "Errand Deleted | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Status: \"{$status_labels[$status]}\" | Created by Client: \"{$client_full_name}\" (ID: {$client_id}) | Assigned to Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}) | Deleted by Admin: \"{$admin_full_name}\" (ID: {$admin_id}).";
                    $this->user->notifyOtherAdmins($admin_id, $notif_type, $admin_msg, $notif_link);
                } elseif ($status === 'pending') {
                    $admin_msg = "Errand Deleted | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Status: \"{$status_labels[$status]}\" | Created by Client: \"{$client_full_name}\" (ID: {$client_id}) | Deleted by Admin: \"{$admin_full_name}\" (ID: {$admin_id}).";
                    $this->user->notifyOtherAdmins($admin_id, $notif_type, $admin_msg, $notif_link);
                }

                $_SESSION['success'] = "Active Errand: \"{$errand_title}\" (ID: {$errand_id}) was permanently deleted.";
            } else {
                $_SESSION['error'] = "Error deleting Active Errand: \"{$errand_title}\" (ID: {$errand_id}). Please try again.";
            }

            header("Location: admin_dashboard.php");
            exit;
        }

        // Delete Finished Errand
        public function handleFinishedErrandDeletion() {
            $this->auth->restrictTo('admin');
            $admin_id = (int)$_SESSION['user_id'];

            $history_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if ($history_id <= 0) {
                $_SESSION['error'] = "Invalid request.";
                header("Location: finished_errands.php");
                exit;
            }

            // 1. Fetch history details from the pure-query model
            $history = $this->admin->getHistoryRowById($history_id);
            if (!$history) {
                $_SESSION['error'] = "Errand not found.";
                header("Location: finished_errands.php");
                exit;
            }

            $errand_title       = $history['title'];
            $original_errand_id = (int)$history['errand_id'];
            $status             = $history['status'];
            $client_id          = (int)$history['client_id'];
            $tasker_id          = (int)$history['tasker_id'];
            $client_deleted     = (int)$history['client_deleted'];
            $tasker_deleted     = (int)$history['tasker_deleted'];

            $admin_full_name  = $this->user->getFullName($admin_id);
            $client_full_name = trim($history['client_first_name'] . ' ' . $history['client_middle_name'] . ' ' . $history['client_last_name']);
            $tasker_full_name = trim($history['tasker_first_name'] . ' ' . $history['tasker_middle_name'] . ' ' . $history['tasker_last_name']);
            
            $display_status = ucwords(str_replace('_', ' ', $status));

            // 2. FILE SYSTEM CLEANUP (Handled here in Controller to keep Admin.php pure query)
            // Clean up attachments
            $attachment_dir = "../../public/uploads/history/attachments/";
            $attachment_res = $this->admin->getAttachment($history_id, true);
            if ($attachment_res && $attachment_res->num_rows > 0) {
                while ($attachment = $attachment_res->fetch_assoc()) {
                    $file_path = $attachment_dir . basename($attachment['file_path']);
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                }
            }

            // Clean up profile pictures
            $profile_dir = "../../public/uploads/history/profile_pictures/";
            if (!empty($history['client_profile_picture']) && basename($history['client_profile_picture']) !== "default.jpg") {
                $client_pic = $profile_dir . basename($history['client_profile_picture']);
                if (file_exists($client_pic)) unlink($client_pic);
            }
            if (!empty($history['tasker_profile_picture']) && basename($history['tasker_profile_picture']) !== "default.jpg") {
                $tasker_pic = $profile_dir . basename($history['tasker_profile_picture']);
                if (file_exists($tasker_pic)) unlink($tasker_pic);
            }

            // 3. Command the pure-query model to execute database removals
            if ($this->admin->deleteFinishedErrandRecord($history_id)) {
                $notif_type = "errand";
                $notif_link = "";

                // Notify Client if still visible and they exist
                if ($client_id > 0 && $client_deleted === 0) {
                    if ($this->user->getFullName($client_id) !== "Unknown User") {
                        $client_msg = "Your Finished Errand: \"{$errand_title}\" (ID: {$original_errand_id}) with Status: \"" . ucfirst($status) . "\", finished by Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}), has been permanently deleted from the system by Admin: \"{$admin_full_name}\" (ID: {$admin_id}).";
                        $this->user->addNotification($client_id, $notif_type, $client_msg, $notif_link);
                    }
                }

                // Notify Tasker if still visible and they exist
                if ($tasker_id > 0 && $tasker_deleted === 0) {
                    if ($this->user->getFullName($tasker_id) !== "Unknown User") {
                        $tasker_msg = "The Finished Errand: \"{$errand_title}\" (ID: {$original_errand_id}) with Status: \"" . ucfirst($status) . "\", which you finished and was created by Client: \"{$client_full_name}\" (ID: {$client_id}), has been permanently deleted from the system by Admin: \"{$admin_full_name}\" (ID: {$admin_id}).";
                        $this->user->addNotification($tasker_id, $notif_type, $tasker_msg, $notif_link);
                    }
                }

                // Broadcast notification to all other admins
                $admin_msg = "Errand Deleted | Errand: \"{$errand_title}\" (ID: {$original_errand_id}) | Status: \"" . ucfirst($status) . "\" | Created by Client: \"{$client_full_name}\" (ID: {$client_id}) | Finished by Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}) | Deleted by Admin: \"{$admin_full_name}\" (ID: {$admin_id}).";
                $this->user->notifyOtherAdmins($admin_id, $notif_type, $admin_msg, $notif_link);

                $_SESSION['success'] = "Errand: \"{$errand_title}\" (ID: {$original_errand_id}) was permanently deleted.";
            } else {
                $_SESSION['error'] = "Error deleting Errand: \"{$errand_title}\" (ID: {$original_errand_id}). Please try again.";
            }

            header("Location: finished_errands.php");
            exit;
        }

        public function handleAddUser() {
            $this->auth->restrictTo('admin');
            $admin_id = (int)$_SESSION['user_id'];

            $fields = [
                'email'          => '', 
                'first_name'     => '', 
                'middle_name'    => '', 
                'last_name'      => '', 
                'contact_number' => '',
                'role'           => ''
            ];

            if (isset($_SESSION['temp_fields'])) {
                $fields = $_SESSION['temp_fields'];
                unset($_SESSION['temp_fields']); 
            }

            if ($_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['email'])) {
                $data = [
                    'email'          => trim($_POST['email']),
                    'first_name'     => trim($_POST['first_name']),
                    'middle_name'    => trim($_POST['middle_name'] ?? ''),
                    'last_name'      => trim($_POST['last_name']),
                    'contact_number' => trim($_POST['contact_number']),
                    'role'           => trim($_POST['role']),
                    'password'       => $_POST['password']
                ];

                // 1. Check Email Format
                if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    $_SESSION['error'] = "Invalid email format.";
                    $_SESSION['temp_fields'] = $data;
                    header("Location: add_user.php");
                    exit;
                }

                // 2. Check Password Match
                if ($data['password'] !== $_POST['confirm_password']) {
                    $_SESSION['error'] = "Passwords do not match!";
                    $_SESSION['temp_fields'] = $data; 
                    header("Location: add_user.php");
                    exit;
                } 
                
                // 3. Duplicate Check using your existing helper inside classes/
                if ($this->user->isEmailRegistered($data['email'])) {
                    $_SESSION['error'] = "Email is already registered!";
                    $_SESSION['temp_fields'] = $data;
                    header("Location: add_user.php");
                    exit;
                }

                // 4. Run database operation through your class instances
                $new_user_id = $this->admin->addUserByAdmin($data);

                if ($new_user_id) {
                    $admin_full_name = $this->user->getFullName($admin_id);
                    $user_full_name  = $this->user->getFullName($new_user_id);

                    // Setup Notification Details
                    $notif_type = "system";
                    $notif_message = "New User Added | User: \"{$user_full_name}\" (ID: {$new_user_id}) | Role: \"" . ucfirst($data['role']) . "\" | Added by Admin: \"{$admin_full_name}\" (ID: {$admin_id})";
                    $notif_link = "../admin/view_user_info.php?id={$new_user_id}";

                    // REVISED: Calling the correct function with the logged-in admin's ID
                    $this->user->notifyOtherAdmins($admin_id, $notif_type, $notif_message, $notif_link);

                    $_SESSION['success'] = "Successfully Added User!";
                    header("Location: view_user_info.php?id={$new_user_id}");
                    exit;
                } else {
                    $_SESSION['error'] = "Error: Could not complete registration due to a system fault.";
                    $_SESSION['temp_fields'] = $data; 
                    header("Location: add_user.php");
                    exit;
                }
            }

            return ['fields' => $fields];
        }

        public function handleDeleteUser() {
            $this->auth->restrictTo('admin');
            $admin_id = (int)$_SESSION['user_id'];

            $user_id = (int)($_POST['id'] ?? 0);
            if ($user_id <= 0) {
                $_SESSION['error'] = "Invalid user identification.";
                header("Location: manage_users.php");
                exit;
            }

            $target_data = $this->admin->getUserDetails($user_id);

            if (!$target_data || !isset($target_data['user'])) {
                $_SESSION['error'] = "User not found!";
                header("Location: manage_users.php");
                exit;
            }

            $user_role = $target_data['user']['role'];
            $user_full_name = $this->user->getFullName($user_id);

            // Execute administrative account deletion process
            $deleted = $this->admin->deleteUserByAdmin($admin_id, $user_id, $user_role, $user_full_name);

            if ($deleted) {
                $_SESSION['success'] = "User: \"{$user_full_name}\" (ID: {$user_id}) has been deleted successfully.";
            } else {
                $_SESSION['error'] = "Error deleting this account. Please try again.";
            }

            header("Location: manage_users.php");
            exit;
        }
    }