<?php
    session_start();
    
    // 1. Load your OOP Classes using the correct paths
    require_once "../../src/Classes/Database.php";
    require_once "../../src/Classes/User.php";
    require_once "../../src/Classes/Auth.php";
    require_once "../../src/Classes/Client.php";
    require_once "../../src/Controllers/ClientController.php";

    // 2. Setup Database and User (for the Sidebar)
    $database = new Database();
    $db = $database->getConnection(); // This replaces the old $conn from db.php
    $user_class = new User($db); 

    // 3. Get Data from the Controller
    $controller = new ClientController($db);
    $data = $controller->index();

    // 4. Extract data for your HTML
    $errands_created = $data['errands_created'];
    $metrics = $data['metrics'];
    $search = $data['search'];
    $status_labels = $data['status_labels'];

    // Only client can access
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
        header("Location: ../auth/login.php");
        exit;
    }
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
                                <a href="client_dashboard.php" class="clear">Clear</a>
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
                <section class="totals">
                    <!-- Dashboard Total Cards -->
                    <h3>Errand Status Metrics</h3>
                    <div class="total-cards-container">
                        <div class="total-card bg-pending">
                            <div class="card-header">
                                <span class="status-circle circle-pending"></span>
                                <h3>Pending</h3>
                            </div>
                            <p><?= number_format($metrics['pending_count']); ?></p>
                        </div>
                        <div class="total-card bg-accepted">
                            <div class="card-header">
                                <span class="status-circle circle-accepted"></span>
                                <h3>Accepted</h3>
                            </div>
                            <p><?= number_format($metrics['accepted_count']); ?></p>
                        </div>
                        <div class="total-card bg-process">
                            <div class="card-header">
                                <span class="status-circle circle-process"></span>
                                <h3>In Process</h3>
                            </div>
                            <p><?= number_format($metrics['in_process_count']); ?></p>
                        </div>
                        <div class="total-card bg-total">
                            <div class="card-header">
                                <span class="status-circle circle-total"></span>
                                <h3>Total Active Errands</h3>
                            </div>
                            <p><?= number_format($metrics['total_errands']); ?></p>
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
            </div>
        </div>
    </main>
</body>
</html>