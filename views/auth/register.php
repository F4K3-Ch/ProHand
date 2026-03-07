<?php
    session_start();

    // 1. Manually pull the classes 
    require_once "../../src/Classes/Database.php";
    require_once "../../src/Classes/Auth.php";
    require_once "../../src/Classes/User.php";

    // 2. Initialize the objects
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);
    $auth = new Auth($db);

    // 3. Security: If the user is already logged in, send them to their dashboard
    $auth->redirectIfLoggedIn();

    // 4. Initialize form variables for the HTML
    $error = "";
    $email = "";
    $first_name = "";
    $middle_name = "";
    $last_name = "";
    $contact_number = "";

    if ($_SERVER['REQUEST_METHOD'] === "POST") {
        // Capture and trim data
        $data = [
            'email'          => trim($_POST['email']),
            'first_name'     => trim($_POST['first_name']),
            'middle_name'    => trim($_POST['middle_name']),
            'last_name'      => trim($_POST['last_name']),
            'contact_number' => trim($_POST['contact_number']),
            'password'       => $_POST['password']
        ];
        
        $confirm_password = $_POST['confirm_password'];

        // Basic Validation
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } elseif ($data['password'] !== $confirm_password) {
            $error = "Passwords do not match!";
        }

        if (empty($error)) {
            // Call the register method from our User class
            $result = $user->register($data);

            if ($result === true) {
                $_SESSION['success'] = "Registration successful! You can login now.";
                header("Location: login.php");
                exit;
            } else {
                // $result will contain the error message (like "Email already registered")
                $error = $result;
            }
        }
        
        // Keep the input values so the user doesn't have to re-type them on error
        $email = $data['email'];
        $first_name = $data['first_name'];
        $middle_name = $data['middle_name'];
        $last_name = $data['last_name'];
        $contact_number = $data['contact_number'];
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include '../../includes/web_logo.php'; ?>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css' rel='stylesheet'> 
    <link rel="stylesheet" href="../../public/assets/style/style.css">
    <title>Register for ProHand</title>
</head>
<body class="register-page">

    <a href="../../public/index.php" class="btn-back-auth">
        <i class="fa-solid fa-arrow-left"></i> 
        Back
    </a>

    <div class="register-container">
        <h1>Register Here</h1>

        <div class="register-error-success-message">
            <?php if (!empty($error)): ?>
                <p class="error"><?= htmlspecialchars($error); ?></p>
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