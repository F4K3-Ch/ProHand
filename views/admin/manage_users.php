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

    // 3. Get Data from the Controller
    $adminController = new AdminController($db);

    // 4. Extract data for your HTML
    $data = $adminController->manageUsers();
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
    <input type="checkbox" id="sidebar-toggle-check" class="sidebar-checkbox">
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
                            <h2>Manage Users</h2>
                        </div>

                        <label for="sidebar-toggle-check" class="sidebar-toggle-btn" aria-label="Toggle Navigation Menu">
                            <i class="fa-solid fa-bars"></i>
                        </label>
                    </div>
                </div>

                <div class="header-right">
                    <form class="search-form" method="GET">
                        <div class="search-wrapper">
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search">
                            <?php if (!empty($search)): ?>
                                <a href="manage_users.php" class="clear">Clear</a>
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
                <!-- User Total Cards -->
                <section class="totals">
                    <h3>Users Metrics</h3>

                    <div class="total-cards-container">
                        <div class="total-card bg-admin">
                            <div class="card-header">
                                <span class="status-circle circle-admin"></span>
                                <h3>Admin</h3>
                            </div>
                            <p><?= number_format($role_counts['admin']); ?></p>
                        </div>

                        <div class="total-card bg-tasker">
                            <div class="card-header">
                                <span class="status-circle circle-tasker"></span>
                                <h3>Tasker</h3>
                            </div>
                            <p><?= number_format($role_counts['tasker']); ?></p>
                        </div>

                        <div class="total-card bg-client">
                            <div class="card-header">
                                <span class="status-circle circle-client"></span>
                                <h3>Client</h3>
                            </div>
                            <p><?= number_format($role_counts['client']); ?></p>
                        </div>

                        <div class="total-card bg-total">
                            <div class="card-header">
                                <span class="status-circle circle-total"></span>
                                <h3>Total Users</h3>
                            </div>
                            <p><?= number_format($total_users); ?></p>
                        </div>
                    </div>
                </section>
            
                <!-- User Accounts -->
                <div class="users-table">
                    <div class="user-header-table">
                        <h4>User Accounts</h4>
                        
                        <!-- Add User Button -->
                        <a class="add-user-btn" href="add_user.php">
                            <i class="fa-solid fa-circle-plus"></i>
                            Add New User
                        </a>
                    </div>

                    <div class="table-scroll">
                        <table>
                            <tr>
                                <th>User ID</th>
                                <th>Picture</th>
                                <th>Email</th>
                                <th>Last Name</th>
                                <th>First Name</th>
                                <th>Middle Name</th>
                                <th>Contact</th>
                                <th>Created At</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                            <?php if ($all_users_result->num_rows > 0): ?>
                                <?php while ($row = $all_users_result->fetch_assoc()): ?>
                                    <?php
                                        // Profile picture directory
                                        $profile_dir = '../../public/uploads/profile_pictures/';
                                        
                                        // Determine which profile picture to use based on availability and file existence
                                        if (!empty($row['profile_picture']) && file_exists($profile_dir . basename($row['profile_picture']))) {
                                            $profile_picture = $profile_dir . basename($row['profile_picture']);
                                        } else {
                                            $profile_picture = $profile_dir . 'default.jpg';
                                        }
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['id']); ?></td>
                                        <td>
                                            <img src="<?= htmlspecialchars($profile_picture); ?>" alt="Profile" class="table-profile-img">
                                        </td>
                                        <td><?= htmlspecialchars($row['email']); ?></td>
                                        <td><?= htmlspecialchars($row['last_name']); ?></td>
                                        <td><?= htmlspecialchars($row['first_name']); ?></td>
                                        <td><?= htmlspecialchars($row['middle_name']); ?></td>
                                        <td><?= htmlspecialchars($row['contact_number']); ?></td>
                                        <td><?= htmlspecialchars(date('F j, Y g:i A', strtotime($row['created_at']))); ?></td>
                                        <td>
                                            <span class="role-badge role-<?= $row['role']; ?>">
                                                <?= htmlspecialchars(ucfirst($row['role'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="button-row">
                                                <form method="GET" action="view_user_info.php">
                                                    <input type="hidden" name="id" value="<?= (int)$row['id']; ?>">
                                                    <input type="hidden" name="from" value="admin_manage">
                                                    <button type="submit" class="btn-view">View Info</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td class="no-found" colspan="10">No user account found.</td></tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>   
        </div>
    </main>
</body>
</html>
