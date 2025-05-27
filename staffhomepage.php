<?php
// Start session to track staff login status
session_start();

// Check if user is logged in and is a staff member
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Staff') {
    header("Location: login.html");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gp";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character set to UTF-8
$conn->set_charset("utf8mb4");

// Get staff ID from session
$staff_id = $_SESSION['user_id'];
$staff_name = $_SESSION['username'] ?? 'الموظف';

// Count assigned service requests that need action (wait_2 or approved but not completed)
$services_count = 0;
$services_sql = "SELECT COUNT(*) as count FROM servicetemp WHERE assigned_staff_id = ? AND (status = 'wait 2' OR (status = 'approved' AND completed_files IS NULL))";
$services_stmt = $conn->prepare($services_sql);
$services_stmt->bind_param("i", $staff_id);
$services_stmt->execute();
$services_result = $services_stmt->get_result();
$services_count = $services_result->fetch_assoc()['count'];

// Close the database connection
$conn->close();

// Helper function to display notification badge if count > 0
function displayBadge($count) {
    if ($count > 0) {
        return "<span class=\"notification-badge\">$count</span>";
    }
    return "";
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم الموظف - دائرة البحث العلمي</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="./styles/staffhome.css">
</head>

<body>

    <header class="staff-header">
        <h1>لوحة تحكم الموظف - دائرة البحث العلمي</h1>
        <p>مرحباً بك، <?php echo htmlspecialchars($staff_name); ?></p>
    </header>

    <nav>
        <ul>
            <li><a href="#" id="home-link">الرئيسية</a></li>
            <li><a href="staff_services.html" id="manage-services-link">إدارة طلبات الخدمات
                    <?php echo displayBadge($services_count); ?></a></li>
            <li><a href="profile.html" id="profile-link">تعديل الملف الشخصي</a></li>
            <li><a href="logout.php" id="logout-link">تسجيل الخروج</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="options">
            <div class="option-card" id="manage-services">
                <i class="fas fa-concierge-bell"></i>
                <h3>إدارة طلبات الخدمات</h3>
                <p>عرض ومتابعة طلبات الخدمات المعينة إليك</p>
                <a href="staff_services.html" class="btn">عرض الطلبات
                    <?php echo displayBadge($services_count); ?></a>
            </div>

            <div class="option-card" id="profile">
                <i class="fas fa-user-edit"></i>
                <h3>تعديل الملف الشخصي</h3>
                <p>قم بتحديث معلوماتك الشخصية ومهاراتك وبيانات الاتصال</p>
                <a href="profile.html" class="btn">تعديل</a>
            </div>

            <div class="option-card" id="logout">
                <i class="fas fa-sign-out-alt"></i>
                <h3>تسجيل الخروج</h3>
                <p>تسجيل الخروج من النظام وإنهاء الجلسة الحالية</p>
                <a href="logout.php" class="btn">تسجيل الخروج</a>
            </div>
        </div>
    </div>

    <footer>
        <p>جميع الحقوق محفوظة &copy; 2025 دائرة البحث العلمي</p>
    </footer>

    <script>
    // رابط الصفحة الرئيسية يعيد تحميل الصفحة
    document.getElementById('home-link').addEventListener('click', function(e) {
        e.preventDefault();
        window.location.reload();
    });

    // تأكيد تسجيل الخروج
    document.getElementById('logout-link').addEventListener('click', function(e) {
        const confirmLogout = confirm('هل أنت متأكد من رغبتك في تسجيل الخروج؟');
        if (!confirmLogout) {
            e.preventDefault();
        }
    });

    // تأكيد تسجيل الخروج لزر البطاقة
    document.querySelector('#logout .btn').addEventListener('click', function(e) {
        const confirmLogout = confirm('هل أنت متأكد من رغبتك في تسجيل الخروج؟');
        if (!confirmLogout) {
            e.preventDefault();
        }
    });
    </script>
</body>

</html>