// Admin Researches JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Get admin info
    fetchAdminInfo();
    
    // Load initial researches data
    loadResearches();
    
    // Set up filter event listeners
    document.getElementById('status-filter').addEventListener('change', function() {
        loadResearches(1); // Reset to page 1 when filter changes
    });
    
    document.getElementById('research-type-filter').addEventListener('change', function() {
        loadResearches(1); // Reset to page 1 when filter changes
    });
    
    document.getElementById('publish-filter').addEventListener('change', function() {
        loadResearches(1); // Reset to page 1 when filter changes
    });
    
    // Set up search functionality
    document.getElementById('search-btn').addEventListener('click', function() {
        loadResearches(1); // Reset to page 1 when searching
    });
    
    document.getElementById('search-input').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            loadResearches(1); // Reset to page 1 when searching
        }
    });
    
    // Set up modal event listeners
    const researchModal = document.getElementById('research-modal');
    const assignModal = document.getElementById('assign-modal');
    
    document.querySelector('.close-modal').addEventListener('click', function() {
        researchModal.style.display = 'none';
    });
    
    document.getElementById('modal-close-btn').addEventListener('click', function() {
        researchModal.style.display = 'none';
    });
    
    document.querySelector('.close-assign-modal').addEventListener('click', function() {
        assignModal.style.display = 'none';
    });
    
    document.getElementById('cancel-assign-btn').addEventListener('click', function() {
        assignModal.style.display = 'none';
    });
    
    // Close modals when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === researchModal) {
            researchModal.style.display = 'none';
        }
        if (event.target === assignModal) {
            assignModal.style.display = 'none';
        }
    });
    
    // Set up assign button event
    document.getElementById('assign-btn').addEventListener('click', function() {
        assignReviewersToResearch();
    });
});

// Fetch admin information
function fetchAdminInfo() {
    fetch('admin_researches_handler.php?action=getAdminInfo')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('admin-name').textContent = data.adminName;
            } else {
                console.error('Error fetching admin info:', data.message);
            }
        })
        .catch(error => {
            console.error('Network error:', error);
        });
}

// Load researches with pagination and filters
function loadResearches(page = 1) {
    const researchesContainer = document.getElementById('researches-container');
    researchesContainer.innerHTML = '<div class="loading">جار تحميل طلبات الأبحاث...</div>';
    
    // Set a timeout to show a message if loading takes too long
    const loadingTimeout = setTimeout(() => {
        console.log('Loading timeout reached');
        researchesContainer.innerHTML = '<div class="no-researches">استغرق التحميل وقتًا طويلًا. جاري المحاولة بإستخدام البيانات التجريبية...</div>';
        
        // Try using test data as fallback
        fetch(`admin_researches_handler.php?action=getResearches&test=1`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.researches) {
                    displayResearches(data.researches);
                    displayPagination(data.pagination);
                } else {
                    researchesContainer.innerHTML = '<div class="no-researches">لا يمكن تحميل البيانات. يرجى التحقق من الاتصال بقاعدة البيانات.</div>';
                }
            })
            .catch(error => {
                researchesContainer.innerHTML = '<div class="no-researches">فشل استرجاع البيانات التجريبية أيضًا. يرجى التحقق من تكوين الخادم.</div>';
            });
    }, 5000); // 5 seconds timeout
    
    // Get filter values
    const statusFilter = document.getElementById('status-filter').value;
    const researchTypeFilter = document.getElementById('research-type-filter').value;
    const publishFilter = document.getElementById('publish-filter').value;
    const searchTerm = document.getElementById('search-input').value;
    
    // Build API request
    let apiUrl = `admin_researches_handler.php?action=getResearches&page=${page}`;
    
    if (statusFilter !== 'all') {
        apiUrl += `&status=${encodeURIComponent(statusFilter)}`;
    }
    
    if (researchTypeFilter !== 'all') {
        apiUrl += `&r_type=${encodeURIComponent(researchTypeFilter)}`;
    }
    
    if (publishFilter !== 'all') {
        apiUrl += `&publish_type=${encodeURIComponent(publishFilter)}`;
    }
    
    if (searchTerm) {
        apiUrl += `&search=${encodeURIComponent(searchTerm)}`;
    }
    
    console.log('Fetching researches from:', apiUrl); // Debug log
    
    fetch(apiUrl)
        .then(response => {
            clearTimeout(loadingTimeout); // Clear the timeout
            
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('API response:', data); // Debug log
            
            if (data.success) {
                if (data.researches && Array.isArray(data.researches)) {
                    console.log(`Loaded ${data.researches.length} researches`); // Debug log
                    
                    if (data.researches.length === 0) {
                        researchesContainer.innerHTML = '<div class="no-researches">لا توجد أبحاث متاحة</div>';
                    } else {
                        displayResearches(data.researches);
                        displayPagination(data.pagination);
                    }
                } else {
                    console.error('Invalid researches data format:', data.researches);
                    researchesContainer.innerHTML = '<div class="no-researches">خطأ في تنسيق البيانات المستلمة</div>';
                }
            } else {
                console.error('API error:', data.message);
                researchesContainer.innerHTML = 
                    `<div class="no-researches">${data.message || 'حدث خطأ أثناء تحميل البيانات'}</div>`;
            }
        })
        .catch(error => {
            clearTimeout(loadingTimeout); // Clear the timeout
            console.error('Network or parsing error:', error);
            researchesContainer.innerHTML = 
                `<div class="no-researches">حدث خطأ في الاتصال بالخادم: ${error.message}</div>`;
        });
}

