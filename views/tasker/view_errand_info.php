<?php
    session_start();
    require_once "../../src/Classes/Database.php";
    require_once "../../src/Classes/User.php";
    require_once "../../src/Classes/Auth.php";
    require_once "../../src/Classes/Tasker.php"; 
    require_once "../../src/Controllers/TaskerController.php";
    // 1. Load the SharedController file
    require_once "../../src/Controllers/SharedController.php";

    $database = new Database();
    $db = $database->getConnection();
    
    $controller = new TaskerController($db);
    // 2. Initialize the SharedController
    $sharedController = new SharedController($db);

    // --- NEW: Download Logic ---
    if (isset($_GET['action']) && $_GET['action'] === 'download' && isset($_GET['download_id'])) {
        $is_history_download = (isset($_GET['type']) && $_GET['type'] === 'history');
        
        // 3. CALL it from $sharedController
        $sharedController->handleDownload((int)$_GET['download_id'], $is_history_download ? 'history' : 'regular');
        
        // If handleDownload fails to exit (e.g., file not found), set the error
        $_SESSION['error'] = "File not found on server.";
    }

    // 1. Handle POST Actions (Remains the same)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['accept_id'])) {
            $_POST['id'] = $_POST['accept_id'];
            $controller->accept();
        } elseif (isset($_POST['start_task_id'])) {
            $_POST['id'] = $_POST['start_task_id']; 
            $controller->updateStatusInProcess();
        } elseif (isset($_POST['final_status_id'])) {
            $_POST['id'] = $_POST['final_status_id'];
            $controller->updateStatusFinal();
        } elseif (isset($_POST['cancel_id'])) {
            $_POST['id'] = $_POST['cancel_id']; 
            $controller->cancelErrand();
        }
    }

    // 2. Fetch Errand Data
    $errand_id = (int)($_GET['errand_id'] ?? 0);
    $data = $controller->viewErrandDetails($errand_id);

    if (!$data) {
        $_SESSION['error'] = "Errand not found.";
        header("Location: tasker_dashboard.php");
        exit;
    }

    extract($data); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include '../../includes/web_logo.php'; ?>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css' rel='stylesheet'> 
    <link rel="stylesheet" href="../../public/assets/style/style.css">
    <title>ProHand</title>
