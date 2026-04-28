<?php
    // src/Controllers/TaskerController.php
    class TaskerController {
        private $tasker;
        private $auth;
        private $user;

        public function __construct($db) {
            $this->tasker = new Tasker($db); // Fixed capitalization
            $this->auth = new Auth($db);
            $this->user = new User($db);
        }

        public function index() {
            $this->auth->restrictTo('tasker');
            
            // Get the ID from the session
            $tasker_id = $_SESSION['user_id'];
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';

            // Pass $tasker_id to the methods
            $errands_pending = $this->tasker->getErrandsPending($search);
            $metrics = $this->tasker->getErrandMetrics($tasker_id);

            return [
                'errands_pending' => $errands_pending,
                'metrics' => $metrics,
                'search' => $search,
                'status_labels' => [
                    'pending' => 'Pending'
                ]
            ];
        }

        public function viewErrandDetails($errand_id) {
            $this->auth->restrictTo('tasker');
            $tasker_id = $_SESSION['user_id'];

            // 1. Back URL Logic
            $from = $_GET['from'] ?? 'dashboard';
            $back_urls = [
                'dashboard' => 'tasker_dashboard.php',
                'your_task' => 'your_task.php',
                'finished' => 'tasker_finished_errands.php',
                'notification' => '../shared/notifications.php'
            ];
            $back_url = $back_urls[$from] ?? 'tasker_dashboard.php';

            // 2. Status Check
            $status_res = $this->tasker->getErrandStatus($errand_id);
            if ($status_res->num_rows !== 1) return null;
            
            $status = $status_res->fetch_assoc()['status'];
            $is_active = in_array($status, ['pending', 'accepted', 'in_process']);
            $is_finished = in_array($status, ['completed', 'failed']);

            if ($is_active) {
                $res = $this->tasker->getActiveErrandWithClient($errand_id, $tasker_id);
                if ($res->num_rows !== 1) return null;
                $errand = $res->fetch_assoc();
                $attachments = $this->tasker->getAttachments($errand_id, false);
                $profile_dir = '../../public/uploads/profile_pictures/';
            } elseif ($is_finished) {
                $res = $this->tasker->getFinishedErrand($errand_id, $tasker_id);
                if ($res->num_rows !== 1) return null;
                $errand = $res->fetch_assoc();
                $attachments = $this->tasker->getAttachments($errand['id'], true);
                $profile_dir = '../../public/uploads/history/profile_pictures/';
            } else {
                return null;
            }

            // Client Profile Picture logic
            $client_picture = $profile_dir . 'default.jpg';
            if (!empty($errand['client_profile_picture'])) {
                $pic_path = $profile_dir . basename($errand['client_profile_picture']);
                if (file_exists($pic_path)) $client_picture = $pic_path;
            }

            return [
                'errand' => $errand,
                'attachments_result' => $attachments,
                'client_picture' => $client_picture,
                'is_active' => $is_active,
                'is_finished' => $is_finished,
                'back_url' => $back_url,
                'status_labels' => [
                    'pending' => 'Pending', 'accepted' => 'Accepted', 
                    'in_process' => 'In Process', 'completed' => 'Completed', 'failed' => 'Failed'
                ]
            ];
        }

        public function accept() {
            $this->auth->restrictTo('tasker');
            $tasker_id = $_SESSION['user_id'];

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_id'])) {
                $errand_id = (int)$_POST['accept_id'];
                
                // 1. Perform the database update via the Tasker Model
                $errand = $this->tasker->acceptErrand($errand_id, $tasker_id);

                if ($errand) {
                    $errand_title = $errand['title'];
                    $client_id = (int)$errand['client_id'];

                    // 2. Fetch full names via the user object
                    // This now works because $this->user is no longer null
                    $tasker_name = $this->user->getFullName($tasker_id); 
                    $client_name = $this->user->getFullName($client_id);

                    // 3. Notify the Client
                    $notif_type = "errand";
                    $client_msg = "Your Errand: \"{$errand_title}\" (ID: {$errand_id}) was \"Accepted\" by Tasker: \"{$tasker_name}\".";
                    $client_link = "../client/view_errand_info.php?errand_id={$errand_id}";
                    
                    $this->user->addNotification($client_id, $notif_type, $client_msg, $client_link);

                    // 4. Notify all Admins
                    $admin_msg = "Errand Accepted | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Created by Client: \"{$client_name}\" | Accepted by Tasker: \"{$tasker_name}\".";
                    $admin_link = "../admin/view_errand_info.php?errand_id={$errand_id}";
                    
                    $admins = $this->user->getUsersByRole('admin');
                    foreach ($admins as $admin) {
                        $this->user->addNotification($admin['id'], $notif_type, $admin_msg, $admin_link);
                    }
                    
                    $_SESSION['success'] = "You have successfully accepted the errand.";
                    header("Location: view_errand_info.php?errand_id=$errand_id");
                    exit;
                } else {
                    $_SESSION['error'] = "Errand no longer available.";
                }
            }
        }
    }