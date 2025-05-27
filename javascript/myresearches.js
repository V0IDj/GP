document.addEventListener('DOMContentLoaded', function() {
    // Make sure modal is initially hidden
    const modalContainer = document.getElementById('modal-container');
    modalContainer.classList.add('modal-hidden');
    modalContainer.style.display = 'none';
    
    // Add direct click handlers to each button
    document.getElementById('completed-research').querySelector('.view-btn').addEventListener('click', function() {
        loadCompletedResearch();
    });
    
    document.getElementById('reward-status').querySelector('.view-btn').addEventListener('click', function() {
        loadRewardStatus();
    });
    
    document.getElementById('service-status').querySelector('.view-btn').addEventListener('click', function() {
        loadServiceStatus();
    });
    
    document.getElementById('research-status').querySelector('.view-btn').addEventListener('click', function() {
        loadResearchStatus();
    });
    
    // Set up modal close events
    document.querySelector('.close-modal').addEventListener('click', function() {
        closeModal();
    });
    
    document.getElementById('modal-close-btn').addEventListener('click', function() {
        closeModal();
    });
    
    // Close modal when clicking outside
    modalContainer.addEventListener('click', function(event) {
        if (event.target === this) {
            closeModal();
        }
    });
});

// Fetch user information
function fetchUserInfo() {
    fetch('myresearches.php?action=getUserInfo')
        .then(response => response.json())
        .then(data => {
            if (data.success && document.getElementById('current-user')) {
                document.getElementById('current-user').textContent = data.userName;
            } else {
                console.error('Error fetching user info:', data.message);
            }
        })
        .catch(error => {
            console.error('Network error:', error);
        });
}

// Load completed research data
function loadCompletedResearch() {
    openModal('الأبحاث المكتملة');
    
    // Show loading indicator
    document.getElementById('modal-body').innerHTML = '<div class="loading">جار التحميل...</div>';
    
    fetch('myresearches.php?action=getCompletedResearch')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayCompletedResearch(data.research);
            } else {
                document.getElementById('modal-body').innerHTML = 
                    '<div class="error-message">' + (data.message || 'حدث خطأ أثناء تحميل البيانات') + '</div>';
            }
        })
        .catch(error => {
            console.error('Network error:', error);
            document.getElementById('modal-body').innerHTML = 
                '<div class="error-message">حدث خطأ في الاتصال بالخادم</div>';
        });
}

// Display completed research data
function displayCompletedResearch(research) {
    let html = '';
    
    if (research.length === 0) {
        html = '<div class="no-data">لا توجد أبحاث مكتملة</div>';
    } else {
        // Add search box
        html = '<div class="search-box">' +
               '<input type="text" id="completed-research-search" placeholder="بحث عن أبحاث..." />' +
               '<button id="completed-research-search-btn">بحث</button>' +
               '</div>';
        
        html += '<table class="data-table"><thead><tr>' +
               '<th>عنوان البحث</th>' +
               '<th>نوع البحث</th>' +
               '<th>معرف DOI</th>' +
               '<th>تاريخ النشر</th>' +
               '<th>الملفات</th>' +
               '</tr></thead><tbody id="research-table-body">';
        
        research.forEach(function(item) {
            const doiValue = item.doi || 'غير متوفر';
            const publishDate = item.publish_date ? formatDate(item.publish_date) : 'غير متوفر';
            
            html += '<tr data-title="' + item.title + '" data-type="' + item.research_type + '">' +
                   '<td>' + item.title + '</td>' +
                   '<td>' + (item.research_type === 'Practical' ? 'عملي' : 'نظري') + '</td>' +
                   '<td>' + doiValue + '</td>' +
                   '<td>' + publishDate + '</td>' +
                   '<td>' + (item.has_files ? 
                      '<a href="download.php?type=research&id=' + item.research_id + '" class="download-link">تحميل</a>' : 
                      'لا توجد ملفات') + '</td>' +
                   '</tr>';
        });
        
        html += '</tbody></table>';
    }
    
    document.getElementById('modal-body').innerHTML = html;
    
    // Add search functionality
    const searchBox = document.getElementById('completed-research-search');
    const searchBtn = document.getElementById('completed-research-search-btn');
    
    if (searchBox && searchBtn) {
        searchBtn.addEventListener('click', function() {
            searchCompletedResearch();
        });
        
        searchBox.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchCompletedResearch();
            }
        });
    }
}

