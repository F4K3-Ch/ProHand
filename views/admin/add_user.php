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
    $role = "";

    // Only admin can access
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header("Location: ../auth/login.php");
        exit;
    }

    $admin_id = (int)$_SESSION['user_id'];

    // Fetch admin info
    $stmt = $conn->prepare("SELECT first_name, middle_name, last_name FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $admin_result = $stmt->get_result();
    $stmt->close();

    if ($admin_result->num_rows === 1) {
        $admin = $admin_result->fetch_assoc();
        $admin_full_name = trim($admin['first_name'] . ' ' . $admin['middle_name'] . ' ' . $admin['last_name']);
    } else {
        session_unset();
        session_destroy();
        header("Location: ../auth/login.php");
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === "POST") {
        $email = trim($_POST['email']);
        $first_name = trim($_POST['first_name']);
        $middle_name = trim($_POST['middle_name']);
        $last_name = trim($_POST['last_name']);
        $contact_number = trim($_POST['contact_number']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $role = $_POST['role'];

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

            // Default profile picture DB path
            $default_profile_picture = 'uploads/profile_pictures/default.jpg';

            $insert_stmt = $conn->prepare("
                INSERT INTO users (
                    email, first_name, middle_name, last_name, password, profile_picture, contact_number, role
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
                $user_id = $insert_stmt->insert_id;
                $insert_stmt->close();

                // Fetch newly created user info
                $stmt = $conn->prepare("SELECT first_name, middle_name, last_name FROM users WHERE id = ? LIMIT 1");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $user_result = $stmt->get_result();
                $stmt->close();

                if ($user_result->num_rows === 1) {
                    $user = $user_result->fetch_assoc();
                    $user_full_name = trim($user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name']);
                }

                // Fetch other admins info
                $stmt = $conn->prepare("SELECT id FROM users WHERE role = 'admin' AND id NOT IN (?, ?)");
                $stmt->bind_param("ii", $admin_id, $user_id);
                $stmt->execute();
                $other_admins_result = $stmt->get_result();
                $stmt->close();

                // Notify other admins
                while ($other_admin = $other_admins_result->fetch_assoc()) {
                    $other_admin_id = (int)$other_admin['id'];

                    // Notification content
                    $notif_type = "system";
                    $notif_message = "New User Added | User: \"{$user_full_name}\" (ID: {$user_id}) | Role: \"" . ucfirst($role)  . "\" | Added by Admin: \"{$admin_full_name}\" (ID: {$admin_id})";
                    $notif_link = "../admin/view_user_info.php?id={$user_id}"; // link to user details

                    // Insert into notifications table
                    $notif_stmt = $conn->prepare("
                        INSERT INTO notifications (user_id, type, message, link)
                        VALUES (?, ?, ?, ?)
                    ");
                    $notif_stmt->bind_param("isss", $other_admin_id, $notif_type, $notif_message, $notif_link);
                    $notif_stmt->execute();
                    $notif_stmt->close();
                }

                $_SESSION['success'] = "Successfully Added User!";
                header("Location: view_user_info.php?id={$user_id}");
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
    <?php include '../../includes/web_logo.php'; ?>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css' rel='stylesheet'> 
    <link rel="stylesheet" href="../../public/assets/style/style.css">
    <title>ProHand</title>
</head>
<body>
    <!-- Sidebar -->
    <?php include '../../includes/sidebar.php'; ?>

    <!-- Back button -->
    <a href="manage_users.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i>Back</a>

    <!-- View Main Content -->
    <main class="view-main">
        <div class="add-user-container">
            <h1>Add a User</h1>

            <!-- Error Message -->
            <div class="add-user-error-success-message">
                <?php if (!empty($error)): ?>
                    <p class="error"><?= htmlspecialchars($error); ?></p>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
            </div>

            <form method="POST">
                <div class="add-user-input-box">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($email); ?>" required>
                </div>

                <div class="add-user-input-box">
                    <label>Last Name</label>
                    <input type="text" name="last_name" value="<?= htmlspecialchars($last_name); ?>" required>
                </div>

                <div class="add-user-input-box">
                    <label>First Name</label>
                    <input type="text" name="first_name" value="<?= htmlspecialchars($first_name); ?>" required>
                </div>

                <div class="add-user-input-box">
                    <label>Middle Name (optional)</label>
                    <input type="text" name="middle_name" value="<?= htmlspecialchars($middle_name); ?>">
                </div>

                <div class="add-user-input-box">
                    <label>Contact Number</label>
                    <input type="text" name="contact_number" value="<?= htmlspecialchars($contact_number); ?>" required>
                </div>

                <div class="add-user-input-box">
                    <label>Role</label>
                    <select name="role" required>
                        <option value="">Select Role</option>
                        <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="tasker" <?= $role === 'tasker' ? 'selected' : '' ?>>Tasker</option>
                        <option value="client" <?= $role === 'client' ? 'selected' : '' ?>>Client</option>
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
