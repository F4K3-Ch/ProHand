<?php
    session_start();
    require_once "../../src/Classes/Database.php";
    require_once "../../src/Classes/User.php";
    require_once "../../src/Classes/Auth.php";
    require_once "../../src/Classes/Client.php";
    require_once "../../src/Controllers/ClientController.php";

    $database = new Database();
    $db = $database->getConnection();
    $controller = new ClientController($db);

    $errand_id = (int)$_GET['errand_id'];
    $data = $controller->viewErrandDetails($errand_id); 

    // Process form if submitted
    $formData = $controller->handleEditErrand($errand_id, $_POST, $_FILES);

    if (!$data) {
        $_SESSION['error'] = "Errand not found.";
        header("Location: client_dashboard.php");
        exit;
    }
    
    extract($data); // Now we have $errand and $attachments_result
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include '../../includes/web_logo.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css' rel='stylesheet'> 
    <link rel="stylesheet" href="../../public/assets/style/style.css">
    <title>ProHand</title>
</head>
<body>
    <!-- Sidebar -->
    <?php include '../../includes/sidebar.php'; ?>

    <!-- Back button -->
    <a href="view_errand_info.php?errand_id=<?= (int)$errand_id; ?>" class="btn-back">
        <i class="fa-solid fa-arrow-left"></i> 
        Back
    </a>

    <!-- View Main Content -->
    <main class="view-main">

        <!-- Edit Errand Container --> 
        <div class="edit-errand-container">
            <h1>Edit Errand</h1>

            <!-- Success/Error Message -->
            <div class="view-main-error-success-message">
                <?php if (isset($_SESSION['success'])): ?>
                    <p class="success"><?= htmlspecialchars($_SESSION['success']); ?></p>
                    <?php unset($_SESSION['success']); ?>
                <?php elseif (isset($_SESSION['error'])): ?>
                    <p class="error"><?= htmlspecialchars($_SESSION['error']); ?></p>
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
                    <input type="number" step="0.01" name="fee" value="<?= htmlspecialchars($errand['fee']) ?>" required>
                </div>

                <div class="edit-errand-input-box">
                    <label>Existing Attachment</label>
                    <div class="edit-attachment-list">
                        <?php if ($attachment_result && $attachment_result->num_rows > 0): ?>
                            <?php 
                                $file = $attachment_result->fetch_assoc(); 
                                $filename = basename($file['file_path']);
                                $relative_path = '../../public/uploads/attachments/' . $filename;
                            ?>
                            <div class="edit-attachment-item">
                                <a href="<?= htmlspecialchars($relative_path); ?>" target="_blank" class="edit-attachment-name">
                                    <i class="fa-solid fa-file"></i> <?= htmlspecialchars($file['original_name']); ?>
                                </a>
                                
                                <label class="delete-checkbox" style="margin-left: 15px; color: red; cursor: pointer;">
                                    <input type="checkbox" name="remove_current_file" value="1"> 
                                    <span>Remove file</span>
                                </label>
                            </div>
                        <?php else: ?>
                            <p class="no-attachment">None</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="edit-errand-input-box">
                    <label>Replace Attachment (optional)</label>
                    <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.gif,.docx">
                    <small>Only 1 file allowed. Maximum size: 10 MB</small>
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
