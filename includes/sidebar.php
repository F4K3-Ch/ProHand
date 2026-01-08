<?php
    require_once "../shared/db.php";

    // Ensure the user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        header("Location: ../auth/login.php");
        exit;
    }

    $user_id = (int)$_SESSION['user_id'];
    $role = $_SESSION['role'];

    // Fetch user info
    $stmt = $conn->prepare("SELECT email, first_name, middle_name, last_name, profile_picture FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if ($result->num_rows === 1) {
        $sidebar_user = $result->fetch_assoc();
        $sidebar_full_name = trim($sidebar_user['first_name'] . ' ' . $sidebar_user['middle_name'] . ' ' . $sidebar_user['last_name']);

        // Profile picture path
        $profile_dir = '../uploads/profile_pictures/';

        // Determine which profile picture to use based on availability and file existence
        if (!empty($sidebar_user['profile_picture']) && file_exists($profile_dir . basename($sidebar_user['profile_picture']))) {
            $sidebar_profile_picture = $profile_dir . basename($sidebar_user['profile_picture']);
        } else {
            $sidebar_profile_picture = $profile_dir . 'default.jpg';
        }
    } else {
        session_unset();
        session_destroy();
        header("Location: ../auth/login.php");
        exit;
    }

    // Fetch unread notifications count
    $stmt = $conn->prepare("SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
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
            <img src="<?= htmlspecialchars($sidebar_profile_picture); ?>" alt="Profile Picture" class="profile-img">
            <div class="profile-text">
                <p class="role"><?= ucfirst(htmlspecialchars($role)); ?></p>
                <p class="name"><?= htmlspecialchars($sidebar_full_name); ?></p>
                <p class="email"><?= htmlspecialchars($sidebar_user['email']); ?></p>
            </div>
        </div>

        <ul class="sidebar-links">
            <li>
                <a
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        href="../admin/admin_dashboard.php"
                    <?php elseif ($_SESSION['role'] === 'client'): ?>
                        href="../client/client_dashboard.php"
                    <?php elseif ($_SESSION['role'] === 'tasker'): ?>
                        href="../tasker/tasker_dashboard.php"
                    <?php endif; ?>
                >
                    <i class="fa-solid fa-house"></i>
                    Dashboard
                </a>
            </li>


            <li>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="../admin/finished_errands.php">
                        <i class="fa-solid fa-clipboard-check"></i>
                        Finished Errands
                    </a>
                <?php elseif ($_SESSION['role'] === 'client'): ?>
                    <a href="../client/client_finished_errands.php">
                        <i class="fa-solid fa-clipboard-check"></i>
                        Finished Errands
                    </a>
                <?php elseif ($_SESSION['role'] === 'tasker'): ?>
                    <a href="../tasker/your_task.php"> 
                        <i class="fa-solid fa-clipboard-list"></i>
                        Your Task
                    </a>
                <?php endif; ?>
            </li>

            <li>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="../admin/manage_users.php">
                        <i class="fa-solid fa-users"></i>
                        Manage Users
                    </a>
                <?php elseif ($_SESSION['role'] === 'client'): ?>
                    <a href="../client/create_errand.php">
                        <i class="fa-solid fa-circle-plus"></i>
                        Create Errand
                    </a>
                <?php elseif ($_SESSION['role'] === 'tasker'): ?>
                    <a href="../tasker/tasker_finished_errands.php">
                        <i class="fa-solid fa-clipboard-check"></i>
                        Finished Errands
                    </a>
                <?php endif; ?>
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
