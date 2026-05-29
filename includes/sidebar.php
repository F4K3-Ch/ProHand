<?php
    // SharedController handles the logic for the LOGGED-IN user
    require_once "../../src/Classes/User.php";
    require_once "../../src/Controllers/SharedController.php";
    require_once "../../src/Classes/Auth.php";
    require_once "../../src/Controllers/AuthController.php";

    $database = new Database();
    $db = $database->getConnection();
    $sharedController = new SharedController($db);
    $authController = new AuthController($db);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout_action'])) {
        $authController->logout();
    }
    
    // Get the data, but DO NOT extract it
    $sidebarData = $sharedController->profile();
?>

<div class="sidebar">
    <div class="sidebar-top">
        <div class="profile-info">
            <!-- Access the array keys directly -->
            <img src="<?= htmlspecialchars($sidebarData['profile_picture']); ?>" class="profile-img">
            <div class="profile-text">
                <p class="role"><?= htmlspecialchars(ucfirst($sidebarData['role'])); ?></p>
                <p class="name"><?= htmlspecialchars($sidebarData['full_name']); ?></p>
                <p class="email"><?= htmlspecialchars($sidebarData['user']['email']); ?></p>
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
                    <?php $notifCount = $sidebarData['notif_count'] ?? 0; ?>
                    <?php if ($notifCount > 0): ?>
                        <span class="notif-badge">
                            <?= $notifCount > 99 ? '99+' : $notifCount ?>
                        </span>
                    <?php endif; ?>
                </a>
            </li>

            <li>
                <form method="POST">
                    <button type="submit" name="logout_action">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </button>
                </form>
            </li>
        </ul>
    </div>

    <div class="sidebar-bottom">
        <footer class="sidebar-footer">
            <p>&copy; 2025 ProHand. All rights reserved.</p>
        </footer>
    </div>
</div>
