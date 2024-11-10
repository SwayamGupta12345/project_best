<?php
session_start(); // This must be the very first thing in your script

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'connection.php';

// Handle user registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $email = $_POST['reg_email'];
    $password = $_POST['reg_password'];

    // Regular expression for stronger email validation
    $pattern = "/^(23[0-9]{6}|99(23|24|25|26|27|28|29)[0-9]{4}|JEG(23|24|25|26|27|28|29)[0-9]{4}|NRG(23|24|25|26|27|28|29)[0-9]{4}|ECN(23|24|25|26|27|28|29)[0-9]{4}|NJG(23|24|25|26|27|28|29)[0-9]{4})@mail\.jiit\.ac\.in$/";

    // Check if email matches the pattern
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match($pattern, $email)) {
        echo "<script>alert('Invalid email format. Please use an appropriate JIIT email address.');</script>";
    } else {
        // Check if the email already exists
        $checkEmailStmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $checkEmailStmt->bind_param("s", $email);
        $checkEmailStmt->execute();
        $checkEmailStmt->store_result();

        if ($checkEmailStmt->num_rows > 0) {
            // Email already exists, show an error message
            echo "<script>alert('This email is already registered. Please use a different email or login.');</script>";
        } else {
            // Hash the password
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);

            // Prepare and bind
            $stmt = $conn->prepare("INSERT INTO users (email, password_hash) VALUES (?, ?)");
            $stmt->bind_param("ss", $email, $passwordHash);

            if ($stmt->execute()) {
                echo "<script>alert('Registration successful! Now Login');</script>";
                header("Location: index.php");
            } else {
                echo "<script>alert('Error: " . $stmt->error . "');</script>";
            }

            $stmt->close();

        }

        $checkEmailStmt->close();
    }
}
// Handle user login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // First, check if the email exists in the admin table
    $stmt = $conn->prepare("SELECT password_hash FROM admin WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Admin found
        $stmt->bind_result($passwordHash);
        $stmt->fetch();

        // Verify the password for admin
        if (password_verify($password, $passwordHash)) {
            // Store session data for admin
            $_SESSION['user_email'] = $email;
            $_SESSION['user_type'] = "admin";
            
            header("Location: admin_panel.php");
            exit();
        } else {
            echo "<script>alert('Invalid admin credentials.');</script>";
        }
    } else {
        // If not found in admin, check the users table
        $stmt->close(); // Close the previous statement

        $stmt = $conn->prepare("SELECT password_hash FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // User found   
            $stmt->bind_result($passwordHash);
            $stmt->fetch();

            // Verify the password for user
            if (password_verify($password, $passwordHash)) {
                // Store session data for regular user
                $_SESSION['user_email'] = $email;
                $_SESSION['user_type'] = "user";
                header("Location: index1.php"); // Redirect to a regular page
                exit();
            } else {
                echo "<script>alert('Invalid user credentials.');</script>";
            }
        } else {
            echo "<script>alert('No user found with this email.');</script>";
        }
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Jaypee Learning Platform</title>
    <link rel="stylesheet" href="inde.css">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
</head>

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
    <a class="home" href="#">HOME</a>
    <div class="nav-links" id="nav-links">
<a href="#" class="login-button">Login</a>
<a href="#" id="show-register">Register</a>
    </div>
</nav>

<!-- Login Form -->
<div class="flex-col login-form" id="login-form">
    <button class="close-button" id="close-button">&times;</button> <!-- Close Button -->
    <h2>Login</h2>
    <form method="POST" action="">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="login">Login</button>
        <p>   Not a user? <a href="#" id="show-register">Don't worry, register first!</a></p>
    </form>
    <div id="message"></div> <!-- For displaying messages -->
</div>

<!-- Registration Form -->
<div class="flex-col register-form" id="register-form">
    <button class="close-button" id="close-register-button">&times;</button> <!-- Close Button -->
    <h2>Register</h2>
    <form method="POST" action="">
        <input type="email" name="reg_email" placeholder="Email" required>
        <input type="password" name="reg_password" placeholder="Password" required>
        <button type="submit" name="register">Register</button>
    </form>
    <p>   remember username and password? <a href="#" id="login-button">Don't worry, login!</a></p>
    <div id="register-message"></div> <!-- For displaying registration messages -->
</div>
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
        
<?php include 'footer.php'; ?>
<script>
    function toggleDropdown() {
    const dropdown = document.getElementById('dropdown-content');
    dropdown.classList.toggle('show');
}   


window.onclick = function(event) {
    const emailElement = document.querySelector('.user-email');
    const dropdown = document.getElementById('dropdown-content');
    const burgerMenu = document.getElementById('burger-menu');
    const navLinks = document.getElementById('nav-links');

    // Close dropdown if clicking outside of email and dropdown
    if (dropdown.classList.contains('show') && !emailElement.contains(event.target) && !dropdown.contains(event.target)) {
        dropdown.classList.remove('show');
    }

    // Close nav-links if clicking outside of burger icon and nav-links
    if (navLinks.classList.contains('show') && !burgerMenu.contains(event.target) && !navLinks.contains(event.target)) {
        navLinks.classList.remove('show');
    }
};

document.getElementById('burger-menu').addEventListener('click', function() {
    document.getElementById('nav-links').classList.toggle('show');
});

document.getElementById('burger-menu').addEventListener('click', function(event) {
    event.stopPropagation(); // Prevent click from propagating
    document.getElementById('nav-links').classList.toggle('show');
});


// Close the menu when clicking outside the burger menu or nav-links
document.addEventListener("DOMContentLoaded", function() {
    const burger = document.querySelector('.burger');
    const navLinks = document.querySelector('.nav-links');

    burger.addEventListener('click', () => {
    navLinks.classList.toggle('show'); // Toggles the 'show' class on click
    });
}); 

// Close the menu when clicking any link inside the nav-links
document.querySelectorAll('#nav-links a').forEach(function (link) {
    link.addEventListener('click', function () {
        document.getElementById('nav-links').classList.remove('show');
    });
});


// Toggle login form on "Login" button click
document.querySelector('.login-button').addEventListener('click', function (event) {
    event.preventDefault(); // Prevent default link behavior
    document.getElementById('login-form').classList.toggle('active');
});

// Close login form on close button click
document.getElementById('close-button').addEventListener('click', function () {
    document.getElementById('login-form').classList.remove('active');
})

// Show registration form when "register first" is clicked
document.getElementById('show-register').addEventListener('click', function (event) {
    event.preventDefault(); // Prevent the default link behavior
    document.getElementById('login-form').classList.remove('active'); // Hide login form
    document.getElementById('register-form').classList.add('active'); // Show registration form
});
// Show login form when "login" is clicked on registration form
document.getElementById('login-button').addEventListener('click', function (event) {
    event.preventDefault(); // Prevent the default link behavior
    document.getElementById('register-form').classList.remove('active'); // Hide registration form
    document.getElementById('login-form').classList.add('active'); // Show login form
});

// Close button functionality for the registration form
document.getElementById('close-register-button').addEventListener('click', function () {
    document.getElementById('register-form').classList.remove('active'); // Hide registration form
});
</script>
</body>
</html>
