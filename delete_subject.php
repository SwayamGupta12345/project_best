<?php
session_start();
if (!isset($_SESSION['user_email'])) {
    header("Location: index.php");
    exit();
}

include 'connection.php';

// Initialize variables for success and message
$success = false;
$message = "";

// Handle deletion confirmation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_delete']) && isset($_POST['sem']) && isset($_POST['id'])) {
    $sem = (int)$_POST['sem'];
    $subject = $_POST['id'];
    $email = $_SESSION['user_email'];  // Make sure the email is fetched from the session

    // Determine the semester table
    $semesterTable = "sem" . $sem;

    // Prepare and execute delete statements
    $stmt = $conn->prepare("DELETE FROM $semesterTable WHERE subject = ?");
    $stmt->bind_param("s", $subject);

    if ($stmt->execute()) {
        // Delete from the cards table with a prepared statement for the email to prevent SQL injection
        $stmt_cards = $conn->prepare("DELETE FROM cards WHERE subject = ? AND sem = ? AND added_by = ?");
        $stmt_cards->bind_param("sis", $subject, $sem, $email);  // Using the email from the session

        if ($stmt_cards->execute()) {
            $success = true;
            $message = "The subject has been successfully deleted.";
        } else {
            $message = "Error deleting subject from cards table: " . $stmt_cards->error;
        }
        $stmt_cards->close();
    } else {
        $message = "Error deleting subject from $semesterTable table: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch unique semesters for dropdown
$semesters = [];
$result = $conn->query("SELECT DISTINCT sem FROM cards ORDER BY sem ASC");
while ($row = $result->fetch_assoc()) {
    $semesters[] = $row['sem'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Subject</title>
    <link rel="stylesheet" href="inde.css">
    <link rel="stylesheet" href="admin_panel.css">
    <link rel="stylesheet" href="add_subject.css">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function loadSubjects(sem) {
            if (sem) {
                $.ajax({
                    url: "fetch_subjects.php",
                    type: "GET",
                    data: { sem: sem },
                    success: function (response) {
                        $('#subjectSelect').html(response);
                    },
                    error: function (xhr, status, error) {
                        console.error("AJAX Error: " + status + ": " + error);
                    }
                });
            } else {
                $('#subjectSelect').html('<option value="">Select a subject</option>');
            }
        }
    </script>
</head>
<body>
    <?php include "admin_nav.php"; ?>
    <div class="ap_container">
        <?php if ($success): ?>
            <h1>Subject Deleted</h1>
            <p><?php echo $message; ?></p>
            <a href="admin_panel.php" class="btn">Back to Admin Panel</a>
        <?php else: ?>
            <h1>Delete Subject</h1>
            <form method="POST">
                <div class="form-group">
                    <label for="sem">Select Semester:</label>
                    <select id="sem" name="sem" onchange="loadSubjects(this.value)">
                        <option value="">Select a semester</option>
                        <?php foreach ($semesters as $semester): ?>
                            <option value="<?php echo $semester; ?>"><?php echo $semester; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="subjectSelect">Select Subject:&nbsp;</label>
                    <select id="subjectSelect" name="id">
                        <option value="">Select a subject:&nbsp;</option>
                    </select>
                </div>
                <div class="soption">
                <input type="submit" value="Delete Subject">
                <a href="admin_panel.php" class="back">Return to Admin</a>
            </div>
            </form>
        <?php endif; ?>
    </div>
    <?php include "footer.php"; ?>
</body>
</html>

<?php
$conn->close();
?>
