<?php
// get_research.php - للحصول على بيانات الأبحاث مع التصفية والتنقل بين الصفحات

// تهيئة الاتصال بقاعدة البيانات
$host = 'localhost'; // اسم المضيف
$username = 'root'; // اسم المستخدم
$password = ''; // كلمة المرور
$database = 'gp'; // اسم قاعدة البيانات

// إنشاء اتصال جديد
$conn = new mysqli($host, $username, $password, $database);

// التحقق من الاتصال
if ($conn->connect_error) {
    die(json_encode(['error' => 'فشل الاتصال بقاعدة البيانات: ' . $conn->connect_error]));
}

// التأكد من ضبط الترميز المناسب
$conn->set_charset("utf8mb4");

// الحصول على معلمات التصفية
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 9; // عدد العناصر في الصفحة
$offset = ($page - 1) * $limit;

$category = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';
$classification = isset($_GET['classification']) ? $conn->real_escape_string($_GET['classification']) : '';
$status = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
$availability = isset($_GET['availability']) ? $conn->real_escape_string($_GET['availability']) : '';
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// بناء استعلام العدد الإجمالي مع التصفية
$countQuery = "SELECT COUNT(*) as total FROM researchparticipation WHERE 1=1";

// بناء استعلام جلب البيانات مع التصفية
$query = "
    SELECT 
        r.id, 
        r.title,
        r.status,
        r.category,
        r.classification,
        r.created_at,
        r.submits_research_id,
        u1.name as participant1_name,
        u2.name as participant2_name,
        u3.name as participant3_name,
        u4.name as participant4_name,
        u5.name as participant5_name
    FROM 
        researchparticipation r
    LEFT JOIN 
        researchuser u1 ON r.participate1 = u1.user_id
    LEFT JOIN 
        researchuser u2 ON r.participate2 = u2.user_id
    LEFT JOIN 
        researchuser u3 ON r.participate3 = u3.user_id
    LEFT JOIN 
        researchuser u4 ON r.participate4 = u4.user_id
    LEFT JOIN 
        researchuser u5 ON r.participate5 = u5.user_id
    WHERE 1=1
";

// إضافة شروط التصفية
if ($category !== '') {
    $countQuery .= " AND category = '$category'";
    $query .= " AND r.category = '$category'";
}

if ($classification !== '') {
    $countQuery .= " AND classification = '$classification'";
    $query .= " AND r.classification = '$classification'";
}

if ($status !== '') {
    $countQuery .= " AND status = '$status'";
    $query .= " AND r.status = '$status'";
}

if ($search !== '') {
    $countQuery .= " AND title LIKE '%$search%'";
    $query .= " AND (r.title LIKE '%$search%' OR u1.name LIKE '%$search%' OR u2.name LIKE '%$search%' OR u3.name LIKE '%$search%' OR u4.name LIKE '%$search%' OR u5.name LIKE '%$search%')";
}

// شرط التوفر (متاح أو مكتمل)
if ($availability === 'available') {
    $query .= " AND (r.participate1 IS NULL OR r.participate2 IS NULL OR r.participate3 IS NULL OR r.participate4 IS NULL OR r.participate5 IS NULL)";
    $countQuery .= " AND (participate1 IS NULL OR participate2 IS NULL OR participate3 IS NULL OR participate4 IS NULL OR participate5 IS NULL)";
} elseif ($availability === 'full') {
    $query .= " AND r.participate1 IS NOT NULL AND r.participate2 IS NOT NULL AND r.participate3 IS NOT NULL AND r.participate4 IS NOT NULL AND r.participate5 IS NOT NULL";
    $countQuery .= " AND participate1 IS NOT NULL AND participate2 IS NOT NULL AND participate3 IS NOT NULL AND participate4 IS NOT NULL AND participate5 IS NOT NULL";
}

// نعرض فقط الأبحاث المعتمدة
$countQuery .= " AND status = 'approved'";
$query .= " AND r.status = 'approved'";

// إضافة الترتيب
$query .= " ORDER BY r.created_at DESC";

// إضافة حدود الصفحة
$query .= " LIMIT $limit OFFSET $offset";

// تنفيذ استعلام العدد الإجمالي
$totalResult = $conn->query($countQuery);
$totalRow = $totalResult->fetch_assoc();
$totalRecords = $totalRow['total'];
$totalPages = ceil($totalRecords / $limit);

// تنفيذ استعلام جلب البيانات
$result = $conn->query($query);

if (!$result) {
    die(json_encode(['error' => 'خطأ في تنفيذ الاستعلام: ' . $conn->error]));
}

// تجميع النتائج
$research = [];
while ($row = $result->fetch_assoc()) {
    $research[] = $row;
}

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
?>