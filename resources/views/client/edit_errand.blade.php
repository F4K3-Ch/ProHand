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
        <div class="create-errand-container">
            <h1>Edit Errand</h1>

            <div class="error-success-message">
                <p class="error" id="errorElement" style="display: none;"></p>
            </div>

            <form id="edit-errand-form">
                <div class="create-errand-input-box">
                    <label>Title</label>
                    <input type="text" name="title" id="title" required>
                </div>

                <div class="create-errand-input-box">
                    <label>Category</label>
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

                <div class="create-errand-input-box">
                    <label>Description</label>
                    <textarea name="description" id="description" required></textarea>
                </div>

                <div class="create-errand-input-box">
                    <label>Location</label>
                    <input type="text" name="location" id="location" required>
                </div>

                <div class="create-errand-input-box">
                    <label>Deadline</label>
                    <input type="text" name="deadline" id="deadline" required>
                </div>

                <div class="create-errand-input-box">
                    <label>Fee (₱)</label>
                    <input type="number" step="0.01" name="fee" id="fee" required>
                </div>

                <div class="create-errand-input-box">
                    <label>Contact</label>
                    <textarea
                        name="contact"
                        id="contact"
                        placeholder="Phone, email, social media..."
                        required
                    ></textarea>
                </div>

                <div class="create-errand-input-box">
                    <label>Existing Attachment</label>

                    <div id="current-attachment">
                        Loading...
                    </div>
                </div>

                <div class="create-errand-input-box">
                    <label>Replace Attachment (Optional)</label>

                    <input
                        type="file"
                        name="attachment"
                        id="attachment"
                        accept=".pdf,.jpg,.jpeg,.png,.gif,.docx"
                    >

                    <small>
                        Only 1 file allowed. Maximum size: 10 MB
                    </small>
                </div>

                <button type="submit" class="btn">
                    Update Errand
                    <div
                        class="circle-spinner"
                        id="errandSpinner"
                        style="display: none;"
                    ></div>
                </button>
            </form>
        </div>
    </main>
@endsection

@push('styles')
    <link rel="stylesheet"href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
        document.addEventListener('DOMContentLoaded', async function () {
            const token = localStorage.getItem('token');
            const mainElement = document.querySelector('.view-main');
            const errandId = mainElement?.dataset.errandId;

            const form = document.getElementById('edit-errand-form');
            const errorElement = document.getElementById('errorElement');
            const spinner = document.getElementById('errandSpinner');
            const fileInput = document.getElementById('attachment');
            const currentAttachment = document.getElementById('current-attachment');

            if (!token || !errandId) {
                showError('Unable to identify the errand.');
                return;
            }

            flatpickr('#deadline', {
                enableTime: true,
                dateFormat: 'Y-m-d H:i:s',
                altInput: true,
                altFormat: 'F j, Y h:i K',
                minDate: 'today'
            });

            function escapeHtml(value) {
                const div = document.createElement('div');
                div.textContent = value ?? '';
                return div.innerHTML;
            }

            function showError(message) {
                errorElement.textContent = message;
                errorElement.style.display = 'block';
            }

            function hideError() {
                errorElement.textContent = '';
                errorElement.style.display = 'none';
            }

            fileInput.addEventListener('change', function() {
                const maxSize = 10 * 1024 * 1024; 
                if (this.files.length > 0) {
                    const fileSize = this.files[0].size;
                    if (fileSize > maxSize) {
                        errorElement.textContent = 'This file is too large! Please choose a file smaller than 10MB.';
                        errorElement.style.display = 'block';
                        this.value = '';
                    } else {
                        errorElement.textContent = '';
                        errorElement.style.display = 'none';
                    }
                }
            });

            try {
                const response = await fetch(
                    `/api/client/errands/${encodeURIComponent(errandId)}`,
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
                    throw new Error(
                        'Errand data was not returned.'
                    );
                }

                if (errand.status !== 'pending') {
                    showError(
                        'Only pending errands can be edited.'
                    );

                    form.style.display = 'none';
                    return;
                }

                document.getElementById('title').value = errand.title ?? '';
                document.getElementById('category').value = errand.category ?? '';
                document.getElementById('description').value = errand.description ?? '';
                document.getElementById('location').value = errand.location ?? '';
                document.getElementById('fee').value = errand.fee ?? '';
                document.getElementById('contact').value = errand.contact ?? '';

                if (errand.deadline) {
                    document
                        .querySelector('#deadline')
                        ._flatpickr
                        .setDate(errand.deadline, true);
                }

                if (errand.attachment) {
                    const attachmentUrl =
                        "{{ asset('storage') }}/" +
                        errand.attachment.file_path;

                    currentAttachment.innerHTML = `
                        <div class="edit-attachment-item">
                            <a
                                href="${attachmentUrl}"
                                target="_blank"
                                class="edit-attachment-name"
                                title="Preview File"
                            >
                                <i class="fa-solid fa-file"></i>
                                ${escapeHtml(
                                    errand.attachment.original_name
                                )}
                            </a>

                            <label class="delete-checkbox">
                                <input
                                    type="checkbox"
                                    name="remove_current_file"
                                    id="remove_current_file"
                                    value="1"
                                >
                                <span>Remove file</span>
                            </label>
                        </div>
                    `;
                } else {
                    currentAttachment.innerHTML = `
                        <div class="no-attachments">
                            <p class="no-attachment">None</p>
                        </div>
                    `;
                }

            } catch (error) {
                console.error(
                    'Failed to load errand:',
                    error
                );

                showError(
                    error.error ||
                    error.message ||
                    'Failed to load errand.'
                );

                form.style.display = 'none';
                return;
            }

            form.addEventListener('submit', async function (e) {
                e.preventDefault();

                hideError();
                spinner.style.display = 'inline-block';

                const formData = new FormData(form);

                formData.append('_method', 'PUT');

                try {
                    const response = await fetch(
                        `/api/client/errands/${encodeURIComponent(errandId)}`,
                        {
                            method: 'POST',
                            headers: {
                                'Authorization': 'Bearer ' + token,
                                'Accept': 'application/json'
                            },
                            body: formData
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

                    window.location.href =
                        `/client/view-errand/${errandId}`;

                } catch (error) {
                    console.error(
                        'Failed to update errand:',
                        error
                    );

                    let errorMessage =
                        'An unexpected error occurred.';

                    if (error.error) {
                        errorMessage = error.error;
                    } else if (error.message) {
                        errorMessage = error.message;
                    } else if (error.errors) {
                        const firstKey =
                            Object.keys(error.errors)[0];

                        errorMessage =
                            error.errors[firstKey][0];
                    }

                    showError(errorMessage);

                } finally {
                    spinner.style.display = 'none';
                }
            });
        });
    </script>
@endpush