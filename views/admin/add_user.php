<?php
    session_start();
    
    require_once "../../src/Classes/Database.php";
    require_once "../../src/Classes/User.php";
    require_once "../../src/Classes/Auth.php";
    require_once "../../src/Classes/Admin.php";
    require_once "../../src/Controllers/AdminController.php";

    $database = new Database();
    $db = $database->getConnection();
    
    $adminController = new AdminController($db);

    $data = $adminController->handleAddUser();
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
    <?php include '../../includes/sidebar.php'; ?>

    <a href="manage_users.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i>Back</a>

    <main class="view-main">
        <div class="add-user-container">
            <h1>Add a User</h1>

            <div class="add-user-error-success-message">
                <?php if (isset($_SESSION['success']) || isset($_SESSION['error'])): ?>
                    <div class="error-success-message">
                        <?php if (isset($_SESSION['success'])): ?>
                            <p class="success"><?= htmlspecialchars($_SESSION['success']); ?></p>
                            <?php unset($_SESSION['success']); ?>
                        <?php elseif (isset($_SESSION['error'])): ?>
                            <p class="error"><?= htmlspecialchars($_SESSION['error']); ?></p>
                            <?php unset($_SESSION['error']); ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <form method="POST">
                <div class="add-user-input-box">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($fields['email']); ?>" required>
                </div>

                <div class="add-user-input-box">
                    <label>Last Name</label>
                    <input type="text" name="last_name" value="<?= htmlspecialchars($fields['last_name']); ?>" required>
                </div>

                <div class="add-user-input-box">
                    <label>First Name</label>
                    <input type="text" name="first_name" value="<?= htmlspecialchars($fields['first_name']); ?>" required>
                </div>

                <div class="add-user-input-box">
                    <label>Middle Name (optional)</label>
                    <input type="text" name="middle_name" value="<?= htmlspecialchars($fields['middle_name']); ?>">
                </div>

                <div class="add-user-input-box">
                    <label>Contact Number</label>
                    <input type="text" name="contact_number" value="<?= htmlspecialchars($fields['contact_number']); ?>" required>
                </div>

                <div class="add-user-input-box">
                    <label>Role</label>
                    <select name="role" required>
                        <option value="">Select Role</option>
                        <option value="admin" <?= $fields['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="tasker" <?= $fields['role'] === 'tasker' ? 'selected' : '' ?>>Tasker</option>
                        <option value="client" <?= $fields['role'] === 'client' ? 'selected' : '' ?>>Client</option>
                    </select>
                </div>

                <div class="add-user-input-box">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>

                <div class="add-user-input-box"> 
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" required>
                </div>

                <button type="submit" class="btn">Register</button>
            </form>
        </div>
    </main>
</body>
</html>