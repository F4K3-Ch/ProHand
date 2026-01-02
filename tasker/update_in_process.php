<?php
    session_start();
    require_once "../shared/db.php";

    // Ensure only tasker can access
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tasker') {
        header("Location: ../auth/login.php");
        exit;
    }

    $tasker_id = (int)$_SESSION['user_id'];

    // Ensure POST request with errand_id
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
        $errand_id = (int)$_POST['id'];

        // Fetch errand with client info
        $stmt = $conn->prepare("
            SELECT e.*, 
                c.first_name AS client_first, 
                c.middle_name AS client_middle, 
                c.last_name AS client_last
            FROM errands AS e
            LEFT JOIN users AS c ON e.client_id = c.id
            WHERE e.id = ? AND e.tasker_id = ?
            LIMIT 1
        ");
        $stmt->bind_param("ii", $errand_id, $tasker_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows !== 1) {
            $_SESSION['error'] = "Errand not found or you are not authorized.";
            header("Location: tasker_dashboard.php");
            exit;
        }

        $errand = $result->fetch_assoc();
        $client_id = $errand['client_id'];
        $errand_title = $errand['title'];

        // Update status to in_process
        $update_stmt = $conn->prepare("UPDATE errands SET status = 'in_process', in_process_at = NOW(), updated_at = NOW() WHERE id = ? AND tasker_id = ?");
        $update_stmt->bind_param("ii", $errand_id, $tasker_id);
        $update_stmt->execute();
        $update_stmt->close();

        // ===============================
        //  Send Notifications
        // ===============================

        // Fetch tasker info 
        $tasker_stmt = $conn->prepare("SELECT first_name, middle_name, last_name FROM users WHERE id = ? LIMIT 1");
        $tasker_stmt->bind_param("i", $tasker_id);
        $tasker_stmt->execute();
        $tasker = $tasker_stmt->get_result()->fetch_assoc();
        $tasker_name = trim($tasker['first_name'] . ' ' . $tasker['middle_name'] . ' ' . $tasker['last_name']);
        $tasker_stmt->close();

        // client fullname
        $client_name = trim($errand['client_first'] . ' ' . $errand['client_middle'] . ' ' . $errand['client_last']);

        // Notification for client
        // Notification content
        $notif_type = "errand";
        $notif_message = "Your Errand: \"{$errand_title}\" (ID: {$errand_id}) was updated to \"In Process\" by Tasker: \"{$tasker_name}\" (ID: {$tasker_id}).";
        $notif_link = "../client/view_errand_info.php?errand_id={$errand_id}"; // link to errand details

        // Insert into notifications table
        $notif_stmt = $conn->prepare("
            INSERT INTO notifications (user_id, type, message, link)
            VALUES (?, ?, ?, ?)
        ");
        $notif_stmt->bind_param("isss", $client_id, $notif_type, $notif_message, $notif_link);
        $notif_stmt->execute();
        $notif_stmt->close();

        // Notify all admins
        $admins_result = $conn->query("SELECT id FROM users WHERE role = 'admin'");
        while ($admin = $admins_result->fetch_assoc()) {
            $admin_id = (int)$admin['id'];

            // Notification content
            $notif_type = "errand";
            $notif_message = "Errand Updated to In Process | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Created by Client: \"{$client_name}\" (ID: {$client_id}) | Updated by Tasker: \"{$tasker_name}\" (ID: {$tasker_id}).";
            $notif_link = "../admin/view_errand_info.php?errand_id={$errand_id}"; // link to errand details

            // Insert into notifications table
            $notif_stmt = $conn->prepare("
                INSERT INTO notifications (user_id, type, message, link)
                VALUES (?, ?, ?, ?)
            ");
            $notif_stmt->bind_param("isss", $admin_id, $notif_type, $notif_message, $notif_link);
            $notif_stmt->execute();
            $notif_stmt->close();
        }

        $_SESSION['success'] = "Errand status updated to \"In Process\".";
        header("Location: view_errand_info.php?errand_id={$errand_id}");
        exit;

    } else {
        $_SESSION['error'] = "Invalid request.";
        header("Location: tasker_dashboard.php");
        exit;
    }
?>
