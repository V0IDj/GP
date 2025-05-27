<?php
// تعيين رأس يسمح بمعالجة البيانات
header('Content-Type: application/json; charset=utf-8');

// التحقق من أخطاء بدء تشغيل PHP
try {
    // استيراد ملف الاتصال بقاعدة البيانات
    require_once 'config.php';

    // التحقق من صحة الاتصال بقاعدة البيانات
    if ($conn->connect_error) {
        throw new Exception("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
    }

    // التحقق من أن الطلب هو POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("طريقة طلب غير صالحة.");
    }

    // بدء الجلسة للحصول على معرف المستخدم
    session_start();
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("يجب تسجيل الدخول أولاً.");
    }
    $user_id = $_SESSION['user_id'];

    // التحقق من البيانات المطلوبة
    if (empty($_POST['title'])) {
        throw new Exception("يرجى إدخال عنوان البحث.");
    }
    
    if (empty($_POST['classification'])) {
        throw new Exception("يرجى اختيار تصنيف البحث.");
    }
      if (empty($_POST['r_type'])) {
        throw new Exception("يرجى اختيار نوع البحث.");
    }
    
    if (empty($_POST['where_to_publish'])) {
        throw new Exception("يرجى اختيار مكان النشر.");
    }

    // التحقق من الملف
    if (!isset($_FILES['research_files']) || $_FILES['research_files']['error'] != 0) {
        $error_message = "حدث خطأ أثناء تحميل الملف: ";
        switch($_FILES['research_files']['error']) {
            case 1:
                $error_message .= "حجم الملف أكبر من الحد المسموح به.";
                break;
            case 2:
                $error_message .= "حجم الملف أكبر من الحد المسموح به.";
                break;
            case 3:
                $error_message .= "تم تحميل جزء من الملف فقط.";
                break;
            case 4:
                $error_message .= "لم يتم تحميل أي ملف.";
                break;
            default:
                $error_message .= "خطأ غير معروف في تحميل الملف.";
        }
        throw new Exception($error_message);
    }

    // التحقق من حجم الملف (الحد الأقصى 10 ميجابايت)
    if ($_FILES['research_files']['size'] > 10485760) { // 10MB in bytes
        throw new Exception("حجم الملف كبير جدًا. الحد الأقصى هو 10 ميجابايت.");
    }

    // الحصول على البيانات من النموذج
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $classification = mysqli_real_escape_string($conn, $_POST['classification']);
    $where_to_publish = mysqli_real_escape_string($conn, $_POST['where_to_publish']);
    $r_type = mysqli_real_escape_string($conn, $_POST['r_type']);
    $college = !empty($_POST['college']) ? mysqli_real_escape_string($conn, $_POST['college']) : null;
    $user_notes = !empty($_POST['user_notes']) ? mysqli_real_escape_string($conn, $_POST['user_notes']) : null;

    // التحقق من صحة التصنيف
    $valid_classifications = ['Q1', 'Q2', 'Q3', 'Q4'];
    if (!in_array($classification, $valid_classifications)) {
        throw new Exception("تصنيف غير صالح.");
    }

    // التحقق من صحة مكان النشر
    $valid_publish_locations = ['Journal', 'Book Chapter', 'Conference'];
    if (!in_array($where_to_publish, $valid_publish_locations)) {
        throw new Exception("مكان نشر غير صالح.");
    }
    $valid_r_type = [ 'practical', 'theoretical'];
    if (!in_array($r_type, $valid_r_type)) {
        throw new Exception("نوع البحث غير صالح.");
    }

    // معالجة الملف
    $file_data = file_get_contents($_FILES['research_files']['tmp_name']);
    if ($file_data === false) {
        throw new Exception("فشل في قراءة الملف.");
    }
    
    // إعداد الاستعلام بطريقة أكثر أمانًا
    $stmt = $conn->prepare("INSERT INTO submitsresearch (user_id, classification, where_to_publish, college, title, user_notes, files,r_type) VALUES (?, ?, ?, ?, ?, ?, ?,?)");
    
    if (!$stmt) {
        throw new Exception("خطأ في إعداد الاستعلام: " . $conn->error);
    }

    // ربط المعلمات والبيانات
    $null = NULL;
    $stmt->bind_param("isssssbs", $user_id, $classification, $where_to_publish, $college, $title, $user_notes, $null, $r_type);
    
    // تعيين قيمة ملف الـ BLOB بشكل صحيح
    $stmt->send_long_data(6, $file_data);

    // تنفيذ الاستعلام
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'تم تقديم البحث بنجاح.',
            'submission_id' => $conn->insert_id
        ]);
    } else {
        throw new Exception("فشل في تقديم البحث: " . $stmt->error);
    }

    // إغلاق الاتصال
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    // التعامل مع جميع الاستثناءات وإرجاع رسالة الخطأ
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    
    // تسجيل الخطأ للمراجعة اللاحقة
    error_log("Research submission error: " . $e->getMessage());
    
    // إذا كان الاتصال مفتوحًا، أغلقه
    if (isset($stmt) && $stmt) {
        $stmt->close();
    }
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
?>