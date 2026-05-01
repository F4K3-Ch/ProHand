<?php
    session_start();
    require_once "../shared/db.php";

    // Only admin can permanently delete errands
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header("Location: ../auth/login.php");
        exit;
    }

    // Status Labels
    $status_labels = array(
        'pending' => 'Pending',
        'accepted' => 'Accepted',
        'in_process' => 'In Process'
    );

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
        $errand_id = (int)$_POST['id'];

        // Fetch errand details before deletion
        $stmt = $conn->prepare("SELECT * FROM errands WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $errand_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows !== 1) {
            $_SESSION['error'] = "Errand not found.";
            header("Location: admin_dashboard.php");
            exit;
        }

        $row = $result->fetch_assoc();
        $tasker_id = (int)$row['tasker_id'];
        $client_id = (int)$row['client_id'];
        $status = $row['status'];
        $errand_title = $row['title'];

        // Fetch tasker info 
        $tasker_stmt = $conn->prepare("SELECT first_name, middle_name, last_name FROM users WHERE id = ? LIMIT 1");
        $tasker_stmt->bind_param("i", $tasker_id);
        $tasker_stmt->execute();
        $tasker = $tasker_stmt->get_result()->fetch_assoc();
        $tasker_full_name = trim($tasker['first_name'] . ' ' . $tasker['middle_name'] . ' ' . $tasker['last_name']);
        $tasker_stmt->close();

        // Fetch client info
        $client_stmt = $conn->prepare("SELECT first_name, middle_name, last_name FROM users WHERE id = ? LIMIT 1");
        $client_stmt->bind_param("i", $client_id);
        $client_stmt->execute();
        $client = $client_stmt->get_result()->fetch_assoc();
        $client_full_name = trim($client['first_name'] . ' ' . $client['middle_name'] . ' ' . $client['last_name']);
        $client_stmt->close();

        // Delete attachments
        $attachment_dir = "../uploads/attachments/";
        $stmt = $conn->prepare("SELECT file_path FROM errand_attachments WHERE errand_id = ?");
        $stmt->bind_param("i", $errand_id);
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

        // Delete attachment records
        $stmt = $conn->prepare("DELETE FROM errand_attachments WHERE errand_id = ?");
        $stmt->bind_param("i", $errand_id);
        $stmt->execute();
        $stmt->close();

        // Delete errand itself
        $stmt = $conn->prepare("DELETE FROM errands WHERE id = ?");
        $stmt->bind_param("i", $errand_id);
        $delete_result = $stmt->execute();
        $stmt->close();

        if ($delete_result === TRUE) {
                // Notify admin, client and tasker if status is Pending/Accepted/In_Process
                $notif_type = "errand";
                
                if (in_array($status, ['accepted', 'in_process'])) {
                    // Message for CLIENT
                    $client_notif_message = "Your Errand: \"{$errand_title}\" (ID: {$errand_id}), which was assigned to Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}) and was in Status: \"{$status_labels[$status]}\", has been deleted by Admin: \"{$admin_full_name}\" (ID: {$admin_id}).";

                    // Message for TASKER
                    $tasker_notif_message = "The Errand: \"{$errand_title}\" (ID: {$errand_id}), assigned to you and created by Client: \"{$client_full_name}\" (ID: {$client_id}) and in Status: \"{$status_labels[$status]}\", has been deleted by Admin: \"{$admin_full_name}\" (ID: {$admin_id}).";

                } elseif ($status === 'pending') {
                    // Message for CLIENT with no tasker assigned
                    $client_notif_message = "Your Errand: \"{$errand_title}\" (ID: {$errand_id}), which was in \"Pending\" status, has been deleted by Admin: \"{$admin_full_name}\" (ID: {$admin_id}).";

                }

                $notif_link = "";

                if ($client_id > 0) {
                    $notif_stmt = $conn->prepare("
                        INSERT INTO notifications (user_id, type, message, link)
                        VALUES (?, ?, ?, ?)
                    ");
                    $notif_stmt->bind_param("isss", $client_id, $notif_type, $client_notif_message, $notif_link);
                    $notif_stmt->execute();
                    $notif_stmt->close();
                }

                if ($tasker_id > 0) {
                    $notif_stmt = $conn->prepare("
                        INSERT INTO notifications (user_id, type, message, link)
                        VALUES (?, ?, ?, ?)
                    ");
                    $notif_stmt->bind_param("isss", $tasker_id, $notif_type, $tasker_notif_message, $notif_link);
                    $notif_stmt->execute();
                    $notif_stmt->close();
                }

            // Notify all other admins
            $stmt = $conn->prepare("SELECT id FROM users WHERE role = 'admin' AND id != ?");
            $stmt->bind_param("i", $admin_id);
            $stmt->execute();
            $other_admins_result = $stmt->get_result();
            $stmt->close();

            while ($other_admin = $other_admins_result->fetch_assoc()) {
                $other_admin_id = (int)$other_admin['id'];

                if (in_array($status, ['accepted', 'in_process'])) {
                    $notif_type = "errand";
                    $notif_message = "Errand Deleted | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Status: \"{$status_labels[$status]}\" | Created by Client: \"{$client_full_name}\" (ID: {$client_id}) | Assigned to Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}) | Deleted by Admin: \"{$admin_full_name}\" (ID: {$admin_id}).";
                    $notif_link = "";

                    $notif_stmt = $conn->prepare("
                        INSERT INTO notifications (user_id, type, message, link)
                        VALUES (?, ?, ?, ?)
                    ");
                    $notif_stmt->bind_param("isss", $other_admin_id, $notif_type, $notif_message, $notif_link);
                    $notif_stmt->execute();
                    $notif_stmt->close();
                } elseif ($status === 'pending') {
                    $notif_type = "errand";
                    $notif_message = "Errand Deleted | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Status: \"{$status_labels[$status]}\" | Created by Client: \"{$client_full_name}\" (ID: {$client_id}) | Deleted by Admin: \"{$admin_full_name}\" (ID: {$admin_id}).";
                    $notif_link = "";

                    $notif_stmt = $conn->prepare("
                        INSERT INTO notifications (user_id, type, message, link)
                        VALUES (?, ?, ?, ?)
                    ");
                    $notif_stmt->bind_param("isss", $other_admin_id, $notif_type, $notif_message, $notif_link);
                    $notif_stmt->execute();
                    $notif_stmt->close();
                }
            }

            $_SESSION['success'] = "Errand: \"{$errand_title}\" (ID: {$errand_id}) was permanently deleted.";
            header("Location: admin_dashboard.php");
            exit;
        } else {
            $_SESSION['error'] = "Error deleting Errand: \"{$errand_title}\" (ID: {$errand_id}). Please try again.";
            header("Location: admin_dashboard.php");
            exit;
        }

    } else {
        $_SESSION['error'] = "Invalid request.";
        header("Location: admin_dashboard.php");
        exit;   
    }
?>