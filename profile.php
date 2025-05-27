<?php
session_start();

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['user_id'])) {
    // For this example, we'll create a mock session for testing
    // In a real application, you would redirect to login
    $_SESSION['user_id'] = 1; // Example user ID
}

// Database connection
$servername = "localhost";
$username = "root";  // Replace with your database username
$password = "";  // Replace with your database password
$dbname = "gb";    // Replace with your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// Set the character set to utf8
$conn->set_charset("utf8");

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Get user data
$sql = "SELECT user_id, name, email, username, bio, profile_image, college, major, Number, PhoneNumber 
        FROM researchuser 
        WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    
    // Convert profile image to base64 if exists
    $profile_image_base64 = '';
    if ($user['profile_image']) {
        $profile_image_base64 = base64_encode($user['profile_image']);
    }
} else {
    die("لم يتم العثور على بيانات المستخدم");
}

// Handle form submissions via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json; charset=utf-8');
    
    if (isset($_POST['section']) && $_POST['section'] === 'about') {
        // Update about information
        $bio = $_POST['bio'];
        $college = $_POST['college'];
        $major = $_POST['major'];
        
        $sql = "UPDATE researchuser SET bio = ?, college = ?, major = ? WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $bio, $college, $major, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'تم تحديث المعلومات بنجاح']);
        } else {
            echo json_encode(['success' => false, 'message' => 'فشل تحديث المعلومات: ' . $stmt->error]);
        }
        
    } elseif (isset($_POST['section']) && $_POST['section'] === 'contact') {
        // Update contact information
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'عنوان البريد الإلكتروني غير صالح']);
            exit;
        }
        
        if (!is_numeric($phone)) {
            echo json_encode(['success' => false, 'message' => 'رقم الهاتف غير صالح']);
            exit;
        }
        
        $sql = "UPDATE researchuser SET email = ?, PhoneNumber = ? WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $email, $phone, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'تم تحديث معلومات الاتصال بنجاح']);
        } else {
            echo json_encode(['success' => false, 'message' => 'فشل تحديث معلومات الاتصال: ' . $stmt->error]);
        }
        
    } elseif (isset($_POST['action']) && $_POST['action'] === 'update_password') {
        // Update password
        $currentPassword = $_POST['currentPassword'];
        $newPassword = $_POST['newPassword'];
        
        // Get current password from database
        $sql = "SELECT password FROM researchuser WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $storedPassword = $row['password'];
        
        // Verify current password
        if (!password_verify($currentPassword, $storedPassword)) {
            echo json_encode(['success' => false, 'message' => 'كلمة المرور الحالية غير صحيحة']);
            exit;
        }
        
        // Hash new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update password in database
        $sql = "UPDATE researchuser SET password = ? WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $hashedPassword, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'تم تحديث كلمة المرور بنجاح']);
        } else {
            echo json_encode(['success' => false, 'message' => 'فشل تحديث كلمة المرور: ' . $stmt->error]);
        }
        
    } elseif (isset($_POST['action']) && $_POST['action'] === 'update_image') {
        // Update profile image
        if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'فشل تحميل الصورة']);
            exit;
        }
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = $_FILES['profile_image']['type'];
        
        if (!in_array($fileType, $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'نوع الملف غير مدعوم. يرجى تحميل صورة بصيغة JPEG أو PNG أو GIF']);
            exit;
        }
        
        $maxSize = 2 * 1024 * 1024;
        if ($_FILES['profile_image']['size'] > $maxSize) {
            echo json_encode(['success' => false, 'message' => 'حجم الملف كبير جدًا. الحد الأقصى هو 2 ميجابايت']);
            exit;
        }
        
        $imageData = file_get_contents($_FILES['profile_image']['tmp_name']);
        
        $sql = "UPDATE researchuser SET profile_image = ? WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $null = NULL;
        $stmt->bind_param("bi", $null, $user_id);
        $stmt->send_long_data(0, $imageData);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'تم تحديث الصورة الشخصية بنجاح']);
        } else {
            echo json_encode(['success' => false, 'message' => 'فشل تحديث الصورة الشخصية: ' . $stmt->error]);
        }
    }
    
    exit;
}

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ملف المستخدم البحثي</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    /* Arabic Font Import */
    @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap');

    :root {
        --primary-color: #2a6cad;
        --secondary-color: #4091df;
        --accent-color: #1d4e89;
        --gray-dark: #333;
        --gray-medium: #666;
        --gray-light: #eee;
        --success-color: #28a745;
        --error-color: #dc3545;
        --white: #ffffff;
        --shadow-light: 0 2px 10px rgba(0, 0, 0, 0.1);
        --shadow-medium: 0 4px 12px rgba(0, 0, 0, 0.15);
        --transition: all 0.3s ease;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Tajawal', sans-serif;
        background-color: #f7f9fc;
        color: var(--gray-dark);
        line-height: 1.6;
        direction: rtl;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 30px 15px;
    }

    /* Profile Container */
    .profile-container {
        background-color: var(--white);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: var(--shadow-medium);
        margin-bottom: 30px;
    }

    /* Profile Header */
    .profile-header {
        position: relative;
        margin-bottom: 30px;
    }

    .profile-cover {
        height: 200px;
        background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
        position: relative;
    }

    .profile-avatar-container {
        position: absolute;
        bottom: -50px;
        right: 50px;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .profile-avatar {
        width: 160px;
        height: 160px;
        border: 5px solid var(--white);
        border-radius: 50%;
        overflow: hidden;
        background-color: var(--gray-light);
        box-shadow: var(--shadow-light);
        margin-bottom: 10px;
        position: relative;
    }

    #profile-image-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        font-size: 4rem;
        color: var(--gray-medium);
    }

    #profile-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        position: absolute;
        top: 0;
        left: 0;
        display: none;
    }

    #change-photo-btn {
        padding: 8px 15px;
        background-color: var(--white);
        border: 1px solid var(--primary-color);
        color: var(--primary-color);
        border-radius: 5px;
        font-weight: 500;
        cursor: pointer;
        transition: var(--transition);
    }

    #change-photo-btn:hover {
        background-color: var(--primary-color);
        color: var(--white);
    }

    .profile-info {
        padding: 20px 50px 0 0;
        margin-right: 170px;
    }

    .profile-info h1 {
        font-size: 2rem;
        margin-bottom: 5px;
        color: var(--accent-color);
    }

    .profile-info p {
        color: var(--gray-medium);
        font-size: 1.1rem;
    }

    /* Profile Navigation */
    .profile-nav {
        border-bottom: 1px solid var(--gray-light);
        margin-bottom: 20px;
    }

    .profile-nav ul {
        list-style: none;
        display: flex;
        padding: 0 30px;
    }

    .profile-nav li {
        margin-left: 30px;
    }

    .profile-nav a {
        display: block;
        padding: 15px 5px;
        color: var(--gray-medium);
        text-decoration: none;
        font-weight: 500;
        position: relative;
        transition: var(--transition);
    }

    .profile-nav a:hover {
        color: var(--primary-color);
    }

    .profile-nav li.active a {
        color: var(--primary-color);
        font-weight: 700;
    }

    .profile-nav li.active a::after {
        content: '';
        position: absolute;
        bottom: -1px;
        right: 0;
        width: 100%;
        height: 3px;
        background-color: var(--primary-color);
    }

    /* Profile Content */
    .profile-content {
        padding: 20px 30px;
    }

    .profile-section {
        display: none;
    }

    .profile-section.active {
        display: block;
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .section-header h2 {
        color: var(--accent-color);
        font-size: 1.5rem;
    }

    .edit-btn {
        padding: 8px 15px;
        background-color: transparent;
        border: 1px solid var(--primary-color);
        color: var(--primary-color);
        border-radius: 5px;
        font-weight: 500;
        cursor: pointer;
        transition: var(--transition);
    }

    .edit-btn:hover {
        background-color: var(--primary-color);
        color: var(--white);
    }

    .info-block {
        margin-bottom: 25px;
        background-color: #fbfbfb;
        padding: 20px;
        border-radius: 8px;
        box-shadow: var(--shadow-light);
    }

    .info-block h3 {
        margin-bottom: 15px;
        color: var(--gray-dark);
        border-bottom: 1px solid var(--gray-light);
        padding-bottom: 8px;
    }

    .info-item {
        display: flex;
        margin-bottom: 15px;
    }

    .info-item i {
        margin-left: 15px;
        color: var(--primary-color);
        font-size: 1.2rem;
        width: 20px;
        text-align: center;
    }

    .info-item div {
        flex: 1;
    }

    .info-item strong {
        display: block;
        margin-bottom: 5px;
        color: var(--gray-dark);
    }

    .info-item p {
        color: var(--gray-medium);
    }

    /* Forms */
    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
    }

    .form-group input,
    .form-group textarea {
        width: 100%;
        padding: 12px;
        border: 1px solid var(--gray-light);
        border-radius: 5px;
        font-family: 'Tajawal', sans-serif;
        font-size: 1rem;
        transition: var(--transition);
    }

    .form-group input:focus,
    .form-group textarea:focus {
        border-color: var(--primary-color);
        outline: none;
        box-shadow: 0 0 0 2px rgba(42, 108, 173, 0.2);
    }

    .form-buttons {
        display: flex;
        justify-content: flex-start;
        gap: 10px;
    }

    .btn {
        padding: 12px 20px;
        border: none;
        border-radius: 5px;
        font-weight: 500;
        cursor: pointer;
        transition: var(--transition);
        font-family: 'Tajawal', sans-serif;
        font-size: 1rem;
    }

    .save-btn {
        background-color: var(--primary-color);
        color: var(--white);
    }

    .save-btn:hover {
        background-color: var(--accent-color);
    }

    .cancel-btn {
        background-color: var(--gray-light);
        color: var(--gray-dark);
    }

    .cancel-btn:hover {
        background-color: #ddd;
    }

    /* Status Message */
    .status-message {
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        padding: 15px 25px;
        border-radius: 5px;
        color: var(--white);
        font-weight: 500;
        z-index: 1000;
        opacity: 0;
        transition: var(--transition);
    }

    .status-message.show {
        opacity: 1;
    }

    .status-message.success {
        background-color: var(--success-color);
    }

    .status-message.error {
        background-color: var(--error-color);
    }

    /* Responsive */
    @media screen and (max-width: 768px) {
        .profile-avatar-container {
            right: 50%;
            transform: translateX(50%);
        }

        .profile-info {
            text-align: center;
            margin-right: 0;
            padding: 80px 20px 0;
        }

        .profile-nav ul {
            justify-content: center;
            flex-wrap: wrap;
        }

        .profile-nav li {
            margin: 0 15px;
        }
    }

    @media screen and (max-width: 480px) {
        .form-buttons {
            flex-direction: column;
        }

        .btn {
            width: 100%;
            margin-bottom: 10px;
        }
    }
    </style>
