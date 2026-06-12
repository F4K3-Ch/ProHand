<?php
    // src/Classes/Tasker.php
    class Tasker extends User {

        public function __construct($db_connection) {
            parent::__construct($db_connection);
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
                    updated_at,
                    cancel_requested_by_client,
                    cancel_requested_by_tasker
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
            return $stmt->get_result();
        }

        public function getTaskerMetrics($type, $tasker_id = null) {
            if ($type === 'pending') {
                // Dashboard: Global Available Errands
                $stmt = $this->db->prepare("
                    SELECT 
                        IFNULL(SUM(status = 'pending'), 0) AS pending,
                        COUNT(*) AS total
                    FROM errands
                    WHERE status = 'pending'
                ");
            } elseif ($type === 'pending_payment') {
                // Your Tasks: Accepted & In Processc  
                $stmt = $this->db->prepare("
                    SELECT 
                        IFNULL(SUM(status = 'pending_payment'), 0) AS pending_payment,
                        IFNULL(SUM(status = 'in_process'), 0) AS in_process,
                        COUNT(*) AS total
                    FROM errands 
                    WHERE tasker_id = ? 
                    AND status IN ('pending_payment', 'in_process')
                ");
                $stmt->bind_param("i", $tasker_id);
            } elseif ($type === 'history') {
                // Finished Errands: Completed & Failed with Earnings
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
            } else {
                return null;
            }

            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
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
                            e.status IN ('pending_payment', 'in_process')
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

        // Accept button
        public function acceptErrand($errand_id, $tasker_id) {
            $check_stmt = $this->db->prepare("SELECT title, client_id, payment_receipt_path FROM errands WHERE id = ? AND status = 'pending' LIMIT 1");
            $check_stmt->bind_param("i", $errand_id);
            $check_stmt->execute();
            $res = $check_stmt->get_result();

            if ($res->num_rows !== 1) return false;
            $errand = $res->fetch_assoc();

            $update_stmt = $this->db->prepare("UPDATE errands SET status = 'pending_payment', tasker_id = ?, accepted_at = NOW(), updated_at = NOW() WHERE id = ? AND status = 'pending'");
            $update_stmt->bind_param("ii", $tasker_id, $errand_id);
            $update_stmt->execute();

            if ($update_stmt->affected_rows === 1) {
                return $errand; // Return details for notification
            }
            return false;
        }

        // Update to Completed or Failed Button
        public function completeOrFailErrand($errand_id, $tasker_id, $new_status) {
                date_default_timezone_set('Asia/Manila');
                $current_timestamp = date('Y-m-d H:i:s');

                $this->db->begin_transaction();

                try {
                    // 1. Fetch active errand data along with user profile information
                    $errand_statement = $this->db->prepare("
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
                    $errand_statement->bind_param("ii", $errand_id, $tasker_id);
                    $errand_statement->execute();
                    $errand_data = $errand_statement->get_result()->fetch_assoc();
                    $errand_statement->close();

                    if (!$errand_data) {
                        throw new Exception("Active errand context not found.");
                    }

                    // 2. Fetch the SINGLE attachment belonging to this errand (Strictly 1 maximum)
                    $attachment_statement = $this->db->prepare("SELECT * FROM errand_attachments WHERE errand_id = ? LIMIT 1");
                    $attachment_statement->bind_param("i", $errand_id);
                    $attachment_statement->execute();
                    $active_attachment = $attachment_statement->get_result()->fetch_assoc();
                    $attachment_statement->close();

                    // 3. Pre-calculate the history table paths for profiles, receipt, and attachment
                    $client_history_picture = $this->generateHistoryPath($errand_data['client_id'], $errand_data['client_picture'], 'profile_pictures', '');
                    $tasker_history_picture = $this->generateHistoryPath($errand_data['tasker_id'], $errand_data['tasker_picture'], 'profile_pictures', '');
                    
                    $history_receipt_path = $this->generateHistoryPath($errand_id, $errand_data['payment_receipt_path'], 'receipts', '_receipt');
                    
                    $history_attachment_path = null;
                    if ($active_attachment) {
                        $history_attachment_path = $this->generateHistoryPath($errand_id, $active_attachment['file_path'], 'attachments', '_attachment');
                    }

                    $completed_at_timestamp = ($new_status === 'completed') ? $current_timestamp : null;
                    $failed_at_timestamp    = ($new_status === 'failed') ? $current_timestamp : null;

                    // 4. Record mapping migration execution into History table
                    $history_insert_query = "INSERT INTO history (
                        errand_id, title, description, deadline, contact, location, fee, status, payment_receipt_path,
                        client_id, client_first_name, client_middle_name, client_last_name, client_email, client_contact_number, client_profile_picture,
                        tasker_id, tasker_first_name, tasker_middle_name, tasker_last_name, tasker_email, tasker_contact_number, tasker_profile_picture,
                        created_at, accepted_at, in_process_at, completed_at, failed_at, updated_at, tasker_deleted, client_deleted
                    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

                    $history_statement = $this->db->prepare($history_insert_query);
                    $tasker_deleted_flag = 0; 
                    $client_deleted_flag = 0;
                    
                    $history_statement->bind_param("isssssdssissssssissssssssssssii", 
                        $errand_data['id'], $errand_data['title'], $errand_data['description'], $errand_data['deadline'], $errand_data['contact'], $errand_data['location'], $errand_data['fee'], $new_status, $history_receipt_path,
                        $errand_data['client_id'], $errand_data['client_first_name'], $errand_data['client_middle_name'], $errand_data['client_last_name'], $errand_data['client_email'], $errand_data['client_contact'], $client_history_picture,
                        $errand_data['tasker_id'], $errand_data['tasker_first_name'], $errand_data['tasker_middle_name'], $errand_data['tasker_last_name'], $errand_data['tasker_email'], $errand_data['tasker_contact'], $tasker_history_picture,
                        $errand_data['created_at'], $errand_data['accepted_at'], $errand_data['in_process_at'], $completed_at_timestamp, $failed_at_timestamp, $current_timestamp, $tasker_deleted_flag, $client_deleted_flag
                    );
                    $history_statement->execute();
                    $history_row_id = $this->db->insert_id;
                    $history_statement->close();

                    // 5. Save the SINGLE attachment record to history attachments table if it exists
                    if ($active_attachment) {
                        $history_attachment_statement = $this->db->prepare("
                            INSERT INTO history_attachments (history_errand_id, file_path, original_name, uploaded_at)
                            VALUES (?, ?, ?, ?)
                        ");
                        $history_attachment_statement->bind_param(
                            "isss", 
                            $history_row_id, 
                            $history_attachment_path, 
                            $active_attachment['original_name'], 
                            $active_attachment['uploaded_at']
                        );
                        $history_attachment_statement->execute();
                        $history_attachment_statement->close();
                    }

                    // 6. Delete active data records cleanly from live operational tables
                    $this->db->query("DELETE FROM errand_attachments WHERE errand_id = $errand_id");
                    $this->db->query("DELETE FROM errands WHERE id = $errand_id");

                    $this->db->commit();

                    // 7. Hand over clear tracking references back to the controller to run file migrations safely
                    return [
                        'title'                => $errand_data['title'],
                        'client_id'            => $errand_data['client_id'],
                        
                        'old_receipt_path'     => $errand_data['payment_receipt_path'],
                        'new_receipt_path'     => $history_receipt_path,
                        
                        'old_attachment_path'  => $active_attachment ? $active_attachment['file_path'] : null,
                        'new_attachment_path'  => $history_attachment_path,
                        
                        'client_old_picture'   => $errand_data['client_picture'],
                        'client_new_picture'   => $client_history_picture,
                        
                        'tasker_old_picture'   => $errand_data['tasker_picture'],
                        'tasker_new_picture'   => $tasker_history_picture
                    ];

                } catch (Exception $exception) {
                    $this->db->rollback();
                    error_log("History Error Query Block Failed: " . $exception->getMessage());
                    return false;
                }
            }

        // Private Helper: Pure path string generator for structural tables matching database targets.
        private function generateHistoryPath($target_id, $current_file_path, $target_folder, $filename_suffix = '') {
            if (empty($current_file_path)) {
                return ($target_folder === 'profile_pictures') ? 'uploads/history/profile_pictures/default.jpg' : null;
            }
            if (basename($current_file_path) === 'default.jpg') {
                return 'uploads/history/profile_pictures/default.jpg';
            }
            
            $file_extension = pathinfo($current_file_path, PATHINFO_EXTENSION);
            return "uploads/history/{$target_folder}/" . $target_id . $filename_suffix . '_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
        }

        // Cancel Request Button
        public function flagTaskerCancellationRequest($errand_id, $tasker_id) {
            // 1. Verify that this errand belongs to the tasker and is pending payment and in process
            $check_stmt = $this->db->prepare("
                SELECT title, client_id 
                FROM errands 
                WHERE id = ? 
                    AND tasker_id = ? 
                    AND status IN ('pending_payment', 'in_process') 
                LIMIT 1
            ");
            $check_stmt->bind_param("ii", $errand_id, $tasker_id);
            $check_stmt->execute();
            $res = $check_stmt->get_result();

            if ($res->num_rows !== 1) {
                return false;
            }

            $errand = $res->fetch_assoc();

            // 2. Set only the cancel flag column to 1
            $update_stmt = $this->db->prepare("
                UPDATE errands
                SET cancel_requested_by_tasker = 1,
                    updated_at = NOW()
                WHERE id = ? 
                    AND tasker_id = ? 
                    AND status IN ('pending_payment', 'in_process')
            ");
            $update_stmt->bind_param("ii", $errand_id, $tasker_id);
            $update_stmt->execute();

            if ($update_stmt->affected_rows === 1) {
                return $errand;
            }
            
            return false;
        }
    }