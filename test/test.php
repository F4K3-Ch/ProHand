<?php
    session_start();
    require_once "../../src/Classes/Database.php";
    require_once "../../src/Classes/User.php";
    require_once "../../src/Classes/Auth.php";
    require_once "../../src/Classes/Tasker.php";
    require_once "../../src/Controllers/TaskerController.php";

    $database = new Database();
    $db = $database->getConnection();
    $controller = new TaskerController($db);

    // Get data from OOP Controller
    $data = $controller->activeTasks();
    extract($data); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProHand | Your Tasks</title>
    <?php include '../../includes/web_logo.php'; ?>
    <link rel="stylesheet" href="../../public/css/tasker_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="dashboard-container">
            <div class="welcome-section">
                <h1>Current Tasks</h1>
                <p>Manage the errands you have accepted.</p>
            </div>

            <div class="table-container">
                <div class="table-header">
                    <h2>Active List</h2>
                    <form action="" method="GET" class="search-form">
                        <input type="text" name="search" placeholder="Search tasks..." value="<?= htmlspecialchars($search); ?>">
                        <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                    </form>
                </div>

                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Location</th>
                                <th>Date Created</th>
                                <th>Deadline</th>
                                <th>Fee</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($tasks && $tasks->num_rows > 0): ?>
                                <?php while ($row = $tasks->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['id']; ?></td>
                                    <td><?= htmlspecialchars($row['title']); ?></td>
                                    <td><?= htmlspecialchars($row['location']); ?></td>
                                    <td><?= date('M e Y', strtotime($row['created_at'])); ?></td>
                                    <td><?= date('M e Y', strtotime($row['deadline'])); ?></td>
                                    <td>₱<?= number_format($row['fee'], 2); ?></td>
                                    <td>
                                        <span class="status-badge <?= $row['status']; ?>">
                                            <?= $status_labels[$row['status']] ?? $row['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <form method="GET" action="view_errand_info.php">
                                                <input type="hidden" name="errand_id" value="<?= $row['id']; ?>">
                                                <input type="hidden" name="from" value="your_task">
                                                <button type="submit" class="btn-view">View</button>
                                            </form>

                                            <form method="POST" action="cancel_errand.php">
                                                <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                                <button type="submit" class="btn-cancel" onclick="return confirm('Are you sure you want to cancel this errand?');">
                                                    Cancel
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td class="no-found" colspan="8">No active tasks found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</body>
</html>