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
                'result_finished'       => $errands,
                'search'                => $search,
                'status_counts'         => [
                    'completed'   => $metrics['completed'] ?? 0,
                    'failed'      => $metrics['failed'] ?? 0
                ],
                'total_errands'         => $metrics['total'] ?? 0,
                
                // 100% Volume figures mapping
                'earnings'              => $metrics['earnings'] ?? 0.0,
                'failed_earnings'       => $metrics['failed_earnings'] ?? 0.0,
                
                // REVISED: Added to pass the company's 20% split directly into your view file
                'admin_earnings'        => $metrics['admin_earnings'] ?? 0.0,
                'admin_failed_earnings' => $metrics['admin_failed_earnings'] ?? 0.0,
                
                'status_labels'         => [
                    'completed' => 'Completed', 
                    'failed'    => 'Failed'
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
                    'pending_payment'   => (int)($data['active']['pending_payment'] ?? 0),
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
                $formatted['admin_earnings']        = (float)($data['history']['admin_earnings'] ?? 0);
                $formatted['admin_failed_earnings'] = (float)($data['history']['admin_failed_earnings'] ?? 0);
                $formatted['total_errands']          = $formatted['total_active_errands'] + $formatted['total_finished_errands'];
            } else {
                // Provide empty defaults for non-taskers to prevent "Undefined Variable" if the HTML check fails
                $formatted['active_status'] = ['pending_payment' => 0, 'in_process' => 0];
                $formatted['finished_status'] = ['completed' => 0, 'failed' => 0];
                $formatted['total_active_errands'] = 0;
                $formatted['total_finished_errands'] = 0;
                $formatted['earnings'] = 0.0;
                $formatted['failed_earnings'] = 0.0;
                $formatted['admin_earnings'] = 0.0;
                $formatted['admin_failed_earnings'] = 0.0;
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
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $target_user_id <= 0) {
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

            $errand_id = (int)($_POST['id'] ?? 0);
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $errand_id <= 0) {
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
            $client_id    = (int)$errand['client_id'];

            $admin_full_name  = $this->user->getFullName($admin_id);
            $client_full_name = $this->user->getFullName($client_id);

            // 2. FILE SYSTEM CLEANUP (Unlinks Task Attachments AND Payment Receipt File)
            // A. Remove task file attachments
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

            // B. Remove payment receipt file securely using your exact folder structure
            if (!empty($errand['payment_receipt_path'])) {
                $receipt_dir = "../../public/uploads/receipts/";
                $receipt_filename = basename($errand['payment_receipt_path']);
                $receipt_path = $receipt_dir . $receipt_filename;

                if (file_exists($receipt_path)) {
                    unlink($receipt_path);
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

                // Broadcast notification to all other admins (Hardcoded as 'Pending' since it's the only entry point)
                $admin_msg = "Errand Deleted | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Status: \"Pending\" | Created by Client: \"{$client_full_name}\" (ID: {$client_id}) | Deleted by Admin: \"{$admin_full_name}\" (ID: {$admin_id}).";
                $this->user->notifyOtherAdmins($admin_id, $notif_type, $admin_msg, $notif_link);

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

            $history_id = (int)($_POST['id'] ?? 0);
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $history_id <= 0) {
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

            // 2. FILE SYSTEM CLEANUP
            
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

            // Clean up payment receipt file securely using your exact folder structure
            if (!empty($history['payment_receipt_path'])) {
                $receipt_dir = "../../public/uploads/history/receipts/";
                $receipt_filename = basename($history['payment_receipt_path']);
                $receipt_path = $receipt_dir . $receipt_filename;

                if (file_exists($receipt_path)) {
                    unlink($receipt_path);
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
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $user_id <= 0) {
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

            // Execute database deletions and receive back structural payload collections
            $deletion_payload = $this->admin->deleteUserByAdmin($admin_id, $user_id, $user_role, $user_full_name);

            if ($deletion_payload && $deletion_payload['success']) {
                $profile_dir = "../../public/uploads/profile_pictures/";
                $attachment_dir = "../../public/uploads/attachments/";
                $receipt_dir = "../../public/uploads/receipts/";

                // 1. Delete single profile picture
                if (!empty($deletion_payload['profile_picture'])) {
                    $profile_filename = basename($deletion_payload['profile_picture']);
                    if ($profile_filename !== "default.jpg") {
                        $profile_path = $profile_dir . $profile_filename;
                        if (file_exists($profile_path)) {
                            unlink($profile_path);
                        }
                    }
                }

                // 2. Use a loop to clean up all attachment files returned across all user errands
                if (!empty($deletion_payload['attachment_paths'])) {
                    foreach ($deletion_payload['attachment_paths'] as $raw_path) {
                        $attachment_path = $attachment_dir . basename($raw_path);
                        if (file_exists($attachment_path)) {
                            unlink($attachment_path);
                        }
                    }
                }

                // 3. Use a loop to clean up all receipt files returned across all user errands
                if (!empty($deletion_payload['receipt_paths'])) {
                    foreach ($deletion_payload['receipt_paths'] as $raw_receipt) {
                        $receipt_path = $receipt_dir . basename($raw_receipt);
                        if (file_exists($receipt_path)) {
                            unlink($receipt_path);
                        }
                    }
                }

                $_SESSION['success'] = "User: \"{$user_full_name}\" (ID: {$user_id}) has been deleted successfully.";
            } else {
                $_SESSION['error'] = "Error deleting this account. Please try again.";
            }

            header("Location: manage_users.php");
            exit;
        }

        public function handleApproveCancellation() {
            $this->auth->restrictTo('admin');
            $admin_id = (int)$_SESSION['user_id'];

            $errand_id = (int)($_POST['id'] ?? 0);
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $errand_id <= 0) {
                $_SESSION['error'] = "Invalid request.";
                header("Location: admin_dashboard.php");
                exit;
            }

            $errand = $this->admin->getActiveErrandDetails($errand_id);
            if (!$errand) {
                $_SESSION['error'] = "Errand information context not found.";
                header("Location: view_errand_info.php?errand_id=" . $errand_id);
                exit;
            }

            // Capture the states consistently before running model updates
            $tasker_was_cancelling = ($errand['cancel_requested_by_tasker'] == 1);
            $client_was_cancelling = ($errand['cancel_requested_by_client'] == 1); 

            $cancellation_flags = $this->admin->approveCancellation($errand_id);

            if ($cancellation_flags) {
                $admin_full_name  = $this->user->getFullName($admin_id);
                $client_id        = (int)$errand['client_id'];
                $tasker_id        = isset($errand['tasker_id']) ? (int)$errand['tasker_id'] : 0;
                $errand_title     = $errand['title'];
                
                $client_full_name = $this->user->getFullName($client_id);
                $tasker_full_name = ($tasker_id > 0) ? $this->user->getFullName($tasker_id) : "None";

                $notif_type = "errand";
                
                $client_link = "../client/view_errand_info.php?errand_id={$errand_id}";
                $tasker_link = "../tasker/view_errand_info.php?errand_id={$errand_id}";
                $admin_link  = "../admin/view_errand_info.php?errand_id={$errand_id}";

                // 1. Send Notification to Client
                if ($client_id > 0) {
                    if ($tasker_was_cancelling && $client_was_cancelling) {
                        $client_msg = "Since both you and the assigned tasker submitted cancellation requests, Admin: \"{$admin_full_name}\" (ID: {$admin_id}) has approved the mutual cancellation for Errand: \"{$errand_title}\" (ID: {$errand_id}). The errand has returned to \"Pending\" status for a new tasker to accept. Rest assured, your uploaded proof of payment remains securely linked to this task.";
                    } elseif ($tasker_was_cancelling) {
                        $client_msg = "The tasker \"{$tasker_full_name}\" (ID: {$tasker_id}) assigned to your Errand: \"{$errand_title}\" (ID: {$errand_id}) has been released by Admin: \"{$admin_full_name}\" (ID: {$admin_id}). Your uploaded proof of payment remains securely attached, and your errand has been reset back to \"Pending\" status for a new tasker to accept.";
                    } else {
                        $client_msg = "Your request to cancel the assignment for your Errand: \"{$errand_title}\" (ID: {$errand_id}) has been approved by Admin: \"{$admin_full_name}\" (ID: {$admin_id}). Your uploaded proof of payment remains securely linked while the errand resets back to \"Pending\" status.";
                    }
                    $this->user->addNotification($client_id, $notif_type, $client_msg, $client_link);
                }

                // 2. Send Notification to Tasker
                if ($tasker_id > 0) {
                    if ($tasker_was_cancelling && $client_was_cancelling) {
                        $tasker_msg = "Since both you and the client submitted cancellation requests, Admin: \"{$admin_full_name}\" (ID: {$admin_id}) has approved the mutual cancellation for Errand: \"{$errand_title}\" (ID: {$errand_id}). You are officially released from this task, and the errand has returned to the pending pool.";
                    } elseif ($tasker_was_cancelling) {
                        $tasker_msg = "Your cancellation request from Errand: \"{$errand_title}\" (ID: {$errand_id}) has been approved by Admin: \"{$admin_full_name}\" (ID: {$admin_id}). You (Tasker ID: {$tasker_id}) are no longer assigned to this task. The errand has returned to \"Pending\" status.";
                    } else {
                        $tasker_msg = "The client \"{$client_full_name}\" (ID: {$client_id}) has requested to cancel your assignment on Errand: \"{$errand_title}\" (ID: {$errand_id}), which has been approved by Admin: \"{$admin_full_name}\" (ID: {$admin_id}). You are released from this task; the errand has been reset to \"Pending\" and the client's payment is preserved.";
                    }
                    $this->user->addNotification($tasker_id, $notif_type, $tasker_msg, $tasker_link);
                }

                // 3. Admin Broadcast Logger
                $initiator = ($tasker_was_cancelling && $client_was_cancelling) ? "Both Client & Tasker" : ($tasker_was_cancelling ? "Tasker" : "Client");
                $admin_msg = "{$initiator} Requested Cancellation Approved | Errand: \"{$errand_title}\" (ID: {$errand_id}) dropped back to \"Pending\" | Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}) | Client: \"{$client_full_name}\" (ID: {$client_id}) | Approved by Admin: \"{$admin_full_name}\" (ID: {$admin_id}).";
                $this->user->notifyOtherAdmins($admin_id, $notif_type, $admin_msg, $admin_link);

                $_SESSION['success'] = "Cancellation approved successfully. Errand reset back to pending pool.";
            } else {
                $_SESSION['error'] = "Error approving cancellation. Please try again.";
            }

            header("Location: view_errand_info.php?errand_id=" . $errand_id);
            exit;
        }

        public function handleDenyCancellation() {
            $this->auth->restrictTo('admin');
            $admin_id = (int)$_SESSION['user_id'];

            $errand_id = (int)($_POST['id'] ?? 0);
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $errand_id <= 0) {
                $_SESSION['error'] = "Invalid request.";
                header("Location: admin_dashboard.php");
                exit;
            }

            $errand = $this->admin->getActiveErrandDetails($errand_id);
            if (!$errand) {
                $_SESSION['error'] = "Errand information context not found.";
                header("Location: view_errand_info.php?errand_id=" . $errand_id);
                exit;
            }

            // Capture the states consistently before running model updates
            $tasker_was_cancelling = ($errand['cancel_requested_by_tasker'] == 1);
            $client_was_cancelling = ($errand['cancel_requested_by_client'] == 1);

            $denied = $this->admin->denyCancellation($errand_id);

            if ($denied) {
                $admin_full_name  = $this->user->getFullName($admin_id);
                $client_id        = (int)$errand['client_id'];
                $tasker_id        = isset($errand['tasker_id']) ? (int)$errand['tasker_id'] : 0;
                $errand_title     = $errand['title'];
                
                $client_full_name = $this->user->getFullName($client_id);
                $tasker_full_name = ($tasker_id > 0) ? $this->user->getFullName($tasker_id) : "None";

                $notif_type = "errand";
                
                $client_link = "../client/view_errand_info.php?errand_id={$errand_id}";
                $tasker_link = "../tasker/view_errand_info.php?errand_id={$errand_id}";
                $admin_link  = "../admin/view_errand_info.php?errand_id={$errand_id}";

                // 1. Send Notification to Client
                if ($client_id > 0) {
                    if ($tasker_was_cancelling && $client_was_cancelling) {
                        $client_msg = "Your request to cancel Errand: \"{$errand_title}\" (ID: {$errand_id}) was denied by Admin: \"{$admin_full_name}\" (ID: {$admin_id}) alongside the tasker's request. The current tasker \"{$tasker_full_name}\" (ID: {$tasker_id}) remains assigned, and the errand continues as planned.";
                    } elseif ($tasker_was_cancelling) {
                        $client_msg = "The cancellation request submitted by tasker \"{$tasker_full_name}\" (ID: {$tasker_id}) for your Errand: \"{$errand_title}\" (ID: {$errand_id}) was denied by Admin: \"{$admin_full_name}\" (ID: {$admin_id}). They remain assigned to finish your task.";
                    } else {
                        $client_msg = "Your request to cancel the assignment for Errand: \"{$errand_title}\" (ID: {$errand_id}) was denied by Admin: \"{$admin_full_name}\" (ID: {$admin_id}). The current tasker \"{$tasker_full_name}\" (ID: {$tasker_id}) remains assigned.";
                    }
                    $this->user->addNotification($client_id, $notif_type, $client_msg, $client_link);
                }

                // 2. Send Notification to Tasker
                if ($tasker_id > 0) {
                    if ($tasker_was_cancelling && $client_was_cancelling) {
                        $tasker_msg = "Your request to cancel Errand: \"{$errand_title}\" (ID: {$errand_id}) was denied by Admin: \"{$admin_full_name}\" (ID: {$admin_id}) alongside the client's request. You remain assigned as the tasker for client \"{$client_full_name}\" (ID: {$client_id}), and the errand continues as planned.";
                    } elseif ($tasker_was_cancelling) {
                        $tasker_msg = "Your cancellation request from Errand: \"{$errand_title}\" (ID: {$errand_id}) was denied by Admin: \"{$admin_full_name}\" (ID: {$admin_id}). You are still required to complete this task for client \"{$client_full_name}\" (ID: {$client_id}).";
                    } else {
                        $tasker_msg = "The cancellation request submitted by client \"{$client_full_name}\" (ID: {$client_id}) for Errand: \"{$errand_title}\" (ID: {$errand_id}) was denied by Admin: \"{$admin_full_name}\" (ID: {$admin_id}). You remain assigned to this task.";
                    }
                    $this->user->addNotification($tasker_id, $notif_type, $tasker_msg, $tasker_link);
                }

                // 3. Admin Broadcast Logger
                $initiator = ($tasker_was_cancelling && $client_was_cancelling) ? "Both Client & Tasker" : ($tasker_was_cancelling ? "Tasker" : "Client");
                $admin_msg = "{$initiator} Requested Cancellation Denied | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Assigned Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}) | Client: \"{$client_full_name}\" (ID: {$client_id}) | Denied by Admin: \"{$admin_full_name}\" (ID: {$admin_id}).";
                $this->user->notifyOtherAdmins($admin_id, $notif_type, $admin_msg, $admin_link);

                $_SESSION['success'] = "Cancellation request successfully denied.";
            } else {
                $_SESSION['error'] = "Error denying cancellation. Please try again.";
            }

            header("Location: view_errand_info.php?errand_id=" . $errand_id);
            exit;
        }

        // Case A: ADMIN APPROVES RECEIPT
        public function handlePaymentApproval() {
            $this->auth->restrictTo('admin');
            $admin_id = (int)$_SESSION['user_id'];
            
            $errand_id = (int)($_POST['id'] ?? 0);
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $errand_id <= 0) {
                $_SESSION['error'] = "Invalid request.";
                header("Location: admin_dashboard.php");
                exit;
            }

            // SAFETY CHECK
            $errand_check = $this->admin->getActiveErrandDetails($errand_id);
            if (!$errand_check || empty($errand_check['payment_receipt_path'])) {
                $_SESSION['error'] = "Action denied: No proof of payment has been uploaded for this errand yet.";
                header("Location: view_errand_info.php?errand_id={$errand_id}");
                exit;
            }

            $deletion_payload = $this->admin->approvePaymentReceipt($errand_id);
            if ($deletion_payload && $deletion_payload['success']) {
                $admin_full_name = $this->user->getFullName($admin_id);
                $errand_title = $deletion_payload['title'];
                $client_id = (int)$deletion_payload['client_id'];
                $tasker_id = $deletion_payload['tasker_id'] ? (int)$deletion_payload['tasker_id'] : null;

                $notif_type = "errand";

                $client_link = "../client/view_errand_info.php?errand_id={$errand_id}";
                $tasker_link = "../tasker/view_errand_info.php?errand_id={$errand_id}";
                $admin_link  = "../admin/view_errand_info.php?errand_id={$errand_id}";

                // 1. Notify Client
                $client_msg = "Payment Verified! Your Errand: \"{$errand_title}\" (ID: {$errand_id}) has been approved by Admin: \"{$admin_full_name}\" (ID: {$admin_id}) and is now \"In Process\".";
                $this->user->addNotification($client_id, $notif_type, $client_msg, $client_link);

                // 2. Notify Assigned Tasker (if assigned)
                if ($tasker_id) {
                    $tasker_msg = "Payment Secured! Errand: \"{$errand_title}\" (ID: {$errand_id}) has been verified by Admin: \"{$admin_full_name}\" (ID: {$admin_id}). You may now begin the task.";
                    $this->user->addNotification($tasker_id, $notif_type, $tasker_msg, $tasker_link);
                }

                // 3. Broadcast notification to all other admins
                $admin_msg = "Payment Verification Approved | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Approved by Admin: \"{$admin_full_name}\" (ID: {$admin_id}). Errand is now In Process.";
                $this->user->notifyOtherAdmins($admin_id, $notif_type, $admin_msg, $admin_link);

                $_SESSION['success'] = "Payment approved successfully. Errand is now In Process.";
                header("Location: view_errand_info.php?errand_id={$errand_id}");
                exit;
            } else {
                $_SESSION['error'] = "Failed to approve payment verification request.";
                header("Location: admin_dashboard.php");
                exit;
            }
        }

        // Case B: ADMIN REJECTS RECEIPT
        public function handlePaymentRejection() {
            $this->auth->restrictTo('admin');
            $admin_id = (int)$_SESSION['user_id'];

            $errand_id = (int)($_POST['id'] ?? 0);
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $errand_id <= 0) {
                $_SESSION['error'] = "Invalid request.";
                header("Location: admin_dashboard.php");
                exit;
            }

            // SAFETY CHECK
            $errand_check = $this->admin->getActiveErrandDetails($errand_id);
            if (!$errand_check || empty($errand_check['payment_receipt_path'])) {
                $_SESSION['error'] = "Action denied: There is no receipt file to reject.";
                header("Location: view_errand_info.php?errand_id={$errand_id}");
                exit;
            }

            $deletion_payload = $this->admin->rejectPaymentReceipt($errand_id);
            if ($deletion_payload && $deletion_payload['success']) {
                
                // Perform physical file system deletion securely within this layer
                if (!empty($deletion_payload['payment_receipt_path'])) {
                    $receipt_dir = "../../public/uploads/receipts/";
                    $receipt_filename = basename($deletion_payload['payment_receipt_path']);
                    $receipt_path = $receipt_dir . $receipt_filename;

                    if (file_exists($receipt_path)) {
                        unlink($receipt_path);
                    }
                }

                $admin_full_name = $this->user->getFullName($admin_id);
                $errand_title = $deletion_payload['title'];
                $client_id = (int)$deletion_payload['client_id'];

                $notif_type = "errand";
                $client_link = "../client/view_errand_info.php?errand_id={$errand_id}";
                $admin_link  = "../admin/view_errand_info.php?errand_id={$errand_id}";

                // 1. Notify Client to upload a new valid screenshot
                $client_msg = "Payment Rejected! The receipt submitted for Errand: \"{$errand_title}\" (ID: {$errand_id}) was declined by Admin: \"{$admin_full_name}\" (ID: {$admin_id}). Please upload a valid proof of payment screenshot.";
                $this->user->addNotification($client_id, $notif_type, $client_msg, $client_link);

                // 2. Broadcast notification to all other admins
                $admin_msg = "Payment Verification Rejected | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Rejected by Admin: \"{$admin_full_name}\" (ID: {$admin_id}).";
                $this->user->notifyOtherAdmins($admin_id, $notif_type, $admin_msg, $admin_link);

                $_SESSION['success'] = "Payment receipt rejected. Client has been notified to re-upload.";
                header("Location: view_errand_info.php?errand_id={$errand_id}");
                exit;
            } else {
                $_SESSION['error'] = "Failed to reject payment verification request.";
                header("Location: admin_dashboard.php");
                exit;
            }
        }

        // Force Cancel Active Errand by Admin
        public function handleAdminForceCancellation() {
            $this->auth->restrictTo('admin');
            $admin_id = (int)$_SESSION['user_id'];

            // Match the hidden input name from your form: admin_cancel_errand_id
            $errand_id = (int)($_POST['id'] ?? 0);
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $errand_id <= 0) {
                $_SESSION['error'] = "Invalid errand identification.";
                header("Location: admin_dashboard.php");
                exit;
            }

            // 1. Fetch current details to build targeted notifications
            $errand = $this->admin->getActiveErrandDetails($errand_id);
            if (!$errand) {
                $_SESSION['error'] = "Errand context not found.";
                header("Location: admin_dashboard.php");
                exit;
            }

            $errand_title = $errand['title'];
            $client_id    = (int)$errand['client_id'];
            $tasker_id    = isset($errand['tasker_id']) ? (int)$errand['tasker_id'] : 0;
            $old_status   = $errand['status']; // e.g., 'pending_payment' or 'in_process'

            $admin_full_name  = $this->user->getFullName($admin_id);
            $client_full_name = $this->user->getFullName($client_id);
            $tasker_full_name = ($tasker_id > 0) ? $this->user->getFullName($tasker_id) : "None";

            // 2. Execute database update to reset status to 'pending' and nullify tasker assignment
            $force_cancelled = $this->admin->forceCancelErrandRecord($errand_id);

            if ($force_cancelled) {
                $notif_type = "errand";
                
                // Define destination paths for redirects when users click their notifications
                $client_link = "../client/view_errand_info.php?errand_id={$errand_id}";
                $tasker_link = "../tasker/view_errand_info.php?errand_id={$errand_id}";
                $admin_link  = "../admin/view_errand_info.php?errand_id={$errand_id}";

                // 3. Send Notification to Client
                if ($client_id > 0) {
                    $client_msg = "Admin: \"{$admin_full_name}\" has intervened and force-cancelled the current progress on your Errand: \"{$errand_title}\" (ID: {$errand_id}). Your task has been safely reset back to \"Pending\" status. Any uploaded proof of payment remains securely linked.";
                    $this->user->addNotification($client_id, $notif_type, $client_msg, $client_link);
                }

                // 4. Send Notification to Tasker (Only if a tasker was actually assigned)
                if ($tasker_id > 0) {
                    $tasker_msg = "Admin: \"{$admin_full_name}\" has issued a critical force-cancellation on Errand: \"{$errand_title}\" (ID: {$errand_id}). You have been released from this task, and it has been returned to the pending pool.";
                    $this->user->addNotification($tasker_id, $notif_type, $tasker_msg, $tasker_link);
                }

                // 5. Broadcast Log to Other System Admins
                $readable_status = ($old_status === 'in_process') ? "In Process" : "Pending Payment";
                $admin_msg = "Critical Force-Cancellation | Errand: \"{$errand_title}\" (ID: {$errand_id}) disrupted from \"{$readable_status}\" and reset to \"Pending\" | Client: \"{$client_full_name}\" (ID: {$client_id}) | Released Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}) | Actioned by Admin: \"{$admin_full_name}\" (ID: {$admin_id}).";
                $this->user->notifyOtherAdmins($admin_id, $notif_type, $admin_msg, $admin_link);

                $_SESSION['success'] = "Errand status critically reset to pending. Tasker released.";
            } else {
                $_SESSION['error'] = "Error executing administrative force-cancellation. Please try again.";
            }

            // Redirect back cleanly to the context view page
            header("Location: view_errand_info.php?errand_id=" . $errand_id);
            exit;
        }
    }