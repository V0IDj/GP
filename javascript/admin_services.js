// Admin Services JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Get admin info
    fetchAdminInfo();
    
    // Load initial services data
    loadServices();
    
    // Set up filter event listeners
    document.getElementById('status-filter').addEventListener('change', function() {
        loadServices(1); // Reset to page 1 when filter changes
    });
    
    document.getElementById('service-type-filter').addEventListener('change', function() {
        loadServices(1); // Reset to page 1 when filter changes
    });
    
    // Set up search functionality
    document.getElementById('search-btn').addEventListener('click', function() {
        loadServices(1); // Reset to page 1 when searching
    });
    
    document.getElementById('search-input').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            loadServices(1); // Reset to page 1 when searching
        }
    });
    
    // Set up modal event listeners
    const serviceModal = document.getElementById('service-modal');
    const assignModal = document.getElementById('assign-modal');
    
    document.querySelector('.close-modal').addEventListener('click', function() {
        serviceModal.style.display = 'none';
    });
    
    document.getElementById('modal-close-btn').addEventListener('click', function() {
        serviceModal.style.display = 'none';
    });
    
    document.querySelector('.close-assign-modal').addEventListener('click', function() {
        assignModal.style.display = 'none';
    });
    
    document.getElementById('cancel-assign-btn').addEventListener('click', function() {
        assignModal.style.display = 'none';
    });
    
    // Close modals when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === serviceModal) {
            serviceModal.style.display = 'none';
        }
        if (event.target === assignModal) {
            assignModal.style.display = 'none';
        }
    });
    
    // Set up assign button event
    document.getElementById('assign-btn').addEventListener('click', function() {
        assignServiceToStaff();
    });
});

// Fetch admin information
function fetchAdminInfo() {
    fetch('admin_services_handler.php?action=getAdminInfo')
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

// Load services with pagination and filters
function loadServices(page = 1) {
    const servicesContainer = document.getElementById('services-container');
    servicesContainer.innerHTML = '<div class="loading">جار تحميل طلبات الخدمات...</div>';
    
    // Get filter values
    const statusFilter = document.getElementById('status-filter').value;
    const serviceTypeFilter = document.getElementById('service-type-filter').value;
    const searchTerm = document.getElementById('search-input').value;
    
    // Build API request
    let apiUrl = `admin_services_handler.php?action=getServices&page=${page}`;
    
    if (statusFilter !== 'all') {
        apiUrl += `&status=${encodeURIComponent(statusFilter)}`;
    }
    
    if (serviceTypeFilter !== 'all') {
        apiUrl += `&service_type=${encodeURIComponent(serviceTypeFilter)}`;
    }
    
    if (searchTerm) {
        apiUrl += `&search=${encodeURIComponent(searchTerm)}`;
    }
    
    fetch(apiUrl)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayServices(data.services);
                displayPagination(data.pagination);
            } else {
                servicesContainer.innerHTML = 
                    `<div class="no-services">${data.message || 'حدث خطأ أثناء تحميل البيانات'}</div>`;
            }
        })
        .catch(error => {
            console.error('Network error:', error);
            servicesContainer.innerHTML = 
                '<div class="no-services">حدث خطأ في الاتصال بالخادم</div>';
        });
}

