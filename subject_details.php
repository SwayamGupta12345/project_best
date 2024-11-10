<?php
session_start();

// Redirect to index.php if not logged in or if user_type is neither 'admin' nor 'user'
if (!isset($_SESSION['user_email']) || !in_array($_SESSION["user_type"], ["admin", "user"])) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Details</title>  
    <link rel="stylesheet" href="subject_details.css">
    <link rel="stylesheet" href="inde.css">
    
</head>
<body>
<header>
    <div class="logo-text">
        <img src="jaypee_main_logo.jpeg" alt="Jaypee Learning Hub" class="logo">
        <h1>Jaypee Learning Hub</h1>
    </div>
</header>
<nav>
    <div class="burger" id="burger-menu">
        <div></div>
        <div></div>
        <div></div>
    </div>
    <a class="home" href="index1.php" >HOME</a>
        
    <?php if (isset($_SESSION['user_email'])): ?>
        <?php $user_name = explode('@', $_SESSION['user_email'])[0]; ?>
        <div class="user-dropdown">
            <span class="user-email" style="font-size:1.2em; letter-spacing:0.25px; border-radius:4px; font-weight:bold;" onclick="toggleDropdown()">
                <?php echo htmlspecialchars($user_name); ?>
            </span>
            <div class="dropdown-content" id="dropdown-content">
                <a href="index.php?logout=true">Logout</a>
            </div>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['user_email']) && $_SESSION['user_type'] === 'admin'): ?>
    <a href="admin_panel.php" class="admin">Admin panel</a>
<?php endif; ?>
</nav>

<?php

include 'connection.php';

// Display subject details
$subject = $_GET['subject'];
$sem = (int)$_GET['sem'];
$semTable = "sem".$sem;

$sql = "SELECT image, subject, description FROM cards WHERE sem = $sem AND subject = '$subject'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo '<h1 class="head">' . $row["subject"] . '</h1>';
    echo '<div class="container">';
    echo '<p>' . $row["description"] . '</p>';
    echo '<img src="' . $row["image"] . '" alt="Subject Image" class="image">';
} else {
    echo '<p>No data available for this subject.</p>';
}
echo '</div>';

// Display subject links by type
echo '<div class="flex">';
$types = ['college', 'youtube', 'other', 'books'];

foreach ($types as $type) {
    echo '<h3>' . ucfirst($type) . ' Resources</h3>';
    echo '<table class="subject-links">';
    echo '<tr><th>Description</th><th>Link</th></tr>';

    $link_sql = "SELECT description, link FROM $semTable WHERE subject = '$subject' AND type='$type'";
    $link_result = $conn->query($link_sql);

    if ($link_result->num_rows > 0) {
        while ($link_row = $link_result->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . $link_row["description"] . '</td>';
            echo '<td><a href="'.$link_row["link"].'">Download Link</a></td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="2">No links available for this subject.</td></tr>';
    }
    echo '</table>';
}
echo '</div>';

$conn->close();
?>

<?php include "footer.php"; ?>
</body>
</html>
