<?php
// get_my_research.php - للحصول على بيانات أبحاث المستخدم مع التصفية والتنقل بين الصفحات

// بدء الجلسة
session_start();

// تهيئة الاتصال بقاعدة البيانات
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'gp';

// إنشاء اتصال جديد
$conn = new mysqli($host, $username, $password, $database);

// التحقق من الاتصال
if ($conn->connect_error) {
    die(json_encode(['error' => 'فشل الاتصال بقاعدة البيانات: ' . $conn->connect_error]));
}

// التأكد من ضبط الترميز المناسب
$conn->set_charset("utf8mb4");

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'يجب تسجيل الدخول أولاً لعرض أبحاثك.']));
}

$userId = $_SESSION['user_id'];

// الحصول على معلمات التصفية
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 9; // عدد العناصر في الصفحة
$offset = ($page - 1) * $limit;

$status = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
$classification = isset($_GET['classification']) ? $conn->real_escape_string($_GET['classification']) : '';
$r_type = isset($_GET['r_type']) ? $conn->real_escape_string($_GET['r_type']) : '';
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// بناء استعلام العدد الإجمالي مع التصفية
$countQuery = "SELECT COUNT(*) as total FROM submitsresearch WHERE user_id = ?";

// بناء استعلام جلب البيانات مع التصفية
$query = "
    SELECT 
        id, 
        title,
        classification,
        where_to_publish,
        r_type,
        college,
        user_notes,
        admin_notes,
        submission_date,
        status,
        is_shared
    FROM 
        submitsresearch
    WHERE 
        user_id = ?
";

// إضافة شروط التصفية
$countParams = [$userId];
$queryParams = [$userId];

if ($status !== '') {
    $countQuery .= " AND status = ?";
    $query .= " AND status = ?";
    $countParams[] = $status;
    $queryParams[] = $status;
}

if ($classification !== '') {
    $countQuery .= " AND classification = ?";
    $query .= " AND classification = ?";
    $countParams[] = $classification;
    $queryParams[] = $classification;
}

if ($r_type !== '') {
    $countQuery .= " AND r_type = ?";
    $query .= " AND r_type = ?";
    $countParams[] = $r_type;
    $queryParams[] = $r_type;
}

if ($search !== '') {
    $countQuery .= " AND title LIKE ?";
    $query .= " AND title LIKE ?";
    $searchParam = "%$search%";
    $countParams[] = $searchParam;
    $queryParams[] = $searchParam;
}

// إضافة الترتيب
$query .= " ORDER BY submission_date DESC";

// إضافة حدود الصفحة
$query .= " LIMIT ? OFFSET ?";
$queryParams[] = $limit;
$queryParams[] = $offset;

// تنفيذ استعلام العدد الإجمالي
$countStmt = $conn->prepare($countQuery);
$countTypes = str_repeat('s', count($countParams));
$countStmt->bind_param($countTypes, ...$countParams);
$countStmt->execute();
$totalResult = $countStmt->get_result();
$totalRow = $totalResult->fetch_assoc();
$totalRecords = $totalRow['total'];
$totalPages = ceil($totalRecords / $limit);
$countStmt->close();

// تنفيذ استعلام جلب البيانات
$queryStmt = $conn->prepare($query);
$queryTypes = str_repeat('s', count($queryParams) - 2) . 'ii'; // آخر معلمتين هما أرقام صحيحة
$queryStmt->bind_param($queryTypes, ...$queryParams);
$queryStmt->execute();
$result = $queryStmt->get_result();

if (!$result) {
    die(json_encode(['error' => 'خطأ في تنفيذ الاستعلام: ' . $conn->error]));
}

// تجميع النتائج
$research = [];
while ($row = $result->fetch_assoc()) {
    $research[] = $row;
}

$queryStmt->close();

// إغلاق الاتصال
$conn->close();

// إعادة البيانات كـ JSON
header('Content-Type: application/json');
echo json_encode([
    'research' => $research,
    'total_records' => $totalRecords,
    'total_pages' => $totalPages,
    'current_page' => $page
]);