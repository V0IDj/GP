// Check if the current user is VP Academic or President
const isVPAcademic = currentUser.username === 'Fayez_99';
const isPresident = currentUser.username === 'imad_99';
const isRegularAdmin = currentUser.role === 'Admin' && !isVPAcademic && !isPresident;

// Current reward being viewed
let currentReward = null;

// DOM Elements
const rewardsContainer = document.getElementById('rewardsContainer');
const rewardDetails = document.getElementById('rewardDetails');
const detailTitle = document.getElementById('detailTitle');
const detailRewardId = document.getElementById('detailRewardId');
const detailResearcherName = document.getElementById('detailResearcherName');
const detailResearchTitle = document.getElementById('detailResearchTitle');
const detailCost = document.getElementById('detailCost');
const detailCreatedAt = document.getElementById('detailCreatedAt');
const detailStatus = document.getElementById('detailStatus');
const detailUserComments = document.getElementById('detailUserComments');
const filesList = document.getElementById('filesList');
const commentsList = document.getElementById('commentsList');
const approveBtn = document.getElementById('approveBtn');
const rejectBtn = document.getElementById('rejectBtn');
const commentBtn = document.getElementById('commentBtn');
const backBtn = document.getElementById('backBtn');
const statusFilter = document.getElementById('statusFilter');
const loader = document.getElementById('loader');

// Approval process elements
const stepUser = document.getElementById('stepUser');
const stepAdmin = document.getElementById('stepAdmin');
const stepVP = document.getElementById('stepVP');
const stepPresident = document.getElementById('stepPresident');
const stepFinance = document.getElementById('stepFinance');
const progressFill = document.getElementById('progressFill');

// Modal elements
const commentModal = document.getElementById('commentModal');
const closeCommentModal = document.getElementById('closeCommentModal');
const cancelComment = document.getElementById('cancelComment');
const commentForm = document.getElementById('commentForm');
const commentText = document.getElementById('commentText');

const rejectModal = document.getElementById('rejectModal');
const closeRejectModal = document.getElementById('closeRejectModal');
const cancelReject = document.getElementById('cancelReject');
const rejectForm = document.getElementById('rejectForm');
const rejectReason = document.getElementById('rejectReason');

const approveModal = document.getElementById('approveModal');
const closeApproveModal = document.getElementById('closeApproveModal');
const cancelApprove = document.getElementById('cancelApprove');
const approveForm = document.getElementById('approveForm');
const approveComments = document.getElementById('approveComments');

// New: Return and Edit modals
const returnModal = document.getElementById('returnModal');
const closeReturnModal = document.getElementById('closeReturnModal');
const cancelReturn = document.getElementById('cancelReturn');
const returnForm = document.getElementById('returnForm');
const returnReason = document.getElementById('returnReason');
const returnTarget = document.getElementById('returnTarget');

// Event listeners for tabs
document.querySelectorAll('.tab-btn').forEach(button => {
    button.addEventListener('click', () => {
        const tab = button.getAttribute('data-tab');

        // Remove active class from all tabs and contents
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });

        // Add active class to clicked tab and its content
        button.classList.add('active');
        document.getElementById(tab).classList.add('active');
    });
});

// Event listeners for modals
closeCommentModal.addEventListener('click', () => {
    commentModal.style.display = 'none';
});
cancelComment.addEventListener('click', () => {
    commentModal.style.display = 'none';
});

closeRejectModal.addEventListener('click', () => {
    rejectModal.style.display = 'none';
});
cancelReject.addEventListener('click', () => {
    rejectModal.style.display = 'none';
});

closeApproveModal.addEventListener('click', () => {
    approveModal.style.display = 'none';
});
cancelApprove.addEventListener('click', () => {
    approveModal.style.display = 'none';
});

// New: Event listeners for return modal
closeReturnModal.addEventListener('click', () => {
    returnModal.style.display = 'none';
});
cancelReturn.addEventListener('click', () => {
    returnModal.style.display = 'none';
});

// Close modals when clicking outside
window.addEventListener('click', (event) => {
    if (event.target === commentModal) {
        commentModal.style.display = 'none';
    }
    if (event.target === rejectModal) {
        rejectModal.style.display = 'none';
    }
    if (event.target === approveModal) {
        approveModal.style.display = 'none';
    }
    if (event.target === returnModal) {
        returnModal.style.display = 'none';
    }
});

