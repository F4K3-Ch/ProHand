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

        @if ($errors->any())
            <div class="error-success-message">
                @foreach ($errors->all() as $error)
                    <p class="error">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}">
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

            <button type="submit" class="btn">Register</button>
        </form>

        <p class="login-link">Already have an account?
            <a href="{{ route('login') }}">Login here</a>
        </p>
    </div>
</body>
</html>