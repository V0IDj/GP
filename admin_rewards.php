<?php
// Start session if not already started
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'Admin') {
    header('Location: login.php');
    exit;
}

// Include database configuration
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة طلبات المكافآت</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./styles/admin_rewards.css">
    <style>
    /* Additional styles for return functionality */
    .status-returned {
        background-color: #3498db;
        color: white;
    }

    .returned {
        border-color: #3498db !important;
    }

    .returned .step-circle {
        background-color: #3498db !important;
        color: white !important;
    }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>إدارة طلبات المكافآت</h1>
            <div class="notification-badge">
                <i class="fas fa-bell fa-lg"></i>
                <span class="badge" id="notificationCount">15</span>
            </div>
        </div>

        <div class="filter-container">
            <label for="statusFilter">تصفية حسب الحالة:</label>
            <select id="statusFilter" class="filter-dropdown">
                <option value="all">جميع الطلبات</option>
                <option value="wait">قيد الانتظار</option>
                <option value="approved">تمت الموافقة</option>
                <option value="rejected">تم الرفض</option>
                <option value="returned">مرجع للتعديل</option>
            </select>
        </div>

        <!-- Cards Container for Rewards -->
        <div class="cards-container" id="rewardsContainer">
            <!-- Cards will be added dynamically by JavaScript -->
            <div class="loader" id="loader"></div>
        </div>

        <!-- Reward Details Container -->
        <div class="reward-details" id="rewardDetails">
            <h2 class="card-title" id="detailTitle">تفاصيل طلب المكافأة</h2>

            <!-- Approval Process Visualization -->
            <div class="approval-process" id="approvalProcess">
                <div class="progress-line">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
                <div class="approval-step" id="stepUser">
                    <div class="step-circle">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="step-label">تقديم الطلب</div>
                </div>
                <div class="approval-step" id="stepAdmin">
                    <div class="step-circle">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="step-label">موافقة المشرف</div>
                </div>
                <div class="approval-step" id="stepVP">
                    <div class="step-circle">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="step-label">موافقة نائب الرئيس</div>
                </div>
                <div class="approval-step" id="stepPresident">
                    <div class="step-circle">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="step-label">موافقة الرئيس</div>
                </div>
                <div class="approval-step" id="stepFinance">
                    <div class="step-circle">
                        <i class="fas fa-money-check-alt"></i>
                    </div>
                    <div class="step-label">الصرف المالي</div>
                </div>
            </div>

            <!-- Details Tabs -->
            <div class="tabs">
                <button class="tab-btn active" data-tab="basicInfo">المعلومات الأساسية</button>
                <button class="tab-btn" data-tab="files">الملفات المرفقة</button>
                <button class="tab-btn" data-tab="comments">التعليقات</button>
            </div>

            <!-- Tab Contents -->
            <div class="tab-content active" id="basicInfo">
                <div class="data-row">
                    <span class="data-label">رقم الطلب:</span>
                    <span class="data-value" id="detailRewardId"></span>
                </div>
                <div class="data-row">
                    <span class="data-label">اسم الباحث:</span>
                    <span class="data-value" id="detailResearcherName"></span>
                </div>
                <div class="data-row">
                    <span class="data-label">عنوان البحث:</span>
                    <span class="data-value" id="detailResearchTitle"></span>
                </div>
                <div class="data-row">
                    <span class="data-label">المبلغ المطلوب:</span>
                    <span class="data-value" id="detailCost"></span>
                </div>
                <div class="data-row">
                    <span class="data-label">تاريخ التقديم:</span>
                    <span class="data-value" id="detailCreatedAt"></span>
                </div>
                <div class="data-row">
                    <span class="data-label">الحالة:</span>
                    <span class="data-value" id="detailStatus"></span>
                </div>
                <div class="data-row">
                    <span class="data-label">ملاحظات الباحث:</span>
                    <div class="data-value" id="detailUserComments"></div>
                </div>
            </div>

            <div class="tab-content" id="files">
                <h3>الملفات المرفقة</h3>
                <div id="filesList">
                    <!-- Files will be added here -->
                </div>
            </div>

            <div class="tab-content" id="comments">
                <h3>تعليقات المراجعة</h3>
                <div id="commentsList">
                    <!-- Comments will be added here -->
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons" id="actionButtons">
                <button class="btn btn-success" id="approveBtn">موافقة</button>
                <button class="btn btn-danger" id="rejectBtn">رفض</button>
                <button class="btn btn-warning" id="commentBtn">إضافة تعليق</button>
                <button class="btn btn-primary" id="backBtn">العودة</button>
            </div>
        </div>
    </div>

    <!-- Add Comment Modal -->
    <div class="modal" id="commentModal">
        <div class="modal-content">
            <span class="close-modal" id="closeCommentModal">&times;</span>
            <h2 class="modal-title">إضافة تعليق</h2>
            <form id="commentForm">
                <div class="form-group">
                    <label for="commentText">التعليق:</label>
                    <textarea class="form-control" id="commentText" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="cancelComment">إلغاء</button>
                    <button type="submit" class="btn btn-primary">إرسال</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal" id="rejectModal">
        <div class="modal-content">
            <span class="close-modal" id="closeRejectModal">&times;</span>
            <h2 class="modal-title">تأكيد رفض الطلب</h2>
            <form id="rejectForm">
                <div class="form-group">
                    <label for="rejectReason">سبب الرفض:</label>
                    <textarea class="form-control" id="rejectReason" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="cancelReject">إلغاء</button>
                    <button type="submit" class="btn btn-danger">تأكيد الرفض</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Approve Modal -->
    <div class="modal" id="approveModal">
        <div class="modal-content">
            <span class="close-modal" id="closeApproveModal">&times;</span>
            <h2 class="modal-title">تأكيد الموافقة على الطلب</h2>
            <form id="approveForm">
                <div class="form-group">
                    <label for="approveComments">ملاحظات (اختياري):</label>
                    <textarea class="form-control" id="approveComments"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="cancelApprove">إلغاء</button>
                    <button type="submit" class="btn btn-success">تأكيد الموافقة</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Return for Edit Modal -->
    <div class="modal" id="returnModal">
        <div class="modal-content">
            <span class="close-modal" id="closeReturnModal">&times;</span>
            <h2 class="modal-title">إرجاع الطلب للتعديل</h2>
            <form id="returnForm">
                <div class="form-group">
                    <label for="returnTarget">إرجاع إلى:</label>
                    <select class="form-control" id="returnTarget" required>
                        <!-- Options will be populated dynamically by JavaScript based on user role -->
                    </select>
                </div>
                <div class="form-group">
                    <label for="returnReason">سبب الإرجاع وملاحظات التعديل:</label>
                    <textarea class="form-control" id="returnReason" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="cancelReturn">إلغاء</button>
                    <button type="submit" class="btn btn-info">تأكيد الإرجاع</button>
                </div>
            </form>
        </div>
    </div>

    <!-- PHP session data to be used in JavaScript -->
    <script>
    // Current user info from PHP session
    const currentUser = {
        userId: '<?php echo $_SESSION["user_id"]; ?>',
        username: '<?php echo $_SESSION["username"]; ?>',
        role: '<?php echo $_SESSION["role"]; ?>'
    };
    </script>

    <!-- Include JavaScript file -->
    <script src="./javascript/admin_rewards.js"></script>
</body>

</html>