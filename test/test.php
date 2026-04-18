<?php
    session_start();
    
    // 1. Load all required classes
    require_once "../../src/Classes/Database.php";
    require_once "../../src/Classes/User.php";
    require_once "../../src/Classes/Auth.php";
    require_once "../../src/Classes/Admin.php";
    require_once "../../src/Controllers/AdminController.php";

    // 2. Initialize Database and User (for Sidebar)
    $database = new Database();
    $db = $database->getConnection();
    $user_class = new User($db); 

    // 3. Initialize Controller and get Data
    $controller = new AdminController($db);
    $data = $controller->index();

    // 4. Extract data for use in the HTML
    $active_errands = $data['active_errands'];
    $metrics = $data['metrics'];
    $search = $data['search'];
    $status_labels = $data['status_labels'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include '../../includes/web_logo.php'; ?>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css' rel='stylesheet'> 
    <link rel="stylesheet" href="../../public/assets/style/style.css">
    <title>ProHand Admin | Active Errands</title>
</head>
<body>
    <?php include '../../includes/sidebar.php'; ?>

    <main class="main">
        <header class="header"> 
            <div class="header-left">
                <h1>Active Errands</h1>
                <p>Monitor and manage ongoing tasks in the system.</p>
            </div>
            <div class="header-right">
                <form class="search-form" method="GET">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by ID, Title, Name, or Status...">
                    <button type="submit">Search</button>
                </form>
            </div>
        </header>

        <div class="dashboard">
            <section class="totals">
                <div class="total-card bg-pending">
                    <div class="card-info">
                        <h3>Pending</h3>
                        <p><?= number_format($metrics['pending_count']); ?></p>
                    </div>
                    <i class="fa-solid fa-clock-rotate-left icon"></i>
                </div>
                <div class="total-card bg-accepted">
                    <div class="card-info">
                        <h3>Accepted</h3>
                        <p><?= number_format($metrics['accepted_count']); ?></p>
                    </div>
                    <i class="fa-solid fa-check-double icon"></i>
                </div>
                <div class="total-card bg-in-process">
                    <div class="card-info">
                        <h3>In Process</h3>
                        <p><?= number_format($metrics['in_process_count']); ?></p>
                    </div>
                    <i class="fa-solid fa-spinner icon"></i>
                </div>
                <div class="total-card bg-total">
                    <div class="card-info">
                        <h3>Total Active</h3>
                        <p><?= number_format($metrics['total_errands']); ?></p>
                    </div>
                    <i class="fa-solid fa-list-check icon"></i>
                </div>
            </section>

            <div class="admin-errands-table-container">
                <div class="table-header">
                    <h2>Recent Activities</h2>
                </div>
                <div class="admin-errands-table">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Errand Title</th>
                                <th>Client</th>
                                <th>Tasker</th>
                                <th>Created At</th>
                                <th>Deadline</th>
                                <th>Fee</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($active_errands && $active_errands->num_rows > 0): ?>
                                <?php while ($row = $active_errands->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['id']); ?></td>
                                    <td class="title-cell"><?= htmlspecialchars($row['title']); ?></td>
                                    <td><?= htmlspecialchars($row['client_first_name'] . ' ' . $row['client_last_name']); ?></td>
                                    <td>
                                        <?php if ($row['tasker_id']): ?>
                                            <?= htmlspecialchars($row['tasker_first_name'] . ' ' . $row['tasker_last_name']); ?>
                                        <?php else: ?>
                                            <span class="unassigned">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($row['created_at'])); ?></td>
                                    <td><?= date('M d, Y', strtotime($row['deadline'])); ?></td>
                                    <td class="fee-cell">₱<?= number_format($row['fee'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?= $row['status']; ?>">
                                            <?= $status_labels[$row['status']] ?? ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <form method="GET" action="view_errand_info.php">
                                                <input type="hidden" name="errand_id" value="<?= $row['id']; ?>">
                                                <input type="hidden" name="from" value="dashboard">
                                                <button type="submit" class="btn-view">View</button>
                                            </form>
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
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</body>
</html>