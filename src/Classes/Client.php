<?php
    // src/Classes/Client.php
    class Client extends User {

        public function __construct($db_connection) {
            parent::__construct($db_connection);
        }

        // client_dashboard.php
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
                    updated_at,
                    cancel_requested_by_client,
                    cancel_requested_by_tasker
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
                        IFNULL(SUM(status = 'pending_payment'), 0) AS pending_payment,
                        IFNULL(SUM(status = 'in_process'), 0) AS in_process,
                        COUNT(*) AS total
                    FROM errands
                    WHERE client_id = ? 
                    AND status IN ('pending', 'pending_payment', 'in_process')
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
                AND e.status IN ('pending', 'pending_payment', 'in_process')
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
        // Gathers the paths to return as an array, then purges the database table records.
        public function deleteErrandAttachments($errand_id) {
            // 1. Fetch file paths to return to the controller
            $stmt = $this->db->prepare("SELECT file_path FROM errand_attachments WHERE errand_id = ?");
            $stmt->bind_param("i", $errand_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $file_paths = [];
            while ($row = $result->fetch_assoc()) {
                if (!empty($row['file_path'])) {
                    $file_paths[] = $row['file_path'];
                }
            }

            // 2. Clear row paths from database cleanly
            $stmt = $this->db->prepare("DELETE FROM errand_attachments WHERE errand_id = ?");
            $stmt->bind_param("i", $errand_id);
            $stmt->execute();

            return $file_paths;
        }

        public function deletePendingErrand($errand_id, $client_id) {
            // 1. Verify ownership and get title
            $stmt = $this->db->prepare("SELECT id, title FROM errands WHERE id = ? AND client_id = ? LIMIT 1");
            $stmt->bind_param("ii", $errand_id, $client_id);
            $stmt->execute();
            $check_result = $stmt->get_result();

            if ($check_result->num_rows !== 1) {
                return false;
            }

            $row = $check_result->fetch_assoc();
            $errand_title = $row['title'];

            // 2. Returns paths array instead of deleting from disk inside model
            $paths_to_delete = $this->deleteErrandAttachments($errand_id);

            // 3. Delete the main errand record itself 
            $stmt = $this->db->prepare("DELETE FROM errands WHERE id = ? AND client_id = ?");
            $stmt->bind_param("ii", $errand_id, $client_id);
            $delete_result = $stmt->execute();

            // Return payload to controller so it can clean the disk and read the title
            return $delete_result ? ['title' => $errand_title, 'files' => $paths_to_delete] : false;
        }

        // Cancel Button
        public function cancelAcceptedErrand($errand_id, $client_id) {
            // 1. Verify ownership and 'pending_payment' status
            $check_stmt = $this->db->prepare("
                SELECT title, tasker_id, payment_receipt_path 
                FROM errands 
                WHERE id = ? AND client_id = ? AND status = 'pending_payment' 
                LIMIT 1
            ");
            $check_stmt->bind_param("ii", $errand_id, $client_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows !== 1) {
                return false; 
            }

            $row = $check_result->fetch_assoc();
            $errand_title = $row['title'];
            $tasker_id = $row['tasker_id'];

            // 2. Reset the errand back to pending but KEEP the receipt path intact
            $update_stmt = $this->db->prepare("
                UPDATE errands 
                SET status = 'pending', 
                    cancel_requested_by_tasker = 0,
                    tasker_id = NULL, 
                    accepted_at = NULL, 
                    in_process_at = NULL, 
                    updated_at = NOW() 
                WHERE id = ? AND client_id = ? AND status = 'pending_payment' 
                LIMIT 1
            ");
            $update_stmt->bind_param("ii", $errand_id, $client_id);
            $update_stmt->execute();

            if ($update_stmt->affected_rows === 1) {
                // 3. Handle Notifications
                $client_full_name = $this->getFullName($client_id);
                
                $tasker_full_name = $this->getFullName($tasker_id);
                $tasker_msg = "The Errand: \"{$errand_title}\" (ID: {$errand_id}) you accepted earlier was \"Cancelled\" by the Client: \"{$client_full_name}\" (ID: {$client_id}).";
                $tasker_link = "../tasker/view_errand_info.php?errand_id={$errand_id}";
                $this->addNotification($tasker_id, "errand", $tasker_msg, $tasker_link);

                $admin_msg = "Errand Cancelled | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Previously Assigned to Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}) | Cancelled by Client: \"{$client_full_name}\" (ID: {$client_id}). Status returned to \"Pending\" status. Any submitted receipt remains preserved.";
                $admin_link = "../admin/view_errand_info.php?errand_id={$errand_id}";
                $this->notifyAllAdmins("errand", $admin_msg, $admin_link);

                return $errand_title; 
            }

            return false; 
        }

        public function submitPaymentReceipt($errand_id, $receipt_path) {
            $query = "UPDATE errands 
                    SET payment_receipt_path = ?, updated_at = NOW() 
                    WHERE id = ? AND status = 'pending_payment'";
                    
            $stmt = $this->db->prepare($query);
            if (!$stmt) {
                return false;
            }
            
            $stmt->bind_param("si", $receipt_path, $errand_id);
            $stmt->execute();
            
            return $stmt->affected_rows === 1;
        }

        // Cancel Request Button
        public function flagClientCancellationRequest($errand_id, $client_id) {
            // 1. Verify that this errand belongs to the client and is strictly in_process
            $check_stmt = $this->db->prepare("
                SELECT title 
                FROM errands 
                WHERE id = ? 
                    AND client_id = ? 
                    AND status = 'in_process' 
                LIMIT 1
            ");
            $check_stmt->bind_param("ii", $errand_id, $client_id);
            $check_stmt->execute();
            $res = $check_stmt->get_result();

            if ($res->num_rows !== 1) {
                return false;
            }
            
            $errand = $res->fetch_assoc();

            // 2. Set only the client cancel flag column to 1
            $update_stmt = $this->db->prepare("
                UPDATE errands
                SET cancel_requested_by_client = 1,
                    updated_at = NOW()
                WHERE id = ? 
                    AND client_id = ? 
                    AND status = 'in_process'
            ");
            $update_stmt->bind_param("ii", $errand_id, $client_id);
            $update_stmt->execute();

            if ($update_stmt->affected_rows === 1) {
                return $errand;
            }
            
            return false;
        }
    }