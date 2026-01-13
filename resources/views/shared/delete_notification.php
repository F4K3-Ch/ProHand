<?php
    session_start();
    require_once "db.php";

    // Ensure the user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        header("Location: ../auth/login.php");
        exit;
    }

    $user_id = (int)$_SESSION['user_id'];
    $role = $_SESSION['role'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // Single delete
        if (isset($_POST['notification_id'])) {
            $notification_id = (int)$_POST['notification_id'];

            $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $notification_id, $user_id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Notification deleted.";
            } else {
                $_SESSION['error'] = "Failed to delete notification.";
            }
            $stmt->close();

        // Delete all notifications
        } elseif (isset($_POST['delete_all'])) {
            $stmt = $conn->prepare("DELETE FROM notifications WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "All notifications deleted.";
            } else {
                $_SESSION['error'] = "Failed to delete notifications.";
            }
            $stmt->close();

        } else {
            $_SESSION['error'] = "Invalid request.";
        }

    } else {
        $_SESSION['error'] = "Invalid request.";
    }

    header("Location: notifications.php");
    exit;
?>
