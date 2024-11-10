    <?php
    session_start();
    if (!isset($_SESSION['user_email'])) {
        header("Location: login.php");
        exit();
    }

    include 'connection.php';
    include 'check_admin.php';
    
    // Initialize variables
    $image = $subject = $description = $sem = "";

    // Handle form submission for updating entries
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $image = $conn->real_escape_string($_POST['image']);
        $subject = $conn->real_escape_string($_POST['subject']);
        $description = $conn->real_escape_string($_POST['description']);
        $sem = (int)$_POST['sem'];

        // Update the record in the database
        $sql = "UPDATE cards SET image='$image', subject='$subject', description='$description', sem='$sem' WHERE subject='$subject' and added_by='$email'";
        if ($conn->query($sql) === TRUE) {
            header("Location: edit_subject.php");
            exit();
        } else {
            echo "Error updating record: " . $conn->error;
        }
    }

    // Handle fetching subject details
    if (isset($_GET['subject'])) {
        $subject = $conn->real_escape_string($_GET['subject']);
        $stmt = $conn->prepare("SELECT * FROM cards WHERE subject = ? and added_by='$email'");
        $stmt->bind_param("s", $subject);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $data = $result->fetch_assoc();
            $image = $data['image'];
            $subject = $data['subject'];
            $description = $data['description'];
            $sem = $data['sem'];
        }

        $stmt->close();
    }
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Edit Subject</title>
        <link rel="stylesheet" href="inde.css">
        <link rel="stylesheet" href="admin_panel.css">
        <link rel="stylesheet" href="add_subject.css">
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

            function loadSubjectDetails(subject) {
                if (subject) {
                    $.ajax({
                        url: "edit_subject.php",
                        type: "GET",
                        data: { subject: subject },
                        success: function (data) {
                            const parsedData = $(data).find('.form')[0];
                            $('#image').val($(parsedData).find('[name="image"]').val());
                            $('#subject').val($(parsedData).find('[name="subject"]').val());
                            $('#description').val($(parsedData).find('[name="description"]').val());
                            $('#sem').val($(parsedData).find('[name="sem"]').val());
                        },
                        error: function (xhr, status, error) {
                            console.error("AJAX Error: " + status + ": " + error);
                        }
                    });
                } else {
                    $('#image').val('');
                    $('#subject').val('');
                    $('#description').val('');
                    $('#sem').val('');
                }
            }
        </script>
    </head>
    <body>
    <?php include "admin_nav.php"; ?>
    <div class="ap_container">
        <h1 class="form-title">Edit Subject</h1>
        <form method="POST" class="form">
            <div class="form-group">
                <label for="sem">Select Semester:&nbsp;</label>
                <select id="sem" name="sem" class="form-control" onchange="loadSubjects(this.value)">
                    <option value="">Select a semester</option>
                    <?php
                    $result = $conn->query("SELECT DISTINCT sem FROM cards ORDER BY sem ASC");
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='" . $row['sem'] . "'" . ($row['sem'] == $sem ? " selected" : "") . ">" . $row['sem'] . "</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="subjectSelect">Select Subject: &nbsp;</label>
                <select id="subjectSelect" name="subject" class="form-control" onchange="loadSubjectDetails(this.value)">
                
                <option value="" selected >Select a subject</option>
                    
                    <?php
                    if ($sem) {
                        $stmt = $conn->prepare("SELECT subject FROM cards WHERE sem = ? and added_by='$email'");
                        $stmt->bind_param("i", $sem);
                        $stmt->execute();
                        $result = $stmt->get_result();
                       
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . $row['subject'] . "'" . ($row['subject'] == $subject ? " selected" : "") . ">" . $row['subject'] . "</option>";
                        }
                        $stmt->close();
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="image">Image URL:</label>
                <input type="text" id="image" name="image" class="form-control" value="<?php echo $image; ?>">
            </div>

            <div class="form-group">
                <label for="subject">Subject Name:</label>
                <input type="text" id="subject" name="subject" class="form-control" value="<?php echo $subject; ?>">
            </div>

            <div class="form-group">
                <label for="description">Description:</label>
                <textarea type="text" id="description" 
                name="description" class="form-control" rows = "4" value="<?php echo $description; ?>"></textarea>
            </div>

            <h3>Note: Check the details twice before submission.</h3>
            <div class="soption">
                <input type="submit" value="Update Subject">
                <input href="admin_panel.php" class="back" value="Return to Admin">
            </div>
        </form>

    </div>
    <?php include "footer.php"; ?>
    </body>
    </html>

    <?php
    $conn->close();
    ?>
