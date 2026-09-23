<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('img/ProHand(Adapt).png') }}">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('style/style.css') }}">
    <title>Verify Email - ProHand</title>

    <script>
        const savedTheme = localStorage.getItem('theme');
        document.documentElement.setAttribute('data-theme', savedTheme || 'dark');
    </script>
</head>

<body class="register-page">

    <button type="button" class="auth-theme-toggle" id="themeToggle">
        <i class="fa-solid fa-moon"></i>
        <span>Dark Mode</span>
    </button>

    <div class="register-container verify-email-container">

        <a href="{{ route('login') }}" class="btn-back-auth">
            <i class="fa-solid fa-arrow-left"></i>
            Back
        </a>

        <div class="auth-logo verify-email-logo">
            <img src="{{ asset('img/ProHand(Adapt).png') }}" alt="ProHand">
        </div>

        <div class="auth-header">
            <h1>Verify Your Email</h1>
            <p>One more step to activate your ProHand account.</p>
        </div>

        <div class="error-success-message">
            <p class="success" id="successMessageText" style="display:none;"></p>
            <p class="error" id="errorMessageText" style="display:none;"></p>
        </div>

        <div class="verification-content">

            <div class="verification-icon">
                <i class="fa-solid fa-envelope"></i>
            </div>

            <h2>Check Your Email</h2>

            <p class="verification-description">
                We sent a verification link to the email address below.
                Click the link in the email to verify your account.
            </p>

            <div class="verification-email-box">
                <i class="fa-solid fa-at"></i>
                <strong id="verificationEmail">your email address</strong>
            </div>

            <button type="button" class="btn" id="resendBtn">
                <span id="resendText">
                    <i class="fa-solid fa-paper-plane"></i>
                    Resend Verification Email
                </span>

                <span class="circle-spinner" id="resendSpinner" style="display:none;"></span>
            </button>

            <p class="verification-note">
                <i class="fa-solid fa-circle-info"></i>
                Didn't receive the email? Check your spam or junk folder.
            </p>

        </div>
    </div>

    <script>
        const apiKey = "{{ config('services.my_api.api_key') }}";
        const origin = "{{ config('services.my_api.origin') }}";

        const successMessageText = document.getElementById('successMessageText');
        const errorMessageText = document.getElementById('errorMessageText');
        const verificationEmail = document.getElementById('verificationEmail');
        const resendBtn = document.getElementById('resendBtn');
        const resendText = document.getElementById('resendText');
        const resendSpinner = document.getElementById('resendSpinner');

        const email = sessionStorage.getItem('verification_email');

        if (email) {
            verificationEmail.textContent = email;
        }

        function showSuccessMessage(text) {
            errorMessageText.textContent = '';
            errorMessageText.style.display = 'none';

            successMessageText.textContent = text;
            successMessageText.style.display = 'block';
        }

        function showErrorMessage(text) {
            successMessageText.textContent = '';
            successMessageText.style.display = 'none';

            errorMessageText.textContent = text;
            errorMessageText.style.display = 'block';
        }

        function clearMessages() {
            successMessageText.textContent = '';
            successMessageText.style.display = 'none';

            errorMessageText.textContent = '';
            errorMessageText.style.display = 'none';
        }

        const successMessage = sessionStorage.getItem('success_message');

        if (successMessage) {
            showSuccessMessage(successMessage);
            sessionStorage.removeItem('success_message');
        }

        resendBtn.addEventListener('click', async function () {
            if (!email) {
                showErrorMessage('Email address not found. Please register again.');
                return;
            }

            resendBtn.disabled = true;
            resendText.style.display = 'none';
            resendSpinner.style.display = 'inline-block';

            clearMessages();

            try {
                const response = await fetch("{{ route('verification.send') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-API-KEY': apiKey,
                        'X-MY-ORIGIN': origin
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        email: email
                    })
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(
                        result.message || 'Failed to resend verification email.'
                    );
                }

                showSuccessMessage(result.message);

            } catch (error) {
                showErrorMessage(error.message);

            } finally {
                resendBtn.disabled = false;
                resendText.style.display = 'inline-flex';
                resendSpinner.style.display = 'none';
            }
        });

        const themeToggle = document.getElementById('themeToggle');
        const html = document.documentElement;

        function updateThemeButton() {
            const dark = html.getAttribute('data-theme') === 'dark';

            themeToggle.innerHTML = dark
                ? '<i class="fa-solid fa-sun"></i><span>Light Mode</span>'
                : '<i class="fa-solid fa-moon"></i><span>Dark Mode</span>';
        }

        updateThemeButton();

        themeToggle.addEventListener('click', function () {
            const dark = html.getAttribute('data-theme') === 'dark';
            const theme = dark ? 'light' : 'dark';

            html.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);

            updateThemeButton();
        });
    </script>

</body>
</html>