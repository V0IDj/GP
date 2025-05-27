
document.getElementById('signupForm').addEventListener('submit', function (e) {
    let username = document.getElementById('username').value;
    let password = document.getElementById('password').value;
    let captcha = document.getElementById('captcha').value;

    // Simple validation (this can be extended)
    if (username === '' || password === '' || captcha === '') {
        e.preventDefault();
        alert("يرجى ملء جميع الحقول.");
    }
});