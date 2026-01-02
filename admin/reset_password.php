<?php
    session_start();
    require_once "../shared/db.php";

    // Ensure only admin can access
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

    // Check if request is valid
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
        $user_id = (int)$_POST['id'];

        // Verify user exists
        $stmt = $conn->prepare("
            SELECT first_name, middle_name, last_name, role
            FROM users 
            WHERE id = ? 
            LIMIT 1
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows !== 1) {
            $_SESSION['error'] = "User not found.";
            header("Location: manage_users.php");
            exit;
        } else {
            $user = $result->fetch_assoc();
            $user_full_name = trim($user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name']);
            $role = $user['role'];
        }

        // Generate new random temporary password
        $temporary_password = substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 10);

        // Hash the password before storing
        $hashed_password = password_hash($temporary_password, PASSWORD_DEFAULT);

        // Update in database
        $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update_stmt->bind_param("si", $hashed_password, $user_id);

        if ($update_stmt->execute()) {
            $update_stmt->close();

            // Fetch other admins info
            $stmt = $conn->prepare("SELECT id FROM users WHERE role = 'admin' AND id != ?");
            $stmt->bind_param("i", $admin_id);
            $stmt->execute();
            $other_admins_result = $stmt->get_result();
            $stmt->close();

            // Notify other admins
            while ($other_admin = $other_admins_result->fetch_assoc()) {
                $other_admin_id = (int)$other_admin['id'];

                // Notification content
                $notif_type = "system";
                $notif_message = "Password Reset | User: \"{$user_full_name}\" (ID: {$user_id}) | Role: \"" . ucfirst($role)  . "\" | Reset by Admin: \"{$admin_full_name}\" (ID: {$admin_id}) | Temporary Password: \"{$temporary_password}\".";
                $notif_link = "../admin/view_user_info.php?id={$user_id}"; // link to user details

                // Insert into notifications table
                $notif_stmt = $conn->prepare("
                    INSERT INTO notifications (user_id, type, message, link)
                    VALUES (?, ?, ?, ?)
                ");
                $notif_stmt->bind_param("isss", $other_admin_id, $notif_type, $notif_message, $notif_link);
                $notif_stmt->execute();
                $notif_stmt->close();
            }

            $_SESSION['success'] = "Password reset successfully! Temporary password: \"$temporary_password\"";
            header("Location: view_user_info.php?id=$user_id");
            exit;
        } else {
            $_SESSION['error'] = "Failed to reset password. Please try again.";
            header("Location: view_user_info.php?id=$user_id");
            exit;
        }
    } else {
        header("Location: manage_users.php");
        exit;
    }
?>
