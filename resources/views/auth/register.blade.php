<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('img/ProHand(Adapt).png') }}">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('style/style.css') }}">
    <title>Register for ProHand</title>
</head>
<body class="register-page">

    <a href="{{ url('/') }}" class="btn-back-auth">
        <i class="fa-solid fa-arrow-left"></i>
        Back
    </a>

    <div class="register-container">
        <h1>Register Here</h1>

        <div class="error-success-message">
            <p id="errorElement" class="error" style="display:none;"></p>
        </div>

        <form id="registerForm">
            <div class="register-input-box">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="register-input-box">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" required>
            </div>

            <div class="register-input-box">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" required>
            </div>

            <div class="register-input-box">
                <label for="middle_name">Middle Name (optional)</label>
                <input type="text" id="middle_name" name="middle_name">
            </div>

            <div class="register-input-box">
                <label for="contact_number">Contact Number</label>
                <input type="text" id="contact_number" name="contact_number" required>
            </div>

            <div class="register-input-box">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="register-input-box">
                <label for="password_confirmation">Confirm Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required>
            </div>

            <button type="submit" class="btn" id="registerButton">
                Register
                <div class="circle-spinner" id="registerSpinner" style="display:none;"></div>
            </button>
        </form>

        <p class="login-link">
            Already have an account?
            <a href="{{ route('login') }}">Login here</a>
        </p>
    </div>

    <script>
        const registerForm = document.getElementById('registerForm');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const passwordConfirmationInput = document.getElementById('password_confirmation');
        const errorElement = document.getElementById('errorElement');
        const spinner = document.getElementById('registerSpinner');
        const registerButton = document.getElementById('registerButton');

        function redirectByRole(user) {
            if (user.role === 'admin') {
                window.location.href = '/admin/dashboard';
            } else if (user.role === 'tasker') {
                window.location.href = '/tasker/dashboard';
            } else if (user.role === 'client') {
                window.location.href = '/client/dashboard';
            } else {
                localStorage.removeItem('token');
                localStorage.removeItem('user');
                throw new Error('Unknown user role.');
            }
        }

        const token = localStorage.getItem('token');

        if (token) {
            fetch('/api/me', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + token
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error();
                }

                return response.json();
            })
            .then(data => {
                localStorage.setItem('user', JSON.stringify(data.user));
                redirectByRole(data.user);
            })
            .catch(() => {
                localStorage.removeItem('token');
                localStorage.removeItem('user');
            });
        }

        registerForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = {
                email: emailInput.value.trim(),
                last_name: document.getElementById('last_name').value.trim(),
                first_name: document.getElementById('first_name').value.trim(),
                middle_name: document.getElementById('middle_name').value.trim(),
                contact_number: document.getElementById('contact_number').value.trim(),
                password: passwordInput.value,
                password_confirmation: passwordConfirmationInput.value
            };

            spinner.style.display = 'inline-block';
            registerButton.disabled = true;

            errorElement.textContent = '';
            errorElement.style.display = 'none';

            fetch('/api/register', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => {
                return response.json().then(data => {
                    if (!response.ok) {
                        throw data;
                    }

                    return data;
                });
            })
            .then(data => {
                localStorage.setItem('token', data.token);
                localStorage.setItem('user', JSON.stringify(data.user));
                sessionStorage.setItem('success_message', data.message);

                redirectByRole(data.user);
            })
            .catch(error => {
                let message = 'Registration failed.';

                if (error.error) {
                    message = error.error;
                } else if (error.message) {
                    message = error.message;
                } else if (error.errors) {
                    const firstKey = Object.keys(error.errors)[0];
                    message = error.errors[firstKey][0];
                }

                passwordInput.value = '';
                passwordConfirmationInput.value = '';

                errorElement.textContent = message;
                errorElement.style.display = 'block';

                if (message.toLowerCase().includes('email')) {
                    emailInput.focus();
                } else {
                    passwordInput.focus();
                }
            })
            .finally(() => {
                spinner.style.display = 'none';
                registerButton.disabled = false;
            });
        });
    </script>
</body>
</html>