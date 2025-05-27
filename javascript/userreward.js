document.addEventListener('DOMContentLoaded', function() {
    // Signature Pad Functionality
    const canvas = document.getElementById('signatureCanvas');
    const clearButton = document.getElementById('clearSignature');
    const signatureDataInput = document.getElementById('signatureData');
    const form = document.getElementById('rewardForm');
    
    // Initialize canvas context
    const ctx = canvas.getContext('2d');
    ctx.strokeStyle = '#771411';
    ctx.lineWidth = 2;
    
    // Set canvas dimensions based on container
    function resizeCanvas() {
        const container = canvas.parentElement;
        canvas.width = container.offsetWidth;
        canvas.height = 200;
        
        // Reset canvas style
        ctx.strokeStyle = '#771411';
        ctx.lineWidth = 2;
    }
    
    // Call resize on load
    resizeCanvas();
    
    // Variables for drawing
    let isDrawing = false;
    let lastX = 0;
    let lastY = 0;
    
    // Drawing functions
    function startDrawing(e) {
        isDrawing = true;
        const rect = canvas.getBoundingClientRect();
        
        // Handle both mouse and touch events
        if (e.type === 'touchstart') {
            lastX = e.touches[0].clientX - rect.left;
            lastY = e.touches[0].clientY - rect.top;
        } else {
            lastX = e.clientX - rect.left;
            lastY = e.clientY - rect.top;
        }
    }
    
    function draw(e) {
        if (!isDrawing) return;
        e.preventDefault();
        const rect = canvas.getBoundingClientRect();
        
        let currentX, currentY;
        
        // Handle both mouse and touch events
        if (e.type === 'touchmove') {
            currentX = e.touches[0].clientX - rect.left;
            currentY = e.touches[0].clientY - rect.top;
        } else {
            currentX = e.clientX - rect.left;
            currentY = e.clientY - rect.top;
        }
        
        // Draw line
        ctx.beginPath();
        ctx.moveTo(lastX, lastY);
        ctx.lineTo(currentX, currentY);
        ctx.stroke();
        
        // Update last position
        lastX = currentX;
        lastY = currentY;
        
        // Save signature data
        saveSignatureData();
    }
    
    function stopDrawing() {
        isDrawing = false;
    }
    
    // Save signature data to hidden input
    function saveSignatureData() {
        signatureDataInput.value = canvas.toDataURL('image/png');
    }
    
    // Clear signature
    function clearSignature() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        signatureDataInput.value = '';
    }
    
    // Event listeners for desktop
    canvas.addEventListener('mousedown', startDrawing);
    canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('mouseup', stopDrawing);
    canvas.addEventListener('mouseout', stopDrawing);
    
    // Event listeners for mobile
    canvas.addEventListener('touchstart', startDrawing);
    canvas.addEventListener('touchmove', draw);
    canvas.addEventListener('touchend', stopDrawing);
    
    // Clear button
    clearButton.addEventListener('click', clearSignature);
    
    // Window resize event
    window.addEventListener('resize', resizeCanvas);
    
    // Dynamic file input validation
    const scopusCheckbox = document.getElementById('scopus');
    const webOfScienceCheckbox = document.getElementById('webofscience');
    const scopusProofFile = document.getElementById('scopusProofFile');
    const wosProofFile = document.getElementById('wosProofFile');
    
    // Update file input required status based on checkbox selection
    function updateFileRequirements() {
        scopusProofFile.required = scopusCheckbox.checked;
        wosProofFile.required = webOfScienceCheckbox.checked;
    }
    
    scopusCheckbox.addEventListener('change', updateFileRequirements);
    webOfScienceCheckbox.addEventListener('change', updateFileRequirements);
    
    // Initialize file requirements
    updateFileRequirements();
    
    // Form validation before submission
    form.addEventListener('submit', function(e) {
        // Make sure at least one classification is selected
        if (!scopusCheckbox.checked && !webOfScienceCheckbox.checked) {
            e.preventDefault();
            alert('الرجاء اختيار تصنيف المجلة (Scopus أو Web of Science)');
            return false;
        }
        
        // Check file requirements based on selections
        if (scopusCheckbox.checked && !scopusProofFile.files.length) {
            e.preventDefault();
            alert('الرجاء إرفاق إثبات نشر البحث في قاعدة بيانات Scopus');
            return false;
        }
        
        if (webOfScienceCheckbox.checked && !wosProofFile.files.length) {
            e.preventDefault();
            alert('الرجاء إرفاق إثبات إدراج المجلة في Web of Science');
            return false;
        }
        
        // Check all required fields
        const requiredInputs = form.querySelectorAll('[required]');
        let allFilled = true;
        
        requiredInputs.forEach(input => {
            if (input.type === 'file' && !input.files.length) {
                allFilled = false;
                input.focus();
            } else if (input.type !== 'file' && !input.value.trim()) {
                allFilled = false;
                input.focus();
            }
        });
        
        if (!allFilled) {
            e.preventDefault();
            alert('الرجاء ملء جميع الحقول المطلوبة');
            return false;
        }
    });
    
    // Research title autocomplete functionality
    const researchTitleInput = document.getElementById('researchTitle');
    
    researchTitleInput.addEventListener('input', function() {
        const searchTerm = this.value.trim();
        
        if (searchTerm.length >= 3) {
            // Make AJAX request to search for existing research titles
            fetch('search_research.php?term=' + encodeURIComponent(searchTerm))
                .then(response => response.json())
                .then(data => {
                    // Create or update datalist for autocomplete
                    let datalist = document.getElementById('researchTitleList');
                    
                    if (!datalist) {
                        datalist = document.createElement('datalist');
                        datalist.id = 'researchTitleList';
                        document.body.appendChild(datalist);
                        researchTitleInput.setAttribute('list', 'researchTitleList');
                    }
                    
                    // Clear existing options
                    datalist.innerHTML = '';
                    
                    // Add new options
                    data.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.title;
                        option.dataset.id = item.id;
                        datalist.appendChild(option);
                    });
                })
                .catch(error => console.error('Error fetching research titles:', error));
        }
    });
});