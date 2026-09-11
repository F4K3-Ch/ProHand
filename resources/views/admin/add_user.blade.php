@extends('layouts.app')

@section('title', 'User Profile - ProHand')

@section('sidebar')
    @include('layouts.sidebar.admin')
@endsection

@section('content')
    <a href="{{ route(request('from', 'admin.manage-users')) }}" class="btn-back">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>

    <main class="view-main">
        <div class="add-user-container">
            <h1>Add a User</h1>

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

                <div class="add-user-input-box">
                    <label>Role</label>
                    <select name="role" id="role" required>
                        <option value="">Select Role</option>
                        <option value="admin">Admin</option>
                        <option value="tasker">Tasker</option>
                        <option value="client">Client</option>
                    </select>
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
        </div>
    </main>
@endsection

@push('scripts')
<script>
    document.getElementById('registerForm').addEventListener('submit', async function (event) {
        event.preventDefault();

        const registerButton = document.getElementById('registerButton');
        const registerSpinner = document.getElementById('registerSpinner');
        const errorElement = document.getElementById('errorElement');

        errorElement.style.display = 'none';
        errorElement.textContent = '';

        registerButton.disabled = true;
        registerSpinner.style.display = 'inline-block';

        const formData = new FormData(this);

        try {
            const response = await fetch("{{ route('register') }}", {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'X-API-KEY': apiKey,
                    'X-MY-ORIGIN' : origin
                },
                body: formData,
                credentials: 'same-origin'
            });

            const result = await response.json();

            if (!response.ok) {
                if (result.errors) {
                    if (result.errors.email) {
                        errorElement.textContent = result.errors.email[0];
                    } else if (result.errors.password) {
                        errorElement.textContent = result.errors.password[0];
                    } else {
                        errorElement.textContent = Object.values(result.errors)[0][0];
                    }
                } else {
                    errorElement.textContent = result.message || 'Registration failed.';
                }

                errorElement.style.display = 'block';
                return;
            }

            sessionStorage.setItem('success_message', result.message);

            const role = result.user.role;

            if (role === 'admin') {
                window.location.href = '/admin/dashboard';
            } else if (role === 'tasker') {
                window.location.href = '/tasker/dashboard';
            } else {
                window.location.href = '/client/dashboard';
            }

        } catch (error) {
            console.error('Registration error:', error);

            errorElement.textContent = 'Something went wrong. Please try again.';
            errorElement.style.display = 'block';

        } finally {
            registerButton.disabled = false;
            registerSpinner.style.display = 'none';
        }
    });
</script>
@endpush