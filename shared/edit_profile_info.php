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

    // Fetch user info
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Determine profile picture path
        $profile_dir = '../uploads/profile_pictures/';
        $profile_picture = $profile_dir . 'default.jpg';

        if (!empty($user['profile_picture']) && file_exists($profile_dir . basename($user['profile_picture']))) {
            $profile_picture = $profile_dir . basename($user['profile_picture']);
        }
    } else {
        $_SESSION['error'] = "User not found. Please login.";
        header("Location: ../auth/login.php");
        exit;
    }

    // Edit Profile Picture
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // Remove profile picture
        if (isset($_POST['remove_picture'])) {
            if (!empty($user['profile_picture']) && basename($user['profile_picture']) !== "default.jpg") {
                $old_file = $profile_dir . basename($user['profile_picture']);
                if (file_exists($old_file)) unlink($old_file);
            }

            $db_default_path = '/uploads/profile_pictures/default.jpg';
            $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
            $stmt->bind_param("si", $db_default_path, $user_id);

            if ($stmt->execute()) {
                $stmt->close();
                $_SESSION['success'] = "Profile picture removed successfully.";
                header("Location: edit_profile_info.php");
                exit;
            } else {
                $_SESSION['error'] = "Failed to remove profile picture.";
                header("Location: edit_profile_info.php");
                exit;
            }

        // Upload new profile picture
        } elseif (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['profile_picture']['tmp_name'];
                $file_size = $_FILES['profile_picture']['size'];
                $file_name = $_FILES['profile_picture']['name'];

                //Validate image using getimagesize()
                $image_info = getimagesize($file_tmp);
                if ($image_info === false) {
                    $_SESSION['error'] = "Upload file is not a valid image.";
                    header("Location: edit_profile_info.php");
                    exit;
                } else {
                    $mime_type = $image_info['mime'];
                    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
                    $max_size = 2 * 1024 * 1024; // 2MB

                    if (!in_array($mime_type, $allowed_types)) {
                        $_SESSION['error'] = "Only JPEG, PNG, and JPG images are allowed.";
                        header("Location: edit_profile_info.php");
                        exit;
                    } elseif ($file_size > $max_size) {
                        $_SESSION['error'] = "File is too large. Maximum size is 2MB.";
                        header("Location: edit_profile_info.php");
                        exit;
                    } else {
                        $extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        $new_file_name = 'user_' . $user_id . '_' . time() . '.' . $extension;
                        $destination = $profile_dir . $new_file_name;

                        if (move_uploaded_file($file_tmp, $destination)) {
                        $profile_picture_path = '/uploads/profile_pictures/' . $new_file_name;

                            // Update user profile_picture in DB
                            $update_stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                            $update_stmt->bind_param("si", $profile_picture_path, $user_id);
                            if ($update_stmt->execute()) {
                                $update_stmt->close();

                                // Delete old picture if not default
                                if (!empty($user['profile_picture']) && basename($user['profile_picture']) !== "default.jpg") {
                                    $old_file = $profile_dir . basename($user['profile_picture']);
                                    if (file_exists($old_file)) unlink($old_file);
                                }

                                $_SESSION['success'] = "Profile picture updated successfully.";
                                header("Location: edit_profile_info.php");
                                exit;
                            } else {
                                // Rollback: delete newly uploaded file if DB update fails
                                if (file_exists($destination)) unlink($destination);
                                $_SESSION['error'] = "Failed to update database. Upload canceled.";
                                header("Location: edit_profile_info.php");
                                exit;
                            }

                        } else {
                            $_SESSION['error'] = "Failed to upload file. Please try again.";
                            header("Location: edit_profile_info.php");
                            exit;
                        }
                    }
                }
        }
    }

    // Edit Info Details / Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === "POST") {
        $email = trim($_POST['email'] ?? '');
        $first_name = trim($_POST['first_name'] ?? '');
        $middle_name = trim($_POST['middle_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $contact_number = trim($_POST['contact_number'] ?? '');

        // Email format validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Invalid email format.";
            header("Location: edit_profile_info.php");
            exit;
        } else {
            // Check duplicate email 
            $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
            $check_stmt->bind_param("si", $email, $user_id);
            $check_stmt->execute();
            $check_stmt->store_result();

            if ($check_stmt->num_rows > 0) {
                $_SESSION['error'] = "This email is already registered to another account.";
                header("Location: edit_profile_info.php");
                exit;
            }
            $check_stmt->close();
        }

        $update_stmt = $conn->prepare("
            UPDATE users 
            SET 
                email=?, 
                first_name=?, 
                middle_name=?, 
                last_name=?, 
                contact_number=? 
            WHERE id=?
        ");
        $update_stmt->bind_param(
            "sssssi", 
            $email, 
            $first_name, 
            $middle_name, 
            $last_name, 
            $contact_number, 
            $user_id
        );
        if ($update_stmt->execute()) {
            $update_stmt->close();
            $_SESSION['success'] = "Profile information updated successfully.";
            header("Location: profile.php");
            exit;
        } else {
            $_SESSION['error'] = "Failed to update profile. Please try again.";
            header("Location: profile.php");
            exit;
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
    <link rel="stylesheet" href="../assets/style/style.css?v=<?= filemtime('../assets/style/style.css'); ?>">
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
        <div class="profile-info-container">
            <div class="profile-container">
                <h1>Edit Profile Info</h1>

                <!-- Success/Error Message -->
                <div class="view-main-error-success-message">
                    <?php if (isset($_SESSION['success'])): ?>
                        <p class="success"><?= htmlspecialchars($_SESSION['success']); ?></p>
                        <?php unset($_SESSION['success']); ?>
                    <?php elseif (isset($_SESSION['error'])): ?>
                        <p class="error"><?= htmlspecialchars($_SESSION['error']); ?></p>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>
                </div>

                <!-- Photo Section -->
                <div class="profile-photo-section">
                    <div class="photo-wrapper">

                        <img src="<?= htmlspecialchars($profile_picture); ?>" alt="Profile Picture" class="profile-avatar">

                        <div class="photo-overlay">
                            <form method="POST" enctype="multipart/form-data" class="photo-form">
                                <label for="profile-picture" class="photo-edit-icon">
                                    <i class="fa-solid fa-pen"></i>
                                </label>
                                <input type="file" id="profile-picture" name="profile_picture" accept="image/*" onchange="this.form.submit()">
                            </form>

                            <form method="POST" onsubmit="return confirm('Remove your profile picture?');">
                                <button type="submit" name="remove_picture" class="photo-remove-icon">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Profile Info Container Display -->
                <div class="profile-bot-container">
                    <!-- Edit Profile Info Form -->
                    <form method="POST">
                        <div class="edit-profile-input-box">
                            <label>Email</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" required>
                        </div>

                        <div class="edit-profile-input-box">
                            <label>Last Name</label>
                            <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']); ?>" required>
                        </div>

                        <div class="edit-profile-input-box">
                            <label>First Name</label>
                            <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']); ?>" required>
                        </div>

                        <div class="edit-profile-input-box">
                            <label>Middle Name (optional)</label>
                            <input type="text" name="middle_name" value="<?= htmlspecialchars($user['middle_name']); ?>">
                        </div>

                        <div class="edit-profile-input-box">
                            <label>Contact Number</label>
                            <input type="text" name="contact_number" value="<?= htmlspecialchars($user['contact_number']); ?>" required>
                        </div>

                        <div class="profile-bot-btn">
                            <!-- Update Profile Info Button -->
                            <button type="submit" class="btn-update-profile">Update Profile Info</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
