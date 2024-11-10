<?php 
session_start();
if (!isset($_SESSION['user_email'])) {
    header("Location: index.php");
    exit();
}

include 'connection.php';

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

// Fetch semesters and subjects from `cards` table
$semesters = [1, 2, 3]; // Adjust based on available semesters
$subjects = [];
$subStmt = $conn->prepare("SELECT DISTINCT subject FROM cards WHERE sem = ?");
foreach ($semesters as $sem) {
    $subStmt->bind_param("i", $sem);
    $subStmt->execute();
    $subStmt->bind_result($subject);
    while ($subStmt->fetch()) {
        $subjects[$sem][] = $subject;
    }
}
$subStmt->close();

// Handle AJAX request to fetch data for editing
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['fetch_data'])) {
    $sem = $_POST['sem'];
    $subject = $_POST['subject'];
    $type = $_POST['type'];
    $table = "sem" . intval($sem);

    $stmt = $conn->prepare("SELECT id, description, link FROM $table WHERE subject = ? AND type = ? AND added_by = ?");
    $stmt->bind_param("sss", $subject, $type, $email);
    $stmt->execute();
    $stmt->bind_result($id, $description, $link);
    $dataItems = [];
    while ($stmt->fetch()) {
        $dataItems[] = ['id' => $id, 'description' => $description, 'link' => $link];
    }
    echo json_encode($dataItems);
    $stmt->close();
    exit();
}

// Handle update operation on selected data
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_item'])) {
    $sem = $_POST['sem'];
    $subject = $_POST['subject'];
    $type = $_POST['type'];
    $id = $_POST['data_id']; // Unique ID of data item
    $description = $_POST['description'];
    $link = $_POST['link'];
    $table = "sem" . intval($sem);

    $stmt = $conn->prepare("UPDATE $table SET description = ?, link = ? WHERE id = ? AND added_by = ?");
    $stmt->bind_param("ssis", $description, $link, $id, $email);
    if ($stmt->execute()) {
        echo "<script>alert('Data updated successfully.');</script>";
    } else {
        echo "<script>alert('Error updating data: " . $stmt->error . "');</script>";
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Data</title>
    <link rel="stylesheet" href="inde.css">
    <link rel="stylesheet" href="admin_panel.css">
    <link rel="stylesheet" href="add_subject.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <?php include "admin_nav.php"; ?>
    <div class="ap_container">
        <h1>Edit Data</h1>

        <form method="POST" id="editForm" class="form">
            <div class="form-group">
                <label for="sem">Select Semester:</label>
                <select name="sem" id="sem" required>
                    <option value="">Select a semester</option>
                    <?php foreach ($semesters as $sem): ?>
                        <option value="<?php echo $sem; ?>"><?php echo "Semester $sem"; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="subject">Select Subject:</label>
                <select name="subject" id="subject" required>
                    <option value="">Select a subject</option>
                </select>
            </div>

            <div class="form-group">
                <label for="type">Select Type:</label>
                <select name="type" id="type" required>
                    <option value="">Select a type</option>
                    <option value="college">College Resources</option>
                    <option value="youtube">YouTube Resources</option>
                    <option value="other">Other Resources</option>
                    <option value="book">Book Resources</option>
                </select>
            </div>

            <div id="dataList" class="display" style="display:none;">
                <label for="data_id">Select Data to Edit:</label>
                <select name="data_id" id="data_id" required>
                    <option value="">Select an item</option>
                </select>
            </div>

            <!-- Editable Fields -->
            <div id="editFields" style="display:none;">
                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea id="description" name="description" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label for="link">Link:</label>
                    <input type="url" id="link" name="link" required>
                </div>
            </div>

            <div class="soption">
                <input type="submit" name="update_item" value="Update Data">
                <a href="admin_panel.php" class="back">Return to Admin</a>
            </div>
        </form>
    </div>

    <script>
        $(document).ready(function() {
            $('#sem').change(function() {
                const sem = $(this).val();
                $('#subject').prop('disabled', !sem).html('<option value="">Select a subject</option>');
                if (sem) {
                    $.each(<?php echo json_encode($subjects); ?>[sem], function(index, subject) {
                        $('#subject').append(new Option(subject, subject));
                    });
                }
                $('#dataList, #editFields').hide();
            });

            $('#subject, #type').change(function() {
                const sem = $('#sem').val();
                const subject = $('#subject').val();
                const type = $('#type').val();
                if (sem && subject && type) {
                    $.post('', { fetch_data: true, sem, subject, type }, function(response) {
                        try {
                            const dataItems = JSON.parse(response);
                            $('#data_id').html('<option value="">Select an item</option>');
                            $.each(dataItems, function(index, item) {
                                $('#data_id').append(new Option(item.description, item.id));
                            });
                            $('#dataList').show();
                        } catch (e) {
                            console.error("Failed to parse response:", response);
                        }
                    });
                }
            });

            $('#data_id').change(function() {
                const dataId = $(this).val();
                const sem = $('#sem').val();
                if (dataId) {
                    $.ajax({
                        url: "fetch_entry_details.php",
                        type: "GET",
                        data: { entry_id: dataId, sem: sem },
                        dataType: 'json',
                        success: function (data) {
                            if (data) {
                                $('#description').val(data.description);
                                $('#link').val(data.link);
                                $('#editFields').show();
                            }
                        }
                    });
                } else {
                    $('#editFields').hide();
                }
            });
        });
    </script>
</body>
</html>
