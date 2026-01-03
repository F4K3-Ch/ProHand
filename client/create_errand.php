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

    // Handle form submission
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $deadline_input = $_POST['deadline'] ?? '';
        $contact = trim($_POST['contact'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $fee_input = $_POST['fee'] ?? '';

        if ($title === '' || $description === '' || $deadline_input === '' || $contact === '' || $location === '') {
            $error = "All fields are required OR Invalid Entry.";
        }

        // Fee validation
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

        // Only proceed if no validation errors
        if (empty($error)) {
            $stmt = $conn->prepare("
                INSERT INTO errands (client_id, title, description, deadline, contact, location, fee)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("isssssd", $client_id, $title, $description, $deadline, $contact, $location, $fee);

            if ($stmt->execute()) {
                $errand_id = $stmt->insert_id;
                $stmt->close();

                $base_dir = "../uploads/attachments/";
                $uploaded_files = []; // track uploaded files in case rollback is needed
                $max_total_size = 10 * 1024 * 1024; // 10MB TOTAL

                // Handle attachments
                if (!empty($_FILES['attachment']['name'][0])) {
                    $total_size = array_sum($_FILES['attachment']['size']);

                    if ($total_size > $max_total_size) {
                        $error = "Total attachment size must not exceed 10MB.";
                    } else {
                        foreach ($_FILES['attachment']['name'] as $key => $original_name) {
                            $tmp_path = $_FILES['attachment']['tmp_name'][$key];
                            $file_size = $_FILES['attachment']['size'][$key];
                            $file_type = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

                            if (empty($original_name) || $file_size == 0) continue;

                            $allowed_types = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'docx'];
                            $max_size = 10 * 1024 * 1024; // 10MB

                            if (!in_array($file_type, $allowed_types)) {
                                $error = "Only PDF, JPG, PNG, GIF, and DOCX files are allowed.";
                            } elseif ($file_size > $max_size) {
                                $error = "File '$original_name' is too large. Max size is 10MB.";
                            } else {
                                $unique_name = "client_" . $client_id . "_" . time() . "_" . uniqid() . "." . $file_type;
                                $destination_path = $base_dir . $unique_name;
                                $db_path = "/uploads/attachments/" . $unique_name;

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

                            // Rollback if any error occurred
                            if (!empty($error)) {
                                // Delete uploaded files from disk
                                foreach ($uploaded_files as $file_path) {
                                    if (file_exists($file_path)) unlink($file_path);
                                }

                                // Delete attachment records in DB
                                $delete_attach_stmt = $conn->prepare("DELETE FROM errand_attachments WHERE errand_id = ?");
                                $delete_attach_stmt->bind_param("i", $errand_id);
                                $delete_attach_stmt->execute();
                                $delete_attach_stmt->close();

                                // Delete the errand itself
                                $delete_errand_stmt = $conn->prepare("DELETE FROM errands WHERE id = ?");
                                $delete_errand_stmt->bind_param("i", $errand_id);
                                $delete_errand_stmt->execute();
                                $delete_errand_stmt->close();

                                break; // exit loop on error
                            }
                        }
                    }
                }

                // Redirect only if no errors after attachments
                if (empty($error)) {
                    $_SESSION['success'] = "Errand created successfully!";
                    header("Location: view_errand_info.php?errand_id=" . $errand_id);
                    exit;
                }
            } else {
                $error = "Failed to create errand. Please try again.";
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
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css' rel='stylesheet'> 
    <link rel="stylesheet" href="../assets/style/style.css">
    <title>ProHand</title>
</head>
<body>
    <!-- Sidebar -->
    <?php include '../includes/client_sidebar.php'; ?>

    <!-- Back button -->
    <a href="client_dashboard.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i>Back</a>

    <!-- View Main Content -->
    <main class="view-main">
        <div class="create-errand-container">
            <h1>Create Errand</h1>

            <!-- Error Message -->
            <div class="create-errand-error-success-message">
                <?php if (!empty($error)): ?>
                    <p class="error"><?= htmlspecialchars($error); ?></p>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <div class="create-errand-input-box">
                    <label>Title</label>
                    <input type="text" name="title" value="<?= htmlspecialchars($title) ?>" required>
                </div>

                <div class="create-errand-input-box">
                    <label>Description</label>
                    <textarea name="description" required><?= htmlspecialchars($description) ?></textarea>
                </div>

                <div class="create-errand-input-box">
                    <label>Deadline</label>
                    <input type="text" name="deadline" id="deadline" value="<?= htmlspecialchars($deadline_input) ?>" required>
                </div>

                <div class="create-errand-input-box">
                    <label>Contact</label>
                    <textarea name="contact" placeholder="Phone, email, social media..." required><?= htmlspecialchars($contact) ?></textarea>
                </div>

                <div class="create-errand-input-box">
                    <label>Location</label>
                    <input type="text" name="location" value="<?= htmlspecialchars($location) ?>" required>
                </div>
                
                <div class="create-errand-input-box">
                    <label>Fee (₱)</label>
                    <input type="number" step="0.01" name="fee" value="<?= htmlspecialchars((string)$fee_input) ?>" required>
                </div>

                <div class="create-errand-input-box">
                    <label>Attachment (optional)</label>
                    <input type="file" name="attachment[]" multiple accept=".pdf,.jpg,.jpeg,.png,.gif,.docx">
                    <small>Maximum file size: 10 MB</small>
                </div>

                <button type="submit" class="btn">Create Errand</button>
            </form>
        </div>
    </main>



    <script>
        // Initialize Flatpickr on the input with id="deadline"
        flatpickr("#deadline", {
            enableTime: true,             // Allow the user to select a time in addition to the date
            dateFormat: "F j, Y h:i K",   // Format the displayed date/time:
                                        // F = full month name (January, February…)
                                        // j = day of the month (1–31)
                                        // Y = full year (2025)
                                        // h = hour (12-hour format)
                                        // i = minutes (00–59)
                                        // K = AM/PM
            minDate: "today",             // Disable selection of past dates; only today or future dates allowed
            time_24hr: false               // Use 12-hour time format with AM/PM instead of 24-hour format
        });
    </script>
</body>
</html>