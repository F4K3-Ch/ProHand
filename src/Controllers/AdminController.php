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
    }