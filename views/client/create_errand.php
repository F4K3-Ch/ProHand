<?php
    session_start();

    // 1. Load required classes and controllers
    require_once "../../src/Classes/Database.php";
    require_once "../../src/Classes/User.php";
    require_once "../../src/Classes/Auth.php";
    require_once "../../src/Classes/Client.php";
    require_once "../../src/Controllers/ClientController.php";

    // 2. Initialize connection and controllers
    $database = new Database();
    $db = $database->getConnection();
    $clientController = new ClientController($db);

    // 3. Initialize variables to prevent "Undefined Variable" warnings
    $title = $description = $deadline_input = $contact = $location = $fee_input = '';
    $error = $_SESSION['error'] ?? ''; 

    // 4. Handle Create Errand action
    // On GET: it returns empty data. On POST: it processes the errand.
    $formData = $clientController->handleCreateErrand($_POST, $_FILES);

    // 5. If the controller returned data (because of a validation error), use it
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_array($formData)) {
        $title = $formData['title'];
        $description = $formData['description'];
        $deadline_input = $formData['deadline'];
        $contact = $formData['contact'];
        $location = $formData['location'];
        $fee_input = $formData['fee'];
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include '../../includes/web_logo.php'; ?>
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css' rel='stylesheet'> 
    <link rel="stylesheet" href="../../public/assets/style/style.css">
    <title>ProHand</title>
</head>
<body>
    <!-- Sidebar -->
    <?php include '../../includes/sidebar.php'; ?>

    <!-- Back button -->
    <a href="client_dashboard.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i>Back</a>

    <!-- View Main Content -->
    <main class="view-main">
        <div class="create-errand-container">
            <h1>Create Errand</h1>

            <!-- Error Message -->
            <div class="create-errand-error-success-message">
                <?php if (!empty($error)): ?>
                    <p class="error"><?= htmlspecialchars($error); ?></p>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <div class="create-errand-input-box">
                    <label>Title</label>
                    <input type="text" name="title" value="<?= htmlspecialchars($title) ?>" required>
                </div>

                <div class="create-errand-input-box">
                    <label>Description</label>
                    <textarea name="description" required><?= htmlspecialchars($description) ?></textarea>
                </div>

                <div class="create-errand-input-box">
                    <label>Deadline</label>
                    <input type="text" name="deadline" id="deadline" value="<?= htmlspecialchars($deadline_input) ?>" required>
                </div>

                <div class="create-errand-input-box">
                    <label>Contact</label>
                    <textarea name="contact" placeholder="Phone, email, social media..." required><?= htmlspecialchars($contact) ?></textarea>
                </div>

                <div class="create-errand-input-box">
                    <label>Location</label>
                    <input type="text" name="location" value="<?= htmlspecialchars($location) ?>" required>
                </div>
                
                <div class="create-errand-input-box">
                    <label>Fee (₱)</label>
                    <input type="number" step="0.01" name="fee" value="<?= htmlspecialchars($fee_input) ?>" required>
                </div>

                <div class="create-errand-input-box">
                    <label>Attachment (optional)</label>
                    <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.gif,.docx">
                    <small>Only 1 file allowed. Maximum size: 10 MB</small>
                </div>

                <button type="submit" class="btn">Create Errand</button>
            </form>
        </div>
    </main>

    <script>
        // Initialize Flatpickr on the input with id="deadline"
        flatpickr("#deadline", {
            enableTime: true,             // Allow the user to select a time in addition to the date
            dateFormat: "F j, Y h:i K",   // Format the displayed date/time:
                                        // F = full month name (January, February…)
                                        // j = day of the month (1–31)
                                        // Y = full year (2025)
                                        // h = hour (12-hour format)
                                        // i = minutes (00–59)
                                        // K = AM/PM
            minDate: "today",             // Disable selection of past dates; only today or future dates allowed
            time_24hr: false               // Use 12-hour time format with AM/PM instead of 24-hour format
        });
    </script>
</body>
</html>