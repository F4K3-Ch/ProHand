<?php
    // src/Classes/Tasker.php
    class Tasker {
        private $db;

        public function __construct($db_connection) {
            $this->db = $db_connection;
        }

        public function getErrandsPending($search = '') {
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
                    status
                FROM errands
                WHERE status = 'pending' 
                AND (tasker_id IS NULL OR tasker_id = 0)
                AND (
                    id = ?
                    OR title LIKE ?
                    OR location LIKE ?
                    OR DATE_FORMAT(created_at, '%M %e %Y') LIKE ?
                    OR DATE_FORMAT(deadline, '%M %e %Y') LIKE ?
                    OR fee LIKE ? 
                    OR status LIKE ?
                )
                ORDER BY created_at DESC
            ");
            
            $stmt->bind_param(
                "issssss", 
                $search_int,   // Errand ID exact match
                $search_query, // Title LIKE
                $search_query, // Location LIKE
                $search_query, // Created date LIKE (e.g., "November 30 2005")
                $search_query, // Deadline date LIKE (e.g., "November 30 2005")
                $search_query,  // Fee LIKE
                $search_status_query  // Status LIKE
            );
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getErrandMetrics() {
            $result = $this->db->query("
                SELECT 
                    SUM(status = 'pending') AS pending_count
                FROM errands
            ");
            return $result->fetch_assoc();
        }
    }