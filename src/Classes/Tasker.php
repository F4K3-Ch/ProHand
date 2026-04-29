<?php
    // src/Classes/Tasker.php
    class Tasker {
        private $db;

        public function __construct($db_connection) {
            $this->db = $db_connection;
        }

        // tasker_dashboard.php
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

        // tasker_dashboard.php
        public function getPendingMetrics() {
            // This is the global "Available" count for the dashboard
            $stmt = $this->db->prepare("
                SELECT 
                    IFNULL(SUM(status = 'pending'), 0) AS pending,
                    COUNT(*) AS total
                FROM errands
                WHERE status = 'pending'
            ");
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }

        // your_task.php
        public function getAcceptedTasks($tasker_id, $search = '') {
            // Exact copy of your search logic
            $search_int = is_numeric($search) ? (int)$search : 0;

            // Normalize search for general fields (remove commas)
            $search_normalized = str_replace(',', '', $search);
            $search_query = '%' . $search_normalized . '%';

            // Normalize search specifically for status
            $search_status_normalized = str_replace(' ', '_', strtolower($search)); 
            $search_status_query = '%' . $search_status_normalized . '%';

            // Fetch Accepted/Active Errands using your exact SQL structure
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
                WHERE tasker_id = ?
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
                $tasker_id,            // User ID
                $search_int,           // Errand ID exact match
                $search_query,          // Title LIKE
                $search_query,          // Location LIKE
                $search_query,          // Created date LIKE
                $search_query,          // Deadline date LIKE
                $search_query,          // Fee LIKE
                $search_status_query    // Status LIKE
            );

            $stmt->execute();
            return $stmt->get_result();
        }

        // your_task.php
        public function getAcceptedTaskMetrics($tasker_id) {
            $stmt = $this->db->prepare("
                SELECT 
                    IFNULL(SUM(status = 'accepted'), 0) AS accepted,
                    IFNULL(SUM(status = 'in_process'), 0) AS in_process,
                    COUNT(*) AS total
                FROM errands 
                WHERE tasker_id = ? 
                AND status IN ('accepted', 'in_process')
            ");
            
            $stmt->bind_param("i", $tasker_id);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }

        // tasker_finished_errands.php
        public function getFinishedErrands($tasker_id, $search = '') {
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
                    client_first_name,
                    client_middle_name,
                    client_last_name,
                    location, 
                    created_at, 
                    completed_at,
                    failed_at,
                    fee, 
                    status,
                    tasker_deleted
                FROM history
                WHERE tasker_id = ? 
                AND tasker_deleted = 0
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
                $tasker_id,
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
            $list = $stmt->get_result();

            // 2. Metrics Query
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_count,
                    IFNULL(SUM(status = 'completed'), 0) as completed_count,
                    IFNULL(SUM(status = 'failed'), 0) as failed_count,
                    IFNULL(SUM(CASE WHEN status = 'completed' THEN fee ELSE 0 END), 0) as total_earnings,
                    IFNULL(SUM(CASE WHEN status = 'failed' THEN fee ELSE 0 END), 0) as total_failed_earnings
                FROM history 
                WHERE tasker_id = ? AND tasker_deleted = 0
            ");
            $stmt->bind_param("i", $tasker_id);
            $stmt->execute();
            $metrics = $stmt->get_result()->fetch_assoc();

            return ['list' => $list, 'metrics' => $metrics];
        }

        // view_errand_info.php
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

        // view_errand_info.php
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

        // view_errand_info.php
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

        // view_errand_info.php
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

        public function acceptErrand($errand_id, $tasker_id) {
            // 1. Check if errand is still pending
            $check_stmt = $this->db->prepare("SELECT title, client_id FROM errands WHERE id = ? AND status = 'pending' LIMIT 1");
            $check_stmt->bind_param("i", $errand_id);
            $check_stmt->execute();
            $res = $check_stmt->get_result();

            if ($res->num_rows !== 1) return false;
            $errand = $res->fetch_assoc();

            // 2. Update status to accepted
            $update_stmt = $this->db->prepare("UPDATE errands SET status = 'accepted', tasker_id = ?, accepted_at = NOW(), updated_at = NOW() WHERE id = ? AND status = 'pending'");
            $update_stmt->bind_param("ii", $tasker_id, $errand_id);
            $update_stmt->execute();

            if ($update_stmt->affected_rows === 1) {
                return $errand; // Return details for notification
            }
            return false;
        }

        public function updateToInProcess($errand_id, $tasker_id) {
            // 1. Fetch info and verify the tasker owns this 'accepted' errand
            $stmt = $this->db->prepare("
                SELECT title, client_id 
                FROM errands 
                WHERE id = ? AND tasker_id = ? AND status = 'accepted'
                LIMIT 1
            ");
            $stmt->bind_param("ii", $errand_id, $tasker_id);
            $stmt->execute();
            $errand = $stmt->get_result()->fetch_assoc();

            if (!$errand) return false;

            // 2. Update the status and set the NEW in_process_at column
            // We do NOT touch accepted_at here
            $update = $this->db->prepare("
                UPDATE errands 
                SET status = 'in_process', 
                    in_process_at = NOW(), 
                    updated_at = NOW() 
                WHERE id = ?
            ");
            $update->bind_param("i", $errand_id);
            
            if ($update->execute()) {
                return $errand;
            }
            return false;
        }

        public function completeOrFailErrand($errand_id, $tasker_id, $new_status) {
            // FIX 1: Set the timezone to Philippine Time
            date_default_timezone_set('Asia/Manila');
            $now = date('Y-m-d H:i:s');

            $this->db->begin_transaction();

            try {
                // Fetch errand with JOINs
                $stmt = $this->db->prepare("
                    SELECT e.*, 
                        c.first_name AS client_first_name, c.middle_name AS client_middle_name, c.last_name AS client_last_name,
                        c.email AS client_email, c.contact_number AS client_contact, c.profile_picture AS client_picture,
                        t.first_name AS tasker_first_name, t.middle_name AS tasker_middle_name, t.last_name AS tasker_last_name,
                        t.email AS tasker_email, t.contact_number AS tasker_contact, t.profile_picture AS tasker_picture
                    FROM errands AS e
                    LEFT JOIN users AS c ON e.client_id = c.id
                    LEFT JOIN users AS t ON e.tasker_id = t.id
                    WHERE e.id = ? AND e.tasker_id = ?
                    LIMIT 1
                ");
                $stmt->bind_param("ii", $errand_id, $tasker_id);
                $stmt->execute();
                $errand = $stmt->get_result()->fetch_assoc();

                if (!$errand) throw new Exception("Errand not found.");

                $client_profile_history = $this->snapshotProfilePic($errand['client_id'], $errand['client_picture']);
                $tasker_profile_history = $this->snapshotProfilePic($errand['tasker_id'], $errand['tasker_picture']);

                $comp_at = ($new_status === 'completed') ? $now : null;
                $fail_at = ($new_status === 'failed') ? $now : null;

                // Insert into history
                $history_sql = "INSERT INTO history (
                    errand_id, title, description, deadline, contact, location, fee, status,
                    client_id, client_first_name, client_middle_name, client_last_name, client_email, client_contact_number, client_profile_picture,
                    tasker_id, tasker_first_name, tasker_middle_name, tasker_last_name, tasker_email, tasker_contact_number, tasker_profile_picture,
                    created_at, accepted_at, in_process_at, completed_at, failed_at, updated_at, tasker_deleted, client_deleted
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

                $ins = $this->db->prepare($history_sql);
                $t_del = 0; $c_del = 0;
                $ins->bind_param("isssssdsissssssissssssssssssii", 
                    $errand['id'], $errand['title'], $errand['description'], $errand['deadline'], $errand['contact'], $errand['location'], $errand['fee'], $new_status,
                    $errand['client_id'], $errand['client_first_name'], $errand['client_middle_name'], $errand['client_last_name'], $errand['client_email'], $errand['client_contact'], $client_profile_history,
                    $errand['tasker_id'], $errand['tasker_first_name'], $errand['tasker_middle_name'], $errand['tasker_last_name'], $errand['tasker_email'], $errand['tasker_contact'], $tasker_profile_history,
                    $errand['created_at'], $errand['accepted_at'], $errand['in_process_at'], $comp_at, $fail_at, $now, $t_del, $c_del
                );
                $ins->execute();
                $history_id = $this->db->insert_id;

                // FIX 2: This helper now handles the physical UNLINK (deletion)
                $this->moveAttachmentsToHistory($errand_id, $history_id);

                // Delete active records
                $this->db->query("DELETE FROM errand_attachments WHERE errand_id = $errand_id");
                $this->db->query("DELETE FROM errands WHERE id = $errand_id");

                $this->db->commit();
                return $errand;

            } catch (Exception $e) {
                $this->db->rollback();
                error_log("History Error: " . $e->getMessage());
                return false;
            }
        }

        // Helper function for Profile Picture Copying
        private function snapshotProfilePic($uid, $pic_path) {
            // 1. Default path if no picture exists
            $default_db_path = 'uploads/history/profile_pictures/default.jpg';
            
            if (!empty($pic_path) && basename($pic_path) !== 'default.jpg') {
                $ext = pathinfo($pic_path, PATHINFO_EXTENSION);
                $unique_name = $uid . '_' . time() . '_' . rand(1000,9999) . '.' . $ext;
                
                // 2. This is the string that will be saved in the 'history' table
                // We exclude the '../../public/' part so the DB stays clean
                $history_db_path = 'uploads/history/profile_pictures/' . $unique_name;
                
                // 3. Absolute Filesystem Paths for the copy() operation
                // From src/Classes/Tasker.php, we go:
                // ../../             -> Project Root
                // public/            -> Public Folder
                // ltrim($pic_path)   -> The original path (e.g., uploads/profiles/me.jpg)
                $src_fs  = __DIR__ . '/../../public/' . ltrim($pic_path, '/');
                $dest_fs = __DIR__ . '/../../public/' . $history_db_path;
                
                // 4. Ensure the history folder exists
                $dir = dirname($dest_fs);
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }

                // 5. Perform the physical copy
                if (file_exists($src_fs)) {
                    copy($src_fs, $dest_fs);
                    return $history_db_path; // Return the new unique path
                }
            }
            
            return $default_db_path;
        }

        // Helper function for Attachments
        private function moveAttachmentsToHistory($errand_id, $history_id) {
            // 1. Get attachments from the ACTIVE table (Check if your table is 'errand_attachments')
            $stmt = $this->db->prepare("SELECT * FROM errand_attachments WHERE errand_id = ?");
            $stmt->bind_param("i", $errand_id);
            $stmt->execute();
            $attachments_result = $stmt->get_result();

            while ($file = $attachments_result->fetch_assoc()) {
                // 2. Generate a unique name for the history folder
                $extension = pathinfo($file['file_path'], PATHINFO_EXTENSION);
                $unique_name = $errand_id . '_' . time() . '_' . rand(1000, 9999) . '.' . $extension;

                // 3. Path for the DATABASE (Front-end view)
                $history_db_path = 'uploads/history/attachments/' . $unique_name;

                // 4. Physical Filesystem Paths (Server-side)
                // From src/Classes, go up two levels to reach /public/
                $original_file_fs = __DIR__ . '/../../public/' . ltrim($file['file_path'], '/');
                $history_file_fs  = __DIR__ . '/../../public/' . $history_db_path;

                // Ensure the history/attachments folder exists
                $dir = dirname($history_file_fs);
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }

                // 5. Copy and then delete the original file
                if (file_exists($original_file_fs)) {
                    if (copy($original_file_fs, $history_file_fs)) {
                        unlink($original_file_fs); // Delete original from active uploads folder
                    }
                }

                // 6. Insert into history_attachments table
                // Note: Check if your column is 'history_errand_id'
                $insert_stmt = $this->db->prepare("
                    INSERT INTO history_attachments (history_errand_id, file_path, original_name, uploaded_at)
                    VALUES (?, ?, ?, ?)
                ");
                $insert_stmt->bind_param(
                    "isss", 
                    $history_id, 
                    $history_db_path,
                    $file['original_name'], 
                    $file['uploaded_at']
                );
                $insert_stmt->execute();
            }
        }

        public function resetToPending($errand_id, $tasker_id) {
            // 1. Verify that this errand belongs to the tasker and can be cancelled
            $check_stmt = $this->db->prepare("
                SELECT title, client_id 
                FROM errands 
                WHERE id = ? AND tasker_id = ? AND status IN ('accepted', 'in_process') 
                LIMIT 1
            ");
            $check_stmt->bind_param("ii", $errand_id, $tasker_id);
            $check_stmt->execute();
            $res = $check_stmt->get_result();

            if ($res->num_rows !== 1) return false;
            $errand = $res->fetch_assoc();

            // 2. Reset the errand status and clear tasker-specific data
            $update_stmt = $this->db->prepare("
                UPDATE errands
                SET status = 'pending',
                    tasker_id = NULL,
                    accepted_at = NULL, 
                    in_process_at = NULL,
                    updated_at = NOW()
                WHERE id = ? AND tasker_id = ? AND status IN ('accepted', 'in_process')
            ");
            $update_stmt->bind_param("ii", $errand_id, $tasker_id);
            $update_stmt->execute();

            if ($update_stmt->affected_rows === 1) {
                return $errand; // Return details for the Controller to use in notifications
            }
            
            return false;
        }
    }