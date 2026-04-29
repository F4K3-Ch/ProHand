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
                    'accepted'   => $metrics['accepted'] ?? 0,
                    'in_process' => $metrics['in_process'] ?? 0
                ],
                'total_errands'   => $metrics['total'] ?? 0,
                'status_labels'   => [
                    'pending'    => 'Pending',
                    'accepted'   => 'Accepted',
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
            $is_active = in_array($status, ['pending', 'accepted', 'in_process']);
            $is_finished = in_array($status, ['completed', 'failed']);

            if ($is_active) {
                // Fetch from active 'errands' table
                $res = $this->client->getActiveErrandWithTasker($errand_id, $client_id);
                if ($res->num_rows !== 1) return null;

                $errand = $res->fetch_assoc();
                $attachments = $this->client->getAttachments($errand_id, false);
                $profile_dir = '../../public/uploads/profile_pictures/';
            } elseif ($is_finished) {
                // Fetch from 'history' table
                $res = $this->client->getFinishedErrand($errand_id, $client_id);
                if ($res->num_rows !== 1) return null;

                $errand = $res->fetch_assoc();
                // Use history PK (id) for attachments if necessary, or errand_id based on your DB logic
                $attachments = $this->client->getAttachments($errand['id'], true); 
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
                'attachments_result' => $attachments,
                'tasker_picture'     => $tasker_picture,
                'is_active'          => $is_active,
                'is_finished'        => $is_finished,
                'back_url'           => $back_url,
                'status_labels'      => [
                    'pending'    => 'Pending', 
                    'accepted'   => 'Accepted', 
                    'in_process' => 'In Process', 
                    'completed'  => 'Completed', 
                    'failed'     => 'Failed'
                ]
            ];
        }

        // Create Errand
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

            $allowed_types = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'docx'];
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
    }