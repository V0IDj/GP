// Staff Services JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Get staff info
    fetchStaffInfo();
    
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
    const completeModal = document.getElementById('complete-modal');
    
    document.querySelector('.close-modal').addEventListener('click', function() {
        serviceModal.style.display = 'none';
    });
    
    document.getElementById('modal-close-btn').addEventListener('click', function() {
        serviceModal.style.display = 'none';
    });
    
    document.querySelector('.close-complete-modal').addEventListener('click', function() {
        completeModal.style.display = 'none';
    });
    
    document.getElementById('cancel-complete-btn').addEventListener('click', function() {
        completeModal.style.display = 'none';
    });
    
    // Close modals when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === serviceModal) {
            serviceModal.style.display = 'none';
        }
        if (event.target === completeModal) {
            completeModal.style.display = 'none';
        }
    });
    
    // Set up complete button event
    document.getElementById('complete-btn').addEventListener('click', function() {
        completeService();
    });
    
    // Set up file upload functionality
    setupFileUpload();
});

// Fetch staff information
function fetchStaffInfo() {
    fetch('staff_services_handler.php?action=getStaffInfo')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('staff-name').textContent = data.staffName;
            } else {
                console.error('Error fetching staff info:', data.message);
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
    let apiUrl = `staff_services_handler.php?action=getServices&page=${page}`;
    
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
        servicesContainer.innerHTML = '<div class="no-services">لا توجد طلبات خدمات معينة لك</div>';
        return;
    }
    
    let html = '';
    services.forEach(function(service) {
        // Define status classes and text
        let statusClass = '';
        let statusText = '';
        
        switch(service.status) {
            case 'wait 2':
                statusClass = 'status-wait2';
                statusText = 'بانتظار موافقتك';
                break;
            case 'approved':
                // Check if service has been completed (has completed_files)
                if (service.has_completed_files) {
                    statusClass = 'status-approved';
                    statusText = 'تمت إكمال الخدمة';
                } else {
                    statusClass = 'status-approved';
                    statusText = 'تمت الموافقة - بانتظار الإكمال';
                }
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
        if (service.status === 'wait 2') {
            html += `
                <button class="btn btn-approve" onclick="approveService(${service.request_id})">قبول</button>
                <button class="btn btn-reject" onclick="rejectService(${service.request_id})">رفض</button>`;
        } else if (service.status === 'approved' && !service.has_completed_files) {
            // Only show complete button for approved services that haven't been completed yet
            html += `
                <button class="btn btn-complete" onclick="openCompleteModal(${service.request_id})">إكمال الخدمة</button>`;
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
    
    fetch(`staff_services_handler.php?action=getServiceDetails&id=${serviceId}`)
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
        case 'wait 2': statusText = 'بانتظار موافقتك'; break;
        case 'approved': 
            statusText = service.completed_files ? 'تمت إكمال الخدمة' : 'تمت الموافقة - بانتظار الإكمال'; 
            break;
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
        
        // Parse files from JSON
        const files = JSON.parse(service.files);
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
                        <a href="download_service_file.php?id=${service.request_id}&file_index=${index}&role=staff" target="_blank">تحميل</a>
                    </div>
                </div>`;
        });
        
        html += `
                </div>
            </div>`;
    }
    
    // Add user notes section if available
    if (service.notes) {
        html += `
            <div class="notes-section">
                <h4>ملاحظات المستخدم:</h4>
                <p>${service.notes}</p>
            </div>`;
    }
    
    // Add admin notes section if available
    if (service.admin_notes) {
        html += `
            <div class="admin-notes-section">
                <h4>ملاحظات المدير:</h4>
                <p>${service.admin_notes}</p>
            </div>`;
    }
    
    // Add appropriate staff action section based on status
    if (service.status === 'wait 2') {
        // Waiting for staff approval
        html += `
            <div class="staff-action-section">
                <h4>إجراءات الموظف:</h4>
                <p>يمكنك قبول هذا الطلب وإكمال الخدمة المطلوبة، أو رفضه.</p>
                <div class="staff-buttons">
                    <button class="btn btn-approve" onclick="approveService(${service.request_id})">قبول</button>
                    <button class="btn btn-reject" onclick="rejectService(${service.request_id})">رفض</button>
                </div>
            </div>`;
    } else if (service.status === 'approved' && !service.completed_files) {
        // Approved but not completed yet
        html += `
            <div class="staff-action-section">
                <h4>إجراءات الموظف:</h4>
                <p>تم قبول الطلب. يرجى إكمال الخدمة المطلوبة برفع الملفات المطلوبة.</p>
                <div class="staff-buttons">
                    <button class="btn btn-complete" onclick="openCompleteModal(${service.request_id})">إكمال الخدمة</button>
                    <button class="btn btn-reject" onclick="revertApproval(${service.request_id})">التراجع عن القبول</button>
                </div>
            </div>`;
    } else if (service.status === 'approved' && service.completed_files) {
        // Completed service
        html += `
            <div class="staff-action-section success-section">
                <h4>حالة الإكمال:</h4>
                <p class="success-message">تم إكمال الخدمة بنجاح!</p>
            </div>`;
    }
    
    // Add completed files section if available
    if (service.completed_files) {
        html += `
            <div class="service-detail">
                <h3>الملفات المكتملة</h3>
                <div class="file-list">`;
        
        // Parse completed files from JSON
        const completedFiles = JSON.parse(service.completed_files);
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
        
        html += `
                </div>
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

// Open the complete service modal
function openCompleteModal(serviceId) {
    const modal = document.getElementById('complete-modal');
    
    // Reset form
    document.getElementById('completion-notes').value = '';
    document.getElementById('fileList').innerHTML = '';
    document.getElementById('current-service-id').value = serviceId;
    
    // Show modal
    modal.style.display = 'block';
}

// Revert approval of service - change back to "wait 2" status
function revertApproval(serviceId) {
    if (confirm('هل أنت متأكد من التراجع عن قبول هذا الطلب؟')) {
        fetch('staff_services_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=revertApproval&service_id=${serviceId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close service modal if open
                document.getElementById('service-modal').style.display = 'none';
                
                // Show success message
                alert(data.message || 'تم التراجع عن قبول الطلب بنجاح');
                
                // Reload services
                loadServices();
            } else {
                alert(data.message || 'حدث خطأ أثناء التراجع عن قبول الطلب');
            }
        })
        .catch(error => {
            console.error('Network error:', error);
            alert('حدث خطأ في الاتصال بالخادم');
        });
    }
}

// Set up file upload functionality
function setupFileUpload() {
    const fileInput = document.getElementById('fileInput');
    const fileUploadBtn = document.getElementById('fileUploadBtn');
    const dropZone = document.getElementById('dropZone');
    const fileList = document.getElementById('fileList');
    
    // Selected files array
    window.selectedFiles = [];
    
    // File upload button click
    fileUploadBtn.addEventListener('click', function() {
        fileInput.click();
    });
    
    // File selection
    fileInput.addEventListener('change', function() {
        handleFiles(this.files);
    });
    
    // Drag and Drop functionality
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, highlight, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, unhighlight, false);
    });
    
    function highlight() {
        dropZone.style.borderColor = '#3498db';
        dropZone.style.backgroundColor = '#e1f0fa';
    }
    
    function unhighlight() {
        dropZone.style.borderColor = '#ddd';
        dropZone.style.backgroundColor = '#f5f5f5';
    }
    
    dropZone.addEventListener('drop', function(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        handleFiles(files);
    }, false);
}