// Display researches
function displayResearches(researches) {
    const researchesContainer = document.getElementById('researches-container');
    
    if (researches.length === 0) {
        researchesContainer.innerHTML = '<div class="no-researches">لا توجد أبحاث مطابقة للفلتر</div>';
        return;
    }
    
    let html = '';
    researches.forEach(function(research) {
        // Define status classes and text
        let statusClass = '';
        let statusText = '';
        
        switch(research.status) {
            case 'Pending 1':
                statusClass = 'status-pending1';
                statusText = 'بانتظار موافقة المدير';
                break;
            case 'Pending 2':
                statusClass = 'status-pending2';
                statusText = 'بانتظار المراجعة النهائية';
                break;
            case 'Approved':
                statusClass = 'status-approved';
                statusText = 'تمت الموافقة';
                break;
            case 'Rejected':
                statusClass = 'status-rejected';
                statusText = 'مرفوض';
                break;
        }
        
        const submissionDate = new Date(research.submission_date).toLocaleDateString('ar-US');
        
        // Create dynamic classification badge if exists
        let classificationBadge = '';
        if (research.classification) {
            classificationBadge = `<span class="classification-badge classification-${research.classification}">${research.classification}</span>`;
        }
        
        html += `
            <div class="research-card" data-id="${research.id}">
                <div class="research-header">
                    <div class="research-title">${research.title}</div>
                    <div class="research-status ${statusClass}">${statusText}</div>
                </div>
                <div class="research-info">
                    <p><strong>الباحث:</strong> ${research.researcher_name || 'غير معروف'}</p>
                    <p><strong>تاريخ التقديم:</strong> ${submissionDate}</p>
                    <p><strong>النوع:</strong> ${research.r_type === 'practical' ? 'عملي' : 'نظري'}</p>
                    <p><strong>وسيلة النشر:</strong> ${getPublishTypeText(research.where_to_publish)}</p>
                    <p><strong>التصنيف:</strong> ${classificationBadge || 'غير مصنف'}</p>
                </div>
                <div class="research-actions">
                    <button class="btn btn-view" onclick="viewResearchDetails(${research.id})">عرض التفاصيل</button>`;
                    
        // Show appropriate buttons based on status
        if (research.status === 'Pending 1') {
            html += `
                <button class="btn btn-approve" onclick="approveAndAssignReviewers(${research.id})">موافقة وتعيين مراجعين</button>
                <button class="btn btn-reject" onclick="rejectResearch(${research.id})">رفض</button>`;
        } else if (research.status === 'Pending 2') {
            html += `
                <button class="btn btn-approve" onclick="approveResearch(${research.id})">موافقة نهائية</button>
                <button class="btn btn-reject" onclick="rejectResearch(${research.id})">رفض</button>`;
        }
        
        html += `
                </div>
            </div>`;
    });
    
    researchesContainer.innerHTML = html;
}

