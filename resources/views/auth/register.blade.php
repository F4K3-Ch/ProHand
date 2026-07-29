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
            <p id="errorElement" class="error" style="display: none;"></p>
        </div>

        <form id="registerForm">
            @csrf

            <div class="register-input-box">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required>
            </div>

            <div class="register-input-box">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" required>
            </div>

            <div class="register-input-box">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" required>
            </div>

            <div class="register-input-box">
                <label for="middle_name">Middle Name (optional)</label>
                <input type="text" id="middle_name" name="middle_name" value="{{ old('middle_name') }}">
            </div>

            <div class="register-input-box">
                <label for="contact_number">Contact Number</label>
                <input type="text" id="contact_number" name="contact_number" value="{{ old('contact_number') }}" required>
            </div>

            <div class="register-input-box">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="register-input-box">
                <label for="password_confirmation">Confirm Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required>
            </div>

            <button type="submit" class="btn">
                Register <div class="circle-spinner" id="registerSpinner" style="display: none;"></div>
            </button>
        </form>

        <p class="login-link">Already have an account?
            <a href="{{ route('login') }}">Login here</a>
        </p>
    </div>

    <script>
        document.getElementById('registerForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const passwordConfirmationInput = document.getElementById('password_confirmation');
            const errorElement = document.getElementById('errorElement');
            const registerSpinner = document.getElementById('registerSpinner');

            registerSpinner.style.display = 'inline-block';
            errorElement.textContent = '';
            errorElement.style.display = 'none';

            const formData = {
                email: emailInput.value,
                last_name: document.getElementById('last_name').value,
                first_name: document.getElementById('first_name').value,
                middle_name: document.getElementById('middle_name').value,
                contact_number: document.getElementById('contact_number').value,
                password: passwordInput.value,
                password_confirmation: passwordConfirmationInput.value,
            };

            fetch('/api/register', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json().then(data => {
                if (!response.ok) {
                    throw data;
                }
                return data;
            }))
            .then(data => {
                localStorage.setItem('token', data.token);
                localStorage.setItem('user_role', data.role);
                
                window.location.href = "/client/dashboard";
            })
            .catch(err => {
                let errorMessage = 'An unexpected error occurred.';
                
                if (err.error) {
                    errorMessage = err.error;
                } else if (err.message) {
                    errorMessage = err.message;
                } else if (err.errors) {
                    if (err.errors.email && err.errors.email.length > 0) {
                        errorMessage = err.errors.email[0];
                    } else {
                        const firstKey = Object.keys(err.errors)[0];
                        errorMessage = err.errors[firstKey][0];
                    }
                }

                errorElement.textContent = errorMessage;
                errorElement.style.display = 'block';

                if (errorMessage.toLowerCase().includes('email')) {
                    emailInput.focus();
                    passwordInput.value = '';
                    passwordConfirmationInput.value = '';
                } else {
                    passwordInput.value = '';
                    passwordConfirmationInput.value = '';
                    passwordInput.focus();
                }
            })
            .finally(() => {
                registerSpinner.style.display = 'none';
            });
        });
    </script>
</body>
</html>