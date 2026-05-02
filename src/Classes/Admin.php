<?php
    // src/Classes/Admin.php

    class Admin extends User {

        public function __construct($db_connection) {
            parent::__construct($db_connection);
        }

        // admin_dashboard.php
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

        // finished_errands.php
        public function getFinishedErrands($search = '') {
            $search_int = is_numeric($search) ? (int)$search : 0;
            $search_normalized = str_replace(',', '', $search);
            $search_query = '%' . $search_normalized . '%';
            $search_status_query = '%' . str_replace(' ', '_', strtolower($search)) . '%';

            $sql = "SELECT * FROM history 
                    WHERE status IN ('completed', 'failed')
                    AND (
                        errand_id = ? 
                        OR title LIKE ? 
                        OR REPLACE(CONCAT_WS(' ', IFNULL(client_first_name,''), IFNULL(client_middle_name,''), IFNULL(client_last_name,'')), '  ', ' ') LIKE ?
                        OR REPLACE(CONCAT_WS(' ', IFNULL(tasker_first_name,''), IFNULL(tasker_middle_name,''), IFNULL(tasker_last_name,'')), '  ', ' ') LIKE ?
                        OR DATE_FORMAT(created_at, '%M %e %Y') LIKE ?
                        OR DATE_FORMAT(completed_at, '%M %e %Y') LIKE ?
                        OR DATE_FORMAT(failed_at, '%M %e %Y') LIKE ?
                        OR fee LIKE ? 
                        OR status LIKE ?
                    )
                    ORDER BY updated_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("issssssss", 
                $search_int, $search_query, $search_query, $search_query, 
                $search_query, $search_query, $search_query, $search_query, $search_status_query
            );
            $stmt->execute();
            return $stmt->get_result();
        }

        // manage_users.php
        public function searchUsers($search, $admin_id) {
            // Handle search input
            $search_int = is_numeric($search) ? (int)$search : 0;

            // Normalize search for general fields (remove commas)
            $search_normalized = str_replace(',', '', $search);
            $search_query = '%' . $search_normalized . '%';

            $stmt = $this->db->prepare("
                (
                    SELECT 
                        id,
                        profile_picture,
                        email,
                        last_name,
                        first_name,
                        middle_name,
                        contact_number,
                        role,
                        created_at
                    FROM users
                    WHERE role = 'admin'
                    AND id != ?
                    AND (
                        id = ?
                        OR email LIKE ?
                        OR REPLACE(CONCAT_WS(' ', IFNULL(first_name,''), IFNULL(middle_name,''), IFNULL(last_name,'')), '  ', ' ') LIKE ?
                        OR contact_number LIKE ?
                        OR role LIKE ?
                        OR DATE_FORMAT(created_at, '%M %e %Y') LIKE ?
                    )
                )
                UNION ALL
                (
                    SELECT 
                        id,
                        profile_picture,
                        email,
                        last_name,
                        first_name,
                        middle_name,
                        contact_number,
                        role,
                        created_at
                    FROM users
                    WHERE role = 'tasker'
                    AND (
                        id = ?
                        OR email LIKE ?
                        OR REPLACE(CONCAT_WS(' ', IFNULL(first_name,''), IFNULL(middle_name,''), IFNULL(last_name,'')), '  ', ' ') LIKE ?
                        OR contact_number LIKE ?
                        OR role LIKE ?
                        OR DATE_FORMAT(created_at, '%M %e %Y') LIKE ?
                    )
                )
                UNION ALL
                (
                    SELECT 
                        id,
                        profile_picture,
                        email,
                        last_name,
                        first_name,
                        middle_name,
                        contact_number,
                        role,
                        created_at
                    FROM users
                    WHERE role = 'client'
                    AND (
                        id = ?
                        OR email LIKE ?
                        OR REPLACE(CONCAT_WS(' ', IFNULL(first_name,''), IFNULL(middle_name,''), IFNULL(last_name,'')), '  ', ' ') LIKE ?
                        OR contact_number LIKE ?
                        OR role LIKE ?
                        OR DATE_FORMAT(created_at, '%M %e %Y') LIKE ?
                    )
                )
                ORDER BY created_at DESC
            ");

            $stmt->bind_param(
                "iisssss"    // admin id + admin search set
                . "isssss"   // tasker search set
                . "isssss",  // client search set
                $admin_id,

                $search_int,
                $search_query,
                $search_query,
                $search_query,
                $search_query,
                $search_query,

                $search_int,
                $search_query,
                $search_query,
                $search_query,
                $search_query,
                $search_query,

                $search_int,
                $search_query,
                $search_query,
                $search_query,
                $search_query,
                $search_query
            );

            $stmt->execute();
            return $stmt->get_result();
        }

        public function getAdminMetrics($type) {
            if ($type === 'active') {
                // For Dashboard: Tracking errands currently in the system
                $sql = "SELECT 
                            IFNULL(SUM(status = 'pending'), 0) AS pending,
                            IFNULL(SUM(status = 'accepted'), 0) AS accepted,
                            IFNULL(SUM(status = 'in_process'), 0) AS in_process,
                            COUNT(*) AS total
                        FROM errands
                        WHERE status IN ('pending', 'accepted', 'in_process')";
            } elseif ($type === 'history') {
                // For Finished Errands: Tracking completed/failed and financial data
                $sql = "SELECT 
                            IFNULL(SUM(status = 'completed'), 0) AS completed,
                            IFNULL(SUM(status = 'failed'), 0) AS failed,
                            COUNT(*) AS total,
                            IFNULL(SUM(CASE WHEN status = 'completed' THEN fee ELSE 0 END), 0) AS earnings,
                            IFNULL(SUM(CASE WHEN status = 'failed' THEN fee ELSE 0 END), 0) AS failed_earnings
                        FROM history
                        WHERE status IN ('completed', 'failed')";
            } elseif ($type === 'users') {
                // For Manage Users: Role counts and overall total
                $sql = "SELECT 
                            IFNULL(SUM(role = 'admin'), 0) AS admin,
                            IFNULL(SUM(role = 'tasker'), 0) AS tasker,
                            IFNULL(SUM(role = 'client'), 0) AS client,
                            COUNT(*) AS total
                        FROM users 
                        WHERE role IN ('admin', 'tasker', 'client')";
            } else {
                return null;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
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

        public function getAttachment($id, $is_finished = false) {
            if ($is_finished) {
                $stmt = $this->db->prepare("SELECT id, file_path, original_name FROM history_attachments WHERE history_errand_id = ?");
            } else {
                $stmt = $this->db->prepare("SELECT id, file_path, original_name FROM errand_attachments WHERE errand_id = ?");
            }
            $stmt->bind_param("i", $id);
            $stmt->execute();
            return $stmt->get_result();
        }

        // view_user_info.php
        public function getUserDetails($user_id) {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if (!$user) return null;

            $data = ['user' => $user];

            if ($user['role'] === 'tasker') {
                $stmt = $this->db->prepare("SELECT IFNULL(SUM(status = 'accepted'), 0) AS accepted, IFNULL(SUM(status = 'in_process'), 0) AS in_process, COUNT(*) AS total_active FROM errands WHERE tasker_id = ? AND status IN ('accepted', 'in_process')");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $data['active'] = $stmt->get_result()->fetch_assoc();

                $stmt = $this->db->prepare("SELECT IFNULL(SUM(status = 'completed'), 0) AS completed, IFNULL(SUM(status = 'failed'), 0) AS failed, COUNT(*) AS total_finished, IFNULL(SUM(CASE WHEN status = 'completed' THEN fee ELSE 0 END), 0) AS earnings, IFNULL(SUM(CASE WHEN status = 'failed' THEN fee ELSE 0 END), 0) AS failed_earnings FROM history WHERE tasker_id = ? AND status IN ('completed', 'failed')");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $data['history'] = $stmt->get_result()->fetch_assoc();
            }
            return $data;
        }
    }