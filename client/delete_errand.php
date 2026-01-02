<?php
    session_start();
    require_once "../shared/db.php";

    // Only client can delete errands
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
        header("Location: ../auth/login.php");
        exit;
    }

    $client_id = (int)$_SESSION['user_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
        $errand_id = (int)$_POST['id'];

        // Verify the errand belongs to the logged-in client 
        $stmt = $conn->prepare("SELECT id, title FROM errands WHERE id = ? AND client_id = ? LIMIT 1");
        $stmt->bind_param("ii", $errand_id, $client_id);
        $stmt->execute();
        $check_result = $stmt->get_result();
        $stmt->close();

        if ($check_result->num_rows !== 1) {
            $_SESSION['error'] = "Errand not found or you don't have permission to cancel it.";
            header("Location: client_dashboard.php");
            exit;
        }

        $row = $check_result->fetch_assoc();
        $errand_title = $row['title'];
        $attachment_dir = "../uploads/attachments/";

        // Fetch all attachments linked to this errand 
        $stmt = $conn->prepare("SELECT file_path FROM errand_attachments WHERE errand_id = ?");
        $stmt->bind_param("i", $errand_id);
        $stmt->execute();
        $attachments_result = $stmt->get_result();
        $stmt->close();

        if ($attachments_result->num_rows > 0) {
            while ($file = $attachments_result->fetch_assoc()) {
                $file_path = $attachment_dir . basename($file['file_path']);
                if (file_exists($file_path)) {
                    unlink($file_path); // Delete the physical file
                }
            }
        }

        // Delete attachment records from DB 
        $stmt = $conn->prepare("DELETE FROM errand_attachments WHERE errand_id = ?");
        $stmt->bind_param("i", $errand_id);
        $stmt->execute();
        $stmt->close();

        // Delete the errand record itself 
        $stmt = $conn->prepare("DELETE FROM errands WHERE id = ? AND client_id = ?");
        $stmt->bind_param("ii", $errand_id, $client_id);
        $delete_result = $stmt->execute();
        $stmt->close();

        if ($delete_result) {
            $_SESSION['success'] = "Your Errand: \"{$errand_title}\" (ID: {$errand_id}) request has been cancelled and deleted successfully.";
            header("Location: client_dashboard.php");
            exit;
        } else {
            $_SESSION['error'] = "Error in cancelling and deleting in your Errand: \"{$errand_title}\" (ID: {$errand_id}) request. Please try again.";
            header("Location: client_dashboard.php");
            exit;
        }

    } else {
        $_SESSION['error'] = "Invalid request.";
        header("Location: client_dashboard.php");
        exit;
    }
?>
