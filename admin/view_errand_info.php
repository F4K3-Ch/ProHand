<?php
    session_start();
    require_once "../shared/db.php";

    // Only admin can access
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header("Location: ../auth/login.php");
        exit;
    }

    // Ensure an errand ID is provided
    if (!isset($_GET['errand_id']) || empty($_GET['errand_id'])) {
        $_SESSION['error'] = "No errand selected.";
        header("Location: admin_dashboard.php");
        exit;
    }

    $errand_id = (int)$_GET['errand_id'];

    // Determine where user came from (dashboard, finished or notification)
    if (!empty($_GET['from']) && $_GET['from'] === 'dashboard') {
        $from = 'dashboard';
    } else if (!empty($_GET['from']) && $_GET['from'] === 'finished') {
        $from = 'finished';
    } else if (!empty($_GET['from']) && $_GET['from'] === 'notification') {
        $from = 'notification';
    } else {
        $from = 'dashboard'; // default
    }
    

    // Build back URL based on origin
    $back_url = "";
    if ($from === 'dashboard') {
        $back_url = "admin_dashboard.php";
    } elseif ($from === 'finished') {
        $back_url = "finished_errands.php";
    } elseif ($from === 'notification') {
        $back_url = "../shared/notifications.php";
    } else {
        $back_url = "admin_dashboard.php";
    }

    // Status Labels
    $status_labels = array(
        'pending' => 'Pending',
        'accepted' => 'Accepted',
        'in_process' => 'In Process',
        'completed' => 'Completed',
        'failed' => 'Failed'
    );

    $stmt = $conn->prepare("
        SELECT status 
        FROM errands 
        WHERE id = ?
        UNION
        SELECT status 
        FROM history 
        WHERE errand_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("ii", $errand_id, $errand_id);
    $stmt->execute();
    $status_result = $stmt->get_result();
    $stmt->close();

    if ($status_result->num_rows !== 1) {
        $_SESSION['error'] = "Errand not found.";
        header("Location: admin_dashboard.php");
        exit;
    }

    $status_row = $status_result->fetch_assoc();
    $status = $status_row['status'];

    $is_active = in_array($status, ['pending', 'accepted', 'in_process']);
    $is_finished = in_array($status, ['completed', 'failed']);


    if ($is_active) {
        // Fetch active errand + client + tasker info
        $stmt = $conn->prepare("
            SELECT e.*,
                e.id AS errand_id,
                c.id AS client_id,
                c.first_name AS client_first_name,
                c.middle_name AS client_middle_name, 
                c.last_name AS client_last_name, 
                c.email AS client_email, 
                c.contact_number AS client_contact_number, 
                c.profile_picture AS client_profile_picture,
                t.id AS tasker_id,
                t.first_name AS tasker_first_name, 
                t.middle_name AS tasker_middle_name, 
                t.last_name AS tasker_last_name, 
                t.email AS tasker_email, 
                t.contact_number AS tasker_contact_number, 
                t.profile_picture AS tasker_profile_picture
            FROM errands AS e
            LEFT JOIN users AS c ON e.client_id = c.id
            LEFT JOIN users AS t ON e.tasker_id = t.id
            WHERE e.id = ?
            LIMIT 1
        ");
        $stmt->bind_param("i", $errand_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows !== 1) {
            $_SESSION['error'] = "Errand not found.";
            header("Location: admin_dashboard.php");
            exit;
        }

        $errand = $result->fetch_assoc();

        // Fetch attachments
        $stmt = $conn->prepare("
            SELECT 
                id, 
                file_path, 
                original_name 
                FROM errand_attachments 
                WHERE errand_id = ?
        ");
        $stmt->bind_param("i", $errand_id);
        $stmt->execute();
        $attachments_result = $stmt->get_result();
        $stmt->close();

        // Profile pictures for client and tasker
        $profile_dir = '../uploads/profile_pictures/';
        $default_picture = $profile_dir . 'default.jpg';

        // Default to placeholder
        $client_picture = $default_picture;
        $tasker_picture = $default_picture;

        // Determine client profile picture
        if (!empty($errand['client_profile_picture']) && file_exists($profile_dir . basename($errand['client_profile_picture']))) {
            $client_picture = $profile_dir . basename($errand['client_profile_picture']);
        }

        // Determine tasker profile picture
        if (!empty($errand['tasker_profile_picture']) && file_exists($profile_dir . basename($errand['tasker_profile_picture']))) {
            $tasker_picture = $profile_dir . basename($errand['tasker_profile_picture']);
        }
    } elseif ($is_finished) {
        // Fetch finished errand + client + tasker info
        $stmt = $conn->prepare("
            SELECT * 
            FROM history 
            WHERE errand_id = ?  
            LIMIT 1
        ");
        $stmt->bind_param("i", $errand_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows !== 1) {
            $_SESSION['error'] = "Errand not found.";
            header("Location: finished_errands.php");
            exit;
        }

        $errand = $result->fetch_assoc();
        $history_id = $errand['id']; // PK of history table

        // Fetch attachments from history_attachments
        $stmt = $conn->prepare("
            SELECT 
                id, 
                file_path, 
                original_name 
                FROM history_attachments 
                WHERE history_errand_id = ?
        ");
        $stmt->bind_param("i", $history_id);
        $stmt->execute();
        $attachments_result = $stmt->get_result();
        $stmt->close();

        // Profile pictures for client and tasker
        $profile_dir = '../uploads/history/profile_pictures/';
        $default_picture = $profile_dir . 'default.jpg';

        // Default to placeholder
        $client_picture = $default_picture;
        $tasker_picture = $default_picture;

        // Determine client profile picture
        if (!empty($errand['client_profile_picture']) && file_exists($profile_dir . basename($errand['client_profile_picture']))) {
            $client_picture = $profile_dir . basename($errand['client_profile_picture']);
        }

        // Determine tasker profile picture
        if (!empty($errand['tasker_profile_picture']) && file_exists($profile_dir . basename($errand['tasker_profile_picture']))) {
            $tasker_picture = $profile_dir . basename($errand['tasker_profile_picture']);
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include '../includes/web_logo.php'; ?>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css' rel='stylesheet'> 
    <link rel="stylesheet" href="../assets/style/style.css?v=<?= filemtime('../assets/style/style.css'); ?>">
    <title>ProHand</title>
</head>
<body>
    <!-- Sidebar -->
    <?php include '../includes/admin_sidebar.php'; ?>

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
                                    if ($is_active) {
                                        $filename = basename($file['file_path']);
                                        $relative_path = '../uploads/attachments/' . $filename;
                                    } elseif ($is_finished) {
                                        $filename = basename($file['file_path']);
                                        $relative_path = '../uploads/history/attachments/' . $filename;
                                    }
                                ?>

                                <div class="attachment-item">
                                    <a href="<?= htmlspecialchars($relative_path); ?>" target="_blank" class="attachment-name">
                                        <?= htmlspecialchars($file['original_name']); ?>
                                    </a>

                                    <?php if ($is_active): ?>
                                        <!-- Download Attachment Button -->
                                        <form method="GET" action="../shared/download_attachment.php">
                                            <input type="hidden" name="id" value="<?= (int)$file['id']; ?>">
                                            <button type="submit" class="btn-download">Download</button>
                                        </form>
                                    <?php elseif ($is_finished): ?>
                                        <!-- Download Attachment Button -->
                                        <form method="GET" action="../shared/download_attachment_history.php">
                                            <input type="hidden" name="id" value="<?= (int)$file['id']; ?>">
                                            <button type="submit" class="btn-download">Download</button>
                                        </form>
                                    <?php endif; ?>
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
