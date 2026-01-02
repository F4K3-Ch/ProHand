<?php
    session_start();
    require_once "db.php";

    // Ensure the user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        header("Location: ../auth/login.php");
        exit;
    }

    // Ensure POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: profile.php");
        exit;
    }

    $user_id = (int)$_SESSION['user_id'];
    $role = $_SESSION['role'];
    $profile_dir = "../uploads/profile_pictures/";

    // Block client account deletion if they still have active errands
    if ($role === 'client') {
        $stmt = $conn->prepare("
            SELECT id 
            FROM errands 
            WHERE client_id = ? 
            AND status = 'in_process'
            LIMIT 1
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $active_errands = $stmt->get_result();
        $stmt->close();

        if ($active_errands->num_rows > 0) {
            $_SESSION['error'] = "You can't delete your account because you have an Errand In process. Please wait for it to finish or contact your tasker to cancel it.";
            header("Location: profile.php");
            exit;
        }
    }

    // Fetch user info
    $stmt = $conn->prepare("SELECT first_name, middle_name, last_name, profile_picture FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_result = $stmt->get_result();
    $stmt->close();

    if ($user_result->num_rows !== 1) {
        session_destroy();
        header("Location: ../auth/login.php?error=Account not found.");
        exit;
    } else {
        $user = $user_result->fetch_assoc();
        $full_name = trim("{$user['first_name']} {$user['middle_name']} {$user['last_name']}");
    }
    
    // Notify affected users
    if ($role === 'tasker') {

        // Fetch tasker info with the errand they are involve
        $stmt = $conn->prepare("SELECT id, title, client_id FROM errands WHERE tasker_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $clients_result = $stmt->get_result();
        $stmt->close();

        // Notification for client
        while ($row = $clients_result->fetch_assoc()) {
            $client_id = (int)$row['client_id'];
            $errand_id = (int)$row['id'];
            $errand_title = $row['title'];

            // Notification content
            $notif_type = "errand";
            $notif_message = "Tasker: \"{$full_name}\" (ID: {$user_id}), who accepted your Errand: \"{$errand_title}\" (ID: {$errand_id}) has deleted their account. The errand status has been returned to \"Pending\".";
            $notif_link = "../client/view_errand_info.php?errand_id={$errand_id}"; // link to errand details

            // Insert into notifications table
            $notif_stmt = $conn->prepare("
                INSERT INTO notifications (user_id, errand_id, type, message, link)
                VALUES (?, ?, ?, ?, ?)
            ");
            $notif_stmt->bind_param("iisss", $client_id, $errand_id, $notif_type, $notif_message, $notif_link);
            $notif_stmt->execute();
            $notif_stmt->close();
        }
    } elseif ($role === 'client') {

        // Fetch client info with the errand they are involve
        $stmt = $conn->prepare("SELECT id, title, tasker_id FROM errands WHERE client_id = ? AND tasker_id IS NOT NULL");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $taskers_result = $stmt->get_result();
        $stmt->close();

        // Notification for tasker
        while ($row = $taskers_result->fetch_assoc()) {
            $tasker_id = (int)$row['tasker_id'];
            $errand_id = (int)$row['id'];
            $errand_title = $row['title'];

            // Notification content
            $notif_type = "errand";
            $notif_message = "Client: \"{$full_name}\" (ID: {$user_id}), whose Errand: \"{$errand_title}\" (ID: {$errand_id}) you accepted has deleted their account. The errand has been removed.";
            $notif_link = "";

            // Insert into notifications table
            $notif_stmt = $conn->prepare("
                INSERT INTO notifications (user_id, errand_id, type, message, link)
                VALUES (?, ?, ?, ?, ?)
            ");
            $notif_stmt->bind_param("iisss", $tasker_id, $errand_id, $notif_type, $notif_message, $notif_link);
            $notif_stmt->execute();
            $notif_stmt->close();
        }
    }

    // Fetch other admin info
    $stmt = $conn->prepare("SELECT id FROM users WHERE role = 'admin' AND id != ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $other_admins_result = $stmt->get_result();
    $stmt->close();

    // Notification for admin
    while ($admin = $other_admins_result->fetch_assoc()) {
        $admin_id = (int)$admin['id'];

        if ($role === 'tasker') {
            // Check if tasker has errands
            $stmt = $conn->prepare("SELECT id, title FROM errands WHERE tasker_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $errand_result = $stmt->get_result();
            $stmt->close();

            if ($errand_result->num_rows > 0) {
                while ($row = $errand_result->fetch_assoc()) {
                    $errand_id = (int)$row['id'];
                    $errand_title = $row['title'];

                    // Notification content
                    $notif_type = "errand";
                    $notif_message = "Account Deleted | Tasker: \"{$full_name}\" (ID: {$user_id}) | Affected Errand: \"{$errand_title}\" (ID: {$errand_id}) | Status Returned To: \"Pending \" | This account was deleted by the Tasker themselves.";
                    $notif_link = "../admin/view_errand_info.php?errand_id={$errand_id}";

                    // Insert into notifications table
                    $notif_stmt = $conn->prepare("
                        INSERT INTO notifications (user_id, errand_id, type, message, link)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $notif_stmt->bind_param("iisss", $admin_id, $errand_id, $notif_type, $notif_message, $notif_link);
                    $notif_stmt->execute();
                    $notif_stmt->close();
                }
            } else {
                // Notification content
                $notif_type = "system";
                $notif_message = "Account Deleted | Tasker: \"{$full_name}\" (ID: {$user_id}) | No errands were affected | This account was deleted by the Tasker themselves.";
                $notif_link = "";

                // Insert into notifications table
                $notif_stmt = $conn->prepare("
                    INSERT INTO notifications (user_id, type, message, link)
                    VALUES (?, ?, ?, ?)
                ");
                $notif_stmt->bind_param("isss", $admin_id, $notif_type, $notif_message, $notif_link);
                $notif_stmt->execute();
                $notif_stmt->close();
            }
        } elseif ($role === 'client') {
            // Check if client has errands
            $stmt = $conn->prepare("SELECT id, title FROM errands WHERE client_id = ? AND tasker_id IS NOT NULL");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $errand_result = $stmt->get_result();
            $stmt->close();

            if ($errand_result->num_rows > 0) {
                while ($row = $errand_result->fetch_assoc()) {
                    $errand_id = (int)$row['id'];
                    $errand_title = $row['title'];

                    // Notification content
                    $notif_type = "errand";
                    $notif_message = "Account Deleted | Client: \"{$full_name}\" (ID: {$user_id}) | Errand: \"{$errand_title}\" (ID: {$errand_id}) has been removed | This account was deleted by the Client themselves.";
                    $notif_link = "";

                    // Insert into notifications table
                    $notif_stmt = $conn->prepare("
                        INSERT INTO notifications (user_id, errand_id, type, message, link)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $notif_stmt->bind_param("iisss", $admin_id, $errand_id, $notif_type, $notif_message, $notif_link);
                    $notif_stmt->execute();
                    $notif_stmt->close();
                }
            } else {
                // Notification content
                $notif_type = "system";
                $notif_message = "Account Deleted | Client: \"{$full_name}\" (ID: {$user_id}) | No errands were affected | This account was deleted by the Client themselves.";
                $notif_link = "";

                // Insert into notifications table
                $notif_stmt = $conn->prepare("
                    INSERT INTO notifications (user_id, type, message, link)
                    VALUES (?, ?, ?, ?)
                ");
                $notif_stmt->bind_param("isss", $admin_id, $notif_type, $notif_message, $notif_link);
                $notif_stmt->execute();
                $notif_stmt->close();
            }
        } elseif ($role === 'admin') {
            // Handle when an admin deletes their own account
            $notif_type = "system";
            $notif_message = "Account Deleted | Admin: \"{$full_name}\" (ID: {$user_id}) | This account was deleted by the Admin themselves.";
            $notif_link = "";

            $notif_stmt = $conn->prepare("
                INSERT INTO notifications (user_id, type, message, link) 
                VALUES (?, ?, ?, ?)
            ");
            $notif_stmt->bind_param("isss", $admin_id, $notif_type, $notif_message, $notif_link);
            $notif_stmt->execute();
            $notif_stmt->close();
        }
    }

    // Delete profile picture if not default
    if (!empty($user['profile_picture']) && basename($user['profile_picture']) !== "default.jpg") {
        $file_to_delete = $profile_dir . basename($user['profile_picture']);
        if (file_exists($file_to_delete)) unlink($file_to_delete);
    }

    // Handle errands
    if ($role === 'tasker') {
        $stmt = $conn->prepare("UPDATE errands SET status='pending', tasker_id=NULL, accepted_at=NULL, in_process_at=NULL, updated_at=NOW() WHERE tasker_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    } elseif ($role === 'client') {
        $stmt = $conn->prepare("SELECT id FROM errands WHERE client_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $errands_result = $stmt->get_result();
        $stmt->close();

        $attachment_dir = "../uploads/attachments/";

        while ($row = $errands_result->fetch_assoc()) {
            $errand_id = (int)$row['id'];

            // Delete attachments
            $stmt = $conn->prepare("SELECT file_path FROM errand_attachments WHERE errand_id = ?");
            $stmt->bind_param("i", $errand_id);
            $stmt->execute();
            $attachments_result = $stmt->get_result();
            $stmt->close();

            while ($file = $attachments_result->fetch_assoc()) {
                $file_path = $attachment_dir . basename($file['file_path']);
                if (file_exists($file_path)) unlink($file_path);
            }

            // Delete attachment records
            $stmt = $conn->prepare("DELETE FROM errand_attachments WHERE errand_id = ?");
            $stmt->bind_param("i", $errand_id);
            $stmt->execute();
            $stmt->close();

            // Delete errand
            $stmt = $conn->prepare("DELETE FROM errands WHERE id=?");
            $stmt->bind_param("i", $errand_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Delete all notifications received by user
    $stmt = $conn->prepare("DELETE FROM notifications WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    // Delete user record
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $delete_result = $stmt->execute();
    $stmt->close();

    if ($delete_result) {
        session_destroy();
        header("Location: ../auth/login.php?success=Your account has been permanently deleted.");
        exit;
    } else {
        $_SESSION['error'] = "Error deleting your account. Please try again.";
        header("Location: profile.php");
        exit;
    } 
?>
