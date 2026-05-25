<?php
    session_start();
    
    // 1. Load your OOP Classes using the correct paths
    require_once "../../src/Classes/Database.php";
    require_once "../../src/Classes/User.php";
    require_once "../../src/Classes/Auth.php";
    require_once "../../src/Classes/Admin.php";
    require_once "../../src/Controllers/AdminController.php";

    // 2. Setup Database and User (for the Sidebar)
    $database = new Database();
    $db = $database->getConnection(); // This replaces the old $conn from db.php
    $adminController = new AdminController($db);

    // 3. Process delete_active_errand if form was sent here
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_active_errand') {
        $adminController->handleActiveErrandDeletion();
    }

    // 4. Extract data for your HTML
    $data = $adminController->index();
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
                            <h2>Dashboard</h2>
                        </div>
                    </div>
                </div>
                <div class="header-right">
                    <form class="search-form" method="GET">
                        <div class="search-wrapper">
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search">
                            <?php if (!empty($search)): ?>
                                <a href="admin_dashboard.php" class="clear">Clear</a>
                            <?php endif; ?>
                        </div>
                        <button type="submit" class="btn-search">Search</button>
                    </form>
                </div>
            </header>
        </div>

        <!-- Dashboard -->
        <div class="dashboard">
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
                <section class="totals">
                    <!-- Dashboard Total Cards -->
                    <h3>Errand Status Metrics</h3>
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
                                <h3>Total Active</h3>
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
                                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this errand?');">
                                                <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                                <input type="hidden" name="action" value="delete_active_errand">
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