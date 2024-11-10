<?php
include 'connection.php';

// Check if 'sem' is set in the GET request and is a valid integer
if (isset($_GET['sem']) && is_numeric($_GET['sem'])) {
    $sem = (int)$_GET['sem'];
    
    // Prepare SQL query to fetch subjects from the cards table for the selected semester
    $stmt = $conn->prepare("SELECT subject FROM cards WHERE sem = ?");
    if ($stmt) {
        $stmt->bind_param("i", $sem);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Check if any subjects were found
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Output each subject as an option
                echo "<option value='" . htmlspecialchars($row['subject']) . "'>" . htmlspecialchars($row['subject']) . "</option>";
            }
        } else {
            echo "<option value=''>No subjects found for the selected semester</option>";
        }

        $stmt->close();
    } else {
        echo "<option value=''>Error preparing statement</option>";
    }
} else {
    echo "<option value=''>Invalid semester selected</option>";
}

// Close the database connection
$conn->close();
