<?php
    session_start();
    require_once "../../src/Classes/Database.php";
    require_once "../../src/Classes/User.php";
    require_once "../../src/Classes/Auth.php";
    require_once "../../src/Classes/Admin.php"; // Ensure you create this class
    require_once "../../src/Controllers/AdminController.php";

    $database = new Database();
    $db = $database->getConnection();
    $controller = new AdminController($db);

    $errand_id = (int)($_GET['errand_id'] ?? 0);
    $data = $controller->viewErrandDetails($errand_id);

    if (!$data) {
        $_SESSION['error'] = "Errand not found.";
        header("Location: admin_dashboard.php");
        exit;
    }

    // This makes $errand, $client_picture, $tasker_picture, etc. available to the HTML
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
                    <label>Attachments</label>

                    <?php if ($attachments_result->num_rows > 0): ?>
                        <div class="attachment-list">
                            <?php while ($file = $attachments_result->fetch_assoc()): ?>
                                <?php
                                    // The database stores 'uploads/attachments/filename.ext'
                                    // We need to go up two levels to reach the root, then into public/
                                    $relative_path = '../../public/' . $file['file_path'];
                                    
                                    // Determine which download script to use
                                    // Assuming your shared folder is at the root level relative to views/client/
                                    $download_action = $is_active ? "../../shared/download_attachment.php" : "../../shared/download_attachment_history.php";
                                ?>

                                <div class="attachment-item">
                                    <a href="<?= htmlspecialchars($relative_path); ?>" target="_blank" class="attachment-name">
                                        <i class="fa-solid fa-file-lines"></i> <?= htmlspecialchars($file['original_name']); ?>
                                    </a>

                                    <form method="GET" action="<?= $download_action; ?>">
                                        <input type="hidden" name="id" value="<?= (int)$file['id']; ?>">
                                        <button type="submit" class="btn-download">
                                            <i class="fa-solid fa-download"></i> Download
                                        </button>
                                    </form>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="attachment-list no-attachments">
                            <p class="no-attachment">None</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="info-bot-btn">
                    <?php if ($is_active): ?>
                        <!-- Delete Active Errand Button -->
                        <form method="POST" action="delete_active_errand.php" onsubmit="return confirm('Are you sure you want to delete this errand?');">
                            <input type="hidden" name="id" value="<?= $errand['id']; ?>">
                            <button type="submit" class="btn-delete">Delete</button>
                        </form>
                    <?php elseif ($is_finished): ?>
                        <!-- Delete Finished Errand Button -->
                        <form method="POST" action="delete_finished_errand.php" onsubmit="return confirm('Are you sure you want to delete this errand?');">
                            <input type="hidden" name="id" value="<?= $errand['id']; ?>">
                            <button type="submit" class="btn-delete">Delete</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Users Details -->
            <div class="users-container">
                <!-- Client Card Details Container -->
                <div class="user-details-container">
                    <h1>Created By</h1>

                    <div class="user-profile-photo">
                        <a href="<?= htmlspecialchars($client_picture); ?>" target="_blank">
                            <img src="<?= htmlspecialchars($client_picture); ?>" alt="Profile Picture">
                        </a>
                    </div>

                    <div class="group-info-box">
                        <div class="info-box">
                            <label>User ID</label>
                            <input type="text" readonly value="<?= htmlspecialchars($errand['client_id']); ?>">
                        </div>

                        <div class="info-box">
                            <label>Email</label>
                            <input type="text" readonly value="<?= htmlspecialchars($errand['client_email']); ?>">
                        </div>
                    </div>

                    <div class="group-info-box">
                        <div class="info-box">
                            <label>Name</label>
                            <input type="text" readonly value="<?= htmlspecialchars(trim($errand['client_first_name'] . ' ' . $errand['client_middle_name'] . ' ' . $errand['client_last_name'])); ?>">
                        </div>

                        <div class="info-box">
                            <label>Contact Number</label>
                            <input type="text" readonly value="<?= htmlspecialchars($errand['client_contact_number']); ?>">
                        </div>
                    </div>
                </div>

                <!-- Tasker Details Container -->
                <div class="user-details-container">
                    <?php if (!empty($errand['tasker_id'])): ?>
                        <h1>Accepted By</h1>

                        <div class="user-profile-photo">
                            <a href="<?= htmlspecialchars($tasker_picture); ?>" target="_blank">
                                <img src="<?= htmlspecialchars($tasker_picture); ?>" alt="Profile Picture">
                            </a>
                        </div>

                        <div class="group-info-box">
                            <div class="info-box">
                                <label>User ID</label>
                                <input type="text" readonly value="<?= htmlspecialchars($errand['tasker_id']); ?>">
                            </div>

                            <div class="info-box">
                                <label>Email</label>
                                <input type="text" readonly value="<?= htmlspecialchars($errand['tasker_email']); ?>">
                            </div>
                        </div>    

                        <div class="group-info-box">
                            <div class="info-box">
                                <label>Name</label>
                                <input type="text" readonly value="<?= htmlspecialchars(trim($errand['tasker_first_name'].' '.$errand['tasker_middle_name'].' '.$errand['tasker_last_name'])); ?>">
                            </div>

                            <div class="info-box">
                                <label>Contact Number</label>
                                <input type="text" readonly value="<?= htmlspecialchars($errand['tasker_contact_number']); ?>">
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="not-accepted-yet">
                            <p>Not Accepted Yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
