<?php
    session_start();

    // 1. Manually pull the classes (Path is ../../ because we are in views/auth/)
    require_once "../../src/Classes/Database.php";
    require_once "../../src/Classes/User.php";

    // 2. We don't actually need a DB connection just to clear a session,
    // but the User class expects a connection in its constructor.
    $user = new User(null); 

    // 3. Call the logout method from your User class
    $user->logout();

    // 4. Redirect back to the login page
    header("Location: login.php");
    exit;
?>