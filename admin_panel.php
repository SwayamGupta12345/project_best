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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="inde.css">
    <link rel="stylesheet" href="admin_panel.css">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
</head>
<body>
    <?php include 'admin_nav.php' ?>

<div class="card-container">
    <a class="card" href="add_subject.php"><h3>Add New Subject</h3></a>
    <a class="card" href="edit_subject.php"><h3>Edit a Subject</h3></a>
    <a class="card" href="delete_subject.php"><h3>Delete a Subject</h3></a>
    <a class="card" href="add_link.php"><h3>Add a Data Link</h3></a>
    <a class="card" href="edit_link.php"><h3>Edit a Data Link</h3></a>
    <a class="card" href="delete_link.php"><h3>Delete a Data Link</h3></a>
    <a href="add_announcements.php" class="card"><h3>Add New Announcement</h3></a>
    <a href="edit_announcements.php" class="card"><h3>Edit Announcement</h3></a>
    <a href="delete_announcements.php" class="card"><h3>Delete Announcement</h3></a>
    <a class="card" href="add_news.php"><h3>Add News</h3></a>
    <a class="card" href="edit_news.php"><h3>Edit News</h3></a>
    <a class="card" href="delete_news.php"><h3>Delete News</h3></a>
</div>

<?php include "footer.php"; ?>
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

<?php
$conn->close();
?>