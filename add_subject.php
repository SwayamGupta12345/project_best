<?php
session_start();
if (!isset($_SESSION['user_email'])) {
    header("Location: index.php"); // Redirect to login page if not logged in
    exit();
}

include 'connection.php';

// Verify if user is admin
$email = $_SESSION['user_email'];
$stmt = $conn->prepare("SELECT is_admin FROM admin WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($isAdmin);
    $stmt->fetch();
    if (!$isAdmin) {
        header("Location: index.php"); // Redirect if not an admin
        exit();
    }
} else {
    header("Location: index.php"); // Redirect if no admin record found
    exit();
}

$stmt->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form input values
    $subject = $_POST['subject'];
    $image = $_POST['image'];
    $description = $_POST['description'];
    $sem = (int)$_POST['sem'];

    // Check if subject already exists
    $check_stmt = $conn->prepare("SELECT COUNT(*) FROM cards WHERE subject = ?");
    $check_stmt->bind_param("s", $subject);
    $check_stmt->execute();
    $check_stmt->bind_result($count);
    $check_stmt->fetch();
    $check_stmt->close();

    // Check if semester is in valid range
    if ($sem < 1 || $sem > 10) {
        echo "<script>alert('Semester must be between 1 and 10.');</script>";
    } else if ($count > 0) {
        echo "<script>alert('Subject already exists.');</script>";
    } else {
        // Insert into 'cards' table
        $stmt_cards = $conn->prepare("INSERT INTO cards (subject, image, description, sem,added_by) VALUES (?, ?, ?, ?,?)");
        if ($stmt_cards) {
            $stmt_cards->bind_param("sssis", $subject, $image, $description, $sem,$email);
            if ($stmt_cards->execute()) {
                echo "<script>alert('Data successfully inserted into cards table.');</script>";
                header("Location: add_subject.php");
                exit();
            } else {
                echo "<script>alert('Error inserting into cards: " . $stmt_cards->error . "');</script>";
            }
            $stmt_cards->close();
        }
    }
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Subject</title>
    <link rel="stylesheet" href="inde.css">
    <link rel="stylesheet" href="admin_panel.css">
    <link rel="stylesheet" href="add_subject.css">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    
    <script>
        function confirmSubmission() {
            const semValue = document.getElementById('sem').value;
            if (semValue < 1 || semValue > 10) {
                alert("Semester must be between 1 and 10.");
                return false;
            }
            return confirm("Are you sure you want to add this subject?");
        }
    </script>
</head>
<body>
    <?php include "admin_nav.php"?>
    <div class="ap_container">
        <h1>Add New Subject</h1>
        <form method="POST" onsubmit="return confirmSubmission();">
            <label for="subject">Subject:</label>
            <input type="text" id="subject" name="subject" required>

            <label for="image">Image URL:</label>
            <input type="text" id="image" name="image" required>

            <label for="description">Description:</label>
            <textarea id="description" name="description" required></textarea>

            <label for="sem">Semester:</label>
            <input type="number" id="sem" name="sem" min="1" max="10" required>

            <h3>Note: Check the details twice before submission.</h3>
            <div class="soption">
                <input type="submit" value="Add Link">
                <a href="admin_panel.php" class="back">Return to Admin</a>
            </div>
        </form>
    </div>
    <?php include "footer.php"?>
</body>
<script>
    // Toggle the menu visibility on burger menu click
    document.addEventListener("DOMContentLoaded", function() {
        const burger = document.getElementById('burger-menu');
        const navLinks = document.getElementById('nav-links');

        burger.addEventListener('click', function(event) {
            event.stopPropagation();
            navLinks.classList.toggle('show');
        });

        window.addEventListener('click', function(event) {
            if (!burger.contains(event.target) && !navLinks.contains(event.target)) {
                navLinks.classList.remove('show');
            }
        });
    });
</script>
</html>