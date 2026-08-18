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
                    <p class="success" id="flashMessageText" style="display: none;"></p>
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
                                <a href="#" target="_blank" class="attachment-name">
                                    <i class="fa-solid fa-file-lines"></i>
                                </a>

                                <a href="#" download class="btn-download">
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
                                <a href="#" target="_blank" class="attachment-name">
                                    <i class="fa-solid fa-file-lines"></i>
                                </a>

                                <a href="#" download class="btn-download">
                                    <i class="fa-solid fa-download"></i>
                                    Download
                                </a>
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
        document.addEventListener('DOMContentLoaded', async function() {
            const token = localStorage.getItem('token');
            const mainElement = document.querySelector('.view-main');
            const errandId = mainElement?.dataset.errandId;

            if (!token || !errandId) return;

            function formatDate(date) {
                if (!date) return 'Not set';

                const value = new Date(date);

                if (isNaN(value.getTime())) return 'Not set';

                return value.toLocaleDateString('en-US', {
                    month: 'long',
                    day: 'numeric',
                    year: 'numeric'
                }) + ' ' + value.toLocaleTimeString('en-US', {
                    hour: 'numeric',
                    minute: '2-digit'
                });
            }

            function formatText(value) {
                if (!value) return 'Not set';

                return value
                    .replace(/_/g, ' ')
                    .replace(/\b\w/g, function(char) {
                        return char.toUpperCase();
                    });
            }

            function setText(id, value) {
                const element = document.getElementById(id);

                if (element) {
                    element.textContent = value;
                }
            }

            function setupAttachment(container, file) {
                const item = container.querySelector('.attachment-item');
                const view = container.querySelector('.attachment-name');
                const download = container.querySelector('.btn-download');
                const none = container.querySelector('.no-attachments');

                if (!item || !view || !download) return;

                const url = "{{ asset('storage') }}/" + file.file_path;

                view.href = url;
                download.href = url;

                view.appendChild(
                    document.createTextNode(' ' + file.original_name)
                );

                item.style.display = 'flex';

                if (none) {
                    none.style.display = 'none';
                }
            }

            async function deleteErrand() {
                const confirmed = confirm(
                    'Are you sure you want to delete this errand?'
                );

                if (!confirmed) return;

                try {
                    const response = await fetch(
                        '/api/client/errands/delete/' + errandId,
                        {
                            method: 'POST',
                            headers: {
                                'Authorization': 'Bearer ' + token,
                                'Accept': 'application/json'
                            }
                        }
                    );

                    const data = await response.json();

                    if (!response.ok) {
                        throw data;
                    }

                    sessionStorage.setItem(
                        'success_message',
                        data.message
                    );

                    window.location.href = "{{ route('client.dashboard') }}";

                } catch (error) {
                    console.error('Failed to delete errand:', error);

                    const flashMessageText =
                        document.getElementById('flashMessageText');

                    if (flashMessageText) {
                        flashMessageText.textContent =
                            error.error ||
                            error.message ||
                            'Failed to delete errand.';

                        flashMessageText.className = 'error';
                        flashMessageText.style.display = 'block';

                        setTimeout(function() {
                            flashMessageText.style.display = 'none';
                        }, 3000);
                    }
                }
            }

            try {
                const response = await fetch(
                    '/api/client/errands/' + errandId,
                    {
                        method: 'GET',
                        headers: {
                            'Authorization': 'Bearer ' + token,
                            'Accept': 'application/json'
                        }
                    }
                );

                const data = await response.json();

                if (!response.ok) {
                    throw data;
                }

                const errand = data.errand;

                if (!errand) {
                    throw new Error('Errand data was not returned.');
                }

                setText('errand-id', errand.id);
                setText('errand-title', errand.title);
                setText('errand-category', formatText(errand.category));
                setText('errand-description', errand.description || 'No description');
                setText('errand-contact', errand.contact || 'No contact information');
                setText('errand-location', errand.location || 'Not set');

                setText(
                    'errand-fee',
                    '₱' + Number(errand.fee || 0).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    })
                );

                setText('errand-created-at', formatDate(errand.created_at));
                setText('errand-deadline', formatDate(errand.deadline));
                setText('errand-accepted-at', formatDate(errand.accepted_at));
                setText('errand-in-process-at', formatDate(errand.in_process_at));
                setText(
                    'errand-finished-at',
                    formatDate(errand.completed_at || errand.failed_at)
                );
                setText('errand-updated-at', formatDate(errand.updated_at));

                const statusElement = document.getElementById('errand-status');

                if (statusElement) {
                    statusElement.textContent = formatText(errand.status);

                    if (errand.status) {
                        statusElement.classList.add('status-' + errand.status);
                    }
                }

                const editErrandButton = document.getElementById('edit-errand-btn');
                const deleteErrandButton = document.getElementById('delete-errand-btn');

                if (deleteErrandButton) {
                    deleteErrandButton.addEventListener('click', deleteErrand);
                }

                if (errand.status === 'pending') {
                    if (editErrandButton) {
                        editErrandButton.href = '/client/edit-errand/' + errand.id;
                        editErrandButton.style.display = 'inline-flex';
                    }

                    if (deleteErrandButton) {
                        deleteErrandButton.style.display = 'inline-flex';
                    }
                }

                const attachmentContainer =
                    document.getElementById('errand-attachment');

                if (errand.attachment) {
                    setupAttachment(
                        attachmentContainer,
                        errand.attachment
                    );
                }

                const paymentProofBox =
                    document.getElementById('payment-proof-box');

                const paymentProofContainer =
                    document.getElementById('errand-payment-proof');

                if (errand.payment_proof) {
                    setupAttachment(
                        paymentProofContainer,
                        errand.payment_proof
                    );

                    paymentProofBox.style.display = 'block';
                }

            } catch (error) {
                console.error('Failed to load errand:', error);

                setText('errand-id', 'Not available');
                setText('errand-title', 'Unable to load errand.');
                setText('errand-category', 'Not available');
                setText('errand-description', 'Not available');
                setText('errand-contact', 'Not available');
                setText('errand-location', 'Not available');
                setText('errand-fee', 'Not available');
                setText('errand-created-at', 'Not available');
                setText('errand-deadline', 'Not available');
                setText('errand-accepted-at', 'Not available');
                setText('errand-in-process-at', 'Not available');
                setText('errand-finished-at', 'Not available');
                setText('errand-updated-at', 'Not available');
                setText('errand-status', 'Not available');

                const attachmentContainer =
                    document.getElementById('errand-attachment');

                const paymentProofBox =
                    document.getElementById('payment-proof-box');

                if (attachmentContainer) {
                    const item = attachmentContainer.querySelector('.attachment-item');
                    const none = attachmentContainer.querySelector('.no-attachments');

                    if (item) item.style.display = 'none';
                    if (none) none.style.display = 'none';
                }

                if (paymentProofBox) {
                    paymentProofBox.style.display = 'none';
                }
            }
        });
    </script>
@endpush