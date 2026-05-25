<?php
    session_start();
    // Back out of the script folder first, then look for src/
    require_once __DIR__ . "/../src/Classes/Database.php";

    // 1. Initialize your new OOP Database class and get the raw connection
    $database = new Database();
    $conn = $database->getConnection();
    
    // How many users you want to create
    $num_users = 10;

    // Default role for generated users
    $role = "client";

    // Default profile picture
    $default_profile_picture = "/uploads/profile_pictures/default.jpg";

    // Sample lists for randomization
    $first_names  = ['John', 'Mary', 'Chris', 'Anna', 'Mark', 'Julia'];
    $last_names   = ['Doe', 'Smith', 'Garcia', 'Reyes', 'Cruz', 'Lopez'];
    $middle_names = ['A', 'B', 'C', 'D', 'E', 'F'];
    $contacts     = ['09171234567', '09223456789', '09334567890'];

    for ($i = 1; $i <= $num_users; $i++) {

        // Auto-generate details
        $first_name  = $first_names[array_rand($first_names)];
        $middle_name = $middle_names[array_rand($middle_names)];
        $last_name   = $last_names[array_rand($last_names)];

        $email = strtolower($first_name . "." . $last_name . $i . "@gmail.com");

        $contact_number = $contacts[array_rand($contacts)];

        $password = password_hash("123", PASSWORD_DEFAULT);

        // Insert user
        $stmt = $conn->prepare("
            INSERT INTO users (
                email, first_name, middle_name, last_name, password,
                profile_picture, contact_number, role
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "ssssssss",
            $email,
            $first_name,
            $middle_name,
            $last_name,
            $password,
            $default_profile_picture,
            $contact_number,
            $role
        );

        $stmt->execute();
        $stmt->close();
    }

    echo "$num_users test users created successfully.";
?>