// Event listeners for action buttons
commentBtn.addEventListener('click', () => {
    commentModal.style.display = 'block';
});

rejectBtn.addEventListener('click', () => {
    rejectModal.style.display = 'block';
});

approveBtn.addEventListener('click', () => {
    approveModal.style.display = 'block';
});

backBtn.addEventListener('click', () => {
    rewardDetails.style.display = 'none';
    rewardsContainer.style.display = 'grid';
});

// Form submissions
commentForm.addEventListener('submit', (e) => {
    e.preventDefault();
    if (currentReward) {
        addComment(currentReward.reward_id, commentText.value);
        commentModal.style.display = 'none';
        commentText.value = '';
    }
});

rejectForm.addEventListener('submit', (e) => {
    e.preventDefault();
    if (currentReward) {
        rejectReward(currentReward.reward_id, rejectReason.value);
        rejectModal.style.display = 'none';
        rejectReason.value = '';
    }
});

approveForm.addEventListener('submit', (e) => {
    e.preventDefault();
    if (currentReward) {
        approveReward(currentReward.reward_id, approveComments.value);
        approveModal.style.display = 'none';
        approveComments.value = '';
    }
});

// New: Return form submission
returnForm.addEventListener('submit', (e) => {
    e.preventDefault();
    if (currentReward) {
        const target = returnTarget.value;
        returnReward(currentReward.reward_id, returnReason.value, target);
        returnModal.style.display = 'none';
        returnReason.value = '';
    }
});

// Filter change event
statusFilter.addEventListener('change', () => {
    fetchRewards();
});

// Function to fetch rewards from server
function fetchRewards() {
    const status = statusFilter.value;
    rewardsContainer.innerHTML = '<div class="loader"></div>';

    // Send AJAX request to fetch rewards
    fetch(`get_rewards.php?status=${status}`)
        .then(response => response.json())
        .then(data => {
            displayRewards(data);
            updateNotificationCount(data);
        })
        .catch(error => {
            console.error('Error fetching rewards:', error);
            rewardsContainer.innerHTML = '<div class="no-data">حدث خطأ أثناء تحميل البيانات</div>';
        });
}

// Function to display rewards in cards
function displayRewards(rewards) {
    rewardsContainer.innerHTML = '';

    if (rewards.length === 0) {
        rewardsContainer.innerHTML = '<div class="no-data">لا توجد طلبات مكافآت مطابقة للتصفية</div>';
        return;
    }

    rewards.forEach(reward => {
        // Create a card for each reward
        const card = document.createElement('div');
        card.className = 'card';

        let statusClass = '';
        let statusText = '';

        switch (reward.status) {
            case 'wait':
                statusClass = 'status-wait';
                statusText = 'قيد الانتظار';
                break;
            case 'approved':
                statusClass = 'status-approved';
                statusText = 'تمت الموافقة';
                break;
            case 'rejected':
                statusClass = 'status-rejected';
                statusText = 'تم الرفض';
                break;
            case 'returned':
                statusClass = 'status-returned';
                statusText = 'مرجع للتعديل';
                break;
        }

        card.innerHTML = `
            <h3 class="card-title">طلب مكافأة #${reward.reward_id}</h3>
            <div class="card-content">
                <div class="data-row">
                    <span class="data-label">المبلغ:</span>
                    <span class="data-value">${reward.cost}شيكل</span>
                </div>
                <div class="data-row">
                    <span class="data-label">الباحث:</span>
                    <span class="data-value">${reward.researcher_name || 'غير معروف'}</span>
                </div>
                <div class="data-row">
                    <span class="data-label">التاريخ:</span>
                    <span class="data-value">${formatDate(reward.created_at)}</span>
                </div>
                <div class="data-row">
                    <span class="data-label">الحالة:</span>
                    <span class="status-badge ${statusClass}">${statusText}</span>
                </div>
            </div>
            <div class="card-footer">
                <button class="btn btn-primary view-details" data-id="${reward.reward_id}">عرض التفاصيل</button>
            </div>
        `;

        rewardsContainer.appendChild(card);

        // Add event listener to view details button
        card.querySelector('.view-details').addEventListener('click', () => {
            viewRewardDetails(reward.reward_id);
        });
    });
}