function searchCompletedResearch() {
    const searchTerm = document.getElementById('completed-research-search').value.toLowerCase();
    const researchRows = document.querySelectorAll('#research-table-body tr');
    
    researchRows.forEach(function(row) {
        const title = row.getAttribute('data-title').toLowerCase();
        const type = row.getAttribute('data-type').toLowerCase();
        
        if (title.includes(searchTerm) || type.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Load reward status data
function loadRewardStatus() {
    openModal('حالة المكافآت');
    
    // Show loading indicator
    document.getElementById('modal-body').innerHTML = '<div class="loading">جار التحميل...</div>';
    
    fetch('myresearches.php?action=getRewardStatus')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayRewardStatus(data.rewards);
            } else {
                document.getElementById('modal-body').innerHTML = 
                    '<div class="error-message">' + (data.message || 'حدث خطأ أثناء تحميل البيانات') + '</div>';
            }
        })
        .catch(error => {
            console.error('Network error:', error);
            document.getElementById('modal-body').innerHTML = 
                '<div class="error-message">حدث خطأ في الاتصال بالخادم</div>';
        });
}

// Display reward status
function displayRewardStatus(rewards) {
    let html = '';
    
    if (rewards.length === 0) {
        html = '<div class="no-data">لا توجد طلبات مكافآت</div>';
    } else {
        // Add search box
        html = '<div class="search-box">' +
               '<input type="text" id="reward-search" placeholder="بحث عن مكافآت..." />' +
               '<button id="reward-search-btn">بحث</button>' +
               '</div>';
        
        html += '<div class="rewards-container" id="rewards-container">';
        
        rewards.forEach(function(reward) {
            // Extract research title
            const researchTitle = reward.research_title || 'بحث غير محدد';
            
            // Define status classes
            const adminStatus = reward.approved_admin === 'Yes' ? 'step-completed' : '';
            const vpStatus = reward.approved_vp_academic === 'Yes' ? 'step-completed' : '';
            const presidentStatus = reward.approved_president === 'Yes' ? 'step-completed' : '';
            
            // Calculate current active step
            let currentStep = '';
            if (reward.status === 'rejected') {
                currentStep = 'step-rejected';
            } else if (reward.approved_admin !== 'Yes') {
                currentStep = 'step-active';
            } else if (reward.approved_vp_academic !== 'Yes') {
                currentStep = 'step-active';
            } else if (reward.approved_president !== 'Yes') {
                currentStep = 'step-active';
            }
            
            // Overall status indicator
            let statusClass = '';
            let statusText = '';
            
            switch(reward.status) {
                case 'wait':
                    statusClass = 'status-wait';
                    statusText = 'قيد الانتظار';
                    break;
                case 'approved':
                    statusClass = 'status-approved';
                    statusText = 'تم الصرف';
                    break;
                case 'rejected':
                    statusClass = 'status-rejected';
                    statusText = 'مرفوض';
                    break;
            }
            
            html += '<div class="reward-item" data-title="' + researchTitle + '" data-status="' + reward.status + '">' +
                   '<div class="reward-header">' +
                   '<h3>' + researchTitle + '</h3>' +
                   '<span class="status-indicator ' + statusClass + '">' + statusText + '</span>' +
                   '</div>' +
                   '<div class="reward-details">' +
                   '<p><strong>قيمة المكافأة:</strong> ' + reward.cost + ' شيكل</p>' +
                   '<p><strong>تعليقات المستخدم:</strong> ' + (reward.user_comments || 'لا يوجد') + '</p>' +
                   '<p><strong>تعليقات الإدارة:</strong> ' + (reward.admins_lastComments || 'لا يوجد') + '</p>' +
                   '</div>' +
                   '<div class="progress-tracker">' +
                   '<div class="progress-step ' + adminStatus + ' ' + (reward.approved_admin !== 'Yes' ? currentStep : '') + '">' +
                   '<div class="step-icon">1</div>' +
                   '<div class="step-label">موافقة المدير</div>' +
                   '</div>' +
                   '<div class="progress-step ' + vpStatus + ' ' + (reward.approved_admin === 'Yes' && reward.approved_vp_academic !== 'Yes' ? currentStep : '') + '">' +
                   '<div class="step-icon">2</div>' +
                   '<div class="step-label">موافقة نائب الرئيس الأكاديمي</div>' +
                   '</div>' +
                   '<div class="progress-step ' + presidentStatus + ' ' + (reward.approved_admin === 'Yes' && reward.approved_vp_academic === 'Yes' && reward.approved_president !== 'Yes' ? currentStep : '') + '">' +
                   '<div class="step-icon">3</div>' +
                   '<div class="step-label">موافقة الرئيس</div>' +
                   '</div>' +
                   '<div class="progress-step ' + (reward.status === 'approved' ? 'step-completed' : '') + '">' +
                   '<div class="step-icon">4</div>' +
                   '<div class="step-label">صرف المكافأة</div>' +
                   '</div>' +
                   '</div>' +
                   '</div>';
        });
        
        html += '</div>';
    }
    
    document.getElementById('modal-body').innerHTML = html;
    
    // Add search functionality
    const searchBox = document.getElementById('reward-search');
    const searchBtn = document.getElementById('reward-search-btn');
    
    if (searchBox && searchBtn) {
        searchBtn.addEventListener('click', function() {
            searchRewards();
        });
        
        searchBox.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchRewards();
            }
        });
    }
}

