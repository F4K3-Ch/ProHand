<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('img/ProHand(Adapt).png') }}">
    <link rel="stylesheet" href="{{ asset('style/style.css') }}">
    <title>ProHand</title>
</head>
<body class="landing-page">

    <header class="navbar">
        <div class="logo">
            <img src="{{ asset('img/ProHand(Adapt).png') }}" alt="ProHand Logo" class="logo-img">
            <p>Pro<span>Hand</span></p>
        </div>

        <div class="nav-actions">
            <a href="/login" class="login-btn">Login</a>
            <a href="/register" class="register-btn">Register</a>
        </div>
    </header>

    <main class="index-info-container">

        <section class="hero">
            <h1>Simplify Your Day. <br> <span>Professional Help for Your Errands.</span></h1>
            <p>Post your task, choose your category, and get matched with verified taskers. 
            Transparent, hourly-based pricing that gets the job done right.</p>
            <a href="/register" class="get-started">Get Started Now</a>
        </section>

        <section class="services">
            <h2 class="section-title">How It <span>Works<span></h2>
            <div class="services-container">
                <div class="service-card">
                    <h3>Post Your Task</h3>
                    <p>Tell us what you need done and pick a category. Our system calculates the fair hourly rate for you.</p>
                </div>
                <div class="service-card">
                    <h3>Verified Taskers</h3>
                    <p>Work with reliable, checked professionals who are ready to handle your request efficiently.</p>
                </div>
                <div class="service-card">
                    <h3>Track & Complete</h3>
                    <p>Stay updated on progress from start to finish. Transparent, simple, and reliable service every time.</p>
                </div>
            </div>
        </section>

        <section class="final-cta">
            <h2>Ready to hand off your to-do list?</h2>
            <a href="/register" class="get-started">Join ProHand Today</a>
        </section>

        <footer class="index-footer">
            <p>&copy; 2026 ProHand. All rights reserved.</p>
        </footer>
    </main>
</body>
</html>