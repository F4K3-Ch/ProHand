<?php
    session_start();
    require_once "../shared/db.php";

    // Only admin can access
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header("Location: ../auth/login.php");
        exit;
    }

    // Status Labels
    $status_labels = array(
        'completed' => 'Completed',
        'failed' => 'Failed'
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

    // Fetch Finish Errands
    $stmt = $conn->prepare("
        SELECT 
            id,
            errand_id,
            title,
            created_at,
            completed_at,
            failed_at,
            client_first_name,
            client_middle_name,
            client_last_name,
            tasker_first_name,
            tasker_middle_name,
            tasker_last_name,
            fee,
            status
        FROM history
        WHERE status IN ('completed', 'failed')
        AND (
            errand_id = ? 
            OR title LIKE ? 
            OR REPLACE(CONCAT_WS(' ', IFNULL(client_first_name,''), IFNULL(client_middle_name,''), IFNULL(client_last_name,'')), '  ', ' ') LIKE ?
            OR REPLACE(CONCAT_WS(' ', IFNULL(tasker_first_name,''), IFNULL(tasker_middle_name,''), IFNULL(tasker_last_name,'')), '  ', ' ') LIKE ?
            OR DATE_FORMAT(created_at, '%M %e %Y') LIKE ?
            OR DATE_FORMAT(completed_at, '%M %e %Y') LIKE ?
            OR DATE_FORMAT(failed_at, '%M %e %Y') LIKE ?
            OR fee LIKE ? 
            OR status LIKE ?
        )
        ORDER BY updated_at DESC
    ");

    $stmt->bind_param(
        "issssssss",
        $search_int,
        $search_query,
        $search_query,
        $search_query,
        $search_query,
        $search_query,
        $search_query,
        $search_query,         
        $search_status_query   
    );
    $stmt->execute();
    $result_finished = $stmt->get_result();
    $stmt->close();

    // Calculate Totals by Status
    $result = $conn->query("
        SELECT 
            SUM(status = 'completed') AS completed_count,
            SUM(status = 'failed') AS failed_count,
            COUNT(*) AS total_errands
        FROM history
        WHERE status IN ('completed', 'failed')
    ");
    $row = $result->fetch_assoc();

    $status_counts = array(
        'completed' => (int)$row['completed_count'],
        'failed' => (int)$row['failed_count']
    );

    $total_errands = (int)$row['total_errands'];

    // Total income in completed errands
    $result = $conn->query("
        SELECT COALESCE(SUM(fee), 0) AS earnings
        FROM history
        WHERE status = 'completed'
    ");
    $earnings = $result->fetch_assoc()['earnings'];

    // Total failed income in failed errands
    $result = $conn->query("
        SELECT COALESCE(SUM(fee), 0) AS failed_earnings
        FROM history
        WHERE status = 'failed'
    ");
    $failed_earnings = $result->fetch_assoc()['failed_earnings'];
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
    <?php include '../includes/admin_sidebar.php'; ?>

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
                            <h2>Finished Errands</h2>
                        </div>
                    </div>
                </div>

                <div class="header-right">
                    <form class="search-form" method="GET">
                        <div class="search-wrapper">
                            <input type="text" name="search" placeholder="Search"
                                value="<?= htmlspecialchars($search ?? '') ?>">

                            <?php if (!empty($search)): ?>
                                <a href="finished_errands.php" class="clear">
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
                <!-- Finished Errands Total Cards -->
                <section class="totals">
                    <h3>Errand Status Metrics</h3>

                    <div class="total-cards-container">
                        <div class="total-card bg-completed">
                            <div class="card-header">
                                <span class="status-circle circle-complete"></span>
                                <h3>Completed</h3>
                            </div>
                            <p><?= number_format($status_counts['completed']); ?></p>
                        </div>

                        <div class="total-card bg-failed">
                            <div class="card-header">
                                <span class="status-circle circle-failed"></span>
                                <h3>Failed</h3>
                            </div>
                            <p><?= number_format($status_counts['failed']); ?></p>
                        </div>

                        <div class="total-card bg-total">
                            <div class="card-header">
                                <span class="status-circle circle-total"></span>
                                <h3>Total Finished Errands</h3>
                            </div>
                            <p><?= number_format($total_errands); ?></p>
                        </div>

                        <div class="total-card bg-earnings">
                            <div class="card-header">
                                <span class="status-circle circle-earnings"></span>
                                <h3>Earnings</h3>
                            </div>
                            <p>₱<?= number_format($earnings, 2); ?></p>
                        </div>

                        <div class="total-card bg-failed-earnings">
                            <div class="card-header">
                                <span class="status-circle circle-failed-earnings"></span>
                                <h3>Failed Earnings</h3>
                            </div>
                            <p>&#8722; ₱<?= number_format($failed_earnings, 2); ?></p>
                        </div>
                    </div>
                </section>
                
                <!-- Finished Errands Table -->
                <div class="admin-errands-table">
                    <h4>Finished Errands</h4>
                    <div class="table-scroll">
                        <table>
                            <tr>
                                <th>Errand ID</th>
                                <th>Title</th>
                                <th>Client</th>
                                <th>Tasker</th>
                                <th>Created At</th>
                                <th>Finished At</th>
                                <th>Fee</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                            <?php if ($result_finished->num_rows > 0): ?>
                                <?php while ($row = $result_finished->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['errand_id']); ?></td>
                                    <td><?= htmlspecialchars($row['title']); ?></td>
                                    <td><?= htmlspecialchars($row['client_first_name'] . ' ' . $row['client_middle_name'] . ' ' . $row['client_last_name']); ?></td>
                                    <td><?= htmlspecialchars($row['tasker_first_name'] . ' ' . $row['tasker_middle_name'] . ' ' . $row['tasker_last_name']); ?></td>
                                    <td><?= htmlspecialchars(date('F j, Y g:i A', strtotime($row['created_at']))); ?></td>
                                    <td>
                                        <?php if (!empty($row['completed_at'])): ?>
                                            <?= htmlspecialchars(date("F j, Y g:i A", strtotime($row['completed_at']))) ?>
                                        <?php elseif (!empty($row['failed_at'])): ?>
                                            <?= htmlspecialchars(date("F j, Y g:i A", strtotime($row['failed_at']))) ?>
                                        <?php else: ?>
                                            &mdash;  <!--dash symbol -->
                                        <?php endif; ?>
                                    </td>
                                    <td>₱<?= number_format($row['fee'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?= $row['status']; ?>">
                                            <?= htmlspecialchars($status_labels[$row['status']]); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="button-row">
                                            <!-- View Button -->
                                            <form  method="GET" action="view_errand_info.php">
                                                <input type="hidden" name="errand_id" value="<?= $row['errand_id']; ?>">
                                                <input type="hidden" name="from" value="finished">
                                                <button type="submit" class="btn-view">View</button>
                                            </form>

                                            <!-- Delete Button -->
                                            <form method="POST" action="delete_finished_errand.php" onsubmit="return confirm('Are you sure you want to delete this finished errand?');">
                                                <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                                <button type="submit" class="btn-delete">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td class="no-found" colspan="9">No errands found.</td></tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>