<?php
    // src/Classes/Client.php
    class Client {
        private $db;

        public function __construct($db_connection) {
            $this->db = $db_connection;
        }

        public function getErrandsCreated($client_id, $search = '') {
            // Handle search input
            $search_int = is_numeric($search) ? (int)$search : 0;

            // Normalize search for general fields (remove commas)
            $search_normalized = str_replace(',', '', $search);
            $search_query = '%' . $search_normalized . '%';

            // Normalize search specifically for status
            $search_status_normalized = str_replace(' ', '_', strtolower($search)); 
            $search_status_query = '%' . $search_status_normalized . '%';

            $stmt = $this->db->prepare("
                SELECT 
                    id, 
                    title, 
                    location, 
                    created_at, 
                    deadline, 
                    fee, 
                    status, 
                    updated_at
                FROM errands
                WHERE client_id = ? 
                AND (
                    id = ?
                    OR title LIKE ?
                    OR location LIKE ?
                    OR DATE_FORMAT(created_at, '%M %e %Y') LIKE ?
                    OR DATE_FORMAT(deadline, '%M %e %Y') LIKE ?
                    OR fee LIKE ?
                    OR status LIKE ?
                )
                ORDER BY updated_at DESC
            ");
            
            $stmt->bind_param(
                "iissssss", 
                $client_id,             // User ID
                $search_int,            // Errand ID exact match
                $search_query,          // Title LIKE
                $search_query,          // Location LIKE
                $search_query,          // Created date LIKE (e.g., "November 30 2005")
                $search_query,          // Deadline date LIKE (e.g., "November 30 2005")
                $search_query,          // Fee LIKE
                $search_status_query    // Status LIKE
            );
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getErrandMetrics($client_id) {
            $stmt = $this->db->prepare("
                SELECT 
                    SUM(status = 'pending') AS pending_count,
                    SUM(status = 'accepted') AS accepted_count,
                    SUM(status = 'in_process') AS in_process_count,
                    COUNT(*) AS total_errands
                FROM errands
                WHERE client_id = ? 
                AND status IN ('pending', 'accepted', 'in_process')
            ");
            $stmt->bind_param("i", $client_id);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }

        public function createErrand($data) {
            $stmt = $this->db->prepare("INSERT INTO errands (client_id, title, description, deadline, contact, location, fee, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
            
            $stmt->bind_param("isssssd", 
                $data['client_id'],
                $data['title'],
                $data['description'],
                $data['deadline'],
                $data['contact'],
                $data['location'],
                $data['fee']
            );

            if ($stmt->execute()) {
                return $this->db->insert_id;
            }
            return false;
        }

        public function saveErrandAttachment($errand_id, $file_path, $original_name) {
            $stmt = $this->db->prepare("INSERT INTO errand_attachments (errand_id, file_path, original_name) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $errand_id, $file_path, $original_name);
            return $stmt->execute();
        }
    }