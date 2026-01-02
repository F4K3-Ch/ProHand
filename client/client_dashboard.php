<?php
    session_start();
    require_once "../shared/db.php";

    // Ensure only client can access
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
        header("Location: ../auth/login.php");
        exit;
    }
    
    $client_id = (int)$_SESSION['user_id'];

    // Status Labels
    $status_labels = array(
        'pending' => 'Pending',
        'accepted' => 'Accepted',
        'in_process' => 'In Process'
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


    // Fetch Created Errands 
    $stmt = $conn->prepare("
        SELECT 
            id,
            title, 
            location,
            created_at, 
            deadline, 
            fee, 
            status,
            updated_at
        FROM errands
        WHERE client_id = ? 
        AND (
            id = ?
            OR title LIKE ?
            OR location LIKE ?
            OR DATE_FORMAT(created_at, '%M %e %Y') LIKE ?
            OR DATE_FORMAT(deadline, '%M %e %Y') LIKE ?
            OR fee LIKE ?
            OR status LIKE ?
        )
        ORDER BY updated_at DESC
    ");
    $stmt->bind_param(
        "iissssss", 
        $client_id,             // User ID
        $search_int,            // Errand ID exact match
        $search_query,          // Title LIKE
        $search_query,          // Location LIKE
        $search_query,          // Created date LIKE (e.g., "November 30 2005")
        $search_query,          // Deadline date LIKE (e.g., "November 30 2005")
        $search_query,          // Fee LIKE
        $search_status_query,   // Status LIKE
    );
    $stmt->execute();
    $errands_created = $stmt->get_result();
    $stmt->close();

    // CLIENT DASHBOARD — TOTALS BY STATUS
    $stmt = $conn->prepare("
        SELECT 
            SUM(status = 'pending')   AS pending_count,
            SUM(status = 'accepted')   AS accepted_count,
            SUM(status = 'in_process') AS in_process_count,
            COUNT(*) AS total_errands
        FROM errands
        WHERE client_id = ?
        AND status IN ('pending', 'accepted', 'in_process')
    ");
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    $row = $result->fetch_assoc();
    $status_counts = array(
        'pending'   => (int)$row['pending_count'],
        'accepted'   => (int)$row['accepted_count'],
        'in_process' => (int)$row['in_process_count']
    );

    $total_errands = (int)$row['total_errands'];
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
    <?php include '../includes/client_sidebar.php'; ?>

    <!-- Fix Wrapper -->
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
                            <a href="client_dashboard.php" class="clear">
                                Clear
                            </a>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="btn-search">Search</button>
                </form>
            </div>
        </header>
    </div>

    <!-- Main Content -->
    <main class="main">
        <!-- Dashboard Total Cards -->
        <section class="totals">
            <h3>Errand Status Metrics</h3>

            <!-- Success/Error Message -->
            <div class="error-success-message">
                <?php if (isset($_SESSION['success'])): ?>
                    <p class="success"><?= htmlspecialchars($_SESSION['success']); ?></p>
                    <?php unset($_SESSION['success']); ?>
                <?php elseif (isset($_SESSION['error'])): ?>
                    <p class="error"><?= htmlspecialchars($_SESSION['error']); ?></p>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
            </div>

            <div class="total-cards-container">
                <div class="total-card bg-pending">
                    <div class="card-header">
                        <span class="status-circle circle-pending"></span>
                        <h3>Pending</h3>
                    </div>
                    <p><?= number_format($status_counts['pending']); ?></p>
                </div>

                <div class="total-card bg-accepted">
                    <div class="card-header">
                        <span class="status-circle circle-accepted"></span>
                        <h3>Accepted</h3>
                    </div>
                    <p><?= number_format($status_counts['accepted']); ?></p>
                </div>

                <div class="total-card bg-process">
                    <div class="card-header">
                        <span class="status-circle circle-process"></span>
                        <h3>In Process</h3>
                    </div>
                    <p><?= number_format($status_counts['in_process']); ?></p>
                </div>

                <div class="total-card bg-total">
                    <div class="card-header">
                        <span class="status-circle circle-total"></span>
                        <h3>Your Total Active Errands</h3>
                    </div>
                    <p><?= number_format($total_errands); ?></p>
                </div>
            </div>
        </section>
        
        <!-- My Errands Table -->
        <div class="client-errands-table">
            <h4>My Errands</h4>
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
                    <?php if ($errands_created->num_rows > 0): ?>
                        <?php while ($row = $errands_created->fetch_assoc()): ?>
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
                                    <form method="GET" action="view_errand_info.php">
                                        <input type="hidden" name="errand_id" value="<?= $row['id']; ?>">
                                        <input type="hidden" name="from" value="dashboard">
                                        <button type="submit" class="btn-view">View</button>
                                    </form>

                                    <?php if ($row['status'] === 'pending'): ?>
                                        <!-- Delete Button -->
                                        <form method="POST" action="delete_errand.php" onsubmit="return confirm('Are you sure you want to delete this errand request? It will be deleted permanently.');">
                                            <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                            <button type="submit" class="btn-delete">Delete</button>
                                        </form>
                                    <?php elseif ($row['status'] === 'accepted'): ?>
                                        <!-- Cancel Button -->
                                        <form method="POST" action="cancel_errand.php" onsubmit="return confirm('Are you sure you want to cancel this errand? It will be return to Pending.');">
                                            <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                            <button type="submit" class="btn-cancel">Cancel</button>
                                        </form>
                                    <?php elseif ($row['status'] === 'in_process'): ?>
                                        <!-- Delete Button Disabled-->
                                        <button disabled class="btn-cancel-disabled">Cancel</button>
                                    <?php endif; ?>
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
    </main>
</body>
</html>