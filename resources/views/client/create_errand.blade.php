@extends('layouts.app')

@section('title', 'ProHand')

@section('content')
    <a href="{{ route('client.dashboard') }}" class="btn-back">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>

    <main class="view-main">
        <div class="create-errand-container">
            <h1>Create Errand</h1>

            @if ($errors->any() || session('error'))
                <div class="error-success-message">
                    @foreach ($errors->all() as $error)
                        <p class="error">{{ $error }}</p>
                    @endforeach
                    
                    @if (session('error'))
                        <p class="error">{{ session('error') }}</p>
                    @endif
                </div>
            @endif

            <form method="POST" action="{{ route('errand.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="create-errand-input-box">
                    <label>Title</label>
                    <input type="text" name="title" value="{{ old('title') }}" required>
                </div>

                <div class="create-errand-input-box">
                    <label>Category</label>
                    <select name="category" id="category" required>
                        <option value="" disabled selected>Select a category</option>
                        <option value="delivery">Delivery</option>
                        <option value="moving">Moving</option>
                        <option value="cleaning">Cleaning</option>
                        <option value="repair">Repair</option>
                        <option value="tutoring">Tutoring</option>
                        <option value="pet_care">Pet Care</option>
                    </select>
                </div>

                <div class="create-errand-input-box">
                    <label>Description</label>
                    <textarea name="description" required>{{ old('description') }}</textarea>
                </div>

                <div class="create-errand-input-box">
                    <label>Expected Hours</label>
                    <input type="number" name="expected_hours" id="expected_hours" step="0.5" min="0.5" value="{{ old('expected_hours') }}" required>
                </div>

                <div class="create-errand-input-box">
                    <label>Total Price (Based on Hourly Rate)</label>
                    <input type="hidden" name="fee" id="fee_input" value="0">
                    <div id="price-display" class="price-display">
                        ₱0.00
                    </div>
                </div>

                <div class="create-errand-input-box">
                    <label>Deadline</label>
                    <input type="text" name="deadline" id="deadline" value="{{ old('deadline') }}" required>
                </div>

                <div class="create-errand-input-box">
                    <label>Location</label>
                    <input type="text" name="location" value="{{ old('location') }}" required>
                </div>

                <div class="create-errand-input-box">
                    <label>Contact</label>
                    <textarea name="contact" placeholder="Phone, email, social media..." required>{{ old('contact') }}</textarea>
                </div>

                <div class="create-errand-input-box">
                    <label>Attachment (Optional)</label>
                    <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.gif,.docx">
                    <small>Only 1 file allowed. Maximum size: 10 MB</small>
                </div>

                <button type="submit" class="btn">Create Errand</button>
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
        flatpickr("#deadline", {
            enableTime: true,
            dateFormat: "F j, Y h:i K",
            minDate: "today"
        });

        const categorySelect = document.getElementById('category');
        const hoursInput = document.getElementById('expected_hours');
        const priceDisplay = document.getElementById('price-display');

        const rates = { 
            'delivery': 50, 
            'moving': 150, 
            'cleaning': 100, 
            'repair': 200, 
            'tutoring': 250,
            'pet_care': 80
        };

        function updatePrice() {
            const rate = rates[categorySelect.value] || 0;
            const hours = hoursInput.value || 0;
            const total = rate * hours;

            priceDisplay.innerText = '₱' + total.toFixed(2);
            document.getElementById('fee_input').value = total.toFixed(2);
        }

        categorySelect.addEventListener('change', updatePrice);
        hoursInput.addEventListener('input', updatePrice);

        const fileInput = document.querySelector('input[name="attachment"]');
        
        fileInput.addEventListener('change', function() {
            const maxSize = 10 * 1024 * 1024; 
            if (this.files.length > 0) {
                const fileSize = this.files[0].size;
                if (fileSize > maxSize) {
                    alert('This file is too large! Please choose a file smaller than 10MB.');
                    this.value = '';
                }
            }
        });
    </script>
@endpush