// Handle files function
function handleFiles(files) {
    if (files.length > 0) {
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            
            // Check if file is already in the list
            if (!isFileAlreadyAdded(file)) {
                window.selectedFiles.push(file);
                addFileToList(file);
            }
        }
    }
}

// Check if file is already added
function isFileAlreadyAdded(file) {
    return window.selectedFiles.some(existingFile => 
        existingFile.name === file.name && 
        existingFile.size === file.size && 
        existingFile.type === file.type
    );
}

// Add file to list
function addFileToList(file) {
    const fileList = document.getElementById('fileList');
    
    const fileItem = document.createElement('div');
    fileItem.className = 'file-item';
    
    const fileName = document.createElement('div');
    fileName.className = 'file-name';
    
    // File icon based on type
    const fileIcon = getFileIconFromType(file.type);
    
    fileName.innerHTML = `<span class="file-icon">${fileIcon}</span> ${file.name} <span class="file-type">(${formatFileSize(file.size)})</span>`;
    
    const removeBtn = document.createElement('span');
    removeBtn.className = 'remove-file';
    removeBtn.textContent = '✖';
    removeBtn.style.cursor = 'pointer';
    removeBtn.style.color = '#e74c3c';
    removeBtn.style.fontWeight = 'bold';
    
    removeBtn.addEventListener('click', function() {
        // Remove file from array
        const fileIndex = window.selectedFiles.indexOf(file);
        if (fileIndex > -1) {
            window.selectedFiles.splice(fileIndex, 1);
        }
        
        // Remove file item from list
        fileList.removeChild(fileItem);
    });
    
    fileItem.appendChild(fileName);
    fileItem.appendChild(removeBtn);
    fileList.appendChild(fileItem);
}

