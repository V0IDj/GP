<?php
// Start session to track admin login status
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
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

// Get admin ID from session
$admin_id = $_SESSION['user_id'];

// Check if this is a new visit (to update counters)
$is_visit = isset($_GET['visit']) ? $_GET['visit'] : '';
$section = isset($_GET['section']) ? $_GET['section'] : '';

// Update last visit timestamp for specific sections when visited
if ($is_visit == 'true' && !empty($section)) {
    $valid_sections = ['rewards', 'researches', 'services'];
    
    if (in_array($section, $valid_sections)) {
        // Update the last visit timestamp for this section
        $timestamp = date('Y-m-d H:i:s');
        
        // Check if record exists
        $check_sql = "SELECT * FROM admin_last_visits WHERE admin_id = ? AND section = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("is", $admin_id, $section);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing record
            $update_sql = "UPDATE admin_last_visits SET last_visit = ? WHERE admin_id = ? AND section = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("sis", $timestamp, $admin_id, $section);
            $update_stmt->execute();
        } else {
            // Insert new record
            $insert_sql = "INSERT INTO admin_last_visits (admin_id, section, last_visit) VALUES (?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("iss", $admin_id, $section, $timestamp);
            $insert_stmt->execute();
        }
        
        // Redirect back to remove the parameters from URL
        header("Location: admin_".$section.".php");
        exit();
    }
}

// Get the last visit timestamps for each section
$last_visits = [
    'rewards' => null,
    'researches' => null,
    'services' => null
];

$visit_sql = "SELECT section, last_visit FROM admin_last_visits WHERE admin_id = ?";
$visit_stmt = $conn->prepare($visit_sql);
$visit_stmt->bind_param("i", $admin_id);
$visit_stmt->execute();
$visit_result = $visit_stmt->get_result();

while ($row = $visit_result->fetch_assoc()) {
    $last_visits[$row['section']] = $row['last_visit'];
}

// Debug info - remove in production
$debug = false;
$debug_info = [];
$debug_info['admin_id'] = $admin_id;
$debug_info['last_visits'] = $last_visits;

// Count new items since last visit
// Rewards count
$rewards_count = 0;
if ($last_visits['rewards'] !== null) {
    // Use proper timestamp comparison for MySQL
    $rewards_sql = "SELECT COUNT(*) as count FROM reward WHERE status = 'wait'";
    if ($last_visits['rewards']) {
        $rewards_sql .= " AND created_at > ?";
        $rewards_stmt = $conn->prepare($rewards_sql);
        $rewards_stmt->bind_param("s", $last_visits['rewards']);
    } else {
        $rewards_stmt = $conn->prepare($rewards_sql);
    }
    $rewards_stmt->execute();
    $rewards_result = $rewards_stmt->get_result();
    $rewards_count = $rewards_result->fetch_assoc()['count'];
    
    $debug_info['rewards_sql'] = $rewards_sql;
    $debug_info['rewards_timestamp'] = $last_visits['rewards'];
} else {
    // If first time, count all waiting rewards
    $rewards_sql = "SELECT COUNT(*) as count FROM reward WHERE status = 'wait'";
    $rewards_stmt = $conn->prepare($rewards_sql);
    $rewards_stmt->execute();
    $rewards_result = $rewards_stmt->get_result();
    $rewards_count = $rewards_result->fetch_assoc()['count'];
    
    $debug_info['rewards_sql'] = $rewards_sql;
    $debug_info['rewards_timestamp'] = 'null';
}

// Research submissions count
$researches_count = 0;
if ($last_visits['researches'] !== null) {
    $researches_sql = "SELECT COUNT(*) as count FROM submitsresearch WHERE (status = 'Pending 1' OR status = 'Pending 2')";
    if ($last_visits['researches']) {
        $researches_sql .= " AND submission_date > ?";
        $researches_stmt = $conn->prepare($researches_sql);
        $researches_stmt->bind_param("s", $last_visits['researches']);
    } else {
        $researches_stmt = $conn->prepare($researches_sql);
    }
    $researches_stmt->execute();
    $researches_result = $researches_stmt->get_result();
    $researches_count = $researches_result->fetch_assoc()['count'];
    
    $debug_info['researches_sql'] = $researches_sql;
    $debug_info['researches_timestamp'] = $last_visits['researches'];
} else {
    // If first time, count all pending researches
    $researches_sql = "SELECT COUNT(*) as count FROM submitsresearch WHERE status = 'Pending 1' OR status = 'Pending 2'";
    $researches_stmt = $conn->prepare($researches_sql);
    $researches_stmt->execute();
    $researches_result = $researches_stmt->get_result();
    $researches_count = $researches_result->fetch_assoc()['count'];
    
    $debug_info['researches_sql'] = $researches_sql;
    $debug_info['researches_timestamp'] = 'null';
}

// Services count
$services_count = 0;
if ($last_visits['services'] !== null) {
    $services_sql = "SELECT COUNT(*) as count FROM servicetemp WHERE status = 'wait 1'";
    if ($last_visits['services']) {
        $services_sql .= " AND created_at > ?";
        $services_stmt = $conn->prepare($services_sql);
        $services_stmt->bind_param("s", $last_visits['services']);
    } else {
        $services_stmt = $conn->prepare($services_sql);
    }
    $services_stmt->execute();
    $services_result = $services_stmt->get_result();
    $services_count = $services_result->fetch_assoc()['count'];
    
    $debug_info['services_sql'] = $services_sql;
    $debug_info['services_timestamp'] = $last_visits['services'];
} else {
    // If first time, count all waiting services
    $services_sql = "SELECT COUNT(*) as count FROM servicetemp WHERE status = 'wait 1'";
    $services_stmt = $conn->prepare($services_sql);
    $services_stmt->execute();
    $services_result = $services_stmt->get_result();
    $services_count = $services_result->fetch_assoc()['count'];
    
    $debug_info['services_sql'] = $services_sql;
    $debug_info['services_timestamp'] = 'null';
}

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
    <title>لوحة تحكم المشرف - دائرة البحث العلمي</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="./styles/admin.css">
