<?php
session_start();
if (!isset($_SESSION['user_email'])) {
    header("Location: index.php");
    exit();
}

include 'connection.php';

// Verify admin status
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

// Fetch all titles for the dropdown - only announcements added by the current user
$titles = [];
$titleStmt = $conn->prepare("SELECT title FROM announcements WHERE added_by = ?");
$titleStmt->bind_param("s", $email);
$titleStmt->execute();
$titleStmt->bind_result($title);
while ($titleStmt->fetch()) {
    $titles[] = $title;
}
$titleStmt->close();

// Fetch selected announcement details if a title is submitted
$selectedAnnouncement = [];
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['data_item'])) {
    $selectedTitle = $_POST['data_item'];
    $addedBy = $_SESSION['user_email'];
    
    $stmt = $conn->prepare("SELECT title, content, image, start_date, end_date FROM announcements WHERE title = ? AND added_by = ?");
    $stmt->bind_param("ss", $selectedTitle, $addedBy);
    $stmt->execute();
    $stmt->bind_result($selectedAnnouncement['title'], $selectedAnnouncement['content'], $selectedAnnouncement['image'], $selectedAnnouncement['start_date'], $selectedAnnouncement['end_date']);
    $stmt->fetch();
    $stmt->close();
}

// Update the announcement when "Update Announcement" is clicked
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $currentTitle = $_POST['current_title'];
    $newTitle = $_POST['new_title'];
    $newContent = $_POST['content'];
    $newImage = $_POST['image'];
    $newStartDate = $_POST['start_date'];
    $newEndDate = $_POST['end_date'];
    $addedBy = $_SESSION['user_email'];

    // Prepare update statement with added_by check
    $stmt = $conn->prepare("UPDATE announcements SET title = ?, content = ?, image = ?, start_date = ?, end_date = ? WHERE title = ? AND added_by = ?");
    if ($stmt) {
        $stmt->bind_param("sssssss", $newTitle, $newContent, $newImage, $newStartDate, $newEndDate, $currentTitle, $addedBy);
        if ($stmt->execute()) {
            echo "<script>alert('Announcement updated successfully.');</script>";
        } else {
            echo "<script>alert('Error updating announcement: " . $stmt->error . "');</script>";
        }
        $stmt->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Announcement</title>
    <link rel="stylesheet" href="inde.css">
    <link rel="stylesheet" href="admin_panel.css">
    <link rel="stylesheet" href="add_subject.css">
    <script>
        function validateSelection() {
            const dataItem = document.getElementById("data_item").value;
            if (!dataItem) {
                alert("Please select an announcement before editing.");
                return false;
            }
            return true;
        }
    </script>
    <style>
        .form-group .sdata {
            font-size:1em;
        }
        .form-group .sdata1 {
            font-size:0.8em;
        }
        .button-container {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 10px;
        }
        .btn-edit-news, .btn-return-admin {
            padding: 8px 15px;
            font-size: 16px;
            text-decoration: none;
            color: #fff;
            background-color: #007bff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-align: center;
        }
        .btn-return-admin {
            background-color: #6c757d;
        }
        .btn-edit-news:hover, .btn-return-admin:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <?php include "admin_nav.php"; ?>
    <div class="ap_container">
        <h1>Edit Announcement</h1>

        <!-- Form for selecting announcement title -->
        <form method="POST" id="selectForm" class="form">
            <div class="form-group">
                <label for="data_item">Select Title:</label>
                <select name="data_item" class="sdata" id="data_item" class="form-control" onchange="document.getElementById('selectForm').submit();">
                    <option value="" class="sdata1">Select a title</option>
                    <?php foreach ($titles as $title): ?>
                        <option value="<?php echo htmlspecialchars($title); ?>" <?php echo isset($selectedTitle) && $selectedTitle == $title ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>

        <div class="soption" style="margin-top: 20px;">
            <!-- Update announcement form -->
            <form method="POST" class="form" onsubmit="return validateSelection();">
                <?php if (!empty($selectedAnnouncement)): ?>
                    <input type="hidden" name="current_title" value="<?php echo htmlspecialchars($selectedAnnouncement['title']); ?>">
                    <div class="form-group">
                        <label for="new_title">New Title:</label>
                        <input type="text" id="new_title" name="new_title" required class="form-control" value="<?php echo htmlspecialchars($selectedAnnouncement['title']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="content">Content:</label>
                        <textarea id="content" name="content" required class="form-control"><?php echo htmlspecialchars($selectedAnnouncement['content']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="image">Image URL:</label>
                        <input type="text" id="image" name="image" class="form-control" value="<?php echo htmlspecialchars($selectedAnnouncement['image']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="start_date">Start Date:</label>
                        <input type="date" id="start_date" name="start_date" class="form-control" required value="<?php echo htmlspecialchars($selectedAnnouncement['start_date']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="end_date">End Date:</label>
                        <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($selectedAnnouncement['end_date']); ?>">
                    </div>
                <?php endif; ?>
                <div class="button-container">
                    <input type="submit" name="update" value="Update Announcement" class="btn-edit-news">
                    <a href="admin_panel.php" class="back btn-return-admin">Return to Admin</a>
                </div>
            </form>
        </div>
    </div>
    <?php include "footer.php"; ?>
</body>
</html>