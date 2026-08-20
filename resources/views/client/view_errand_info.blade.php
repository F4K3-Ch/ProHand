@extends('layouts.app')

@section('title', 'Client View Errand')

@section('sidebar')
    @include('layouts.sidebar.client')
@endsection

@section('content')
    <a href="{{ route('client.dashboard') }}" class="btn-back">
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

                    <div class="info-box" id="payment-proof-box" style="display: none;">
                        <label>Proof Of Payment</label>
                        <div id="errand-payment-proof">
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
                    <a href="#" id="edit-errand-btn" class="btn-edit-errand" style="display: none;">
                        <i class="fa-solid fa-pen"></i>
                        Edit Errand
                    </a>
                    <button type="button" id="delete-errand-btn" class="btn-delete" style="display: none;">
                        <i class="fa-solid fa-trash"></i>
                        Delete Errand
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
            
            const errorMessage = document.getElementById('errorMessageText');

            if (!errandId) {
                errorMessage.textContent = 'Errand ID not found.';
                errorMessage.style.display = 'block';
                return;
            }

            try {
                const response = await fetch('/api/client/errands/' + encodeURIComponent(errandId), {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-API-KEY': apiKey,
                        'X-MY-ORIGIN' : origin
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
                document.getElementById('errand-description').textContent = errand.description || 'None';
                document.getElementById('errand-location').textContent = errand.location;
                document.getElementById('errand-contact').textContent = errand.contact || 'None';
                document.getElementById('errand-fee').textContent = errand.fee;
                
                const statusElement = document.getElementById('errand-status');
                statusElement.textContent = errand.status;
                statusElement.className = errand.status_class;

                document.getElementById('errand-created-at').textContent = errand.created_at;
                document.getElementById('errand-updated-at').textContent = errand.updated_at;
                document.getElementById('errand-accepted-at').textContent = errand.accepted_at;
                document.getElementById('errand-in-process-at').textContent = errand.in_process_at;
                document.getElementById('errand-deadline').textContent = errand.deadline;
                document.getElementById('errand-finished-at').textContent = errand.finished_at;

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

                // Handle Payment Proof
                const paymentBox = document.getElementById('payment-proof-box');
                if (errand.payment_proof) {
                    paymentBox.style.display = 'block';
                    const paymentContainer = document.getElementById('errand-payment-proof');
                    const paymentItem = paymentContainer.querySelector('.attachment-item');
                    const noPaymentItem = paymentContainer.querySelector('.no-attachments');
                    
                    paymentItem.style.display = 'flex';
                    noPaymentItem.style.display = 'none';
                    paymentItem.querySelector('.attachment-filename').textContent = errand.payment_proof.name;
                    paymentItem.querySelector('.attachment-name').href = errand.payment_proof.url;
                    
                    const downloadPaymentBtn = paymentItem.querySelector('.btn-download');
                    downloadPaymentBtn.href = errand.payment_proof.url;
                    downloadPaymentBtn.setAttribute('download', errand.payment_proof.name);
                }

                // Handle Edit and Delete buttons visibility based on status
                const editBtn = document.getElementById('edit-errand-btn');
                const deleteBtn = document.getElementById('delete-errand-btn');

                if (errand.status && errand.status.toLowerCase() === 'pending') {
                    editBtn.href = '/client/edit-errand/' + errand.id;
                    editBtn.style.display = 'inline-flex';
                    deleteBtn.style.display = 'inline-flex';

                    // Delete button event listener
                    deleteBtn.addEventListener('click', async function(){
                            var customMessage = 'Are you sure you want to delete errand #' + errand.id + ' ("' + errand.title + '")?';
                            var isConfirmed = await showDeleteConfirmation(customMessage);
                            
                            if(!isConfirmed){
                                return;
                            }

                        try {
                            const response = await fetch('/api/client/errands/' + errand.id + '/delete', {
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
                                throw new Error(result.message || 'Failed to delete errand.');
                            }

                            sessionStorage.setItem('success_message', result.message);
                            window.location.href = '/client/dashboard';

                        } catch(error) {
                            console.error('Delete error:', error);
                            const errorText = document.getElementById('errorMessageText');
                            errorText.textContent = error.message;
                            errorText.style.display = 'block';
                            setTimeout(() => { errorText.style.display = 'none'; }, 4000);
                        }
                    });

                } else {
                    editBtn.style.display = 'none';
                    deleteBtn.style.display = 'none';
                }

            } catch (error) {
                console.error('Errand details error:', error);
                errorMessage.textContent = error.message || 'Unable to load errand information.';
                errorMessage.style.display = 'block';
            }
        });
    </script>
@endpush