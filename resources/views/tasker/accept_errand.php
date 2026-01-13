<?php
    session_start();
    require_once "../shared/db.php";

    // Ensure only tasker can access
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tasker') {
        header("Location: ../auth/login.php");
        exit;
    }

    $tasker_id = (int)$_SESSION['user_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
        $errand_id = (int)$_POST['id'];

        //  Ensure the errand exists and is still pending
        $check_stmt = $conn->prepare("
            SELECT title, 
            client_id 
            FROM errands 
            WHERE id = ? AND status = 'pending' 
            LIMIT 1
        ");
        $check_stmt->bind_param("i", $errand_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_stmt->close();

        if ($check_result->num_rows === 1) {
            $row = $check_result->fetch_assoc();
            $errand_title = $row['title'];
            $client_id = (int)$row['client_id'];

            // Update the errand to accepted
            $update_stmt = $conn->prepare("
                UPDATE errands
                SET status = 'accepted',
                    tasker_id = ?,
                    accepted_at = NOW(),
                    updated_at = NOW()
                WHERE id = ? AND status = 'pending'
                LIMIT 1
            ");
            $update_stmt->bind_param("ii", $tasker_id, $errand_id);
            $update_stmt->execute();

            if ($update_stmt->affected_rows === 1) {
                $update_stmt->close();

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

                // Notification for client

                // Notification content
                $notif_type = "errand";
                $notif_message = "Your Errand: \"{$errand_title}\" (ID: {$errand_id}) was \"Accepted\" by Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}).";
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
                    $notif_message = "Errand Accepted | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Created by Client: \"{$client_full_name}\" (ID: {$client_id}) | Accepted by Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}).";
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

                    $_SESSION['success'] = "You have successfully accepted this errand.";
                    header("Location: view_errand_info.php?errand_id={$errand_id}");
                    exit;
                } else {
                    $_SESSION['error'] = "Failed to accept the . Please try again.";
                }

            } else {
                $_SESSION['error'] = "Errand not found or already taken by another tasker.";
            }
            
            header("Location: tasker_dashboard.php");
            exit;

    } else {
            $_SESSION['error'] = "Invalid request.";
            header("Location: tasker_dashboard.php");
            exit;
    }
?>