// Display services
function displayServices(services) {
    const servicesContainer = document.getElementById('services-container');
    
    if (services.length === 0) {
        servicesContainer.innerHTML = '<div class="no-services">لا توجد طلبات خدمات مطابقة للفلتر</div>';
        return;
    }
    
    let html = '';
    services.forEach(function(service) {
        // Define status classes and text
        let statusClass = '';
        let statusText = '';
        
        switch(service.status) {
            case 'wait 1':
                statusClass = 'status-wait1';
                statusText = 'بانتظار موافقة المدير';
                break;
            case 'wait 2':
                statusClass = 'status-wait2';
                statusText = 'بانتظار موافقة مقدم الخدمة';
                break;
            case 'approved':
                statusClass = 'status-approved';
                statusText = 'تمت الموافقة';
                break;
            case 'rejected':
                statusClass = 'status-rejected';
                statusText = 'مرفوض';
                break;
        }
        
        const createdDate = new Date(service.created_at).toLocaleDateString('ar-SA');
        
        html += `
            <div class="service-card" data-id="${service.request_id}">
                <div class="service-header">
                    <div class="service-title">${service.service_type}</div>
                    <div class="service-status ${statusClass}">${statusText}</div>
                </div>
                <div class="service-info">
                    <p><strong>المستخدم:</strong> ${service.user_name}</p>
                    <p><strong>تاريخ الطلب:</strong> ${createdDate}</p>
                    <p><strong>الملفات:</strong> ${service.has_files ? 'متوفرة' : 'غير متوفرة'}</p>
                </div>
                <div class="service-actions">
                    <button class="btn btn-view" onclick="viewServiceDetails(${service.request_id})">عرض التفاصيل</button>`;
                    
        // Show appropriate buttons based on status
        if (service.status === 'wait 1') {
            html += `
                <button class="btn btn-approve" onclick="approveService(${service.request_id})">موافقة</button>
                <button class="btn btn-reject" onclick="rejectService(${service.request_id})">رفض</button>
                <button class="btn btn-assign" onclick="openAssignStaffModal(${service.request_id})">تعيين موظف</button>`;
        }
        
        html += `
                </div>
            </div>`;
    });
    
    servicesContainer.innerHTML = html;
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
        html += `<button class="page-btn" onclick="loadServices(${pagination.current_page - 1})">السابق</button>`;
    }
    
    // Page buttons
    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i === pagination.current_page) {
            html += `<button class="page-btn active">${i}</button>`;
        } else {
            html += `<button class="page-btn" onclick="loadServices(${i})">${i}</button>`;
        }
    }
    
    // Next button
    if (pagination.current_page < pagination.total_pages) {
        html += `<button class="page-btn" onclick="loadServices(${pagination.current_page + 1})">التالي</button>`;
    }
    
    paginationContainer.innerHTML = html;
}

