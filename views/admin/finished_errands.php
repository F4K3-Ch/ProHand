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

    // 3. Process finished_active_errand if form was sent here
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_finished_errand') {
        $adminController->handleFinishedErrandDeletion();
    }

    // 4. Extract data for your HTML
    $data = $adminController->finishedErrands();
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
                            <h2>Finished Errands</h2>
                        </div>
                    </div>
                </div>
                <div class="header-right">
                    <form class="search-form" method="GET">
                        <div class="search-wrapper">
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search">
                            <?php if (!empty($search)): ?>
                                <a href="finished_errands.php" class="clear">Clear</a>
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
                                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this finished errand?');">
                                                <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                                <input type="hidden" name="action" value="delete_finished_errand">
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