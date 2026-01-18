<?php
    session_start();
    require_once "../../config/database.php";
    require_once "../../app/classes/auth.php";

    // Initialize form variables
    $error = "";
    $email = "";

    // Redirect logged-in users
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        if ($_SESSION['role'] === 'admin') {
            header("Location: ../../resources/views/admin/admin_dashboard.php");
            exit;
        } elseif ($_SESSION['role'] === 'tasker') {
            header("Location: ../../resources/views/tasker/tasker_dashboard.php");
            exit;
        } elseif ($_SESSION['role'] === 'client') {
            header("Location: ../../resources/views/client/client_dashboard.php");
            exit;
        }
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $auth = new Auth($conn);
        $result = $auth->login($_POST['email'], $_POST['password']);
        $email = trim($_POST['email']);

        if ($result['success']) {
            $user = $result['user'];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: ../admin/admin_dashboard.php");
            } elseif ($user['role'] === 'tasker') {
                header("Location: ../tasker/tasker_dashboard.php");
            } elseif ($user['role'] === 'client') {
                header("Location: ../client/client_dashboard.php");
            }
            exit;
        } else {
            $error = $result['error'];
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include '../../resources/views/includes/web_logo.php'; ?>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../public/assets/style/style.css">
    <title>Log into ProHand</title>
</head>

<body class="login-page">

    <!-- Back button -->
    <a href="../../public/index.html" class="btn-back-auth">
        <i class="fa-solid fa-arrow-left"></i>
        Back
    </a>

    <main class="login-main">

        <section class="login-wrapper">
            <div class="login-container">

                <!-- Logo -->
                <div class="login-logo">
                    <img src="../../public/assets/img/ProHand(Adapt).png" alt="ProHand Logo" class="logo-img">
                    <h1>Login to Pro<span>Hand</span></h1>
                </div>

                <!-- Success / Error Message -->
                <div class="login-error-success-message">
                    <?php if (isset($_SESSION['success'])): ?>
                        <p class="success"><?= htmlspecialchars($_SESSION['success']); ?></p>
                        <?php unset($_SESSION['success']); ?>
                    <?php elseif (!empty($error)): ?>
                        <p class="error"><?= htmlspecialchars($error); ?></p>
                    <?php endif; ?>
                </div>

                <!-- Login Form -->
                <form method="POST">
                    <div class="login-input-box">
                        <label>Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
                    </div>

                    <div class="login-input-box">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>

                    <button type="submit" class="btn">Login</button>
                </form>

                <p class="register-link">
                    Don't have an account?
                    <a href="register.php">Register here</a>
                </p>

            </div>
        </section>

        <!-- Footer -->
        <footer class="index-footer">
            <p>&copy; 2025 ProHand. All rights reserved.</p>
        </footer>
    </main>
</body>
</html>