function searchRewards() {
    const searchTerm = document.getElementById('reward-search').value.toLowerCase();
    const rewardItems = document.querySelectorAll('.reward-item');
    
    rewardItems.forEach(function(item) {
        const title = item.getAttribute('data-title').toLowerCase();
        const status = item.getAttribute('data-status').toLowerCase();
        
        if (title.includes(searchTerm) || status.includes(searchTerm)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

// Load service status data
function loadServiceStatus() {
    openModal('حالة الخدمات');
    
    // Show loading indicator
    document.getElementById('modal-body').innerHTML = '<div class="loading">جار التحميل...</div>';
    
    fetch('myresearches.php?action=getServiceStatus')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayServiceStatus(data.services);
            } else {
                document.getElementById('modal-body').innerHTML = 
                    '<div class="error-message">' + (data.message || 'حدث خطأ أثناء تحميل البيانات') + '</div>';
            }
        })
        .catch(error => {
            console.error('Network error:', error);
            document.getElementById('modal-body').innerHTML = 
                '<div class="error-message">حدث خطأ في الاتصال بالخادم</div>';
        });
}

// Display service status
function displayServiceStatus(services) {
    let html = '';
    
    if (services.length === 0) {
        html = '<div class="no-data">لا توجد طلبات خدمات</div>';
    } else {
        // Add search box
        html = '<div class="search-box">' +
               '<input type="text" id="service-search" placeholder="بحث عن خدمات..." />' +
               '<button id="service-search-btn">بحث</button>' +
               '</div>';
        
        html += '<div class="services-container" id="services-container">';
        
        services.forEach(function(service) {
            // Define status classes and text
            let statusClass = '';
            let statusText = '';
            let currentStep = '';
            
            switch(service.status) {
                case 'wait 1':
                    statusClass = 'status-wait';
                    statusText = 'بانتظار موافقة المدير';
                    currentStep = 'admin-approval';
                    break;
                case 'wait 2':
                    statusClass = 'status-wait';
                    statusText = 'بانتظار موافقة مقدم الخدمة';
                    currentStep = 'provider-approval';
                    break;
                case 'approved':
                    statusClass = 'status-approved';
                    statusText = 'تمت الموافقة';
                    currentStep = 'completed';
                    break;
                case 'rejected':
                    statusClass = 'status-rejected';
                    statusText = 'مرفوض';
                    currentStep = 'rejected';
                    break;
            }
            
            html += '<div class="service-item" data-type="' + service.service_type + '" data-status="' + service.status + '">' +
                   '<div class="service-header">' +
                   '<h3>' + service.service_type + '</h3>' +
                   '<span class="status-indicator ' + statusClass + '">' + statusText + '</span>' +
                   '</div>' +
                   '<div class="service-details">' +
                   '<p><strong>تاريخ الطلب:</strong> ' + formatDate(service.created_at) + '</p>' +
                   '<p><strong>ملاحظات:</strong> ' + (service.notes || 'لا يوجد') + '</p>' +
                   '</div>' +
                   '<div class="progress-tracker">' +
                   '<div class="progress-step ' + (currentStep === 'admin-approval' ? 'step-active' : '') + ' ' + (['approved', 'wait 2'].includes(service.status) ? 'step-completed' : '') + ' ' + (service.status === 'rejected' ? 'step-rejected' : '') + '">' +
                   '<div class="step-icon">1</div>' +
                   '<div class="step-label">موافقة المدير</div>' +
                   '</div>' +
                   '<div class="progress-step ' + (currentStep === 'provider-approval' ? 'step-active' : '') + ' ' + (service.status === 'approved' ? 'step-completed' : '') + ' ' + (service.status === 'rejected' && service.status !== 'wait 1' ? 'step-rejected' : '') + '">' +
                   '<div class="step-icon">2</div>' +
                   '<div class="step-label">موافقة مقدم الخدمة</div>' +
                   '</div>' +
                   '<div class="progress-step ' + (currentStep === 'completed' ? 'step-completed' : '') + '">' +
                   '<div class="step-icon">3</div>' +
                   '<div class="step-label">اكتمال الطلب</div>' +
                   '</div>' +
                   '</div>' +
                   '</div>';
        });
        
        html += '</div>';
    }
    
    document.getElementById('modal-body').innerHTML = html;
    
    // Add search functionality
    const searchBox = document.getElementById('service-search');
    const searchBtn = document.getElementById('service-search-btn');
    
    if (searchBox && searchBtn) {
        searchBtn.addEventListener('click', function() {
            searchServices();
        });
        
        searchBox.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchServices();
            }
        });
    }
}

