document.addEventListener('DOMContentLoaded', function() {
    // Load user profile data
    fetchProfileData();
    
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
            
            // Populate edit form with current values
            if (section === 'about') {
                document.getElementById('edit-bio').value = document.getElementById('user-bio').textContent;
                document.getElementById('edit-college').value = document.getElementById('user-college').textContent;
                document.getElementById('edit-major').value = document.getElementById('user-major').textContent;
            } else if (section === 'contact') {
                document.getElementById('edit-email').value = document.getElementById('user-email').textContent;
                document.getElementById('edit-phone').value = document.getElementById('user-phone').textContent;
            }
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
        const formData = {
            bio: document.getElementById('edit-bio').value,
            college: document.getElementById('edit-college').value,
            major: document.getElementById('edit-major').value
        };
        
        // Send data to server
        updateUserInfo('about', formData);
    });
    
    const contactForm = document.getElementById('contact-form');
    contactForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = {
            email: document.getElementById('edit-email').value,
            phone: document.getElementById('edit-phone').value
        };
        
        // Send data to server
        updateUserInfo('contact', formData);
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
        
        const formData = {
            currentPassword: currentPassword,
            newPassword: newPassword
        };
        
        // Send data to server
        updatePassword(formData);
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
                uploadProfileImage(file);
            };
            
            reader.readAsDataURL(file);
        }
    });
});

// Fetch user profile data from server
function fetchProfileData() {
    fetch('profile_data.php')
    .then(response => {
        if (!response.ok) {
            throw new Error('حدث خطأ في جلب بيانات الملف الشخصي');
        }
        return response.json();
    })
    .then(data => {
        // Update UI with user data
        document.getElementById('user-name').textContent = data.name;
        document.getElementById('user-title').textContent = data.college + ' | ' + data.major;
        document.getElementById('user-bio').textContent = data.bio;
        document.getElementById('user-college').textContent = data.college;
        document.getElementById('user-major').textContent = data.major;
        document.getElementById('user-email').textContent = data.email;
        document.getElementById('user-phone').textContent = data.PhoneNumber;
        
        // Display profile image if exists
        if (data.profile_image) {
            const profileImage = document.getElementById('profile-image');
            profileImage.src = 'data:image/jpeg;base64,' + data.profile_image;
            profileImage.style.display = 'block';
            document.getElementById('profile-image-placeholder').style.display = 'none';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showStatusMessage(error.message, 'error');
    });
}

// Update user information
function updateUserInfo(section, formData) {
    // Create form data object to send to server
    const data = new FormData();
    data.append('section', section);
    
    // Add all form fields to form data
    for (const key in formData) {
        data.append(key, formData[key]);
    }
    
    // Send data to server
    fetch('update_profile.php', {
        method: 'POST',
        body: data
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('حدث خطأ في تحديث البيانات');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Update UI
            if (section === 'about') {
                document.getElementById('user-bio').textContent = formData.bio;
                document.getElementById('user-college').textContent = formData.college;
                document.getElementById('user-major').textContent = formData.major;
                document.getElementById('user-title').textContent = formData.college + ' | ' + formData.major;
            } else if (section === 'contact') {
                document.getElementById('user-email').textContent = formData.email;
                document.getElementById('user-phone').textContent = formData.phone;
            }
            
            // Hide edit form, show content
            const sectionElem = document.getElementById(section);
            sectionElem.querySelector('.section-edit').style.display = 'none';
            sectionElem.querySelector('.section-content').style.display = 'block';
            
            showStatusMessage('تم تحديث البيانات بنجاح', 'success');
        } else {
            showStatusMessage(data.message || 'حدث خطأ في تحديث البيانات', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showStatusMessage(error.message, 'error');
    });
}

// Update password
function updatePassword(formData) {
    const data = new FormData();
    data.append('action', 'update_password');
    data.append('currentPassword', formData.currentPassword);
    data.append('newPassword', formData.newPassword);
    
    fetch('update_profile.php', {
        method: 'POST',
        body: data
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('حدث خطأ في تحديث كلمة المرور');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Clear form
            document.getElementById('password-form').reset();
            
            showStatusMessage('تم تحديث كلمة المرور بنجاح', 'success');
        } else {
            showStatusMessage(data.message || 'حدث خطأ في تحديث كلمة المرور', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showStatusMessage(error.message, 'error');
    });
}

// Upload profile image
function uploadProfileImage(file) {
    const data = new FormData();
    data.append('action', 'update_image');
    data.append('profile_image', file);
    
    fetch('update_profile.php', {
        method: 'POST',
        body: data
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('حدث خطأ في تحديث الصورة الشخصية');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showStatusMessage('تم تحديث الصورة الشخصية بنجاح', 'success');
        } else {
            showStatusMessage(data.message || 'حدث خطأ في تحديث الصورة الشخصية', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showStatusMessage(error.message, 'error');
    });
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