// Get publish type text in Arabic
function getPublishTypeText(publishType) {
    switch (publishType) {
        case 'Journal':
            return 'مجلة علمية';
        case 'Book Chapter':
            return 'فصل في كتاب';
        case 'Conference':
            return 'مؤتمر علمي';
        default:
            return publishType;
    }
}

// Display pagination
function displayPagination(pagination) {
    const paginationContainer = document.getElementById('pagination');
    
    if (pagination.total_pages <= 1) {
        paginationContainer.innerHTML = '';
        return;
    }
    
    let html = '';
    
    // Previous button
    if (pagination.current_page > 1) {
        html += `<button class="page-btn" onclick="loadResearches(${pagination.current_page - 1})">السابق</button>`;
    }
    
    // Page buttons
    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i === pagination.current_page) {
            html += `<button class="page-btn active">${i}</button>`;
        } else {
            html += `<button class="page-btn" onclick="loadResearches(${i})">${i}</button>`;
        }
    }
    
    // Next button
    if (pagination.current_page < pagination.total_pages) {
        html += `<button class="page-btn" onclick="loadResearches(${pagination.current_page + 1})">التالي</button>`;
    }
    
    paginationContainer.innerHTML = html;
}

// View research details with improved error handling
// View research details with improved error handling
function viewResearchDetails(researchId) {
    const modal = document.getElementById('research-modal');
    const modalBody = document.getElementById('modal-body');
    
    // Show loading indicator
    modalBody.innerHTML = '<div class="loading">جار تحميل التفاصيل...</div>';
    modal.style.display = 'block';
    
    // Clear any existing timeout
    if (window.researchDetailsTimeout) {
        clearTimeout(window.researchDetailsTimeout);
    }
    
    // Set timeout to show error if taking too long
    window.researchDetailsTimeout = setTimeout(() => {
        modalBody.innerHTML = '<div class="error-message">يستغرق التحميل وقتًا طويلًا. جارِ المحاولة مرة أخرى...</div>';
    }, 10000); // 10 seconds
    
    fetch(`admin_researches_handler.php?action=getResearchDetails&id=${researchId}`)
        .then(response => {
            // Clear timeout since we got a response
            clearTimeout(window.researchDetailsTimeout);
            
            if (!response.ok) {
                console.error(`HTTP error! Status: ${response.status}`);
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log("Research details response:", data); // Debug log
            
            if (data && data.success) {
                displayResearchDetails(data.research);
            } else {
                const errorMessage = data && data.message ? data.message : 'حدث خطأ أثناء تحميل البيانات';
                modalBody.innerHTML = `<div class="error-message">${errorMessage}</div>`;
            }
        })
        .catch(error => {
            // Clear timeout since we got an error
            clearTimeout(window.researchDetailsTimeout);
            
            console.error('Error fetching research details:', error);
            modalBody.innerHTML = 
                '<div class="error-message">حدث خطأ في الاتصال بالخادم. يرجى التأكد من اتصالك بالإنترنت وإعادة المحاولة.</div>';
        });
}

// Display research details in modal
// Display research details in modal with better error handling
function displayResearchDetails(research) {
    if (!research) {
        const modalBody = document.getElementById('modal-body');
        modalBody.innerHTML = '<div class="error-message">لا توجد بيانات للبحث المطلوب</div>';
        return;
    }
    
    const modalBody = document.getElementById('modal-body');
    const modalTitle = document.getElementById('modal-title');
    
    // Update modal title
    modalTitle.textContent = `تفاصيل البحث: ${research.title || 'بدون عنوان'}`;
    
    // Define status text
    let statusText = '';
    switch(research.status) {
        case 'Pending 1': statusText = 'بانتظار موافقة المدير'; break;
        case 'Pending 2': statusText = 'بانتظار المراجعة النهائية'; break;
        case 'Approved': statusText = 'تمت الموافقة'; break;
        case 'Rejected': statusText = 'مرفوض'; break;
        default: statusText = 'غير معروف';
    }
    
    // Format date - safely
    let submissionDate = 'غير محدد';
    try {
        if (research.submission_date) {
            submissionDate = new Date(research.submission_date).toLocaleDateString('ar-US');
        }
    } catch (e) {
        console.error("Error formatting date:", e);
    }
    
    // Create classification badge
    let classificationBadge = '';
    if (research.classification) {
        classificationBadge = `<span class="classification-badge classification-${research.classification}">${research.classification}</span>`;
    }
    
    // Start building HTML for research details
    let html = `
        <div class="research-detail">
            <h3>معلومات البحث</h3>
            <div class="detail-row">
                <div class="detail-label">رقم البحث:</div>
                <div class="detail-value">${research.id || 'غير معروف'}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">عنوان البحث:</div>
                <div class="detail-value">${research.title || 'غير معروف'}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">اسم الباحث:</div>
                <div class="detail-value">${research.researcher_name || 'غير معروف'}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">الكلية:</div>
                <div class="detail-value">${research.college || research.researcher_college || 'غير محدد'}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">تاريخ التقديم:</div>
                <div class="detail-value">${submissionDate}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">الحالة:</div>
                <div class="detail-value">${statusText}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">نوع البحث:</div>
                <div class="detail-value">${research.r_type === 'practical' ? 'عملي' : 'نظري'}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">وسيلة النشر:</div>
                <div class="detail-value">${getPublishTypeText(research.where_to_publish)}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">التصنيف:</div>
                <div class="detail-value">${classificationBadge || 'غير مصنف'}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">مشاركة البحث:</div>
                <div class="detail-value">${research.is_shared ? 'تم مشاركته' : 'غير مشارك'}</div>
            </div>
        </div>`;
    
    // Add participants section if available
    if (research.participants && research.participants.length > 0) {
        html += `
            <div class="participants-section">
                <h4>المشاركون في البحث:</h4>`;
                
        research.participants.forEach(participant => {
            html += `
                <div class="participant-item">
                    <div class="participant-icon">👨‍🎓</div>
                    <div class="participant-name">${participant.name}</div>
                </div>`;
        });
        
        html += `
            </div>`;
    }
    
    // Add reviewers section if available
    if (research.reviewers && research.reviewers.length > 0) {
        html += `
            <div class="research-detail">
                <h3>المراجعون المعينون</h3>`;
                
        research.reviewers.forEach(reviewer => {
            const reviewStatus = reviewer.review_status === 'reviewed' ? 'تمت المراجعة' : 'بانتظار المراجعة';
            const statusClass = reviewer.review_status === 'reviewed' ? 'status-approved' : 'status-pending2';
            
            html += `
                <div class="reviewer-info-item">
                    <div class="reviewer-header">
                        <div class="reviewer-name">${reviewer.reviewer_name || 'غير معروف'}</div>
                        <div class="reviewer-status ${statusClass}">${reviewStatus}</div>
                    </div>
                    <div class="reviewer-details">
                        <p><strong>الدور:</strong> ${reviewer.reviewer_role === 'Staff' ? 'موظف' : 'باحث'}</p>
                        <p><strong>التخصص:</strong> ${reviewer.specialty || 'غير محدد'}</p>
                        <p><strong>الكلية:</strong> ${reviewer.college || 'غير محدد'}</p>
                    </div>`;
                
            if (reviewer.comments) {
                html += `
                    <div class="reviewer-comments">
                        <h5>تعليقات المراجع:</h5>
                        <p>${reviewer.comments}</p>
                    </div>`;
            }
            
            html += `</div>`;
        });
        
        html += `</div>`;
    }
    
    // Add files section if available
    if (research.has_files) {
        html += `
            <div class="research-detail">
                <h3>الملفات المرفقة</h3>
                <div class="file-list">
                    <div class="file-item">
                        <div class="file-name">
                            <span class="file-icon">📄</span>
                            ملف البحث
                        </div>
                        <div class="file-actions">
                            <a href="download_research_file.php?id=${research.id}" target="_blank">تحميل</a>
                        </div>
                    </div>
                </div>
            </div>`;
    }
    
    // Add user notes section if available
    if (research.user_notes) {
        html += `
            <div class="notes-section">
                <h4>ملاحظات الباحث:</h4>
                <p>${research.user_notes}</p>
            </div>`;
    }
    
    // Add admin notes section if available
    if (research.admin_notes) {
        html += `
            <div class="admin-notes-section">
                <h4>ملاحظات المدير:</h4>
                <p>${research.admin_notes}</p>
            </div>`;
    }
    
    // Add admin action section for pending researches
    if (research.status === 'Pending 1') {
        html += `
            <div class="admin-action-section">
                <h4>إجراءات المدير:</h4>
                <p>يمكنك الموافقة على هذا البحث وتعيين مراجعين، أو رفضه.</p>
                <div class="admin-buttons">
                    <button class="btn btn-approve" onclick="approveAndAssignReviewers(${research.id})">موافقة وتعيين مراجعين</button>
                    <button class="btn btn-reject" onclick="rejectResearch(${research.id})">رفض</button>
                </div>
            </div>`;
    } else if (research.status === 'Pending 2') {
        html += `
            <div class="admin-action-section">
                <h4>إجراءات المدير:</h4>
                <p>البحث في مرحلة المراجعة النهائية. يمكنك الموافقة النهائية أو الرفض.</p>
                <div class="admin-buttons">
                    <button class="btn btn-approve" onclick="approveResearch(${research.id})">موافقة نهائية</button>
                    <button class="btn btn-reject" onclick="rejectResearch(${research.id})">رفض</button>
                </div>
            </div>`;
    }
    
    // Add status history if available
    if (research.status_history && research.status_history.length > 0) {
        html += `
            <div class="status-history">
                <h4>سجل الحالة:</h4>`;
        
        research.status_history.forEach(history => {
            const historyDate = new Date(history.timestamp).toLocaleString('ar-US');
            html += `
                <div class="history-item">
                    <div class="history-date">${historyDate}</div>
                    <div class="history-action">${history.action}</div>
                    <div class="history-user">بواسطة: ${history.user_name}</div>
                </div>`;
        });
        
        html += `
            </div>`;
    }
    
    modalBody.innerHTML = html;
}

// New function to handle approve and assign reviewers in one workflow
function approveAndAssignReviewers(researchId) {
    const modal = document.getElementById('research-modal');
    const assignModal = document.getElementById('assign-modal');
    const reviewersList = document.getElementById('reviewers-list');
    
    // Show loading indicator
    reviewersList.innerHTML = '<div class="loading">جار تحميل قائمة المراجعين...</div>';
    
    // Set current research ID
    document.getElementById('current-research-id').value = researchId;
    
    // Hide research modal and show assign modal
    modal.style.display = 'none';
    assignModal.style.display = 'block';
    
    // Fetch reviewers list
    fetch('admin_researches_handler.php?action=getReviewers')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayReviewersList(data.reviewers);
            } else {
                reviewersList.innerHTML = 
                    `<div class="error-message">${data.message || 'حدث خطأ أثناء تحميل قائمة المراجعين'}</div>`;
            }
        })
        .catch(error => {
            console.error('Network error:', error);
            reviewersList.innerHTML = 
                '<div class="error-message">حدث خطأ في الاتصال بالخادم</div>';
        });
}

