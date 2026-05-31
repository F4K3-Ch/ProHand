<?php
    session_start();
    require_once "../../src/Classes/Database.php";
    require_once "../../src/Classes/User.php";
    require_once "../../src/Classes/Auth.php";
    require_once "../../src/Controllers/SharedController.php";

    $database = new Database();
    $db = $database->getConnection();
    $sharedController = new SharedController($db);

    // --- NEW: Handle Deletion Logic Here ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['notification_id']) || isset($_POST['delete_all'])) {
            $sharedController->handleDeleteNotification($_POST);
            // The controller already has a header("Location: notifications.php") 
            // and exit; inside it, so it will refresh the page automatically.
        }
    }

    // Existing data fetching
    $data = $sharedController->notifications();
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
    <!-- Sidebar -->
    <input type="checkbox" id="sidebar-toggle-check" class="sidebar-checkbox">
    <?php include '../../includes/sidebar.php'; ?>

    <!-- Main -->
    <main class="main">
        <!-- Fixed Wrapper -->
        <div class="fixed-wrapper">
            <header class="header"> 
                <div class="header-left">
                    <div class="logo">
                        <img src="../../public/assets/img/ProHand(Adapt).png" alt="ProHand Logo" class="logo-img">
                        <div class="title">
                            <h1>Pro<span>Hand</span></h1>
                            <h2>Notifications</h2>
                        </div>

                        <label for="sidebar-toggle-check" class="sidebar-toggle-btn" aria-label="Toggle Navigation Menu">
                            <i class="fa-solid fa-bars"></i>
                        </label>
                    </div>
                </div>

                <div class="header-right">
                    <form class="search-form" method="GET">
                        <div class="search-wrapper">
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search">
                            <?php if (!empty($search)): ?>
                                <a href="notifications.php" class="clear">Clear</a>
                            <?php endif; ?>
                        </div>
                        <button type="submit" class="btn-search">Search</button>
                    </form>
                </div>
            </header>
        </div>

        <!-- Notification -->
        <div class="notifications">
            <!-- Success/Error Message -->
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

            <!-- Notification Contents -->
            <div class="notifications-contents">
                <?php if ($notif_result->num_rows > 0): ?>
                    <div class="notif-contents-header">
                        <h3>Notifications</h3>

                        <form method="POST">
                            <button type="submit" name="delete_all" class="btn-delete-all" onclick="return confirm('Are you sure you want to delete all notifications?')">
                                Delete All Notifications
                            </button>
                        </form>
                    </div>

                    <ul class="notifications-list">
                        <?php while ($notif = $notif_result->fetch_assoc()): ?>
                            <li class="notification-card">
                                <div class="notif-message">
                                    <h3><?= ucfirst($notif['type']); ?></h3>
                                    <p><?= nl2br(htmlspecialchars($notif['message'])); ?></p>
                                    <small><?= date('F j, Y g:i A', strtotime($notif['created_at'])); ?></small>
                                </div>

                                <div class="notification-buttons">
                                    <?php if (!empty($notif['link'])): ?>
                                        <?php
                                            // Start with the original notification link
                                            $link = $notif['link'];

                                            // Check if the link already has a query string
                                            if (strpos($link, '?') !== false) {
                                                // Already has query parameters, append using &
                                                $link = $link . '&from=notification';
                                            } else {
                                                // No query parameters, start query string with ?
                                                $link = $link . '?from=notification';
                                            }
                                        ?>
                                        <a href="<?= htmlspecialchars($link); ?>" class="btn-view">View</a>
                                    <?php endif; ?>
                                    
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this notification?');">
                                        <input type="hidden" name="notification_id" value="<?= $notif['id']; ?>">
                                        <button type="submit" class="btn-delete">Delete</button>
                                    </form>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p class="no-found-notif">No notifications found.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
