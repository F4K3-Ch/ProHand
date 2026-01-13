<?php
    require_once "../../app/classes/auth.php";

    $auth = new Auth(null); // No DB connection needed for logout
    $auth->logout();

    /* Redirect */
    header("Location: login.php");
    exit;