// Display reviewers list
function displayReviewersList(reviewersList) {
    const reviewersListContainer = document.getElementById('reviewers-list');
    
    if (reviewersList.length === 0) {
        reviewersListContainer.innerHTML = '<div class="no-reviewer">لا يوجد مراجعين متاحين</div>';
        return;
    }
    
    let html = '';
    
    reviewersList.forEach(reviewer => {
        const roleText = reviewer.role === 'Staff' ? 'موظف' : 'باحث';
        
        html += `
            <div class="reviewer-item" data-id="${reviewer.user_id}" onclick="selectReviewer(this)">
                <div class="reviewer-icon">👨‍🔬</div>
                <div class="reviewer-info">
                    <div class="reviewer-name">${reviewer.name}</div>
                    <div class="reviewer-role">${roleText}</div>
                    <div class="reviewer-specialty">${reviewer.specialty || 'غير محدد'} - ${reviewer.college || 'غير محدد'}</div>
                </div>
            </div>`;
    });
    
    reviewersListContainer.innerHTML = html;
}

// Select reviewer
function selectReviewer(element) {
    // Toggle selected class on clicked item
    element.classList.toggle('selected');
}

// Assign reviewers to research with improved error handling
function assignReviewersToResearch() {
    const selectedReviewers = document.querySelectorAll('.reviewer-item.selected');
    
    if (selectedReviewers.length === 0) {
        alert('الرجاء اختيار مراجع واحد على الأقل');
        return;
    }
    
    const reviewerIds = Array.from(selectedReviewers).map(item => item.getAttribute('data-id'));
    const researchId = document.getElementById('current-research-id').value;
    
    // Send request to assign reviewers
    fetch('admin_researches_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=assignReviewers&research_id=${researchId}&reviewer_ids=${JSON.stringify(reviewerIds)}`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Close assign modal
            document.getElementById('assign-modal').style.display = 'none';
            
            // Close research modal if open
            document.getElementById('research-modal').style.display = 'none';
            
            // Show success message
            alert('تم تعيين المراجعين بنجاح');
            
            // Reload researches
            loadResearches();
        } else {
            alert(data.message || 'حدث خطأ أثناء تعيين المراجعين');
        }
    })
    .catch(error => {
        console.error('Network error:', error);
        alert('حدث خطأ في الاتصال بالخادم');
    });
}

