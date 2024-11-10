<?php
session_start();
if (!isset($_SESSION['user_email'])) {
    header("Location: index.php");
    exit();
}

include 'connection.php';

$email = $_SESSION['user_email'];
$stmt = $conn->prepare("SELECT is_admin FROM admin WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->bind_result($isAdmin);
    $stmt->fetch();
    if (!$isAdmin) {
        header("Location: index.php");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
$stmt->close();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $image = $_POST['image'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $added_By = $_SESSION['user_email'];
    // Check if an announcement with the same title already exists
    $check_stmt = $conn->prepare("SELECT COUNT(*) FROM announcements WHERE title = ? and added_by = ?");
    $check_stmt->bind_param("ss", $title, $added_By);
    $check_stmt->execute();
    $check_stmt->bind_result($count);
    $check_stmt->fetch();
    $check_stmt->close();

    if ($count > 0) {
        echo "<script>alert('An announcement with this title already exists.');</script>";
    } else {
        // Insert the new announcement if no duplicate title is found
        $stmt = $conn->prepare("INSERT INTO announcements (title, content, image, start_date, end_date, added_by) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssssss", $title, $content, $image, $start_date, $end_date, $added_By);
            if ($stmt->execute()) {
                echo "<script>alert('Announcement added successfully.');</script>";
            } else {
                echo "<script>alert('Error adding announcement: " . $stmt->error . "');</script>";
            }
            $stmt->close();
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Announcement</title>
    <link rel="stylesheet" href="inde.css">
    <link rel="stylesheet" href="admin_panel.css">
    <link rel="stylesheet" href="add_subject.css"> 
</head>
<body>
    <?php include "admin_nav.php"; ?>
    <div class="ap_container">
        <h1>Add Announcement</h1>
        <form method="POST" class="form">
            <div class="form-group">
                <label for="title" class="form-control">Title:</label>
                <input type="text" id="title" name="title" required class="form-control">
            </div>
    
            <div class="form-group">
                <label for="content">Content:</label>
                <textarea id="content" name="content" required class="form-control"></textarea>
            </div>
    
            <div class="form-group">
                <label for="image">Image URL:</label>
                <input type="text" id="image" name="image" class="form-control">
            </div>
    
            <div class="form-group">
                <label for="start_date">Start Date:</label>
                <input type="date" id="start_date" name="start_date" class="form-control" required>
            </div>
    
            <div class="form-group">
                <label for="end_date">End Date:</label>
                <input type="date" id="end_date" name="end_date" class="form-control">
            </div>
    
            <div class="soption">
                    <input type="submit" value="Add Announcement">
                    <a href="admin_panel.php" class="back"> Return to Admin</a>
            </div>
        </form>
    </div>
    <?php include "footer.php"; ?>
</body>
</html>