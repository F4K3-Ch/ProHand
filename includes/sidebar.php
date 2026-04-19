<?php
    // 1. Check session safely
    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

    // 2. Fetch data using the User Object
    $sidebar_user = $user_class->getUserInfo($user_id);

    // 3. Prepare display variables (Fixes the Deprecated NULL errors)
    if ($sidebar_user) {
        $role = $sidebar_user['role'] ?? 'User';

        $first = $sidebar_user['first_name'] ?? '';
        $middle = $sidebar_user['middle_name'] ?? '';
        $last = $sidebar_user['last_name'] ?? '';
        $sidebar_full_name = trim("$first $middle $last");
        
        $profile_dir = '../../public/uploads/profile_pictures/';
        $db_pic = $sidebar_user['profile_picture'] ?? '';
        $profile_pic = !empty($db_pic) ? basename($db_pic) : 'default.jpg';
        $sidebar_profile_picture = $profile_dir . $profile_pic;
    } else {
        $sidebar_full_name = "User";
        $sidebar_profile_picture = "../../public/uploads/profile_pictures/default.jpg";
    }

    $notif_count = 0; 
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
