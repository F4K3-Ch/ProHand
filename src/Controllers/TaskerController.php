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
                    'pending_payment'   => $metrics['pending_payment'] ?? 0,
                    'in_process' => $metrics['in_process'] ?? 0
                ],
                'total_errands'   => $metrics['total'] ?? 0,
                'status_labels'   => [
                    'pending_payment'   => 'Pending Payment',
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
            $is_active = in_array($status, ['pending', 'pending_payment', 'in_process']);
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
                    'pending' => 'Pending', 'pending_payment' => 'Pending Payment', 
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

                    // 3. Notify Client (Condition added to verify existing proof of payment)
                    if (!empty($errand['payment_receipt_path'])) {
                        // Message when payment proof is already present in the system
                        $client_msg = "Your Errand: \"{$errand_title}\" (ID: {$errand_id}) was \"Accepted\" by Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}). Your uploaded proof of payment is under review.";
                    } else {
                        // Default fallback message when no payment proof exists yet
                        $client_msg = "Your Errand: \"{$errand_title}\" (ID: {$errand_id}) was \"Accepted\" by Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}). Please settle your payment upload to begin the task.";
                    }
                    $client_link = "../client/view_errand_info.php?errand_id={$errand_id}";
                    $this->user->addNotification($client_id, $notif_type, $client_msg, $client_link);

                    // 4. Notify all Admins (Alerting them to expect a payment upload verification review)
                    $admin_msg = "Errand Accepted | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Created by Client: \"{$client_full_name}\" (ID: {$client_id}) | Accepted by Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}). Awaiting payment verification step.";
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

        // Update to Completed or Failed Button
        public function updateStatusFinal() {
            $this->auth->restrictTo('tasker');
            $tasker_id = $_SESSION['user_id'];
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['new_status'])) {
                $errand_id = (int)$_POST['id'];
                $new_status = $_POST['new_status'];

                // 1. Run Pure DB Query Operation to claim transaction blueprint details
                $migration_data = $this->tasker->completeOrFailErrand($errand_id, $tasker_id, $new_status);

                if ($migration_data) {
                    $errand_title = $migration_data['title'];
                    $client_id    = (int)$migration_data['client_id'];
                    $base_public  = __DIR__ . '/../../public/';

                    // A. Process Client Profile Snapshot Copying
                    if (!empty($migration_data['client_old_picture']) && basename($migration_data['client_old_picture']) !== 'default.jpg') {
                        $src = $base_public . ltrim($migration_data['client_old_picture'], '/');
                        $dst = $base_public . ltrim($migration_data['client_new_picture'], '/');
                        if (!is_dir(dirname($dst))) mkdir(dirname($dst), 0777, true);
                        if (file_exists($src)) copy($src, $dst);
                    }

                    // B. Process Tasker Profile Snapshot Copying
                    if (!empty($migration_data['tasker_old_picture']) && basename($migration_data['tasker_old_picture']) !== 'default.jpg') {
                        $src = $base_public . ltrim($migration_data['tasker_old_picture'], '/');
                        $dst = $base_public . ltrim($migration_data['tasker_new_picture'], '/');
                        if (!is_dir(dirname($dst))) mkdir(dirname($dst), 0777, true);
                        if (file_exists($src)) copy($src, $dst);
                    }

                    // C. Process Errand Task Attachment Relocation 
                    if (!empty($migration_data['old_attachment_path']) && !empty($migration_data['new_attachment_path'])) {
                        $src = $base_public . ltrim($migration_data['old_attachment_path'], '/');
                        $dst = $base_public . ltrim($migration_data['new_attachment_path'], '/');
                        if (!is_dir(dirname($dst))) mkdir(dirname($dst), 0777, true);

                        if (file_exists($src)) {
                            if (copy($src, $dst)) {
                                unlink($src);
                            }
                        }
                    }

                    // D. Process Payment Receipt Relocation
                    if (!empty($migration_data['old_receipt_path']) && !empty($migration_data['new_receipt_path'])) {
                        $src = $base_public . ltrim($migration_data['old_receipt_path'], '/');
                        $dst = $base_public . ltrim($migration_data['new_receipt_path'], '/');
                        if (!is_dir(dirname($dst))) mkdir(dirname($dst), 0777, true);

                        if (file_exists($src)) {
                            if (copy($src, $dst)) {
                                unlink($src);
                            }
                        }
                    }

                    $tasker_full_name = $this->user->getFullName($tasker_id); 
                    $client_full_name = $this->user->getFullName($client_id);
                    $notif_type       = "errand";

                    // Notify Client
                    $client_msg = "Your Errand: \"{$errand_title}\" (ID: {$errand_id}) was updated to \"" . ucfirst($new_status)  . "\" by Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}).";
                    $client_link = "../client/view_errand_info.php?errand_id=$errand_id";
                    $this->user->addNotification($client_id, $notif_type, $client_msg, $client_link);

                    // Notify all Admins
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

        // Cancel Request Button
        public function taskerRequestCancellation() {
            $this->auth->restrictTo('tasker');
            $tasker_id = $_SESSION['user_id'];
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
                $errand_id = (int)$_POST['id'];
                
                $data = $this->tasker->flagTaskerCancellationRequest($errand_id, $tasker_id);

                if ($data) {
                    $errand_title = $data['title'];
                    $tasker_full_name = $this->user->getFullName($tasker_id);

                    $notif_type = "errand";

                    // Notify Admins about the incoming flag request
                    $admin_msg = "Cancellation Requested | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Assigned Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}) has a cancellation request.";
                    $admin_link = "../admin/view_errand_info.php?errand_id=$errand_id";
                    $this->user->notifyAllAdmins($notif_type, $admin_msg, $admin_link);

                    $_SESSION['success'] = "Cancellation request submitted to Admin.";
                    header("Location: view_errand_info.php?errand_id=$errand_id&from=your_task");
                    exit;
                } else {
                    $_SESSION['error'] = "Unable to process request.";
                    header("Location: tasker_dashboard.php");
                    exit;
                }
            }
        }
    }