</head>

<body>

    <header class="admin-header">
        <h1>لوحة تحكم المشرف - دائرة البحث العلمي</h1>
        <p>إدارة ومتابعة الأبحاث العلمية والخدمات والمكافآت</p>
    </header>

    <nav>
        <ul>
            <li><a href="#" id="home-link">الرئيسية</a></li>
            <li><a href="admin_rewards.php?visit=true&section=rewards" id="approve-rewards-link">المكافآت
                    <?php echo displayBadge($rewards_count); ?></a></li>
            <li><a href="admin_researches.html?visit=true&section=researches" id="approve-researches-link">الأبحاث
                    <?php echo displayBadge($researches_count); ?></a></li>
            <li><a href="admin_services.html?visit=true&section=services" id="approve-services-link">الخدمات
                    <?php echo displayBadge($services_count); ?></a></li>
            <li><a href="#" id="analytics-link">لوحة الإحصائيات</a></li>
            <li><a href="profile.html" id="profile-link">تعديل الملف الشخصي</a></li>
            <li><a href="logout.php" id="logout-link">تسجيل الخروج</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="options">
            <div class="option-card" id="approve-rewards">
                <i class="fas fa-award"></i>
                <h3>مراجعة المكافآت</h3>
                <p>عرض طلبات المكافآت وتقييمها والموافقة عليها</p>
                <a href="admin_rewards.php?visit=true&section=rewards" class="btn">عرض الطلبات
                    <?php echo displayBadge($rewards_count); ?></a>
            </div>

            <div class="option-card" id="approve-researches">
                <i class="fas fa-file-alt"></i>
                <h3>مراجعة الأبحاث</h3>
                <p>عرض الأبحاث المقدمة ومراجعتها والموافقة عليها</p>
                <a href="admin_researches.html?visit=true&section=researches" class="btn">عرض الأبحاث
                    <?php echo displayBadge($researches_count); ?></a>
            </div>

            <div class="option-card" id="approve-services">
                <i class="fas fa-concierge-bell"></i>
                <h3>مراجعة الخدمات</h3>
                <p>عرض طلبات الخدمات المختلفة والموافقة عليها</p>
                <a href="admin_services.html?visit=true&section=services" class="btn">عرض الطلبات
                    <?php echo displayBadge($services_count); ?></a>
            </div>

            <div class="option-card" id="analytics">
                <i class="fas fa-chart-line"></i>
                <h3>لوحة الإحصائيات</h3>
                <p>عرض إحصائيات وتحليلات الأبحاث والخدمات والمستخدمين</p>
                <a href="#" class="btn">عرض الإحصائيات</a>
            </div>

            <div class="option-card" id="system-settings">
                <i class="fas fa-users"></i>
                <h3>التحكم في المستخدمين</h3>
                <p>إدارة حسابات المستخدمين وصلاحياتهم</p>
                <a href="#" class="btn">إدارة المستخدمين</a>
            </div>

            <div class="option-card" id="profile">
                <i class="fas fa-user-shield"></i>
                <h3>تعديل الملف الشخصي</h3>
                <p>تعديل بيانات المشرف وتغيير كلمة المرور</p>
                <a href="profile.html" class="btn">تعديل</a>
            </div>
        </div>
    </div>

    <div class="modal" id="feature-modal">
        <div class="modal-content">
            <h3 id="modal-title">عنوان الميزة</h3>
            <p id="modal-message">هذه الميزة ستكون متاحة قريباً</p>
            <button class="close-modal">إغلاق</button>
        </div>
    </div>

    <footer>
        <p>جميع الحقوق محفوظة &copy; 2025 دائرة البحث العلمي - لوحة تحكم المشرف</p>
    </footer>

    <script>
    // الحصول على مراجع للعناصر
    const modal = document.getElementById('feature-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalMessage = document.getElementById('modal-message');
    const closeModal = document.querySelector('.close-modal');

    // قائمة بجميع الروابط وكروت الخيارات
    const allLinks = [{
            id: "analytics",
            title: "لوحة الإحصائيات",
            message: "هنا يمكنك عرض الإحصائيات والتحليلات المتعلقة بالأبحاث والخدمات والمستخدمين."
        },
        {
            id: "analytics-link",
            title: "لوحة الإحصائيات",
            message: "هنا يمكنك عرض الإحصائيات والتحليلات المتعلقة بالأبحاث والخدمات والمستخدمين."
        },
        {
            id: "system-settings",
            title: "التحكم في المستخدمين",
            message: "هنا يمكنك إدارة حسابات المستخدمين وصلاحياتهم."
        }
    ];

    // إضافة استمعي الأحداث لجميع الروابط
    allLinks.forEach(link => {
        const element = document.getElementById(link.id);
        if (element) {
            element.addEventListener('click', function(e) {
                e.preventDefault();
                modalTitle.textContent = link.title;
                modalMessage.textContent = link.message;
                modal.style.display = 'flex';
            });
        }
    });

    // إغلاق الموديل
    closeModal.addEventListener('click', function() {
        modal.style.display = 'none';
    });

    // إغلاق الموديل عند النقر خارجه
    window.addEventListener('click', function(e) {
        if (e.target == modal) {
            modal.style.display = 'none';
        }
    });

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
    </script>
</body>

</html>