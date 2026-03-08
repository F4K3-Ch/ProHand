<?php
    session_start();
    
    require_once "../../src/Classes/Database.php";
    require_once "../../src/Classes/User.php";
    require_once "../../src/Controllers/AuthController.php";

    $controller = new AuthController(null); // No DB needed for session_destroy
    $controller->logout();