<?php
    // src/Classes/Client.php
    class Client extends User {

        public function __construct($db_connection) {
            parent::__construct($db_connection);
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

        // client_finished_errands.php
        public function getFinishedErrands($client_id, $search = '') {
            // Exact copy of your search logic
            $search_int = is_numeric($search) ? (int)$search : 0;
            $search_normalized = str_replace(',', '', $search);
            $search_query = '%' . $search_normalized . '%';
            
            // 1. Fetch List using your exact SQL structure
            $stmt = $this->db->prepare("
                SELECT
                    id,
                    errand_id, 
                    title, 
                    tasker_first_name,
                    tasker_middle_name,
                    tasker_last_name,
                    location, 
                    created_at, 
                    completed_at,
                    failed_at,
                    fee, 
                    status,
                    tasker_deleted
                FROM history
                WHERE client_id = ? 
                AND client_deleted = 0
                AND (
                    errand_id = ? 
                    OR title LIKE ?
                    OR REPLACE(CONCAT_WS(' ', IFNULL(client_first_name,''), IFNULL(client_middle_name,''), IFNULL(client_last_name,'')), '  ', ' ') LIKE ?
                    OR location LIKE ? 
                    OR DATE_FORMAT(created_at, '%M %e %Y') LIKE ? 
                    OR DATE_FORMAT(completed_at, '%M %e %Y') LIKE ?
                    OR DATE_FORMAT(failed_at, '%M %e %Y') LIKE ?
                    OR CAST(fee AS CHAR) LIKE ? 
                    OR status LIKE ?
                )
                ORDER BY id DESC
            ");

            $stmt->bind_param(
                "iissssssss",
                $client_id,
                $search_int,
                $search_query,
                $search_query,
                $search_query,
                $search_query,
                $search_query,
                $search_query,
                $search_query,
                $search_query
            );
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getClientMetrics($type, $client_id) {
            if ($type === 'active') {
                // Dashboard: Metrics for current/live errands
                $stmt = $this->db->prepare("
                    SELECT 
                        IFNULL(SUM(status = 'pending'), 0) AS pending,
                        IFNULL(SUM(status = 'accepted'), 0) AS accepted,
                        IFNULL(SUM(status = 'in_process'), 0) AS in_process,
                        COUNT(*) AS total
                    FROM errands
                    WHERE client_id = ? 
                    AND status IN ('pending', 'accepted', 'in_process')
                ");
            } elseif ($type === 'history') {
                // History: Metrics for finished errands
                $stmt = $this->db->prepare("
                    SELECT 
                        IFNULL(SUM(status = 'completed'), 0) AS completed,
                        IFNULL(SUM(status = 'failed'), 0) AS failed,
                        COUNT(*) AS total
                    FROM history
                    WHERE client_id = ? 
                    AND client_deleted = 0
                    AND status IN ('completed', 'failed')
                ");
            } else {
                return null;
            }

            $stmt->bind_param("i", $client_id);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }

        // view_errand_info.php
        public function getActiveErrandWithTasker($errand_id, $client_id) {
            $stmt = $this->db->prepare("
                SELECT e.*, 
                    e.id AS errand_id,
                    t.id AS tasker_id,
                    t.first_name AS tasker_first_name, 
                    t.middle_name AS tasker_middle_name, 
                    t.last_name AS tasker_last_name, 
                    t.email AS tasker_email, 
                    t.contact_number AS tasker_contact_number,
                    t.profile_picture AS tasker_profile_picture
                FROM errands AS e
                LEFT JOIN users AS t ON e.tasker_id = t.id
                WHERE e.id = ? 
                AND e.client_id = ?
                AND e.status IN ('pending', 'accepted', 'in_process')
                LIMIT 1
            ");
            $stmt->bind_param("ii", $errand_id, $client_id);
            $stmt->execute();
            return $stmt->get_result();
        }

        // view_errand_info.php
        public function getFinishedErrand($errand_id, $client_id) {
            $stmt = $this->db->prepare("
                SELECT * FROM history 
                WHERE errand_id = ? 
                AND client_id = ? 
                AND client_deleted = 0
                LIMIT 1
            ");
            $stmt->bind_param("ii", $errand_id, $client_id);
            $stmt->execute();
            return $stmt->get_result();
        }

        // create_errand.php
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

        // create_errand.php & edit_errand.php
        public function saveErrandAttachment($errand_id, $file_path, $original_name) {
            $stmt = $this->db->prepare("INSERT INTO errand_attachments (errand_id, file_path, original_name) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $errand_id, $file_path, $original_name);
            return $stmt->execute();
        }

        // edit_errand.php
        public function updateErrand($errand_id, $client_id, $data) {
            $stmt = $this->db->prepare("
                UPDATE errands 
                SET title=?, description=?, deadline=?, contact=?, location=?, fee=?
                WHERE id=? AND client_id=?
            ");
            $stmt->bind_param("sssssdii", 
                $data['title'], 
                $data['description'], 
                $data['deadline'], 
                $data['contact'], 
                $data['location'], 
                $data['fee'], 
                $errand_id, 
                $client_id
            );
            return $stmt->execute();
        }

        // edit_errand.php
        // Delete existing attachments for an errand (used when replacing with a new file)
        public function deleteErrandAttachments($errand_id) {
            // 1. Get file paths to delete from server
            $stmt = $this->db->prepare("SELECT file_path FROM errand_attachments WHERE errand_id = ?");
            $stmt->bind_param("i", $errand_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $full_path = "../../public/" . $row['file_path'];
                if (file_exists($full_path)) {
                    unlink($full_path);
                }
            }

            // 2. Delete from database
            $stmt = $this->db->prepare("DELETE FROM errand_attachments WHERE errand_id = ?");
            $stmt->bind_param("i", $errand_id);
            return $stmt->execute();
        }

        // edit_errand.php
        public function getAssignedTasker($errand_id) {
            $stmt = $this->db->prepare("SELECT u.id, u.first_name, u.last_name FROM errands e JOIN users u ON e.tasker_id = u.id WHERE e.id = ?");
            $stmt->bind_param("i", $errand_id);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }
    }