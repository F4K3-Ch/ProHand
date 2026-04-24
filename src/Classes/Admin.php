<?php
    // src/Classes/Admin.php

    class Admin {
        private $db;

        public function __construct($db_connection) {
            $this->db = $db_connection;
        }

        /**
         * Fetches active errands with optional search filters
         */
        public function getActiveErrands($search = '') {
            $search_int = is_numeric($search) ? (int)$search : 0;
            
            // Normalize search for general fields (remove commas)
            $search_normalized = str_replace(',', '', $search);
            $search_query = '%' . $search_normalized . '%';

            // Normalize search specifically for status (handle underscores)
            $search_status_normalized = str_replace(' ', '_', strtolower($search)); 
            $search_status_query = '%' . $search_status_normalized . '%';

            // Unassigned Search Logic
            $unassigned_terms = ['unassigned', 'none', 'no tasker', 'null'];
            $is_unassigned_search = in_array(strtolower(trim($search)), $unassigned_terms) ? 1 : 0;

            $stmt = $this->db->prepare("
                SELECT 
                    e.id,
                    e.title,
                    e.created_at,
                    e.deadline,
                    c.first_name AS client_first_name,
                    c.middle_name AS client_middle_name,
                    c.last_name AS client_last_name,
                    t.id AS tasker_id,
                    t.first_name AS tasker_first_name,
                    t.middle_name AS tasker_middle_name,
                    t.last_name AS tasker_last_name,
                    e.fee,
                    e.status
                FROM errands AS e
                LEFT JOIN users AS c ON e.client_id = c.id
                LEFT JOIN users AS t ON e.tasker_id = t.id
                WHERE e.status IN ('pending', 'accepted', 'in_process')
                AND (
                    e.id = ? 
                    OR e.title LIKE ? 
                    OR REPLACE(CONCAT_WS(' ', IFNULL(c.first_name,''), IFNULL(c.middle_name,''), IFNULL(c.last_name,'')), '  ', ' ') LIKE ?
                    OR REPLACE(CONCAT_WS(' ', IFNULL(t.first_name,''), IFNULL(t.middle_name,''), IFNULL(t.last_name,'')), '  ', ' ') LIKE ?
                    OR DATE_FORMAT(e.created_at, '%M %e %Y') LIKE ?
                    OR DATE_FORMAT(e.deadline, '%M %e %Y') LIKE ?
                    OR e.fee LIKE ?
                    OR e.status LIKE ?
                    OR (? = 1 AND e.tasker_id IS NULL)
                )
                ORDER BY e.updated_at DESC
            ");
            
            // The bind_param order must match the '?' in the SQL exactly:
            // i = id, s = title, s = client name, s = tasker name, s = created, s = deadline, s = fee, s = status, i = unassigned
            $stmt->bind_param(
                "isssssssi",
                $search_int,            // e.id
                $search_query,          // e.title
                $search_query,          // Client full name
                $search_query,          // Tasker full name
                $search_query,          // Created date
                $search_query,          // Deadline date
                $search_query,          // e.fee
                $search_status_query,   // e.status
                $is_unassigned_search   // tasker_id IS NULL check
            );

            $stmt->execute();
            return $stmt->get_result();
        }

        /**
         * Gets summary counts for the dashboard cards
         */
        public function getErrandMetrics() {
            $result = $this->db->query("
                SELECT 
                    SUM(status = 'pending') AS pending_count,
                    SUM(status = 'accepted') AS accepted_count,
                    SUM(status = 'in_process') AS in_process_count,
                    COUNT(*) AS total_errands
                FROM errands
                WHERE status IN ('pending', 'accepted', 'in_process')
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

        public function getActiveErrandFullDetails($errand_id) {
            $stmt = $this->db->prepare("
                SELECT e.*,
                    e.id AS errand_id,
                    c.id AS client_id,
                    c.first_name AS client_first_name,
                    c.middle_name AS client_middle_name, 
                    c.last_name AS client_last_name, 
                    c.email AS client_email, 
                    c.contact_number AS client_contact_number, 
                    c.profile_picture AS client_profile_picture,
                    t.id AS tasker_id,
                    t.first_name AS tasker_first_name, 
                    t.middle_name AS tasker_middle_name, 
                    t.last_name AS tasker_last_name, 
                    t.email AS tasker_email, 
                    t.contact_number AS tasker_contact_number, 
                    t.profile_picture AS tasker_profile_picture
                FROM errands AS e
                LEFT JOIN users AS c ON e.client_id = c.id
                LEFT JOIN users AS t ON e.tasker_id = t.id
                WHERE e.id = ?
                LIMIT 1
            ");
            $stmt->bind_param("i", $errand_id);
            $stmt->execute();
            return $stmt->get_result();
        }

        public function getFinishedErrandFullDetails($errand_id) {
            $stmt = $this->db->prepare("SELECT * FROM history WHERE errand_id = ? LIMIT 1");
            $stmt->bind_param("i", $errand_id);
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