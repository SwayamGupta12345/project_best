<?php
include 'connection.php';

// Admin email and plain text password
$email = 'rishugoyal@gmail.com';
$plainPassword = 'rishu@1234'; // Replace with your desired password

// Hash the password
$passwordHash = password_hash($plainPassword, PASSWORD_BCRYPT);

// Insert into the admin table
$stmt = $conn->prepare("INSERT INTO admin (email, password_hash) VALUES (?, ?)");
$stmt->bind_param("ss", $email, $passwordHash);
$stmt->execute();

echo "Admin account created successfully.";

$stmt->close();
$conn->close();
?>