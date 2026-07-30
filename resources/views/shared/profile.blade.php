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
                        <img id="profile-preview-photo" class="profile-preview-photo" style="display: none;" alt="Profile Picture">
                        <div id="profile-photo-spinner" class="circle-spinner" style="width: 80px; height: 80px; border-width: 4px;"></div>
                    </a>
                </div>

                <div class="profile-bot-container">

                    <div class="profile-box">
                        <label>ID</label>
                        <input type="text" readonly id="user-id" value="Loading...">
                    </div>

                    <div class="profile-box">
                        <label>Email</label>
                        <input type="text" readonly id="user-email" value="Loading...">
                    </div>

                    <div class="group-profile-box">
                        <div class="profile-box">
                            <label>Last Name</label>
                            <input type="text" readonly id="user-last-name" value="Loading...">
                        </div>

                        <div class="profile-box">
                            <label>First Name</label>
                            <input type="text" readonly id="user-first-name" value="Loading...">
                        </div>

                        <div class="profile-box">
                            <label>Middle Name</label>
                            <input type="text" readonly id="user-middle-name" value="Loading...">
                        </div>
                    </div>

                    <div class="profile-box">
                        <label>Role</label>
                        <input type="text" readonly id="user-role-input" class="role-input-field" value="Loading...">
                    </div>

                    <div class="profile-box">
                        <label>Contact Number</label>
                        <input type="text" readonly id="user-contact" value="Loading...">
                    </div>

                    <div class="profile-box">
                        <label>Created At</label>
                        <input type="text" readonly id="user-created" value="Loading...">
                    </div>

                    <div class="profile-bot-btn">
                        <button type="button" id="btn-edit-profile" class="btn-edit-profile"><i class="fa-regular fa-pen-to-square"></i>Edit Profile</button>

                        <button type="button" id="btn-change-password" class="btn-change-password"><i class="fa-solid fa-key"></i>Change Password</button>

                        <button type="button" id="btn-delete-account" class="btn-delete-account"><i class="fa-regular fa-trash-can"></i>Delete Account</button>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const token = localStorage.getItem('token');
        if (!token) return;

        fetch('/api/user', {
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + token,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) throw new Error('Failed to fetch profile');
            return response.json();
        })
        .then(user => {
            document.getElementById('user-id').value = user.id || '';
            document.getElementById('user-email').value = user.email || '';
            document.getElementById('user-last-name').value = user.last_name || '';
            document.getElementById('user-first-name').value = user.first_name || '';
            document.getElementById('user-middle-name').value = user.middle_name || '';
            document.getElementById('user-contact').value = user.contact_number || '';
            
            if (user.created_at) {
                const date = new Date(user.created_at);
                document.getElementById('user-created').value = date.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) + ' ' + date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
            }

            const roleInput = document.getElementById('user-role-input');
            const formattedRole = user.role.charAt(0).toUpperCase() + user.role.slice(1);
            roleInput.value = formattedRole;
            roleInput.classList.add('role-' + user.role.toLowerCase());

            const imgEl = document.getElementById('profile-preview-photo');
            const imgSpinner = document.getElementById('profile-photo-spinner');
            const imgLink = document.getElementById('profile-photo-link');
            
            const pictureUrl = user.profile_picture 
                ? `/storage/${user.profile_picture}` 
                : `/storage/uploads/profile_pictures/default.jpg`;

            imgEl.src = pictureUrl;
            imgLink.href = pictureUrl;
            imgEl.style.display = 'block';
            if (imgSpinner) imgSpinner.style.display = 'none';

            const role = user.role.toLowerCase();
            const backBtn = document.getElementById('back-btn');
            
            backBtn.href = `/${role}/dashboard`;

        })
        .catch(error => {
            console.error(error);
        });
    });
</script>
@endpush