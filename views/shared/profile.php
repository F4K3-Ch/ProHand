<?php
    session_start();
    require_once "../../src/Classes/Database.php";
    require_once "../../src/Classes/User.php";
    require_once "../../src/Classes/Auth.php";
    require_once "../../src/Controllers/SharedController.php";

    $database = new Database();
    $db = $database->getConnection();
    $controller = new SharedController($db);

    $data = $controller->profile();
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
        <div class="profile-info-container">
            <div class="profile-container">
                <h1>My Profile</h1>

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
                    <a href="<?= htmlspecialchars($profile_picture); ?>" target="_blank">
                        <img class="profile-preview-photo" src="<?= htmlspecialchars($profile_picture); ?>" alt="Profile Picture" style="cursor:pointer;">
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
                        <!-- Edit Profile Button -->
                        <a href="edit_profile_info.php" class="btn-edit-profile">Edit Profile</a>

                        <!-- Change Password Button -->
                        <a href="change_password.php" class="btn-change-password">Change Password</a>

                        <!-- Delete Account Button -->
                        <form method="POST" action="delete_account.php" onsubmit="return confirm('Are you sure you want to delete your account?');">
                            <button type="submit" class="btn-delete-account">Delete Account</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
