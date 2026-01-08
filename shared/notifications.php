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

    // Mark all unread notifications as read
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    // Handle search input
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $search_int = is_numeric($search) ? (int)$search : 0;

    // Normalize search for general fields (remove commas)
    $search_normalized = str_replace(',', '', $search);
    $search_query = '%' . $search_normalized . '%';

    // Build query
    if (!empty($search)) {
        $stmt = $conn->prepare("
            SELECT *
            FROM notifications
            WHERE user_id = ?
            AND (
                type LIKE ?
                OR message LIKE ? 
                OR DATE_FORMAT(created_at, '%M %e %Y') LIKE ?
            )
            ORDER BY created_at DESC
        ");

        $stmt->bind_param(
            "isss",
            $user_id,            // Exact match for notification ID
            $search_query,       // Type LIKE
            $search_query,       // Message LIKE
            $search_query        // Date LIKE
        );
    } else {
        $stmt = $conn->prepare("
            SELECT *
            FROM notifications
            WHERE user_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->bind_param("i", $user_id);
    }

    $stmt->execute();
    $notif_result = $stmt->get_result();
    $stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include '../includes/web_logo.php'; ?>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css' rel='stylesheet'> 
    <link rel="stylesheet" href="../assets/style/style.css">
    <title>ProHand</title>
</head>
<body>
    <!-- Sidebar -->
    <?php include '../includes/sidebar.php'; ?>

    <!-- Main -->
    <main class="main">
        <!-- Fixed Wrapper -->
        <div class="fixed-wrapper">
            <!-- Header -->
            <header class="header"> 
                <div class="header-left">
                    <div class="logo">
                        <img src="../assets/img/ProHand(Adapt).png" alt="ProHand Logo" class="logo-img">

                        <div class="title">
                            <h1>Pro<span>Hand</span></h1>
                            <h2>Notifications</h2>
                        </div>
                    </div>
                </div>

                <div class="header-right">
                    <form class="search-form" method="GET">
                        <div class="search-wrapper">
                            <input type="text" name="search" placeholder="Search"
                                value="<?= htmlspecialchars($search ?? '') ?>">

                            <?php if (!empty($search)): ?>
                                <a href="notifications.php" class="clear">
                                    Clear
                                </a>
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

                        <form action="delete_notification.php" method="POST">
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
                                    
                                    <form method="POST" action="delete_notification.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this notification?');">
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