// Function to update notification count
function updateNotificationCount(rewards) {
    // Count pending rewards that need current user's action
    let count = 0;

    rewards.forEach(reward => {
        if (reward.status === 'wait') {
            if (isRegularAdmin && reward.approved_admin === 'No') {
                count++;
            } else if (isVPAcademic && reward.approved_admin === 'Yes' && reward.approved_vp_academic === 'No') {
                count++;
            } else if (isPresident && reward.approved_admin === 'Yes' && reward.approved_vp_academic === 'Yes' && 
                      reward.approved_president === 'No') {
                count++;
            }
        }
    });

    document.getElementById('notificationCount').textContent = count;
}

// Function to view reward details
function viewRewardDetails(rewardId) {
    // Show loading indicator
    rewardDetails.innerHTML = '<div class="loader"></div>';
    rewardDetails.style.display = 'block';
    rewardsContainer.style.display = 'none';

    // Fetch reward details from server
    fetch(`get_reward_details.php?id=${rewardId}`)
        .then(response => response.json())
        .then(data => {
            currentReward = data;
            displayRewardDetails(data);
        })
        .catch(error => {
            console.error('Error fetching reward details:', error);
            rewardDetails.innerHTML = '<div class="no-data">حدث خطأ أثناء تحميل البيانات</div>';
        });
}

// Function to display reward details
function displayRewardDetails(reward) {
    // Reset the details view
    document.getElementById('rewardDetails').innerHTML = `
        <h2 class="card-title" id="detailTitle">تفاصيل طلب المكافأة #${reward.reward_id}</h2>
        
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
                <span class="data-value" id="detailRewardId">${reward.reward_id}</span>
            </div>
            <div class="data-row">
                <span class="data-label">اسم الباحث:</span>
                <span class="data-value" id="detailResearcherName">${reward.researcher_name || 'غير معروف'}</span>
            </div>
            <div class="data-row">
                <span class="data-label">عنوان البحث:</span>
                <span class="data-value" id="detailResearchTitle">${reward.research_title || 'غير متوفر'}</span>
            </div>
            <div class="data-row">
                <span class="data-label">المبلغ المطلوب:</span>
                <span class="data-value" id="detailCost">${reward.cost}شيكل</span>
            </div>
            <div class="data-row">
                <span class="data-label">تاريخ التقديم:</span>
                <span class="data-value" id="detailCreatedAt">${formatDate(reward.created_at)}</span>
            </div>
            <div class="data-row">
                <span class="data-label">الحالة:</span>
                <span class="data-value" id="detailStatus">
                    ${getStatusBadge(reward.status)}
                </span>
            </div>
            <div class="data-row">
                <span class="data-label">ملاحظات الباحث:</span>
                <div class="data-value" id="detailUserComments">${reward.user_comments || 'لا توجد ملاحظات'}</div>
            </div>
        </div>

        <div class="tab-content" id="files">
            <h3>الملفات المرفقة</h3>
            <div id="filesList">
                ${reward.has_files ? 
                    `<a href="download_file.php?id=${reward.reward_id}" class="file-btn">
                        <i class="fas fa-file-download"></i> تحميل الملفات المرفقة
                    </a>` : 
                    'لا توجد ملفات مرفقة'}
            </div>
        </div>

        <div class="tab-content" id="comments">
            <h3>تعليقات المراجعة</h3>
            <div id="commentsList">
                ${reward.admins_lastComments ? 
                    `<div class="comment-item">
                        <div class="comment-header">
                            <span>المشرف</span>
                            <span>${formatDate(reward.created_at)}</span>
                        </div>
                        <div class="comment-body">${reward.admins_lastComments}</div>
                    </div>` : 
                    'لا توجد تعليقات بعد'}

                ${reward.comments && reward.comments.length > 0 ?
                    reward.comments.map(comment => `
                        <div class="comment-item">
                            <div class="comment-header">
                                <span>${comment.user_name}</span>
                                <span>${formatDate(comment.timestamp)}</span>
                            </div>
                            <div class="comment-body">${comment.action}</div>
                        </div>
                    `).join('') : ''}
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons" id="actionButtons">
            <!-- Buttons will be added dynamically based on user role and reward status -->
        </div>
    `;

    // Update approval process visualization
    updateApprovalProcess(reward);

    // Add event listeners for tabs
    document.querySelectorAll('.tab-btn').forEach(button => {
        button.addEventListener('click', () => {
            const tab = button.getAttribute('data-tab');

            // Remove active class from all tabs and contents
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });

            // Add active class to clicked tab and its content
            button.classList.add('active');
            document.getElementById(tab).classList.add('active');
        });
    });

    // Add action buttons based on role and reward status
    updateActionButtons(reward);
}

