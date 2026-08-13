<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="api-key" content="{{ config('services.my_api.api_key') }}">
    <meta name="api-origin" content="{{ config('services.my_api.origin') }}">
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
    document.getElementById('registerForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const button = document.getElementById('registerButton');
        const spinner = document.getElementById('registerSpinner');
        const error = document.getElementById('errorElement');

        button.disabled = true;
        spinner.style.display = 'inline-block';
        error.style.display = 'none';

        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const apiKey = document.querySelector('meta[name="api-key"]').content;
        const Origin = document.querySelector('meta[name="api-origin"]').content;

        try {
            const response = await fetch('/api/register', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'x-api-key': apiKey,
                    'Origin': Origin
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    email: document.getElementById('email').value,
                    first_name: document.getElementById('first_name').value,
                    middle_name: document.getElementById('middle_name').value,
                    last_name: document.getElementById('last_name').value,
                    contact_number: document.getElementById('contact_number').value,
                    password: document.getElementById('password').value,
                    password_confirmation: document.getElementById('password_confirmation').value
                })
            });

            const data = await response.json();

            if (!response.ok) {
                if (data.errors) {
                    const messages = Object.values(data.errors).flat();
                    throw new Error(messages.join(' '));
                }

                throw new Error(data.message || 'Registration failed.');
            }

            window.location.href = data.redirect;
        } catch (err) {
            error.textContent = err.message;
            error.style.display = 'block';
            button.disabled = false;
            spinner.style.display = 'none';
        }
    });
</script>

</body>
</html>