<?php
// get_my_submitted_research.php - للحصول على بيانات أبحاث المستخدم المقدمة

// بدء الجلسة
session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'يجب تسجيل الدخول أولاً لعرض أبحاثك.']));
}

$userId = $_SESSION['user_id'];

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

// تصحيح البيانات - التحقق من تطابق حالة is_shared مع وجود البحث في جدول researchparticipation
$updateQuery = "
    UPDATE submitsresearch sr
    SET sr.is_shared = 0
    WHERE sr.is_shared = 1 
    AND NOT EXISTS (
        SELECT 1 FROM researchparticipation rp 
        WHERE rp.submits_research_id = sr.id
    )
";
$conn->query($updateQuery);

// تحديث الأبحاث التي تمت مشاركتها بالفعل ولكن لم يتم تعيين is_shared
$updateQuery2 = "
    UPDATE submitsresearch sr
    SET sr.is_shared = 1
    WHERE sr.is_shared = 0 
    AND EXISTS (
        SELECT 1 FROM researchparticipation rp 
        WHERE rp.submits_research_id = sr.id
    )
";
$conn->query($updateQuery2);

// استعلام جلب بيانات أبحاث المستخدم المقدمة بغض النظر عن حالتها
$query = "
    SELECT 
        sr.id, 
        sr.title,
        sr.classification,
        sr.where_to_publish,
        sr.r_type,
        sr.college,
        sr.user_notes,
        sr.admin_notes,
        sr.submission_date,
        sr.status,
        sr.is_shared,
        CASE 
            WHEN rp.id IS NOT NULL THEN 1
            ELSE 0
        END as actually_shared
    FROM 
        submitsresearch sr
    LEFT JOIN
        researchparticipation rp ON sr.id = rp.submits_research_id
    WHERE 
        sr.user_id = ?
    ORDER BY
        sr.submission_date DESC
";

// تنفيذ الاستعلام
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die(json_encode(['error' => 'خطأ في تنفيذ الاستعلام: ' . $conn->error]));
}

// تجميع النتائج
$research = [];
while ($row = $result->fetch_assoc()) {
    // تحويل null إلى قيم فارغة لتجنب مشاكل في JavaScript
    foreach ($row as $key => $value) {
        if ($value === null) {
            $row[$key] = '';
        }
    }
    
    // تصحيح قيمة is_shared بناءً على القيمة الفعلية
    $row['is_shared'] = $row['actually_shared'] ? 1 : 0;
    unset($row['actually_shared']); // إزالة العمود المؤقت
    
    $research[] = $row;
}

$stmt->close();

// إغلاق الاتصال
$conn->close();

// إعادة البيانات كـ JSON
header('Content-Type: application/json');
echo json_encode([
    'research' => $research
]);
?>