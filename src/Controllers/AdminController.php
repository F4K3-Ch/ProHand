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
                    'accepted'   => $metrics['accepted'],
                    'in_process' => $metrics['in_process']
                ],
                'total_errands'  => $metrics['total'],
                'status_labels'  => [
                    'pending' => 'Pending', 
                    'accepted' => 'Accepted', 
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
            $is_active = in_array($status, ['pending', 'accepted', 'in_process']);
            $is_finished = in_array($status, ['completed', 'failed']);

            if ($is_active) {
                $res = $this->admin->getActiveErrandFullDetails($errand_id);
                if ($res->num_rows !== 1) return null;
                $errand = $res->fetch_assoc();
                $attachment = $this->admin->getAttachment($errand_id, false);
                $profile_dir = '../../public/uploads/profile_pictures/';
            } elseif ($is_finished) {
                $res = $this->admin->getFinishedErrandFullDetails($errand_id);
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
                    'pending' => 'Pending', 'accepted' => 'Accepted', 
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

        // Reset Password Button
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

            // 3. Gather Admin and Target Details
            $admin_data = $this->user->getUserInfo($admin_id);
            $target_data = $this->admin->getUserDetails($target_user_id);

            if (!$admin_data || !$target_data || !isset($target_data['user'])) {
                $_SESSION['error'] = "User validation failed.";
                header("Location: manage_users.php");
                exit;
            }

            $user = $target_data['user'];
            $admin_full_name = trim(($admin_data['first_name'] ?? '') . ' ' . ($admin_data['middle_name'] ?? '') . ' ' . ($admin_data['last_name'] ?? ''));
            $user_full_name  = trim(($user['first_name'] ?? '') . ' ' . ($user['middle_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));

            // 4. Generate Random Temporary Password
            $charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
            $temporary_password = substr(str_shuffle($charset), 0, 10);
            $hashed_password = password_hash($temporary_password, PASSWORD_DEFAULT);

            // 5. Commit to Database
            if ($this->admin->resetUserPassword($target_user_id, $hashed_password)) {
                
                // 6. Setup Notification Payload
                $notif_type = "system";
                $notif_message = "Password Reset | User: \"{$user_full_name}\" (ID: {$target_user_id}) | Role: \"" . ucfirst($user['role'] ?? 'user') . "\" | Reset by Admin: \"{$admin_full_name}\" (ID: {$admin_id}) | Temporary Password: \"{$temporary_password}\".";
                $notif_link = "../admin/view_user_info.php?id={$target_user_id}";

                // ONE SINGLE LINE: Broadcasts to all other admins automatically!
                $this->user->notifyOtherAdmins($admin_id, $notif_type, $notif_message, $notif_link);

                $_SESSION['success'] = "Password reset successfully! Temporary password: \"$temporary_password\"";
            } else {
                $_SESSION['error'] = "Failed to reset password. Please try again.";
            }

            header("Location: view_user_info.php?id=" . $target_user_id);
            exit;
        }
    }