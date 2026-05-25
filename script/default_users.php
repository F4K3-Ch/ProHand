<?php
    session_start();
    // Back out of the script folder first, then look for src/
    require_once __DIR__ . "/../src/Classes/Database.php";

    // 1. Initialize your new OOP Database class and get the raw connection
    $database = new Database();
    $conn = $database->getConnection();
    // Default users to create
    $default_users = [
        [
            'email' => 'admin@gmail.com',
            'first_name' => 'Super',
            'middle_name' => '',
            'last_name' => 'Admin',
            'contact_number' => '09170000000',
            'role' => 'admin'
        ],
        [
            'email' => 'tasker@gmail.com',
            'first_name' => 'Kisuke',
            'middle_name' => '',
            'last_name' => 'Uruhara',
            'contact_number' => '09179876543',
            'role' => 'tasker'
        ],
        [
            'email' => 'client@gmail.com',
            'first_name' => 'Iries',
            'middle_name' => 'Fra',
            'last_name' => 'Ra',
            'contact_number' => '09171234567',
            'role' => 'client'
        ]
    ];

    $default_profile_picture = "uploads/profile_pictures/default.jpg";

    foreach ($default_users as $user) {
        $password_hashed = password_hash("123", PASSWORD_DEFAULT);

        // Insert user
        $stmt = $conn->prepare("
            INSERT INTO users (
                email, first_name, middle_name, last_name, password,
                profile_picture, contact_number, role
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "ssssssss",
            $user['email'],
            $user['first_name'],
            $user['middle_name'],
            $user['last_name'],
            $password_hashed,
            $default_profile_picture,
            $user['contact_number'],
            $user['role']
        );

        $stmt->execute();
        $stmt->close();
    }

    echo "3 default users created successfully.";
?>