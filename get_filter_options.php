<?php
// get_filter_options.php - للحصول على خيارات التصفية المتاحة (الفئات والتصنيفات)

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

// الحصول على الفئات الفريدة
$categoryQuery = "SELECT DISTINCT category FROM researchparticipation WHERE category IS NOT NULL AND category != '' ORDER BY category";
$categoryResult = $conn->query($categoryQuery);

if (!$categoryResult) {
    die(json_encode(['error' => 'خطأ في استعلام الفئات: ' . $conn->error]));
}

$categories = [];
while ($row = $categoryResult->fetch_assoc()) {
    $categories[] = $row['category'];
}

// الحصول على التصنيفات الفريدة
$classificationQuery = "SELECT DISTINCT classification FROM researchparticipation WHERE classification IS NOT NULL AND classification != '' ORDER BY classification";
$classificationResult = $conn->query($classificationQuery);

if (!$classificationResult) {
    die(json_encode(['error' => 'خطأ في استعلام التصنيفات: ' . $conn->error]));
}

$classifications = [];
while ($row = $classificationResult->fetch_assoc()) {
    $classifications[] = $row['classification'];
}

// إغلاق الاتصال
$conn->close();

// إعادة البيانات كـ JSON
header('Content-Type: application/json');
echo json_encode([
    'categories' => $categories,
    'classifications' => $classifications
]);
?>