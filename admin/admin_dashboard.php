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

    // Unassigned Search
    $unassigned_terms = ['unassigned', 'none', 'no tasker', 'null'];
    $is_unassigned_search = in_array(strtolower(trim($search)), $unassigned_terms);

    // Fetch Active Errands
    $stmt = $conn->prepare("
        SELECT 
            e.id,
            e.title,
            e.created_at,
            e.deadline,
            c.first_name AS client_first_name,
            c.middle_name AS client_middle_name,
            c.last_name AS client_last_name,
            t.id AS tasker_id,
            t.first_name AS tasker_first_name,
            t.middle_name AS tasker_middle_name,
            t.last_name AS tasker_last_name,
            e.fee,
            e.status
        FROM errands AS e
        LEFT JOIN users AS c ON e.client_id = c.id
        LEFT JOIN users AS t ON e.tasker_id = t.id
        WHERE e.status IN ('pending', 'accepted', 'in_process')
        AND (
            e.id = ? 
            OR e.title LIKE ? 
            OR REPLACE(CONCAT_WS(' ', IFNULL(c.first_name,''), IFNULL(c.middle_name,''), IFNULL(c.last_name,'')), '  ', ' ') LIKE ?
            OR REPLACE(CONCAT_WS(' ', IFNULL(t.first_name,''), IFNULL(t.middle_name,''), IFNULL(t.last_name,'')), '  ', ' ') LIKE ?
            OR DATE_FORMAT(e.created_at, '%M %e %Y') LIKE ?
            OR DATE_FORMAT(e.deadline, '%M %e %Y') LIKE ?
            OR e.fee LIKE ?
            OR e.status LIKE ?
            OR (? = 1 AND e.tasker_id IS NULL)
        )
        ORDER BY e.updated_at DESC
    ");
    $stmt->bind_param(
        "isssssssi",
        $search_int,            // Errand ID exact match
        $search_query,          // Title LIKE
        $search_query,          // Client full name
        $search_query,          // Tasker full name
        $search_query,          // Created date LIKE (e.g., "November 30 2005")
        $search_query,          // Updated date LIKE (e.g., "November 30 2005")
        $search_query,          // Fee LIKE
        $search_status_query,   // Status LIKE
        $is_unassigned_search   // Unassigned LIKE
    );
    $stmt->execute();
    $active_errands = $stmt->get_result();
    $stmt->close();

    // Calculate Totals by Status
    $result = $conn->query("
        SELECT 
            SUM(status = 'pending') AS pending_count,
            SUM(status = 'accepted') AS accepted_count,
            SUM(status = 'in_process') AS in_process_count,
            COUNT(*) AS total_errands
        FROM errands
        WHERE status IN ('pending', 'accepted', 'in_process')
    ");
    $row = $result->fetch_assoc();

    $status_counts = array(
        'pending' => (int)$row['pending_count'],
        'accepted' => (int)$row['accepted_count'],
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
    <?php include '../includes/admin_sidebar.php'; ?>

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
                            <a href="admin_dashboard.php" class="clear">
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
                        <h3>Total Active Errands</h3>
                    </div>
                    <p><?= number_format($total_errands); ?></p>
                </div>
            </div>
        </section>

        <!-- Active Errands Table -->
        <div class="admin-errands-table">
            <h4>Active Errands</h4>
            <div class="table-scroll">
                <table>
                    <tr>
                        <th>Errand ID</th>
                        <th>Title</th>
                        <th>Client</th>
                        <th>Tasker</th>
                        <th>Created At</th>
                        <th>Deadline</th>
                        <th>Fee</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    <?php if ($active_errands->num_rows > 0): ?>
                        <?php while ($row = $active_errands->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['id']); ?></td>
                            <td><?= htmlspecialchars($row['title']); ?></td>
                            <td><?= htmlspecialchars($row['client_first_name'] . ' ' . $row['client_middle_name'] . ' ' . $row['client_last_name']); ?></td>
                            <td><?= $row['tasker_id'] ? htmlspecialchars($row['tasker_first_name'] . ' ' . $row['tasker_middle_name'] . ' ' . $row['tasker_last_name']) : 'Unassigned'; ?></td>
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
                                    <form  method="GET" action="view_errand_info.php">
                                        <input type="hidden" name="errand_id" value="<?= $row['id']; ?>">
                                        <input type="hidden" name="from" value="dashboard">
                                        <button type="submit" class="btn-view">View</button>
                                    </form>

                                    <!-- Delete Button -->
                                    <form method="POST" action="delete_active_errand.php" onsubmit="return confirm('Are you sure you want to delete this errand?');">
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
    </main>
</body>
</html>