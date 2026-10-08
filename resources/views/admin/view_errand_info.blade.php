@extends('layouts.app')

@section('title', 'View Errand')

@section('sidebar')
    @include('layouts.sidebar.admin')
@endsection

@section('content')
    <a href="{{ route(request('from', 'admin.dashboard')) }}" class="btn-back">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>

    <div class="print-button-container">
        <button type="button" id="printErrandBtn" class="btn-print">
            <i class="fa-solid fa-print"></i> Print
        </button>
    </div>

    <main class="view-main" data-errand-id="{{ $id }}">
        <div class="info-container">
            <div class="errand-details-container">

                <div class="errand-info-header">
                    <h1><i class="fa-solid fa-circle-info"></i> Errand Info</h1>
                </div>

                <div class="errand-card-top">
                    <span class="errand-id-badge">ID: <span id="errand-id">Loading...</span></span>
                    <span id="errand-status">Loading...</span>
                </div>

                <h2 class="errand-title" id="errand-title">Loading...</h2>

                <div class="view-main-error-success-message">
                    @if(session('success'))
                        <p class="success" id="sessionSuccessMessage">{{ session('success') }}</p>
                    @endif
                    @if(session('error'))
                        <p class="error" id="sessionErrorMessage">{{ session('error') }}</p>
                    @endif
                    <p class="success" id="successMessageText" style="display:none;"></p>
                    <p class="error" id="errorMessageText" style="display:none;"></p>
                </div>

                <div class="errand-meta-list">
                    <div class="detail-row">
                        <span><i class="fa-solid fa-folder-open"></i> Category</span>
                        <span id="errand-category" class="errand-val">Loading...</span>
                    </div>
                    <div class="detail-row">
                        <span><i class="fa-solid fa-location-dot"></i> Location</span>
                        <span id="errand-location" class="errand-val">Loading...</span>
                    </div>
                    <div class="detail-row">
                        <span><i class="fa-solid fa-peso-sign"></i> Fee</span>
                        <strong id="errand-fee" class="errand-fee">Loading...</strong>
                    </div>
                    <div class="detail-row">
                        <span><i class="fa-solid fa-credit-card"></i> Payment Method</span>
                        <span id="payment-method" class="errand-val">Loading...</span>
                    </div>
                    <div class="detail-row">
                        <span><i class="fa-solid fa-wallet"></i> Payment Status</span>
                        <span id="payment-status" class="status-field">Loading...</span>
                    </div>
                    <div class="detail-row">
                        <span><i class="fa-solid fa-calendar-check"></i> Paid At</span>
                        <span id="payment-paid-at" class="errand-val">Loading...</span>
                    </div>
                    <div class="detail-row">
                        <span><i class="fa-solid fa-calendar-plus"></i> Created At</span>
                        <span id="errand-created-at" class="errand-val">Loading...</span>
                    </div>
                    <div class="detail-row">
                        <span><i class="fa-solid fa-clock"></i> Deadline</span>
                        <span id="errand-deadline" class="errand-val">Loading...</span>
                    </div>
                    <div class="detail-row">
                        <span><i class="fa-solid fa-rotate"></i> Last Updated</span>
                        <span id="errand-updated-at" class="errand-val">Loading...</span>
                    </div>
                    <div class="detail-row">
                        <span><i class="fa-solid fa-user-check"></i> Accepted At</span>
                        <span id="errand-accepted-at" class="errand-val">Loading...</span>
                    </div>
                    <div class="detail-row">
                        <span><i class="fa-solid fa-person-running"></i> In Progress At</span>
                        <span id="errand-in-progress-at" class="errand-val">Loading...</span>
                    </div>
                    <div class="detail-row">
                        <span><i class="fa-solid fa-flag-checkered"></i> Finished At</span>
                        <span id="errand-finished-at" class="errand-val">Loading...</span>
                    </div>
                </div>

                <div class="errand-text-sections">
                    <div class="text-section-box">
                        <label><i class="fa-solid fa-align-left"></i> Description</label>
                        <div id="errand-description" class="text-area">Loading...</div>
                    </div>
                    <div class="text-section-box">
                        <label><i class="fa-solid fa-address-book"></i> Contact</label>
                        <div id="errand-contact" class="text-area">Loading...</div>
                    </div>
                </div>

                <div class="errand-attachment-section">
                    <label><i class="fa-solid fa-paperclip"></i> Attachment</label>
                    <div id="errand-attachment">
                        <div class="attachment-item" style="display:none;">
                            <a href="#" target="_blank" class="attachment-name" title="Preview File">
                                <i class="fa-solid fa-file"></i>
                                <span class="attachment-filename"></span>
                            </a>
                            <a href="#" class="btn-download" download>
                                <i class="fa-solid fa-download"></i> Download
                            </a>
                        </div>
                        <div class="no-attachments">
                            <p class="no-attachment">None</p>
                        </div>
                    </div>
                </div>

                <div class="users-container" id="users-container">
                    <div class="user-details-container">
                        <h2>Posted By</h2>
                        <div class="user-main-content">
                            <div class="user-profile-card-layout">
                                <div class="profile-photo-section">
                                    <div class="photo-wrapper">
                                        <img id="client-profile-photo" class="profile-preview-photo" src="" alt="Profile Picture" style="display:none;">
                                        <div id="client-profile-photo-spinner" class="circle-spinner"></div>
                                    </div>
                                </div>
                                <div class="user-info-rows">
                                    <div class="detail-row"><span>User ID</span> <span id="client-id" class="errand-val">Loading...</span></div>
                                    <div class="detail-row"><span>Email</span> <span id="client-email" class="errand-val">Loading...</span></div>
                                    <div class="detail-row"><span>Name</span> <span id="client-name" class="errand-val">Loading...</span></div>
                                    <div class="detail-row"><span>Contact Number</span> <span id="client-contact-number" class="errand-val">Loading...</span></div>
                                </div>
                            </div>
                            <div class="user-cancellation-alert-box" id="client-cancellation-box" style="display:none;">
                                <p class="alert-title">
                                    <i class="fa-solid fa-triangle-exclamation"></i> Requesting Cancellation
                                </p>
                                <div class="alert-action-buttons">
                                    <button type="button" class="btn-approve-release" data-requester="client">
                                        <i class="fa-solid fa-user-minus"></i> Approve
                                    </button>
                                    <button type="button" class="btn-deny-release" data-requester="client">
                                        <i class="fa-solid fa-user-check"></i> Deny
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="user-details-container" id="tasker-container" style="display:none;">
                        <h2>Accepted By</h2>
                        <div class="user-main-content">
                            <div class="user-profile-card-layout">
                                <div class="profile-photo-section">
                                    <div class="photo-wrapper">
                                        <img id="tasker-profile-photo" class="profile-preview-photo" src="" alt="Profile Picture" style="display:none;">
                                        <div id="tasker-profile-photo-spinner" class="circle-spinner"></div>
                                    </div>
                                </div>
                                <div class="user-info-rows">
                                    <div class="detail-row"><span>User ID</span> <span id="tasker-id" class="errand-val">Loading...</span></div>
                                    <div class="detail-row"><span>Email</span> <span id="tasker-email" class="errand-val">Loading...</span></div>
                                    <div class="detail-row"><span>Name</span> <span id="tasker-name" class="errand-val">Loading...</span></div>
                                    <div class="detail-row"><span>Contact Number</span> <span id="tasker-contact-number" class="errand-val">Loading...</span></div>
                                </div>
                            </div>
                            <div class="user-cancellation-alert-box" id="tasker-cancellation-box" style="display:none;">
                                <p class="alert-title">
                                    <i class="fa-solid fa-triangle-exclamation"></i> Requesting Cancellation
                                </p>
                                <div class="alert-action-buttons">
                                    <button type="button" class="btn-approve-release" data-requester="tasker">
                                        <i class="fa-solid fa-user-minus"></i> Approve
                                    </button>
                                    <button type="button" class="btn-deny-release" data-requester="tasker">
                                        <i class="fa-solid fa-user-check"></i> Deny
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="gcash-payment-container" id="paymentProofSection" style="display:none;">
                    <h1>
                        <i class="fa-solid fa-receipt"></i>
                        Payment Verification
                    </h1>

                    <div id="gcashPaymentForm" class="payment-verification-panel" style="display:none;">
                        <div class="payment-status-header">
                            <i class="fa-solid fa-receipt status-icon-orange"></i>
                            <h2 class="status-title-orange">Payment Receipt Submitted</h2>
                        </div>

                        <p class="payment-status-message">
                            <strong>This errand is currently pending, but a payment receipt has been submitted.</strong>
                            Please review the uploaded payment receipt before taking further action.
                        </p>

                        <div class="errand-attachment-section">
                            <label>
                                <i class="fa-solid fa-file-invoice-dollar"></i>
                                Uploaded Payment Receipt
                            </label>

                            <div id="pendingPaymentProofAttachment">
                                <div class="attachment-item">
                                    <a href="#" target="_blank" class="attachment-name" id="pendingPaymentProofPreview" title="Preview Payment Receipt">
                                        <i class="fa-solid fa-file"></i>
                                        <span id="pendingPaymentProofFilename"></span>
                                    </a>

                                    <a href="#" class="btn-download" id="pendingPaymentProofDownload" download>
                                        <i class="fa-solid fa-download"></i>
                                        Download
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="paymentVerificationPanel" class="payment-verification-panel" style="display:none;">
                        <div class="payment-status-header">
                            <i class="fa-solid fa-receipt status-icon-orange"></i>
                            <h2 class="status-title-orange">Payment Receipt Submitted</h2>
                        </div>

                        <p class="payment-status-message">
                            <strong>Please review the uploaded payment receipt.</strong>
                            Accept the receipt if the payment details are valid or deny it if the receipt is invalid.
                        </p>

                        <div class="errand-attachment-section">
                            <label>
                                <i class="fa-solid fa-file-invoice-dollar"></i>
                                Uploaded Payment Receipt
                            </label>

                            <div id="paymentProofAttachment">
                                <div class="attachment-item">
                                    <a href="#" target="_blank" class="attachment-name" id="paymentProofPreview" title="Preview Payment Receipt">
                                        <i class="fa-solid fa-file"></i>
                                        <span id="paymentProofFilename"></span>
                                    </a>

                                    <a href="#" class="btn-download" id="paymentProofDownload" download>
                                        <i class="fa-solid fa-download"></i>
                                        Download
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="refundProofPanel" class="payment-verification-panel" style="display:none;">
                        <div class="payment-status-header">
                            <i class="fa-solid fa-money-bill-transfer status-icon-orange"></i>
                            <h2 class="status-title-orange">Refund Required</h2>
                        </div>

                        <p class="payment-status-message">
                            <strong>If this errand cannot be resolved, you may mark it as failed.</strong>
                            Since the errand has already been paid, refund the paid amount to the client and upload the refund receipt below before marking the errand as failed.
                        </p>

                        <div class="info-box create-errand-input-box payment-right-upload">
                            <label>Upload Refund Receipt</label>
                            <input type="file" id="refundProof" accept=".jpg,.jpeg,.png">
                            <small>Only PNG, JPG, or JPEG allowed. Max: 10 MB</small>
                        </div>

                        <button type="button" id="uploadRefundProofBtn" class="btn-submit-proof">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            Submit Refund Receipt
                            <div class="circle-spinner" id="refundProofSpinner" style="display:none;"></div>
                        </button>
                    </div>

                    <div id="uploadedRefundProofPanel" class="payment-verification-panel" style="display:none;">
                        <div class="payment-status-header">
                            <i class="fa-solid fa-circle-check status-icon-orange"></i>
                            <h2 class="status-title-orange">Refund Receipt Uploaded</h2>
                        </div>

                        <p class="payment-status-message">
                            <strong>The refund receipt has been uploaded successfully.</strong>
                            Review the receipt below before marking this errand as failed.
                        </p>

                        <div class="errand-attachment-section">
                            <label>
                                <i class="fa-solid fa-file-invoice-dollar"></i>
                                Uploaded Refund Receipt
                            </label>

                            <div id="uploadedRefundProofAttachment">
                                <div class="attachment-item">
                                    <a href="#" target="_blank" class="attachment-name" id="uploadedRefundProofPreview" title="Preview Refund Receipt">
                                        <i class="fa-solid fa-file"></i>
                                        <span id="uploadedRefundProofFilename"></span>
                                    </a>

                                    <a href="#" class="btn-download" id="uploadedRefundProofDownload" download>
                                        <i class="fa-solid fa-download"></i>
                                        Download
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="completedPaymentProofPanel" class="payment-verification-panel" style="display:none;">
                        <div class="payment-status-header">
                            <i class="fa-solid fa-circle-check status-icon-orange"></i>
                            <h2 class="status-title-orange">Payment Receipt</h2>
                        </div>

                        <p class="payment-status-message">
                            <strong>This errand has been completed.</strong>
                            The original payment receipt has been preserved in the errand history.
                        </p>

                        <div class="errand-attachment-section">
                            <label>
                                <i class="fa-solid fa-file-invoice-dollar"></i>
                                Original Payment Receipt
                            </label>

                            <div id="completedPaymentProofAttachment">
                                <div class="attachment-item">
                                    <a href="#" target="_blank" class="attachment-name" id="completedPaymentProofPreview" title="Preview Payment Receipt">
                                        <i class="fa-solid fa-file"></i>
                                        <span id="completedPaymentProofFilename"></span>
                                    </a>

                                    <a href="#" class="btn-download" id="completedPaymentProofDownload" download>
                                        <i class="fa-solid fa-download"></i>
                                        Download
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="failedRefundProofPanel" class="payment-verification-panel" style="display:none;">
                        <div class="payment-status-header">
                            <i class="fa-solid fa-money-bill-transfer status-icon-orange"></i>
                            <h2 class="status-title-orange">Payment & Refund Receipts</h2>
                        </div>

                        <p class="payment-status-message">
                            <strong>This errand has been marked as failed and refunded.</strong>
                            The original payment receipt and refund receipt have been preserved in the errand history.
                        </p>

                        <div class="errand-attachment-section">
                            <label>
                                <i class="fa-solid fa-file-invoice-dollar"></i>
                                Original Payment Receipt
                            </label>

                            <div id="failedPaymentProofAttachment">
                                <div class="attachment-item">
                                    <a href="#" target="_blank" class="attachment-name" id="failedPaymentProofPreview" title="Preview Payment Receipt">
                                        <i class="fa-solid fa-file"></i>
                                        <span id="failedPaymentProofFilename"></span>
                                    </a>

                                    <a href="#" class="btn-download" id="failedPaymentProofDownload" download>
                                        <i class="fa-solid fa-download"></i>
                                        Download
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="errand-attachment-section">
                            <label>
                                <i class="fa-solid fa-file-invoice-dollar"></i>
                                Refund Receipt
                            </label>

                            <div id="failedRefundProofAttachment">
                                <div class="attachment-item">
                                    <a href="#" target="_blank" class="attachment-name" id="failedRefundProofPreview" title="Preview Refund Receipt">
                                        <i class="fa-solid fa-file"></i>
                                        <span id="failedRefundProofFilename"></span>
                                    </a>

                                    <a href="#" class="btn-download" id="failedRefundProofDownload" download>
                                        <i class="fa-solid fa-download"></i>
                                        Download
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="info-bot-btn">
                    <button type="button" id="acceptPaymentProofBtn" class="btn-approve" style="display:none;">
                        <i class="fa-solid fa-circle-check"></i> Accept Receipt
                    </button>
                    <button type="button" id="denyPaymentProofBtn" class="btn-delete" style="display:none;">
                        <i class="fa-solid fa-circle-xmark"></i> Deny Receipt
                    </button>
                    <button type="button" id="deleteErrandBtn" class="btn-delete" style="display:none;">
                        <i class="fa-solid fa-trash"></i> Delete Errand
                    </button>
                    <div class="badge-button-column" id="inProgressBadgeContainer" style="display:none;">
                        <span id="performingBadge" class="descriptive-status-badge" style="display:none;">
                            <i class="fa-solid fa-spinner fa-spin"></i> Tasker is currently working on this errand...
                        </span>
                    </div>
                    <button type="button" id="confirmCompletionBtn" class="btn-confirm-completion" style="display:none;">
                        <i class="fa-solid fa-check"></i> Confirm Completion
                    </button>
                    <button type="button" id="completeDisputeBtn" class="btn-approve" style="display:none;">
                        <i class="fa-solid fa-circle-check"></i> Mark as Completed
                    </button>
                    <button type="button" id="failRefundBtn" class="btn-delete" style="display:none;">
                        <i class="fa-solid fa-money-bill-transfer"></i> Mark as Failed & Refund
                    </button>
                    <button type="button" id="cancelBtn" class="btn-cancel" style="display:none;">
                        <i class="fa-solid fa-triangle-exclamation"></i> Force Cancel
                    </button>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', async function () {
            const mainContainer = document.querySelector('.view-main');
            const errandId = mainContainer ? mainContainer.getAttribute('data-errand-id') : null;
            const errorMessageText = document.getElementById('errorMessageText');
            
            function displayGlobalError(message){
                if(!errorMessageText) return;
                errorMessageText.textContent = message;
                errorMessageText.style.display = 'flex';
                setTimeout(() => { 
                    errorMessageText.style.display = 'none'; 
                }, 5000);
            }

            const printErrandBtn = document.getElementById('printErrandBtn');

            if (printErrandBtn) {
                printErrandBtn.addEventListener('click', function () {
                    window.print();
                });
            }

            try {
                const response = await fetch('/api/admin/errands/' + errandId, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-API-KEY': apiKey,
                        'X-MY-ORIGIN': origin
                    },
                    credentials: 'same-origin'
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Failed to load errand details.');
                }

                const errand = result.errand;

                // Populate text fields
                document.getElementById('errand-id').textContent = errand.id;
                document.getElementById('errand-category').textContent = errand.category;
                document.getElementById('errand-title').textContent = errand.title;
                document.getElementById('errand-description').textContent = errand.description;
                document.getElementById('errand-location').textContent = errand.location;
                document.getElementById('errand-contact').textContent = errand.contact;
                document.getElementById('errand-fee').textContent = errand.fee;

                const statusElement = document.getElementById('errand-status');
                statusElement.textContent = errand.status;
                statusElement.className = errand.status_class;

                document.getElementById('payment-method').textContent = errand.payment.method;

                const paymentStatusElement = document.getElementById('payment-status');
                paymentStatusElement.textContent = errand.payment.status;
                paymentStatusElement.className = errand.payment.status_class;

                document.getElementById('payment-paid-at').textContent = errand.payment.paid_at;
                document.getElementById('errand-created-at').textContent = errand.created_at;
                document.getElementById('errand-updated-at').textContent = errand.updated_at;
                document.getElementById('errand-accepted-at').textContent = errand.accepted_at;
                document.getElementById('errand-in-progress-at').textContent = errand.in_progress_at;
                document.getElementById('errand-deadline').textContent = errand.deadline;
                document.getElementById('errand-finished-at').textContent = errand.finished_at;

                const usersContainer = document.getElementById('users-container');
                const taskerContainer = document.getElementById('tasker-container');

                usersContainer.style.display = 'flex';

                /* Client */
                const client = errand.client;
                const clientPhoto = document.getElementById('client-profile-photo');
                const clientSpinner = document.getElementById('client-profile-photo-spinner');

                if (client) {
                    document.getElementById('client-id').textContent = client.id;
                    document.getElementById('client-email').textContent = client.email;
                    document.getElementById('client-name').textContent = client.name;
                    document.getElementById('client-contact-number').textContent = client.contact_number;

                    if (client.profile_picture) {
                        clientPhoto.src = client.profile_picture;

                        clientPhoto.onload = function () {
                            clientSpinner.style.display = 'none';
                            clientPhoto.style.display = 'flex';
                        };

                        clientPhoto.onerror = function () {
                            clientSpinner.style.display = 'none';
                            clientPhoto.style.display = 'none';
                        };
                    } else {
                        clientSpinner.style.display = 'none';
                    }

                    const clientCancelBox = document.getElementById('client-cancellation-box');

                    if (errand.cancel_requested_by_client) {
                        clientCancelBox.style.display = 'flex';
                    }
                } else {
                    document.getElementById('client-id').textContent = 'None';
                    document.getElementById('client-email').textContent = 'None';
                    document.getElementById('client-name').textContent = 'None';
                    document.getElementById('client-contact-number').textContent = 'None';
                    clientSpinner.style.display = 'none';
                }

                /* Tasker */
                if (errand.tasker) {
                    const tasker = errand.tasker;

                    taskerContainer.style.display = 'flex';

                    document.getElementById('tasker-id').textContent = tasker.id;
                    document.getElementById('tasker-email').textContent = tasker.email;
                    document.getElementById('tasker-name').textContent = tasker.name;
                    document.getElementById('tasker-contact-number').textContent = tasker.contact_number;

                    const taskerPhoto = document.getElementById('tasker-profile-photo');
                    const taskerSpinner = taskerContainer.querySelector('.circle-spinner');

                    if (tasker.profile_picture) {
                        taskerPhoto.src = tasker.profile_picture;

                        taskerPhoto.onload = function () {
                            taskerSpinner.style.display = 'none';
                            taskerPhoto.style.display = 'flex';
                        };

                        taskerPhoto.onerror = function () {
                            taskerSpinner.style.display = 'none';
                            taskerPhoto.style.display = 'none';
                        };
                    } else {
                        taskerSpinner.style.display = 'none';
                    }

                    const taskerCancelBox = document.getElementById('tasker-cancellation-box');

                    if (errand.cancel_requested_by_tasker) {
                        taskerCancelBox.style.display = 'flex';
                    }
                } else {
                    taskerContainer.style.display = 'none';
                }
        
                document.querySelectorAll('.btn-approve-release, .btn-deny-release').forEach(button => {
                    button.addEventListener('click', async function () {
                        const requesterType = this.getAttribute('data-requester');
                        const isApprove = this.classList.contains('btn-approve-release');
                        
                        const actionText = isApprove ? 'approve' : 'deny';
                        const customTitle = (isApprove ? 'Approve' : 'Deny') + ' Cancellation';
                        const customMessage = 'Are you sure you want to ' + actionText + ' the cancellation request from this ' + requesterType + '?';
                        
                        const isConfirmed = await showConfirmation(customTitle, customMessage);
                        if (!isConfirmed) return;

                        const endpoint = isApprove 
                            ? '/api/admin/errands/' + errand.id + '/approve-cancellation' 
                            : '/api/admin/errands/' + errand.id + '/deny-cancellation';

                        try {
                            const response = await fetch(endpoint, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                    'X-API-KEY': apiKey,
                                    'X-MY-ORIGIN': origin
                                },
                                body: JSON.stringify({ requester: requesterType }),
                                credentials: 'same-origin'
                            });

                            const result = await response.json();
                            if (!response.ok || !result.success) {
                                throw new Error(result.message || 'Failed to process cancellation request.');
                            }

                            sessionStorage.setItem('success_message', result.message);
                            window.location.href = window.location.href;

                        } catch (error) {
                            console.error('Error:', error);
                            displayGlobalError(error.message);
                        }
                    });
                });

                // Handle Attachments
                const attachContainer = document.getElementById('errand-attachment');
                const attachItem = attachContainer.querySelector('.attachment-item');
                const noAttachItem = attachContainer.querySelector('.no-attachments');

                if (errand.attachment) {
                    attachItem.style.display = 'flex';
                    noAttachItem.style.display = 'none';
                    attachItem.querySelector('.attachment-filename').textContent = errand.attachment.name;
                    attachItem.querySelector('.attachment-name').href = errand.attachment.url;
                    
                    const downloadBtn = attachItem.querySelector('.btn-download');
                    downloadBtn.href = errand.attachment.url;
                    downloadBtn.setAttribute('download', errand.attachment.name);
                }

                if (errand.raw_status === 'pending') {
                    const deleteBtn = document.getElementById('deleteErrandBtn');
                    const paymentProofSection = document.getElementById('paymentProofSection');
                    const gcashPaymentForm = document.getElementById('gcashPaymentForm');
                    const paymentVerificationPanel = document.getElementById('paymentVerificationPanel');

                    const pendingPaymentProofPreview = document.getElementById('pendingPaymentProofPreview');
                    const pendingPaymentProofDownload = document.getElementById('pendingPaymentProofDownload');
                    const pendingPaymentProofFilename = document.getElementById('pendingPaymentProofFilename');

                    deleteBtn.style.display = 'flex';
                    paymentProofSection.style.display = 'none';
                    gcashPaymentForm.style.display = 'none';
                    paymentVerificationPanel.style.display = 'none';

                    if (errand.payment && errand.payment.proof) {
                        paymentProofSection.style.display = 'flex';
                        gcashPaymentForm.style.display = 'flex';

                        pendingPaymentProofFilename.textContent = errand.payment.proof.name;
                        pendingPaymentProofPreview.href = errand.payment.proof.url;
                        pendingPaymentProofDownload.href = errand.payment.proof.url;
                        pendingPaymentProofDownload.setAttribute('download', errand.payment.proof.name);
                    }

                    deleteBtn.addEventListener('click', async function() {
                        const customTitle = 'Confirm Deletion';
                        const customMessage = 'Are you sure you want to delete errand #' + errand.id + ' ("' + errand.title + '")? It will be deleted permanently.';
                        const isConfirmed = await showConfirmation(customTitle, customMessage);

                        if (!isConfirmed) return;

                        try {
                            const response = await fetch('/api/admin/errands/' + errand.id + '/delete', {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                    'X-API-KEY': apiKey,
                                    'X-MY-ORIGIN': origin
                                },
                                credentials: 'same-origin'
                            });

                            const result = await response.json();

                            if (!response.ok || !result.success) {
                                throw new Error(result.message || 'Failed to delete errand.');
                            }

                            sessionStorage.setItem('success_message', result.message);
                            window.location.href = '{{ route("admin.dashboard") }}';

                        } catch (error) {
                            console.error('Error:', error);
                            displayGlobalError(error.message);
                        }
                    });

                } else if (errand.raw_status === 'accepted') {
                    const paymentProofSection = document.getElementById('paymentProofSection');
                    const gcashPaymentForm = document.getElementById('gcashPaymentForm');
                    const paymentVerificationPanel = document.getElementById('paymentVerificationPanel');

                    const paymentProofPreview = document.getElementById('paymentProofPreview');
                    const paymentProofDownload = document.getElementById('paymentProofDownload');
                    const paymentProofFilename = document.getElementById('paymentProofFilename');

                    const acceptPaymentProofBtn = document.getElementById('acceptPaymentProofBtn');
                    const denyPaymentProofBtn = document.getElementById('denyPaymentProofBtn');

                    paymentProofSection.style.display = 'none';
                    gcashPaymentForm.style.display = 'none';
                    paymentVerificationPanel.style.display = 'none';

                    acceptPaymentProofBtn.style.display = 'none';
                    denyPaymentProofBtn.style.display = 'none';

                    if (
                        errand.payment &&
                        errand.payment.method &&
                        errand.payment.method.toLowerCase() === 'gcash' &&
                        errand.payment.proof
                    ) {
                        paymentProofSection.style.display = 'flex';
                        paymentVerificationPanel.style.display = 'flex';

                        acceptPaymentProofBtn.style.display = 'flex';
                        denyPaymentProofBtn.style.display = 'flex';

                        paymentProofFilename.textContent = errand.payment.proof.name;
                        paymentProofPreview.href = errand.payment.proof.url;
                        paymentProofDownload.href = errand.payment.proof.url;
                        paymentProofDownload.setAttribute('download', errand.payment.proof.name);
                    }

                    acceptPaymentProofBtn.addEventListener('click', async function() {
                        const isConfirmed = await showConfirmation(
                            'Accept Payment Receipt',
                            'Are you sure you want to accept this payment receipt? The payment will be marked as paid and the errand will move to "In Progress".'
                        );

                        if (!isConfirmed) return;

                        try {
                            const response = await fetch('/api/admin/errands/' + errand.id + '/accept-receipt', {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                    'X-API-KEY': apiKey,
                                    'X-MY-ORIGIN': origin
                                },
                                credentials: 'same-origin'
                            });

                            const result = await response.json();

                            if (!response.ok || !result.success) {
                                throw new Error(result.message || 'Failed to accept payment receipt.');
                            }

                            sessionStorage.setItem('success_message', result.message);
                            window.location.href = window.location.href;

                        } catch (error) {
                            console.error('Error:', error);
                            displayGlobalError(error.message);
                        }
                    });

                    denyPaymentProofBtn.addEventListener('click', async function() {
                        const isConfirmed = await showConfirmation(
                            'Deny Payment Receipt',
                            'Are you sure you want to deny this payment receipt? The client will need to upload a new payment receipt.'
                        );

                        if (!isConfirmed) return;

                        try {
                            const response = await fetch('/api/admin/errands/' + errand.id + '/deny-receipt', {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                    'X-API-KEY': apiKey,
                                    'X-MY-ORIGIN': origin
                                },
                                credentials: 'same-origin'
                            });

                            const result = await response.json();

                            if (!response.ok || !result.success) {
                                throw new Error(result.message || 'Failed to deny payment receipt.');
                            }

                            sessionStorage.setItem('success_message', result.message);
                            window.location.href = window.location.href;

                        } catch (error) {
                            console.error('Error:', error);
                            displayGlobalError(error.message);
                        }
                    });

                    const cancelBtn = document.getElementById('cancelBtn');
                    cancelBtn.style.display = 'flex';
                    cancelBtn.addEventListener('click', async function(){
                        const customTitle = 'Confirm Cancellation';
                        const customMessage = 'Force-cancel this errand? The errand status will be reset back to "Pending" status.';
                        const isConfirmed = await showConfirmation(customTitle, customMessage);
                        
                        if(!isConfirmed) return;
                        
                        try {
                            const response = await fetch('/api/admin/errands/' + errand.id + '/reset-to-pending', {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                    'X-API-KEY': apiKey,
                                    'X-MY-ORIGIN' : origin
                                },
                                credentials: 'same-origin'
                            });

                            const result = await response.json();
                            if(!response.ok || !result.success){
                                throw new Error(result.message);
                            }

                            sessionStorage.setItem('success_message', result.message);
                            window.location.href = window.location.href;

                        } catch(error) {
                            console.error('Error:', error);
                            displayGlobalError(error.message);
                        }
                    });

                } else if (errand.raw_status === 'in_progress') {
                    const inProgressBadgeContainer = document.getElementById('inProgressBadgeContainer');
                    inProgressBadgeContainer.style.display = 'flex';
                    document.getElementById('performingBadge').style.display = 'flex';
                    cancelBtn.style.display = 'flex';
                    cancelBtn.addEventListener('click', async function(){
                        const customTitle = 'Confirm Cancellation';
                        const customMessage = 'Force-cancel this errand? The errand status will be reset back to "Pending" status.';
                        const isConfirmed = await showConfirmation(customTitle, customMessage);
                        
                        if(!isConfirmed) return;
                        
                        try {
                            const response = await fetch('/api/admin/errands/' + errand.id + '/reset-to-pending', {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                    'X-API-KEY': apiKey,
                                    'X-MY-ORIGIN' : origin
                                },
                                credentials: 'same-origin'
                            });

                            const result = await response.json();
                            if(!response.ok || !result.success){
                                throw new Error(result.message);
                            }

                            sessionStorage.setItem('success_message', result.message);
                            window.location.href = window.location.href;

                        } catch(error) {
                            console.error('Error:', error);
                            displayGlobalError(error.message);
                        }
                    });
                } else if (errand.raw_status === 'awaiting_confirmation') {
                    const confirmCompletionBtn = document.getElementById('confirmCompletionBtn');
                    confirmCompletionBtn.style.display = 'flex';

                    confirmCompletionBtn.addEventListener('click', async function() {
                        const customTitle = 'Confirm Completion';
                        const customMessage = 'Are you sure you want to confirm the completion of errand #' + errand.id + ' ("' + errand.title + '")? This action cannot be undone.';
                        const isConfirmed = await showConfirmation(customTitle, customMessage);
                        
                        if(!isConfirmed) return;
                        
                        try {
                            const response = await fetch('/api/admin/errands/' + errand.id + '/completed', {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                    'X-API-KEY': apiKey,
                                    'X-MY-ORIGIN' : origin
                                },
                                credentials: 'same-origin'
                            });

                            const result = await response.json();
                            if(!response.ok || !result.success){
                                throw new Error(result.message);
                            }

                            sessionStorage.setItem('success_message', result.message);
                            window.location.href = '{{ route("admin.history") }}';

                        } catch(error) {
                            console.error('Error:', error);
                            displayGlobalError(error.message);
                        }
                    });

                    cancelBtn.style.display = 'flex';
                    cancelBtn.addEventListener('click', async function(){
                        const customTitle = 'Confirm Cancellation';
                        const customMessage = 'Force-cancel this errand? The errand status will be reset back to "Pending" status.';
                        const isConfirmed = await showConfirmation(customTitle, customMessage);
                        
                        if(!isConfirmed) return;
                        
                        try {
                            const response = await fetch('/api/admin/errands/' + errand.id + '/reset-to-pending', {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                    'X-API-KEY': apiKey,
                                    'X-MY-ORIGIN' : origin
                                },
                                credentials: 'same-origin'
                            });

                            const result = await response.json();
                            if(!response.ok || !result.success){
                                throw new Error(result.message);
                            }

                            sessionStorage.setItem('success_message', result.message);
                            window.location.href = window.location.href;

                        } catch(error) {
                            console.error('Error:', error);
                            displayGlobalError(error.message);
                        }
                    });

                } else if (errand.raw_status === 'dispute') {
                    const paymentProofSection = document.getElementById('paymentProofSection');
                    const refundProofPanel = document.getElementById('refundProofPanel');
                    const uploadedRefundProofPanel = document.getElementById('uploadedRefundProofPanel');

                    const uploadedRefundProofPreview = document.getElementById('uploadedRefundProofPreview');
                    const uploadedRefundProofDownload = document.getElementById('uploadedRefundProofDownload');
                    const uploadedRefundProofFilename = document.getElementById('uploadedRefundProofFilename');

                    const completeDisputeBtn = document.getElementById('completeDisputeBtn');
                    const failRefundBtn = document.getElementById('failRefundBtn');

                    const refundProof = document.getElementById('refundProof');
                    const uploadRefundProofBtn = document.getElementById('uploadRefundProofBtn');
                    const refundProofSpinner = document.getElementById('refundProofSpinner');

                    paymentProofSection.style.display = 'flex';
                    refundProofPanel.style.display = 'none';
                    uploadedRefundProofPanel.style.display = 'none';

                    completeDisputeBtn.style.display = 'flex';
                    failRefundBtn.style.display = 'none';

                    if (errand.payment && errand.payment.refund_proof) {
                        refundProofPanel.style.display = 'none';
                        uploadedRefundProofPanel.style.display = 'flex';

                        uploadedRefundProofFilename.textContent = errand.payment.refund_proof.name;
                        uploadedRefundProofPreview.href = errand.payment.refund_proof.url;
                        uploadedRefundProofDownload.href = errand.payment.refund_proof.url;
                        uploadedRefundProofDownload.setAttribute('download', errand.payment.refund_proof.name);

                        uploadRefundProofBtn.style.display = 'none';
                        failRefundBtn.style.display = 'flex';
                    } else {
                        refundProofPanel.style.display = 'flex';
                        uploadedRefundProofPanel.style.display = 'none';

                        uploadRefundProofBtn.style.display = 'flex';
                        failRefundBtn.style.display = 'none';
                    }

                    uploadRefundProofBtn.addEventListener('click', async function() {
                        if (!refundProof.files.length) {
                            displayGlobalError('Please select a refund receipt first.');
                            return;
                        }

                        const formData = new FormData();
                        formData.append('refund_proof', refundProof.files[0]);

                        uploadRefundProofBtn.disabled = true;
                        refundProofSpinner.style.display = 'inline-block';

                        try {
                            const response = await fetch(
                                '/api/admin/errands/' + errand.id + '/refund-proof',
                                {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                        'X-API-KEY': apiKey,
                                        'X-MY-ORIGIN': origin
                                    },
                                    body: formData,
                                    credentials: 'same-origin'
                                }
                            );

                            const result = await response.json();

                            if (!response.ok || !result.success) {
                                throw new Error(result.message || 'Failed to upload refund receipt.');
                            }

                            sessionStorage.setItem('success_message', result.message);
                            window.location.href = window.location.href;

                        } catch (error) {
                            console.error('Error:', error);
                            displayGlobalError(error.message);
                        } finally {
                            uploadRefundProofBtn.disabled = false;
                            refundProofSpinner.style.display = 'none';
                        }
                    });

                    failRefundBtn.addEventListener('click', async function() {
                        const customTitle = 'Confirm Failure & Refund';
                        const customMessage = 'Mark this disputed errand as "Failed" and confirm that the paid amount has been refunded to the Client? This action cannot be undone.';

                        const isConfirmed = await showConfirmation(
                            customTitle,
                            customMessage
                        );

                        if (!isConfirmed) return;

                        try {
                            const response = await fetch(
                                '/api/admin/errands/' + errand.id + '/failed',
                                {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'application/json',
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                        'X-API-KEY': apiKey,
                                        'X-MY-ORIGIN': origin
                                    },
                                    body: JSON.stringify({
                                        refund: true
                                    }),
                                    credentials: 'same-origin'
                                }
                            );

                            const result = await response.json();

                            if (!response.ok || !result.success) {
                                throw new Error(result.message || 'Failed to mark errand as failed.');
                            }

                            sessionStorage.setItem('success_message', result.message);
                            window.location.href = '{{ route("admin.history") }}';

                        } catch (error) {
                            console.error('Error:', error);
                            displayGlobalError(error.message);
                        }
                    });

                    completeDisputeBtn.addEventListener('click', async function() {
                        const customTitle = 'Confirm Completion';
                        const customMessage = 'Mark this disputed errand as "Completed"? This will resolve the dispute and finish the errand. This action cannot be undone.';

                        const isConfirmed = await showConfirmation(
                            customTitle,
                            customMessage
                        );

                        if (!isConfirmed) return;

                        try {
                            const response = await fetch(
                                '/api/admin/errands/' + errand.id + '/completed',
                                {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                        'X-API-KEY': apiKey,
                                        'X-MY-ORIGIN': origin
                                    },
                                    credentials: 'same-origin'
                                }
                            );

                            const result = await response.json();

                            if (!response.ok || !result.success) {
                                throw new Error(result.message || 'Failed to complete errand.');
                            }

                            sessionStorage.setItem('success_message', result.message);
                            window.location.href = '{{ route("admin.history") }}';

                        } catch (error) {
                            console.error('Error:', error);
                            displayGlobalError(error.message);
                        }
                    });

                } else if (errand.raw_status === 'completed') {
                    const paymentProofSection = document.getElementById('paymentProofSection');
                    const completedPaymentProofPanel = document.getElementById('completedPaymentProofPanel');
                    const deleteBtn = document.getElementById('deleteErrandBtn');

                    const completedPaymentProofPreview = document.getElementById('completedPaymentProofPreview');
                    const completedPaymentProofDownload = document.getElementById('completedPaymentProofDownload');
                    const completedPaymentProofFilename = document.getElementById('completedPaymentProofFilename');

                    paymentProofSection.style.display = 'none';
                    completedPaymentProofPanel.style.display = 'none';

                    deleteBtn.style.display = 'flex';

                    if (errand.payment && errand.payment.proof) {
                        paymentProofSection.style.display = 'flex';
                        completedPaymentProofPanel.style.display = 'flex';

                        completedPaymentProofFilename.textContent = errand.payment.proof.name;
                        completedPaymentProofPreview.href = errand.payment.proof.url;
                        completedPaymentProofDownload.href = errand.payment.proof.url;
                        completedPaymentProofDownload.setAttribute('download', errand.payment.proof.name);
                    }

                    deleteBtn.addEventListener('click', async function() {
                        const customTitle = 'Confirm Deletion';
                        const customMessage = 'Are you sure you want to delete errand #' + errand.id + ' ("' + errand.title + '")? It will be deleted permanently.';
                        const isConfirmed = await showConfirmation(customTitle, customMessage);

                        if (!isConfirmed) return;

                        try {
                            const response = await fetch(
                                '/api/admin/history/' + errand.id + '/delete',
                                {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                        'X-API-KEY': apiKey,
                                        'X-MY-ORIGIN': origin
                                    },
                                    credentials: 'same-origin'
                                }
                            );

                            const result = await response.json();

                            if (!response.ok || !result.success) {
                                throw new Error(result.message);
                            }

                            sessionStorage.setItem('success_message', result.message);
                            window.location.href = '{{ route("admin.history") }}';

                        } catch (error) {
                            console.error('Error:', error);
                            displayGlobalError(error.message);
                        }
                    });

                } else if (errand.raw_status === 'failed') {
                    const paymentProofSection = document.getElementById('paymentProofSection');
                    const completedPaymentProofPanel = document.getElementById('completedPaymentProofPanel');
                    const failedRefundProofPanel = document.getElementById('failedRefundProofPanel');
                    const refundProofPanel = document.getElementById('refundProofPanel');
                    const uploadedRefundProofPanel = document.getElementById('uploadedRefundProofPanel');

                    const deleteBtn = document.getElementById('deleteErrandBtn');

                    const failedPaymentProofPreview = document.getElementById('failedPaymentProofPreview');
                    const failedPaymentProofDownload = document.getElementById('failedPaymentProofDownload');
                    const failedPaymentProofFilename = document.getElementById('failedPaymentProofFilename');

                    const failedRefundProofPreview = document.getElementById('failedRefundProofPreview');
                    const failedRefundProofDownload = document.getElementById('failedRefundProofDownload');
                    const failedRefundProofFilename = document.getElementById('failedRefundProofFilename');

                    paymentProofSection.style.display = 'none';
                    completedPaymentProofPanel.style.display = 'none';
                    failedRefundProofPanel.style.display = 'none';
                    refundProofPanel.style.display = 'none';
                    uploadedRefundProofPanel.style.display = 'none';

                    deleteBtn.style.display = 'flex';

                    if (errand.payment) {
                        const paymentProof = errand.payment.proof;
                        const refundProof = errand.payment.refund_proof;

                        if (paymentProof || refundProof) {
                            paymentProofSection.style.display = 'flex';
                            failedRefundProofPanel.style.display = 'flex';
                        }

                        if (paymentProof) {
                            failedPaymentProofFilename.textContent = paymentProof.name;
                            failedPaymentProofPreview.href = paymentProof.url;
                            failedPaymentProofDownload.href = paymentProof.url;
                            failedPaymentProofDownload.setAttribute('download', paymentProof.name);
                        }

                        if (refundProof) {
                            failedRefundProofFilename.textContent = refundProof.name;
                            failedRefundProofPreview.href = refundProof.url;
                            failedRefundProofDownload.href = refundProof.url;
                            failedRefundProofDownload.setAttribute('download', refundProof.name);
                        }
                    }

                    deleteBtn.addEventListener('click', async function() {
                        const customTitle = 'Confirm Deletion';
                        const customMessage = 'Are you sure you want to delete errand #' + errand.id + ' ("' + errand.title + '")? It will be deleted permanently.';
                        const isConfirmed = await showConfirmation(customTitle, customMessage);

                        if (!isConfirmed) return;

                        try {
                            const response = await fetch(
                                '/api/admin/history/' + errand.id + '/delete',
                                {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                        'X-API-KEY': apiKey,
                                        'X-MY-ORIGIN': origin
                                    },
                                    credentials: 'same-origin'
                                }
                            );

                            const result = await response.json();

                            if (!response.ok || !result.success) {
                                throw new Error(result.message || 'Failed to delete errand.');
                            }

                            sessionStorage.setItem('success_message', result.message);
                            window.location.href = '{{ route("admin.history") }}';

                        } catch (error) {
                            console.error('Error:', error);
                            displayGlobalError(error.message);
                        }
                    });
                }

            } catch (error) {
                console.error('Error:', error);
                displayGlobalError(error.message);
            }
        });
    </script>
@endpush