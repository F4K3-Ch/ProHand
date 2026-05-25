<?php
    session_start();

    // 1. Load Dependencies
    require_once "../../src/Classes/Database.php";
    require_once "../../src/Classes/User.php";
    require_once "../../src/Classes/Auth.php";
    require_once "../../src/Classes/Admin.php";
    require_once "../../src/Controllers/AdminController.php";

    // 2. Initialize Objects
    $database = new Database();
    $db = $database->getConnection();
    $adminController = new AdminController($db);

    // 3. Process password reset request if form was sent here
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password_id'])) {
        $_POST['id'] = $_POST['reset_password_id'];
        $adminController->handlePasswordReset();
    }

    // 4. Fetch specific user data based on $_GET['id']
    $viewData = $adminController->viewUserInfo();
    extract($viewData);
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
                            <!-- Reset Password Button -->
                            <form method="POST" onsubmit="return confirm('Are you sure you want to reset this user\'s password?');">
                                <input type="hidden" name="reset_password_id" value="<?= htmlspecialchars($user['id']) ?>">
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