// View service details
function viewServiceDetails(serviceId) {
    const modal = document.getElementById('service-modal');
    const modalBody = document.getElementById('modal-body');
    
    // Show loading indicator
    modalBody.innerHTML = '<div class="loading">جار تحميل التفاصيل...</div>';
    modal.style.display = 'block';
    
    fetch(`admin_services_handler.php?action=getServiceDetails&id=${serviceId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayServiceDetails(data.service);
            } else {
                modalBody.innerHTML = 
                    `<div class="error-message">${data.message || 'حدث خطأ أثناء تحميل البيانات'}</div>`;
            }
        })
        .catch(error => {
            console.error('Network error:', error);
            modalBody.innerHTML = 
                '<div class="error-message">حدث خطأ في الاتصال بالخادم</div>';
        });
}

// Display service details in modal
function displayServiceDetails(service) {
    const modalBody = document.getElementById('modal-body');
    const modalTitle = document.getElementById('modal-title');
    
    // Update modal title
    modalTitle.textContent = `تفاصيل الخدمة: ${service.service_type}`;
    
    // Define status text
    let statusText = '';
    switch(service.status) {
        case 'wait 1': statusText = 'بانتظار موافقة المدير'; break;
        case 'wait 2': statusText = 'بانتظار موافقة مقدم الخدمة'; break;
        case 'approved': statusText = 'تمت الموافقة'; break;
        case 'rejected': statusText = 'مرفوض'; break;
    }
    
    // Format date
    const createdDate = new Date(service.created_at).toLocaleDateString('ar-SA');
    
    // Start building HTML for service details
    let html = `
        <div class="service-detail">
            <h3>معلومات الطلب</h3>
            <div class="detail-row">
                <div class="detail-label">رقم الطلب:</div>
                <div class="detail-value">${service.request_id}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">نوع الخدمة:</div>
                <div class="detail-value">${service.service_type}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">اسم المستخدم:</div>
                <div class="detail-value">${service.user_name}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">معرّف المستخدم:</div>
                <div class="detail-value">${service.user_id}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">تاريخ الطلب:</div>
                <div class="detail-value">${createdDate}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">الحالة:</div>
                <div class="detail-value">${statusText}</div>
            </div>
        </div>`;
    
    // Add files section if available
    if (service.has_files) {
        html += `
            <div class="service-detail">
                <h3>الملفات المرفقة</h3>
                <div class="file-list">`;
        
        try {
            // Parse files from JSON if it's a string
            const files = typeof service.files === 'string' ? JSON.parse(service.files) : [];
            
            if (Array.isArray(files) && files.length > 0) {
                files.forEach((file, index) => {
                    const fileName = file.split('/').pop();
                    const fileIcon = getFileIcon(file);
                    
                    html += `
                        <div class="file-item">
                            <div class="file-name">
                                <span class="file-icon">${fileIcon}</span>
                                ${fileName}
                            </div>
                            <div class="file-actions">
                                <a href="download_service_file.php?id=${service.request_id}&file_index=${index}" target="_blank">تحميل</a>
                            </div>
                        </div>`;
                });
            } else if (service.files) {
                // Handle case where files is a blob instead of a JSON array
                html += `
                    <div class="file-item">
                        <div class="file-name">
                            <span class="file-icon">📄</span>
                            ملف مرفق
                        </div>
                        <div class="file-actions">
                            <a href="download_service_file.php?id=${service.request_id}&file_index=0" target="_blank">تحميل</a>
                        </div>
                    </div>`;
            }
        } catch (e) {
            // In case of JSON parsing error, show a simple link
            html += `
                <div class="file-item">
                    <div class="file-name">
                        <span class="file-icon">📄</span>
                        ملف مرفق
                    </div>
                    <div class="file-actions">
                        <a href="download_service_file.php?id=${service.request_id}&file_index=0" target="_blank">تحميل</a>
                    </div>
                </div>`;
        }
        
        html += `
                </div>
            </div>`;
    }
    
    // Add notes section if available
    if (service.notes) {
        html += `
            <div class="notes-section">
                <h4>ملاحظات المستخدم:</h4>
                <p>${service.notes}</p>
            </div>`;
    }
    
    // Add admin action section for pending services
    if (service.status === 'wait 1') {
        html += `
            <div class="admin-action-section">
                <h4>إجراءات المدير:</h4>
                <p>يمكنك الموافقة على هذا الطلب وتعيين موظف لتنفيذه، أو رفضه.</p>
                <div class="admin-buttons">
                    <button class="btn btn-approve" onclick="approveService(${service.request_id})">موافقة</button>
                    <button class="btn btn-reject" onclick="rejectService(${service.request_id})">رفض</button>
                    <button class="btn btn-assign" onclick="openAssignStaffModal(${service.request_id})">تعيين موظف</button>
                </div>
            </div>`;
    }
    
    // Add assigned staff info if applicable
    if (service.assigned_staff_id) {
        html += `
            <div class="service-detail">
                <h3>معلومات الموظف المعين</h3>
                <div class="detail-row">
                    <div class="detail-label">اسم الموظف:</div>
                    <div class="detail-value">${service.assigned_staff_name || 'غير متوفر'}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">معرّف الموظف:</div>
                    <div class="detail-value">${service.assigned_staff_id}</div>
                </div>
            </div>`;
    }
    
    // Add admin notes section if available
    if (service.admin_notes) {
        html += `
            <div class="notes-section">
                <h4>ملاحظات المدير:</h4>
                <p>${service.admin_notes}</p>
            </div>`;
    }
    
    // Add staff notes section if available
    if (service.staff_notes) {
        html += `
            <div class="notes-section">
                <h4>ملاحظات الموظف:</h4>
                <p>${service.staff_notes}</p>
            </div>`;
    }
    
    // Add completed files section if available
    if (service.completed_files) {
        html += `
            <div class="service-detail">
                <h3>الملفات المكتملة</h3>
                <div class="file-list">`;
        
        try {
            // Parse completed_files from JSON if it's a string
            const completedFiles = typeof service.completed_files === 'string' ? 
                JSON.parse(service.completed_files) : [];
            
            if (Array.isArray(completedFiles) && completedFiles.length > 0) {
                completedFiles.forEach((file, index) => {
                    const fileName = file.split('/').pop();
                    const fileIcon = getFileIcon(file);
                    
                    html += `
                        <div class="file-item">
                            <div class="file-name">
                                <span class="file-icon">${fileIcon}</span>
                                ${fileName}
                            </div>
                            <div class="file-actions">
                                <a href="download_completed_file.php?id=${service.request_id}&file_index=${index}" target="_blank">تحميل</a>
                            </div>
                        </div>`;
                });
            } else if (service.completed_files) {
                // Handle case where completed_files is a blob
                html += `
                    <div class="file-item">
                        <div class="file-name">
                            <span class="file-icon">📄</span>
                            ملف مكتمل
                        </div>
                        <div class="file-actions">
                            <a href="download_completed_file.php?id=${service.request_id}&file_index=0" target="_blank">تحميل</a>
                        </div>
                    </div>`;
            }
        } catch (e) {
            // In case of JSON parsing error, show a simple link
            html += `
                <div class="file-item">
                    <div class="file-name">
                        <span class="file-icon">📄</span>
                        ملف مكتمل
                    </div>
                    <div class="file-actions">
                        <a href="download_completed_file.php?id=${service.request_id}&file_index=0" target="_blank">تحميل</a>
                    </div>
                </div>`;
        }
        
        html += `
                </div>
            </div>`;
    }
    
    // Add status history if available
    if (service.status_history && service.status_history.length > 0) {
        html += `
            <div class="status-history">
                <h4>سجل الحالة:</h4>`;
        
        service.status_history.forEach(history => {
            const historyDate = new Date(history.timestamp).toLocaleString('ar-SA');
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

// Open assign staff modal
function openAssignStaffModal(serviceId) {
    const modal = document.getElementById('assign-modal');
    const staffList = document.getElementById('staff-list');
    
    // Show loading indicator
    staffList.innerHTML = '<div class="loading">جار تحميل قائمة الموظفين...</div>';
    
    // Set current service ID
    document.getElementById('current-service-id').value = serviceId;
    
    // Show the modal
    modal.style.display = 'block';
    
    // Fetch staff list
    fetch('admin_services_handler.php?action=getStaffList')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayStaffList(data.staff);
            } else {
                staffList.innerHTML = 
                    `<div class="error-message">${data.message || 'حدث خطأ أثناء تحميل قائمة الموظفين'}</div>`;
            }
        })
        .catch(error => {
            console.error('Network error:', error);
            staffList.innerHTML = 
                '<div class="error-message">حدث خطأ في الاتصال بالخادم</div>';
        });
}

// Display staff list
function displayStaffList(staffList) {
    const staffListContainer = document.getElementById('staff-list');
    
    if (staffList.length === 0) {
        staffListContainer.innerHTML = '<div class="no-staff">لا يوجد موظفين متاحين</div>';
        return;
    }
    
    let html = '';
    
    staffList.forEach(staff => {
        html += `
            <div class="staff-item" data-id="${staff.user_id}" onclick="selectStaff(this)">
                <div class="staff-icon">👨‍💼</div>
                <div class="staff-info">
                    <div class="staff-name">${staff.name}</div>
                    <div class="staff-role">${staff.role}</div>
                    <div class="staff-specialty">${staff.specialty || 'غير محدد'}</div>
                </div>
            </div>`;
    });
    
    staffListContainer.innerHTML = html;
}

// Select staff
function selectStaff(element) {
    // Remove selected class from all staff items
    const staffItems = document.querySelectorAll('.staff-item');
    staffItems.forEach(item => {
        item.classList.remove('selected');
    });
    
    // Add selected class to clicked item
    element.classList.add('selected');
}

// Assign service to staff
function assignServiceToStaff() {
    const selectedStaff = document.querySelector('.staff-item.selected');
    
    if (!selectedStaff) {
        alert('الرجاء اختيار موظف');
        return;
    }
    
    const staffId = selectedStaff.getAttribute('data-id');
    const serviceId = document.getElementById('current-service-id').value;
    
    // Send request to assign staff
    fetch('admin_services_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=assignStaff&service_id=${serviceId}&staff_id=${staffId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close assign modal
            document.getElementById('assign-modal').style.display = 'none';
            
            // Close service modal if open
            document.getElementById('service-modal').style.display = 'none';
            
            // Show success message
            alert('تم تعيين الموظف بنجاح');
            
            // Reload services
            loadServices();
        } else {
            alert(data.message || 'حدث خطأ أثناء تعيين الموظف');
        }
    })
    .catch(error => {
        console.error('Network error:', error);
        alert('حدث خطأ في الاتصال بالخادم');
    });
}

// Approve service
function approveService(serviceId) {
    if (confirm('هل أنت متأكد من الموافقة على هذا الطلب؟')) {
        updateServiceStatus(serviceId, 'approve');
    }
}

// Reject service
function rejectService(serviceId) {
    const rejectReason = prompt('يرجى إدخال سبب الرفض:');
    
    if (rejectReason === null) {
        // User canceled
        return;
    }
    
    if (rejectReason.trim() === '') {
        alert('يرجى إدخال سبب الرفض');
        return;
    }
    
    updateServiceStatus(serviceId, 'reject', rejectReason);
}

// Update service status
function updateServiceStatus(serviceId, action, reason = '') {
    fetch('admin_services_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=updateStatus&service_id=${serviceId}&status_action=${action}&reason=${encodeURIComponent(reason)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close service modal if open
            document.getElementById('service-modal').style.display = 'none';
            
            // Show success message
            alert(data.message || 'تم تحديث حالة الطلب بنجاح');
            
            // Reload services
            loadServices();
        } else {
            alert(data.message || 'حدث خطأ أثناء تحديث حالة الطلب');
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