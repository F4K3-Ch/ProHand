@extends('layouts.app')

@section('title', 'My Profile - ProHand')

@section('sidebar')
    @if(request()->is('admin*'))
        @include('layouts.sidebar.admin')
    @elseif(request()->is('tasker*'))
        @include('layouts.sidebar.tasker')
    @elseif(request()->is('client*'))
        @include('layouts.sidebar.client')
    @endif
@endsection

@section('content')
    <a href="#" id="back-btn" class="btn-back">
        <i class="fa-solid fa-arrow-left"></i>
        Back
    </a>

    <main class="view-main">
        <div class="profile-info-container">
            <div class="profile-container">
                <h1>My Profile</h1>

                <div class="view-main-error-success-message" id="message-container"></div>

                <div class="profile-photo-section">
                    <a id="profile-photo-link" href="#" target="_blank">
                        <img id="profile-preview-photo" class="profile-preview-photo" style="display:none;" alt="Profile Picture">
                        <div id="profile-photo-spinner" class="circle-spinner" style="width:150px; height:150px; border-width:4px;"></div>
                    </a>
                </div>

                <div class="profile-bot-container">

                    <div class="profile-box">
                        <label>ID</label>
                        <span id="user-id">Loading...</span>
                    </div>

                    <div class="profile-box">
                        <label>Email</label>
                        <span id="user-email">Loading...</span>
                    </div>

                    <div class="group-profile-box">
                        <div class="profile-box">
                            <label>Last Name</label>
                            <span id="user-last-name">Loading...</span>
                        </div>

                        <div class="profile-box">
                            <label>First Name</label>
                            <span id="user-first-name">Loading...</span>
                        </div>

                        <div class="profile-box">
                            <label>Middle Name</label>
                            <span id="user-middle-name">Loading...</span>
                        </div>
                    </div>

                    <div class="profile-box">
                        <label>Role</label>
                        <span id="user-role" class="role-field">Loading...</span>
                    </div>

                    <div class="profile-box">
                        <label>Contact Number</label>
                        <span id="user-contact">Loading...</span>
                    </div>

                    <div class="profile-box">
                        <label>Created At</label>
                        <span id="user-created">Loading...</span>
                    </div>

                    <div class="profile-bot-btn">
                        <button type="button" id="btn-edit-profile" class="btn-edit-profile">
                            <i class="fa-regular fa-pen-to-square"></i>Edit Profile
                        </button>

                        <button type="button" id="btn-change-password" class="btn-change-password">
                            <i class="fa-solid fa-key"></i>Change Password
                        </button>

                        <button type="button" id="btn-delete-account" class="btn-delete-account">
                            <i class="fa-regular fa-trash-can"></i>Delete Account
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
        try {
            const response = await fetch('/api/me', {
                method: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to retrieve user profile.');
            }

            const data = await response.json();
            const user = data.user;

            if (!user) {
                throw new Error('User data was not returned.');
            }

            document.getElementById('user-id').textContent = user.id || '';
            document.getElementById('user-email').textContent = user.email || '';
            document.getElementById('user-last-name').textContent = user.last_name || '';
            document.getElementById('user-first-name').textContent = user.first_name || '';
            document.getElementById('user-middle-name').textContent = user.middle_name || '';
            document.getElementById('user-contact').textContent = user.contact_number || '';

            if (user.created_at) {
                const date = new Date(user.created_at);

                document.getElementById('user-created').textContent =
                    date.toLocaleDateString('en-US', {
                        month: 'long',
                        day: 'numeric',
                        year: 'numeric'
                    }) + ' ' +
                    date.toLocaleTimeString('en-US', {
                        hour: 'numeric',
                        minute: '2-digit'
                    });
            } else {
                document.getElementById('user-created').textContent = '';
            }

            const roleElement = document.getElementById('user-role');
            const role = user.role?.toLowerCase() || '';

            if (roleElement) {
                roleElement.textContent =
                    role.charAt(0).toUpperCase() + role.slice(1);

                if (role) {
                    roleElement.classList.add('role-' + role);
                }
            }

            const imageElement = document.getElementById('profile-preview-photo');
            const imageSpinner = document.getElementById('profile-photo-spinner');
            const imageLink = document.getElementById('profile-photo-link');

            const pictureUrl = user.profile_picture
                ? "{{ asset('storage') }}/" + user.profile_picture
                : "{{ asset('storage/uploads/profile_pictures/default.jpg') }}";

            if (imageElement) {
                imageElement.src = pictureUrl;
                imageElement.style.display = 'block';
            }

            if (imageLink) {
                imageLink.href = pictureUrl;
            }

            if (imageSpinner) {
                imageSpinner.style.display = 'none';
            }

            const backButton = document.getElementById('back-btn');

            if (backButton && role) {
                backButton.href = `/${role}/dashboard`;
            }

        } catch (error) {
            console.error('Profile error:', error);
        }
    });
</script>
@endpush