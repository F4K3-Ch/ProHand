<?php 
    session_start();
    require_once "../shared/db.php";

    // Only admin can permanently delete errands
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header("Location: ../auth/login.php");
        exit;
    }

    $admin_id = (int)$_SESSION['user_id'];

    // Fetch admin info
    $stmt = $conn->prepare("SELECT first_name, middle_name, last_name FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $admin_result = $stmt->get_result();
    $stmt->close();

    if ($admin_result->num_rows === 1) {
        $admin = $admin_result->fetch_assoc();
        $admin_full_name = trim($admin['first_name'] . ' ' . $admin['middle_name'] . ' ' . $admin['last_name']);
    } else {
        session_unset();
        session_destroy();
        header("Location: ../auth/login.php");
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
        $history_id = (int)$_POST['id'];

        // Fetch history details before deletion
        $stmt = $conn->prepare("SELECT * FROM history WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $history_id );
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows !== 1) {
            $_SESSION['error'] = "Errand not found.";
            header("Location: finished_errands.php");
            exit;
        }

        $row = $result->fetch_assoc();
        $tasker_id = (int)$row['tasker_id'];
        $tasker_full_name = trim($row['tasker_first_name'] . ' ' . $row['tasker_middle_name'] . ' ' . $row['tasker_last_name']);
        $client_id = (int)$row['client_id'];
        $client_full_name = trim($row['client_first_name'] . ' ' . $row['client_middle_name'] . ' ' . $row['client_last_name']);
        $status = $row['status'];
        $errand_title = $row['title'];
        $original_errand_id = (int)$row['errand_id']; 
        $client_deleted = (int)$row['client_deleted'];
        $tasker_deleted = (int)$row['tasker_deleted'];


        // Delete attachments
        $attachment_dir = "../uploads/history/attachments/";
        $stmt = $conn->prepare("SELECT file_path FROM history_attachments WHERE history_errand_id = ?");
        $stmt->bind_param("i", $history_id );
        $stmt->execute();
        $attachments_result = $stmt->get_result();
        $stmt->close();

        if ($attachments_result->num_rows > 0) {
            while ($file = $attachments_result->fetch_assoc()) {
                $file_path = $attachment_dir . basename($file['file_path']);
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
        }

        $profile_dir = "../uploads/history/profile_pictures/";

        // Delete client profile picture if not default
        if (!empty($row['client_profile_picture']) && basename($row['client_profile_picture']) !== "default.jpg") {
            $file_to_delete = $profile_dir . basename($row['client_profile_picture']);
            if (file_exists($file_to_delete)) unlink($file_to_delete);
        }

        // Delete tasker profile picture if not default
        if (!empty($row['tasker_profile_picture']) && basename($row['tasker_profile_picture']) !== "default.jpg") {
            $file_to_delete = $profile_dir . basename($row['tasker_profile_picture']);
            if (file_exists($file_to_delete)) unlink($file_to_delete);
        }

        // Delete attachment records
        $stmt = $conn->prepare("DELETE FROM history_attachments WHERE history_errand_id = ?");
        $stmt->bind_param("i", $history_id);
        $stmt->execute();
        $stmt->close();

        // Delete errand itself
        $stmt = $conn->prepare("DELETE FROM history WHERE id = ?");
        $stmt->bind_param("i", $history_id);
        $delete_result = $stmt->execute();
        $stmt->close();

        if ($delete_result === TRUE) {
            $notif_type = "errand";
            $notif_link = "";

            // Notify CLIENT only if still visible to client
            if ($client_id > 0 && $client_deleted === 0) {
                $check_client = $conn->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
                $check_client->bind_param("i", $client_id);
                $check_client->execute();
                $client_result = $check_client->get_result();
                $check_client->close();

                if ($client_result->num_rows === 1) {
                    $client_notif_message = "Your Finished Errand: \"{$errand_title}\" (ID: {$original_errand_id}) with Status: \"" . ucfirst($status) . "\", finished by Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}), has been permanently deleted from the system by Admin: \"{$admin_full_name}\" (ID: {$admin_id}).";

                    $stmt = $conn->prepare("
                        INSERT INTO notifications (user_id, type, message, link)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->bind_param("isss", $client_id, $notif_type, $client_notif_message, $notif_link);
                    $stmt->execute();
                    $stmt->close();
                }
            }

            // Notify TASKER only if still visible to tasker
            if ($tasker_id > 0 && $tasker_deleted === 0) {
                $check_tasker = $conn->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
                $check_tasker->bind_param("i", $tasker_id);
                $check_tasker->execute();
                $tasker_result = $check_tasker->get_result();
                $check_tasker->close();

                if ($tasker_result->num_rows === 1) {
                    $tasker_notif_message = "The Finished Errand: \"{$errand_title}\" (ID: {$original_errand_id}) with Status: \"" . ucfirst($status) . "\", which you finished and was created by Client: \"{$client_full_name}\" (ID: {$client_id}), has been permanently deleted from the system by Admin: \"{$admin_full_name}\" (ID: {$admin_id}).";

                    $stmt = $conn->prepare("
                        INSERT INTO notifications (user_id, type, message, link)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->bind_param("isss", $tasker_id, $notif_type, $tasker_notif_message, $notif_link);
                    $stmt->execute();
                    $stmt->close();
                }
            }


            // Notify all other admins
            $stmt = $conn->prepare("SELECT id FROM users WHERE role = 'admin' AND id != ?");
            $stmt->bind_param("i", $admin_id);
            $stmt->execute();
            $other_admins_result = $stmt->get_result();
            $stmt->close();

            while ($other_admin = $other_admins_result->fetch_assoc()) {
                $other_admin_id = (int)$other_admin['id'];

                $notif_type = "errand";
                $notif_message = "Errand Deleted | Errand: \"{$errand_title}\" (ID: {$original_errand_id}) | Status: \"" . ucfirst($status) . "\" | Created by Client: \"{$client_full_name}\" (ID: {$client_id}) | Finished by Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}) | Deleted by Admin: \"{$admin_full_name}\" (ID: {$admin_id}).";
                $notif_link = "";

                $notif_stmt = $conn->prepare("
                    INSERT INTO notifications (user_id, type, message, link)
                    VALUES (?, ?, ?, ?)
                ");
                $notif_stmt->bind_param("isss", $other_admin_id, $notif_type, $notif_message, $notif_link);
                $notif_stmt->execute();
                $notif_stmt->close();
            }

            $_SESSION['success'] = "Errand: \"{$errand_title}\" (ID: {$original_errand_id}) was permanently deleted.";
            header("Location: finished_errands.php");
            exit;
        } else {
            $_SESSION['error'] = "Error deleting Errand: \"{$errand_title}\" (ID: {$original_errand_id}). Please try again.";
            header("Location: finished_errands.php");
            exit;
        }

    } else {
        $_SESSION['error'] = "Invalid request.";
        header("Location: finished_errands.php");
        exit;
    }
?>
