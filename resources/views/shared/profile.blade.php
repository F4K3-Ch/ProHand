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
                        src="{{ asset(auth()->user()->profile_picture) }}"
                        alt="Profile Picture"
                    >
                </div>

                <div class="profile-bot-container">
                    <div class="profile-box">
                        <label>ID</label>
                        <input type="text" readonly value="{{ auth()->user()->id }}">
                    </div>

                    <div class="profile-box">
                        <label>Email</label>
                        <input type="text" readonly value="{{ auth()->user()->email }}">
                    </div>

                    <div class="group-profile-box">
                        <div class="profile-box">
                            <label>Last Name</label>
                            <input type="text" readonly value="{{ auth()->user()->last_name }}">
                        </div>

                        <div class="profile-box">
                            <label>First Name</label>
                            <input type="text" readonly value="{{ auth()->user()->first_name }}">
                        </div>

                        <div class="profile-box">
                            <label>Middle Name</label>
                            <input type="text" readonly value="{{ auth()->user()->middle_name ?? '' }}">
                        </div>
                    </div>

                    <div class="profile-box">
                        <label>Role</label>
                        <input
                            type="text"
                            readonly
                            class="role-input-field role-{{ strtolower(auth()->user()->role) }}"
                            value="{{ ucfirst(auth()->user()->role) }}"
                        >
                    </div>

                    <div class="profile-box">
                        <label>Contact Number</label>
                        <input type="text" readonly value="{{ auth()->user()->contact_number }}">
                    </div>

                    <div class="profile-box">
                        <label>Created At</label>
                        <input
                            type="text"
                            readonly
                            value="{{ auth()->user()->created_at->timezone('Asia/Manila')->format('F d, Y h:i A') }}"
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