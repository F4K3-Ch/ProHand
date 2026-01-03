<?php
    session_start();
    require_once "../shared/db.php";

    // Initialize form variables
    $error = "";
    $title = "";
    $description = "";
    $deadline_input = "";
    $contact = "";
    $location = "";
    $fee_input = "";
    $fee = null;

    // Ensure only client can access
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
        header("Location: ../auth/login.php");
        exit;
    }

    $client_id = (int)$_SESSION['user_id'];

    // Ensure an errand ID is provided
    if (!isset($_GET['errand_id']) || empty($_GET['errand_id'])) {
        $_SESSION['error'] = "No errand selected.";
        header("Location: client_dashboard.php");
        exit;
    }

    // Fetch client info
    $stmt = $conn->prepare("SELECT first_name, middle_name, last_name FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $client_result = $stmt->get_result();
    $stmt->close();

    if ($client_result->num_rows === 1) {
        $client = $client_result->fetch_assoc();
        $client_full_name = trim($client['first_name'] . ' ' . $client['middle_name'] . ' ' . $client['last_name']);
    } else {
        session_unset();
        session_destroy();
        header("Location: ../auth/login.php");
        exit;
    }

    $errand_id = (int)$_GET['errand_id'];

    // Fetch errand
    $stmt = $conn->prepare("SELECT * FROM errands WHERE id = ? AND client_id = ? LIMIT 1");
    $stmt->bind_param("ii", $errand_id, $client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if ($result->num_rows !== 1) {
        $_SESSION['error'] = "Errand not found or access denied.";
        header("Location: client_dashboard.php");
        exit;
    }

    $errand = $result->fetch_assoc();

    // Fetch attachments
    $stmt = $conn->prepare("SELECT * FROM errand_attachments WHERE errand_id = ?");
    $stmt->bind_param("i", $errand_id);
    $stmt->execute();
    $attachments_result = $stmt->get_result();
    $stmt->close();

    // Initialize form values with existing data
    $title = $errand['title'];
    $description = $errand['description'];
    $deadline_input = date('F j, Y g:i A', strtotime($errand['deadline']));
    $contact = $errand['contact'];
    $location = $errand['location'];
    $fee_input = $errand['fee'];

    // Handle form submission
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $deadline_input = $_POST['deadline'] ?? '';
        $contact = trim($_POST['contact'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $fee_input = $_POST['fee'] ?? '';

        // Validate required fields
        if ($title === '' || $description === '' || $deadline_input === '' || $contact === '' || $location === '') {
            $error = "All fields are required OR Invalid Entry.";
        }

        // Validate fee
        if (empty($error)) {
            if ($fee_input === '' || !is_numeric($fee_input) || (float)$fee_input <= 0) {
                $error = "Fee must be a positive number.";
            } else {
                $fee = (float)$fee_input;
            }
        }

        // Validate deadline
        if (empty($error)) {
            $timestamp = strtotime($deadline_input);
            if (!$timestamp) {
                $error = "Invalid deadline format.";
            } elseif ($timestamp < time()) {
                $error = "Deadline cannot be in the past.";
            } else {
                $deadline = date('Y-m-d H:i:s', $timestamp);
            }
        }

        // Proceed if no validation errors
        if (empty($error)) {
            $stmt = $conn->prepare("
                UPDATE errands 
                SET title=?, description=?, deadline=?, contact=?, location=?, fee=?
                WHERE id=? AND client_id=?
            ");
            $stmt->bind_param("sssssdii", $title, $description, $deadline, $contact, $location, $fee, $errand_id, $client_id);
            $update_result = $stmt->execute();
            $stmt->close();

            if ($update_result) {
                // Handle deletions
                if (isset($_POST['delete_attachments']) && is_array($_POST['delete_attachments'])) {
                    foreach ($_POST['delete_attachments'] as $attachment_id) {
                        $attachment_id = (int)$attachment_id;
                        $stmt = $conn->prepare("SELECT file_path FROM errand_attachments WHERE id=? AND errand_id=? LIMIT 1");
                        $stmt->bind_param("ii", $attachment_id, $errand_id);
                        $stmt->execute();
                        $file_result = $stmt->get_result();
                        $stmt->close();

                        if ($file_result && $file_result->num_rows === 1) {
                            $file_row = $file_result->fetch_assoc();
                            $file_path = __DIR__ . '/../' . ltrim($file_row['file_path'], '/');
                            if (file_exists($file_path)) unlink($file_path);

                            $delete_stmt = $conn->prepare("DELETE FROM errand_attachments WHERE id=? LIMIT 1");
                            $delete_stmt->bind_param("i", $attachment_id);
                            $delete_stmt->execute();
                            $delete_stmt->close();
                        }
                    }
                }

                // Handle new attachments
                if (!empty($_FILES['attachment']['name'][0])) {
                    $uploaded_files = [];
                    $total_new_size = array_sum($_FILES['attachment']['size']);
                    $max_total_size = 10 * 1024 * 1024; // 10MB TOTAL

                    if ($total_new_size > $max_total_size) {
                        $error = "The total size of new attachments must not exceed 10MB.";
                    } else {
                        foreach ($_FILES['attachment']['name'] as $key => $original_name) {
                            $tmp_path = $_FILES['attachment']['tmp_name'][$key];
                            $file_size = $_FILES['attachment']['size'][$key];
                            $file_type = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

                            if (empty($original_name) || $file_size == 0) continue;

                            $allowed_types = ['pdf','jpg','jpeg','png','gif','docx'];
                            $max_size = 10 * 1024 * 1024; // 10 MB

                            if (!in_array($file_type, $allowed_types)) {
                                $error = "Only PDF, JPG, PNG, GIF, DOCX files are allowed.";
                            } elseif ($file_size > $max_size) {
                                $error = "File '$original_name' is too large. Maximum size is 10MB.";
                            } else {
                                $unique_name = "client_" . $client_id . "_" . time() . "_" . uniqid() . "." . $file_type;
                                $destination_path = "../uploads/attachments/" . $unique_name;
                                $db_path = "uploads/attachments/" . $unique_name;

                                if (move_uploaded_file($tmp_path, $destination_path)) {
                                    $attach_stmt = $conn->prepare("
                                        INSERT INTO errand_attachments (errand_id, file_path, original_name)
                                        VALUES (?, ?, ?)
                                    ");
                                    $attach_stmt->bind_param("iss", $errand_id, $db_path, $original_name);
                                    $attach_stmt->execute();
                                    $attach_stmt->close();

                                    $uploaded_files[] = $destination_path;
                                } else {
                                    $error = "Failed to upload file '$original_name'. Please try again.";
                                }
                            }

                            // Rollback new files on error
                            if (!empty($error)) {
                                foreach ($uploaded_files as $file_path) {
                                    if (file_exists($file_path)) unlink($file_path);
                                }
                                break;
                            }
                        }
                    }
                }

                if (empty($error)) {
                    // Fetch tasker info
                    $tasker_stmt = $conn->prepare("
                        SELECT u.first_name, u.middle_name, u.last_name, e.tasker_id
                        FROM errands AS e
                        JOIN users AS u ON e.tasker_id = u.id
                        WHERE e.id = ? AND e.tasker_id IS NOT NULL
                        LIMIT 1
                    ");
                    $tasker_stmt->bind_param("i", $errand_id);
                    $tasker_stmt->execute();
                    $tasker = $tasker_stmt->get_result()->fetch_assoc();
                    $tasker_stmt->close();

                    if ($tasker) {
                        $tasker_id = (int)$tasker['tasker_id'];
                        $tasker_full_name = trim($tasker['first_name'] . ' ' . $tasker['middle_name'] . ' ' . $tasker['last_name']);

                        // Notify tasker
                        $notif_type = "errand";
                        $notif_message = "The Client: \"{$client_full_name}\" (ID: {$client_id}) has Edited the details of your assigned Errand: \"{$title}\" (ID: {$errand_id}).";
                        $notif_link = "../tasker/view_errand_info.php?errand_id={$errand_id}";

                        $notif_stmt = $conn->prepare("
                            INSERT INTO notifications (user_id, type, message, link)
                            VALUES (?, ?, ?, ?)
                        ");
                        $notif_stmt->bind_param("isss", $tasker_id, $notif_type, $notif_message, $notif_link);
                        $notif_stmt->execute();
                        $notif_stmt->close();

                        // Notify admins
                        $admins_result = $conn->query("SELECT id FROM users WHERE role = 'admin'");
                        while ($admin = $admins_result->fetch_assoc()) {
                            $admin_id = (int)$admin['id'];
                            $notif_type = "errand";
                            $notif_message = "Errand Edited | Errand: \"{$title}\" (ID: {$errand_id}) | Assigned to Tasker: \"{$tasker_full_name}\" (ID: {$tasker_id}) | Edited by Client: \"{$client_full_name}\" (ID: {$client_id}).";

                            $notif_link = "../admin/view_errand_info.php?errand_id={$errand_id}";

                            $notif_stmt = $conn->prepare("
                                INSERT INTO notifications (user_id, type, message, link)
                                VALUES (?, ?, ?, ?)
                            ");
                            $notif_stmt->bind_param("isss", $admin_id, $notif_type, $notif_message, $notif_link);
                            $notif_stmt->execute();
                            $notif_stmt->close();
                        }
                    }
                    
                    $_SESSION['success'] = "Errand edited successfully!";
                    header("Location: view_errand_info.php?errand_id=$errand_id");
                    exit;
                }

            } else {
                $error = "Failed to update errand. Please try again.";
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include '../includes/web_logo.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css' rel='stylesheet'> 
    <link rel="stylesheet" href="../assets/style/style.css">
    <title>ProHand</title>
</head>
<body>
    <!-- Sidebar -->
    <?php include '../includes/client_sidebar.php'; ?>

    <!-- Back button -->
    <a href="view_errand_info.php?errand_id=<?= $errand_id; ?>" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Back</a>

    <!-- View Main Content -->
    <main class="view-main">

        <!-- Edit Errand Container --> 
        <div class="edit-errand-container">
            <h1>Edit Errand</h1>

            <!-- Success/Error Message -->
            <div class="edit-errand-error-success-message">
                <?php if (isset($_SESSION['success'])): ?>
                    <p class="success"><?= htmlspecialchars($_SESSION['success']); ?></p>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <p class="error"><?= htmlspecialchars($error); ?></p>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <div class="edit-errand-input-box">
                    <label>Title</label>
                    <input type="text" name="title" value="<?= htmlspecialchars($errand['title']); ?>" required>
                </div>

                <div class="edit-errand-input-box">
                    <label>Description</label>
                    <textarea name="description" required><?= htmlspecialchars($errand['description']); ?></textarea>
                </div>

                <div class="edit-errand-input-box">
                    <label>Deadline</label>
                    <input type="text" name="deadline" id="deadline" value="<?= date('F j, Y g:i A', strtotime($errand['deadline'])); ?>" required>
                </div>

                <div class="edit-errand-input-box">
                    <label>Contact</label>
                    <textarea name="contact" required><?= htmlspecialchars($errand['contact']); ?></textarea>
                </div>
                
                <div class="edit-errand-input-box">
                    <label>Location</label>
                    <input type="text" name="location" value="<?= htmlspecialchars($errand['location']); ?>" required>
                </div>

                <div class="edit-errand-input-box">
                    <label>Fee (₱)</label>
                    <input type="number" step="0.01" name="fee" value="<?= htmlspecialchars((string)$fee_input); ?>" required>
                </div>

                <div class="edit-errand-input-box">
                    <label>Existing Attachments</label>

                    <?php if ($attachments_result->num_rows > 0): ?>
                        <div class="edit-attachment-list">
                            <?php while ($file = $attachments_result->fetch_assoc()): ?>
                                <?php
                                    $filename = basename($file['file_path']);
                                    $relative_path = '../uploads/attachments/' . $filename;
                                ?>
                                
                                <div class="edit-attachment-item">
                                    <a href="<?= htmlspecialchars($relative_path); ?>" target="_blank" class="edit-attachment-name">
                                        <?= htmlspecialchars($file['original_name']); ?>
                                    </a>

                                    <!-- Checkbox styled as button -->
                                    <label class="delete-checkbox">
                                        <input type="checkbox" name="delete_attachments[]" value="<?= (int)$file['id']; ?>">
                                        <span>Delete</span>
                                    </label>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="edit-attachment-list edit-no-attachments">
                            <p class="edit-no-attachment">None</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="edit-errand-input-box">
                    <label>New Attachments (optional)</label>
                    <input type="file" name="attachment[]" multiple accept=".pdf,.jpg,.jpeg,.png,.gif,.docx">
                    <small>Maximum file size: 10 MB</small>
                </div>

                <button type="submit" class="btn">Update Errand</button>
            </form>
        </div>
    </main>

    <script>
    flatpickr("#deadline", {
        enableTime: true,
        dateFormat: "F j, Y h:i K",
        minDate: "today",
        time_24hr: false
    });
    </script>
</body>
</html>
