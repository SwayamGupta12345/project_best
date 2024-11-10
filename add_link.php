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

// Initialize variables for semester and subject
$selectedSem = $selectedSubject = "";
$selectedSubjects = [];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sem = (int)$_POST['link_sem'];
    $subject = $conn->real_escape_string($_POST['subject']);
    $description = $conn->real_escape_string($_POST['link_description']);
    $link = $conn->real_escape_string($_POST['link']);
    $type = $conn->real_escape_string($_POST['type']);

    // Check if the semester is in a valid range
    if ($sem < 1 || $sem > 10) {
        echo "<script>alert('Semester must be between 1 and 10.');</script>";
    } else {
        // Convert Google Drive link to a downloadable link if it matches the typical pattern
        if (preg_match('/drive\.google\.com\/file\/d\/([^\/]+)\/view/', $link, $matches)) {
            $file_id = $matches[1];
            $link = "https://drive.google.com/uc?export=download&id=" . $file_id;
        }

        // Construct the semester table name
        $semTable = "sem" . $sem;

        // Insert into the appropriate semester table
        $sql = "INSERT INTO $semTable (subject, description, link, type,added_by) VALUES ('$subject', '$description', '$link', '$type','$email')";
        
        if ($conn->query($sql) === TRUE) {
            echo "<script>alert('Link added successfully.');</script>";
            header("Location: add_link.php");
            exit();
        } else {
            echo "<script>alert('Error inserting link: " . $conn->error . "');</script>";
        }
    }
}

// Fetch subjects based on semester for AJAX
if (isset($_GET['sem'])) {
    $sem = (int)$_GET['sem'];
    $subject_query = $conn->query("SELECT subject FROM cards WHERE sem = $sem");
    $subjects = [];
    while ($row = $subject_query->fetch_assoc()) {
        $subjects[] = $row['subject'];
    }
    echo json_encode($subjects);
    exit();
}

// Preload subjects if a semester is selected
if (isset($_POST['link_sem'])) {
    $selectedSem = (int)$_POST['link_sem'];
    $subject_query = $conn->query("SELECT subject FROM cards WHERE sem = $selectedSem");
    while ($row = $subject_query->fetch_assoc()) {
        $selectedSubjects[] = $row['subject'];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Link</title>
    <link rel="stylesheet" href="inde.css">
    <link rel="stylesheet" href="admin_panel.css">
    <link rel="stylesheet" href="add_subject.css">  
    <script>
        // Function to load subjects based on the selected semester
        function loadSubjects() {
            var sem = document.getElementById('link_sem').value;
            var subjectDropdown = document.getElementById('subject');
            subjectDropdown.innerHTML = '<option>Loading...</option>';  // Show loading text

            // Send AJAX request to fetch subjects based on semester
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'add_link.php?sem=' + sem, true);
            xhr.onload = function() {
                if (this.status == 200) {
                    var subjects = JSON.parse(this.responseText);
                    subjectDropdown.innerHTML = ''; // Clear previous options
                    if (subjects.length > 0) {
                        subjects.forEach(function(subject) {
                            var option = document.createElement('option');
                            option.value = subject;
                            option.textContent = subject;
                            subjectDropdown.appendChild(option);
                        });
                    } else {
                        subjectDropdown.innerHTML = '<option>No subjects available</option>';
                    }
                }
            };
            xhr.send();
        }

        // Preload selected semester and subjects when the page loads
        window.onload = function() {
            var sem = "<?php echo $selectedSem; ?>";
            if (sem) {
                document.getElementById('link_sem').value = sem;
                loadSubjects();
                document.getElementById('subject').value = "<?php echo $selectedSubject; ?>";
            }
        };

        // Function to confirm form submission
        function confirmSubmission() {
            const semValue = document.getElementById('link_sem').value;
            if (semValue < 1 || semValue > 10) {
                alert("Semester must be between 1 and 10.");
                return false;
            }
            return confirm("Are you sure you want to add this link?");
        }
    </script>
</head>
<body>
    <?php include "admin_nav.php"?>
    <div class="ap_container">
        <h1>Add a Data Link</h1>
        <form method="POST" onsubmit="return confirmSubmission()">
            <label for="link_sem"><b>Semester:</b></label>
            <select id="link_sem" name="link_sem" onchange="loadSubjects()" required>
                <option value="">Select Semester</option>
                <?php for ($i = 1; $i <= 10; $i++): ?>
                    <option value="<?= $i ?>" <?= ($i == $selectedSem) ? 'selected' : '' ?>><?= $i ?></option>
                <?php endfor; ?>
            </select>

            <label for="subject"><b>Subject:</b></label>
            <select id="subject" name="subject" required>
                <option value="">Select Subject</option>
                <?php if (!empty($selectedSubjects)): ?>
                    <?php foreach ($selectedSubjects as $subject): ?>
                        <option value="<?= $subject ?>" <?= ($subject == $selectedSubject) ? 'selected' : '' ?>><?= $subject ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>

            <label for="link_description"><b>Description:</b></label>
            <input type="text" id="link_description" name="link_description" required>

            <label for="link"><b>Link:</b></label>
            <input type="url" id="link" name="link" required>

            <label for="type"><b>Type:</b></label>
            <select id="type" name="type" required>
                <option value="">Select Type</option>
                <option value="college">College</option>
                <option value="youtube">YouTube</option>
                <option value="books">Books</option>
                <option value="other">Other</option>
            </select>

            <div class="soption">
                <input type="submit" value="Add Link">
                <a href="admin_panel.php" class="back">Return to Admin</a>
            </div>
        </form>
    </div>
    <?php include "footer.php"?>
</body>
</html>