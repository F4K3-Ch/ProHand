<?php
    class User {
        protected $db; // Think of this as a shared shelf

        public function __construct($db) {
            $this->db = $db; // Put the database on the shelf
        }

        // LOGIN METHOD
        public function login($email, $password) {
            $stmt = $this->db->prepare("SELECT id, password, role FROM users WHERE email = ? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            // Guard check just in case, otherwise fetch details safely
            if ($result->num_rows === 0) {
                $stmt->close();
                return false; 
            }

            $user = $result->fetch_assoc();
            $stmt->close();

            // Verify password and return structural credentials payload
            if (password_verify($password, $user['password'])) {
                return [
                    'id'   => $user['id'],
                    'role' => $user['role']
                ];
            }

            return false;
        }
        
        // EMAIL DUPLICATE CHECK HELPER
        public function isEmailRegistered($email) {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            $exists = $stmt->num_rows > 0;
            $stmt->close();
            return $exists;
        }

        // REGISTER METHOD: Returns true on query success, false on failure
        public function register($data) {
            $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
            $role = "client"; 
            $default_pic = 'uploads/profile_pictures/default.jpg';

            $insert_stmt = $this->db->prepare("
                INSERT INTO users (email, first_name, middle_name, last_name, password, profile_picture, contact_number, role) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $insert_stmt->bind_param(
                "ssssssss",
                $data['email'], $data['first_name'], $data['middle_name'], 
                $data['last_name'], $hashed_password, $default_pic, 
                $data['contact_number'], $role
            );

            $success = $insert_stmt->execute();
            $insert_stmt->close();

            return $success;
        }

        // LOGOUT METHOD
        public function logout() {
            session_unset();
            session_destroy();

            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
        }

        // profile.php, admin/view_user_info.php & sidebar.php
        public function getUserInfo($user_id) {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            return $result->fetch_assoc();
        }

        // Returns the full name of a user by ID
        public function getFullName($user_id) {
            $stmt = $this->db->prepare("SELECT first_name, middle_name, last_name FROM users WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            
            if (!$res) return "Unknown User";
            
            return trim($res['first_name'] . ' ' . ($res['middle_name'] ?? '') . ' ' . $res['last_name']);
        }

        // Fetches all users with a specific role
        public function getUsersByRole($role) {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE role = ?");
            $stmt->bind_param("s", $role);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        public function isEmailTakenByOthers($email, $user_id) {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
            $stmt->bind_param("si", $email, $user_id);
            $stmt->execute();
            return $stmt->get_result()->num_rows > 0;
        }

        public function updateProfilePicture($user_id, $path) {
            $stmt = $this->db->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
            $stmt->bind_param("si", $path, $user_id);
            return $stmt->execute();
        }

        public function updateProfileInfo($data) {
            // Note: $data['id'] is now $data['user_id'] to match your request
            $stmt = $this->db->prepare("UPDATE users SET email=?, first_name=?, middle_name=?, last_name=?, contact_number=? WHERE id=?");
            $stmt->bind_param("sssssi", 
                $data['email'], 
                $data['first_name'], 
                $data['middle_name'], 
                $data['last_name'], 
                $data['contact_number'], 
                $data['user_id']
            );
            return $stmt->execute();
        }

        public function updatePassword($user_id, $new_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);
            return $stmt->execute();
        }

        public function getNotifications($user_id, $search = '') {
            $search_normalized = str_replace(',', '', $search);
            $search_query = '%' . $search_normalized . '%';

            if (!empty($search)) {
                $stmt = $this->db->prepare("
                    SELECT * FROM notifications 
                    WHERE user_id = ? 
                    AND (
                        type LIKE ? 
                        OR message LIKE ? 
                        OR DATE_FORMAT(created_at, '%M %e %Y') LIKE ?
                    )
                    ORDER BY created_at DESC
                ");
                $stmt->bind_param("isss", $user_id, $search_query, $search_query, $search_query);
            } else {
                $stmt = $this->db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
                $stmt->bind_param("i", $user_id);
            }
            
            $stmt->execute();
            return $stmt->get_result();
        }

        public function markNotificationsAsRead($user_id) {
            $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
            $stmt->bind_param("i", $user_id);
            return $stmt->execute();
        }

        public function getUnreadNotificationCount($user_id) {
            // This query uses 'is_read' to match your markNotificationsAsRead function
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
            
            if (!$stmt) {
                return 0;
            }

            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            return $result['count'] ?? 0;
        }

        public function deleteNotification($notification_id, $user_id) {
            $stmt = $this->db->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $notification_id, $user_id);
            return $stmt->execute();
        }

        public function deleteAllNotifications($user_id) {
            $stmt = $this->db->prepare("DELETE FROM notifications WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            return $stmt->execute();
        }

        // Standardized method to add notifications
        public function addNotification($user_id, $type, $message, $link) {
            $stmt = $this->db->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $user_id, $type, $message, $link);
            return $stmt->execute();
        }

        public function notifyAllAdmins($type, $message, $link) {
            // This replicates the "SELECT id FROM users WHERE role = 'admin'" logic
            $stmt = $this->db->prepare("SELECT id FROM users WHERE role = 'admin'");
            $stmt->execute();
            $result = $stmt->get_result();

            while ($admin = $result->fetch_assoc()) {
                $this->addNotification((int)$admin['id'], $type, $message, $link);
            }
            $stmt->close();
        }

        public function notifyOtherAdmins($current_admin_id, $type, $message, $link) {
            // Identical logic, but with the "AND id != ?" condition added
            $stmt = $this->db->prepare("SELECT id FROM users WHERE role = 'admin' AND id != ?");
            $stmt->bind_param("i", $current_admin_id);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($admin = $result->fetch_assoc()) {
                $this->addNotification((int)$admin['id'], $type, $message, $link);
            }
            $stmt->close();
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

        // view_errand_info.php (Attachment Viewer Fetcher)
        public function getAttachment($id, $is_finished = false) {
            if ($is_finished) {
                $stmt = $this->db->prepare("SELECT id, file_path, original_name FROM history_attachments WHERE history_errand_id = ? LIMIT 1");
            } else {
                $stmt = $this->db->prepare("SELECT id, file_path, original_name FROM errand_attachments WHERE errand_id = ? LIMIT 1");
            }
            $stmt->bind_param("i", $id);
            $stmt->execute();
            return $stmt->get_result();
        }

        // Use this for the Download Script (Attachment Downloader Details)
        public function getAttachmentDetails($attachment_id, $is_history = false) {
            $table = $is_history ? "history_attachments" : "errand_attachments";
            $stmt = $this->db->prepare("SELECT file_path, original_name FROM $table WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $attachment_id);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }

        // view_errand_info.php (Receipt Viewer Fetcher)
        public function getPaymentReceipt($id, $is_finished = false) {
            if ($is_finished) {
                $stmt = $this->db->prepare("SELECT errand_id AS id, payment_receipt_path FROM history WHERE errand_id = ? LIMIT 1");
            } else {
                $stmt = $this->db->prepare("SELECT id, payment_receipt_path FROM errands WHERE id = ? LIMIT 1");
            }
            $stmt->bind_param("i", $id);
            $stmt->execute();
            return $stmt->get_result();
        }

        // Use this for the Download Script (Receipt Downloader Details)
        public function getPaymentReceiptDetails($errand_id, $is_history = false) {
            $table = $is_history ? "history" : "errands";
            $column = $is_history ? "errand_id" : "id";
            
            $stmt = $this->db->prepare("SELECT payment_receipt_path FROM $table WHERE $column = ? LIMIT 1");
            $stmt->bind_param("i", $errand_id);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }

        public function deleteHistoryRecord($history_id, $user_id, $role) {
            $column = ($role === 'client') ? 'client_id' : 'tasker_id';
            
            // We fetch the errand_id and title specifically for your message indicators
            $stmt = $this->db->prepare("SELECT errand_id, title FROM history WHERE id = ? AND $column = ? LIMIT 1");
            $stmt->bind_param("ii", $history_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows !== 1) return false;
            $row = $result->fetch_assoc();

            $delete_flag = ($role === 'client') ? 'client_deleted' : 'tasker_deleted';
            $update_stmt = $this->db->prepare("UPDATE history SET $delete_flag = 1 WHERE id = ?");
            $update_stmt->bind_param("i", $history_id);
            
            if ($update_stmt->execute()) {
                // Return both pieces of info to the controller
                return [
                    'title' => $row['title'],
                    'errand_id' => $row['errand_id']
                ];
            }
            return false;
        }
        
        // Delete Account (User Self-Deletion)
        public function performAccountDeletion($user_id, $role) {
            // 1. Fetch the user's name
            $full_name = $this->getFullName($user_id);

            if ($full_name === "Unknown User") {
                return ['success' => false];
            }

            // Standardized tracking payload to pass back up to the Controller
            $payload = [
                'success'          => false,
                'profile_picture'  => '',
                'attachment_paths' => [],
                'receipt_paths'    => []
            ];

            // 2. Fetch the target profile picture file path early for later disk cleanup
            $stmt = $this->db->prepare("SELECT profile_picture FROM users WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user_data = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($user_data) {
                $payload['profile_picture'] = $user_data['profile_picture'];
            }

            // 3. Notify Affected Users & Peer Admins based on the Target's Role
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

                        // Notify client that their tasker deleted their own account
                        $msg = "Tasker: \"{$full_name}\" (ID: {$user_id}), who accepted your Errand: \"{$errand_title}\" (ID: {$errand_id}) has deleted their account. The errand status has been returned to \"Pending\".";
                        $link = "../client/view_errand_info.php?errand_id={$errand_id}";
                        $this->addNotification($target_client_id, "errand", $msg, $link);

                        // Notify fellow admins about this broken connection using internal helper
                        $admin_msg = "Account Deleted | Tasker: \"{$full_name}\" (ID: {$user_id}) | Affected Errand: \"{$errand_title}\" (ID: {$errand_id}) | Status Returned To: \"Pending\" | This account was deleted by the Tasker themselves.";
                        $admin_link = "../admin/view_errand_info.php?errand_id={$errand_id}";
                        $this->notifyOtherAdmins($user_id, "errand", $admin_msg, $admin_link);
                    }
                } else {
                    // No errands affected, send a single summary notification to peer admins
                    $admin_msg = "Account Deleted | Tasker: \"{$full_name}\" (ID: {$user_id}) | No errands were affected | This account was deleted by the Tasker themselves.";
                    $this->notifyOtherAdmins($user_id, "system", $admin_msg, "");
                }

            } elseif ($role === 'client') {
                // CHANGED: We now fetch payment_receipt_path as well
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

                        // FIXED: Collect receipt paths into the payload array safely
                        if (!empty($row['payment_receipt_path'])) {
                            $payload['receipt_paths'][] = $row['payment_receipt_path'];
                        }

                        // Notify tasker assigned to this deleted client's errand
                        if ($target_tasker_id !== null) {
                            $msg = "Client: \"{$full_name}\" (ID: {$user_id}), whose Errand: \"{$errand_title}\" (ID: {$errand_id}) you accepted has deleted their account. The errand has been removed.";
                            $this->addNotification($target_tasker_id, "errand", $msg, "");
                        }

                        // Notify fellow admins about dropped client post using internal helper
                        $admin_msg = "Account Deleted | Client: \"{$full_name}\" (ID: {$user_id}) | Errand: \"{$errand_title}\" (ID: {$errand_id}) has been removed | This account was deleted by the Client themselves.";
                        $this->notifyOtherAdmins($user_id, "errand", $admin_msg, "");

                        // FIXED: Collect attachment paths into the payload array instead of unlinking them here
                        $stmt_a = $this->db->prepare("SELECT file_path FROM errand_attachments WHERE errand_id = ?");
                        $stmt_a->bind_param("i", $errand_id);
                        $stmt_a->execute();
                        $attachments_res = $stmt_a->get_result();
                        $stmt_a->close();

                        while ($file = $attachments_res->fetch_assoc()) {
                            if (!empty($file['file_path'])) {
                                $payload['attachment_paths'][] = $file['file_path'];
                            }
                        }

                        // Pure database table row purge
                        $stmt_d = $this->db->prepare("DELETE FROM errand_attachments WHERE errand_id = ?");
                        $stmt_d->bind_param("i", $errand_id);
                        $stmt_d->execute();
                        $stmt_d->close();
                    }
                } else {
                    // No client posts to clear out, send summary alert
                    $admin_msg = "Account Deleted | Client: \"{$full_name}\" (ID: {$user_id}) | No errands were affected | This account was deleted by the Client themselves.";
                    $this->notifyOtherAdmins($user_id, "system", $admin_msg, "");
                }

            } elseif ($role === 'admin') {
                // If an admin account deletes themselves, log it cleanly
                $admin_msg = "Account Deleted | Admin: \"{$full_name}\" (ID: {$user_id}) | This account was deleted by the Admin themselves.";
                $this->notifyOtherAdmins($user_id, "system", $admin_msg, "");
            }

            // 4. Cascade Structural Records Inside the Database Engine
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

            // 5. Final Deletion execution context points
            $this->deleteAllNotifications($user_id);
            $finalStmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
            $finalStmt->bind_param("i", $user_id);
            $result = $finalStmt->execute();
            $finalStmt->close();

            $payload['success'] = $result;
            return $payload;
        }
    }