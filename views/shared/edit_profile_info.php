<?php
    session_start();
    require_once "../../src/Classes/Database.php";
    require_once "../../src/Classes/User.php";
    require_once "../../src/Classes/Auth.php";
    require_once "../../src/Controllers/SharedController.php";

    $database = new Database();
    $db = $database->getConnection();
    $sharedController = new SharedController($db);

    // Handle any POST actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $sharedController->handleProfileUpdate($_POST, $_FILES);
    }

    // Get current data for display
    $data = $sharedController->profile();
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
    <a href="profile.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i>Back</a>

    <!-- View Main Content -->
    <main class="view-main">
        <div class="profile-info-container">
            <div class="profile-container">
                <h1>Edit Profile Info</h1>

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
                    <div class="photo-wrapper">

                        <img src="<?= htmlspecialchars($profile_picture); ?>" alt="Profile Picture" class="profile-avatar">

                        <div class="photo-overlay">
                            <form method="POST" enctype="multipart/form-data" class="photo-form">
                                <label for="profile-picture" class="photo-edit-icon">
                                    <i class="fa-solid fa-pen"></i>
                                </label>
                                <input type="file" id="profile-picture" name="profile_picture" accept="image/*" onchange="this.form.submit()">
                            </form>

                            <form method="POST" onsubmit="return confirm('Remove your profile picture?');">
                                <button type="submit" name="remove_picture" class="photo-remove-icon">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Profile Info Container Display -->
                <div class="profile-bot-container">
                    <!-- Edit Profile Info Form -->
                    <form method="POST">
                        <div class="edit-profile-input-box">
                            <label>Email</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" required>
                        </div>

                        <div class="edit-profile-input-box">
                            <label>Last Name</label>
                            <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']); ?>" required>
                        </div>

                        <div class="edit-profile-input-box">
                            <label>First Name</label>
                            <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']); ?>" required>
                        </div>

                        <div class="edit-profile-input-box">
                            <label>Middle Name (optional)</label>
                            <input type="text" name="middle_name" value="<?= htmlspecialchars($user['middle_name']); ?>">
                        </div>

                        <div class="edit-profile-input-box">
                            <label>Contact Number</label>
                            <input type="text" name="contact_number" value="<?= htmlspecialchars($user['contact_number']); ?>" required>
                        </div>

                        <div class="profile-bot-btn">
                            <!-- Update Profile Info Button -->
                            <button type="submit" name="update_info" class="btn-update-profile">Update Profile Info</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
