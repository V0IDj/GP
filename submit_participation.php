<?php
// submit_participation.php - لتقديم طلب المشاركة في البحث

// بدء الجلسة إذا لم تكن قد بدأت بالفعل
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
    die(json_encode(['success' => false, 'message' => 'فشل الاتصال بقاعدة البيانات: ' . $conn->connect_error]));
}

// التأكد من ضبط الترميز المناسب
$conn->set_charset("utf8mb4");

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول أولاً للمشاركة في الأبحاث.']));
}

$userId = $_SESSION['user_id'];

// الحصول على معرف البحث من الطلب
if (!isset($_POST['research_id']) || empty($_POST['research_id'])) {
    die(json_encode(['success' => false, 'message' => 'معرف البحث مطلوب']));
}

$researchId = intval($_POST['research_id']);

// التحقق مما إذا كان البحث موجودًا وبحالة "موافق عليه"
$checkResearchQuery = "SELECT * FROM researchparticipation WHERE id = ? AND status = 'approved'";
$checkResearchStmt = $conn->prepare($checkResearchQuery);
$checkResearchStmt->bind_param("i", $researchId);
$checkResearchStmt->execute();
$checkResearchResult = $checkResearchStmt->get_result();

if ($checkResearchResult->num_rows === 0) {
    $checkResearchStmt->close();
    die(json_encode(['success' => false, 'message' => 'البحث غير موجود أو غير معتمد']));
}

// التحقق مما إذا كان المستخدم هو مالك البحث
$research = $checkResearchResult->fetch_assoc();
$checkResearchStmt->close();

// لا يمكن للمستخدم المشاركة في بحثه الخاص
if ($research['participate1'] == $userId) {
    die(json_encode(['success' => false, 'message' => 'لا يمكنك المشاركة في بحثك الخاص']));
}

// التحقق مما إذا كان المستخدم قد تقدم بالفعل للمشاركة في هذا البحث
$checkQuery = "
    SELECT * FROM researchparticipation 
    WHERE id = ? AND (participate1 = ? OR participate2 = ? OR participate3 = ? OR participate4 = ? OR participate5 = ?)
";

$checkStmt = $conn->prepare($checkQuery);
$checkStmt->bind_param("iiiiii", $researchId, $userId, $userId, $userId, $userId, $userId);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    $checkStmt->close();
    die(json_encode(['success' => false, 'message' => 'أنت مشارك بالفعل في هذا البحث']));
}

$checkStmt->close();

// تحديد أول مكان فارغ للمشاركة
$participationField = null;
for ($i = 1; $i <= 5; $i++) {
    $field = "participate$i";
    if ($research[$field] === null) {
        $participationField = $field;
        break;
    }
}

if ($participationField === null) {
    die(json_encode(['success' => false, 'message' => 'لا توجد أماكن متاحة للمشاركة في هذا البحث']));
}

// تحديث البحث بإضافة المستخدم كمشارك
$updateQuery = "
    UPDATE researchparticipation
    SET $participationField = ?
    WHERE id = ?
";

$updateStmt = $conn->prepare($updateQuery);
$updateStmt->bind_param("ii", $userId, $researchId);
$updateResult = $updateStmt->execute();

if (!$updateResult) {
    $updateStmt->close();
    die(json_encode(['success' => false, 'message' => 'حدث خطأ أثناء تقديم طلب المشاركة: ' . $conn->error]));
}

$updateStmt->close();

// إذا كان البحث من النوع المقدم، أرسل إشعارًا للمالك الأصلي (اختياري)
if ($research['submits_research_id']) {
    // يمكنك هنا إضافة رمز لإرسال إشعار للمالك الأصلي للبحث
    // على سبيل المثال، إضافة سجل في جدول الإشعارات أو إرسال بريد إلكتروني
    
    // مثال على إضافة سجل في جدول افتراضي للإشعارات (يجب إنشاء هذا الجدول)
    /*
    $notificationQuery = "
        INSERT INTO notifications (user_id, message, research_id, created_at)
        SELECT user_id, ?, ?, NOW()
        FROM submitsresearch
        WHERE id = ?
    ";
    $message = "قام شخص ما بالمشاركة في بحثك: " . $research['title'];
    $notificationStmt = $conn->prepare($notificationQuery);
    $notificationStmt->bind_param("sii", $message, $researchId, $research['submits_research_id']);
    $notificationStmt->execute();
    $notificationStmt->close();
    */
}

// إغلاق الاتصال
$conn->close();

// إعادة النجاح
echo json_encode(['success' => true, 'message' => 'تم تقديم طلب المشاركة بنجاح.']);
?>