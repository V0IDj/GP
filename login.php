<?php
session_start();

// Custom session function
function custom_session_start() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

custom_session_start();

require_once 'config.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (!empty($username) && !empty($password)) {
        $sql = "SELECT * FROM researchuser WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) { 
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['logged_in'] = true;
                $_SESSION['role'] = $user['role']; // Save the role in the session
                $_SESSION['last_login'] = date('Y-m-d H:i:s');

                // Set cookies to remember the user for 1 week
                setcookie('username', $username, time() + (86400 * 7), "/");
                setcookie('user_id', $user['user_id'], time() + (86400 * 7), "/");

                // If admin, initialize their visit records if they don't exist
                if ($user['role'] === 'Admin') {
                    initializeAdminVisits($conn, $user['user_id']);
                }

                // Redirect based on role
                switch ($user['role']) {
                    case 'Researcher':
                        header("Location: userhomepage.php");
                        break;
                    case 'Admin':
                        header("Location: adminhomepage.php");
                        break;
                    case 'Student':
                        header("Location: studenthomepage.php");
                        break;
                    case 'Staff':
                        header("Location: staffhomepage.php");
                        break;
                    default:
                        // Fallback to a default page if role is not recognized
                        header("Location: userhomepage.php");
                        break;
                }
                exit();
            } else {   
                header("Location: login.html?error=" . urlencode("Invalid username or password."));
                exit();
            }
        } else { 
            header("Location: login.html?error=" . urlencode("Invalid username or password."));
            exit();
        }
    } else {
        header("Location: login.html?error=" . urlencode("Please fill in all fields."));
        exit();
    }
}

// Function to initialize admin visit records if they don't exist
function initializeAdminVisits($conn, $admin_id) {
    // Check if the admin_last_visits table exists, create it if it doesn't
    $check_table_sql = "SHOW TABLES LIKE 'admin_last_visits'";
    $table_result = $conn->query($check_table_sql);
    
    if ($table_result->num_rows == 0) {
        // Table doesn't exist, create it
        $create_table_sql = "CREATE TABLE IF NOT EXISTS `admin_last_visits` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `admin_id` int(11) NOT NULL,
            `section` enum('rewards','researches','services') NOT NULL,
            `last_visit` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `admin_section_unique` (`admin_id`,`section`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
        
        $conn->query($create_table_sql);
    }
    
    // Check for existing visit records for this admin
    $check_visits_sql = "SELECT COUNT(*) as count FROM admin_last_visits WHERE admin_id = ?";
    $check_stmt = $conn->prepare($check_visits_sql);
    $check_stmt->bind_param("i", $admin_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $visit_count = $check_result->fetch_assoc()['count'];
    
    // If no visit records exist, create initial null records for each section
    if ($visit_count < 3) {
        $sections = ['rewards', 'researches', 'services'];
        
        foreach ($sections as $section) {
            // Check if this specific section record exists
            $check_section_sql = "SELECT COUNT(*) as count FROM admin_last_visits 
                                WHERE admin_id = ? AND section = ?";
            $check_section_stmt = $conn->prepare($check_section_sql);
            $check_section_stmt->bind_param("is", $admin_id, $section);
            $check_section_stmt->execute();
            $section_result = $check_section_stmt->get_result();
            $section_count = $section_result->fetch_assoc()['count'];
            
            // If this section doesn't have a record, insert one with NULL timestamp
            if ($section_count == 0) {
                $insert_sql = "INSERT INTO admin_last_visits (admin_id, section, last_visit) 
                             VALUES (?, ?, NULL)";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("is", $admin_id, $section);
                $insert_stmt->execute();
            }
        }
    }
}
?>