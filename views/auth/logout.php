<?php
    session_start();

    require_once "../../src/Classes/Database.php";
    require_once "../../src/Classes/User.php";
    require_once "../../src/Classes/Auth.php";
    require_once "../../src/Controllers/AuthController.php";

    // Initialize Database
    $database = new Database();
    $db = $database->getConnection();

    // Initialize the Controller
    $controller = new AuthController($db);
    $controller->logout();