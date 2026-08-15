@extends('layouts.app')

@section('title', 'My Profile - ProHand')

@section('sidebar')
    @include('layouts.sidebar.' . auth()->user()->role)
@endsection

@section('content')
    <a href="{{ route(auth()->user()->role . '.dashboard') }}" class="btn-back">
        <i class="fa-solid fa-arrow-left"></i>
        Back
    </a>

    <main class="view-main">
        <div class="profile-info-container">
            <div class="profile-container">
                <h1>My Profile</h1>

                <div class="view-main-error-success-message">
                    <p class="error" id="error-message" style="display: none;"></p>
                </div>

                <div class="profile-photo-section">
                    <img
                        id="profile-preview-photo"
                        class="profile-preview-photo"
                        src=""
                        alt="Profile Picture"
                        style="display: none;"
                    >

                    <div
                        id="profile-photo-spinner"
                        class="circle-spinner"
                        style="width: 150px; height: 150px; border-width: 4px;"
                    ></div>
                </div>

                <div class="profile-bot-container">
                    <div class="profile-box">
                        <label>ID</label>
                        <span id="profile-id">Loading...</span>
                    </div>

                    <div class="profile-box">
                        <label>Email</label>
                        <span id="profile-email">Loading...</span>
                    </div>

                    <div class="group-profile-box">
                        <div class="profile-box">
                            <label>Last Name</label>
                            <span id="profile-last-name">Loading...</span>
                        </div>

                        <div class="profile-box">
                            <label>First Name</label>
                            <span id="profile-first-name">Loading...</span>
                        </div>

                        <div class="profile-box">
                            <label>Middle Name</label>
                            <span id="profile-middle-name">Loading...</span>
                        </div>
                    </div>

                    <div class="profile-box">
                        <label>Role</label>
                        <span id="profile-role" class="role-input-field">Loading...</span>
                    </div>

                    <div class="profile-box">
                        <label>Contact Number</label>
                        <span id="profile-contact-number">Loading...</span>
                    </div>

                    <div class="profile-box">
                        <label>Created At</label>
                        <span id="profile-created-at">Loading...</span>
                    </div>

                    <div class="profile-bot-btn">
                        <button type="button" id="btn-edit-profile" class="btn-edit-profile">
                            <i class="fa-regular fa-pen-to-square"></i>
                            Edit Profile
                        </button>

                        <button type="button" id="btn-change-password" class="btn-change-password">
                            <i class="fa-solid fa-key"></i>
                            Change Password
                        </button>

                        <button type="button" id="btn-delete-account" class="btn-delete-account">
                            <i class="fa-regular fa-trash-can"></i>
                            Delete Account
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', async function () {

        const messageContainer = document.getElementById('error-message');
        const profilePhoto = document.getElementById('profile-preview-photo');
        const profilePhotoSpinner = document.getElementById('profile-photo-spinner');

        try {
            const response = await fetch('/api/profile/data', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-API-Key': "{{ \App\Models\AccessToken::find(1)->token }}",
                },
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(
                    result.message || 'Failed to load profile information.'
                );
            }

            const user = result.user;

            document.getElementById('profile-id').textContent =
                user.id;

            document.getElementById('profile-email').textContent =
                user.email;

            document.getElementById('profile-last-name').textContent =
                user.last_name;

            document.getElementById('profile-first-name').textContent =
                user.first_name;

            document.getElementById('profile-middle-name').textContent =
                user.middle_name ?? '';

            document.getElementById('profile-contact-number').textContent =
                user.contact_number;

            document.getElementById('profile-created-at').textContent =
                user.created_at;

            const roleElement = document.getElementById('profile-role');

            roleElement.textContent =
                user.role.charAt(0).toUpperCase() + user.role.slice(1);

            roleElement.classList.add(
                'role-' + user.role.toLowerCase()
            );

            const profilePhoto = document.getElementById('profile-preview-photo');
            const profilePhotoSpinner = document.getElementById('profile-photo-spinner');

            profilePhoto.onload = function () {
                profilePhotoSpinner.style.display = 'none';
                profilePhoto.style.display = 'block';
            };

            profilePhoto.onerror = function () {
                profilePhotoSpinner.style.display = 'none';
                profilePhoto.style.display = 'block';
            };

            profilePhoto.src = user.profile_picture;

        } catch (error) {
            console.error('Profile error:', error);

            messageContainer.textContent =
                error.message || 'Unable to load profile information.';

            messageContainer.style.display = 'block';
        }
    });
</script>
@endpush