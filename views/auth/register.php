<?php
    session_start();
    require_once "../../src/Classes/Database.php";
    require_once "../../src/Classes/User.php";
    require_once "../../src/Classes/Auth.php";
    require_once "../../src/Controllers/AuthController.php";

    $db = (new Database())->getConnection();
    $controller = new AuthController($db);
    $res = $controller->register();

    $error = $res['error'];
    $f = $res['fields']; // Use these in your value="<?= htmlspecialchars($f['email'])"
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
                <input type="email" name="email" value="<?= htmlspecialchars($f['email']); ?>" required>
            </div>

            <div class="register-input-box">
                <label>Last Name</label>
                <input type="text" name="last_name" value="<?= htmlspecialchars($f['last_name']); ?>" required>
            </div>

            <div class="register-input-box">
                <label>First Name</label>
                <input type="text" name="first_name" value="<?= htmlspecialchars($f['first_name']); ?>" required>
            </div>

            <div class="register-input-box">
                <label>Middle Name (optional)</label>
                <input type="text" name="middle_name" value="<?= htmlspecialchars($f['middle_name']); ?>">
            </div>

            <div class="register-input-box">
                <label>Contact Number</label>
                <input type="text" name="contact_number" value="<?= htmlspecialchars($f['contact_number']); ?>" required>
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