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

    $errand_id = (int)($_GET['errand_id'] ?? 0);
    $data = $controller->viewErrandDetails($errand_id);

    // Handle "Accept" POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_id'])) {
        $controller->accept();
    }

    // Handle "In Process" POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_task_id'])) {
        $_POST['id'] = $_POST['start_task_id']; 
        $controller->updateStatusInProcess();
    }

    // --- ADD THIS: Handle "Completed" or "Failed" POST ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['final_status_id'])) {
        $_POST['id'] = $_POST['final_status_id'];
        // $_POST['new_status'] is passed from the button value
        $controller->updateStatusFinal();
    }

    if (!$data) {
        $_SESSION['error'] = "Errand not found.";
        header("Location: tasker_dashboard.php");
        exit;
    }

    extract($data); 
?>