// Function to update approval process visualization
function updateApprovalProcess(reward) {
    const stepUser = document.getElementById('stepUser');
    const stepAdmin = document.getElementById('stepAdmin');
    const stepVP = document.getElementById('stepVP');
    const stepPresident = document.getElementById('stepPresident');
    const stepFinance = document.getElementById('stepFinance');
    const progressFill = document.getElementById('progressFill');

    // Reset all steps
    [stepUser, stepAdmin, stepVP, stepPresident, stepFinance].forEach(step => {
        step.className = 'approval-step';
    });

    // Mark user step as completed
    stepUser.classList.add('completed');

    let progressPercentage = 20; // 20% for the first step (submission)

    // If status is returned, show appropriate step as "returned"
    if (reward.status === 'returned') {
        if (reward.return_target === 'user') {
            stepUser.classList.add('returned');
        } else if (reward.return_target === 'admin') {
            stepAdmin.classList.add('returned');
        } else if (reward.return_target === 'vp') {
            stepVP.classList.add('returned');
        }
    } else {
        // Check admin approval
        if (reward.approved_admin === 'Yes') {
            stepAdmin.classList.add('completed');
            progressPercentage = 40;

            // Check VP approval
            if (reward.approved_vp_academic === 'Yes') {
                stepVP.classList.add('completed');
                progressPercentage = 60;

                // Check president approval
                if (reward.approved_president === 'Yes') {
                    stepPresident.classList.add('completed');
                    progressPercentage = 80;

                    // Check if fully approved (financial release)
                    if (reward.status === 'approved') {
                        stepFinance.classList.add('completed');
                        progressPercentage = 100;
                    } else {
                        stepFinance.classList.add('current');
                    }
                } else {
                    stepPresident.classList.add('current');
                }
            } else {
                stepVP.classList.add('current');
            }
        } else {
            stepAdmin.classList.add('current');
        }

        // If rejected, mark the current step as rejected
        if (reward.status === 'rejected') {
            if (reward.approved_admin === 'No') {
                stepAdmin.classList.add('rejected');
                stepAdmin.classList.remove('current');
            } else if (reward.approved_vp_academic === 'No') {
                stepVP.classList.add('rejected');
                stepVP.classList.remove('current');
            } else if (reward.approved_president === 'No') {
                stepPresident.classList.add('rejected');
                stepPresident.classList.remove('current');
            }
        }
    }

    // Update progress fill
    progressFill.style.width = `${progressPercentage}%`;
}

