<?php
session_start();
require_once "../shared/db.php";

// Set this to the client ID you want to assign the errands to
$client_id = 30; // your example user

// Set How many Errands you want to create
$num_errands = 3;

// Predefined statuses for variety
$statuses = ['pending'];

// Locations, contacts, and sample titles for variety
$locations = ['Makati', 'Taguig', 'Quezon City', 'Pasig', 'Manila'];
$contacts = ['09171234567', '09987654321', '09223334444'];
$titles = ['Buy groceries', 'Pick up documents', 'Send package', 'Repair computer', 'Clean house'];

for ($i = 1; $i <= $num_errands; $i++) {
    $title = $titles[array_rand($titles)] . " #" . $i;
    $description = "This is a test errand number $i for client ID $client_id.";
    $deadline = date('Y-m-d H:i:s', strtotime("+".rand(1,7)." days")); // 1-7 days from now
    $location = $locations[array_rand($locations)];
    $contact = $contacts[array_rand($contacts)];
    $fee = rand(50, 500); // random fee
    $status = $statuses[array_rand($statuses)];

    $stmt = $conn->prepare("
        INSERT INTO errands (client_id, title, description, deadline, contact, location, fee, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isssssss", $client_id, $title, $description, $deadline, $contact, $location, $fee, $status);
    $stmt->execute();
    $stmt->close();
}

echo "{$num_errands} test errands created successfully for client ID $client_id.";
?>
