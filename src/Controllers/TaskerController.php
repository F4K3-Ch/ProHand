<?php
    // src/Controllers/TaskerController.php
    class TaskerController {
        private $tasker;
        private $auth;

        public function __construct($db) {
            $this->tasker = new Tasker($db); // Fixed capitalization
            $this->auth = new Auth($db);
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
    }