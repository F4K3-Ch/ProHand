<?php
    session_start();
    require_once "../../src/Classes/Database.php";
    require_once "../../src/Classes/User.php";
    require_once "../../src/Classes/Auth.php";
    require_once "../../src/Classes/Admin.php";
    require_once "../../src/Controllers/AdminController.php";
    require_once "../../src/Controllers/SharedController.php";

    $database = new Database();
    $db = $database->getConnection();

    $adminController = new AdminController($db);
    $sharedController = new SharedController($db);

    // Download Logic
    if (isset($_GET['action']) && $_GET['action'] === 'download' && isset($_GET['download_id'])) {
        $is_history_download = (isset($_GET['type']) && $_GET['type'] === 'history');
        
        // 3. CALL it from $sharedController
        $sharedController->handleDownload((int)$_GET['download_id'], $is_history_download ? 'history' : 'regular');
        
        // If handleDownload fails to exit (e.g., file not found), set the error
        $_SESSION['error'] = "File not found on server.";
    }

    // Handle POST Request
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['delete_active_errand_id'])) {
            $_POST['id'] = $_POST['delete_active_errand_id']; 
            $adminController->handleActiveErrandDeletion();
        } elseif (isset($_POST['delete_finished_errand_id'])) {
            $_POST['id'] = $_POST['delete_finished_errand_id']; 
            $adminController->handleFinishedErrandDeletion();
        } elseif (isset($_POST['approve_cancellation_id'])) {
            $_POST['id'] = $_POST['approve_cancellation_id']; 
            $adminController->handleApproveCancellation();
        } elseif (isset($_POST['deny_cancellation_id'])) {
            $_POST['id'] = $_POST['deny_cancellation_id'];
            $adminController->handleDenyCancellation();
        }
    }

    $errand_id = (int)($_GET['errand_id'] ?? 0);
    $data = $adminController->viewErrandDetails($errand_id);

    if (!$data) {
        $_SESSION['error'] = "Errand not found.";
        header("Location: admin_dashboard.php");
        exit;
    }

    // This makes $errand, $client_picture, $tasker_picture, etc. available to the HTML
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

    <!-- Back button -->
    <a href="<?= $back_url; ?>" class="btn-back">
        <i class="fa-solid fa-arrow-left"></i> 
        Back
    </a>

    <!-- View Main Content -->
    <main class="view-main">

        <!-- Info Container --> 
        <div class="info-container">

            <!-- Errand Details Container -->
            <div class="errand-details-container">
                <h1>Errand Details</h1>

                <!-- Success/Error Message -->
                <div class="view-main-error-success-message">
                    <?php if (isset($_SESSION['success'])): ?>
                        <p class="success"><?= htmlspecialchars($_SESSION['success']); ?></p>
                        <?php unset($_SESSION['success']); ?>
                    <?php elseif (isset($_SESSION['error'])): ?>
                        <p class="error"><?= htmlspecialchars($_SESSION['error']); ?></p>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>
                </div>

                <div class="group-info-box">
                    <div class="info-box">
                        <label>Errand ID</label>
                        <input type="text" readonly value="<?= htmlspecialchars($errand['errand_id']); ?>">
                    </div>

                    <div class="info-box">
                        <label>Title</label>
                        <input type="text" readonly value="<?= htmlspecialchars($errand['title']); ?>">
                    </div>
                </div>

                <div class="info-box">
                    <label>Description</label>
                    <textarea readonly><?= htmlspecialchars($errand['description']); ?></textarea>
                </div>

                <div class="info-box">
                    <label>Contact</label>
                    <textarea readonly><?= htmlspecialchars($errand['contact']); ?></textarea>
                </div>

                <div class="group-info-box">
                    <div class="info-box">
                        <label>Location</label>
                        <input type="text" readonly value="<?= htmlspecialchars($errand['location']); ?>">
                    </div>

                    <div class="info-box">
                        <label>Fee</label>
                        <input type="text" readonly value="₱<?= number_format($errand['fee'], 2); ?>">
                    </div>
                </div>

                <div class="group-info-box">
                    <div class="info-box">
                        <label>Created At</label>
                        <input type="text" readonly value="<?= htmlspecialchars(date('F j, Y g:i A', strtotime($errand['created_at']))); ?>">
                    </div>

                    <div class="info-box">
                        <label>Deadline</label>
                        <input type="text" readonly value="<?= htmlspecialchars(date('F j, Y g:i A', strtotime($errand['deadline']))); ?>">
                    </div>
                </div>

                <div class="group-info-box">
                    <div class="info-box">
                        <label>Accepted At</label>
                        <input type="text" readonly value="<?php 
                            if (!empty($errand['accepted_at'])) {
                                echo htmlspecialchars(date('F j, Y g:i A', strtotime($errand['accepted_at'])));
                            } else {
                                echo 'Not set';
                            }
                        ?>">
                    </div>

                    <div class="info-box">
                        <label>In Process At</label>
                        <input type="text" readonly value="<?php 
                            if (!empty($errand['in_process_at'])) {
                                echo htmlspecialchars(date('F j, Y g:i A', strtotime($errand['in_process_at'])));
                            } else {
                                echo 'Not set';
                            }
                        ?>">
                    </div>
                </div>

                <div class="group-info-box">
                    <div class="info-box">
                        <label>Finished At</label>
                        <input type="text" readonly value="<?php 
                            if (!empty($errand['completed_at'])) {
                                echo htmlspecialchars(date('F j, Y g:i A', strtotime($errand['completed_at'])));
                            } elseif (!empty($errand['failed_at'])) {
                                echo htmlspecialchars(date('F j, Y g:i A', strtotime($errand['failed_at'])));
                            } else {
                                echo 'Not set';
                            }
                        ?>">
                    </div>
                    
                    <div class="info-box">
                        <label>Status</label>
                        <input type="text" 
                            readonly 
                            class="status-input-field status-<?= $errand['status']; ?>" 
                            value="<?= htmlspecialchars($status_labels[$errand['status']]); ?>">
                    </div>
                </div>

                <div class="group-info-box">
                    <div class="info-box">
                        <label>Attachment</label>

                        <?php if ($attachment_result && $attachment_result->num_rows > 0): ?>
                            <?php 
                                // Reset pointer or loop if needed; here we fetch the row
                                $file = $attachment_result->fetch_assoc(); 
                                
                                // Path for browser preview
                                $relative_path = '../../public/' . $file['file_path'];
                                
                                // Point back to the current file
                                $download_action = "view_errand_info.php";
                            ?>

                            <div class="attachment-item">
                                <a href="<?= htmlspecialchars($relative_path); ?>" target="_blank" class="attachment-name">
                                    <i class="fa-solid fa-file-lines"></i> <?= htmlspecialchars($file['original_name']); ?>
                                </a>

                                <form method="GET">
                                    <!-- Keep the user on the current errand page -->
                                    <input type="hidden" name="errand_id" value="<?= $errand_id; ?>">
                                    
                                    <!-- Trigger word for the IF statement -->
                                    <input type="hidden" name="action" value="download">
                                    
                                    <!-- Your specific change here -->
                                    <input type="hidden" name="download_id" value="<?= (int)$file['id']; ?>">
                                    
                                    <!-- Check if history is needed -->
                                    <?php if (isset($is_finished) && $is_finished): ?>
                                        <input type="hidden" name="type" value="history">
                                    <?php endif; ?>

                                    <button type="submit" class="btn-download">
                                        <i class="fa-solid fa-download"></i> Download
                                    </button>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="no-attachments">
                                <p class="no-attachment">None</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($errand['payment_receipt_path'])): ?>
                        <div class="info-box">
                            <label>Proof of Payment</label>
                            <div class="attachment-item">
                                <a href="../../public/uploads/receipts/<?= basename($errand['payment_receipt_path']); ?>" target="_blank" class="attachment-name">
                                    <i class="fa-solid fa-file-invoice-dollar"></i> <?= htmlspecialchars(basename($errand['payment_receipt_path'])); ?>
                                </a>
                            </div>
                        </div>
                    <?php elseif ($errand['status'] === 'pending_payment'): ?>
                        <div class="info-box">
                            <label>Proof of Payment</label>
                            <div class="no-attachments">
                                <p class="no-attachment">None</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="info-bot-btn">
                    <?php if ($errand['status'] === 'pending_payment'): ?>
                        <form method="POST" onsubmit="return confirm('Confirm you received the GCash deposit? This will change the status to In Process.');">
                            <input type="hidden" name="approve_payment_id" value="<?= $errand['id']; ?>">
                            <button type="submit" class="btn-approve">
                                <i class="fa-solid fa-circle-check"></i> Accept Receipt
                            </button>
                        </form>
                        <form method="POST" onsubmit="return confirm('Reject this proof screenshot? The client will need to submit a valid receipt.');">
                            <input type="hidden" name="reject_payment_id" value="<?= $errand['id']; ?>">
                            <button type="submit" class="btn-reject">
                                <i class="fa-solid fa-circle-xmark"></i> Reject Receipt
                            </button>
                        </form>
                    <?php elseif ($errand['status'] === 'in_process'): ?>
                        <span class="descriptive-status-badge">
                            <i class="fa-solid fa-spinner fa-spin"></i> Tasker is performing errand...
                        </span>

                        <form method="POST" onsubmit="return confirm('CRITICAL: Force-cancel this active errand? The platform will hold the fund for manual payout adjustments.');">
                            <input type="hidden" name="admin_cancel_errand_id" value="<?= $errand['id']; ?>">
                            <button type="submit" class="btn-cancel">
                                <i class="fa-solid fa-ban"></i> Force Cancel Errand
                            </button>
                        </form>
                    <?php endif; ?>
                    <?php if ($is_active): ?>
                        <!-- Delete Active Errand Button -->
                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this errand?');">
                            <input type="hidden" name="delete_active_errand_id" value="<?= $errand['id']; ?>">
                            <button type="submit" class="btn-delete"><i class="fa-regular fa-trash-can"></i>Delete</button>
                        </form>
                    <?php elseif ($is_finished): ?>
                        <!-- Delete Finished Errand Button -->
                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this errand?');">
                            <input type="hidden" name="delete_finished_errand_id" value="<?= $errand['id']; ?>">
                            <button type="submit" class="btn-delete"><i class="fa-regular fa-trash-can"></i>Delete</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Users Details -->
            <div class="users-container">
                <!-- Client Card Details Container -->
                <div class="user-details-container">
                    <h1>Created By</h1>

                    <div class="user-profile-photo">
                        <a href="<?= htmlspecialchars($client_picture); ?>" target="_blank">
                            <img src="<?= htmlspecialchars($client_picture); ?>" alt="Profile Picture">
                        </a>
                    </div>

                    <div class="group-info-box">
                        <div class="info-box">
                            <label>User ID</label>
                            <input type="text" readonly value="<?= htmlspecialchars($errand['client_id']); ?>">
                        </div>

                        <div class="info-box">
                            <label>Email</label>
                            <input type="text" readonly value="<?= htmlspecialchars($errand['client_email']); ?>">
                        </div>
                    </div>

                    <div class="group-info-box">
                        <div class="info-box">
                            <label>Name</label>
                            <input type="text" readonly value="<?= htmlspecialchars(trim($errand['client_first_name'] . ' ' . $errand['client_middle_name'] . ' ' . $errand['client_last_name'])); ?>">
                        </div>

                        <div class="info-box">
                            <label>Contact Number</label>
                            <input type="text" readonly value="<?= htmlspecialchars($errand['client_contact_number']); ?>">
                        </div>
                    </div>
                </div>

                <!-- Tasker Details Container -->
                <div class="user-details-container">
                    <?php if (!empty($errand['tasker_id'])): ?>
                        <h1>Accepted By</h1>

                        <div class="tasker-header-row">
                            <div class="user-profile-photo">
                                <a href="<?= htmlspecialchars($tasker_picture); ?>" target="_blank">
                                    <img src="<?= htmlspecialchars($tasker_picture); ?>" alt="Profile Picture">
                                </a>
                            </div>

                            <?php if ($errand['status'] === 'pending_payment' && isset($errand['cancel_requested_by_tasker']) && $errand['cancel_requested_by_tasker'] == 1): ?>
                                <div class="tasker-cancellation-alert-box">
                                    <p class="alert-title">
                                        <i class="fa-solid fa-triangle-exclamation"></i> Requesting Cancellation
                                    </p>
                                    <div class="alert-action-buttons">
                                        <form method="POST" onsubmit="return confirm('Approve tasker release? This removes the current tasker but keeps the client payment receipt intact so a new tasker can claim this errand.');">
                                            <input type="hidden" name="approve_cancellation_id" value="<?= $errand['id']; ?>">
                                            <button type="submit" class="btn-approve-release">
                                                <i class="fa-solid fa-user-minus"></i> Approve
                                            </button>
                                        </form>

                                        <form method="POST" onsubmit="return confirm('Deny tasker release? This forces the tasker to stay locked into the commitment.');">
                                            <input type="hidden" name="deny_cancellation_id" value="<?= $errand['id']; ?>">
                                            <button type="submit" class="btn-deny-release">
                                                <i class="fa-solid fa-user-check"></i> Deny
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="group-info-box">
                            <div class="info-box">
                                <label>User ID</label>
                                <input type="text" readonly value="<?= htmlspecialchars($errand['tasker_id']); ?>">
                            </div>

                            <div class="info-box">
                                <label>Email</label>
                                <input type="text" readonly value="<?= htmlspecialchars($errand['tasker_email']); ?>">
                            </div>
                        </div>    

                        <div class="group-info-box">
                            <div class="info-box">
                                <label>Name</label>
                                <input type="text" readonly value="<?= htmlspecialchars(trim($errand['tasker_first_name'].' '.$errand['tasker_middle_name'].' '.$errand['tasker_last_name'])); ?>">
                            </div>

                            <div class="info-box">
                                <label>Contact Number</label>
                                <input type="text" readonly value="<?= htmlspecialchars($errand['tasker_contact_number']); ?>">
                            </div>
                        </div>

                    <?php else: ?>
                        <div class="not-accepted-yet">
                            <p>Not Accepted Yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
