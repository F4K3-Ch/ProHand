<?php
    // src/Controllers/AdminController.php

    class AdminController {
        private $admin;
        private $auth;

        public function __construct($db) {
            $this->admin = new Admin($db);
            $this->auth = new Auth($db);
        }

        public function index() {
            // 1. Security check
            $this->auth->restrictTo('admin');

            // 2. Handle Search Logic
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';

            // 3. Get Data from the Class
            $active_errands = $this->admin->getActiveErrands($search);
            $metrics = $this->admin->getErrandMetrics();

            // 4. Return everything the View needs
            return [
                'active_errands' => $active_errands,
                'metrics' => $metrics,
                'search' => $search,
                'status_labels' => [
                    'pending' => 'Pending',
                    'accepted' => 'Accepted',
                    'in_process' => 'In Process'
                ]
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
                $attachments = $this->admin->getAttachments($errand_id, false);
                $profile_dir = '../../public/uploads/profile_pictures/';
            } elseif ($is_finished) {
                $res = $this->admin->getFinishedErrandFullDetails($errand_id);
                if ($res->num_rows !== 1) return null;
                $errand = $res->fetch_assoc();
                $attachments = $this->admin->getAttachments($errand['id'], true);
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
                'attachments_result' => $attachments,
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
    }