</head>
<body>
    <!-- Sidebar -->
    <?php include '../../includes/sidebar.php'; ?>

    <!-- Back button -->
    <a href="<?= $back_url; ?>" class="btn-back">
        <i class="fa-solid fa-arrow-left"></i> 
        Back
    </a>

    <!-- View Main Content -->
    <main class="view-main">

        <!-- Info Container --> 
        <div class="info-container">

            <!-- Errand Details Container -->
            <div class="errand-details-container">
                <h1>Errand Details</h1>

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

                <div class="group-info-box">
                    <div class="info-box">
                        <label>Errand ID</label>
                        <input type="text" readonly value="<?= htmlspecialchars($errand['errand_id']); ?>">
                    </div>

                    <div class="info-box">
                        <label>Title</label>
                        <input type="text" readonly value="<?= htmlspecialchars($errand['title']); ?>">
                    </div>
                </div>

                <div class="info-box">
                    <label>Description</label>
                    <textarea readonly><?= htmlspecialchars($errand['description']); ?></textarea>
                </div>

                <div class="info-box">
                    <label>Contact</label>
                    <textarea readonly><?= htmlspecialchars($errand['contact']); ?></textarea>
                </div>

                <div class="group-info-box">
                    <div class="info-box">
                        <label>Location</label>
                        <input type="text" readonly value="<?= htmlspecialchars($errand['location']); ?>">
                    </div>

                    <div class="info-box">
                        <label>Fee</label>
                        <input type="text" readonly value="₱<?= number_format($errand['fee'], 2); ?>">
                    </div>
                </div>

                <div class="group-info-box">
                    <div class="info-box">
                        <label>Created At</label>
                        <input type="text" readonly value="<?= htmlspecialchars(date('F j, Y g:i A', strtotime($errand['created_at']))); ?>">
                    </div>

                    <div class="info-box">
                        <label>Deadline</label>
                        <input type="text" readonly value="<?= htmlspecialchars(date('F j, Y g:i A', strtotime($errand['deadline']))); ?>">
                    </div>
                </div>

                <div class="group-info-box">
                    <div class="info-box">
                        <label>Accepted At</label>
                        <input type="text" readonly value="<?php 
                            if (!empty($errand['accepted_at'])) {
                                echo htmlspecialchars(date('F j, Y g:i A', strtotime($errand['accepted_at'])));
                            } else {
                                echo 'Not set';
                            }
                        ?>">
                    </div>

                    <div class="info-box">
                        <label>In Process At</label>
                        <input type="text" readonly value="<?php 
                            if (!empty($errand['in_process_at'])) {
                                echo htmlspecialchars(date('F j, Y g:i A', strtotime($errand['in_process_at'])));
                            } else {
                                echo 'Not set';
                            }
                        ?>">
                    </div>
                </div>

                <div class="group-info-box">
                    <div class="info-box">
                        <label>Finished At</label>
                        <input type="text" readonly value="<?php 
                            if (!empty($errand['completed_at'])) {
                                echo htmlspecialchars(date('F j, Y g:i A', strtotime($errand['completed_at'])));
                            } elseif (!empty($errand['failed_at'])) {
                                echo htmlspecialchars(date('F j, Y g:i A', strtotime($errand['failed_at'])));
                            } else {
                                echo 'Not set';
                            }
                        ?>">
                    </div>
                    
                    <div class="info-box">
                        <label>Status</label>
                        <input type="text" readonly value="<?= htmlspecialchars($status_labels[$errand['status']]); ?>">
                    </div>
                </div>

                <div class="info-box">
                    <label>Attachment</label>

                    <?php if ($attachment_result && $attachment_result->num_rows > 0): ?>
                        <div class="attachment-list">
                            <?php 
                                // Reset pointer or loop if needed; here we fetch the row
                                $file = $attachment_result->fetch_assoc(); 
                                
                                // Path for browser preview
                                $relative_path = '../../public/' . $file['file_path'];
                                
                                // Point back to the current file
                                $download_action = "view_errand_info.php";
                            ?>

                            <div class="attachment-item">
                                <a href="<?= htmlspecialchars($relative_path); ?>" target="_blank" class="attachment-name">
                                    <i class="fa-solid fa-file-lines"></i> <?= htmlspecialchars($file['original_name']); ?>
                                </a>

                                <form method="GET" action="view_errand_info.php">
                                    <!-- Keep the user on the current errand page -->
                                    <input type="hidden" name="errand_id" value="<?= $errand_id; ?>">
                                    
                                    <!-- Trigger word for the IF statement -->
                                    <input type="hidden" name="action" value="download">
                                    
                                    <!-- Your specific change here -->
                                    <input type="hidden" name="download_id" value="<?= (int)$file['id']; ?>">
                                    
                                    <!-- Check if history is needed -->
                                    <?php if (isset($is_finished) && $is_finished): ?>
                                        <input type="hidden" name="type" value="history">
                                    <?php endif; ?>

                                    <button type="submit" class="btn-download">
                                        <i class="fa-solid fa-download"></i> Download
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="attachment-list no-attachments">
                            <p class="no-attachment">None</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="info-bot-btn">
                    <?php if ($errand['status'] === 'pending'): ?>
                        <!-- Accept Button -->
                        <form method="POST">
                            <input type="hidden" name="accept_id" value="<?= $errand['id']; ?>">
                            <button type="submit" class="btn-accept" onclick="return confirm('Do you want to accept this errand?');">
                                Accept
                            </button>
                        </form>
                    <?php elseif ($errand['status'] === 'accepted'): ?>
                        <!-- Update Button -->
                        <form method="POST">
                            <input type="hidden" name="start_task_id" value="<?= $errand['id']; ?>">
                            <input type="hidden" name="new_status" value="in_process">
                            <button type="submit" class="btn-start-errand">Start Errand</button>
                        </form>
                        <!-- Cancel Button -->
                        <form method="POST">
                            <input type="hidden" name="cancel_id" value="<?= $errand['id']; ?>">
                            <button type="submit" class="btn-cancel" onclick="return confirm('Are you sure you want to cancel this errand?');">
                                Cancel
                            </button>
                        </form>
                    <?php elseif ($errand['status'] === 'in_process'): ?>
                        <!-- Update Button -->
                        <form method="POST">
                            <input type="hidden" name="final_status_id" value="<?= $errand['id']; ?>">
                                <div class="update-wrapper">
                                    <select name="new_status" required>
                                        <option value="">Select</option>
                                        <option value="completed">Completed</option>
                                        <option value="failed">Failed</option>
                                    </select>
                                </div>
                            <button type="submit" class="btn-update">Update</button>
                        </form>
                        <!-- Cancel Button -->
                        <form method="POST">
                            <input type="hidden" name="cancel_id" value="<?= $errand['id']; ?>">
                            <button type="submit" class="btn-cancel" onclick="return confirm('Are you sure you want to cancel this errand?');">
                                Cancel
                            </button>
                        </form>
                    <?php elseif (in_array($errand['status'], ['completed', 'failed'])): ?>
                        <!-- Delete Button -->
                        <form method="POST" action="../shared/delete_finished_errand.php" onsubmit="return confirm('Are you sure you want to delete this archived errand?');">
                            <input type="hidden" name="id" value="<?= $errand['id']; ?>">
                            <button type="submit" class="btn-delete">Delete</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Client Details Container -->
            <div class="client-details-container">
                <h1>Created By</h1>

                <div class="client-profile-photo">
                    <a href="<?= htmlspecialchars($client_picture); ?>" target="_blank">
                        <img src="<?= htmlspecialchars($client_picture); ?>">
                    </a>
                </div>

                <div class="client-info-box">
                    <label>User ID</label>
                    <input type="text" readonly value="<?= htmlspecialchars($errand['client_id']); ?>">
                </div>

                <div class="client-info-box">
                    <label>Email</label>
                    <input type="text" readonly value="<?= htmlspecialchars($errand['client_email']); ?>">
                </div>   

                <div class="client-info-box">
                    <label>Name</label>
                    <input type="text" readonly value="<?= htmlspecialchars(trim($errand['client_first_name'].' '.$errand['client_middle_name'].' '.$errand['client_last_name'])); ?>">
                </div>

                <div class="client-info-box">
                    <label>Contact Number</label>
                    <input type="text" readonly value="<?= htmlspecialchars($errand['client_contact_number']); ?>">
                </div>
            </div>
        </div>
    </main>
</body>
</html>
