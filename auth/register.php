<?php
    session_start();
    require_once "../shared/db.php";

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
            header("Location: ../admin/admin_dashboard.php");
            exit;
        } elseif ($_SESSION['role'] === 'tasker') {
            header("Location: ../tasker/tasker_dashboard.php");
            exit;
        } elseif ($_SESSION['role'] === 'client') {
            header("Location: ../client/client_dashboard.php");
            exit;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === "POST") {
        $email = trim($_POST['email']);
        $first_name = trim($_POST['first_name']);
        $middle_name = trim($_POST['middle_name']);
        $last_name = trim($_POST['last_name']);
        $contact_number = trim($_POST['contact_number']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Validate email and password
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match!";
        }

        // Check for duplicate email
        if (empty($error)) {
            $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            $check_stmt->store_result();
            if ($check_stmt->num_rows > 0) {
                $error = "Email is already registered!";
            }
            $check_stmt->close();
        }

        // Insert user with default profile picture
        if (empty($error)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = "client"; // Default role: client
            // Default profile picture DB path
            $default_profile_picture = '/uploads/profile_pictures/default.jpg';

            $insert_stmt = $conn->prepare("
                INSERT INTO users (
                    email, first_name, middle_name, last_name, password, 
                    profile_picture, contact_number, role
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $insert_stmt->bind_param(
                "ssssssss",
                $email,
                $first_name,
                $middle_name,
                $last_name,
                $hashed_password,
                $default_profile_picture,
                $contact_number,
                $role
            );

            if ($insert_stmt->execute()) {
                $insert_stmt->close();
                $_SESSION['success'] = "Registration successful! You can login now.";
                header("Location: login.php");
                exit;
            } else {
                $error = "Error: Could not complete registration.";
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
    <title>Register for ProHand</title>
</head>
<body class="register-page">

    <!-- Back button -->
    <a href="../index.html" class="btn-back-login">
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

