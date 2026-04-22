<?php
    // src/Controllers/ClientController.php
    class ClientController {
        private $client;
        private $auth;

        public function __construct($db) {
            $this->client = new Client($db); // Fixed capitalization
            $this->auth = new Auth($db);
        }

        public function index() {
            $this->auth->restrictTo('client');
            
            // Get the ID from the session
            $client_id = $_SESSION['user_id'];
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';

            // Pass $client_id to the methods
            $errands_created = $this->client->getErrandsCreated($client_id, $search);
            $metrics = $this->client->getErrandMetrics($client_id);

            return [
                'errands_created' => $errands_created,
                'metrics' => $metrics,
                'search' => $search,
                'status_labels' => [
                    'pending' => 'Pending',
                    'accepted' => 'Accepted',
                    'in_process' => 'In Process'
                ]
            ];
        }

        public function handleCreateErrand($post, $files) {
            $this->auth->restrictTo('client');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                return [
                    'title' => '', 'description' => '', 'deadline' => '', 
                    'contact' => '', 'location' => '', 'fee' => ''
                ];
            }

            $formData = [
                'title' => trim($post['title'] ?? ''),
                'description' => trim($post['description'] ?? ''),
                'deadline' => $post['deadline'] ?? '',
                'contact' => trim($post['contact'] ?? ''),
                'location' => trim($post['location'] ?? ''),
                'fee' => $post['fee'] ?? ''
            ];

            // Simple validation
            foreach ($formData as $key => $value) {
                if (empty($value)) {
                    $_SESSION['error'] = "Please fill in all required fields.";
                    return $formData;
                }
            }

            // Format date for MySQL
            $formatted_deadline = date('Y-m-d H:i:s', strtotime($formData['deadline']));

            $insertData = $formData;
            $insertData['client_id'] = $_SESSION['user_id'];
            $insertData['deadline'] = $formatted_deadline;
            $insertData['fee'] = (float)$formData['fee'];

            $errand_id = $this->client->createErrand($insertData);

            if ($errand_id) {
                // Handle single attachment upload
                if (isset($files['attachment']) && $files['attachment']['error'] === 0) {
                    $upload_dir = '../../public/uploads/attachments/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

                    $file_extension = pathinfo($files['attachment']['name'], PATHINFO_EXTENSION);
                    $file_name = time() . '_' . uniqid() . '.' . $file_extension;
                    $target_file = $upload_dir . $file_name;

                    if (move_uploaded_file($files['attachment']['tmp_name'], $target_file)) {
                        $db_path = 'uploads/attachments/' . $file_name;
                        $this->client->saveErrandAttachment($errand_id, $db_path);
                    }
                }

                $_SESSION['success'] = "Errand created successfully!";
                header("Location: client_dashboard.php");
                exit;
            } else {
                $_SESSION['error'] = "Failed to create errand. Please try again.";
                return $formData;
            }
        }
    }