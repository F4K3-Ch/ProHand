<?php
    session_start();
    require_once '../shared/db.php';

    // Ensure only admins can access
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header("Location: ../auth/login.php");
        exit;
    }

    // Check if 'id' is provided
    if (!isset($_GET['id'])) {
        $_SESSION['error'] = "No Selected User.";
        header("Location: manage_users.php?error=No user selected.");
        exit;
    }

    $user_id = (int)$_GET['id'];

    // Determine where user came from (dashboard or notification)
    if (!empty($_GET['from']) && $_GET['from'] === 'dashboard') {
        $from = 'dashboard';
    } else if (!empty($_GET['from']) && $_GET['from'] === 'notification') {
        $from = 'notification';
    } else {
        $from = 'dashboard'; // default
    }

    // Build back URL based on origin
    $back_url = "";
    if ($from === 'dashboard') {
        $back_url = "manage_users.php";
    } elseif ($from === 'notification') {
        $back_url = "../shared/notifications.php";
    }

    // Fetch user info
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Profile picture path
        $profile_dir = '../uploads/profile_pictures/';

        // Determine which profile picture to use based on availability and file existence
        if (!empty($user['profile_picture']) && file_exists($profile_dir . basename($user['profile_picture']))) {
            $user_profile_picture = $profile_dir . basename($user['profile_picture']);
        } else {
            $user_profile_picture = $profile_dir . 'default.jpg';
        }
    } else {
        $_SESSION['error'] = "User not found.";
        header("Location: manage_users.php?error=User not found.");
        exit;
    }

    if ($user['role'] === 'tasker') {
        // Active errands
        $stmt = $conn->prepare("
            SELECT 
                SUM(status = 'accepted')   AS accepted_count,
                SUM(status = 'in_process') AS in_process_count,
                COUNT(*) AS total_active_errands
            FROM errands
            WHERE tasker_id = ?
            AND status IN ('accepted', 'in_process')
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        $row = $result->fetch_assoc();
        $active_status = array (
            'accepted'   => (int)$row['accepted_count'],
            'in_process' => (int)$row['in_process_count'],
        );
        $total_active_errands = (int)$row['total_active_errands'];

        // Completed/failed errands
        $stmt = $conn->prepare("
            SELECT 
                SUM(status = 'completed') AS completed_count,
                SUM(status = 'failed') AS failed_count,
                COUNT(*) AS total_finished_errands
            FROM history
            WHERE tasker_id = ?
            AND status IN ('completed', 'failed')
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        $row = $result->fetch_assoc();
        $finished_status = array(
            'completed' => (int)$row['completed_count'],
            'failed' => (int)$row['failed_count']
        );

        $total_finished_errands = (int)$row['total_finished_errands'];

        // Total errands (active + finished)
        $total_errands = $total_active_errands + $total_finished_errands;

        // Earnings
        $stmt = $conn->prepare("
            SELECT COALESCE(SUM(fee), 0) AS earnings
            FROM history
            WHERE tasker_id = ?
            AND status = 'completed'
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $earnings = $stmt->get_result()->fetch_assoc()['earnings'];
        $stmt->close();

        $stmt = $conn->prepare("
            SELECT COALESCE(SUM(fee), 0) AS failed_earnings
            FROM history
            WHERE tasker_id = ?
            AND status = 'failed'
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $failed_earnings = $stmt->get_result()->fetch_assoc()['failed_earnings'];
        $stmt->close();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include '../includes/web_logo.php'; ?>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css' rel='stylesheet'> 
    <link rel="stylesheet" href="../assets/style/style.css">
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
        <div class="profile-info-container">
            <div class="profile-container">
                <h1>User Profile</h1>

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

                <!-- Photo Section -->
                <div class="profile-photo-section">
                    <a href="<?= htmlspecialchars($user_profile_picture); ?>" target="_blank">
                        <img class="profile-preview-photo" src="<?= htmlspecialchars($user_profile_picture); ?>" alt="Profile Picture" style="cursor:pointer;">
                    </a>
                </div>

                <!-- Profile Info Container Display -->
                <div class="profile-bot-container">

                    <div class="profile-box">
                        <label>ID</label>
                        <input type="text" readonly value="<?= htmlspecialchars($user['id']); ?>">
                    </div>

                    <div class="profile-box">
                        <label>Email</label>
                        <input type="text" readonly value="<?= htmlspecialchars($user['email']); ?>">
                    </div>

                    <div class="group-profile-box">
                        <div class="profile-box">
                            <label>Last Name</label>
                            <input type="text" readonly value="<?= htmlspecialchars($user['last_name']); ?>">
                        </div>

                        <div class="profile-box">
                            <label>First Name</label>
                            <input type="text" readonly value="<?= htmlspecialchars($user['first_name']); ?>">
                        </div>

                        <div class="profile-box">
                            <label>Middle Name</label>
                            <input type="text" readonly value="<?= htmlspecialchars($user['middle_name']); ?>">
                        </div>
                    </div>

                    <div class="profile-box">
                        <label>Role</label>
                        <input type="text" readonly value="<?= htmlspecialchars(ucfirst($user['role'])); ?>">
                    </div>

                    <div class="profile-box">
                        <label>Contact Number</label>
                        <input type="text" readonly value="<?= htmlspecialchars($user['contact_number']); ?>">
                    </div>

                    <div class="profile-box">
                        <label>Created At</label>
                        <input type="text" readonly value="<?= htmlspecialchars(date('F j, Y g:i A', strtotime($user['created_at']))); ?>">
                    </div>

                    <div class="profile-bot-btn">
                        <?php if ($user['role'] === 'admin'): ?>
                            <p>Admin accounts cannot be edited or deleted.</p>
                        <?php else: ?>
                            <!-- Edit Profile Button -->
                            <form method="GET" action="edit_user_info.php">
                                <input type="hidden" name="id" value="<?= (int)$user['id']; ?>">
                                <button type="submit" class="btn-edit-profile">Edit this user</button>
                            </form>

                            <!-- Reset Password Button -->
                            <form method="POST" action="reset_password.php" onsubmit="return confirm('Are you sure you want to reset this user\'s password?');">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($user['id']) ?>">
                                <button type="submit" class="btn-reset-password">Reset Password</button>
                            </form>
                            
                            <!-- Delete Account Button -->
                            <form method="POST" action="delete_user.php" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                <input type="hidden" name="id" value="<?= (int)$user['id']; ?>">
                                <button type="submit" class="btn-delete-account">Delete this user</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php if ($user['role'] === 'tasker'): ?>
            <div class="tasker-errand-metrics">
                <h3>Errand Status Metrics</h3>

                <div class="total-cards-container">
                    <div class="total-card bg-accepted">
                        <div class="card-header">
                            <span class="status-circle circle-accepted"></span>
                            <h3>Accepted</h3>
                        </div>
                        <p><?= number_format($active_status['accepted']); ?></p>
                    </div>

                    <div class="total-card bg-process">
                        <div class="card-header">
                            <span class="status-circle circle-process"></span>
                            <h3>In Process</h3>
                        </div>
                        <p><?= number_format($active_status['in_process']); ?></p>
                    </div>

                    <div class="total-card bg-total">
                        <div class="card-header">
                            <span class="status-circle circle-total"></span>
                            <h3>Total Active Errands</h3>
                        </div>
                        <p><?= number_format($total_active_errands); ?></p>
                    </div>

                    <div class="total-card bg-completed">
                        <div class="card-header">
                            <span class="status-circle circle-complete"></span>
                            <h3>Completed</h3>
                        </div>
                        <p><?= number_format($finished_status['completed']); ?></p>
                    </div>

                    <div class="total-card bg-failed">
                        <div class="card-header">
                            <span class="status-circle circle-failed"></span>
                            <h3>Failed</h3>
                        </div>
                        <p><?= number_format($finished_status['failed']); ?></p>
                    </div>

                    <div class="total-card bg-total">
                        <div class="card-header">
                            <span class="status-circle circle-total"></span>
                            <h3>Total Finished Errands</h3>
                        </div>
                        <p><?= number_format($total_finished_errands); ?></p>
                    </div>

                    <div class="total-card bg-earnings">
                        <div class="card-header">
                            <span class="status-circle circle-earnings"></span>
                            <h3>Earnings</h3>
                        </div>
                        <p>₱<?= number_format($earnings, 2); ?></p>
                    </div>

                    <div class="total-card bg-failed-earnings">
                        <div class="card-header">
                            <span class="status-circle circle-failed-earnings"></span>
                            <h3>Failed Earnings</h3>
                        </div>
                        <p>&#8722; ₱<?= number_format($failed_earnings, 2); ?></p>
                    </div>

                    <div class="total-card bg-total">
                        <div class="card-header">
                            <span class="status-circle circle-total"></span>
                            <h3>Total Errands</h3>
                        </div>
                        <p><?= number_format($total_errands); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>