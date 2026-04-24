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

        public function getErrandStatus($errand_id) {
            $stmt = $this->db->prepare("
                SELECT status FROM errands WHERE id = ?
                UNION
                SELECT status FROM history WHERE errand_id = ?
                LIMIT 1
            ");
            $stmt->bind_param("ii", $errand_id, $errand_id);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getActiveErrandWithClient($errand_id, $tasker_id) {
            $stmt = $this->db->prepare("
                SELECT e.*,
                    e.id AS errand_id,
                    c.id AS client_id,
                    c.first_name AS client_first_name,
                    c.middle_name AS client_middle_name, 
                    c.last_name AS client_last_name, 
                    c.email AS client_email, 
                    c.contact_number AS client_contact_number, 
                    c.profile_picture AS client_profile_picture
                FROM errands AS e
                LEFT JOIN users AS c ON e.client_id = c.id
                WHERE e.id = ?
                AND (
                        e.status = 'pending'
                        OR (
                            e.status IN ('accepted', 'in_process')
                            AND e.tasker_id = ?
                        )
                    )
                LIMIT 1
            ");
            $stmt->bind_param("ii", $errand_id, $tasker_id);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getFinishedErrand($errand_id, $tasker_id) {
            $stmt = $this->db->prepare("
                SELECT * FROM history
                WHERE errand_id = ? 
                AND tasker_id = ? 
                AND tasker_deleted = 0
                LIMIT 1
            ");
            $stmt->bind_param("ii", $errand_id, $tasker_id);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getAttachments($id, $is_finished = false) {
            if ($is_finished) {
                $stmt = $this->db->prepare("SELECT id, file_path, original_name FROM history_attachments WHERE history_errand_id = ?");
            } else {
                $stmt = $this->db->prepare("SELECT id, file_path, original_name FROM errand_attachments WHERE errand_id = ?");
            }
            $stmt->bind_param("i", $id);
            $stmt->execute();
            return $stmt->get_result();
        }
    }