</head>

<body>
    <div class="container">
        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-cover"></div>
                <div class="profile-avatar-container">
                    <div class="profile-avatar">
                        <div id="profile-image-placeholder"
                            <?php if (!empty($profile_image_base64)) echo 'style="display: none;"'; ?>>
                            <i class="fas fa-user"></i>
                        </div>
                        <img id="profile-image"
                            src="<?php if (!empty($profile_image_base64)) echo 'data:image/jpeg;base64,' . $profile_image_base64; ?>"
                            alt="صورة الملف الشخصي"
                            <?php if (!empty($profile_image_base64)) echo 'style="display: block;"'; ?>>
                    </div>
                    <button id="change-photo-btn" class="btn"><i class="fas fa-camera"></i> تغيير الصورة</button>
                    <input type="file" id="profile-image-upload" accept="image/*" style="display: none;">
                </div>
                <div class="profile-info">
                    <h1 id="user-name"><?php echo htmlspecialchars($user['name']); ?></h1>
                    <p id="user-title"><?php echo htmlspecialchars($user['college'] . ' | ' . $user['major']); ?></p>
                </div>
            </div>

            <div class="profile-nav">
                <ul>
                    <li class="active"><a href="#about" data-section="about">نبذة عني</a></li>
                    <li><a href="#contact" data-section="contact">معلومات الاتصال</a></li>
                    <li><a href="#settings" data-section="settings">الإعدادات</a></li>
                </ul>
            </div>

            <div class="profile-content">
                <!-- About Section -->
                <div id="about" class="profile-section active">
                    <div class="section-header">
                        <h2>نبذة عني</h2>
                        <button class="edit-btn" data-section="about"><i class="fas fa-edit"></i> تعديل</button>
                    </div>
                    <div class="section-content">
                        <div class="info-block">
                            <h3>السيرة الذاتية</h3>
                            <p id="user-bio"><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
                        </div>
                        <div class="info-block">
                            <h3>التعليم</h3>
                            <div class="info-item">
                                <i class="fas fa-university"></i>
                                <div>
                                    <strong id="user-college"><?php echo htmlspecialchars($user['college']); ?></strong>
                                    <p id="user-major"><?php echo htmlspecialchars($user['major']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="section-edit" style="display: none;">
                        <form id="about-form">
                            <div class="form-group">
                                <label for="edit-bio">السيرة الذاتية</label>
                                <textarea id="edit-bio"
                                    rows="5"><?php echo htmlspecialchars($user['bio']); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="edit-college">الكلية</label>
                                <input type="text" id="edit-college"
                                    value="<?php echo htmlspecialchars($user['college']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="edit-major">التخصص</label>
                                <input type="text" id="edit-major"
                                    value="<?php echo htmlspecialchars($user['major']); ?>">
                            </div>
                            <div class="form-buttons">
                                <button type="submit" class="btn save-btn">حفظ</button>
                                <button type="button" class="btn cancel-btn">إلغاء</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Contact Section -->
                <div id="contact" class="profile-section">
                    <div class="section-header">
                        <h2>معلومات الاتصال</h2>
                        <button class="edit-btn" data-section="contact"><i class="fas fa-edit"></i> تعديل</button>
                    </div>
                    <div class="section-content">
                        <div class="info-block">
                            <div class="info-item">
                                <i class="fas fa-envelope"></i>
                                <div>
                                    <strong>البريد الإلكتروني</strong>
                                    <p id="user-email"><?php echo htmlspecialchars($user['email']); ?></p>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-phone"></i>
                                <div>
                                    <strong>رقم الهاتف</strong>
                                    <p id="user-phone"><?php echo htmlspecialchars($user['PhoneNumber']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="section-edit" style="display: none;">
                        <form id="contact-form">
                            <div class="form-group">
                                <label for="edit-email">البريد الإلكتروني</label>
                                <input type="email" id="edit-email"
                                    value="<?php echo htmlspecialchars($user['email']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="edit-phone">رقم الهاتف</label>
                                <input type="tel" id="edit-phone"
                                    value="<?php echo htmlspecialchars($user['PhoneNumber']); ?>">
                            </div>
                            <div class="form-buttons">
                                <button type="submit" class="btn save-btn">حفظ</button>
                                <button type="button" class="btn cancel-btn">إلغاء</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Settings Section -->
                <div id="settings" class="profile-section">
                    <div class="section-header">
                        <h2>الإعدادات</h2>
                    </div>
                    <div class="section-content">
                        <div class="info-block">
                            <h3>تغيير كلمة المرور</h3>
                            <form id="password-form">
                                <div class="form-group">
                                    <label for="current-password">كلمة المرور الحالية</label>
                                    <input type="password" id="current-password">
                                </div>
                                <div class="form-group">
                                    <label for="new-password">كلمة المرور الجديدة</label>
                                    <input type="password" id="new-password">
                                </div>
                                <div class="form-group">
                                    <label for="confirm-password">تأكيد كلمة المرور</label>
                                    <input type="password" id="confirm-password">
                                </div>
                                <div class="form-buttons">
                                    <button type="submit" class="btn save-btn">تحديث كلمة المرور</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Messages -->
    <div id="status-message" class="status-message"></div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Navigation
        const navLinks = document.querySelectorAll('.profile-nav a');
        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const section = this.getAttribute('data-section');

                // Update active class in navigation
                navLinks.forEach(navLink => {
                    navLink.parentElement.classList.remove('active');
                });
                this.parentElement.classList.add('active');

                // Show active section
                document.querySelectorAll('.profile-section').forEach(sectionElem => {
                    sectionElem.classList.remove('active');
                });
                document.getElementById(section).classList.add('active');
            });
        });

        // Edit buttons
        const editButtons = document.querySelectorAll('.edit-btn');
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const section = this.getAttribute('data-section');
                const contentSection = document.querySelector(`#${section} .section-content`);
                const editSection = document.querySelector(`#${section} .section-edit`);

                contentSection.style.display = 'none';
                editSection.style.display = 'block';
            });
        });

        // Cancel buttons
        const cancelButtons = document.querySelectorAll('.cancel-btn');
        cancelButtons.forEach(button => {
            button.addEventListener('click', function() {
                const formContainer = this.closest('.section-edit');
                const contentContainer = formContainer.previousElementSibling;

                formContainer.style.display = 'none';
                contentContainer.style.display = 'block';
            });
        });

        // Form submissions
        const aboutForm = document.getElementById('about-form');
        aboutForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData();
            formData.append('section', 'about');
            formData.append('bio', document.getElementById('edit-bio').value);
            formData.append('college', document.getElementById('edit-college').value);
            formData.append('major', document.getElementById('edit-major').value);

            // Send data to server
            submitFormData(formData, function(response) {
                if (response.success) {
                    document.getElementById('user-bio').textContent = document.getElementById(
                        'edit-bio').value;
                    document.getElementById('user-college').textContent = document
                        .getElementById('edit-college').value;
                    document.getElementById('user-major').textContent = document.getElementById(
                        'edit-major').value;
                    document.getElementById('user-title').textContent = document.getElementById(
                        'edit-college').value + ' | ' + document.getElementById(
                        'edit-major').value;

                    const sectionElem = document.getElementById('about');
                    sectionElem.querySelector('.section-edit').style.display = 'none';
                    sectionElem.querySelector('.section-content').style.display = 'block';

                    showStatusMessage(response.message, 'success');
                } else {
                    showStatusMessage(response.message, 'error');
                }
            });
        });

        const contactForm = document.getElementById('contact-form');
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData();
            formData.append('section', 'contact');
            formData.append('email', document.getElementById('edit-email').value);
            formData.append('phone', document.getElementById('edit-phone').value);

            // Send data to server
            submitFormData(formData, function(response) {
                if (response.success) {
                    document.getElementById('user-email').textContent = document.getElementById(
                        'edit-email').value;
                    document.getElementById('user-phone').textContent = document.getElementById(
                        'edit-phone').value;

                    const sectionElem = document.getElementById('contact');
                    sectionElem.querySelector('.section-edit').style.display = 'none';
                    sectionElem.querySelector('.section-content').style.display = 'block';

                    showStatusMessage(response.message, 'success');
                } else {
                    showStatusMessage(response.message, 'error');
                }
            });
        });

        const passwordForm = document.getElementById('password-form');
        passwordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const currentPassword = document.getElementById('current-password').value;
            const newPassword = document.getElementById('new-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;

            if (newPassword !== confirmPassword) {
                showStatusMessage('كلمات المرور الجديدة غير متطابقة', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'update_password');
            formData.append('currentPassword', currentPassword);
            formData.append('newPassword', newPassword);

            // Send data to server
            submitFormData(formData, function(response) {
                if (response.success) {
                    document.getElementById('password-form').reset();
                    showStatusMessage(response.message, 'success');
                } else {
                    showStatusMessage(response.message, 'error');
                }
            });
        });

        // Profile image upload
        const changePhotoBtn = document.getElementById('change-photo-btn');
        const fileInput = document.getElementById('profile-image-upload');

        changePhotoBtn.addEventListener('click', function() {
            fileInput.click();
        });

        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                const reader = new FileReader();

                reader.onload = function(e) {
                    const profileImage = document.getElementById('profile-image');
                    profileImage.src = e.target.result;
                    profileImage.style.display = 'block';
                    document.getElementById('profile-image-placeholder').style.display = 'none';

                    // Upload to server
                    const formData = new FormData();
                    formData.append('action', 'update_image');
                    formData.append('profile_image', file);

                    submitFormData(formData, function(response) {
                        if (response.success) {
                            showStatusMessage(response.message, 'success');
                        } else {
                            showStatusMessage(response.message, 'error');
                        }
                    });
                };

                reader.readAsDataURL(file);
            }
        });

        // Helper function to submit form data via AJAX
        function submitFormData(formData, callback) {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'profile.php', true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        callback(response);
                    } catch (e) {
                        console.error('Error parsing JSON response:', e);
                        showStatusMessage('حدث خطأ في معالجة الاستجابة من الخادم', 'error');
                    }
                } else {
                    showStatusMessage('حدث خطأ في الاتصال بالخادم', 'error');
                }
            };

            xhr.onerror = function() {
                showStatusMessage('حدث خطأ في الاتصال بالخادم', 'error');
            };

            xhr.send(formData);
        }

        // Show status message
        function showStatusMessage(message, type) {
            const statusMessage = document.getElementById('status-message');
            statusMessage.textContent = message;
            statusMessage.className = 'status-message show ' + type;

            setTimeout(() => {
                statusMessage.classList.remove('show');
            }, 3000);
        }
    });
    </script>
</body>

</html>