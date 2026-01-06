<?php
    session_start();
    require_once "../shared/db.php";

    /* ==============================
    INITIALIZATION
    ============================== */
    $error = "";
    $title = $description = $deadline_input = $contact = $location = "";
    $fee_input = "";
    $fee = null;

    /* ==============================
    AUTH CHECK
    ============================== */
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
        header("Location: ../auth/login.php");
        exit;
    }

    $client_id = (int)$_SESSION['user_id'];

    /* ==============================
    ERRAND ID CHECK
    ============================== */
    if (empty($_GET['errand_id'])) {
        $_SESSION['error'] = "No errand selected.";
        header("Location: client_dashboard.php");
        exit;
    }

    $errand_id = (int)$_GET['errand_id'];

    /* ==============================
    FETCH CLIENT
    ============================== */
    $stmt = $conn->prepare("SELECT first_name, middle_name, last_name FROM users WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $client = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$client) {
        session_destroy();
        header("Location: ../auth/login.php");
        exit;
    }

    $client_full_name = trim($client['first_name']." ".$client['middle_name']." ".$client['last_name']);

    /* ==============================
    FETCH ERRAND
    ============================== */
    $stmt = $conn->prepare("SELECT * FROM errands WHERE id=? AND client_id=? LIMIT 1");
    $stmt->bind_param("ii", $errand_id, $client_id);
    $stmt->execute();
    $errand = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$errand) {
        $_SESSION['error'] = "Errand not found.";
        header("Location: client_dashboard.php");
        exit;
    }

    /* ==============================
    FETCH ATTACHMENTS
    ============================== */
    $stmt = $conn->prepare("SELECT * FROM errand_attachments WHERE errand_id=?");
    $stmt->bind_param("i", $errand_id);
    $stmt->execute();
    $attachments_result = $stmt->get_result();
    $stmt->close();

    /* ==============================
    DEFAULT FORM VALUES
    ============================== */
    $title = $errand['title'];
    $description = $errand['description'];
    $deadline_input = date('F j, Y g:i A', strtotime($errand['deadline']));
    $contact = $errand['contact'];
    $location = $errand['location'];
    $fee_input = $errand['fee'];

    /* ==============================
    POST HANDLER
    ============================== */
    if ($_SERVER["REQUEST_METHOD"] === "POST") {

        /* ---------- INPUT ---------- */
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $deadline_input = $_POST['deadline'] ?? '';
        $contact = trim($_POST['contact'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $fee_input = $_POST['fee'] ?? '';

        /* ---------- VALIDATION ---------- */
        if ($title==='' || $description==='' || $deadline_input==='' || $contact==='' || $location==='') {
            $error = "All fields are required.";
        }

        if (empty($error)) {
            if (!is_numeric($fee_input) || (float)$fee_input <= 0) {
                $error = "Fee must be a positive number.";
            } else {
                $fee = (float)$fee_input;
            }
        }

        if (empty($error)) {
            $timestamp = strtotime($deadline_input);
            if (!$timestamp || $timestamp < time()) {
                $error = "Invalid or past deadline.";
            } else {
                $deadline = date('Y-m-d H:i:s', $timestamp);
            }
        }

        /* ==============================
        ATTACHMENT DELETIONS
        ============================== */
        if (empty($error) && isset($_POST['delete_attachments'])) {
            foreach ($_POST['delete_attachments'] as $aid) {
                $aid = (int)$aid;
                $stmt = $conn->prepare("SELECT file_path FROM errand_attachments WHERE id=? AND errand_id=?");
                $stmt->bind_param("ii", $aid, $errand_id);
                $stmt->execute();
                $file = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($file) {
                    $path = __DIR__ . '/../' . ltrim($file['file_path'],'/');
                    if (file_exists($path)) unlink($path);

                    $stmt = $conn->prepare("DELETE FROM errand_attachments WHERE id=?");
                    $stmt->bind_param("i", $aid);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        /* ==============================
        ATTACHMENT UPLOADS
        ============================== */
        $uploaded_files = [];

        if (empty($error) && !empty($_FILES['attachment']['name'][0])) {

            $allowed = ['pdf','jpg','jpeg','png','gif','docx'];
            $total = array_sum($_FILES['attachment']['size']);

            if ($total > 10*1024*1024) {
                $error = "Attachments exceed 10MB total.";
            } else {
                foreach ($_FILES['attachment']['name'] as $k => $name) {
                    $tmp = $_FILES['attachment']['tmp_name'][$k];
                    $size = $_FILES['attachment']['size'][$k];
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

                    if (!in_array($ext,$allowed) || $size > 10*1024*1024) {
                        $error = "Invalid attachment: $name";
                        break;
                    }

                    $uniq = "client_{$client_id}_".uniqid().".$ext";
                    $disk = "../uploads/attachments/$uniq";
                    $db = "uploads/attachments/$uniq";

                    if (!move_uploaded_file($tmp,$disk)) {
                        $error = "Upload failed: $name";
                        break;
                    }

                    $uploaded_files[] = [$disk,$db,$name];
                }
            }
        }

        if (!empty($error)) {
            foreach ($uploaded_files as $f) if (file_exists($f[0])) unlink($f[0]);
        }

        /* ==============================
        UPDATE + INSERT
        ============================== */
        if (empty($error)) {

            $stmt = $conn->prepare("
                UPDATE errands 
                SET title=?, description=?, deadline=?, contact=?, location=?, fee=?
                WHERE id=? AND client_id=?
            ");
            $stmt->bind_param("sssssdii",$title,$description,$deadline,$contact,$location,$fee,$errand_id,$client_id);
            $stmt->execute();
            $stmt->close();

            foreach ($uploaded_files as $f) {
                $stmt = $conn->prepare("
                    INSERT INTO errand_attachments (errand_id,file_path,original_name)
                    VALUES (?,?,?)
                ");
                $stmt->bind_param("iss",$errand_id,$f[1],$f[2]);
                $stmt->execute();
                $stmt->close();
            }

            /* ==============================
            NOTIFICATIONS
            ============================== */
            $stmt = $conn->prepare("
                SELECT u.id,u.first_name,u.middle_name,u.last_name
                FROM errands e JOIN users u ON e.tasker_id=u.id
                WHERE e.id=? AND e.tasker_id IS NOT NULL
            ");
            $stmt->bind_param("i",$errand_id);
            $stmt->execute();
            $tasker = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($tasker) {
                $msg = "Client \"$client_full_name\" edited errand \"$title\".";
                $link = "../tasker/view_errand_info.php?errand_id=$errand_id";

                $stmt = $conn->prepare("
                    INSERT INTO notifications (user_id,type,message,link)
                    VALUES (?, 'errand', ?, ?)
                ");
                $stmt->bind_param("iss",$tasker['id'],$msg,$link);
                $stmt->execute();
                $stmt->close();
            }

            $_SESSION['success'] = "Errand edited successfully!";
            header("Location: view_errand_info.php?errand_id=$errand_id");
            exit;
        }
    }
?>
