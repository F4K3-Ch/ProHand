<?php
    session_start();
    require_once "../shared/db.php";

    // Only tasker can update
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tasker') {
        header("Location: ../auth/login.php");
        exit;
    }

    $tasker_id = (int)$_SESSION['user_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['new_status'])) {
        $errand_id = (int)$_POST['id'];
        $new_status = $_POST['new_status'];

        // Validate status
        if ($new_status !== 'completed' && $new_status !== 'failed') {
            $_SESSION['error'] = "Invalid status.";
            header("Location: tasker_dashboard.php");
            exit;
        }

        // Fetch errand with client & tasker info
        $stmt = $conn->prepare("
            SELECT e.*, 
                c.first_name AS client_first_name, 
                c.middle_name AS client_middle_name, 
                c.last_name AS client_last_name,
                c.email AS client_email, 
                c.contact_number AS client_contact, 
                c.profile_picture AS client_picture,
                t.first_name AS tasker_first_name, 
                t.middle_name AS tasker_middle_name, 
                t.last_name AS tasker_last_name,
                t.email AS tasker_email, 
                t.contact_number AS tasker_contact, 
                t.profile_picture AS tasker_picture
            FROM errands AS e
            LEFT JOIN users AS c ON e.client_id = c.id
            LEFT JOIN users AS t ON e.tasker_id = t.id
            WHERE e.id = ? AND e.tasker_id = ?
            LIMIT 1
        ");
        $stmt->bind_param("ii", $errand_id, $tasker_id);
        $stmt->execute();
        $errand_result = $stmt->get_result();
        $stmt->close();

        if ($errand_result->num_rows !== 1) {
            $_SESSION['error'] = "Errand not found.";
            header("Location: tasker_dashboard.php");
            exit;
        }

        $errand = $errand_result->fetch_assoc();

        // Update status
        if ($new_status === 'completed') {
            $update_stmt = $conn->prepare("
                UPDATE errands
                SET status = ?, completed_at = NOW(), failed_at = NULL, updated_at = NOW()
                WHERE id = ? AND tasker_id = ?
            ");
        } elseif ($new_status === 'failed') {
            $update_stmt = $conn->prepare("
                UPDATE errands
                SET status = ?, completed_at = NULL, failed_at = NOW(), updated_at = NOW()
                WHERE id = ? AND tasker_id = ?
            ");
        } else {
            $_SESSION['error'] = "Invalid status!";
            exit;
        }
        $update_stmt->bind_param("sii", $new_status, $errand_id, $tasker_id);
        $update_stmt->execute();
        $update_stmt->close();

        // Refetch the errand with JOINs again to get updated timestamps
        $stmt = $conn->prepare("
            SELECT e.*, 
                c.first_name AS client_first_name, 
                c.middle_name AS client_middle_name, 
                c.last_name AS client_last_name,
                c.email AS client_email, 
                c.contact_number AS client_contact, 
                c.profile_picture AS client_picture,
                t.first_name AS tasker_first_name, 
                t.middle_name AS tasker_middle_name, 
                t.last_name AS tasker_last_name,
                t.email AS tasker_email, 
                t.contact_number AS tasker_contact, 
                t.profile_picture AS tasker_picture
            FROM errands AS e
            LEFT JOIN users AS c ON e.client_id = c.id
            LEFT JOIN users AS t ON e.tasker_id = t.id
            WHERE e.id = ?
            LIMIT 1
        ");
        $stmt->bind_param("i", $errand_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $errand = $result->fetch_assoc();
        $stmt->close();

        // Prepare full names safely
        $client_full_name = trim(($errand['client_first_name'] ?? '') . ' ' . ($errand['client_middle_name'] ?? '') . ' ' . ($errand['client_last_name'] ?? ''));
        $tasker_full_name = trim(($errand['tasker_first_name'] ?? '') . ' ' . ($errand['tasker_middle_name'] ?? '') . ' ' . ($errand['tasker_last_name'] ?? ''));

        // Default client profile picture DB
        $client_profile_history = '/uploads/history/profile_pictures/default.jpg';

        // Copy client profile picture
        if (!empty($errand['client_picture']) && basename($errand['client_picture']) !== 'default.jpg') {

            // Get extension
            $extension = pathinfo($errand['client_picture'], PATHINFO_EXTENSION);

            // Generate unique name
            $unique_client_name = $errand['client_id'] . '_' . time() . '_' . rand(1000,9999) . '.' . $extension;

            // NEW history path for DB
            $client_profile_history = '/uploads/history/profile_pictures/' . $unique_client_name;

            // Filesystem paths
            $client_profile_original_fs = __DIR__ . '/../' . ltrim($errand['client_picture'], '/');
            $client_profile_history_fs  = __DIR__ . '/../uploads/history/profile_pictures/' . $unique_client_name;

            // Copy
            if (file_exists($client_profile_original_fs)) {
                copy($client_profile_original_fs, $client_profile_history_fs);
            }
        }

        // Default tasker profile picture DB
        $tasker_profile_history = '/uploads/history/profile_pictures/default.jpg';

        // Copy tasker profile picture
        if (!empty($errand['tasker_picture']) && basename($errand['tasker_picture']) !== 'default.jpg') {

            // Get extension
            $extension = pathinfo($errand['tasker_picture'], PATHINFO_EXTENSION);

            // Generate unique name
            $unique_tasker_name = $errand['tasker_id'] . '_' . time() . '_' . rand(1000,9999) . '.' . $extension;

            // NEW history path for DB
            $tasker_profile_history = '/uploads/history/profile_pictures/' . $unique_tasker_name;

            // Filesystem paths
            $tasker_profile_original_fs = __DIR__ . '/../' . ltrim($errand['tasker_picture'], '/');
            $tasker_profile_history_fs = __DIR__ . '/../uploads/history/profile_pictures/' . $unique_tasker_name;

            // Copy
            if (file_exists($tasker_profile_original_fs)) {
                copy($tasker_profile_original_fs, $tasker_profile_history_fs);
            }
        }

        // Insert into history
        $insert_stmt = $conn->prepare("
            INSERT INTO history (
                errand_id, 
                title, 
                description, 
                deadline, 
                contact, 
                location, 
                fee, 
                status,
                client_id, 
                client_first_name,
                client_middle_name, 
                client_last_name, 
                client_email, 
                client_contact_number, 
                client_profile_picture,
                tasker_id, 
                tasker_first_name, 
                tasker_middle_name, 
                tasker_last_name, 
                tasker_email, 
                tasker_contact_number, 
                tasker_profile_picture,
                created_at, 
                accepted_at, 
                in_process_at, 
                completed_at, 
                failed_at,
                updated_at, 
                tasker_deleted, 
                client_deleted
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $tasker_deleted = 0;
        $client_deleted = 0;

        $insert_stmt->bind_param(
            "isssssdsissssssissssssssssssii",
            $errand['id'], 
            $errand['title'],
            $errand['description'], 
            $errand['deadline'], 
            $errand['contact'], 
            $errand['location'], 
            $errand['fee'], 
            $new_status,
            $errand['client_id'], 
            $errand['client_first_name'],
            $errand['client_middle_name'],
            $errand['client_last_name'],
            $errand['client_email'], 
            $errand['client_contact'], 
            $client_profile_history,
            $errand['tasker_id'], 
            $errand['tasker_first_name'],
            $errand['tasker_middle_name'],
            $errand['tasker_last_name'],
            $errand['tasker_email'], 
            $errand['tasker_contact'], 
            $tasker_profile_history,
            $errand['created_at'], 
            $errand['accepted_at'], 
            $errand['in_process_at'], 
            $errand['completed_at'], 
            $errand['failed_at'],
            $errand['updated_at'], 
            $tasker_deleted, 
            $client_deleted
        );
        $insert_stmt->execute();
        $history_id = $conn->insert_id;
        $insert_stmt->close();

        // Move attachments
        $stmt = $conn->prepare("SELECT * FROM errand_attachments WHERE errand_id = ?");
        $stmt->bind_param("i", $errand_id);
        $stmt->execute();
        $attachments_result = $stmt->get_result();
        $stmt->close();

        // Copy attachments
        while ($file = $attachments_result->fetch_assoc()) {

            // Get extension
            $extension = pathinfo($file['file_path'], PATHINFO_EXTENSION);

            // Generate unique name
            $unique_name = $errand_id . '_' . time() . '_' . rand(1000, 9999) . '.' . $extension;

            // NEW history path for DB
            $history_file_path = '/uploads/history/attachments/' . $unique_name;

            // Filesystem paths
            $original_file_fs = __DIR__ . '/../' . ltrim($file['file_path'], '/');
            $history_file_fs = __DIR__ . '/../uploads/history/attachments/' . $unique_name;

            // Copy file to history if exists
            if (file_exists($original_file_fs)) {
                copy($original_file_fs, $history_file_fs);
                unlink($original_file_fs); // delete original from uploads/attachments
            }

            // Insert into history_attachments
            $insert_stmt = $conn->prepare("
                INSERT INTO history_attachments (history_errand_id, file_path, original_name, uploaded_at)
                VALUES (?, ?, ?, ?)
            ");
            $insert_stmt->bind_param(
                "isss", 
                $history_id, 
                $history_file_path,
                $file['original_name'], 
                $file['uploaded_at']
            );
            $insert_stmt->execute();
            $insert_stmt->close();
        }

        // Delete attachment records from database
        $delete_stmt = $conn->prepare("DELETE FROM errand_attachments WHERE errand_id = ?");
        $delete_stmt->bind_param("i", $errand_id);
        $delete_stmt->execute();
        $delete_stmt->close();

        // Delete original errand
        $delete_stmt = $conn->prepare("DELETE FROM errands WHERE id = ?");
        $delete_stmt->bind_param("i", $errand_id);
        $delete_stmt->execute();
        $delete_stmt->close();

        // Notifications

        // Notification for client
        $client_id = $errand['client_id'];

        // Notification content
        $notif_type = "errand";
        $notif_message = "Your Errand: \"{$errand['title']}\" (ID: {$errand_id}) was updated to \"" . ucfirst($new_status)  . "\" by Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}).";
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
            $notif_message = "Errand Updated to " . ucfirst($new_status)  . " | Errand: \"{$errand['title']}\" (ID: {$errand_id}) | Created by Client: \"{$client_full_name}\" (ID: {$client_id}) | Updated by Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}).";
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

        $_SESSION['success'] = "Errand updated to \"" . ucfirst($new_status) . "\".";
        header("Location: view_errand_info.php?errand_id={$errand_id}");
        exit;

    } else {
        $_SESSION['error'] = "Invalid request.";
        header("Location: tasker_dashboard.php");
        exit;
    }
?>
