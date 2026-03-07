<?php
    session_start();

    // 1. Manually pull the classes
    require_once "../../src/Classes/Database.php";
    require_once "../../src/Classes/Auth.php";
    require_once "../../src/Classes/User.php";

    // 2. Setup the "Battery" (Database)
    $database = new Database();
    $db = $database->getConnection();

    // 3. Setup the "Phone" and "Security Guard"
    $user = new User($db);
    $auth = new Auth($db);

    // 4. Use Auth to kick out already logged-in users
    $auth->redirectIfLoggedIn();

    // 5. Handle the Login attempt
    $email = "";
    $error = "";
    
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        $role = $user->login($email, $password);
        if ($role) {
            // Redirection logic is clean!
            header("Location: ../$role/{$role}_dashboard.php");
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include '../../includes/web_logo.php'; ?>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../public/assets/style/style.css">
    <title>Log into ProHand</title>
</head>
<body class="login-page">

    <a href="../../public/index.php" class="btn-back-auth">
        <i class="fa-solid fa-arrow-left"></i>
        Back
    </a>

    <main class="login-main">
        <section class="login-wrapper">
            <div class="login-container">

                <div class="login-logo">
                    <img src="../../public/assets/img/ProHand(Adapt).png" alt="ProHand Logo" class="logo-img">
                    <h1>Login to Pro<span>Hand</span></h1>
                </div>

                <div class="login-error-success-message">
                    <?php if (isset($_SESSION['success'])): ?>
                        <p class="success"><?= htmlspecialchars($_SESSION['success']); ?></p>
                        <?php unset($_SESSION['success']); ?>
                    <?php elseif (!empty($error)): ?>
                        <p class="error"><?= htmlspecialchars($error); ?></p>
                    <?php endif; ?>
                </div>

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

        <footer class="index-footer">
            <p>&copy; 2026 ProHand. All rights reserved.</p>
        </footer>
    </main>
</body>
</html>