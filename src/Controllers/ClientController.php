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
    }