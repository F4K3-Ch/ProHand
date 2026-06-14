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

    // Detect current file to handle highlights instantly
    $current_page = basename($_SERVER['SCRIPT_NAME']);

    // Extract and sanitize URL parameter context upfront
    $source = isset($_GET['from']) ? trim($_GET['from']) : '';
    $role = $_SESSION['role'] ?? '';

    // VIEW ERRAND INFO VIEW
    if ($current_page === 'view_errand_info.php') {
        
        // Check if we are dealing with a standard history context
        $is_history_type = false;
        if (isset($_GET['type']) && $_GET['type'] === 'history') {
            $is_history_type = true;
        }

        // Case A: Coming from Main Dashboards or missing direct context flags
        if ($source === 'dashboard' || (empty($source) && !$is_history_type)) {
            if ($role === 'admin') {
                $current_page = 'admin_dashboard.php';
            } elseif ($role === 'tasker') {
                $current_page = 'tasker_dashboard.php';
            } elseif ($role === 'client') {
                $current_page = 'client_dashboard.php';
            }
            
        // Case B: Coming from Ongoing Tasks view
        } elseif ($source === 'tasks' || $source === 'your_task') {
            $current_page = 'your_task.php';
            
        // Case C: Coming from Finished Errands view (hidden input "finished" or history URL token)
        } elseif ($source === 'finished' || $is_history_type) {
            if ($role === 'admin') {
                $current_page = 'finished_errands.php';
            } elseif ($role === 'tasker') {
                $current_page = 'tasker_finished_errands.php';
            } elseif ($role === 'client') {
                $current_page = 'client_finished_errands.php';
            }
            
        // Case D: Coming from direct Notifications
        } elseif ($source === 'notification') {
            $current_page = 'notifications.php';
        }
    } 
    // ADMIN VIEW USER INFO
    elseif ($current_page === 'view_user_info.php') {
        if ($source === 'notification') {
            $current_page = 'notifications.php';
        } else {
            $current_page = 'manage_users.php';
        }
    }
    // PROFILE PAGES
    elseif (in_array($current_page, ['edit_profile_info.php', 'change_password.php'])) {
        $current_page = 'profile.php';
    } 
    // CLIENT EDIT ERRAND
    elseif ($current_page === 'edit_errand.php') {
        if ($source === 'notification') {
            $current_page = 'notifications.php';
        } else {
            $current_page = 'client_dashboard.php';
        }
    }
?>

<div class="sidebar">
    <div class="sidebar-top">
        <div class="profile-info">
            <img src="<?= htmlspecialchars($sidebarData['profile_picture']); ?>" class="profile-img">
            <div class="profile-text">
                <p class="role"><?= htmlspecialchars(ucfirst($sidebarData['role'])); ?></p>
                <p class="name"><?= htmlspecialchars($sidebarData['full_name']); ?></p>
                <p class="email"><?= htmlspecialchars($sidebarData['user']['email']); ?></p>
            </div>
        </div>

        <ul class="sidebar-links">
            <?php 
                $dash_pages = ['admin_dashboard.php', 'client_dashboard.php', 'tasker_dashboard.php'];
                $dash_active = in_array($current_page, $dash_pages) ? 'active' : '';
            ?>
            <li>
                <a class="<?= $dash_active; ?>"
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
                    <a href="../admin/finished_errands.php" class="<?= $current_page === 'finished_errands.php' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-clipboard-check"></i> Finished Errands
                    </a>
                <?php elseif ($_SESSION['role'] === 'client'): ?>
                    <a href="../client/client_finished_errands.php" class="<?= $current_page === 'client_finished_errands.php' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-clipboard-check"></i> Finished Errands
                    </a>
                <?php elseif ($_SESSION['role'] === 'tasker'): ?>
                    <a href="../tasker/your_task.php" class="<?= $current_page === 'your_task.php' ? 'active' : ''; ?>"> 
                        <i class="fa-solid fa-clipboard-list"></i> Your Task
                    </a>
                <?php endif; ?>
            </li>

            <li>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="../admin/manage_users.php" class="<?= $current_page === 'manage_users.php' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-users"></i> Manage Users
                    </a>
                <?php elseif ($_SESSION['role'] === 'client'): ?>
                    <a href="../client/create_errand.php" class="<?= $current_page === 'create_errand.php' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-circle-plus"></i> Create Errand
                    </a>
                <?php elseif ($_SESSION['role'] === 'tasker'): ?>
                    <a href="../tasker/tasker_finished_errands.php" class="<?= $current_page === 'tasker_finished_errands.php' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-clipboard-check"></i> Finished Errands
                    </a>
                <?php endif; ?>
            </li>

            <li>
                <a href="../shared/profile.php" class="<?= $current_page === 'profile.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-circle-user"></i> My Profile
                </a>
            </li>

            <li>
                <a href="../shared/notifications.php" class="notif-link <?= $current_page === 'notifications.php' ? 'active' : ''; ?>">
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
