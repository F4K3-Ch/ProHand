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
        public function viewUserInfo() {
            $this->auth->restrictTo('admin');
            $target_user_id = (int)($_GET['id'] ?? 0);
            
            if ($target_user_id <= 0) {
                header("Location: manage_users.php");
                exit;
            }

            $data = $this->admin->getUserDetails($target_user_id);
            if (!$data) {
                header("Location: manage_users.php");
                exit;
            }

            // FIX: Capture the 'from' parameter from the URL (defaulting to admin_manage)
            $from = $_GET['from'] ?? 'admin_manage';

            // Pass the actual $from variable to the formatter instead of hard-coding 'admin_manage'
            $formatted = $this->user->formatUserDisplayData($data['user'], $from);

            if ($data['user']['role'] === 'tasker') {
                $formatted['active_status'] = $data['active'];
                $formatted['finished_status'] = $data['history'];
                
                $formatted['total_active_errands'] = (int)$data['active']['total_active'];
                $formatted['total_finished_errands'] = (int)$data['history']['total_finished'];
                $formatted['earnings'] = (float)$data['history']['earnings'];
                $formatted['failed_earnings'] = (float)$data['history']['failed_earnings'];
                $formatted['total_errands'] = $formatted['total_active_errands'] + $formatted['total_finished_errands'];
            }

            return $formatted;
        }
    }