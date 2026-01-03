<?php
    require_once "../shared/db.php";

    // Ensure admin is logged in
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header("Location: ../auth/login.php");
        exit;
    }

    $admin_id = (int)$_SESSION['user_id'];

    // Fetch admin info
    $stmt = $conn->prepare("SELECT email, first_name, middle_name, last_name, profile_picture, role FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $admin_result = $stmt->get_result();
    $stmt->close();

    if ($admin_result->num_rows === 1) {
        $admin = $admin_result->fetch_assoc();
        $admin_full_name = trim($admin['first_name'] . ' ' . $admin['middle_name'] . ' ' . $admin['last_name']);

        // Profile picture path
        $profile_dir = '../uploads/profile_pictures/';

        // Determine which profile picture to use based on availability and file existence
        if (!empty($admin['profile_picture']) && file_exists($profile_dir . basename($admin['profile_picture']))) {
            $profile_picture = $profile_dir . basename($admin['profile_picture']);
        } else {
            $profile_picture = $profile_dir . 'default.jpg';
        }
    } else {
        session_unset();
        session_destroy();
        header("Location: ../auth/login.php");
        exit;
    }

    // Fetch unread notifications count
    $stmt = $conn->prepare("SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $notif_count_result = $stmt->get_result();
    $notif_count = 0;
    if ($notif_count_result->num_rows === 1) {
        $notif_count = $notif_count_result->fetch_assoc()['unread_count'];
    }
    $stmt->close();
?>

<div class="sidebar">
    <div class="sidebar-top">
        <div class="profile-info">
            <img src="<?= htmlspecialchars($profile_picture); ?>" alt="Profile Picture" class="profile-img">
            <div class="profile-text">
                <p class="role"><?= ucfirst(htmlspecialchars($admin['role'])); ?></p>
                <p class="name"><?= htmlspecialchars($admin_full_name); ?></p>
                <p class="email"><?= htmlspecialchars($admin['email']); ?></p>
            </div>
        </div>

        <ul class="sidebar-links">
            <li>
                <a href="../admin/admin_dashboard.php">
                    <i class="fa-solid fa-house"></i>
                    Dashboard
                </a>
            </li>

            <li>
                <a href="../admin/finished_errands.php">
                    <i class="fa-solid fa-clipboard-check"></i>
                    Finished Errands
                </a>
            </li>

            <li>
                <a href="../admin/manage_users.php">
                    <i class="fa-solid fa-users"></i>
                    Manage Users
                </a>
            </li>

            <li>
                <a href="../shared/profile.php">
                    <i class="fa-solid fa-circle-user"></i>
                    My Profile
                </a>
            </li>

            <li>
                <a href="../shared/notifications.php" class="notif-link">
                    <i class="fa-solid fa-bell"></i> Notifications
                    <?php if ($notif_count > 0): ?>
                        <span class="notif-badge">
                            <?= $notif_count > 99 ? '99+' : $notif_count ?>
                        </span>
                    <?php endif; ?>
                </a>
            </li>

            <li>
                <a href="../auth/logout.php">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    Logout
                </a>
            </li>
        </ul>
    </div>

    <div class="sidebar-bottom">
        <footer class="sidebar-footer">
            <p>&copy; 2025 ProHand. All rights reserved.</p>
        </footer>
    </div>
</div>
