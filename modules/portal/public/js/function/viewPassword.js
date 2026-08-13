const password = document.getElementById('password');
        const passwordIcon = document.getElementById('passwordIcon');
        passwordIcon.addEventListener('click', function() {
            if (password.type === 'password') {
                password.type = 'text';
                passwordIcon.classList.remove('fa-eye');
                passwordIcon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                passwordIcon.classList.remove('fa-eye-slash');
                passwordIcon.classList.add('fa-eye');
            }
        });