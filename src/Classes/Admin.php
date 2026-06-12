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
                WHERE e.status IN ('pending', 'pending_payment', 'in_process')
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
                            IFNULL(SUM(status = 'pending_payment'), 0) AS pending_payment,
                            IFNULL(SUM(status = 'in_process'), 0) AS in_process,
                            COUNT(*) AS total
                        FROM errands
                        WHERE status IN ('pending', 'pending_payment', 'in_process')";
            } elseif ($type === 'history') {
                // For Finished Errands: Tracking completed/failed and financial data
                $sql = "SELECT 
                            IFNULL(SUM(status = 'completed'), 0) AS completed,
                            IFNULL(SUM(status = 'failed'), 0) AS failed,
                            COUNT(*) AS total,
                            
                            /* 100% Gross volumes (Combined platform financial scale) */
                            IFNULL(SUM(CASE WHEN status = 'completed' THEN fee ELSE 0 END), 0) AS earnings,
                            IFNULL(SUM(CASE WHEN status = 'failed' THEN fee ELSE 0 END), 0) AS failed_earnings,
                            
                            /* 20% Net Company platform revenue cuts */
                            IFNULL(SUM(CASE WHEN status = 'completed' THEN fee ELSE 0 END), 0) * 0.20 AS admin_earnings,
                            IFNULL(SUM(CASE WHEN status = 'failed' THEN fee ELSE 0 END), 0) * 0.20 AS admin_failed_earnings
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

        // view_user_info.php
        public function getUserDetails($user_id) {
            // 1. Fetch primary profile data for the targeted user
            $stmt = $this->db->prepare("
                SELECT * FROM users 
                WHERE id = ? 
                LIMIT 1
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            // Guard clause: Return null immediately if the user doesn't exist
            if (!$user) return null;

            // Initialize the data payload array with user info
            $data = ['user' => $user];

            // 2. Fetch specialized operational metrics if the user is a Tasker
            if ($user['role'] === 'tasker') {
                
                // Get active errand progress numbers (Pending Payment and In Process states)
                $stmt = $this->db->prepare("
                    SELECT 
                        IFNULL(SUM(status = 'pending_payment'), 0) AS pending_payment, 
                        IFNULL(SUM(status = 'in_process'), 0) AS in_process, 
                        COUNT(*) AS total_active 
                    FROM errands 
                    WHERE tasker_id = ? 
                    AND status IN ('pending_payment', 'in_process')
                ");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $data['active'] = $stmt->get_result()->fetch_assoc();

                // Get historical performance metrics, calculate the 80% Tasker split, and calculate your 20% Admin commission
                $stmt = $this->db->prepare("
                    SELECT 
                        IFNULL(SUM(status = 'completed'), 0) AS completed, 
                        IFNULL(SUM(status = 'failed'), 0) AS failed, 
                        COUNT(*) AS total_finished, 
                        
                        /* TASKER SPLIT (80%) */
                        IFNULL(SUM(CASE WHEN status = 'completed' THEN fee ELSE 0 END), 0) * 0.80 AS earnings, 
                        IFNULL(SUM(CASE WHEN status = 'failed' THEN fee ELSE 0 END), 0) * 0.80 AS failed_earnings,
                        
                        /* ADMIN COMMISSION (20%) */
                        IFNULL(SUM(CASE WHEN status = 'completed' THEN fee ELSE 0 END), 0) * 0.20 AS admin_earnings, 
                        IFNULL(SUM(CASE WHEN status = 'failed' THEN fee ELSE 0 END), 0) * 0.20 AS admin_failed_earnings
                    FROM history 
                    WHERE tasker_id = ? 
                    AND status IN ('completed', 'failed')
                ");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $data['history'] = $stmt->get_result()->fetch_assoc();
            }
            
            // Return the aggregated multi-dimensional metrics payload array
            return $data;
        }

        // Reset password
        public function resetUserPassword($user_id, $hashed_password) {
            $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
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

        public function getActiveErrandDetails($errand_id) {
            $stmt = $this->db->prepare("SELECT * FROM errands WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $errand_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();
            return $data;
        }

        public function getHistoryRowById($history_id) {
            $stmt = $this->db->prepare("SELECT * FROM history WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $history_id);
            $stmt->execute();
            $data = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $data;
        }

        public function getFinishedErrandDetails($errand_id) {
            $stmt = $this->db->prepare("SELECT * FROM history WHERE errand_id = ? LIMIT 1");
            $stmt->bind_param("i", $errand_id);
            $stmt->execute();
            return $stmt->get_result();
        }
        
        // delete active errand query
        public function deleteActiveErrandRecord($errand_id) {
            // Delete active attachment database rows
            $stmt = $this->db->prepare("DELETE FROM errand_attachments WHERE errand_id = ?");
            $stmt->bind_param("i", $errand_id);
            $stmt->execute();
            $stmt->close();

            // Delete active errand main row record
            $stmt = $this->db->prepare("DELETE FROM errands WHERE id = ?");
            $stmt->bind_param("i", $errand_id);
            $success = $stmt->execute();
            $stmt->close();

            return $success;
        }

        // Delete finished errand query
        public function deleteFinishedErrandRecord($history_id) {
            // Delete attachment history row records from database
            $stmt = $this->db->prepare("DELETE FROM history_attachments WHERE history_errand_id = ?");
            $stmt->bind_param("i", $history_id);
            $stmt->execute();
            $stmt->close();

            // Delete final history row record
            $stmt = $this->db->prepare("DELETE FROM history WHERE id = ?");
            $stmt->bind_param("i", $history_id);
            $success = $stmt->execute();
            $stmt->close();

            return $success;
        }

        // Add user
        public function addUserByAdmin($data) {
            $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
            $default_pic = 'uploads/profile_pictures/default.jpg';

            $insert_stmt = $this->db->prepare("
                INSERT INTO users (email, first_name, middle_name, last_name, password, profile_picture, contact_number, role) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $insert_stmt->bind_param(
                "ssssssss",
                $data['email'], $data['first_name'], $data['middle_name'], 
                $data['last_name'], $hashed_password, $default_pic, 
                $data['contact_number'], $data['role']
            );

            $success = $insert_stmt->execute();
            $new_id = $insert_stmt->insert_id;
            $insert_stmt->close();

            return $success ? $new_id : false;
        }

        // Delete User via Admin Context
        public function deleteUserByAdmin($admin_id, $user_id, $role, $full_name) {
            if ($full_name === "Unknown User") {
                return ['success' => false];
            }

            $admin_full_name = $this->getFullName($admin_id);
            
            // FIXED: Using arrays to safely catch files across MULTIPLE separate errands
            $payload = [
                'success'          => false,
                'profile_picture'  => '',
                'attachment_paths' => [], 
                'receipt_paths'    => []  
            ];

            // 1. Fetch profile picture path
            $stmt = $this->db->prepare("SELECT profile_picture FROM users WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user_data = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($user_data) {
                $payload['profile_picture'] = $user_data['profile_picture'];
            }

            // 2. Handle Tasker-specific cascades
            if ($role === 'tasker') {
                $stmt = $this->db->prepare("SELECT id, title, client_id FROM errands WHERE tasker_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $errands_result = $stmt->get_result();
                $stmt->close();

                if ($errands_result->num_rows > 0) {
                    while ($row = $errands_result->fetch_assoc()) {
                        $errand_id = (int)$row['id'];
                        $errand_title = $row['title'];
                        $target_client_id = (int)$row['client_id'];

                        $msg = "Tasker: \"{$full_name}\" (ID: {$user_id}), who accepted your Errand: \"{$errand_title}\" (ID: {$errand_id}), had their account deleted by Admin: \"{$admin_full_name}\" (ID: {$admin_id}). The errand status has been returned to \"Pending\".";
                        $link = "../client/view_errand_info.php?errand_id={$errand_id}";
                        $this->addNotification($target_client_id, "errand", $msg, $link);

                        $admin_msg = "Account Deleted | Tasker: \"{$full_name}\" (ID: {$user_id}) | Affected Errand: \"{$errand_title}\" (ID: {$errand_id}) | Status Returned To: \"Pending\" | Deleted by Admin: \"{$admin_full_name}\" (ID: {$admin_id}).";
                        $admin_link = "../admin/view_errand_info.php?errand_id={$errand_id}";
                        $this->notifyOtherAdmins($admin_id, "errand", $admin_msg, $admin_link);
                    }
                } else {
                    $admin_msg = "Account Deleted | Tasker: \"{$full_name}\" (ID: {$user_id}) | No errands were affected | Deleted by Admin: \"{$admin_full_name}\" (ID: {$admin_id}).";
                    $this->notifyOtherAdmins($admin_id, "system", $admin_msg, "");
                }

            // 3. Handle Client-specific cascades
            } elseif ($role === 'client') {
                $stmt = $this->db->prepare("SELECT id, title, tasker_id, payment_receipt_path FROM errands WHERE client_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $errands_result = $stmt->get_result();
                $stmt->close();

                if ($errands_result->num_rows > 0) {
                    while ($row = $errands_result->fetch_assoc()) {
                        $errand_id = (int)$row['id'];
                        $errand_title = $row['title'];
                        $target_tasker_id = $row['tasker_id'] !== null ? (int)$row['tasker_id'] : null;

                        // FIXED: Append receipt path to list array so multiple errands don't overwrite each other
                        if (!empty($row['payment_receipt_path'])) {
                            $payload['receipt_paths'][] = $row['payment_receipt_path'];
                        }

                        if ($target_tasker_id !== null) {
                            $msg = "Client: \"{$full_name}\" (ID: {$user_id}), whose Errand: \"{$errand_title}\" (ID: {$errand_id}) you accepted, had their account deleted by Admin: \"{$admin_full_name}\" (ID: {$admin_id}). The errand has been removed.";
                            $this->addNotification($target_tasker_id, "errand", $msg, "");
                        }

                        $admin_msg = "Account Deleted | Client: \"{$full_name}\" (ID: {$user_id}) | Errand: \"{$errand_title}\" (ID: {$errand_id}) has been removed | Deleted by Admin: \"{$admin_full_name}\" (ID: {$admin_id}).";
                        $this->notifyOtherAdmins($admin_id, "errand", $admin_msg, "");

                        // Captures single attachment file entry for this precise loop iteration
                        $stmt_a = $this->db->prepare("SELECT file_path FROM errand_attachments WHERE errand_id = ? LIMIT 1");
                        $stmt_a->bind_param("i", $errand_id);
                        $stmt_a->execute();
                        $file_data = $stmt_a->get_result()->fetch_assoc();
                        $stmt_a->close();

                        // FIXED: Append attachment path to list array
                        if ($file_data) {
                            $payload['attachment_paths'][] = $file_data['file_path'];
                        }

                        // Pure database table row purge
                        $stmt_d = $this->db->prepare("DELETE FROM errand_attachments WHERE errand_id = ?");
                        $stmt_d->bind_param("i", $errand_id);
                        $stmt_d->execute();
                        $stmt_d->close();
                    }
                } else {
                    $admin_msg = "Account Deleted | Client: \"{$full_name}\" (ID: {$user_id}) | No errands were affected | Deleted by Admin: \"{$admin_full_name}\" (ID: {$admin_id}).";
                    $this->notifyOtherAdmins($admin_id, "system", $admin_msg, "");
                }
            } elseif ($role === 'admin') {
                $admin_msg = "Account Deleted | Admin: \"{$full_name}\" (ID: {$user_id}) | Deleted by Admin: \"{$admin_full_name}\" (ID: {$admin_id}).";
                $this->notifyOtherAdmins($admin_id, "system", $admin_msg, "");
            }

            // 4. Structural Cascades
            if ($role === 'tasker') {
                $stmt = $this->db->prepare("UPDATE errands SET status='pending', tasker_id=NULL, accepted_at=NULL, in_process_at=NULL, updated_at=NOW() WHERE tasker_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->close();
            } elseif ($role === 'client') {
                $stmt = $this->db->prepare("DELETE FROM errands WHERE client_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->close();
            }

            // 5. User cleanup execution context
            $this->deleteAllNotifications($user_id);
            $finalStmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
            $finalStmt->bind_param("i", $user_id);
            $result = $finalStmt->execute();
            $finalStmt->close();

            $payload['success'] = $result;
            return $payload;
        }

        public function approveCancellation($errand_id) {
            // 1. Fetch the errand first to see who requested the cancellation
            $check = $this->db->prepare("
                SELECT cancel_requested_by_tasker, cancel_requested_by_client 
                FROM errands 
                WHERE id = ? AND status IN ('pending_payment', 'in_process')
                LIMIT 1
            ");
            $check->bind_param("i", $errand_id);
            $check->execute();
            $errand = $check->get_result()->fetch_assoc();

            if (!$errand) return false;
            $stmt = $this->db->prepare("UPDATE errands 
                SET status = 'pending',
                    tasker_id = NULL,
                    accepted_at = NULL,
                    in_process_at = NULL,
                    cancel_requested_by_tasker = 0,
                    cancel_requested_by_client = 0,
                    updated_at = NOW()
                WHERE id = ? AND status IN ('pending_payment', 'in_process')
                ");
            $stmt->bind_param("i", $errand_id);
            
            if ($stmt->execute()) {
                // Return the original flags so your controller knows who to notify!
                return $errand; 
            }
            return false;
        }

        public function denyCancellation($errand_id) {
            $stmt = $this->db->prepare("
                UPDATE errands 
                SET cancel_requested_by_tasker = 0,
                    cancel_requested_by_client = 0,
                    updated_at = NOW()
                WHERE id = ? AND status IN ('pending_payment', 'in_process')
            ");
            $stmt->bind_param("i", $errand_id);
            return $stmt->execute();
        }
        
        // Approve Receipt & Transition to In Process
        public function approvePaymentReceipt($errand_id) {
            // Fetch info to verify state and gather text context for notifications
            $stmt = $this->db->prepare("
                SELECT title, client_id, tasker_id 
                FROM errands 
                WHERE id = ? AND status = 'pending_payment' AND payment_receipt_path IS NOT NULL 
                LIMIT 1
            ");
            $stmt->bind_param("i", $errand_id);
            $stmt->execute();
            $errand = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$errand) return false;

            // Update state to in_process and log timestamp
            $update = $this->db->prepare("
                UPDATE errands 
                SET status = 'in_process', 
                    in_process_at = NOW(), 
                    updated_at = NOW() 
                WHERE id = ?
            ");
            $update->bind_param("i", $errand_id);
            $execution_result = $update->execute();
            $update->close();
            
            if ($execution_result) {
                return [
                    'success'   => true,
                    'title'     => $errand['title'],
                    'client_id' => $errand['client_id'],
                    'tasker_id' => $errand['tasker_id']
                ];
            }
            return false;
        }

        // Reject Receipt & Reset Path Field / Maintain pending_payment status
        public function rejectPaymentReceipt($errand_id) {
            $stmt = $this->db->prepare("
                SELECT title, client_id, payment_receipt_path 
                FROM errands 
                WHERE id = ? AND status = 'pending_payment' 
                LIMIT 1
            ");
            $stmt->bind_param("i", $errand_id);
            $stmt->execute();
            $errand = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$errand) return false;

            $update = $this->db->prepare("
                UPDATE errands 
                SET payment_receipt_path = NULL, 
                    updated_at = NOW() 
                WHERE id = ?
            ");
            $update->bind_param("i", $errand_id);
            $execution_result = $update->execute();
            $update->close();
            
            if ($execution_result) {
                // Return tracking structure to controller; no physical file unlinking performed here
                return [
                    'success'              => true,
                    'title'                => $errand['title'],
                    'client_id'            => $errand['client_id'],
                    'payment_receipt_path' => $errand['payment_receipt_path']
                ];
            }
            return false;
        }

            public function forceCancelErrandRecord($errand_id) {
            $query = "UPDATE errands 
                    SET status = 'pending', 
                        tasker_id = NULL, 
                        cancel_requested_by_tasker = 0, 
                        cancel_requested_by_client = 0 
                    WHERE id = ?";
                    
            $stmt = $this->db->prepare($query);
            if (!$stmt) {
                return false;
            }
            
            $stmt->bind_param("i", $errand_id);
            $result = $stmt->execute();
            $stmt->close();
            
            return $result;
        }
    }