// Function to update action buttons based on role and reward status
function updateActionButtons(reward) {
    const actionButtons = document.getElementById('actionButtons');
    actionButtons.innerHTML = '';

    // Add back button (always visible)
    const backBtn = document.createElement('button');
    backBtn.className = 'btn btn-primary';
    backBtn.textContent = 'العودة';
    backBtn.addEventListener('click', () => {
        document.getElementById('rewardDetails').style.display = 'none';
        rewardsContainer.style.display = 'grid';
    });

    // Add comment button (always visible for admins)
    const commentBtn = document.createElement('button');
    commentBtn.className = 'btn btn-warning';
    commentBtn.textContent = 'إضافة تعليق';
    commentBtn.addEventListener('click', () => {
        document.getElementById('commentModal').style.display = 'block';
    });

    // Add return button
    const returnBtn = document.createElement('button');
    returnBtn.className = 'btn btn-info';
    returnBtn.textContent = 'ارجاع وتعديل';
    returnBtn.addEventListener('click', () => {
        // Clear previous selections
        if (document.getElementById('returnTarget')) {
            const returnTarget = document.getElementById('returnTarget');
            returnTarget.innerHTML = '';
            
            // Add options based on user role
            if (isRegularAdmin) {
                // Regular admin can only return to user
                const userOption = document.createElement('option');
                userOption.value = 'user';
                userOption.textContent = 'إعادة للمستخدم';
                returnTarget.appendChild(userOption);
            } else if (isVPAcademic) {
                // VP can return to admin or user
                const adminOption = document.createElement('option');
                adminOption.value = 'admin';
                adminOption.textContent = 'إعادة للمشرف';
                returnTarget.appendChild(adminOption);
                
                const userOption = document.createElement('option');
                userOption.value = 'user';
                userOption.textContent = 'إعادة للمستخدم';
                returnTarget.appendChild(userOption);
            } else if (isPresident) {
                // President can return to VP, admin, or user
                const vpOption = document.createElement('option');
                vpOption.value = 'vp';
                vpOption.textContent = 'إعادة لنائب الرئيس';
                returnTarget.appendChild(vpOption);
                
                const adminOption = document.createElement('option');
                adminOption.value = 'admin';
                adminOption.textContent = 'إعادة للمشرف';
                returnTarget.appendChild(adminOption);
                
                const userOption = document.createElement('option');
                userOption.value = 'user';
                userOption.textContent = 'إعادة للمستخدم';
                returnTarget.appendChild(userOption);
            }
        }
        
        document.getElementById('returnModal').style.display = 'block';
    });

    // Add approve and reject buttons based on role and status
    if (reward.status === 'wait') {
        // Regular admin can approve if not yet approved by admin
        if (isRegularAdmin && reward.approved_admin === 'No') {
            addApproveRejectButtons(actionButtons, reward);
            actionButtons.appendChild(returnBtn);
        }

        // VP Academic can approve if approved by admin but not by VP
        else if (isVPAcademic && reward.approved_admin === 'Yes' && reward.approved_vp_academic === 'No') {
            addApproveRejectButtons(actionButtons, reward);
            actionButtons.appendChild(returnBtn);
        }

        // President can approve if approved by admin and VP but not by President
        else if (isPresident && reward.approved_admin === 'Yes' && reward.approved_vp_academic === 'Yes' && reward
            .approved_president === 'No') {
            addApproveRejectButtons(actionButtons, reward);
            actionButtons.appendChild(returnBtn);
        }
    }

    // Add comment and back buttons
    actionButtons.appendChild(commentBtn);
    actionButtons.appendChild(backBtn);
}

// Function to add approve and reject buttons
function addApproveRejectButtons(container, reward) {
    const approveBtn = document.createElement('button');
    approveBtn.className = 'btn btn-success';
    approveBtn.textContent = 'موافقة';
    approveBtn.addEventListener('click', () => {
        document.getElementById('approveModal').style.display = 'block';
    });

    const rejectBtn = document.createElement('button');
    rejectBtn.className = 'btn btn-danger';
    rejectBtn.textContent = 'رفض';
    rejectBtn.addEventListener('click', () => {
        document.getElementById('rejectModal').style.display = 'block';
    });

    container.appendChild(approveBtn);
    container.appendChild(rejectBtn);
}

// Function to approve a reward
function approveReward(rewardId, comments) {
    // Determine which approval field to update based on user role
    let approvalField = '';

    if (isRegularAdmin) {
        approvalField = 'approved_admin';
    } else if (isVPAcademic) {
        approvalField = 'approved_vp_academic';
    } else if (isPresident) {
        approvalField = 'approved_president';
    }

    if (!approvalField) {
        alert('ليس لديك صلاحية الموافقة على هذا الطلب');
        return;
    }

    // Show loading indicator
    const actionButtons = document.getElementById('actionButtons');
    actionButtons.innerHTML = '<div class="loader"></div>';

    // Send AJAX request to approve reward
    const formData = new FormData();
    formData.append('id', rewardId);
    formData.append('field', approvalField);
    formData.append('value', 'Yes');
    formData.append('comments', comments);

    fetch('update_reward_status.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload reward details
                viewRewardDetails(rewardId);

                // Reload rewards list
                fetchRewards();

                // Show success message
                alert('تمت الموافقة على الطلب بنجاح');
            } else {
                alert('حدث خطأ أثناء تحديث حالة الطلب: ' + data.message);
                updateActionButtons(currentReward);
            }
        })
        .catch(error => {
            console.error('Error updating reward status:', error);
            alert('حدث خطأ أثناء تحديث حالة الطلب');
            updateActionButtons(currentReward);
        });
}

