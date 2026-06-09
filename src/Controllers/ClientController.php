<?php
    // src/Controllers/ClientController.php
    class ClientController {
        private $client;
        private $auth;
        private $user;

        public function __construct($db) {
            $this->client = new Client($db);
            $this->auth = new Auth($db);
            $this->user = new User($db);
        }

        // client_dashboard.php
        public function index() {
            $this->auth->restrictTo('client');
            
            $client_id = $_SESSION['user_id'];
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';

            // Using the unified getClientMetrics function we created
            $metrics = $this->client->getClientMetrics('active', $client_id);
            $errands = $this->client->getErrandsCreated($client_id, $search);

            return [
                'errands_created' => $errands,
                'search'          => $search,
                'status_counts'   => [
                    'pending'    => $metrics['pending'] ?? 0,
                    'pending_payment'   => $metrics['pending_payment'] ?? 0,
                    'in_process' => $metrics['in_process'] ?? 0
                ],
                'total_errands'   => $metrics['total'] ?? 0,
                'status_labels'   => [
                    'pending'    => 'Pending',
                    'pending_payment'   => 'Pending Payment',
                    'in_process' => 'In Process'
                ]
            ];
        }

        // client_finished_errands.php
        public function finishedTasks() {
            $this->auth->restrictTo('client');
            $client_id = $_SESSION['user_id'];
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';

            // Fetch unified history metrics (Completed vs Failed)
            $metrics = $this->client->getClientMetrics('history', $client_id);
            
            // Fetch the search-filtered list of finished errands
            $errands = $this->client->getFinishedErrands($client_id, $search);

            return [
                'result_archived' => $errands,
                'search'          => $search,
                'status_counts'   => [
                    'completed' => $metrics['completed'] ?? 0,
                    'failed'    => $metrics['failed'] ?? 0
                ],
                'total_errands'   => $metrics['total'] ?? 0,
                'status_labels'   => [
                    'completed' => 'Completed',
                    'failed'    => 'Failed'
                ]
            ];
        }

        // view_errand_info.php
        public function viewErrandDetails($errand_id) {
            $this->auth->restrictTo('client');
            $client_id = $_SESSION['user_id'];

            // 1. Back URL Logic
            $from = $_GET['from'] ?? 'dashboard';
            $back_urls = [
                'dashboard'    => 'client_dashboard.php',
                'finished'     => 'client_finished_errands.php',
                'notification' => '../shared/notifications.php'
            ];
            $back_url = $back_urls[$from] ?? 'client_dashboard.php';

            // 2. Status Check
            $status_res = $this->client->getErrandStatus($errand_id);
            if ($status_res->num_rows !== 1) return null;
            
            $status = $status_res->fetch_assoc()['status'];
            $is_active = in_array($status, ['pending', 'pending_payment', 'in_process']);
            $is_finished = in_array($status, ['completed', 'failed']);

            if ($is_active) {
                // Fetch from active 'errands' table
                $res = $this->client->getActiveErrandWithTasker($errand_id, $client_id);
                if ($res->num_rows !== 1) return null;

                $errand = $res->fetch_assoc();
                $attachment = $this->client->getAttachment($errand_id, false);
                $profile_dir = '../../public/uploads/profile_pictures/';
            } elseif ($is_finished) {
                // Fetch from 'history' table
                $res = $this->client->getFinishedErrand($errand_id, $client_id);
                if ($res->num_rows !== 1) return null;

                $errand = $res->fetch_assoc();
                // Use history PK (id) for attachments if necessary, or errand_id based on your DB logic
                $attachment = $this->client->getAttachment($errand['id'], true); 
                $profile_dir = '../../public/uploads/history/profile_pictures/';
            } else {
                return null;
            }

            // 3. Tasker Profile Picture logic (Matches Tasker's style)
            $tasker_picture = $profile_dir . 'default.jpg';
            if (!empty($errand['tasker_profile_picture'])) {
                $pic_path = $profile_dir . basename($errand['tasker_profile_picture']);
                // Only use the path if the file actually exists on the server
                if (file_exists($pic_path)) {
                    $tasker_picture = $pic_path;
                }
            }

            return [
                'errand'             => $errand,
                'attachment_result'  => $attachment,
                'tasker_picture'     => $tasker_picture,
                'is_active'          => $is_active,
                'is_finished'        => $is_finished,
                'back_url'           => $back_url,
                'status_labels'      => [
                    'pending'    => 'Pending', 
                    'pending_payment'   => 'Pending Payment', 
                    'in_process' => 'In Process', 
                    'completed'  => 'Completed', 
                    'failed'     => 'Failed'
                ]
            ];
        }

        // create_errand.php
        public function handleCreateErrand($post, $files) {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') return null;

            $this->auth->restrictTo('client');
            $client_id = $_SESSION['user_id'];

            // 1. Basic Field Validation
            $deadline_ts = strtotime($post['deadline'] ?? '');
            if (!$deadline_ts || $deadline_ts < time()) {
                $_SESSION['error'] = "Invalid or past deadline.";
                return $post;
            }

            $data = [
                'client_id'   => $client_id,
                'title'       => trim($post['title'] ?? ''),
                'description' => trim($post['description'] ?? ''),
                'deadline'    => date('Y-m-d H:i:s', $deadline_ts),
                'contact'     => trim($post['contact'] ?? ''),
                'location'    => trim($post['location'] ?? ''),
                'fee'         => (float)($post['fee'] ?? 0)
            ];

            $allowed_types = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'docx', 'ppt', 'pptx'];
            $max_size = 10 * 1024 * 1024; // 10MB
            
            $file_size = $files['attachment']['size']; // This is an integer
            $file_name = $files['attachment']['name'];
            $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            // 2. Single File Validation
            if (!empty($files['attachment']['name'])) {

                // Check Size
                if ($file_size > $max_size) {
                    $_SESSION['error'] = "The file is too large. Maximum size is 10MB.";
                    return $post;
                }

                // Check Type
                if (!in_array($file_ext, $allowed_types)) {
                    $_SESSION['error'] = "File type .$file_ext is not allowed.";
                    return $post;
                }
            }

            // 3. Create Errand
            $errand_id = $this->client->createErrand($data);

            if ($errand_id) {
                // 4. Handle Upload
                if (!empty($files['attachment']['name']) && $files['attachment']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = "../../public/uploads/attachments/";
                    
                    // Ensure folder exists
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

                    $unique_name = "client_" . $client_id . "_" . time() . "_" . uniqid() . "." . $file_ext;
                    $destination = $upload_dir . $unique_name;
                    $db_path = "uploads/attachments/" . $unique_name;

                    if (move_uploaded_file($files['attachment']['tmp_name'], $destination)) {
                        $this->client->saveErrandAttachment($errand_id, $db_path, $file_name);
                    }
                }

                $_SESSION['success'] = "Errand created successfully!";
                header("Location: client_dashboard.php");
                exit;
            }
            
            return $post;
        }

        // edit_errand.php
        public function handleEditErrand($errand_id, $post, $files) {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') return null;

            $this->auth->restrictTo('client');
            $client_id = $_SESSION['user_id'];
            
            // 1. Validation (Same as create_errand style)
            $deadline_ts = strtotime($post['deadline'] ?? '');
            if (!$deadline_ts || $deadline_ts < time()) {
                $_SESSION['error'] = "Invalid or past deadline.";
                return $post;
            }

            $data = [
                'title'       => trim($post['title'] ?? ''),
                'description' => trim($post['description'] ?? ''),
                'deadline'    => date('Y-m-d H:i:s', $deadline_ts),
                'contact'     => trim($post['contact'] ?? ''),
                'location'    => trim($post['location'] ?? ''),
                'fee'         => (float)($post['fee'] ?? 0)
            ];

            // PowerPoint Fix: Added 'ppt' and 'pptx' to allowed types
            $allowed_types = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'docx', 'ppt', 'pptx'];
            $max_size = 10 * 1024 * 1024;

            if (!empty($files['attachment']['name'])) {
                $file_ext = strtolower(pathinfo($files['attachment']['name'], PATHINFO_EXTENSION));
                
                if ($files['attachment']['size'] > $max_size) {
                    $_SESSION['error'] = "The file is too large. Maximum size is 10MB.";
                    return $post;
                }
                if (!in_array($file_ext, $allowed_types)) {
                    $_SESSION['error'] = "File type .$file_ext is not allowed.";
                    return $post;
                }
            }

            // 2. Update Errand
            if ($this->client->updateErrand($errand_id, $client_id, $data)) {
                
                // 3. Handle File Deletion & Upload
                // Check if user ticked "Remove file" OR is uploading a replacement
                $remove_requested = isset($post['remove_current_file']);
                $new_file_uploaded = !empty($files['attachment']['name']) && $files['attachment']['error'] === UPLOAD_ERR_OK;

                if ($remove_requested || $new_file_uploaded) {
                    // This clears the database record and unlinks the physical file from the server
                    $this->client->deleteErrandAttachments($errand_id);
                }

                // Only save a new record if a file was actually provided
                if ($new_file_uploaded) {
                    $file_ext = strtolower(pathinfo($files['attachment']['name'], PATHINFO_EXTENSION));
                    $upload_dir = "../../public/uploads/attachments/";
                    $unique_name = "client_" . $client_id . "_" . time() . "_" . uniqid() . "." . $file_ext;
                    
                    if (move_uploaded_file($files['attachment']['tmp_name'], $upload_dir . $unique_name)) {
                        $this->client->saveErrandAttachment($errand_id, "uploads/attachments/" . $unique_name, $files['attachment']['name']);
                    }
                }

                $_SESSION['success'] = "Errand updated successfully!";
                header("Location: view_errand_info.php?errand_id=$errand_id");
                exit;
            }

            $_SESSION['error'] = "Failed to update errand.";
            return $post;
        }
        
        // Delete pending errand button
        public function handleDeleteErrand() {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

            // 1. Auth Guard and User ID retrieval
            $this->auth->restrictTo('client');
            $client_id = $_SESSION['user_id'];
            
            // Access $_POST directly, just like your TaskerController does
            $errand_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

            if ($errand_id > 0) {
                $errand_title = $this->client->deletePendingErrand($errand_id, $client_id);

                if ($errand_title) {
                    $_SESSION['success'] = "Your Errand: \"{$errand_title}\" (ID: {$errand_id}) request has been cancelled and deleted successfully.";
                } else {
                    $_SESSION['error'] = "Error in cancelling and deleting your Errand request. Please try again.";
                }
            } else {
                $_SESSION['error'] = "Invalid request.";
            }

            header("Location: client_dashboard.php");
            exit;
        }

        public function handleCancelAccepted() {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

            $this->auth->restrictTo('client');
            $client_id = $_SESSION['user_id'];
            $errand_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

            if ($errand_id > 0) {
                // Now returns title or false
                $errand_title = $this->client->cancelAcceptedErrand($errand_id, $client_id);

                if ($errand_title) {
                    $_SESSION['success'] = "You successfully cancelled Errand: \"{$errand_title}\" (ID: {$errand_id}). The admin and tasker has been notified.";
                } else {
                    $_SESSION['error'] = "Failed to cancel Errand (ID: {$errand_id}). It may no longer be in 'Pending Payment' status.";
                }
            } else {
                $_SESSION['error'] = "Invalid request.";
            }

            header("Location: client_dashboard.php");
            exit;
        }

        public function handlePaymentUpload() {
            // 1. Enforce authentication rules
            $this->auth->restrictTo('client');
            $client_id = $_SESSION['user_id'];

            // 2. Intercept the specific POST form submit trigger
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_payment_proof'])) {
                $errand_id = (int)$_POST['errand_id'];
                
                // 3. Structural validation of the upload array
                if (!isset($_FILES['gcash_receipt']) || $_FILES['gcash_receipt']['error'] !== UPLOAD_ERR_OK) {
                    $_SESSION['error'] = "File upload failed. Please try capturing or uploading the screenshot again.";
                    header("Location: view_errand_info.php?errand_id=" . $errand_id);
                    exit;
                }

                $file = $_FILES['gcash_receipt'];
                
                // 4. File Size Constraint Validation (10 MB Maximum limit)
                $max_size = 10 * 1024 * 1024; // 10 Megabytes in Bytes
                if ($file['size'] > $max_size) {
                    $_SESSION['error'] = "The file is too large. Maximum allowed size is 10 MB.";
                    header("Location: view_errand_info.php?errand_id=" . $errand_id);
                    exit;
                }

                // 5. Explicit Extension Check
                $filename = $file['name'];
                $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                $allowed_extensions = ['png', 'jpg', 'jpeg'];

                if (!in_array($file_ext, $allowed_extensions)) {
                    $_SESSION['error'] = "Invalid file type. Only PNG, JPG, and JPEG screenshots are permitted.";
                    header("Location: view_errand_info.php?errand_id=" . $errand_id);
                    exit;
                }

                // 6. Define Target Directory Structure
                $upload_dir = '../../public/uploads/receipts/';
                
                // Safety lock: Automatically generate directory recursively if missing
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                // 7. Sanitize filename to prevent directory traversal exploits
                // Formats filename securely: receipt_[ERRAND_ID]_[TIMESTAMP].[EXTENSION]
                $new_filename = "receipt_" . $errand_id . "_" . time() . "." . $file_ext;
                $target_filepath = $upload_dir . $new_filename;

                // 8. Move file out of temporary system memory to permanent storage
                if (move_uploaded_file($file['tmp_name'], $target_filepath)) {
                    
                    // 9. Send the sanitized filename path string directly to the model query
                    $db_save_path = "uploads/receipts/" . $new_filename;
                    $success = $this->client->submitPaymentReceipt($errand_id, $db_save_path);

                    if ($success) {
                        $errand_res = $this->client->getActiveErrandWithTasker($errand_id, $client_id);
                        $errand_data = ($errand_res && $errand_res->num_rows === 1) ? $errand_res->fetch_assoc() : null;
                        
                        $errand_title = $errand_data ? $errand_data['title'] : "Unknown Title";
                        
                        $client_full_name = $this->user->getFullName($client_id);
                        $notif_type = "errand";
                        
                        // Explicit routing path so admins can click it anywhere outside the /admin/ module context
                        $admin_link = "../admin/view_errand_info.php?errand_id={$errand_id}";
                        $admin_msg = "Proof of Payment Uploaded | Client: \"{$client_full_name}\" (ID: {$client_id}) uploaded a receipt for Errand: \"{$errand_title}\" (ID: {$errand_id}). Awaiting verification.";
                        
                        // Fire broadcast dispatch logic to all active server administrators
                        $this->user->notifyAllAdmins($notif_type, $admin_msg, $admin_link);

                        $_SESSION['success'] = "Payment proof submitted successfully! Awaiting admin verification.";
                    } else {
                        // Garbage Collection: Delete uploaded file if the database query fails
                        if (file_exists($target_filepath)) {
                            unlink($target_filepath);
                        }
                        $_SESSION['error'] = "Database sync failed. Please attempt upload procedures again.";
                    }
                } else {
                    $_SESSION['error'] = "Failed to write file image to folder directory permissions.";
                }

                header("Location: view_errand_info.php?errand_id=" . $errand_id);
                exit;
            }
        }
    }