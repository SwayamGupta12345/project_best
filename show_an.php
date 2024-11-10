<div class="an_container">
    <!-- Announcements Section -->
    <div class="announcements">
    <?php
    include 'connection.php';

    // Set timezone and get today's date
    date_default_timezone_set('America/New_York');
    $today = date('Y-m-d');

    // SQL query to fetch active announcements
    $sql = "SELECT title, content, image, start_date FROM announcements 
            WHERE start_date <= ? AND (end_date IS NULL OR end_date >= ?) 
            ORDER BY start_date DESC";

    // Prepare and execute the statement
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $today, $today);
    $stmt->execute();
    $result = $stmt->get_result();

    // Display announcements if available
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo '<div class="announcement">';
            echo '<div class="text-content">';
            echo '<h2>' . htmlspecialchars($row["title"]) . '</h2>';
            echo '<p>' . htmlspecialchars($row["content"]) . '</p>';
            echo '<p>Posted on: ' . htmlspecialchars($row["start_date"]) . '</p>';
            echo '</div>';
            if (!empty($row["image"])) {
                echo '<img src="' . htmlspecialchars($row["image"]) . '" alt="Image for ' . htmlspecialchars($row["title"]) . '">';
            }
            echo '</div>';
        }
    } else {
        echo "<p>No active announcements available.</p>";
    }
    ?>
</div>


    <!-- News Section -->
    <div id="news-section">
        <h3>News and Updates</h3>
        <?php
        // SQL query to fetch active news
        $sql_news = "SELECT data, link, end_date FROM news 
                     WHERE end_date >= ? 
                     ORDER BY end_date ASC";
        $stmt_news = $conn->prepare($sql_news);
        $stmt_news->bind_param('s', $today);
        $stmt_news->execute();
        $result_news = $stmt_news->get_result();

        // Display news if available
        if ($result_news->num_rows > 0) {
            while ($row = $result_news->fetch_assoc()) {
                echo '<div class="news-item">';
                echo '<p><a href="' . htmlspecialchars($row["link"]) . '" target="_blank">' . htmlspecialchars($row["data"]) . '</a></p>';
                echo '<p>Expires on: ' . htmlspecialchars($row["end_date"]) . '</p>';
                echo '</div>';
            }
        } else {
            echo "<p>No active news available.</p>";
        }

        // Close statements and connection
        $stmt->close();
        $stmt_news->close();
        $conn->close();?>
    </div>
    </div>
        

