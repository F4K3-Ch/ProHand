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

            if ($result->num_rows === 0) {
                return "Email not found."; // Unique string for email error
            }

            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                return $user['role']; 
            } else {
                return "Incorrect password."; // Unique string for password error
            }
        }
        
        // REGISTER METHOD
        public function register($data) {
            // Check for duplicate email
            $check_stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $check_stmt->bind_param("s", $data['email']);
            $check_stmt->execute();
            $check_stmt->store_result();
            
            if ($check_stmt->num_rows > 0) {
                return "Email is already registered!";
            }
            $check_stmt->close();

            // Insert new user
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

            return $insert_stmt->execute() ? true : "Registration failed.";
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

        // profile.php, admin/view_user_info.php & sidebar.php
        public function formatUserDisplayData($userData, $back_origin = 'dashboard') {
            // 1. Name Formatting
            $full_name = $this->getFullName($userData['id'] ?? 0);
            
            // 2. Picture Path Logic
            $db_pic = $userData['profile_picture'] ?? '';
            $file = !empty($db_pic) ? basename($db_pic) : 'default.jpg';
            $path = "../../public/uploads/profile_pictures/" . $file;

            // 3. Back URL Logic - Define $role_raw early to avoid Undefined Variable errors
            $role_raw = $userData['role'] ?? 'user';
            
            if ($back_origin === 'notification') {
                $back_url = "../shared/notifications.php";
            } elseif ($back_origin === 'admin_manage') {
                $back_url = "manage_users.php";
            } else {
                // Fallback to dashboard
                $back_url = "../$role_raw/" . $role_raw . "_dashboard.php";
            }

            return [
                'user'                 => $userData,
                'full_name'            => $full_name,
                'role'                 => $role_raw,
                'role_label'           => ucfirst($role_raw),
                'profile_picture'      => $path,
                'user_profile_picture' => $path, 
                'back_url'             => $back_url,
                'notif_count'          => 0
            ];
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

        // view_errand_info.php
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

        // Use this for the Download Script (Single File)
        public function getAttachmentDetails($attachment_id, $is_history = false) {
            $table = $is_history ? "history_attachments" : "errand_attachments";
            $stmt = $this->db->prepare("SELECT file_path, original_name FROM $table WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $attachment_id);
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
        
        public function performAccountDeletion($user_id, $role) {
            // 1. Use your helper function for the name
            $full_name = $this->getFullName($user_id);
            if ($full_name === "Unknown User") {
                return false; // User doesn't exist
            }

            // 2. Fetch only the profile picture for file cleanup
            $stmt = $this->db->prepare("SELECT profile_picture FROM users WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user_data = $stmt->get_result()->fetch_assoc();
            $profile_dir = "../../public/uploads/profile_pictures/"; 

            // 3. Notify Affected Users (Taskers or Clients)
            if ($role === 'tasker') {
                $stmt = $this->db->prepare("SELECT id, title, client_id FROM errands WHERE tasker_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $clients_result = $stmt->get_result();

                while ($row = $clients_result->fetch_assoc()) {
                    $errand_id = (int)$row['id'];
                    $errand_title = $row['title'];
                    $target_client_id = (int)$row['client_id'];

                    $msg = "Tasker: \"{$full_name}\" (ID: {$user_id}), who accepted your Errand: \"{$errand_title}\" (ID: {$errand_id}) has deleted their account. The errand status has been returned to \"Pending\".";
                    $link = "../client/view_errand_info.php?errand_id={$errand_id}";
                    $this->addNotification($target_client_id, "errand", $msg, $link);
                }
            } elseif ($role === 'client') {
                $stmt = $this->db->prepare("SELECT id, title, tasker_id FROM errands WHERE client_id = ? AND tasker_id IS NOT NULL");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $taskers_result = $stmt->get_result();

                while ($row = $taskers_result->fetch_assoc()) {
                    $errand_id = (int)$row['id'];
                    $errand_title = $row['title'];
                    $target_tasker_id = (int)$row['tasker_id'];

                    $msg = "Client: \"{$full_name}\" (ID: {$user_id}), whose Errand: \"{$errand_title}\" (ID: {$errand_id}) you accepted has deleted their account. The errand has been removed.";
                    $this->addNotification($target_tasker_id, "errand", $msg, "");
                }
            }

            // 4. Notify Other Admins
            $stmt = $this->db->prepare("SELECT id FROM users WHERE role = 'admin' AND id != ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $admins_result = $stmt->get_result();

            while ($admin = $admins_result->fetch_assoc()) {
                $admin_id = (int)$admin['id'];

                if ($role === 'tasker') {
                    $stmt_e = $this->db->prepare("SELECT id, title FROM errands WHERE tasker_id = ?");
                    $stmt_e->bind_param("i", $user_id);
                    $stmt_e->execute();
                    $errands_result = $stmt_e->get_result();

                    if ($errands_result->num_rows > 0) {
                        while ($e = $errands_result->fetch_assoc()) {
                            $errand_id = (int)$e['id'];
                            $errand_title = $e['title'];

                            $msg = "Account Deleted | Tasker: \"{$full_name}\" (ID: {$user_id}) | Affected Errand: \"{$errand_title}\" (ID: {$errand_id}) | Status Returned To: \"Pending \" | This account was deleted by the Tasker themselves.";
                            $link = "../admin/view_errand_info.php?errand_id={$errand_id}";
                            $this->addNotification($admin_id, "errand", $msg, $link);
                        }
                    } else {
                        $msg = "Account Deleted | Tasker: \"{$full_name}\" (ID: {$user_id}) | No errands were affected | This account was deleted by the Tasker themselves.";
                        $this->addNotification($admin_id, "system", $msg, "");
                    }
                } elseif ($role === 'client') {
                    $stmt_e = $this->db->prepare("SELECT id, title FROM errands WHERE client_id = ?");
                    $stmt_e->bind_param("i", $user_id);
                    $stmt_e->execute();
                    $errands_result = $stmt_e->get_result();

                    if ($errands_result->num_rows > 0) {
                        while ($e = $errands_result->fetch_assoc()) {
                            $errand_id = (int)$e['id'];
                            $errand_title = $e['title'];

                            $msg = "Account Deleted | Client: \"{$full_name}\" (ID: {$user_id}) | Errand: \"{$errand_title}\" (ID: {$errand_id}) has been removed | This account was deleted by the Client themselves.";
                            $this->addNotification($admin_id, "errand", $msg, "");
                        }
                    } else {
                        $msg = "Account Deleted | Client: \"{$full_name}\" (ID: {$user_id}) | No errands were affected | This account was deleted by the Client themselves.";
                        $this->addNotification($admin_id, "system", $msg, "");
                    }
                } elseif ($role === 'admin') {
                    $msg = "Account Deleted | Admin: \"{$full_name}\" (ID: {$user_id}) | This account was deleted by the Admin themselves.";
                    $this->addNotification($admin_id, "system", $msg, "");
                }
            }

            // 5. Cleanup Physical Files (Profile Picture)
            if (!empty($user_data['profile_picture']) && basename($user_data['profile_picture']) !== "default.jpg") {
                $file_to_delete = $profile_dir . basename($user_data['profile_picture']);
                if (file_exists($file_to_delete)) {
                    unlink($file_to_delete);
                }
            }

            // 6. Handle Errand Cleanup in Database
            if ($role === 'tasker') {
                $stmt = $this->db->prepare("UPDATE errands SET status='pending', tasker_id=NULL, accepted_at=NULL, in_process_at=NULL, updated_at=NOW() WHERE tasker_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
            } elseif ($role === 'client') {
                $stmt = $this->db->prepare("DELETE FROM errands WHERE client_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
            }

            // 7. Final Deletion
            $this->deleteAllNotifications($user_id);
            $finalStmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
            $finalStmt->bind_param("i", $user_id);
            return $finalStmt->execute();
        }
    }