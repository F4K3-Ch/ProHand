@extends('layouts.app')

@section('title', 'View Errand')

@section('sidebar')
    @include('layouts.sidebar.client')
@endsection

@section('content')
    <a href="{{ route('admin.dashboard') }}" class="btn-back">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>

    <main class="view-main" data-errand-id="{{ $id }}">
        <div class="info-container">
            <div class="errand-details-container">
                <h1>Errand Details</h1>

                <div class="view-main-error-success-message">
                    @if(session('success'))
                        <p class="success" id="sessionSuccessMessage">{{ session('success') }}</p>
                    @endif

                    @if(session('error'))
                        <p class="error" id="sessionErrorMessage">{{ session('error') }}</p>
                    @endif

                    <p class="success" id="successMessageText" style="display: none;"></p>
                    <p class="error" id="errorMessageText" style="display: none;"></p>
                </div>

                <div class="group-info-box">
                    <div class="info-box">
                        <label>Errand ID</label>
                        <span id="errand-id">Loading...</span>
                    </div>
                    <div class="info-box">
                        <label>Category</label>
                        <span id="errand-category">Loading...</span>
                    </div>
                </div>

                <div class="info-box">
                    <label>Title</label>
                    <span id="errand-title">Loading...</span>
                </div>

                <div class="info-box">
                    <label>Description</label>
                    <div id="errand-description" class="text-area">Loading...</div>
                </div>

                <div class="info-box">
                    <label>Location</label>
                    <span id="errand-location">Loading...</span>
                </div>

                <div class="info-box">
                    <label>Contact</label>
                    <div id="errand-contact" class="text-area">Loading...</div>
                </div>

                <div class="group-info-box">
                    <div class="info-box">
                        <label>Fee</label>
                        <span id="errand-fee">Loading...</span>
                    </div>
                    <div class="info-box">
                        <label>Status</label>
                        <span id="errand-status" class="status-field">Loading...</span>
                    </div>
                </div>

                <div class="group-info-box">
                    <div class="info-box">
                        <label>Created At</label>
                        <span id="errand-created-at">Loading...</span>
                    </div>
                    <div class="info-box">
                        <label>Last Updated</label>
                        <span id="errand-updated-at">Loading...</span>
                    </div>
                </div>

                <div class="group-info-box">
                    <div class="info-box">
                        <label>Accepted At</label>
                        <span id="errand-accepted-at">Loading...</span>
                    </div>
                    <div class="info-box">
                        <label>In Process At</label>
                        <span id="errand-in-process-at">Loading...</span>
                    </div>
                </div>

                <div class="group-info-box">
                    <div class="info-box">
                        <label>Deadline</label>
                        <span id="errand-deadline">Loading...</span>
                    </div>
                    <div class="info-box">
                        <label>Finished At</label>
                        <span id="errand-finished-at">Loading...</span>
                    </div>
                </div>

                <div class="group-info-box">
                    <div class="info-box">
                        <label>Attachment</label>
                        <div id="errand-attachment">
                            <div class="attachment-item" style="display: none;">
                                <a href="#" target="_blank" class="attachment-name" title="Preview File">
                                    <i class="fa-solid fa-file"></i>
                                    <span class="attachment-filename"></span>
                                </a>
                                <a href="#" class="btn-download" download>
                                    <i class="fa-solid fa-download"></i>
                                    Download
                                </a>
                            </div>
                            <div class="no-attachments">
                                <p class="no-attachment">None</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="info-bot-btn">

                </div>
            </div>

            <div class="users-container">
                <div class="user-details-container">
                    <h1>Created By</h1>

                    <div class="user-header-row">
                        <div class="user-profile-photo">
                            <img
                                id="client-profile-photo"
                                class="profile-preview-photo"
                                src=""
                                alt="Profile Picture"
                                style="display: none;"
                            >

                            <div
                                id="profile-photo-spinner"
                                class="circle-spinner"
                                style="width: 110px; height: 110px; border-width: 4px;"
                            ></div>
                        </div>

                        <div class="user-cancellation-alert-box">
                            <p class="alert-title">
                                <i class="fa-solid fa-triangle-exclamation"></i> Requesting Cancellation
                            </p>

                            <div class="alert-action-buttons">
                                <button type="button" class="btn-approve-release">
                                    <i class="fa-solid fa-user-minus"></i>Approve
                                </button>

                                <button type="button" class="btn-deny-release">
                                    <i class="fa-solid fa-user-check"></i>Deny
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="group-info-box">
                        <div class="info-box">
                            <label>User ID</label>
                            <span id="client-id">Loading...</span>
                        </div>

                        <div class="info-box">
                            <label>Email</label>
                            <span id="client-email">Loading...</span>
                        </div>
                    </div>

                    <div class="group-info-box">
                        <div class="info-box">
                            <label>Name</label>
                            <span id="client-name">Loading...</span>
                        </div>

                        <div class="info-box">
                            <label>Contact Number</label>
                            <span id="client-contact-number">Loading...</span>
                        </div>
                    </div>
                </div>

                <div class="user-details-container">
                    <h1>Accepted By</h1>

                    <div class="user-header-row">
                        <div class="user-profile-photo">
                            <img
                                id="tasker-profile-photo"
                                class="profile-preview-photo"
                                src=""
                                alt="Profile Picture"
                                style="display: none;"
                            >

                            <div
                                id="profile-photo-spinner"
                                class="circle-spinner"
                                style="width: 110px; height: 110px; border-width: 4px;"
                            ></div>
                        </div>

                        <div class="user-cancellation-alert-box">
                            <p class="alert-title">
                                <i class="fa-solid fa-triangle-exclamation"></i> Requesting Cancellation
                            </p>

                            <div class="alert-action-buttons">
                                <button type="button" class="btn-approve-release">
                                    <i class="fa-solid fa-user-minus"></i>Approve
                                </button>

                                <button type="button" class="btn-deny-release">
                                    <i class="fa-solid fa-user-check"></i>Deny
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="group-info-box">
                        <div class="info-box">
                            <label>User ID</label>
                            <span id="tasker-id">Loading...</span>
                        </div>

                        <div class="info-box">
                            <label>Email</label>
                            <span id="tasker-email">Loading...</span>
                        </div>
                    </div>

                    <div class="group-info-box">
                        <div class="info-box">
                            <label>Name</label>
                            <span id="tasker-name">Loading...</span>
                        </div>

                        <div class="info-box">
                            <label>Contact Number</label>
                            <span id="tasker-contact-number">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('scripts')

@endpush