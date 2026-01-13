<?php
    session_start();
    require_once "../../config/database.php";
    require_once "../../app/classes/auth.php";

    // Initialize form variables
    $error = "";
    $email = "";
    $first_name = "";
    $middle_name = "";
    $last_name = "";
    $contact_number = "";

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

    if ($_SERVER['REQUEST_METHOD'] === "POST") {
        $auth = new Auth($conn);
        $data = [
            'email' => $_POST['email'],
            'first_name' => $_POST['first_name'],
            'middle_name' => $_POST['middle_name'],
            'last_name' => $_POST['last_name'],
            'contact_number' => $_POST['contact_number'],
            'password' => $_POST['password'],
            'confirm_password' => $_POST['confirm_password']
        ];
        $result = $auth->register($data);

        $email = trim($_POST['email']);
        $first_name = trim($_POST['first_name']);
        $middle_name = trim($_POST['middle_name']);
        $last_name = trim($_POST['last_name']);
        $contact_number = trim($_POST['contact_number']);

        if ($result['success']) {
            $_SESSION['success'] = "Registration successful! You can login now.";
            header("Location: login.php");
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
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../public/assets/style/style.css">
    <title>Register for ProHand</title>
</head>
<body class="register-page">

    <!-- Back button -->
    <a href="../../public/index.html" class="btn-back-auth">
        <i class="fa-solid fa-arrow-left"></i> 
        Back
    </a>

    <div class="register-container">
        <h1>Register Here</h1>

        <!-- Error Message -->
        <div class="register-error-success-message">
            <?php if (!empty($error)): ?>
                <p class="error"><?= htmlspecialchars($error); ?></p>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
        </div>

        <form method="POST">
            <div class="register-input-box">
                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($email); ?>" required>
            </div>

            <div class="register-input-box">
                <label>Last Name</label>
                <input type="text" name="last_name" value="<?= htmlspecialchars($last_name); ?>" required>
            </div>

            <div class="register-input-box">
                <label>First Name</label>
                <input type="text" name="first_name" value="<?= htmlspecialchars($first_name); ?>" required>
            </div>

            <div class="register-input-box">
                <label>Middle Name (optional)</label>
                <input type="text" name="middle_name" value="<?= htmlspecialchars($middle_name); ?>">
            </div>

            <div class="register-input-box">
                <label>Contact Number</label>
                <input type="text" name="contact_number" value="<?= htmlspecialchars($contact_number); ?>" required>
            </div>

            <div class="register-input-box">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>

            <div class="register-input-box">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required>
            </div>

            <button type="submit" class="btn">Register</button>
        </form>

        <p class="login-link">Already have an account?
            <a href="login.php">Login here</a>
        </p>
    </div>
</body>
</html>

