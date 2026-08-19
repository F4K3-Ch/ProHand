@extends('layouts.app')

@section('title', 'Change Password - ProHand')

@section('sidebar')
    @include('layouts.sidebar.' . auth()->user()->role)
@endsection

@section('content')
    <a href="{{ route('profile') }}" class="btn-back">
        <i class="fa-solid fa-arrow-left"></i>
        Back
    </a>

    <main class="view-main">
        <div class="change-password-container">
            <h1>Change Password</h1>

            <div class="view-main-error-success-message">
                <p class="success" id="successMessageText" style="display: none;"></p>
                <p class="error" id="errorMessageText" style="display: none;"></p>
            </div>

            <form id="changePasswordForm">
                @csrf

                <div class="change-password-box">
                    <label>Enter Current Password</label>
                    <input type="password" name="current_password" id="current_password" required>
                </div>

                <div class="change-password-box">
                    <label>Enter New Password</label>
                    <input type="password" name="new_password" id="new_password" required>
                </div>

                <div class="change-password-box">
                    <label>Confirm New Password</label>
                    <input type="password" name="new_password_confirmation" id="new_password_confirmation" required>
                </div>

                <div class="profile-bot-btn">
                    <button type="submit" name="change_password_btn" id="changePasswordBtn" class="btn">
                        Change password
                        <div id="btnSpinner" class="circle-spinner" style="display: none;"></div>
                    </button>
                </div>
            </form>
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const apiKey = "{{ config('services.my_api.api_key') }}";
            const form = document.getElementById('changePasswordForm');
            const errorMessage = document.getElementById('errorMessageText');
            const successMessage = document.getElementById('successMessageText');
            const btn = document.getElementById('changePasswordBtn');
            const btnSpinner = document.getElementById('btnSpinner');

            form.addEventListener('submit', async function (e) {
                e.preventDefault();
                errorMessage.style.display = 'none';
                successMessage.style.display = 'none';

                const currentPassword = document.getElementById('current_password').value;
                const newPassword = document.getElementById('new_password').value;
                const confirmPassword = document.getElementById('new_password_confirmation').value;

                if (newPassword !== confirmPassword) {
                    errorMessage.textContent = 'The new password confirmation does not match.';
                    errorMessage.style.display = 'block';
                    return;
                }

                btn.disabled = true;
                btn.style.opacity = '0.7';
                btn.firstChild.nodeValue = 'Updating... ';
                btnSpinner.style.display = 'inline-block';

                try {
                    const response = await fetch('/api/profile/update-password', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                            'X-API-Key': apiKey,
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            current_password: currentPassword,
                            new_password: newPassword,
                            new_password_confirmation: confirmPassword
                        })
                    });

                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        throw data;
                    }

                    sessionStorage.setItem('success_message', data.message);
                    window.location.href = "{{ route('profile') }}";

                } catch (err) {
                    let errMessage = 'An unexpected error occurred.';

                    if (err.errors) {
                        const firstKey = Object.keys(err.errors)[0];
                        if (firstKey && err.errors[firstKey]?.length) {
                            errMessage = err.errors[firstKey][0];
                        }
                    } else if (err.message) {
                        errMessage = err.message;
                    } else if (err.error) {
                        errMessage = err.error;
                    }

                    errorMessage.textContent = errMessage;
                    errorMessage.style.display = 'block';
                } finally {
                    if (errorMessage.style.display === 'block') {
                        btn.disabled = false;
                        btn.style.opacity = '1';
                        btn.firstChild.nodeValue = 'Change password ';
                        btnSpinner.style.display = 'none';
                    }
                }
            });
        });
    </script>
@endpush