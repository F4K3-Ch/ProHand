<?php
    session_start();
    require_once "db.php";

    // Ensure the user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        header("Location: ../auth/login.php");
        exit;
    }

    $user_id = (int)$_SESSION['user_id'];
    $role = $_SESSION['role'];

    // Handle password change
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Fetch current password 
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $_SESSION['error'] = "Account not found.";
            header("Location: ../auth/login.php");
            exit;
        } elseif (!password_verify($current_password, $user['password'])) {
            $_SESSION['error'] = "Current password is incorrect.";
            header("Location: change_password.php");
            exit;
        } elseif ($new_password !== $confirm_password) {
            $_SESSION['error'] = "New password and confirm password do not match.";
            header("Location: change_password.php");
            exit;
        } else {
            // Update password 
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $hashed_password, $user_id);

            if ($update_stmt->execute()) {
                $update_stmt->close();
                $_SESSION['success'] = "Password changed successfully.";
                header("Location: profile.php");
                exit;
            } else {
                $_SESSION['error'] = "Failed to update password. Please try again.";
                header("Location: profile.php");
                exit;
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
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css' rel='stylesheet'> 
    <link rel="stylesheet" href="../assets/style/style.css">
    <title>ProHand</title>
</head>
<body>
    <!-- Sidebar -->
    <?php
        if ($role === 'admin') {
            include '../includes/admin_sidebar.php';
        } elseif ($role === 'client') {
            include '../includes/client_sidebar.php';
        } elseif ($role === 'tasker') {
            include '../includes/tasker_sidebar.php';
        } else {
            header("Location: ../auth/login.php");
            exit;
        }
    ?>

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

                <button type="submit" class="btn">Change password</button>
            </form>
        </div>
    </main>
</body>
</html>
