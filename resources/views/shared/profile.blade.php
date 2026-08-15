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

                <div class="view-main-error-success-message" id="message-container"></div>

                <div class="profile-photo-section">
                    <img
                        id="profile-preview-photo"
                        class="profile-preview-photo"
                        src=""
                        alt="Profile Picture"
                    >
                </div>

                <div class="profile-bot-container">
                    <div class="profile-box">
                        <label>ID</label>
                        <input type="text" id="profile-id" readonly>
                    </div>

                    <div class="profile-box">
                        <label>Email</label>
                        <input type="text" id="profile-email" readonly>
                    </div>

                    <div class="group-profile-box">
                        <div class="profile-box">
                            <label>Last Name</label>
                            <input type="text" id="profile-last-name" readonly>
                        </div>

                        <div class="profile-box">
                            <label>First Name</label>
                            <input type="text" id="profile-first-name" readonly>
                        </div>

                        <div class="profile-box">
                            <label>Middle Name</label>
                            <input type="text" id="profile-middle-name" readonly>
                        </div>
                    </div>

                    <div class="profile-box">
                        <label>Role</label>
                        <input
                            type="text"
                            id="profile-role"
                            readonly
                            class="role-input-field"
                        >
                    </div>

                    <div class="profile-box">
                        <label>Contact Number</label>
                        <input type="text" id="profile-contact-number" readonly>
                    </div>

                    <div class="profile-box">
                        <label>Created At</label>
                        <input
                            type="text"
                            id="profile-created-at"
                            readonly
                        >
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

        const messageContainer = document.getElementById('message-container');

        try {
            const response = await fetch("{{ route('profile.data') }}", {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to load profile.');
            }

            const user = result.user;

            document.getElementById('profile-id').value = user.id;
            document.getElementById('profile-email').value = user.email;
            document.getElementById('profile-last-name').value = user.last_name;
            document.getElementById('profile-first-name').value = user.first_name;
            document.getElementById('profile-middle-name').value = user.middle_name ?? '';
            document.getElementById('profile-contact-number').value = user.contact_number;
            document.getElementById('profile-created-at').value = user.created_at;

            const roleInput = document.getElementById('profile-role');

            roleInput.value = user.role.charAt(0).toUpperCase() + user.role.slice(1);

            roleInput.classList.add(
                'role-' + user.role.toLowerCase()
            );

            document.getElementById('profile-preview-photo').src =
                user.profile_picture;

        } catch (error) {
            console.error('Profile error:', error);

            messageContainer.textContent =
                'Unable to load profile information.';

            messageContainer.style.display = 'block';
        }
    });
</script>
@endpush