function searchServices() {
    const searchTerm = document.getElementById('service-search').value.toLowerCase();
    const serviceItems = document.querySelectorAll('.service-item');
    
    serviceItems.forEach(function(item) {
        const type = item.getAttribute('data-type').toLowerCase();
        const status = item.getAttribute('data-status').toLowerCase();
        
        if (type.includes(searchTerm) || status.includes(searchTerm)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

// Load research status data
function loadResearchStatus() {
    openModal('حالة الأبحاث');
    
    // Show loading indicator
    document.getElementById('modal-body').innerHTML = '<div class="loading">جار التحميل...</div>';
    
    fetch('myresearches.php?action=getResearchStatus')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayResearchStatus(data.research);
            } else {
                document.getElementById('modal-body').innerHTML = 
                    '<div class="error-message">' + (data.message || 'حدث خطأ أثناء تحميل البيانات') + '</div>';
            }
        })
        .catch(error => {
            console.error('Network error:', error);
            document.getElementById('modal-body').innerHTML = 
                '<div class="error-message">حدث خطأ في الاتصال بالخادم</div>';
        });
}

// Display research status
function displayResearchStatus(researchItems) {
    let html = '';
    
    if (researchItems.length === 0) {
        html = '<div class="no-data">لا توجد أبحاث مقدمة</div>';
    } else {
        // Add search box
        html = '<div class="search-box">' +
               '<input type="text" id="research-search" placeholder="بحث عن أبحاث..." />' +
               '<button id="research-search-btn">بحث</button>' +
               '</div>';
        
        html += '<div class="research-status-container">';
        
        researchItems.forEach(function(item) {
            // Define status classes and text
            let statusClass = '';
            let statusText = '';
            let currentStep = '';
            
            switch(item.status) {
                case 'Pending 1':
                    statusClass = 'status-pending';
                    statusText = 'قيد مراجعة الإدارة';
                    currentStep = 'admin-review';
                    break;
                case 'Pending 2':
                    statusClass = 'status-pending';
                    statusText = 'قيد مراجعة الباحث';
                    currentStep = 'reviewer-review';
                    break;
                case 'Pending':
                    statusClass = 'status-pending';
                    statusText = 'قيد المراجعة';
                    currentStep = 'admin-review';
                    break;
                case 'Approved':
                    statusClass = 'status-approved';
                    statusText = 'تمت الموافقة';
                    currentStep = 'completed';
                    break;
                case 'Rejected':
                    statusClass = 'status-rejected';
                    statusText = 'مرفوض';
                    currentStep = 'rejected';
                    break;
            }
            
            // Format classification and publication type
            let classificationText = '';
            switch(item.classification) {
                case 'Q1': classificationText = 'Q1'; break;
                case 'Q2': classificationText = 'Q2'; break;
                case 'Q3': classificationText = 'Q3'; break;
                case 'Q4': classificationText = 'Q4'; break;
            }
            
            let publicationText = '';
            switch(item.where_to_publish) {
                case 'Journal': publicationText = 'مجلة علمية'; break;
                case 'Book Chapter': publicationText = 'فصل في كتاب'; break;
                case 'Conference': publicationText = 'مؤتمر'; break;
            }
            
            html += '<div class="research-item" data-title="' + item.title + '" data-college="' + (item.college || '') + '">' +
                   '<div class="research-header">' +
                   '<h3>' + item.title + '</h3>' +
                   '<span class="status-indicator ' + statusClass + '">' + statusText + '</span>' +
                   '</div>' +
                   '<div class="research-details">' +
                   '<p><strong>التصنيف:</strong> ' + classificationText + '</p>' +
                   '<p><strong>نوع النشر:</strong> ' + publicationText + '</p>' +
                   '<p><strong>الكلية:</strong> ' + (item.college || 'غير محدد') + '</p>' +
                   '<p><strong>تاريخ التقديم:</strong> ' + formatDate(item.submission_date) + '</p>' +
                   (item.user_notes ? '<p><strong>ملاحظات الباحث:</strong> ' + item.user_notes + '</p>' : '') +
                   (item.admin_notes ? '<p><strong>ملاحظات الإدارة:</strong> ' + item.admin_notes + '</p>' : '') +
                   '</div>' +
                   
                   // Add progress tracker for research status
                   '<div class="progress-tracker">' +
                   '<div class="progress-step ' + (currentStep === 'admin-review' ? 'step-active' : '') + ' ' + 
                   (['Pending 2', 'Approved'].includes(item.status) ? 'step-completed' : '') + ' ' + 
                   (item.status === 'Rejected' ? 'step-rejected' : '') + '">' +
                   '<div class="step-icon">1</div>' +
                   '<div class="step-label">مراجعة الإدارة</div>' +
                   '</div>' +
                   '<div class="progress-step ' + (currentStep === 'reviewer-review' ? 'step-active' : '') + ' ' + 
                   (['Approved'].includes(item.status) ? 'step-completed' : '') + ' ' + 
                   (item.status === 'Rejected' && item.status !== 'Pending 1' ? 'step-rejected' : '') + '">' +
                   '<div class="step-icon">2</div>' +
                   '<div class="step-label">مراجعة الباحث</div>' +
                   '</div>' +
                   '<div class="progress-step ' + (currentStep === 'completed' ? 'step-completed' : '') + '">' +
                   '<div class="step-icon">3</div>' +
                   '<div class="step-label">موافقة ونشر</div>' +
                   '</div>' +
                   '</div>' +
                   
                   '<div class="file-section">' +
                   (item.has_files ? 
                   '<a href="download.php?type=research_submission&id=' + item.id + '" class="download-link">تحميل الملفات</a>' : 
                   '<p>لا توجد ملفات مرفقة</p>') +
                   '</div>' +
                   '</div>';
        });
        
        html += '</div>';
    }
    
    document.getElementById('modal-body').innerHTML = html;
    
    // Add search functionality
    const searchBox = document.getElementById('research-search');
    const searchBtn = document.getElementById('research-search-btn');
    
    if (searchBox && searchBtn) {
        searchBtn.addEventListener('click', function() {
            searchResearch();
        });
        
        searchBox.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchResearch();
            }
        });
    }
}

function searchResearch() {
    const searchTerm = document.getElementById('research-search').value.toLowerCase();
    const researchItems = document.querySelectorAll('.research-item');
    
    researchItems.forEach(function(item) {
        const title = item.getAttribute('data-title').toLowerCase();
        const college = item.getAttribute('data-college').toLowerCase();
        
        if (title.includes(searchTerm) || college.includes(searchTerm)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

// Helper function to format dates
function formatDate(dateString) {
    if (!dateString) return 'غير متوفر';
    
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return dateString; // Return original if invalid
    
    return date.toLocaleDateString('ar-SA');
}

// Open modal with specific title
function openModal(title) {
    document.getElementById('modal-title').textContent = title;
    const modalContainer = document.getElementById('modal-container');
    modalContainer.classList.remove('modal-hidden');
    modalContainer.style.display = 'flex';
    document.body.style.overflow = 'hidden'; // Prevent scrolling
}

// Close modal
function closeModal() {
    const modalContainer = document.getElementById('modal-container');
    modalContainer.classList.add('modal-hidden');
    modalContainer.style.display = 'none';
    document.body.style.overflow = 'auto'; // Restore scrolling
}