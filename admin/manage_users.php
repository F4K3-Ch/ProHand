<?php
    session_start();
    require_once '../shared/db.php';

    // Only admin can access
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header("Location: ../auth/login.php");
        exit;
    }

    $admin_id = (int)$_SESSION['user_id'];

    // Handle search input
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $search_int = is_numeric($search) ? (int)$search : 0;

    // Normalize search for general fields (remove commas)
    $search_normalized = str_replace(',', '', $search);
    $search_query = '%' . $search_normalized . '%';

    $stmt = $conn->prepare("
        (
            SELECT 
                id,
                profile_picture,
                email,
                last_name,
                first_name,
                middle_name,
                contact_number,
                role,
                created_at
            FROM users
            WHERE role = 'admin'
            AND id != ?
            AND (
                id = ?
                OR email LIKE ?
                OR REPLACE(CONCAT_WS(' ', IFNULL(first_name,''), IFNULL(middle_name,''), IFNULL(last_name,'')), '  ', ' ') LIKE ?
                OR contact_number LIKE ?
                OR role LIKE ?
                OR DATE_FORMAT(created_at, '%M %e %Y') LIKE ?
            )
        )
        UNION ALL
        (
            SELECT 
                id,
                profile_picture,
                email,
                last_name,
                first_name,
                middle_name,
                contact_number,
                role,
                created_at
            FROM users
            WHERE role = 'tasker'
            AND (
                id = ?
                OR email LIKE ?
                OR REPLACE(CONCAT_WS(' ', IFNULL(first_name,''), IFNULL(middle_name,''), IFNULL(last_name,'')), '  ', ' ') LIKE ?
                OR contact_number LIKE ?
                OR role LIKE ?
                OR DATE_FORMAT(created_at, '%M %e %Y') LIKE ?
            )
        )
        UNION ALL
        (
            SELECT 
                id,
                profile_picture,
                email,
                last_name,
                first_name,
                middle_name,
                contact_number,
                role,
                created_at
            FROM users
            WHERE role = 'client'
            AND (
                id = ?
                OR email LIKE ?
                OR REPLACE(CONCAT_WS(' ', IFNULL(first_name,''), IFNULL(middle_name,''), IFNULL(last_name,'')), '  ', ' ') LIKE ?
                OR contact_number LIKE ?
                OR role LIKE ?
                OR DATE_FORMAT(created_at, '%M %e %Y') LIKE ?
            )
        )
        ORDER BY created_at DESC
    ");

    $stmt->bind_param(
        "iisssss"    // admin id + admin search set
        . "isssss"   // tasker search set
        . "isssss",  // client search set
        $admin_id,

        $search_int,
        $search_query,
        $search_query,
        $search_query,
        $search_query,
        $search_query,

        $search_int,
        $search_query,
        $search_query,
        $search_query,
        $search_query,
        $search_query,

        $search_int,
        $search_query,
        $search_query,
        $search_query,
        $search_query,
        $search_query
    );

    $stmt->execute();
    $all_users_result = $stmt->get_result();
    $stmt->close();

    // Calculate Totals by Role of the Users
    // Initialize counts
    $role_counts = array(
        'admin' => 0,
        'tasker' => 0,
        'client' => 0
    );

    // ----- Count Roles from (users table) -----
    $result = $conn->query("
        SELECT role, COUNT(*) AS count
        FROM users
        WHERE role IN ('admin', 'tasker', 'client')
        GROUP BY role
    ");
    while ($row = $result->fetch_assoc()) {
        $role_counts[$row['role']] = (int)$row['count'];
    }

    // Total Users
    $total_users = array_sum($role_counts);
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
    <?php include '../includes/sidebar.php'; ?>

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
                            <h2>Manage Users</h2>
                        </div>
                    </div>
                </div>

                <div class="header-right">
                    <form class="search-form" method="GET">
                        <div class="search-wrapper">
                            <input type="text" name="search" placeholder="Search"
                                value="<?= htmlspecialchars($search ?? '') ?>">

                            <?php if (!empty($search)): ?>
                                <a href="manage_users.php" class="clear">
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
                                <th>ID</th>
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
                                        $profile_dir = '../uploads/profile_pictures/';
                                        
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
                                                    <input type="hidden" name="from" value="dashboard">
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
