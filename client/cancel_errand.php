<?php
    session_start();
    require_once "../shared/db.php";

    // Ensure only client can access
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
        header("Location: ../auth/login.php");
        exit;
    }

    $client_id = (int)$_SESSION['user_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
        $errand_id = (int)$_POST['id'];

        // Verify that this errand belongs to the client and is accepted
        $check_stmt = $conn->prepare("
            SELECT title, tasker_id
            FROM errands
            WHERE id = ? AND client_id = ? AND status = 'accepted'
            LIMIT 1
        ");
        $check_stmt->bind_param("ii", $errand_id, $client_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_stmt->close();

        if ($check_result->num_rows === 1) {
            $row = $check_result->fetch_assoc();
            $errand_title = $row['title'];
            $tasker_id = $row['tasker_id'];

            // Reset the errand back to pending
            $update_stmt = $conn->prepare("
                UPDATE errands
                SET status = 'pending',
                    tasker_id = NULL,
                    accepted_at = NULL,
                    in_process_at = NULL,
                    updated_at = NOW()
                WHERE id = ? AND client_id = ? AND status = 'accepted'
                LIMIT 1
            ");
            $update_stmt->bind_param("ii", $errand_id, $client_id);
            $update_stmt->execute();

            if ($update_stmt->affected_rows === 1) {
                $update_stmt->close();

                // Fetch client info
                $client_stmt = $conn->prepare("SELECT first_name, middle_name, last_name FROM users WHERE id = ? LIMIT 1");
                $client_stmt->bind_param("i", $client_id);
                $client_stmt->execute();
                $client = $client_stmt->get_result()->fetch_assoc();
                $client_full_name = trim($client['first_name'] . ' ' . $client['middle_name'] . ' ' . $client['last_name']);
                $client_stmt->close();

                // Notify assigned tasker if exists
                if ($tasker_id) {
                    $tasker_stmt = $conn->prepare("SELECT first_name, middle_name, last_name FROM users WHERE id = ? LIMIT 1");
                    $tasker_stmt->bind_param("i", $tasker_id);
                    $tasker_stmt->execute();
                    $tasker = $tasker_stmt->get_result()->fetch_assoc();
                    $tasker_full_name = trim($tasker['first_name'] . ' ' . $tasker['middle_name'] . ' ' . $tasker['last_name']);
                    $tasker_stmt->close();

                    $notif_type = "errand";
                    $notif_message = "The Errand: \"{$errand_title}\" (ID: {$errand_id}) you accepted earlier was \"Cancelled\" by the Client: \"{$client_full_name}\" (ID: {$client_id}).";
                    $notif_link = "../tasker/view_errand_info.php?errand_id={$errand_id}";

                    $notif_stmt = $conn->prepare("
                        INSERT INTO notifications (user_id, type, message, link)
                        VALUES (?, ?, ?, ?)
                    ");
                    $notif_stmt->bind_param("isss", $tasker_id, $notif_type, $notif_message, $notif_link);
                    $notif_stmt->execute();
                    $notif_stmt->close();
                }

                // Notify all admins
                $admins_result = $conn->query("SELECT id FROM users WHERE role = 'admin'");
                while ($admin = $admins_result->fetch_assoc()) {
                    $admin_id = (int)$admin['id'];

                    $notif_type = "errand";
                    $notif_message = "Errand Cancelled | Errand: \"{$errand_title}\" (ID: {$errand_id}) | Previously Assigned to Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}) | Cancelled by Client: \"{$client_full_name}\" (ID: {$client_id}).";
                    $notif_link = "../admin/view_errand_info.php?errand_id={$errand_id}";

                    $notif_stmt = $conn->prepare("
                        INSERT INTO notifications (user_id, type, message, link)
                        VALUES (?, ?, ?, ?)
                    ");
                    $notif_stmt->bind_param("isss", $admin_id, $notif_type, $notif_message, $notif_link);
                    $notif_stmt->execute();
                    $notif_stmt->close();
                }

                $_SESSION['success'] = "You cancelled Errand: \"{$errand_title}\" (ID: {$errand_id}).";
                header("Location: client_dashboard.php");
                exit;

            } else {
                $_SESSION['error'] = "Failed to cancel Errand: \"{$errand_title}\" (ID: {$errand_id}). Please try again.";
                header("Location: client_dashboard.php");
                exit;
            }

        } else {
            $_SESSION['error'] = "You are not authorized to cancel this errand or it's not in accepted status.";
            header("Location: client_dashboard.php");
            exit;
        }

    } else {
        $_SESSION['error'] = "Invalid request.";
        header("Location: client_dashboard.php");
        exit;
    }
?>
