// code for valdion the login page 
document.getElementById('loginForm').addEventListener('submit', function (e) {
    let username = document.getElementById('username').value;
    let password = document.getElementById('password').value;   

    // Simple validation (this can be extended)
    if (username === '' || password === '') {
        e.preventDefault();
        alert("يرجى ملء جميع الحقول.");
    }
});

