<?php include 'login_check.php'; ?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دائرة البحث العلمي</title>
    <link rel="stylesheet" href="./styles/userhome.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>

    <?php check_login(); ?>

    <header>
        <h1>دائرة البحث العلمي</h1>
        <p>نحو مستقبل أفضل من خلال البحث والابتكار</p>
    </header>
    <nav>
        <ul>
            <li><a href="#" id="home-link">الرئيسية</a></li>
            <li><a href="researchsubmit.html" id="submit-research-link">تقديم على بحث علمي</a></li>
            <li><a href="ResearchParticipation.html" id="participate-link">المشاركة في بحث</a></li>
            <li><a href="userreward.html" id="request-reward-link">طلب مكافأة</a></li>
            <li><a href="service.html" id="service-request-link">طلب خدمة</a></li>
            <li><a href="myresearches.html" id="my-research-link">صفحة أبحاثي</a></li>
            <li><a href="profile.html" id="profile-link">تعديل الملف الشخصي</a></li>
            <li><a href="logout.php" id="logout-link">تسجيل الخروج</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="options">
            <div class="option-card" id="submit-research">
                <i class="fas fa-file-alt"></i>
                <h3>تقديم على بحث علمي</h3>
                <p>قم بتقديم مقترح بحث علمي جديد للحصول على الدعم والتمويل</p>
                <a href="researchsubmit.html" class="btn">تقديم طلب</a>
            </div>

            <div class="option-card" id="participate">
                <i class="fas fa-users"></i>
                <h3>المشاركة في بحث</h3>
                <p>استعرض الأبحاث المتاحة وتقدم للمشاركة في المشاريع البحثية</p>
                <a href="ResearchParticipation.html" class="btn">استعراض الفرص</a>
            </div>

            <div class="option-card" id="request-reward">
                <i class="fas fa-award"></i>
                <h3>طلب مكافأة</h3>
                <p>تقديم طلب للحصول على مكافأة الإنجاز العلمي أو البحثي</p>
                <a href="userreward.html" class="btn">تقديم طلب</a>
            </div>

            <div class="option-card" id="service-request">
                <i class="fas fa-concierge-bell"></i>
                <h3>طلب خدمة</h3>
                <p>طلب خدمات متنوعة مثل الاحصاء التحليلي وخدمات الترجمة والتدقيق اللغوي وخدمات الدعم الفني</p>
                <a href="service.html" class="btn">طلب خدمة</a>
            </div>

            <div class="option-card" id="my-research">
                <i class="fas fa-book"></i>
                <h3>صفحة أبحاثي</h3>
                <p>استعرض وتتبع الأبحاث الخاصة بك والمنشورات العلمية</p>
                <a href="myresearches.html" class="btn">استعراض</a>
            </div>

            <div class="option-card" id="profile">
                <i class="fas fa-user-edit"></i>
                <h3>تعديل الملف الشخصي</h3>
                <p>قم بتحديث معلوماتك الشخصية، المؤهلات، والاهتمامات البحثية</p>
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
        <p>جميع الحقوق محفوظة &copy; 2025 دائرة البحث العلمي</p>
    </footer>

    <script>
    // الحصول على مراجع للعناصر
    const modal = document.getElementById('feature-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalMessage = document.getElementById('modal-message');
    const closeModal = document.querySelector('.close-modal');

    // قائمة بجميع الروابط وكروت الخيارات
    const allLinks = [



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
        // في تطبيق حقيقي سينتقل إلى الصفحة الرئيسية
        // حالياً فقط سيعيد تحميل الصفحة
        window.location.reload();
    });
    </script>
</body>

</html>