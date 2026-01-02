<?php
    session_start();
    require_once "db.php";

    // Ensure logged in & valid role
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['client', 'tasker'])) {
        header("Location: ../auth/login.php");
        exit;
    }

    $user_id = (int)$_SESSION['user_id'];
    $role = $_SESSION['role'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
        $history_id = (int)$_POST['id'];

        // Verify owner of this history record
        //Fetch Errand Info
        if ($role === 'client') {
            $check_stmt = $conn->prepare("SELECT id, errand_id, title FROM history WHERE id = ? AND client_id = ? LIMIT 1");
        } elseif ($role === 'tasker') {
            $check_stmt = $conn->prepare("SELECT id, errand_id, title FROM history WHERE id = ? AND tasker_id = ? LIMIT 1");
        } else {
            $_SESSION['error'] = "Invalid role!";
            exit;
        }

        $check_stmt->bind_param("ii", $history_id, $user_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $check_stmt->close();

        if ($result->num_rows !== 1) {
            $_SESSION['error'] = "You are not authorized to delete this errand.";
            if ($role === 'client') {
                header("Location: ../client/client_finished_errands.php");
            } elseif ($role === 'tasker') {
                header("Location: ../tasker/tasker_finished_errands.php");
            } else {
                header("Location: ../auth/login.php");
            }
            exit;
        }

        //Fetch Errand Info
        $row = $result->fetch_assoc();
        $errand_id = (int)$row['errand_id'];
        $errand_title = $row['title'];

        // Update correct archive flag
        if ($role === 'client') {
            $update_stmt = $conn->prepare("UPDATE history SET client_deleted = 1 WHERE id = ?");
        } elseif ($role === 'tasker') {
            $update_stmt = $conn->prepare("UPDATE history SET tasker_deleted = 1 WHERE id = ?");
        } else {
            $_SESSION['error'] = "Invalid role!";
            exit;
        }

        $update_stmt->bind_param("i", $history_id);

        if ($update_stmt->execute()) {
            $_SESSION['success'] = "Errand: \"{$errand_title}\" (ID: {$errand_id}) was deleted successfully.";
        } else {
            $_SESSION['error'] = "Error deleting Errand: \"{$errand_title}\" (ID: {$errand_id}). Please try again.";
        }

        $update_stmt->close();

        if ($role === 'client') {
            header("Location: ../client/client_finished_errands.php");
        } elseif ($role === 'tasker') {
            header("Location: ../tasker/tasker_finished_errands.php");
        } else {
            header("Location: ../auth/login.php");
        }
        exit;

    } else {
        $_SESSION['error'] = "Invalid request.";
        if ($role === 'client') {
            header("Location: ../client/client_finished_errands.php");
        } elseif ($role === 'tasker') {
            header("Location: ../tasker/tasker_finished_errands.php");
        } else {
            header("Location: ../auth/login.php");
        }
        exit;
    }
?>
