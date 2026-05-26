<?php
    session_start();
    require_once "../../src/Classes/Database.php";
    require_once "../../src/Classes/User.php";
    require_once "../../src/Classes/Auth.php";
    require_once "../../src/Controllers/SharedController.php";

    $database = new Database();
    $db = $database->getConnection();
    $sharedController = new SharedController($db);

    // Handle POST action
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password_btn'])) {
        $sharedController->handleChangePassword($_POST);
    }

    // Get current data for Sidebar/Layout
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
        <div class="change-password-container">
            <form method="POST">
                <h1>Change Password</h1>

                <!-- Success/Error Message -->
                <div class="change-password-error-success-message">
                    <?php if (isset($_SESSION['success'])): ?>
                        <p class="success"><?= htmlspecialchars($_SESSION['success']); ?></p>
                        <?php unset($_SESSION['success']); ?>
                    <?php elseif (isset($_SESSION['error'])): ?>
                        <p class="error"><?= htmlspecialchars($_SESSION['error']); ?></p>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>
                </div>

                <div class="change-password-box">
                    <label>Enter Current Password</label>
                    <input type="password" name="current_password">
                </div>

                <div class="change-password-box">
                    <label>Enter New Password</label>
                    <input type="password" name="new_password">
                </div>

                <div class="change-password-box">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password">
                </div>

                <button type="submit" name="change_password_btn" class="btn">Change password</button>
            </form>
        </div>
    </main>
</body>
</html>
