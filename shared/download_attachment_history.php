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

    // Check for attachment ID
    if (!isset($_GET['id'])) {
        $_SESSION['error'] = "Invalid file request.";
        if ($role === 'admin') {
            header("Location: ../admin/admin_dashboard.php");
        } elseif ($role === 'tasker') {
            header("Location: ../tasker/tasker_dashboard.php");
        } elseif ($role === 'client') {
            header("Location: ../client/client_dashboard.php");
        } else {
            header("Location: ../auth/login.php");
        }
        exit;
    }

    $attachment_id = (int)$_GET['id'];

    // Fetch attachment info 
    $stmt = $conn->prepare("SELECT file_path, original_name FROM history_attachments WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $attachment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if ($result->num_rows !== 1) {
        $_SESSION['error'] = "File not found.";

        if ($role === 'admin') {
            header("Location: ../admin/admin_dashboard.php");
        } elseif ($role === 'tasker') {
            header("Location: ../tasker/tasker_dashboard.php");
        } elseif ($role === 'client') {
            header("Location: ../client/client_dashboard.php");
        } else {
            header("Location: ../auth/login.php");
        }
        exit;
    }

    $attachment = $result->fetch_assoc();

    $file_path = "../uploads/history/attachments/" . basename($attachment['file_path']);
    $original_name = $attachment['original_name'];

    // Verify file exists
    if (!file_exists($file_path)) {
        $_SESSION['error'] = "File does not exist on server.";
        if ($role === 'admin') {
            header("Location: ../admin/admin_dashboard.php");
        } elseif ($role === 'tasker') {
            header("Location: ../tasker/tasker_dashboard.php");
        } elseif ($role === 'client') {
            header("Location: ../client/client_dashboard.php");
        } else {
            header("Location: ../auth/login.php");
        }
        exit;
    }

    // Force download
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($original_name) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file_path));

    // Output the file
    readfile($file_path);
    exit;
?>
