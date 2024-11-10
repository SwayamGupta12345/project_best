<?php
session_start();
if (!isset($_SESSION['user_email'])) {
    header("Location: index.php");
    exit();
}

include 'connection.php';

// AJAX request to fetch news details
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['fetch_data'])) {
    $selectedNews = $_POST['fetch_data'];
    $email = $_SESSION['user_email'];

    // Fetch the news details for the selected item
    $stmt = $conn->prepare("SELECT data, link, end_date FROM news WHERE data = ? AND added_by = ?");
    $stmt->bind_param("ss", $selectedNews, $email);
    $stmt->execute();
    $stmt->bind_result($data, $link, $end_date);
    $stmt->fetch();

    // Return JSON response
    echo json_encode(['data' => $data, 'link' => $link, 'end_date' => $end_date]);
    $stmt->close();
    exit();
}

// Verify if the user is an admin
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

// Fetch all news data for the dropdown - only news added by the current user
$newsItems = [];
$newsStmt = $conn->prepare("SELECT data FROM news WHERE added_by = ?");
$newsStmt->bind_param("s", $email);  // Bind current user email
$newsStmt->execute();
$newsStmt->bind_result($newsData);
while ($newsStmt->fetch()) {
    $newsItems[] = $newsData;
}
$newsStmt->close();

// Handle deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['data_item'])) {
    $dataToDelete = $_POST['data_item'];
    $added_By = $_SESSION['user_email'];
    $stmt = $conn->prepare("DELETE FROM news WHERE data = ? and added_by = ?");
    if ($stmt) {
        $stmt->bind_param("ss", $dataToDelete, $added_By);
        if ($stmt->execute()) {
            echo "<script>alert('News deleted successfully.');</script>";
        } else {
            echo "<script>alert('Error deleting news: " . $stmt->error . "');</script>";
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Delete News</title>
    <link rel="stylesheet" href="inde.css">
    <link rel="stylesheet" href="admin_panel.css">
    <link rel="stylesheet" href="add_subject.css"> 
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <!-- Include jQuery for AJAX -->
    <style>
        .display{
            display: none; 
            margin-top: 25px;
        }
        .display h3{
            font-weight: bold;
            font-size:1.6em;
            margin-top: -20px;
        }
        .sdata{
            font-size:1em;
        }
        .sdata1{
            font-size:0.8em;
        }
    </style>
</head>
<body>
    <?php include "admin_nav.php"; ?>
    <div class="ap_container">
        <h1>Delete News</h1>
        
        <form method="POST" id="deleteForm" class="form" onsubmit="return confirm('Are you sure you want to delete this announcement?');">
            <label for="data_item">Select News Data to Delete:</label>
            <select class="sdata" name="data_item" id="data_item" required><!-- sdata means select tag class -->
                <option value="">Select a news item</option>
                <?php foreach ($newsItems as $newsData): ?>
                    <option class="sdata1" value="<?php echo htmlspecialchars($newsData); ?>"><?php echo htmlspecialchars($newsData); ?></option>
                <?php endforeach; ?>
            </select>
            <!-- Display the details of the selected news item -->
            <div id="newsDetails" class="display">
                <h3>News Details</h3>
                <p><strong>Data:</strong> <span id="newsData"></span></p>
                <p><strong>Link:</strong> <span id="newsLink"></span></p>
                <p><strong>End Date:</strong> <span id="newsEndDate"></span></p>
            </div>
            <div class="soption">
                <input type="submit" value="Delete News">
                <a href="admin_panel.php" class="back">Return to Admin</a>
            </div>
        </form>
    </div>

    <script>
        $(document).ready(function() {
            $('#data_item').change(function(event) {
                event.preventDefault(); // Prevent form submission
                const selectedNews = $(this).val();
                if (selectedNews) {
                    $.ajax({
                        type: 'POST',
                        url: '',  // Current page URL
                        data: { fetch_data: selectedNews },
                        success: function(response) {
                            const newsDetails = JSON.parse(response);
                            $('#newsData').text(newsDetails.data);
                            $('#newsLink').text(newsDetails.link);
                            $('#newsEndDate').text(newsDetails.end_date);
                            $('#newsDetails').show(); // Show the details section
                        }
                    });
                } else {
                    $('#newsDetails').hide();
                }
            });
        });
    </script>
    <?php include 'footer.php' ?>
</body>
</html>