// Function to reject a reward
function rejectReward(rewardId, reason) {
    // Determine which approval field to update based on user role
    let approvalField = '';

    if (isRegularAdmin) {
        approvalField = 'approved_admin';
    } else if (isVPAcademic) {
        approvalField = 'approved_vp_academic';
    } else if (isPresident) {
        approvalField = 'approved_president';
    }

    if (!approvalField) {
        alert('ليس لديك صلاحية رفض هذا الطلب');
        return;
    }

    // Show loading indicator
    const actionButtons = document.getElementById('actionButtons');
    actionButtons.innerHTML = '<div class="loader"></div>';

    // Send AJAX request to reject reward
    const formData = new FormData();
    formData.append('id', rewardId);
    formData.append('field', approvalField);
    formData.append('value', 'No');
    formData.append('status', 'rejected');
    formData.append('comments', reason);

    fetch('update_reward_status.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload reward details
                viewRewardDetails(rewardId);

                // Reload rewards list
                fetchRewards();

                // Show success message
                alert('تم رفض الطلب بنجاح');
            } else {
                alert('حدث خطأ أثناء تحديث حالة الطلب: ' + data.message);
                updateActionButtons(currentReward);
            }
        })
        .catch(error => {
            console.error('Error updating reward status:', error);
            alert('حدث خطأ أثناء تحديث حالة الطلب');
            updateActionButtons(currentReward);
        });
}

// New: Function to return a reward for modification
function returnReward(rewardId, reason, target) {
    // Show loading indicator
    const actionButtons = document.getElementById('actionButtons');
    actionButtons.innerHTML = '<div class="loader"></div>';

    // Send AJAX request to return reward
    const formData = new FormData();
    formData.append('id', rewardId);
    formData.append('status', 'returned');
    formData.append('return_target', target);
    formData.append('comments', reason);

    // If returning to a lower level, reset approvals accordingly
    if (target === 'user') {
        formData.append('approved_admin', 'No');
        formData.append('approved_vp_academic', 'No');
        formData.append('approved_president', 'No');
    } else if (target === 'admin') {
        formData.append('approved_admin', 'No');
        formData.append('approved_vp_academic', 'No');
        formData.append('approved_president', 'No');
    } else if (target === 'vp') {
        formData.append('approved_vp_academic', 'No');
        formData.append('approved_president', 'No');
    }

    fetch('return_reward.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload reward details
                viewRewardDetails(rewardId);

                // Reload rewards list
                fetchRewards();

                // Show success message
                alert('تم إرجاع الطلب للتعديل بنجاح');
            } else {
                alert('حدث خطأ أثناء إرجاع الطلب: ' + data.message);
                updateActionButtons(currentReward);
            }
        })
        .catch(error => {
            console.error('Error returning reward:', error);
            alert('حدث خطأ أثناء إرجاع الطلب');
            updateActionButtons(currentReward);
        });
}

// Function to add a comment
function addComment(rewardId, commentText) {
    // Show loading indicator
    const commentsList = document.getElementById('commentsList');
    commentsList.innerHTML = '<div class="loader"></div>';

    // Send AJAX request to add comment
    const formData = new FormData();
    formData.append('id', rewardId);
    formData.append('comments', commentText);

    fetch('add_reward_comment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload reward details
                viewRewardDetails(rewardId);

                // Show success message
                alert('تمت إضافة التعليق بنجاح');
            } else {
                alert('حدث خطأ أثناء إضافة التعليق: ' + data.message);
                commentsList.innerHTML = 'حدث خطأ أثناء إضافة التعليق';
            }
        })
        .catch(error => {
            console.error('Error adding comment:', error);
            alert('حدث خطأ أثناء إضافة التعليق');
            commentsList.innerHTML = 'حدث خطأ أثناء إضافة التعليق';
        });
}

// Helper function to format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('ar-Us', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Helper function to get status badge HTML
function getStatusBadge(status) {
    let statusClass = '';
    let statusText = '';

    switch (status) {
        case 'wait':
            statusClass = 'status-wait';
            statusText = 'قيد الانتظار';
            break;
        case 'approved':
            statusClass = 'status-approved';
            statusText = 'تمت الموافقة';
            break;
        case 'rejected':
            statusClass = 'status-rejected';
            statusText = 'تم الرفض';
            break;
        case 'returned':
            statusClass = 'status-returned';
            statusText = 'مرجع للتعديل';
            break;
    }

    return `<span class="status-badge ${statusClass}">${statusText}</span>`;
}

// Initialize the page: fetch rewards
document.addEventListener('DOMContentLoaded', () => {
    fetchRewards();
});