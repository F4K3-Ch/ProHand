@extends('layouts.app')

@section('title', 'ProHand')

@section('content')
<a href="{{ route('client.dashboard') }}" class="btn-back">
    <i class="fa-solid fa-arrow-left"></i> Back
</a>

<main class="view-main">
    <div class="create-errand-container">
        <h1>Create Errand</h1>

        @if ($errors->any())
            <div class="create-errand-error-success-message">
                @foreach ($errors->all() as $error)
                    <p class="error">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('errand.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="create-errand-input-box">
                <label>Title</label>
                <input type="text" name="title" value="{{ old('title') }}" required>
            </div>

            <div class="create-errand-input-box">
                <label>Description</label>
                <textarea name="description" required>{{ old('description') }}</textarea>
            </div>

            <div class="create-errand-input-box">
                <label>Deadline</label>
                <input type="text" name="deadline" id="deadline" value="{{ old('deadline') }}" required>
            </div>

            <div class="create-errand-input-box">
                <label>Contact</label>
                <textarea name="contact" placeholder="Phone, email, social media..." required>{{ old('contact') }}</textarea>
            </div>

            <div class="create-errand-input-box">
                <label>Location</label>
                <input type="text" name="location" value="{{ old('location') }}" required>
            </div>
            
            <div class="create-errand-input-box">
                <label>Fee (₱)</label>
                <input type="number" step="0.01" name="fee" value="{{ old('fee') }}" required>
            </div>

            <div class="create-errand-input-box">
                <label>Attachment (optional)</label>
                <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.gif,.docx">
                <small>Only 1 file allowed. Maximum size: 10 MB</small>
            </div>

            <button type="submit" class="btn">Create Errand</button>
        </form>
    </div>
</main>

<script>
    flatpickr("#deadline", {
        enableTime: true,
        dateFormat: "F j, Y h:i K",
        minDate: "today",
        time_24hr: false
    });
</script>
@endsection