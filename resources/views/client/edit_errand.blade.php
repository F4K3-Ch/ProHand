@extends('layouts.app')

@section('title', 'ProHand')

@section('sidebar')
    @include('layouts.sidebar.client')
@endsection

@section('content')
    <a href="/client/view-errand/{{ $id }}" class="btn-back">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>

    <main class="view-main" data-errand-id="{{ $id }}">
        <div class="edit-errand-container">
            <h1>Edit Errand</h1>

            <div class="view-main-error-success-message">
                <p class="error" id="errorElement" style="display: none;"></p>
            </div>

            <form id="edit-errand-form" enctype="multipart/form-data">
                @csrf

                <div class="edit-errand-input-box">
                    <label for="title">Title</label>
                    <input type="text" name="title" id="title" required>
                </div>

                <div class="edit-errand-input-box">
                    <label for="category">Category</label>
                    <select name="category" id="category" required>
                        <option value="" disabled>Select a category</option>
                        <option value="delivery">Delivery</option>
                        <option value="moving">Moving</option>
                        <option value="cleaning">Cleaning</option>
                        <option value="repair">Repair</option>
                        <option value="grocery">Grocery</option>
                        <option value="laundry">Laundry</option>
                        <option value="project">Project</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="edit-errand-input-box">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" required></textarea>
                </div>

                <div class="edit-errand-input-box">
                    <label for="location">Location</label>
                    <input type="text" name="location" id="location" required>
                </div>

                <div class="edit-errand-input-box">
                    <label for="deadline">Deadline</label>
                    <input type="text" name="deadline" id="deadline" required>
                </div>

                <div class="edit-errand-input-box">
                    <label for="fee">Fee (₱)</label>
                    <input type="number" step="0.01" min="0" name="fee" id="fee" required>
                </div>

                <div class="edit-errand-input-box">
                    <label for="contact">Contact</label>
                    <textarea name="contact" id="contact" placeholder="Phone, email, social media..." required></textarea>
                </div>

                <div class="edit-errand-input-box">
                    <label>Existing Attachment</label>

                    <div id="current-attachment">
                        <div id="current-file" class="edit-attachment-item" style="display: none;">
                            <a href="#" id="current-file-link" target="_blank" class="edit-attachment-name" title="Preview File">
                                <i class="fa-solid fa-file"></i>
                                <span id="current-file-name"></span>
                            </a>

                            <label class="delete-checkbox">
                                <input type="checkbox" name="remove_current_file" id="remove_current_file" value="1">
                                <span>Remove file</span>
                            </label>
                        </div>

                        <div id="no-current-file" class="no-attachments">
                            <p class="no-attachment">None</p>
                        </div>
                    </div>
                </div>

                <div class="edit-errand-input-box">
                    <label for="attachment">Replace Attachment (Optional)</label>
                    <input type="file" name="attachment" id="attachment" accept=".pdf,.jpg,.jpeg,.png,.gif,.docx">
                    <small>Only 1 file allowed. Maximum size: 10 MB</small>
                </div>

                <button type="submit" class="btn" id="updateErrandButton">
                    Update Errand
                    <div class="circle-spinner" id="errandSpinner" style="display: none;"></div>
                </button>
            </form>
        </div>
    </main>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', async function () {
            const mainContainer = document.querySelector('.view-main');
            const errandId = mainContainer ? mainContainer.getAttribute('data-errand-id') : null;
            
            const form = document.getElementById('edit-errand-form');
            const fileInput = document.getElementById('attachment');
            const errorElement = document.getElementById('errorElement');
            const errandSpinner = document.getElementById('errandSpinner');
            const updateErrandButton = document.getElementById('updateErrandButton');

            const fp = flatpickr('#deadline', {
                enableTime: true,
                dateFormat: 'Y-m-d H:i:s',
                altInput: true,
                altFormat: 'F j, Y h:i K',
                minDate: 'today'
            });

            if (!errandId) {
                errorElement.textContent = 'Errand ID not found.';
                errorElement.style.display = 'block';
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

                document.getElementById('title').value = errand.title || '';
                document.getElementById('category').value = errand.category ? errand.category.toLowerCase().replace(/\s+/g, '_') : '';
                document.getElementById('description').value = errand.description || '';
                document.getElementById('location').value = errand.location || '';
                document.getElementById('fee').value = errand.fee ? errand.fee.toString().replace(/[^\d.]/g, '') : '';
                document.getElementById('contact').value = errand.contact || '';
                
                if (errand.deadline && errand.deadline !== 'Not Set') {
                    fp.setDate(errand.deadline);
                }

                const currentFileDiv = document.getElementById('current-file');
                const noCurrentFileDiv = document.getElementById('no-current-file');
                if (errand.attachment) {
                    currentFileDiv.style.display = 'flex';
                    noCurrentFileDiv.style.display = 'none';
                    document.getElementById('current-file-name').textContent = errand.attachment.name;
                    document.getElementById('current-file-link').href = errand.attachment.url;
                } else {
                    currentFileDiv.style.display = 'none';
                    noCurrentFileDiv.style.display = 'block';
                }

            } catch (error) {
                console.error('Error loading errand:', error);
                errorElement.textContent = error.message;
                errorElement.style.display = 'block';
            }

            fileInput.addEventListener('change', function () {
                const maxSize = 10 * 1024 * 1024;

                if (this.files.length > 0 && this.files[0].size > maxSize) {
                    errorElement.textContent = 'This file is too large. Please choose a file smaller than 10MB.';
                    errorElement.style.display = 'block';
                    this.value = '';
                    return;
                }

                errorElement.textContent = '';
                errorElement.style.display = 'none';
            });

            form.addEventListener('submit', async function (e) {
                e.preventDefault();

                errandSpinner.style.display = 'inline-block';
                updateErrandButton.disabled = true;
                errorElement.textContent = '';
                errorElement.style.display = 'none';

                const formData = new FormData(form);

                try {
                    const response = await fetch('/api/client/errands/' + encodeURIComponent(errandId) + '/update', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                            'X-API-KEY': apiKey,
                            'X-MY-ORIGIN' : origin
                        },
                        body: formData,
                        credentials: 'same-origin'
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw data;
                    }

                    sessionStorage.setItem('success_message', data.message);
                    window.location.href = '/client/view-errand/' + errandId;

                } catch (err) {
                    let errorMessage = 'An unexpected error occurred.';

                    if (err.errors) {
                        const firstKey = Object.keys(err.errors)[0];
                        if (firstKey && err.errors[firstKey]?.length) {
                            errorMessage = err.errors[firstKey][0];
                        }
                    } else if (err.message) {
                        errorMessage = err.message;
                    } else if (err.error) {
                        errorMessage = err.error;
                    }

                    errorElement.textContent = errorMessage;
                    errorElement.style.display = 'block';
                } finally {
                    errandSpinner.style.display = 'none';
                    updateErrandButton.disabled = false;
                }
            });
        });
    </script>
@endpush