// Format file size
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Complete service
function completeService() {
    const serviceId = document.getElementById('current-service-id').value;
    const notes = document.getElementById('completion-notes').value;
    
    if (window.selectedFiles.length === 0) {
        alert('يرجى تحميل ملف واحد على الأقل');
        return;
    }
    
    // Create FormData object
    const formData = new FormData();
    formData.append('action', 'completeService');
    formData.append('service_id', serviceId);
    formData.append('notes', notes);
    
    // Append selected files
    window.selectedFiles.forEach(function(file, index) {
        formData.append('files[]', file);
    });
    
    // Show loading message
    const completeBtn = document.getElementById('complete-btn');
    const originalText = completeBtn.textContent;
    completeBtn.textContent = 'جار المعالجة...';
    completeBtn.disabled = true;
    
    // Submit form using AJAX
    fetch('staff_services_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Reset button
        completeBtn.textContent = originalText;
        completeBtn.disabled = false;
        
        if (data.success) {
            // Close modals
            document.getElementById('complete-modal').style.display = 'none';
            document.getElementById('service-modal').style.display = 'none';
            
            // Show success message
            alert(data.message || 'تم إكمال الخدمة بنجاح');
            
            // Reload services
            loadServices();
        } else {
            alert(data.message || 'حدث خطأ أثناء إكمال الخدمة');
        }
    })
    .catch(error => {
        // Reset button
        completeBtn.textContent = originalText;
        completeBtn.disabled = false;
        
        console.error('Network error:', error);
        alert('حدث خطأ في الاتصال بالخادم');
    });
}

// Approve service
function approveService(serviceId) {
    if (confirm('هل أنت متأكد من قبول هذا الطلب؟ سيتطلب منك إكمال الخدمة بعد القبول.')) {
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
    fetch('staff_services_handler.php', {
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

// Helper function to get file icon based on MIME type
function getFileIconFromType(mimeType) {
    if (mimeType.includes('pdf')) {
        return '📑';
    } else if (mimeType.includes('word') || mimeType.includes('document')) {
        return '📝';
    } else if (mimeType.includes('excel') || mimeType.includes('spreadsheet')) {
        return '📊';
    } else if (mimeType.includes('powerpoint') || mimeType.includes('presentation')) {
        return '📽️';
    } else if (mimeType.includes('image')) {
        return '🖼️';
    } else if (mimeType.includes('zip') || mimeType.includes('compressed')) {
        return '📦';
    } else if (mimeType.includes('text')) {
        return '📄';
    } else {
        return '📁';
    }
}