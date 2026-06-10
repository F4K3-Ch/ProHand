<?php
    session_start();
    require_once "../../src/Classes/Database.php";
    require_once "../../src/Classes/User.php";
    require_once "../../src/Classes/Auth.php";
    require_once "../../src/Classes/Client.php";
    require_once "../../src/Controllers/ClientController.php";
    require_once "../../src/Controllers/SharedController.php";

    $database = new Database();
    $db = $database->getConnection();

    $clientController = new ClientController($db);
    $sharedController = new SharedController($db);

    // Standard Attachment Download Logic
    if (isset($_GET['action']) && $_GET['action'] === 'download' && isset($_GET['download_attachment_id'])) {
        $is_history_download = (isset($_GET['type']) && $_GET['type'] === 'history');
        
        $sharedController->handleDownload((int)$_GET['download_attachment_id'], $is_history_download ? 'history' : 'regular');
        
        $_SESSION['error'] = "File not found on server.";
    }

    // Proof of Payment Receipt Download Logic
    if (isset($_GET['action']) && $_GET['action'] === 'download_receipt' && isset($_GET['download_receipt_id'])) {
        $is_history_download = (isset($_GET['type']) && $_GET['type'] === 'history');
        
        $sharedController->handleReceiptDownload((int)$_GET['download_receipt_id'], $is_history_download ? 'history' : 'regular');
        
        $_SESSION['error'] = "Receipt file not found on server.";
    }

    // 1. Handle POST Actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['delete_id'])) {
            $_POST['id'] = $_POST['delete_id']; 
            $clientController->handleDeleteErrand(); 
        } elseif (isset($_POST['cancel_accepted_id'])) {
            $_POST['id'] = $_POST['cancel_accepted_id']; 
            $clientController->handleCancelAccepted(); 
        } elseif (isset($_POST['delete_history_id'])) {
            $_POST['id'] = $_POST['delete_history_id']; 
            $sharedController->handleDeleteHistory();
        } elseif (isset($_POST['submit_payment_proof'])) {
            $clientController->handlePaymentUpload();
        }
    }

    // 2. Fetch Errand Data
    $errand_id = (int)($_GET['errand_id'] ?? 0);
    $data = $clientController->viewErrandDetails($errand_id);

    if (!$data) {
        $_SESSION['error'] = "Errand not found.";
        header("Location: client_dashboard.php");
        exit;
    }

    extract($data); 
    // $errand, $attachments_result, $is_active, $is_finished, $back_url, $status_labels, $tasker_picture
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

                        <?php 
                            $download_action = "view_errand_info.php"; 
                        ?>

                        <?php if ($attachment_result && $attachment_result->num_rows > 0): ?>
                            <?php 
                                $file = $attachment_result->fetch_assoc(); 
                                $relative_path = '../../public/' . $file['file_path'];
                            ?>

                            <div class="attachment-item">
                                <a href="<?= htmlspecialchars($relative_path); ?>" target="_blank" class="attachment-name">
                                    <i class="fa-solid fa-file-lines"></i> <?= htmlspecialchars($file['original_name']); ?>
                                </a>

                                <form method="GET" action="<?= $download_action; ?>">
                                    <input type="hidden" name="errand_id" value="<?= $errand_id; ?>">
                                    
                                    <input type="hidden" name="action" value="download">
                                    
                                    <input type="hidden" name="download_attachment_id" value="<?= (int)$file['id']; ?>">
                                    
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
                    
                    <?php if (!empty($errand['payment_receipt_path']) && $errand['status'] === 'pending'): ?>
                        <?php 
                            $receipt_preview_path = '../../public/' . $errand['payment_receipt_path'];
                        ?>
                        <div class="info-box">
                            <label>Proof of Payment</label>
                            <div class="attachment-item">
                                <a href="<?= htmlspecialchars($receipt_preview_path); ?>" target="_blank" class="attachment-name">
                                    <i class="fa-solid fa-file-invoice-dollar"></i> <?= htmlspecialchars(basename($errand['payment_receipt_path'])); ?>
                                </a>

                                <form method="GET" action="<?= $download_action; ?>">
                                    <input type="hidden" name="errand_id" value="<?= $errand_id; ?>">
                                    
                                    <input type="hidden" name="action" value="download_receipt">
                                    
                                    <input type="hidden" name="download_receipt_id" value="<?= $errand_id; ?>">
                                    
                                    <?php if (isset($is_finished) && $is_finished): ?>
                                        <input type="hidden" name="type" value="history">
                                    <?php endif; ?>

                                    <button type="submit" class="btn-download">
                                        <i class="fa-solid fa-download"></i> Download
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>


                <div class="info-bot-btn">
                    <?php if ($errand['status'] === 'pending'): ?>
                        <!-- Edit -->
                        <form method="GET" action="edit_errand.php">
                            <input type="hidden" name="errand_id" value="<?= $errand['id']; ?>">
                            <button type="submit" class="btn-edit-errand"><i class="fa-regular fa-pen-to-square"></i>Edit</button>
                        </form>
                        <!-- Delete -->
                        <form method="POST"
                            onsubmit="return confirm('Are you sure you want to delete this errand request?');">
                            <input type="hidden" name="delete_id" value="<?= $errand['id']; ?>">
                            <button type="submit" class="btn-delete"><i class="fa-regular fa-trash-can"></i>Delete</button>
                        </form>
                    <?php elseif ($errand['status'] === 'pending_payment'): ?>
                        <!-- Edit Button Disabled-->
                        <button disabled class="btn-edit-errand-disabled"><i class="fa-regular fa-pen-to-square"></i>Edit</button>
                        <!-- Cancel Button-->
                        <form method="POST"
                            onsubmit="return confirm('Are you sure you want to cancel this errand? It will return to Pending.');">
                            <input type="hidden" name="cancel_accepted_id" value="<?= $errand['id']; ?>">
                            <button type="submit" class="btn-cancel"><i class="fa-solid fa-ban"></i>Cancel</button>
                        </form>
                    <?php elseif ($errand['status'] === 'in_process'): ?>
                        <!-- Edit Button Disabled-->
                        <button disabled class="btn-edit-errand-disabled"><i class="fa-regular fa-pen-to-square"></i>Edit</button>
                        <!-- Cancel Button Disabled-->
                        <button disabled class="btn-cancel-disabled"><i class="fa-solid fa-ban"></i>Cancel</button>
                    <?php elseif (in_array($errand['status'], ['completed', 'failed'])): ?>
                        <!-- Delete Button -->
                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this archived errand?');">
                            <input type="hidden" name="delete_history_id" value="<?= $errand['id']; ?>">
                            <button type="submit" class="btn-delete"><i class="fa-regular fa-trash-can"></i>Delete</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (in_array($errand['status'], ['pending_payment', 'in_process', 'completed', 'failed'])): ?>
                <!-- Users Details -->
                <div class="users-container">
                    <!-- Tasker Details Container -->
                    <div class="user-details-container">
                        <h1>Accepted By</h1>

                        <div class="user-profile-photo">
                            <a href="<?= htmlspecialchars($tasker_picture); ?>" target="_blank">
                                <img src="<?= htmlspecialchars($tasker_picture); ?>" alt="Profile Picture">
                            </a>
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
                    </div>

                    <div class="user-details-container">
                        <h1>Secure Payment</h1>

                        <?php if ($errand['status'] === 'pending_payment' && empty($errand['payment_receipt_path'])): ?>
                            <div class="info-box alert-box alert-blue payment-instruction-wrapper">
                                <div class="payment-qr-frame">
                                    <a href="../../public/assets/img/gcash-full-qr.jpg" target="_blank">
                                        <img src="../../public/assets/img/gcash-qr.jpg" alt="GCash Merchant QR Code" class="payment-qr-img">
                                    </a>
                                </div>
                                
                                <div class="payment-text-details">
                                    <p class="alert-text">
                                        <strong>GCash Payment Instructions:</strong><br>
                                        Scan the QR code or send the exact amount of <strong>₱<?= number_format($errand['fee'], 2); ?></strong> to our business wallet to securely lock in your tasker:
                                    </p>
                                    <p class="merchant-number">
                                        <i class="fa-solid fa-mobile-screen"></i> 0994-221-5875
                                    </p>
                                    <span class="qr-hint-text"><i class="fa-solid fa-qrcode"></i> Tip: Save QR to gallery to scan via GCash App</span>
                                </div>
                            </div>

                            <form method="POST" enctype="multipart/form-data" class="payment-upload-form">
                                <input type="hidden" name="errand_id" value="<?= $errand['id']; ?>">
                                
                                <div class="info-box create-errand-input-box">
                                    <label>Upload Receipt Image</label>
                                    <input type="file" name="gcash_receipt" accept=".jpg,.jpeg,.png" required>
                                    <small>Only PNG, JPG, or JPEG allowed. Maximum size: 10 MB</small>
                                </div>

                                <button type="submit" name="submit_payment_proof" class="btn btn-submit-proof">
                                    <i class="fa-solid fa-cloud-arrow-up"></i> Submit Proof of Payment
                                </button>
                            </form>

                        <?php elseif ($errand['status'] === 'pending_payment' && !empty($errand['payment_receipt_path'])): ?>
                            <?php 
                                $receipt_preview_path = '../../public/' . $errand['payment_receipt_path'];
                                $download_action = "view_errand_info.php";
                            ?>
                            <div class="payment-verification-panel">
                                <div class="payment-status-header">
                                    <i class="fa-solid fa-spinner fa-spin status-icon-orange"></i>
                                    <h2 class="status-title-orange">Pending Admin Verification</h2>
                                </div>

                                <p class="payment-status-message">
                                    <strong>Pending. Please wait until the admin checks your uploaded receipt.</strong> 
                                    Once verified, your tasker will be notified immediately to start your errand.
                                </p>

                                <div class="payment-receipt-section">
                                    <span class="receipt-section-label">Your Uploaded Receipt</span>
                                    
                                    <div class="attachment-item">
                                        <a href="<?= htmlspecialchars($receipt_preview_path); ?>" target="_blank" class="attachment-name">
                                            <i class="fa-solid fa-file-invoice-dollar"></i> <?= htmlspecialchars(basename($errand['payment_receipt_path'])); ?>
                                        </a>

                                        <form method="GET" action="<?= $download_action; ?>">
                                            <input type="hidden" name="errand_id" value="<?= $errand_id; ?>">
                                            <input type="hidden" name="action" value="download_receipt">
                                            <input type="hidden" name="download_receipt_id" value="<?= $errand_id; ?>">
                                            
                                            <?php if (isset($is_finished) && $is_finished): ?>
                                                <input type="hidden" name="type" value="history">
                                            <?php endif; ?>

                                            <button type="submit" class="btn-download">
                                                <i class="fa-solid fa-download"></i> Download
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="info-box transaction-status-box">
                                <i class="fa-solid fa-info-circle status-icon-muted"></i>
                                <label class="status-label-muted">Current Errand Status</label>
                                <p class="status-description-text">
                                    Current status: <strong><?= htmlspecialchars($errand['status']); ?></strong>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
