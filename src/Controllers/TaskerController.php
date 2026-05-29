<?php
    // src/Controllers/TaskerController.php
    class TaskerController {
        private $tasker;
        private $auth;
        private $user;

        public function __construct($db) {
            $this->tasker = new Tasker($db);
            $this->auth = new Auth($db);
            $this->user = new User($db);
        }
        
        // tasker_dashboard.php
        public function index() {
            $this->auth->restrictTo('tasker');
            
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';

            $metrics = $this->tasker->getTaskerMetrics('pending');
            $errands = $this->tasker->getErrandsPending($search);

            return [
                'errands_pending' => $errands,
                'search'          => $search,
                'status_counts'   => [
                    'pending' => $metrics['pending'] ?? 0 
                ],
                'total_errands'   => $metrics['total'] ?? 0,
                'status_labels'   => [
                    'pending' => 'Pending'
                ]
            ];
        }

        // your_task.php
        public function acceptedTasks() {
            $this->auth->restrictTo('tasker');
            $tasker_id = $_SESSION['user_id'];
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';

            $metrics = $this->tasker->getTaskerMetrics('accepted', $tasker_id);
            $errands = $this->tasker->getAcceptedTasks($tasker_id, $search);

            return [
                'tasks'           => $errands,
                'search'          => $search,
                'status_counts'   => [
                    'accepted'   => $metrics['accepted'] ?? 0,
                    'in_process' => $metrics['in_process'] ?? 0
                ],
                'total_errands'   => $metrics['total'] ?? 0,
                'status_labels'   => [
                    'accepted'   => 'Accepted',
                    'in_process' => 'In Process'
                ]
            ];
        } 

        // tasker_finished_errands.php
        public function finishedTasks() {
            $this->auth->restrictTo('tasker');
            $tasker_id = $_SESSION['user_id'];
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';

            $metrics = $this->tasker->getTaskerMetrics('history', $tasker_id);
            $errands = $this->tasker->getFinishedErrands($tasker_id, $search);

            return [
                'result_archived' => $errands,
                'search'          => $search,
                'status_counts'   => [
                    'completed' => $metrics['completed_count'] ?? 0,
                    'failed'    => $metrics['failed_count'] ?? 0
                ],
                'total_errands'   => $metrics['total_count'] ?? 0,
                'earnings'        => $metrics['total_earnings'] ?? 0,
                'failed_earnings' => $metrics['total_failed_earnings'] ?? 0,
                'status_labels'   => [
                    'completed' => 'Completed',
                    'failed'    => 'Failed'
                ]
            ];
        }

        // view_errand_info.php
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
                $attachment = $this->tasker->getAttachment($errand_id, false);
                $profile_dir = '../../public/uploads/profile_pictures/';
            } elseif ($is_finished) {
                $res = $this->tasker->getFinishedErrand($errand_id, $tasker_id);
                if ($res->num_rows !== 1) return null;
                $errand = $res->fetch_assoc();
                $attachment = $this->tasker->getAttachment($errand['id'], true);
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
                'attachment_result' => $attachment,
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
        
        // Accept button
        public function accept() {
            $this->auth->restrictTo('tasker');
            $tasker_id = $_SESSION['user_id'];

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
                $errand_id = (int)$_POST['id'];
                
                // 1. Perform database update (Sets status to 'accepted' and accepted_at to NOW)
                $errand = $this->tasker->acceptErrand($errand_id, $tasker_id);

                if ($errand) {
                    $errand_title = $errand['title'];
                    $client_id = (int)$errand['client_id'];

                    // 2. Fetch full names via the user class
                    $tasker_full_name = $this->user->getFullName($tasker_id); 
                    $client_full_name = $this->user->getFullName($client_id);

                    $notif_type = "errand";

                    // 3. Notify Client (Your original detailed format)
                    $client_msg = "Your Errand: \"{$errand_title}\" (ID: {$errand_id}) was \"Accepted\" by Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}).";
                    $client_link = "../client/view_errand_info.php?errand_id={$errand_id}";
                    $this->user->addNotification($client_id, $notif_type, $client_msg, $client_link);

                    // 4. Notify all Admins (Your original detailed format with all IDs)
                    $admin_msg = "Errand Accepted | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Created by Client: \"{$client_full_name}\" (ID: {$client_id}) | Accepted by Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}).";
                    $admin_link = "../admin/view_errand_info.php?errand_id={$errand_id}";
                    $this->user->notifyAllAdmins($notif_type, $admin_msg, $admin_link);
                    
                    $_SESSION['success'] = "You have successfully accepted this errand.";
                    header("Location: view_errand_info.php?errand_id=$errand_id");
                    exit;
                } else {
                    $_SESSION['error'] = "Invalid request.";
                    header("Location: tasker_dashboard.php");
                    exit;
                }
            }
        }

        // Update to In Process Button
        public function updateStatusInProcess() {
            $this->auth->restrictTo('tasker');
            $tasker_id = $_SESSION['user_id'];
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
                $errand_id = (int)$_POST['id'];
                
                // 1. Update database (Sets status to 'in_process' and in_process_at to NOW)
                $data = $this->tasker->updateToInProcess($errand_id, $tasker_id);

                if ($data) {
                    $errand_title = $data['title'];
                    $client_id = (int)$data['client_id'];

                    // 2. Fetch names for the notifications
                    $tasker_full_name = $this->user->getFullName($tasker_id);
                    $client_full_name = $this->user->getFullName($client_id);

                    $notif_type = "errand";

                    // 3. Notify Client (Your original format)
                    $client_msg = "Your Errand: \"{$errand_title}\" (ID: {$errand_id}) was updated to \"In Process\" by Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}).";
                    $client_link = "../client/view_errand_info.php?errand_id=$errand_id";
                    $this->user->addNotification($client_id, $notif_type, $client_msg, $client_link);

                    // 4. Notify Admins (Your original detailed format)
                    $admin_msg = "Errand Updated to In Process | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Created by Client: \"{$client_full_name}\" (ID: {$client_id}) | Updated by Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}).";
                    $admin_link = "../admin/view_errand_info.php?errand_id=$errand_id";
                    $this->user->notifyAllAdmins($notif_type, $admin_msg, $admin_link);

                    $_SESSION['success'] = "Errand status updated to \"In Process\".";
                    header("Location: view_errand_info.php?errand_id={$errand_id}");
                    exit;
                } else {
                    $_SESSION['error'] = "Invalid request.";
                    header("Location: tasker_dashboard.php");
                    exit;
                }
            }
        }

        // Update to Completed or Failed Button
        public function updateStatusFinal() {
            $this->auth->restrictTo('tasker');
            $tasker_id = $_SESSION['user_id'];
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['new_status'])) {
                $errand_id = (int)$_POST['id'];
                $new_status = $_POST['new_status'];

                // 1. Perform the database transaction (Snapshot to history and delete original)
                $errand = $this->tasker->completeOrFailErrand($errand_id, $tasker_id, $new_status);

                if ($errand) {
                    $errand_title = $errand['title'];
                    $client_id = (int)$errand['client_id'];

                    // 2. Fetch full names via the user class for consistency
                    $tasker_full_name = $this->user->getFullName($tasker_id); 
                    $client_full_name = $this->user->getFullName($client_id);

                    $notif_type = "errand";

                    // 3. Notify Client (Matching your original format with IDs)
                    $client_msg = "Your Errand: \"{$errand_title}\" (ID: {$errand_id}) was updated to \"" . ucfirst($new_status)  . "\" by Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}).";
                    $client_link = "../client/view_errand_info.php?errand_id=$errand_id";
                    $this->user->addNotification($client_id, $notif_type, $client_msg, $client_link);

                    // 4. Notify all Admins (Matching your original detailed format with IDs)
                    $admin_msg = "Errand Updated to " . ucfirst($new_status)  . " | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Created by Client: \"{$client_full_name}\" (ID: {$client_id}) | Updated by Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}).";
                    $admin_link = "../admin/view_errand_info.php?errand_id=$errand_id";
                    $this->user->notifyAllAdmins($notif_type, $admin_msg, $admin_link);
                    
                    $_SESSION['success'] = "Errand updated to \"" . ucfirst($new_status) . "\".";
                    header("Location: view_errand_info.php?errand_id={$errand_id}");
                    exit;
                } else {
                    $_SESSION['error'] = "Failed to finalize errand. Please try again.";
                    header("Location: your_task.php");
                    exit;
                }
            }
        }

        // Cancel Button
        public function cancelErrand() {
            $this->auth->restrictTo('tasker');
            $tasker_id = $_SESSION['user_id'];
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
                $errand_id = (int)$_POST['id'];
                
                // 1. Update database (Resets to pending, clears tasker_id and timestamps)
                $data = $this->tasker->resetToPending($errand_id, $tasker_id);

                if ($data) {
                    $errand_title = $data['title'];
                    $client_id = (int)$data['client_id'];

                    // 2. Fetch names for consistency
                    $tasker_full_name = $this->user->getFullName($tasker_id);
                    $client_full_name = $this->user->getFullName($client_id);

                    $notif_type = "errand";

                    // 3. Notify Client
                    $client_msg = "Your Errand: \"{$errand_title}\" (ID: {$errand_id}) was \"Cancelled\" by Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}).";
                    $client_link = "../client/view_errand_info.php?errand_id=$errand_id";
                    $this->user->addNotification($client_id, $notif_type, $client_msg, $client_link);

                    // 4. Notify Admins
                    $admin_msg = "Errand Cancelled | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Created by Client: \"{$client_full_name}\" (ID: {$client_id}) | Cancelled by Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}).";
                    $admin_link = "../admin/view_errand_info.php?errand_id=$errand_id";
                    $this->user->notifyAllAdmins($notif_type, $admin_msg, $admin_link);

                    $_SESSION['success'] = "You cancelled Errand: \"{$errand_title}\" (ID: {$errand_id}).";
                    header("Location: tasker_dashboard.php");
                    exit;
                } else {
                    $_SESSION['error'] = "You are not authorized to cancel this errand or it is in an invalid status.";
                    header("Location: tasker_dashboard.php");
                    exit;
                }
            }
        }
    }