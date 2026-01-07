<?php
    session_start();
    require_once "../shared/db.php";

    // Ensure only tasker can access
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tasker') {
        header("Location: ../auth/login.php");
        exit;
    }

    $tasker_id = (int)$_SESSION['user_id'];

    // Status Labels
    $status_labels = array(
        'pending' => 'Pending'
    );

    // Handle search input
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $search_int = is_numeric($search) ? (int)$search : 0;

    // Normalize search for general fields (remove commas)
    $search_normalized = str_replace(',', '', $search);
    $search_query = '%' . $search_normalized . '%';

    // Normalize search specifically for status
    $search_status_normalized = str_replace(' ', '_', strtolower($search)); 
    $search_status_query = '%' . $search_status_normalized . '%';

    // Fetch Pending Errands (available to accept)
    $stmt = $conn->prepare("
        SELECT 
            id, 
            title, 
            location, 
            created_at, 
            deadline, 
            fee, 
            status
        FROM errands
        WHERE status = 'pending' 
        AND (tasker_id IS NULL OR tasker_id = 0)
        AND (
            id = ?
            OR title LIKE ?
            OR location LIKE ?
            OR DATE_FORMAT(created_at, '%M %e %Y') LIKE ?
            OR DATE_FORMAT(deadline, '%M %e %Y') LIKE ?
            OR fee LIKE ? 
        )
        ORDER BY created_at DESC
    ");
    $stmt->bind_param(
        "isssss", 
        $search_int,   // Errand ID exact match
        $search_query, // Title LIKE
        $search_query, // Location LIKE
        $search_query, // Created date LIKE (e.g., "November 30 2005")
        $search_query, // Deadline date LIKE (e.g., "November 30 2005")
        $search_query  // Fee LIKE
    );
    $stmt->execute();
    $result_pending = $stmt->get_result();
    $stmt->close();

    // TASKER DASHBOARD — TOTALS BY STATUS
    $result = $conn->query("
        SELECT 
            SUM(status = 'pending') AS pending_count
        FROM errands
    ");
    $row = $result->fetch_assoc();

    $status_counts = array(
        'pending' => (int)$row['pending_count']
    );
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
    <?php include '../includes/tasker_sidebar.php'; ?>

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
                            <h2>Dashboard</h2>
                        </div>
                    </div>
                </div>

                <div class="header-right">
                    <form class="search-form" method="GET">
                        <div class="search-wrapper">
                            <input type="text" name="search" placeholder="Search"
                                value="<?= htmlspecialchars($search ?? '') ?>">

                            <?php if (!empty($search)): ?>
                                <a href="tasker_dashboard.php" class="clear">
                                    Clear
                                </a>
                            <?php endif; ?>
                        </div>
                        <button type="submit" class="btn-search">Search</button>
                    </form>
                </div>
            </header>
        </div>

        <!-- Dashboard -->
        <div class="dashboard">
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

            <!-- Contents -->
            <div class="contents">
                <!-- Dashboard Total Cards -->
                <section class="totals">
                    <h3>Errand Status Metrics</h3>

                    <div class="total-cards-container">
                        <div class="total-card bg-pending">
                            <div class="card-header">
                                <span class="status-circle circle-pending"></span>
                                <h3>Pending</h3>
                            </div>
                            <p><?= number_format($status_counts['pending']); ?></p>
                        </div>
                    </div>  
                </section>
        
                <!-- Pending Errands Table -->
                <div class="tasker-errands-table">
                    <h4>Available Pending Errands</h4>
                    <div class="table-scroll">
                        <table>
                            <tr>
                                <th>Errand ID</th>
                                <th>Title</th>
                                <th>Location</th>
                                <th>Created At</th>
                                <th>Deadline</th>
                                <th>Fee</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                            <?php if ($result_pending->num_rows > 0): ?>
                                <?php while ($row = $result_pending->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['id']); ?></td>
                                    <td><?= htmlspecialchars($row['title']); ?></td>
                                    <td><?= htmlspecialchars($row['location']); ?></td>
                                    <td><?= htmlspecialchars(date('F j, Y g:i A', strtotime($row['created_at']))); ?></td>
                                    <td><?= htmlspecialchars(date('F j, Y g:i A', strtotime($row['deadline']))); ?></td>
                                    <td>₱<?= number_format($row['fee'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?= $row['status']; ?>">
                                            <?= htmlspecialchars($status_labels[$row['status']]); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="button-row">
                                            <!-- View Button -->
                                            <form method="GET" action="view_errand_info.php">
                                                <input type="hidden" name="errand_id" value="<?= $row['id']; ?>">
                                                <input type="hidden" name="from" value="dashboard">
                                                <button type="submit" class="btn-view">View</button>
                                            </form>

                                            <!-- Accept Button -->
                                            <form method="POST" action="accept_errand.php">
                                                <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                                <button type="submit" class="btn-accept" onclick="return confirm('Do you want to accept this errand?');">
                                                    Accept
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td class="no-found" colspan="8">No errands found.</td></tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>