// Approve research
function approveResearch(researchId) {
    if (confirm('هل أنت متأكد من الموافقة على هذا البحث؟')) {
        updateResearchStatus(researchId, 'Approved');
    }
}

// Reject research
function rejectResearch(researchId) {
    const rejectReason = prompt('يرجى إدخال سبب الرفض:');
    
    if (rejectReason === null) {
        // User canceled
        return;
    }
    
    if (rejectReason.trim() === '') {
        alert('يرجى إدخال سبب الرفض');
        return;
    }
    
    updateResearchStatus(researchId, 'Rejected', rejectReason);
}

// Update research status
function updateResearchStatus(researchId, status, reason = '') {
    fetch('admin_researches_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=updateStatus&research_id=${researchId}&status=${status}&reason=${encodeURIComponent(reason)}`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Close research modal if open
            document.getElementById('research-modal').style.display = 'none';
            
            // Show success message
            alert(data.message || 'تم تحديث حالة البحث بنجاح');
            
            // Reload researches
            loadResearches();
        } else {
            alert(data.message || 'حدث خطأ أثناء تحديث حالة البحث');
        }
    })
    .catch(error => {
        console.error('Network error:', error);
        alert('حدث خطأ في الاتصال بالخادم');
    });
}

// Helper function to get file icon based on file extension
function getFileIcon(filePath) {
    const extension = filePath.split('.').pop().toLowerCase();
    
    switch (extension) {
        case 'pdf':
            return '📑';
        case 'doc':
        case 'docx':
            return '📝';
        case 'xls':
        case 'xlsx':
            return '📊';
        case 'ppt':
        case 'pptx':
            return '📽️';
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
            return '🖼️';
        case 'zip':
        case 'rar':
            return '📦';
      case 'txt':
            return '📄';
        default:
            return '📁';
    }
}

// Notification function - simple version using alert
function showNotification(message, type) {
    if (type === 'error') {
        alert('خطأ: ' + message);
    } else {
        alert(message);
    }
}

// Function to check if a research has downloadable files
function hasDownloadableFiles(research) {
    return research && research.has_files;
}

// Function to create a notification container if it doesn't exist
function createNotificationContainer() {
    if (!document.getElementById('notification-container')) {
        const container = document.createElement('div');
        container.id = 'notification-container';
        document.body.appendChild(container);
        
        // Add styles for the notification container
        container.style.position = 'fixed';
        container.style.top = '20px';
        container.style.left = '50%';
        container.style.transform = 'translateX(-50%)';
        container.style.zIndex = '9999';
        container.style.width = '100%';
        container.style.maxWidth = '500px';
        container.style.display = 'flex';
        container.style.flexDirection = 'column';
        container.style.gap = '10px';
    }
    
    return document.getElementById('notification-container');
}